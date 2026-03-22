<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-08-23 15:39:52
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use think\App;
use think\facade\Env;

class Show extends MobileController
{
    
    public function news()
    {
        $id = request()->param('id/d','','intval');
        if($id){
            $cate_id = \app\admin\model\NewsLists::where('id',$id)->where('status',1)->value('cate_id');
            $info = \app\admin\model\LangLists::where('item','news')->where('item_id', $id)->where('lang', $this->lang)->find();
            if(!$info){
                $this->redirect(server_url());
            }
            $info['cate_id'] = $cate_id;
            $web_name = $info['title'].'-'.$this->web_name;
            $this->assign(['web_name'=>$web_name,'info'=>$info]);
            return $this->fetch();
        }
    }

    public function lists()
    {
        $id = request()->param('id/d','','intval');
        if($id){
            $info['cate_id'] = $id;
            $web_name = $this->web_name;
            $this->assign(['web_name'=>$web_name,'info'=>$info]);
            return $this->fetch();
        }
    }
    
}

