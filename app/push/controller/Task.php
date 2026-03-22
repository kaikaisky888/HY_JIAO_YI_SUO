<?php

/*
 * @Author: bluefox
 * @Motto: Running fox looking for dreams
 * @Date: 2020-12-05 21:03:41
 * @LastEditTime: 2021-09-22 20:42:57
 */
namespace app\push\controller;

use app\common\controller\PushController;
use app\push\controller\Doing;
/*
 * 定时任务
 *
 * */
class Task extends PushController
{
    protected static $instance = null;
    public function __construct()
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
    /*
     * 默认定时器执行事件
     * */
    public function start($sec)
    {
        $obj = 'task_' . $sec;
        call_user_func([$this, $obj]);
    }
    /*
     * 每隔1秒执行
     * */
    public function task_1()
    {
        // var_dump('每隔1秒执行');
        try { Doing::kong_kline(); } catch (\Throwable $e) {}
        try { Doing::do_seconds_order(); } catch (\Throwable $e) {}
        try { Doing::do_leverdeal_order(); } catch (\Throwable $e) {}
        try { Doing::find_product_list(); } catch (\Throwable $e) {}
    }
    /*
     * 每隔5秒执行
     * */
    public function task_5()
    {
        try { Doing::do_deal_order(); } catch (\Throwable $e) {}
        try { Doing::do_good_order(); } catch (\Throwable $e) {}
        try { Doing::do_winer_order(); } catch (\Throwable $e) {}
        // var_dump('每隔5秒执行');
    }
    /*
     * 每隔10秒执行
     * */
    public function task_10()
    {
        // var_dump('每隔10秒执行');
    }
    /*
     * 每隔30秒执行
     * */
    public function task_30()
    {
        // var_dump('每隔30秒执行');
    }
    /*
     * 每隔60秒执行
     * */
    public function task_60()
    {
        try { Doing::do_loan_order(); } catch (\Throwable $e) {}
        // var_dump('每隔60秒执行');
    }
    /*
     * 每隔180秒执行
     * */
    public function task_180()
    {
        // var_dump('每隔180秒执行');
        // posix_kill(posix_getppid(), SIGUSR1); //测试时用
    }
    /**
     * @Title: 1小时
     */
    public function task_3600()
    {
        // var_dump('每隔3600秒执行');
        // posix_kill(posix_getppid(), SIGUSR1); //测试时用
    }
    /**
     * @Title: 3小时
     */
    public function task_10800()
    {
        // posix_kill(posix_getppid(), SIGUSR1);
        // var_dump('reload');
    }
}