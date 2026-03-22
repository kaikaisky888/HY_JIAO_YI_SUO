<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-07-06 21:56:38
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;

class Show extends IndexController
{
    protected $lang;
    
    public function news()
    {
        $id = request()->param('id/d','','intval');
        if($id){
            $cate_id = \app\admin\model\NewsLists::where('id',$id)->where('status',1)->value('cate_id');
            // 先尝试获取当前语言版本
            $info = \app\admin\model\LangLists::where('item','news')->where('item_id', $id)->where('lang', $this->lang)->find();
            // 如果没找到，尝试获取中文版本
            if(!$info){
                $info = \app\admin\model\LangLists::where('item','news')->where('item_id', $id)->where('lang', 'zh-cn')->find();
            }
            // 如果还没找到，获取任意语言版本
            if(!$info){
                $info = \app\admin\model\LangLists::where('item','news')->where('item_id', $id)->find();
            }
            if(!$info){
                $this->redirect(server_url());
            }
            $info['cate_id'] = $cate_id;
            $web_name = $info['title'].'-'.$this->web_name;
            $this->assign(['web_name'=>$web_name,'info'=>$info]);
            return $this->fetch();
        }
    }
    
}

