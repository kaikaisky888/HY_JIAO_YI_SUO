<?php

/*
 * @Author: Fox Blue
 * @Date: 2021-08-16 11:50:11
 * @LastEditTime: 2021-09-08 11:21:50
 * @Description: Forward, no stop
 */
namespace app\common\service;

use think\facade\Db;
use think\facade\Config;
use app\common\service\HuobiRedis;
/**
 * Kline表
 */
class KlineService
{
    /**
     * 当前实例
     * @var object
     */
    protected static $instance;
    /**
     * 表前缀
     * @var string
     */
    protected $tablePrefix;
    /**
     * 构造方法
     * SystemLogService constructor.
     */
    protected function __construct()
    {
        $this->tablePrefix = Config::get('database.connections.kline.prefix');
        return $this;
    }
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    public static function hbrds()
    {
        $setredis = \think\facade\Config::get('cache.stores.redis');
        $hbrds = new HuobiRedis($setredis['host'], $setredis['port'], $setredis['password']);
        return $hbrds;
    }
    /**
     * 保存数据
     * @param $data
     * @return bool|string
     */
    public static function save($tablename, $data)
    {
        Db::startTrans();
        try {
            // $line = Db::connect('kline')->table($tablename)->where('time',$data['time'])->lock(true)->find();
            $t = self::hbrds()->get($tablename);
            if ($t != $data['time']) {
                Db::connect('kline')->table($tablename)->insert($data, true);
                self::hbrds()->set($tablename, $data['time'], 60);
                Db::commit();
            } else {
                Db::rollback();
            }
        } catch (\Throwable $e) {
            Db::rollback();
        }
        return true;
    }
    /**
     * 检测数据表
     * @return bool
     */
    public function detectTable($tablename)
    {
        $check = Db::connect('kline')->query("show tables like '{$tablename}'");
        if (empty($check)) {
            $sql = $this->getCreateSql($tablename);
            Db::connect('kline')->execute($sql);
        }
        return true;
    }
    public static function search_one($tablename, $time)
    {
        return Db::connect('kline')->name($tablename)->where('time', '<', $time)->order('time', 'desc')->field('close')->limit(1)->select();
    }
    public static function search_one_day($tablename, $time)
    {
        return Db::connect('kline')->name($tablename)->where('time', '>', $time)->order('time', 'asc')->field('close')->limit(1)->select();
    }
    public static function search($tablename, $from = null, $to = null, $type = '1min', $size = 1200)
    {
        $where[] = ['ranges', 'like', '%,' . $type . '%'];
        if ($from && $to) {
            $where[] = ['time', '>=', $from];
            $where[] = ['time', '<=', $to];
        }
        $data = Db::connect('kline')->name($tablename)->where($where)->order('time', 'desc')->limit($size)->select()->ToArray();
        $datas = [];
        $num = count($data);
        if ($data) {
            $data = array_reverse($data);
            if ($num > 1) {
                foreach ($data as $k => $v) {
                    $datas[$k]['id'] = (int) $v['time'];
                    $datas[$k]['amount'] = (double) $v['amount'];
                    if ($k == 0) {
                        $datas[$k]['open'] = (double) $data[$k]['open'];
                        $datas[$k]['close'] = (double) $data[$k + 1]['close'];
                    } else {
                        $datas[$k]['open'] = (double) $data[$k - 1]['close'];
                        $datas[$k]['close'] = (double) $data[$k]['close'];
                    }
                    $datas[$k]['high'] = (double) $v['high'];
                    $datas[$k]['low'] = (double) $v['low'];
                    $datas[$k]['vol'] = (double) $v['vol'];
                    $datas[$k]['volume'] = (double) $v['vol'];
                    $datas[$k]['count'] = (int) $v['count'];
                    $datas[$k]['time'] = (int) $v['time'];
                    $datas[$k]['isBarClosed'] = true;
                    $datas[$k]['isLastBar'] = false;
                }
            } else {
                foreach ($data as $k => $v) {
                    $datas[$k]['id'] = (int) $v['time'];
                    $datas[$k]['amount'] = (double) $v['amount'];
                    $datas[$k]['open'] = (double) $v['close'];
                    $datas[$k]['close'] = (double) $v['open'];
                    $datas[$k]['high'] = (double) $v['high'];
                    $datas[$k]['low'] = (double) $v['low'];
                    $datas[$k]['vol'] = (double) $v['vol'];
                    $datas[$k]['volume'] = (double) $v['vol'];
                    $datas[$k]['count'] = (int) $v['count'];
                    $datas[$k]['time'] = (int) $v['time'] * 1000;
                    $datas[$k]['isBarClosed'] = true;
                    $datas[$k]['isLastBar'] = false;
                }
            }
        }
        return $datas;
    }
    public static function search_day($tablename, $size = 0)
    {
        $where[] = ['ranges', 'like', '%,1min%'];
        $from = strtotime(date('Y-m-d'));
        $to = time();
        $where[] = ['time', '>=', $from];
        $where[] = ['time', '<=', $to];
        $data['high'] = Db::connect('kline')->name($tablename)->max('high');
        $data['low'] = Db::connect('kline')->name($tablename)->max('low');
        $data['volume'] = Db::connect('kline')->name($tablename)->sum('vol');
        $data['amount'] = Db::connect('kline')->name($tablename)->sum('amount');
        $data['count'] = Db::connect('kline')->name($tablename)->sum('count');
        return $data;
    }
    public static function search_svg($tablename, $type = '1min', $size = 20)
    {
        $where[] = ['ranges', 'like', '%,' . $type . '%'];
        $data = $data = Db::connect('kline')->name($tablename)->where($where)->order('time', 'desc')->limit($size)->select();
        $datas = [];
        if (isset($data)) {
            foreach ($data as $k => $v) {
                $datas[$k] = (double) $v['close'];
            }
        }
        $datas = array_reverse($datas);
        return $datas;
    }
    /**
     * 根据后缀获取创建表的sql
     * @return string
     */
    protected function getCreateSql($tablename)
    {
        return <<<EOT
CREATE TABLE `{$tablename}` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `type` varchar(50) NOT NULL COMMENT '类型',
    `symbol` varchar(30) NOT NULL COMMENT '币CODE',
    `ch` varchar(100) DEFAULT NULL COMMENT '交易对',
    `period` varchar(20) DEFAULT NULL COMMENT '分期',
    `open` decimal(30,8) DEFAULT NULL COMMENT 'OPEN',
    `close` decimal(30,8) DEFAULT NULL COMMENT 'CLOSE',
    `low` decimal(30,8) DEFAULT NULL COMMENT 'LOW',
    `high` decimal(30,8) DEFAULT NULL COMMENT 'HIGH',
    `vol` decimal(30,8) DEFAULT NULL COMMENT 'VO',
    `count` bigint(30) DEFAULT NULL COMMENT 'COUNT',
    `amount` decimal(30,8) DEFAULT NULL COMMENT 'AMOUNT',
    `time` int(11) DEFAULT NULL COMMENT 'TIME',
    `ranges` varchar(255) DEFAULT NULL COMMENT 'RANGES',
    PRIMARY KEY (`id`),
    UNIQUE KEY `time` (`time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Kline表';
EOT;
    }
}