<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-08-17 16:04:51
 * @Description: Forward, no stop
 */

// [ 应用入口文件 ]
namespace think;

require __DIR__ . '/../vendor/autoload.php';

// // 声明全局变量
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__ . DS . '..' . DS);

// 执行HTTP应用并响应
$http = (new App())->debug(true)->http;

$response = $http->run();

$response->send();

$http->end($response);
