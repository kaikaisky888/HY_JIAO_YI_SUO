<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-08-17 15:58:11
 * @Description: Forward, no stop
 */

namespace app\mobile\service;


use think\facade\Session;

class TriggerService
{

    /**
     * 更新模板
     * @return bool
     */
    public static function updateTheme($dark = null)
    {
        Session::set('theme',$dark);
        return Session::get('theme');
    }

    public static function setLang($lang = null)
    {
        \think\facade\Lang::setLangSet($lang);
        return \think\facade\Lang::getLangSet();
    }


}