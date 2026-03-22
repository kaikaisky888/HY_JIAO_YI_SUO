<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-08-17 15:51:41
 * @Description: Forward, no stop
 */
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // 应用地址
    'app_host'         => Env::get('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 是否启用事件
    'with_event'       => true,
    // 开启应用快速访问
    'app_express'      => true,
    // 默认应用
    'default_app'      => 'index',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 应用映射（自动多应用模式有效）
    'app_map'          => [
        Env::get('ffadmin.admin', 'fox') => 'admin',
        Env::get('ffadmin.index', 'index') => 'index',
        Env::get('ffadmin.mobile', 'mobile') => 'mobile',
    ],
    // 后台别名
    'admin_alias_name' => Env::get('ffadmin.admin', 'fox'),
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => ['common'],
    // // 异常页面的模板文件
    // 'exception_tmpl'   => Env::get('app_debug') == 1 ? app()->getThinkPath() . 'tpl/think_exception.tpl' : app()->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'think_exception.tpl',
    // // 跳转页面的成功模板文件
    // 'dispatch_success_tmpl'   => app()->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'dispatch_jump.tpl',
    // // 跳转页面的失败模板文件
    // 'dispatch_error_tmpl'   => app()->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'dispatch_jump.tpl',
    // 错误显示信息,非调试模式有效
    'error_message'    => 'Is Wrong,But ～',
    // 显示错误信息
    'show_error_msg'   => false,
    // 静态资源上传到OSS前缀
    'oss_static_prefix'   => Env::get('ffadmin.oss_static_prefix', 'static_ffadmin'),
];
