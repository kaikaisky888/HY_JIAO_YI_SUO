<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-09-16 15:30:29
 * @Description: Forward, no stop
 */

namespace app\mobile\service;

use think\facade\Cache;
use think\facade\Session;

class ConfigService
{

    public static function getVersion()
    {
        $version = Cache('version');
        if (empty($version)) {
            $version = sysconfig('site', 'site_version');
            cache('site_version', $version);
            Cache::set('version', $version, 3600);
        }
        return $version;
    }

    public static function getTheme()
    {
        $theme = Session::get('theme');
        if(empty($theme)){
            Session::set('theme','Dark');
            $theme = Session::get('theme');
        }
        return $theme;
    }

}