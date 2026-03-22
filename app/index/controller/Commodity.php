<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2026-01-09 06:50:00
 * @Description: 大宗商品控制器
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;
use app\common\FoxKline;

class Commodity extends IndexController
{
    
    public function index()
    {
        $product = \app\admin\model\ProductLists::where('status',1)->where('base',0)->where('cate_id',11)->order('sort','desc')->select();
        $this->assign('product',$product);
        $web_name = lang('commodity.title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'commodity']);
        return $this->fetch();
    }
    
}
