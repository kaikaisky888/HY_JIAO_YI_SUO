<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-07-02 15:26:13
 * @Description: Forward, no stop
 */

namespace app\index\service;


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