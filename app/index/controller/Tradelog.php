<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-08-10 02:43:24
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;

class Tradelog extends IndexController
{
    
    public function lista()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'0','int');
            $page = $post['page'];
            $limit = 10;
            $this->model = new \app\admin\model\OrderDeal();
            $list = $this->model->where('uid',$this->memberInfo['id'])
                ->where('status',2)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->model->where('uid',$this->memberInfo['id'])
                ->where('status',2)
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['otype'] = lang('order_a.type'.$v['type']);
                    $list[$k]['odirection'] = lang('order_a.direction'.$v['direction']);
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
    }

    public function listb()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $page = $post['page'];
            $limit = 10;
            $this->m_order = new \app\admin\model\OrderLeverdeal();
            $list = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('status',2)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('status',2)
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['ostyle'] = lang('leverdeal.style_'.$v['style']);
                    $list[$k]['owin'] = lang('leverdeal.win_'.$v['is_win']);
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
    }

    public function listc()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'0','int');
            $page = $post['page'];
            $limit = 10;
            $this->model = new \app\admin\model\OrderSeconds();
            $list = $this->model->where('uid',$this->memberInfo['id'])
                ->where('op_status',1)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->model->where('uid',$this->memberInfo['id'])
                ->where('op_status',1)
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['ostyle'] = lang('order_c.style'.$v['op_style']);
                    $list[$k]['oiswin'] = lang('order_c.iswin'.$v['is_win']);
                    $list[$k]['title'] = \app\admin\model\ProductLists::where('id',$v['product_id'])->value('title');
                    if($v['is_win'] ==1){
                        $list[$k]['money'] = '+'.$v['all_fee'];
                    }else{
                        $list[$k]['money'] = '-'.$v['op_number'];
                    }
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
    }

    public function listd()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'0','int');
            $page = $post['page'];
            $limit = 10;
            $this->model = new \app\admin\model\OrderGood();
            $list = $this->model->where('uid',$this->memberInfo['id'])
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->model->where('uid',$this->memberInfo['id'])
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['ostatus'] = lang('order_d.status'.$v['status']);
                    $list[$k]['title'] = \app\admin\model\GoodLists::where('id',$v['good_id'])->value('title');
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
    }

    public function liste()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'0','int');
            $page = $post['page'];
            $limit = 10;
            $this->model = new \app\admin\model\OrderIeorg();
            $list = $this->model->where('uid',$this->memberInfo['id'])
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->model->where('uid',$this->memberInfo['id'])
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['otype'] = lang('order_e.type'.$v['type']);
                    $list[$k]['title'] = \app\admin\model\IeoLists::where('id',$v['ieo_id'])->value('title');
                    $list[$k]['ptitle'] = \app\admin\model\ProductLists::where('id',$v['product_id'])->value('title');
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
    }
    
}

