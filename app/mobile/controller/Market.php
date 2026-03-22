<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-08-17 22:50:05
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use think\App;
use think\facade\Env;
use app\common\FoxKline;

class Market extends MobileController
{
    
    public function index()
    {
        $product = \app\admin\model\ProductLists::where('status',1)->where('base',0)->order('sort','desc')->select();
        $this->assign('product',$product);
        $web_name = lang('market.title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'market']);
        $this->assign(['footmenu'=>'market']);
        return $this->fetch();
    }
    
}

