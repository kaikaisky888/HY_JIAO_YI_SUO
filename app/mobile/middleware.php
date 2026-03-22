<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-01 16:44:23
 * @LastEditTime: 2021-07-01 17:40:04
 * @Description: Forward, no stop
 */
// 全局中间件定义文件
return [

    // Session初始化
    \think\middleware\SessionInit::class,

    // 多语言加载
    \think\middleware\LoadLangPack::class,

];
