<?php

/*
 * @Author: bluefox
 * @Motto: Running fox looking for dreams
 * @Date: 2020-12-05 21:03:30
 * @LastEditTime: 2021-09-07 13:13:50
 */
namespace app\push\controller;

use app\common\controller\PushController;
use GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;
use app\common\service\ElasticService;
use app\common\service\KlineService;
class Push extends PushController
{
    //定时器间隔
    protected static $interval = 1;
    protected static $intervals = 0.5;
    protected static $intervald = 60;
    //定时器
    protected static $timer = null;
    protected static $timers = null;
    /*
     * @var array 消息内容
     * */
    protected $message_data = ['type' => '', 'message' => ''];
    /*
     * @var string 消息类型
     * */
    protected $message_type = '';
    /*
     * @var string $client_id
     * */
    protected $client_id = '';
    /*
     * @var int 当前登陆用户
     * */
    protected $uid = null;
    /*
     * @var null 本类实例化结果
     * */
    protected static $instance = null;
    /*
     *
     * */
    public function __construct($message_data = [])
    {
    }
    /*
     * 实例化本类
     * */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    public static function elastic()
    {
        $obj = new ElasticService();
        return $obj;
    }
    /*
     * 检测参数并返回
     * @param array || string $keyValue 需要提取的键值
     * @param null || bool $value
     * @return array;
     * */
    protected function checkValue($keyValue = null, $value = null)
    {
        if (is_null($keyValue)) {
            $message_data = $this->message_data;
        }
        if (is_string($keyValue)) {
            $message_data = isset($this->message_data[$keyValue]) ? $this->message_data[$keyValue] : (is_null($value) ? '' : $value);
        }
        if (is_array($keyValue)) {
            $message_data = array_merge($keyValue, $this->message_data);
        }
        if (is_bool($value) && $value === true && is_array($message_data) && is_array($keyValue)) {
            $newData = [];
            foreach ($keyValue as $key => $item) {
                $newData[] = $message_data[$key];
            }
            return $newData;
        }
        return $message_data;
    }
    /*
     * 开始设置回调
     * @param string $typeFnName 回调函数名
     * @param string $client_id
     * @param array $message_data
     *
     * */
    public function start($typeFnName, $client_id, $message_data)
    {
        $this->message_type = $typeFnName;
        $this->message_data = $message_data;
        $this->client_id = $client_id;
        if (method_exists($this, $typeFnName)) {
            call_user_func([$this, $typeFnName]);
        } else {
            throw new \Exception('缺少回调方法');
        }
    }
    /*
     * 心跳检测
     *
     * */
    protected function ping()
    {
        // var_dump('ping-----------------');
        return;
    }
    protected function home()
    {
        $message_data = $this->message_data;
        $new_message = ['type' => 'home', 'content' => $message_data['find'], 'time' => date('H:i:s'), 'timestamp' => time()];
        Gateway::sendToClient($this->client_id, json_encode($new_message));
    }
    protected function kline()
    {
        $client_id = $this->client_id;
        $message_data = $this->message_data;
        $find = isset($message_data['find']) ? $message_data['find'] : '';
        $req = isset($message_data['req']) ? $message_data['req'] : '';
        $id = isset($message_data['id']) ? $message_data['id'] : '';
        $from = isset($message_data['from']) ? $message_data['from'] : '';
        $to = isset($message_data['to']) ? $message_data['to'] : '';
        $sub = isset($message_data['sub']) ? $message_data['sub'] : '';
        $this->uid = isset($message_data['uid']) ? $message_data['uid'] : '0';
        $groups = Gateway::getAllGroupIdList();
        if ($groups) {
            foreach ($groups as $group) {
                Gateway::leaveGroup($client_id, $group);
            }
        }
        if ($find) {
            Gateway::joinGroup($client_id, $find);
        }
        $arrr = [];
        if ($req) {
            $msgs = [];
            $arrr['req'] = $req;
            $arrr['id'] = $id;
            $pieces = explode(".", $req);
            $estable = 'market_' . $pieces[1] . '_kline_1min';
            $arrr['data'] = KlineService::search($estable, $from, $to, $pieces[3], 200);
            $code = $pieces[1];
            $type = $pieces[3];
            if (Gateway::isOnline($client_id)) {
                Gateway::sendToClient($client_id, json_encode($arrr));
            }
        }
        if ($sub) {
            $arrr['sub'] = $sub;
            $arrr['id'] = $id;
            $pieces = explode(".", $sub);
            $estable = 'market_' . $pieces[1] . '_kline_1min';
            Doing::find_product_trade($client_id, $find, $pieces[1]);
            Doing::find_product_tick($client_id, $find, $pieces[1], '1min', $this->uid);
        }
    }
    protected function groupout()
    {
        $client_id = $this->client_id;
        $message_data = $this->message_data;
        $find = isset($message_data['find']) ? $message_data['find'] : '';
        Gateway::leaveGroup($client_id, $find);
        Timer::del(self::$timer);
    }
}