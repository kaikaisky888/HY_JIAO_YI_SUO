<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-01 16:50:40
 * @LastEditTime: 2021-08-20 14:00:53
 * @Description: Forward, no stop
 */
// +----------------------------------------------------------------------
// | 路由设置
// +----------------------------------------------------------------------

return [

    // 路由中间件
    'middleware' => [

        // 视图初始化
        \app\index\middleware\ViewInit::class,

        // 检测用户是否登录
        \app\index\middleware\CheckUser::class,

        //初始化
        \app\common\middleware\CheckOut::class,

    ],
];
