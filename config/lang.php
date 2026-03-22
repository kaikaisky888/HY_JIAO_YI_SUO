<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-07-07 20:49:40
 * @Description: Forward, no stop
 */
// +----------------------------------------------------------------------
// | 多语言设置
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // 默认语言
    'default_lang'    => 'ko-kr',
    // 允许的语言列表
    // 'allow_lang_list' => ['zh-cn','hk-cn','en-us','ja-jp','ko-kr','ru-ru','es-es','it-it','ar-ae'],
    'allow_lang_list' => ['en-us','zh-cn','hk-cn','ja-jp','ko-kr','ru-ru'],
    // 转义为对应语言包名称
    'accept_language' => [
        'zh-hans-cn' => 'en-us',
    ],
    // 多语言自动侦测变量名
    'detect_var'      => 'lang',
    // 多语言 Cookie 变量
    'cookie_var'      => 'lang',
    // 多语言 Header 变量
    'header_var'      => 'lang',
    // 使用 Cookie 记录
    'use_cookie'      => true,
    // 是否支持语言分组
    'allow_group'     => true,
    // 扩展语言包
    'extend_list'     => [
        'en-us'    => [
            app()->getBasePath()  . 'lang\en-us.php',
        ],
        'zh-cn'    => [
            app()->getBasePath()  . 'lang\zh-cn.php',
        ],
        'hk-cn'    => [
            app()->getBasePath()  . 'lang\hk-cn.php',
        ],
        'ja-jp'    => [
            app()->getBasePath()  . 'lang\ja-jp.php',
        ],
        'ko-kr'    => [
            app()->getBasePath()  . 'lang\ko-kr.php',
        ],
        'ru-ru'    => [
            app()->getBasePath()  . 'lang\ru-ru.php',
        ],
        'es-es'    => [
            app()->getBasePath()  . 'lang\es-es.php',
        ],
        'it-it'    => [
            app()->getBasePath()  . 'lang\it-it.php',
        ],
        'ar-ae'    => [
            app()->getBasePath()  . 'lang\ar-ae.php',
        ],
    ],
    //对应图片
    'img_list'     => [
        'en-us'    => '/static/index/images/en.png',
        'zh-cn'    => '/static/index/images/cn.png',
        'hk-cn'    => '/static/index/images/hk.png',
        'ja-jp'    => '/static/index/images/jp.png',
        'ko-kr'    => '/static/index/images/ko.png',
        'ru-ru'    => '/static/index/images/ru.png',
        'es-es'    => '/static/index/images/es.png',
        'it-it'    => '/static/index/images/it.png',
        'ar-ae'    => '/static/index/images/ar.png',
    ],

];
