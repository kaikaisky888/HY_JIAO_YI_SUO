<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-08-20 14:01:08
 * @Description: Forward, no stop
 */
// +----------------------------------------------------------------------
// | 路由设置
// +----------------------------------------------------------------------

return [

    // 路由中间件
    'middleware' => [

        // 后台视图初始化
        \app\admin\middleware\ViewInit::class,

        // 检测用户是否登录
        \app\admin\middleware\CheckAdmin::class,

        //初始化
        \app\common\middleware\CheckOut::class,
    ],
];
