<?php

/*
 * @Author: Fox Blue
 * @Date: 2021-07-03 16:39:14
 * @LastEditTime: 2021-08-15 03:02:56
 * @Description: Forward, no stop
 */
namespace app\common\service;

class HuobiRedis
{
    private $redis;
    private $host;
    //redis ip
    private $port;
    //redis 端口
    private $pass;
    //redis 密码
    public $db = null;
    public function __construct($host, $port, $pass)
    {
        $this->host = $host;
        $this->port = $port;
        //连接redis
        if (class_exists('Redis')) {
            $this->redis = new \Redis();
            if ($this->redis->pconnect($this->host, $this->port, $this->pass)) {
                $this->connect = true;
            }
        } else {
            exit('redis扩展不存在');
        }
    }
    /**
     * 设置过期时间
     * @param  string or int $key 键名
     * @param  int $expire 过期时间(秒)
     * @return bool 返回布尔值  [如果成功返回true,如果键不存在或已过期则返回false]
     */
    public function expire($key, $expire = 0)
    {
        $expire = (int) $expire ? $expire : $this->redis->{$expire};
        if ($this->redis->expire($key, $expire)) {
            return true;
        }
        return false;
    }
    /**
     * Notes: 向has表中写入数据
     * User: ${USER}
     * Date: ${DATE}
     * Time: ${TIME}
     * @param $table
     */
    public function write($table, $data)
    {
        //批量设置
        return $this->redis->hMset($table, $data);
    }
    /**
     * Notes:查找是否存在重复 id 存在重复就更新覆盖，如果不重复 就取出保存到mysql中
     * User: ${USER}
     * Date: ${DATE}
     * Time: ${TIME}
     * @param $table
     * @param $id
     * @return int
     */
    public function SeachId($table, $id)
    {
        $idtime = $this->redis->hGet($table, 'time');
        // 获取h表中时间字段value
        if ($idtime) {
            if ($idtime == $id) {
                return 1;
                //存在相等
            } else {
                return 2;
                //不相等
            }
        }
        return 0;
        //不存在
    }
    /**
     * Notes: 读取缓存has表中的数据
     * User: ${USER}
     * Date: ${DATE}
     * Time: ${TIME}
     * @param $table
     * @return array
     */
    public function read($table)
    {
        return $this->redis->hGetAll($table);
    }
    public function read_list($table)
    {
        $info = $this->redis->hGetAll($table);
        if ($info) {
            $infos = [];
            $infos['id'] = (int) $info['time'];
            $infos['open'] = (double) $info['open'];
            $infos['close'] = (double) $info['close'];
            $infos['high'] = (double) $info['high'];
            $infos['low'] = (double) $info['low'];
            $infos['vol'] = (double) $info['vol'];
            $infos['volume'] = (double) $info['vol'];
            $infos['amount'] = (double) $info['amount'];
            $infos['count'] = (int) $info['count'];
            return $infos;
        }
    }
    /**
     * 存储一个键值
     * @param  string or int $key 键名
     * @param  mix $value 键值，支持数组、对象
     * @param  int $expire 过期时间(秒)
     * @return bool 返回布尔值
     */
    public function set($key, $value, $expire = '')
    {
        if (is_int($key) || is_string($key)) {
            //如果是int类型的数字就不要序列化，否则用自增自减功能会失败，
            //如果不序列化，set()方法只能保存字符串和数字类型,
            //如果不序列化，浮点型数字会有失误，如13.6保存，获取时是13.59999999999
            $value = is_int($value) ? $value : serialize($value);
            $expire = (int) $expire ? $expire : $this->redis->{$expire};
            if ($this->redis->set($key, $value) && $this->redis->expire($key, $expire)) {
                return true;
            }
            return false;
        }
        return false;
    }
    /**
     * 获取键值
     * @param string or int $key 键名
     * @return mix 返回值
     */
    public function get($key)
    {
        $value = $this->redis->get($key);
        if (is_object($value)) {
            return $value;
        }
        return is_numeric($value) ? $value : unserialize($value);
    }
    /**
     * 删除一个键值
     * @param  string or int $key 键名
     * @return int 成功返回1 ，失败或不存在键返回0
     */
    public function del($key)
    {
        return $this->redis->del($key);
    }
    /**
     * 截取字符串,支持汉字
     * @param  string or int $key 键名
     * @param  int $start 起始位，从0开始
     * @param  int $end   截取长度
     * @return string   返回字符串,如果键不存在或值不是字符串类型则返回false
     */
    public function substr($key, $start, $end = 0)
    {
        $value = $this->redis->get($key);
        if ($value && is_string($value)) {
            if ($end) {
                return mb_substr($value, $start, $end);
            }
            return mb_substr($value, $start);
        }
        return false;
    }
    /**
     * 设置指定 key 的值，并返回 key 的旧值
     * @param  string or int  $key 键名
     * @param  mix  $value 要指定的健值，支持数组
     * @param  int $expire 过期时间，如果不填则用全局配置
     * @return mix 返回旧值，如果旧值不存在则返回false,并新创建key的键值
     */
    public function replace($key, $value, $expire = 0)
    {
        $value = $this->redis->getSet($key, $value);
        $expire = (int) $expire ? $expire : $this->redis->{$expire};
        $this->redis->expire($key, $expire);
        return is_numeric($value) ? $value : unserialize($value);
    }
    /**
     * 同时设置一个或多个键值对。（支持数组值）
     * @param  array  $arr [要设置的键值对数组]
     * @return bool 返回布尔值，成功true否则false
     */
    public function mset($arr)
    {
        if ($arr && is_array($arr)) {
            foreach ($arr as &$value) {
                $value = is_int($value) ? $value : serialize($value);
            }
            if ($this->redis->mset($arr)) {
                return true;
            }
            return false;
        }
        return false;
    }
    /**
     * 返回所有(一个或多个)给定 key 的值
     * 可传入一个或多个键名参数，键名字符串类型，如 $values = $redis::mget('one','two','three', ...);
     * @return 返回包含所有指定键值数组，如果不存在则返回false
     */
    public function mget()
    {
        $keys = func_get_args();
        if ($keys) {
            $values = $this->redis->mget($keys);
            if ($values) {
                foreach ($values as &$value) {
                    $value = is_numeric($value) ? $value : unserialize($value);
                }
                return $values;
            }
        }
        return false;
    }
    /**
     * 查询剩余过期时间（秒）
     * @param  string or int $key  键名
     * @return int 返回剩余的时间，如果已过期则返回负数
     */
    public function expiretime($key)
    {
        return $this->redis->ttl($key);
    }
    /**
     * 指定的 key 不存在时，为 key 设置指定的值(SET if Not eXists)
     * @param  string or int $key  键名
     * @param  mix  $value 要指定的健值，支持数组
     * @param  int $expire 过期时间，如果不填则用全局配置
     * @return bool  设置成功返回true 否则false
     */
    public function setnx($key, $value, $expire = 0)
    {
        $value = is_int($value) ? $value : serialize($value);
        $res = $this->redis->setnx($key, $value);
        if ($res) {
            $expire = (int) $expire ? $expire : $this->redis->{$expire};
            $this->redis->expire($key, $expire);
        }
        return $res;
    }
    /**
     * 返回对应键值的长度
     * @param  string or int $key  键名
     * @return int  返回字符串的长度，如果键值是数组则返回数组元素的个数，如果键值不存在则返回0
     */
    public function valuelen($key)
    {
        $value = $this->redis->get($key);
        $lenth = 0;
        if ($value) {
            if (is_array($value)) {
                $lenth = count($value);
            } else {
                $lenth = strlen($value);
            }
        }
        return $lenth;
    }
    /**
     * 将 key 中储存的数字值自增
     * @param  string or int $key  键名
     * @param  int $int 自增量，如果不填则默认是自增量为 1
     * @return int  返回自增后的值，如果键不存在则新创建一个值为0，并在此基础上自增，返回自增后的数值.如果键值不是可转换的整数，则返回false
     */
    public function inc($key, $int = 0)
    {
        if ((int) $int) {
            return $this->redis->incrby($key, $int);
        } else {
            return $this->redis->incr($key);
        }
    }
    /**
     * 将 key 中储存的数字值自减
     * @param  string or int $key  键名
     * @param  int $int 自减量，如果不填则默认是自减量为 1
     * @return int  返回自减后的值，如果键不存在则新创建一个值为0，并在此基础上自减，返回自减后的数值.如果键值不是可转换的整数，则返回false
     */
    public function dec($key, $int = 0)
    {
        if ((int) $int) {
            return $this->redis->decrby($key, $int);
        } else {
            return $this->redis->decr($key);
        }
    }
    /**
     * 为指定的 key 追加值
     * @param  string or int $key  键名
     * @param  mix  $value 要指定的健值，支持数组
     * @param  bool $pos 要追加的位置，默认false为追加至末尾，true则追加到开头
     * @param  int $expire 过期时间，如果不填则用全局配置
     * @return bool  设置成功返回true 否则false,支付向字符串或者数组追加内容，向字符串追加时加入的值必须为字符串类型，如果健不存在则创建新的键值对
     */
    public function append($key, $value, $pos = false, $expire = 0)
    {
        $cache = $this->redis->get($key);
        if ($cache) {
            if (is_array($cache)) {
                if ($pos === true) {
                    $value = array_unshift($cache, $value);
                } else {
                    $value = array_push($cache, $value);
                }
            } else {
                if (!is_string($value)) {
                    return false;
                }
                if ($pos === true) {
                    $value .= $cache;
                } else {
                    $value = $cache . $value;
                }
            }
        }
        return $this->redis->set($key, $value, $expire);
    }
    // +--------------------------------------------------
    // | 以上方法均为字符串常用方法，并且把数组也兼容了
    // | 以下为哈希表处理方法
    // +--------------------------------------------------
    /**
     * 为哈希表中的字段赋值
     * @param  string  $table  哈希表名
     * @param  string  $column 字段名
     * @param  string|array  $value  字段值
     * @param  int $expire 过期时间, 如果不填则不设置过期时间
     * @return int  如果成功返回 1，否则返回 0.当字段值已存在时覆盖旧值并且返回 0
     */
    public function hset($table, $column, $value, $expire = 0)
    {
        $value = is_array($value) ? json_encode($value) : $value;
        $res = $this->redis->hset($table, $column, $value);
        if ((int) $expire) {
            $this->redis->expire($table, $expire);
        }
        return $res;
    }
    /**
     * 获取哈希表字段值
     * @param  string $table  表名
     * @param  string $column 字段名
     * @return mix  返回字段值，如果字段值是数组保存的返回json格式字符串，转换成数组json_encode($value),如果字段不存在返回false;
     */
    public function hget($table, $column)
    {
        return $this->redis->hget($table, $column);
    }
    /**
     * 删除哈希表 key 中的一个或多个指定字段，不存在的字段将被忽略
     * @param  string $table  表名
     * @param  string $column 字段名
     * @return int  返回被成功删除字段的数量，不包括被忽略的字段,(删除哈希表用$this->redis->del($table))
     */
    public function hdel($table, $columns)
    {
        $columns = func_get_args();
        $table = $columns[0];
        $count = count($columns);
        $num = 0;
        for ($i = 1; $i < $count; $i++) {
            $num += $this->redis->hdel($table, $columns[$i]);
        }
        return $num;
    }
    /**
     * 查看哈希表的指定字段是否存在
     * @param  string $table  表名
     * @param  string $column 字段名
     * @return bool  存在返回true,否则false
     */
    public function hexists($table, $column)
    {
        if ((int) $this->redis->hexists($table, $column)) {
            return true;
        }
        return false;
    }
    /**
     * 返回哈希表中，所有的字段和值
     * @param  string $table 表名
     * @return array   返回键值对数组
     */
    public function hgetall($table)
    {
        return $this->redis->hgetall($table);
    }
    /**
     * 为哈希表中的字段值加上指定增量值(支持整数和浮点数)
     * @param  string $table  表名
     * @param  string $column 字段名
     * @param  int $num  增量值，默认1, 也可以是负数值,相当于对指定字段进行减法操作
     * @return int|float|bool  返回计算后的字段值,如果字段值不是数字值则返回false,如果哈希表不存在或字段不存在返回false
     */
    public function hinc($table, $column, $num = 1)
    {
        $value = $this->redis->hget($table, $column);
        if (is_numeric($value)) {
            //数字类型，包括整数和浮点数
            $value += $num;
            $this->redis->hset($table, $column, $value);
            return $value;
        } else {
            return false;
        }
    }
    /**
     * 获取哈希表中的所有字段
     * @param  string $table  表名
     * @return array  返回包含所有字段的数组
     */
    public function hkeys($table)
    {
        return $this->redis->hkeys($table);
    }
    /**
     * 返回哈希表所有域(field)的值
     * @param  string $table  表名
     * @return array  返回包含所有字段值的数组,数字索引
     */
    public function hvals($table)
    {
        return $this->redis->hvals($table);
    }
    /**
     * 获取哈希表中字段的数量
     * @param  string $table  表名
     * @return int 如果哈希表不存在则返回0
     */
    public function hlen($table)
    {
        return $this->redis->hlen($table);
    }
    /**
     * 获取哈希表中，一个或多个给定字段的值
     * @param  string $table  表名
     * @param  string $columns 字段名
     * @return array  返回键值对数组，如果字段不存在则字段值为null, 如果哈希表不存在返回空数组
     */
    public function hmget($table, $columns)
    {
        $data = $this->redis->hgetall($table);
        $result = [];
        if ($data) {
            $columns = func_get_args();
            unset($columns[0]);
            foreach ($columns as $value) {
                $result[$value] = isset($data[$value]) ? $data[$value] : null;
            }
        }
        return $result;
    }
    /**
     * 同时将多个 field-value (字段-值)对设置到哈希表中
     * @param  string $table  表名
     * @param  array $data  要添加的键值对
     * @param  int $expire  过期时间，不填则不设置过期时间
     * @return bool 成功返回true,否则false
     */
    public function hmset($table, array $data, $expire = 0)
    {
        $result = $this->redis->hmset($table, $data);
        if ((int) $expire) {
            $this->redis->expire($table, $expire);
        }
        return $result;
    }
    /**
     * 为哈希表中不存在的的字段赋值
     * @param  string  $table  哈希表名
     * @param  string  $column 字段名
     * @param  string|array  $value  字段值
     * @param  int $expire 过期时间, 如果不填则不设置过期时间
     * @return bool  如果成功返回true，否则返回 false.
     */
    public function hsetnx($table, $column, $value, $expire = 0)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $result = $this->redis->hsetnx($table, $column, $value);
        if ((int) $expire) {
            $this->redis->expire($table, $expire);
        }
        return $result;
    }
    // +--------------------------------------------------
    // | 以上方法均为哈希表常用方法
    // | 以下为列表处理方法
    // +--------------------------------------------------
    /**
     * 将一个或多个值插入到列表头部（值可重复）或列表尾部。如果列表不存在，则创建新列表并插入值
     * @param  string  $list  列表名
     * @param  string|array  $value  要插入的值,如果是多个值请放入数组传入
     * @param  string $pop 要插入的位置，默认first头部,last表示尾部
     * @param  int $expire 过期时间, 如果不填则不设置过期时间
     * @return int  返回列表的长度
     */
    public function lpush($list, $value, $pop = 'first', $expire = 0)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $result = $pop == 'last' ? $this->redis->rpush($list, $v) : $this->redis->lpush($list, $v);
            }
        } else {
            $result = $pop == 'last' ? $this->redis->rpush($list, $value) : $this->redis->lpush($list, $value);
        }
        if ((int) $expire) {
            $this->redis->expire($list, $expire);
        }
        return $result;
    }
    /**
     * 通过索引获取列表中的元素
     * @param  string  $list  列表名
     * @param  int  $index  索引位置，从0开始计,默认0表示第一个元素，-1表示最后一个元素索引
     * @return string  返回指定索引位置的元素
     */
    public function lindex($list, $index = 0)
    {
        return $this->redis->lindex($list, $index);
    }
    /**
     * 通过索引来设置元素的值
     * @param  string $list  列表名
     * @param  string $value 元素值
     * @param  int $index 索引值
     * @return bool  成功返回true,否则false.当索引参数超出范围，或列表不存在返回false。
     */
    public function lset($list, $index, $value)
    {
        return $this->redis->lset($list, $index, $value);
    }
    /**
     * 返回列表中指定区间内的元素
     * @param  string  $list  列表名
     * @param  int  $start  起始位置，从0开始计,默认0
     * @param  int  $end 结束位置，-1表示最后一个元素，默认-1
     * @return array  返回列表元素数组
     */
    public function lrange($list, $start = 0, $end = -1)
    {
        return $this->redis->lrange($list, $start, $end);
    }
    /**
     * 返回列表的长度
     * @param  string $list 列表名
     * @return int  列表长度
     */
    public function llen($list)
    {
        return $this->redis->llen($list);
    }
    /**
     * 移出并获取列表的第一个元素或最后一个元素
     * @param  string $list 列表名
     * @param  string $pop 移出并获取的位置，默认first为第一个元素
     * @return string|bool  列表第一个元素或最后一个元素,如果列表不存在则返回false
     */
    public function lpop($list, $pop = 'first')
    {
        if ($pop == 'last') {
            return $this->redis->rpop($list);
        }
        return $this->redis->lpop($list);
    }
    /**
     * 从列表中弹出最后一个值，将弹出的元素插入到另外一个列表开头并返回这个元素
     * @param  string $list1  要弹出元素的列表名
     * @param  string $list2  要接收元素的列表名
     * @return string|bool  返回被弹出的元素,如果其中有一个列表不存在则返回false
     */
    public function lpoppush($list1, $list2)
    {
        if ($this->redis->lrange($list1) && $this->redis->lrange($list2)) {
            return $this->redis->brpoplpush($list1, $list2, 500);
        }
        return false;
    }
    /**
     * 用于在指定的列表元素前或者后插入元素。如果元素有重复则选择第一个出现的。当指定元素不存在于列表中时，不执行任何操作
     * @param  string $list   列表名
     * @param  string $element 指定的元素
     * @param  string $value   要插入的元素
     * @param  string $pop     要插入的位置，before前,after后。默认before
     * @return int  返回列表的长度。 如果没有找到指定元素 ，返回 -1 。 如果列表不存在或为空列表，返回 0 。
     */
    public function linsert($list, $element, $value, $pop = 'before')
    {
        return $this->redis->linsert($list, $pop, $element, $value);
    }
    /**
     * 移除列表中指定的元素
     * @param  string  $list  列表名
     * @param  string  $element  指定的元素
     * @param  int $count  要删除的个数，0表示删除所有指定元素，负整数表示从表尾搜索, 默认0
     * @return int  被移除元素的数量。 列表不存在时返回 0
     */
    public function lrem($list, $element, $count = 0)
    {
        return $this->redis->lrem($list, $count, $element);
    }
    /**
     * 让列表只保留指定区间内的元素，不在指定区间之内的元素都将被删除
     * @param  string $list  列表名
     * @param  int $start 起始位置，从0开始
     * @param  int $stop  结束位置，负数表示倒数第n个
     * @return bool  成功返回true否则false
     */
    public function ltrim($list, $start, $stop)
    {
        return $this->redis->ltrim($list, $start, $stop);
    }
    // +--------------------------------------------------
    // | 以上方法均为列表常用方法
    // | 以下为集合处理方法(集合分为有序和无序集合)
    // +--------------------------------------------------
    /**
     * 将一个或多个元素加入到无序集合中，已经存在于集合的元素将被忽略.如果集合不存在，则创建一个只包含添加的元素作成员的集合。
     * @param  string $set  集合名称
     * @param  string|array $value 元素值（唯一）,如果要加入多个元素请传入多个元素的数组
     * @return int  返回被添加元素的数量.如果$set不是集合类型时返回0
     */
    public function sadd($set, $value)
    {
        $num = 0;
        if (is_array($value)) {
            foreach ($value as $key => $v) {
                $num += $this->redis->sadd($set, $v);
            }
        } else {
            $num += $this->redis->sadd($set, $value);
        }
        return $num;
    }
    /**
     * 返回无序集合中的所有的成员
     * @param  string $set 集合名称
     * @return  array  返回包含所有成员的数组
     */
    public function smembers($set)
    {
        return $this->redis->smembers($set);
    }
    /**
     * 获取集合中元素的数量。
     * @param  string $set 集合名称
     * @return int  返回集合的成员数量
     */
    public function scard($set)
    {
        return $this->redis->scard($set);
    }
    /**
     * 移除并返回集合中的一个随机元素
     * @param  string $set 集合名称
     * @return string|bool  返回移除的元素,如果集合为空则返回false
     */
    public function spop($set)
    {
        return $this->redis->spop($set);
    }
    /**
     * 移除集合中的一个或多个成员元素，不存在的成员元素会被忽略
     * @param  string $set 集合名称
     * @param  string|array $member 要移除的元素，如果要移除多个请传入多个元素的数组
     * @return int  返回被移除元素的个数
     */
    public function srem($set, $member)
    {
        $num = 0;
        if (is_array($member)) {
            foreach ($member as $value) {
                $num += $this->redis->srem($set, $value);
            }
        } else {
            $num += $this->redis->srem($set, $member);
        }
        return $num;
    }
    /**
     * 返回集合中的一个或多个随机元素
     * @param  string $set   集合名称
     * @param  int $count 要返回的元素个数，0表示返回单个元素，大于等于集合基数则返回整个元素数组。默认0
     * @return string|array   返回随机元素，如果是返回多个则为数组返回
     */
    public function srand($set, $count = 0)
    {
        return (int) $count == 0 ? $this->redis->srandmember($set) : $this->redis->srandmember($set, $count);
    }
    /**
     * 返回给定集合之间的差集(集合1对于集合2的差集)。不存在的集合将视为空集
     * @param  string $set1 集合1名称
     * @param  string $set2 集合2名称
     * @return array  返回差集（即筛选存在集合1中但不存在于集合2中的元素）
     */
    public function sdiff($set1, $set2)
    {
        return $this->redis->sdiff($set1, $set2);
    }
    /**
     * 将给定集合set1和set2之间的差集存储在指定的set集合中。如果指定的集合已存在，则会被覆盖。
     * @param  string $set  指定存储的集合
     * @param  string $set1 集合1
     * @param  string $set2 集合2
     * @return int  返回指定存储集合元素的数量
     */
    public function sdiffstore($set, $set1, $set2)
    {
        return $this->redis->sdiffstore($set, $set1, $set2);
    }
    /**
     * 返回set1集合和set2集合的交集（即筛选同时存在集合1和集合2中的元素）
     * @param  string $set1 集合1
     * @param  string $set2 集合2
     * @return array  返回包含交集元素的数组
     */
    public function sinter($set1, $set2)
    {
        return $this->redis->sinter($set1, $set2);
    }
    /**
     * 将给定集合set1和set2之间的交集存储在指定的set集合中。如果指定的集合已存在，则会被覆盖。
     * @param  string $set  指定存储的集合
     * @param  string $set1 集合1
     * @param  string $set2 集合2
     * @return int  返回指定存储集合元素的数量
     */
    public function sinterstore($set, $set1, $set2)
    {
        return $this->redis->sinterstore($set, $set1, $set2);
    }
    /**
     * 判断成员元素是否是集合的成员
     * @param  string $set   集合名称
     * @param  string $member 要判断的元素
     * @return bool 如果成员元素是集合的成员返回true,否则false
     */
    public function sismember($set, $member)
    {
        return $this->redis->sismember($set, $member);
    }
    /**
     * 将元素从集合1中移动到集合2中
     * @param  string $set1 集合1
     * @param  string $set2 集合2
     * @param  string $member 要移动的元素成员
     * @return bool  成功返回true,否则false
     */
    public function smove($set1, $set2, $member)
    {
        return $this->redis->smove($set1, $set2, $member);
    }
    /**
     * 返回集合1和集合2的并集(即两个集合合并后去重的结果)。不存在的集合被视为空集。
     * @param  string $set1 集合1
     * @param  string $set2 集合2
     * @return array  返回并集数组
     */
    public function sunion($set1, $set2)
    {
        return $this->redis->sunion($set1, $set2);
    }
    /**
     * 将给定集合set1和set2之间的并集存储在指定的set集合中。如果指定的集合已存在，则会被覆盖。
     * @param  string $set  指定存储的集合
     * @param  string $set1 集合1
     * @param  string $set2 集合2
     * @return int  返回指定存储集合元素的数量
     */
    public function sunionstore($set, $set1, $set2)
    {
        return $this->redis->sunionstore($set, $set1, $set2);
    }
    /* ----------------------以下有序集合----------------------- */
    /**
     * 将一个或多个成员元素及其分数值加入到有序集当中，如果成员已存在则更新它的分数值，如果集合不存在则创建
     * @param  string $set    集合名称
     * @param  array $arr  元素（唯一）与其分数值（分数值可以是整数值或双精度浮点数）组成的数组
     * @return int  返回添加成功的成员数量
     */
    public function zadd($set, $arr)
    {
        $num = 0;
        if ($arr && is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (is_numeric($value)) {
                    $num += $this->redis->zadd($set, $value, $key);
                }
            }
        }
        return $num;
    }
    /**
     * 返回有序集中，指定区间内的成员。默认返回所有成员(默认升序排列)
     * @param  string  $set   集合名称
     * @param  int  $start 起始位置，从0开始计，默认0
     * @param  int $stop  结束位置，-1表示最后一位，默认-1
     * @param  bool $desc 排序，false默认升序，true为倒序
     * @return array  返回有序集合中指定区间内的成员数组（默认返回所有）
     */
    public function zrange($set, $start = 0, $stop = -1, $desc = false)
    {
        if ($desc === true) {
            return $this->redis->ZREVRANGE($set, $start, $stop, 'WITHSCORES');
        }
        return $this->redis->zrange($set, $start, $stop, 'withscores');
    }
    /**
     * 返回有序集合中成员数量
     * @param  string $set 集合名称
     * @return int  返回成员的数量
     */
    public function zcard($set)
    {
        return $this->redis->zcard($set);
    }
    /**
     * 计算有序集合中指定分数区间的成员数量
     * @param  string $set 集合名称
     * @param  int|float $min 最小分数值
     * @param  int|float $max 最大分数值
     * @return int  返回指定区间的成员数量
     */
    public function zcount($set, $min, $max)
    {
        return $this->redis->zcount($set, $min, $max);
    }
    /**
     * 对有序集合中指定成员的分数加上增量
     * @param  string $set 集合名称
     * @param  string  $member 指定元素
     * @param  int $num   增量值，负数表示进行减法运算，默认1
     * @return float  返回运算后的分数值（浮点型）
     */
    public function zinc($set, $member, $num = 1)
    {
        return $this->redis->zincrby($set, $num, $member);
    }
    /**
     * 计算set1和set2有序集的交集，并将该交集(结果集)储存到新集合set中。
     * @param  string $set  指定存储的集合名称
     * @param  string $set1 集合1
     * @param  string $set2 集合2
     * @return int  返回保存到目标结果集的的成员数量
     */
    public function zinterstore($set, $set1, $set2)
    {
        return $this->redis->zinterstore($set, 2, $set1, $set2);
    }
    /**
     * 计算set1和set2有序集的并集，并将该并集(结果集)储存到新集合set中。
     * @param  string $set  指定存储的集合名称
     * @param  string $set1 集合1
     * @param  string $set2 集合2
     * @return int  返回保存到目标结果集的的成员数量
     */
    public function zunionstore($set, $set1, $set2)
    {
        return $this->redis->zunionstore($set, 2, $set1, $set2);
    }
    /**
     * 返回有序集合中指定分数区间的成员列表。有序集成员按分数值递增(从小到大)次序排列
     * @param  string $set   集合名称
     * @param  string $min  最小分数值字符串表示，'1'表示>=1，(1'表示>1
     * @param  string $max  最大分数值字符串表示,'100'表示<=1，(100'表示<100
     * @param  bool $withscores  返回的数组是否包含分数值，默认true, false不包含
     * @return array   返回指定区间的成员，默认是元素=>分数值的键值对数组。如果只要返回包含元素的数组请设置$withscores=false
     */
    public function zrangebyscore($set, $min, $max, $withscores = true)
    {
        if ($withscores === true) {
            return $this->redis->zrangebyscore($set, $min, $max, ['withscores' => '-inf']);
        }
        return $this->redis->zrangebyscore($set, $min, $max);
    }
    /**
     * 返回有序集中指定成员的排名。其中有序集成员按分数值递增(从小到大)顺序排列。
     * @param  string $set    集合名称
     * @param  string $member 成员名称（元素）
     * @return int|bool   返回 member 的排名, 如果member不存在返回false
     */
    public function zrank($set, $member)
    {
        return $this->redis->zrank($set, $member);
    }
    /**
     * 移除有序集中的一个或多个成员，不存在的成员将被忽略。
     * @param  string $set     有序集合名称
     * @param  string|array $members 要移除的成员，如果要移除多个请传入多个成员的数组
     * @return int   返回被移除的成员数量，不存在的成员将被忽略
     */
    public function zrem($set, $members)
    {
        $num = 0;
        if (is_array($members)) {
            foreach ($members as $value) {
                $num += $this->redis->zrem($set, $value);
            }
        } else {
            $num += $this->redis->zrem($set, $members);
        }
        return $num;
    }
    /**
     * 移除有序集中，指定分数（score）区间内的所有成员。
     * @param  string $set   集合名称
     * @param  int $min 最小分数值
     * @param  int $max 最大分数值
     * @return int  返回被移除的成员数量
     */
    public function zrembyscore($set, $min, $max)
    {
        return $this->redis->zremrangebyscore($set, $min, $max);
    }
    /**
     * 移除有序集中，指定排名(rank)区间内的所有成员（这个排名数字越大排名越高，最低排名0开始）
     * @param  string $set   集合名称
     * @param  int $min 最小排名，从0开始计
     * @param  int $max 最大排名
     * @return int  返回被移除的成员数量，如移除排名为倒数5名的成员：$redis::zremrank($set,0,4);
     */
    public function zrembyrank($set, $min, $max)
    {
        return $this->redis->zremrangebyrank($set, $min, $max);
    }
    /**
     * 返回有序集中，成员的分数值。
     * @param  string $set    集合名称
     * @param  string $member 成员
     * @return float|bool   返回分数值(浮点型)，如果成员不存在返回false
     */
    public function zscore($set, $member)
    {
        return $this->redis->zscore($set, $member);
    }
    /**
     * 开启事务
     */
    public function transation()
    {
        $this->redis->multi();
    }
    /**
     * 提交事务
     */
    public function commit()
    {
        $this->redis->exec();
    }
    /**
     * 取消事务
     */
    public function discard()
    {
        $this->redis->discard();
    }
}
// $huobiredis= new HuobiRedis("127.0.0.1",6379);
// $huobiredis->huobi1min();
//   $ids= $huobiredis->SeachId("klin1mina",1556160420);
// $tables=$huobiredis->Read("klin1min");
/*  $data=array (
      'id' => 1556150400,
      'open' => 5413.0,
      'close' => 5409.02,
      'low' => 5404.12,
      'high' => 5436.0,
      'amount' => 1776.0156640772,
      'vol' => 9631184.0369913,
      'count' => 15163,
  );
  $huobiredis->write("klin1mins",$data)*/