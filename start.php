<?php
ini_set('display_errors', 'on');
use think\worker\command\Worker;
use think\worker\command\Server;
use think\worker\Server as WorkerServer;
use think\worker\command\GatewayWorker;
use think\worker\Http;
use Workerman\Worker as WorkerMan;

if(strpos(strtolower(PHP_OS), 'win') === 0)
{
    exit("start.php not support windows, please use start_for_win.bat\n");
}

// 检查扩展
if(!extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

if(!extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

// 标记是全局启动
define('GLOBAL_START', 1);

require_once __DIR__ .'/vendor/autoload.php';

// 初始化 ThinkPHP 应用，使 app() 等助手函数可用
$app = new \think\App();
$app->initialize();

$ws_worke = new Worker();
$ws_server = new Server(); 
$gatWay = new GatewayWorker();
/*$worker_server = new WorkerServer(); */

// 注意：$http 和 $workerMan 在 1236 端口与 Register 冲突，导致整个 GatewayWorker 崩溃循环
// 已注释掉以解决端口冲突问题
// $http = new Http('0.0.0.0',1236); 
// $workerMan = new WorkerMan('websocket://0.0.0.0:1236'); 
// $http->name = 'http';
// $workerMan->name = 'WorkerMan';
// $workerMan->onMessage = function ($connection, $data) {
//     $connection->send('Hello ' . $data);
// };
/*$worker_server->start();*/

// 获取 gateway_worker 配置
$gatewayOption = require __DIR__ . '/config/gateway_worker.php';
$gatWay->start('0.0.0.0', 2348, $gatewayOption);

// 注意：GatewayWorker::start() 内部已调用 Worker::runAll()
// 所以下面的代码不会执行，需要分开启动或注释掉 gateway
// $http->start();
// WorkerMan::runAll();

/*
require_once __DIR__ .'/vendor/workerman/workerman/Autoloader.php';

// SSL context.
$context = array(
    'ssl' => array(
        'local_cert'  => __DIR__ .'/ssl/server.pem',
        'local_pk'    =>  __DIR__ .'/ssl/server.key',
        'verify_peer' => false,
    )
);

// Create a Websocket server with ssl context.
$ws_worker = new Worker('websocket://0.0.0.0:1236', $context);

// Enable SSL. WebSocket+SSL means that Secure WebSocket (wss://). 
// The similar approaches for Https etc.
$ws_worker->transport = 'ssl';

$ws_worker->onMessage = function ($connection, $data) {
    // Send hello $data
    $connection->send('Hello ' . $data);
};


Worker::runAll();*/