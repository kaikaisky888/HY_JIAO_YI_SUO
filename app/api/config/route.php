<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-01 16:50:40
 * @LastEditTime: 2021-08-21 13:13:42
 * @Description: Forward, no stop
 */
// +----------------------------------------------------------------------
// | 路由设置
// +----------------------------------------------------------------------

return [

    // 路由中间件
    'middleware' => [

        //初始化
        \app\common\middleware\CheckOut::class,

        //鉴权
        \app\api\middleware\Api::class,

    ],
];
