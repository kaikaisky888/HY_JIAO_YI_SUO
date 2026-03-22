<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-08-03 02:12:56
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;
use app\common\FoxKline;

class Seconds extends IndexController
{
    
    public function index()
    {
        $productwhere[] = ['types','like','%3%'];
        $productwhere[] = ['status','=','1'];
        $productwhere[] = ['base','=','0'];
        $product = \app\admin\model\ProductLists::where($productwhere)->order('sort','desc')->select();
        $this->assign('product',$product);
        $web_name = lang('seconds.title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'seconds']);
        // 使用新的现代化模板
        return $this->fetch('index_new');
    }

    public function lista()
    {
        if(request()->isPost()){
            // 检查用户是否登录
            if(empty($this->memberInfo['id'])){
                return json(['code'=>0,'data'=>[],'pages'=>0]);
            }
            $post = $this->request->post(null,'','trim');
            $page = $post['page'];
            $code = $post['code'];
            $product_id = \app\admin\model\ProductLists::where('code',$code)->value('id');
            $limit = 5;
            $m_order = new \app\admin\model\OrderSeconds();
            $list = $m_order->where('uid',$this->memberInfo['id'])
                ->where('product_id',$product_id)
                ->where('op_status',0)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $m_order->where('uid',$this->memberInfo['id'])
                ->where('product_id',$product_id)
                ->where('op_status',0)
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    if($v['op_style']==1){
                        $list[$k]['opstyle'] = '<span class="color-green">'.lang('seconds_trade.btn_buy').'</span>';
                    }else if($v['op_style']==2){
                        $list[$k]['opstyle'] = '<span class="color-red">'.lang('seconds_trade.btn_sell').'</span>';
                    }
                    $list[$k]['creates_time'] = time()-strtotime($v['create_time']);
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
    }
    
    public function listb()
    {
        if(request()->isPost()){
            // 检查用户是否登录
            if(empty($this->memberInfo['id'])){
                return json(['code'=>0,'data'=>[],'pages'=>0]);
            }
            $post = $this->request->post(null,'','trim');
            $page = $post['page'];
            $code = $post['code'];
            $product_id = \app\admin\model\ProductLists::where('code',$code)->value('id');
            $limit = 100;
            $m_order = new \app\admin\model\OrderSeconds();
            $list = $m_order->where('uid',$this->memberInfo['id'])
                ->where('product_id',$product_id)
                ->where('op_status',1)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $m_order->where('uid',$this->memberInfo['id'])
                ->where('product_id',$product_id)
                ->where('op_status',1)
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    if($v['op_style']==1){
                        $list[$k]['opstyle'] = '<span class="color-green">'.lang('seconds_trade.btn_buy').'</span>';
                    }else if($v['op_style']==2){
                        $list[$k]['opstyle'] = '<span class="color-red">'.lang('seconds_trade.btn_sell').'</span>';
                    }
                    if($v['is_win']==1){
                        $list[$k]['iswin'] = '<span class="color-green">'.lang('seconds_trade.is_win_1').'</span>';
                        $list[$k]['fee'] = '<span class="color-green">+'.floatVal($v['all_fee']).'</span>';
                    }else if($v['is_win']==2){
                        $list[$k]['fee'] = '<span class="color-red">-'.floatVal($v['true_fee']).'</span>';
                        $list[$k]['iswin'] = '<span class="color-red">'.lang('seconds_trade.is_win_2').'</span>';
                    }
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
    }

}

