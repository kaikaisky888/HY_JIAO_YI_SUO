<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-08-10 15:31:27
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;
use app\common\FoxKline;

class Leverdeal extends IndexController
{
    protected $model;
    protected $modellog;
    protected $modelwallet;
    protected $m_order;
    
    public function index()
    {
        $cate_id = input('cate_id', 9);
        $productwhere[] = ['types','like','%2%'];
        $productwhere[] = ['status','=','1'];
        $productwhere[] = ['cate_id','=',$cate_id];
        $product = \app\admin\model\ProductLists::where($productwhere)->order('sort','desc')->select();
        $this->assign('product',$product);
        $this->assign('cate_id',$cate_id);
        $web_name = lang('leverdeal.title').'-'.$this->web_name;
        
        // 根据分类设置不同的导航高亮
        if($cate_id == 10){
            $topmenu = 'market';
        }elseif($cate_id == 11){
            $topmenu = 'commodity';
        }else{
            $topmenu = 'leverdeal';
        }
        $this->assign(['web_name'=>$web_name,'topmenu'=>$topmenu]);
        
        // 外汇和大宗商品使用专用模板
        if($cate_id == 10 || $cate_id == 11){
            return $this->fetch('forex');
        }
        return $this->fetch();
    }
    
    public function get_play_time()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $code = $post['code'];
            $info = [];
            if($code){
                $play_time = \app\admin\model\ProductLists::where('code',$code)->value('le_play_time');
                $info['play_time'] = explode(',',$play_time);
                return json(['code'=>1,'data'=>$info]);
            }
            return json(['code'=>0]);
        }
        return json(['code'=>0]);
    }

    public function orderdo()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $code = $post['code'];
            $money = $post['money'];
            $proInfo = \app\admin\model\ProductLists::where('code',$code)->field('id,le_sx_fee,title')->find();
            if(!$proInfo){
                return $this->error(lang('public.do_fail'));
            }
            $indata['play_time'] = $post['play_time'];
            $indata['account'] = $post['account'];
            $indata['buy_price'] = $post['buy_price'];
            $indata['now_price'] = $indata['buy_price'];
            $indata['play_rate'] = $proInfo['le_sx_fee'];
            $indata['product_id'] = $proInfo['id'];
            $indata['style'] = $post['style'];
            $indata['type'] = 0;
            $indata['uid'] = session('member.id');
            $indata['status'] = 1;//持仓中
            if($indata['account'] <= 0){
                return $this->error(lang('leverdeal.check_laccount_err'));
            }
            $max_buy_num = bc_div($indata['account'],$indata['play_time']);
            $user_wallet = \app\admin\model\MemberWallet::where('product_id',$proInfo['id'])->where('uid',$this->memberInfo['id'])->field('id,le_money')->find();
            if(bc_sub($money,$max_buy_num) < 0){
                return $this->error(lang('leverdeal.check_le_money_noenough',['tit'=>$proInfo['title'],'num'=>floatVal($indata['account'])]));
            }
            $indata['price_account'] = $max_buy_num;//实耗量
            $rate = bc_mul(bc_mul($indata['buy_price'],$indata['account']),$indata['play_rate']);
            $coin_rate = bc_div($rate,$indata['buy_price']);//化为币
            $indata['rate_account'] = $coin_rate;//手续费
            $indata['title'] = $proInfo['title'];
            $this->model = new \app\admin\model\OrderLeverdeal();
            $this->modellog = new \app\admin\model\MemberWalletLog();
            $this->modelwallet = new \app\admin\model\MemberWallet();
            try {
                $save = $this->model->save($indata);
                if($save){
                    $lastId = $this->model->id;
                    $now_le_money = bc_sub($user_wallet['le_money'],$indata['rate_account']);
                    $prowallet = $this->modelwallet->where('product_id',$proInfo['id'])->where('uid',$this->memberInfo['id'])->update(['le_money'=>$now_le_money]);
                    if($prowallet){
                        $logdata['account'] = $indata['account'];
                        $logdata['wallet_id'] = $user_wallet['id'];
                        $logdata['product_id'] = $proInfo['id'];
                        $logdata['uid'] = session('member.id');
                        $logdata['is_test'] = session('member.is_test');
                        $logdata['before'] = $user_wallet['le_money'];
                        $logdata['after'] = $now_le_money;
                        $logdata['account_sxf'] = 0;
                        $logdata['all_account'] = $indata['rate_account'];
                        $logdata['type'] = 5;//合约订单
                        $logdata['title'] = $proInfo['title'];
                        $logdata['order_type'] = 1;//手续费
                        $logdata['order_id'] = $lastId;
                        $inlog = $this->modellog->save($logdata);
                    }
                }
            }catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save && $inlog){
                return json(['code'=>1,'msg'=>lang('leverdeal.order_success'),'id'=>$lastId]);
            }else{
                return $this->error(lang('public.do_fail'));
            }
        }
    }

    public function order_this()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','int');
            $id = $post['id'];
            if($id){
                $info = \app\admin\model\OrderLeverdeal::where('id',$id)->find();
                if(!$info){
                    return json(['code'=>0,'msg'=>lang('leverdeal.order_status_question')]);
                }
                if($info['status'] <> 1){
                    return json(['code'=>0,'msg'=>lang('leverdeal.order_status_question')]);
                }
                try {
                    $this->model = new \app\admin\model\OrderLeverdeal();
                    $save = $this->model->where('id',$info['id'])->update(['status'=>2,'is_lock'=>1,'close_price'=>$info['now_price']]);
                    if($save){
                        $this->modellog = new \app\admin\model\MemberWalletLog();
                        $this->modelwallet = new \app\admin\model\MemberWallet();
                        $user_wallet = \app\admin\model\MemberWallet::where('product_id',$info['product_id'])->where('uid',$this->memberInfo['id'])->field('id,le_money')->find();
                        $now_le_money = bc_add($user_wallet['le_money'],$info['win_account']);
                        $prowallet = $this->modelwallet->where('id',$user_wallet['id'])->update(['le_money'=>$now_le_money]);
                        if($prowallet){
                            $logdata['account'] = $info['win_account'];
                            $logdata['wallet_id'] = $user_wallet['id'];
                            $logdata['product_id'] = $info['product_id'];
                            $logdata['uid'] = session('member.id');
                            $logdata['is_test'] = session('member.is_test');
                            $logdata['before'] = $user_wallet['le_money'];
                            $logdata['after'] = $now_le_money;
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = $info['win_account'];
                            $logdata['type'] = 5;//合约订单
                            $logdata['title'] = $info['title'];
                            $logdata['order_type'] = $info['is_win']+10;//手动平仓
                            $logdata['order_id'] = $id;
                            $inlog = $this->modellog->save($logdata);
                        }
                    }
                }catch (\Exception $e) {
                    return $this->error(lang('public.do_fail'));
                }
                if($save && $inlog){
                    return json(['code'=>1,'msg'=>lang('leverdeal.order_status_success'),'id'=>$id]);
                }
                return json(['code'=>0,'msg'=>lang('leverdeal.order_status_question')]);
            }
            return json(['code'=>0,'msg'=>lang('leverdeal.order_status_question')]);
        }
        return json(['code'=>0,'msg'=>lang('leverdeal.order_status_question')]);
    }

    public function findorder()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','int');
            $id = $post['id'];
            if($id){
                $info = \app\admin\model\OrderLeverdeal::where('id',$id)->where('status',1)->field('id,win_account,now_price')->find();
                if($info){
                    return json(['code'=>1,'data'=>$info]);
                }
                return json(['code'=>0]);
            }
            return json(['code'=>0]);
        }
        return json(['code'=>0]);
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
            $limit = 8;
            $this->m_order = new \app\admin\model\OrderLeverdeal();
            $list = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('status',1)
                ->where('product_id',$product_id)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('status',1)
                ->where('product_id',$product_id)
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['ostyle'] = lang('leverdeal.style_'.$v['style']);
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
            $limit = 8;
            $this->m_order = new \app\admin\model\OrderLeverdeal();
            $list = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('status',2)
                ->where('product_id',$product_id)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('status',2)
                ->where('product_id',$product_id)
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

}

