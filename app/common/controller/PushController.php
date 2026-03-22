<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-27 13:10:28
 * @LastEditTime: 2021-08-17 10:53:06
 * @Description: Forward, no stop
 */

namespace app\common\controller;


use app\BaseController;
use think\Model;
use app\ExceptionHandle;

// Workerman 环境下 autoload 已在 start.php 中加载，无需重复 require
// require app()->getRootPath(). 'vendor/autoload.php';

/**
 * Class PushController
 * @package app\common\controller
 */


class PushController extends BaseController
{

    /**
     * 当前模型
     * @Model
     * @var object
     */
    protected $model;

    protected $member=[];

    protected $memberInfo;

    protected $lang;
    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();
        
        if(!$this->lang =$this->app->lang->getLangSet()){
			$lang = \think\facade\Config::get('lang.default_lang');
			$this->app->lang->setLangSet($lang);
			$this->app->lang->saveToCookie($this->app->cookie);
		}
    }

}