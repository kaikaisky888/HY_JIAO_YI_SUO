<?php

/*
 * @Author: bluefox
 * @Motto: Running fox looking for dreams
 * @Date: 2020-12-05 20:48:14
 * @LastEditTime: 2021-09-14 13:09:02
 */
namespace app\push\controller;

use app\common\controller\PushController;
use GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;
class Events extends PushController
{
    private $connections;
    //定时器间隔
    protected static $interval = 2;
    //定时器
    protected static $timer = null;
    //固定定时线程数字
    protected static $timework = 1;
    //事件处理类
    protected static $evevtRunClass = \app\push\controller\Task::class;
    protected $client_id = '';
    /*
     * 消息事件回调 class
     *
     * */
    protected static $eventClassName = \app\push\controller\Push::class;
    /**
     * 写调试日志
     */
    public static function debugLog($msg)
    {
        $logFile = '/tmp/binance_debug.log';
        $time = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[{$time}] {$msg}\n", FILE_APPEND);
    }

    public static function onWorkerStart($worker)
    {
        self::debugLog("=== onWorkerStart called, worker id={$worker->id}, name={$worker->name} ===");

        if ($worker->id < self::$timework) {
            $last = time();
            $task = [1 => $last, 5 => $last, 30 => $last, 60 => $last, 180 => $last, 3600 => $last, 10800 => $last];
            self::$timer = Timer::add(self::$interval, function () use(&$task) {
                try {
                    $now = time();
                    foreach ($task as $sec => &$time) {
                        if ($now - $time >= $sec) {
                            $time = $now;
                            $className = self::$evevtRunClass . '::instance';
                            if (is_callable($className)) {
                                $className()->start($sec);
                            } else {
                                throw new \Exception('回调不存在。[' + $className + ']');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    self::debugLog("Timer error: " . $e->getMessage());
                }
            });
        }

        // 异步建立 HTX (火币) WebSocket 连接
        try {
            $htxWsUrl = sysconfig('api', 'huobi_api');
            self::debugLog("huobi_api config = [{$htxWsUrl}]");

            if (empty($htxWsUrl)) {
                $htxWsUrl = 'wss://api.htx.com/ws';
                self::debugLog("huobi_api is empty, using default: {$htxWsUrl}");
            }

            $symbolStr = sysconfig('api', 'huobi_symbol');
            self::debugLog("huobi_symbol config = [{$symbolStr}]");

            if (empty($symbolStr)) {
                self::debugLog("ERROR: huobi_symbol is empty!");
                return;
            }

            $make = array_unique(array_filter(array_map('trim', explode(',', $symbolStr))));
            self::debugLog("Parsed symbols (unique): " . implode(', ', $make));

            // 保存symbols到静态变量，供onConnect时订阅
            \app\push\controller\Huobi::$symbols = $make;

            $con = new AsyncTcpConnection($htxWsUrl);
            $con->transport = 'ssl';

            $con->onConnect = function ($con) {
                self::debugLog(">>> HTX WebSocket CONNECTED!");
                \app\push\controller\Huobi::onAsyncConnect($con);
            };
            $con->onMessage = function ($con, $message) use($worker) {
                static $msgCount = 0;
                $msgCount++;
                if ($msgCount <= 5 || $msgCount % 100 === 0) {
                    self::debugLog("Msg #{$msgCount}, len=" . strlen($message));
                }
                try {
                    \app\push\controller\Huobi::onAsyncMessage($con, $message, $worker);
                } catch (\Throwable $e) {
                    self::debugLog("onAsyncMessage ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
                }
            };
            $con->onClose = function ($con) {
                self::debugLog("<<< HTX WebSocket DISCONNECTED, will reconnect in 3s");
                $con->reConnect(3);
            };
            $con->onError = function ($con, $code, $msg) {
                self::debugLog("!!! HTX WebSocket ERROR: code={$code}, msg={$msg}");
            };

            self::debugLog("Calling connect() to {$htxWsUrl}...");
            $con->connect();
            self::debugLog("connect() called OK");

        } catch (\Throwable $e) {
            self::debugLog("FATAL onWorkerStart error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
        // 每天2点10分运行一次
        // new Crontab('30 2 * * *', function(){
        //     Doing::goods_up();
        // });
        // 注销
        // $crontab->destroy();
    }
    /**
     * onConnect 事件回调
     * 当客户端连接上gateway进程时(TCP三次握手完毕时)触发
     *
     * @access public
     * @param  int       $client_id
     * @return void
     */
    public static function onConnect($client_id)
    {
        Gateway::sendToClient($client_id, json_encode(array('type' => 'init', 'client_id' => $client_id)));
    }
    /**
     * onWebSocketConnect 事件回调
     * 当客户端连接上gateway完成websocket握手时触发
     *
     * @param  integer  $client_id 断开连接的客户端client_id
     * @param  mixed    $data
     * @return void
     */
    public static function onWebSocketConnect($client_id, $data)
    {
        // var_export($data);
        Gateway::sendToClient($client_id, json_encode(array('type' => 'onweb', 'client_id' => $client_id)));
    }
    /**
     * onMessage 事件回调
     * 当客户端发来数据(Gateway进程收到数据)后触发
     *
     * @access public
     * @param  int       $client_id
     * @param  mixed     $data
     * @return void
     */
    public static function onMessage($client_id, $message)
    {
        $message_data = json_decode($message, true);
        // var_dump($message_data);
        if (!$message_data) {
            return;
        }
        try {
            if (!isset($message_data['type'])) {
                throw new \Exception('缺少消息参数类型');
            }
            //消息回調处理
            $evevtName = self::$eventClassName . '::instance';
            if (is_callable($evevtName)) {
                $evevtName()->start($message_data['type'], $client_id, $message_data);
            } else {
                throw new \Exception('消息处理回调不存在。[' + $evevtName + ']');
            }
        } catch (\Exception $e) {
            var_dump(['file' => $e->getFile(), 'code' => $e->getCode(), 'msg' => $e->getMessage()]);
        }
    }
    /**
     * onClose 事件回调 当用户断开连接时触发的方法
     *
     * @param  integer $client_id 断开连接的客户端client_id
     * @return void
     */
    public static function onClose($client_id)
    {
        Gateway::sendToClient($client_id, json_encode(['type' => 'logout', 'message' => "client[{$client_id}]"]));
    }
    /**
     * onWorkerStop 事件回调
     * 当businessWorker进程退出时触发。每个进程生命周期内都只会触发一次。
     *
     * @param  \Workerman\Worker    $worker
     * @return void
     */
    public static function onWorkerStop($worker)
    {
        Timer::del(self::$timer);
    }
}