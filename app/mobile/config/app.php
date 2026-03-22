<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-08-23 15:30:00
 * @Description: Forward, no stop
 */
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // // 异常页面的模板文件
    /*'exception_tmpl'   => app()->getBasePath() . 'mobile' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'think_exception.tpl',*/
    'dispatch_success_tmpl'=>'layout/success',
    'dispatch_error_tmpl'  =>'layout/success',
    
];
