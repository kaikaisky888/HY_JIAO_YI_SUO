<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-10-08 15:58:03
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;
use app\common\FoxKline;

class Coinwin extends IndexController
{

    public function index()
    {
        $pl= new \app\admin\model\ProductLists();
        $where[] = ['types','like','%4%'];
        $products = $pl->where('status',1)->where($where)->field('id,title')->select();
        if($products){
            foreach($products as $k => $v){
                $products[$k]['counts'] = \app\admin\model\GoodLists::where('status',1)->where('product_id',$v['id'])->count();
            }
        }
        $this->assign('products',$products);
        $web_name = lang('coinwin.title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'coinwin']);
        return $this->fetch();
    }
    
    public function lists()
    {
        $product_id = request()->get('id/d','0',"int");
        $goods = \app\admin\model\GoodLists::where('status',1)->where('product_id',$product_id)->order('sort','desc')->select();
        if($goods){
            foreach($goods as $k => $v){
                $goods[$k]['info'] = \app\admin\model\LangLists::where('item','good')->where('item_id', $v['id'])->where('lang', $this->lang)->find();
            }
        }
        $this->assign('goods',$goods);
        $productBase = \app\admin\model\ProductLists::where('id',$product_id)->field('id,title')->find();
        $info['money'] = \app\admin\model\MemberWallet::where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->value('up_money');
        $info['rate_account'] = \app\admin\model\OrderGood::where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->sum('rate_account');
        $info['buy_account'] = \app\admin\model\OrderGood::where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->where('status',1)->sum('buy_account');
        $this->assign('info',$info);
        $this->assign(['product_id'=>$product_id,'coin_title'=>$productBase['title']]);
        $web_name = lang('coinwin.title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'coinwin']);
        return $this->fetch();
    }
    
    public function dobuy()
    {
        if(request()->isPost()){
            $good_id = request()->post('good_id','0',"int");
            $product_id = request()->post('product_id','0',"int");
            $buy_account = request()->post('buy_account','0',"floatVal");
            if($buy_account <= 0){
                return $this->error(lang('coinwin.check_buy_number'));
            }   
            if($good_id && $buy_account>0){
                $this->model = new \app\admin\model\GoodLists();
                $this->modelwallet = new \app\admin\model\MemberWallet();
                $this->morder = new \app\admin\model\OrderGood();
                $this->modellog = new \app\admin\model\MemberWalletLog();
                $productBase = \app\admin\model\ProductLists::where('id',$product_id)->field('id,title')->find();
                $user_base_wallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->field('id,up_money')->find();
                if($buy_account > $user_base_wallet['up_money']){
                    return $this->error(fox_all_replace(lang('coinwin.check_buy_money'),floatVal($user_base_wallet['up_money'])));
                } 
                $good = $this->model->where('id',$good_id)->find();
                if($good['max_price']>0){
                    if($buy_account > $good['max_price']){
                        return $this->error(fox_all_replace(lang('coinwin.check_max_price'),floatVal($good['max_price'])));
                    } 
                }
                if($buy_account < $good['play_price']){
                    return $this->error(fox_all_replace(lang('coinwin.check_min_price'),floatVal($good['play_price'])));
                } 
                if($good['can_buy'] > 0){
                    $count = $this->morder->where('uid',session('member.id'))->where('good_id',$good_id)->where('product_id',$product_id)->where('lock','>',0)->count('id');
                    if($count >= $good['can_buy']){
                        return $this->error(lang('coinwin.can_buy_num',['num'=>$good['can_buy']]));
                    }
                }
                $t = strtotime(date("Y-m-d H:i:s",strtotime("+1 day")));
                $indata = [];
                $indata['good_id'] = $good_id;
                $indata['product_id'] = $product_id;
                $indata['uid'] = session('member.id');
                $indata['buy_account'] = $buy_account;
                $indata['time'] = $good['play_time'];
                $indata['rate'] = $good['play_rate'];
                $indata['lock'] = $indata['time'];
                $indata['type'] = 0;
                $indata['status'] = 1;
                $indata['lock_time'] = $t;
                
                $check = request()->checkToken('__token__');
                if(false === $check) {
                    return $this->error(lang('public.do_fail'));
                }
                try {
                    $save = $this->morder->save($indata);
                    if($save){
                        $lastId = $this->morder->id;
                        $now_up_money = bc_sub($user_base_wallet['up_money'],$indata['buy_account']);
                        $prowallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->update(['up_money'=>$now_up_money]);
                        if($prowallet){
                            $logdata['account'] = $indata['buy_account'];
                            $logdata['wallet_id'] = $user_base_wallet['id'];
                            $logdata['product_id'] = $productBase['id'];
                            $logdata['uid'] = session('member.id');
                            $logdata['is_test'] = session('member.is_test');
                            $logdata['before'] = $user_base_wallet['up_money'];
                            $logdata['after'] = $now_up_money;
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                            $logdata['type'] = 7;//购买理财
                            $logdata['title'] = $productBase['title'];
                            $logdata['remark'] = $good['title'];
                            $logdata['order_type'] = 1;//下单
                            $logdata['order_id'] = $lastId;
                            $inlog = $this->modellog->save($logdata);
                        }
                    }
                } catch (\Exception $e) {
                    return $this->error(lang('public.do_fail'));
                }
                if($save && $prowallet && $inlog){
                    $url = (string)url('coinwin/index');
                    return $this->success(lang('coinwin.buy_account_ok'),[],$url);
                }
            }
            return $this->error(lang('public.do_fail'));
        }
        return $this->error(lang('public.do_fail'));
    }
    
    public function lista()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $page = $post['page'];
            $product_id = $post['product_id'];
            $limit = 5;
            $this->m_order = new \app\admin\model\OrderGood();
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
                $can_win_today = 0;
                foreach($list as $k => $v){
                    $list[$k]['title'] = \app\admin\model\GoodLists::where('id',$v['good_id'])->value('title');
                    if($v['lock'] > 0){
                        if($v['status']==1){
                            $list[$k]['upstatus'] = '<span class="color-green">'.lang('coinwin.status_1').'</span>';
                        }else if($v['status']==2){
                            $list[$k]['upstatus'] = '<span class="color-red">'.lang('coinwin.status_2').'</span>';
                        }
                    }else{
                        $list[$k]['upstatus'] = '<span class="color-red">'.lang('coinwin.lock_0').'</span>';
                    }
                    
                    $can_win_today += bc_mul($v['buy_account'],$v['rate']);
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit),'can_win_today'=>number_format($can_win_today,4)]);
        }
    }

    public function listb()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $page = $post['page'];
            $product_id = $post['product_id'];
            $limit = 5;
            $this->m_order = new \app\admin\model\OrderGood();
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
                $can_win_today = 0;
                foreach($list as $k => $v){
                    $list[$k]['title'] = \app\admin\model\GoodLists::where('id',$v['good_id'])->value('title');
                    if($v['status']==1){
                        $list[$k]['upstatus'] = '<span class="color-green">'.lang('coinwin.status_1').'</span>';
                    }else if($v['status']==2){
                        $list[$k]['upstatus'] = '<span class="color-red">'.lang('coinwin.status_2').'</span>';
                    }
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit),'can_win_today'=>number_format($can_win_today,4)]);
        }
    }

    public function listc()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $page = $post['page'];
            $product_id = $post['product_id'];
            $limit = 5;
            $this->m_order = new \app\admin\model\MemberWalletLog();
            $list = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('type',7)
                ->where('product_id',$product_id)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('type',7)
                ->where('product_id',$product_id)
                ->count('id');
            if($list){
                $can_win_today = 0;
                foreach($list as $k => $v){
                    if($v['order_type']==1){
                        $list[$k]['ordertype'] = '<span class="color-blue">'.lang('coinwin.order_type_1').'</span>';
                        $list[$k]['allacount'] = '<span class="color-blue">- '.number_format($v['all_account'],4).'</span>';
                    }else if($v['order_type']==2){
                        $list[$k]['ordertype'] = '<span class="color-green">'.lang('coinwin.order_type_2').'</span>';
                        $list[$k]['allacount'] = '<span class="color-green">+ '.number_format($v['all_account'],4).'</span>';
                    }else if($v['order_type']==3){
                        $list[$k]['ordertype'] = '<span class="color-red">'.lang('coinwin.order_type_3').'</span>';
                        $list[$k]['allacount'] = '<span class="color-red">+ '.number_format($v['all_account'],4).'</span>';
                    }
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit),'can_win_today'=>number_format($can_win_today,4)]);
        }
    }
    
}

