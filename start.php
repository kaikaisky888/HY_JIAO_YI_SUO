<?php
ini_set('display_errors', 'on');
use think\worker\command\GatewayWorker;
use Workerman\Worker as WorkerMan;

if (strpos(strtolower(PHP_OS), 'win') === 0) {
    exit("start.php not support windows, please use start_for_win.bat\n");
}

if (!extension_loaded('pcntl')) {
    exit("Please install pcntl extension.\n");
}

if (!extension_loaded('posix')) {
    exit("Please install posix extension.\n");
}

define('GLOBAL_START', 1);

require_once __DIR__ . '/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

// 固定 PID 和日志路径，避免 Workerman 因 PID 复用误判"already running"而退出
WorkerMan::$pidFile = '/tmp/gateway_worker.pid';
WorkerMan::$logFile = '/tmp/gateway_worker.log';

// 如果存在残留 PID 文件但进程已死，清除它，防止误判
if (is_file(WorkerMan::$pidFile)) {
    $old_pid = (int)file_get_contents(WorkerMan::$pidFile);
    if ($old_pid > 0 && !posix_kill($old_pid, 0)) {
        unlink(WorkerMan::$pidFile);
        echo "[start.php] Removed stale PID file (pid=$old_pid)\n";
    }
}

$gatWay = new GatewayWorker();
$gatewayOption = require __DIR__ . '/config/gateway_worker.php';
$gatWay->start('0.0.0.0', 2348, $gatewayOption);