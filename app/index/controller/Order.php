<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-10-11 20:09:39
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;
use app\common\FoxCommon;

class Order extends IndexController
{
    
    public function dodeal()
    {
        if(request()->isPost()){
            $this->model = new \app\admin\model\OrderDeal();
            $this->modelwallet = new \app\admin\model\MemberWallet();
            $this->modellog = new \app\admin\model\MemberWalletLog();
            $post = $this->request->post(null,'','trim');
            $indata = [];
            $price = !empty($post['deal_price'])?$post['deal_price']:'';
            $buy_account1 = !empty($post['buy_account1'])?$post['buy_account1']:'';
            $buy_account2 = !empty($post['buy_account2'])?$post['buy_account2']:'';
            $buy_account3 = !empty($post['buy_account3'])?$post['buy_account3']:'';
            $buy_account4 = !empty($post['buy_account4'])?$post['buy_account4']:'';
            $pro = \app\admin\model\ProductLists::where('code',$post['code'])->where('status',1)->field('id,title,close,ex_sx_fee')->find();
            $productBase = \app\admin\model\ProductLists::where('base',1)->field('id,title')->find();
            if(!$pro){
                return $this->error(lang('deal_trage.check_product_no'));
            }
            if($price){
                $indata['type'] = 1;
                $indata['status'] = 1;
                $indata['price'] = $price;
            }else{
                $indata['type'] = 2;
                $indata['status'] = 2;
                $indata['price'] = 0;
            }
            $indata['last_price'] = $pro['close'];
            if($buy_account1){
                $indata['direction'] = 1;
                $indata['price_usdt'] = $post['price_usdt1'];
                $indata['account'] = $buy_account1;
                if($pro['ex_sx_fee'] > 0){
                    $indata['account_sxf'] = bc_mul($buy_account1,$pro['ex_sx_fee']);
                    $indata['account_sxf_tit'] = $pro['title'];
                }else{
                    $indata['account_sxf'] = 0;
                    $indata['account_sxf_tit'] = $pro['title'];
                }
                $indata['account_product'] = bc_sub($indata['account'],$indata['account_sxf']);
                $indata['title'] = $pro['title'].'/'.$productBase['title'];
                $indata['product_id'] = $pro['id'];
                $indata['uid'] = $this->memberInfo['id'];
                $user_base_wallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                if($indata['price_usdt'] > $user_base_wallet['ex_money']){
                    return $this->error(fox_all_replace(lang('deal_trade.usdt_buy_num_no'),$user_base_wallet['ex_money']));
                }
                try {
                    $save = $this->model->save($indata);
                    if($save){
                        $lastId = $this->model->id;
                        $now_ex_money = bc_sub($user_base_wallet['ex_money'],$indata['price_usdt']);
                        $basewallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->update(['ex_money'=>$now_ex_money]);
                        if($basewallet){
                            $logdata['account'] = $indata['price_usdt'];
                            $logdata['wallet_id'] = $user_base_wallet['id'];
                            $logdata['product_id'] = $user_base_wallet['product_id'];
                            $logdata['uid'] = session('member.id');
                            $logdata['is_test'] = session('member.is_test');
                            $logdata['before'] = $user_base_wallet['ex_money'];
                            $logdata['after'] = bc_sub($user_base_wallet['ex_money'],$indata['price_usdt']);
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                            $logdata['type'] = 4;
                            $logdata['title'] = $indata['title'];
                            $logdata['order_type'] = 1;
                            $logdata['order_id'] = $lastId;
                            $this->modellog->save($logdata);
                        }
                    }
                } catch (\Exception $e) {
                    return $this->error(lang('public.do_fail'));
                }
                if($save){
                    return json(['code'=>1,'msg'=>lang('deal_trade.order_success'),'id'=>$lastId]);
                }else{
                    return $this->error(lang('public.do_fail'));
                }
            }else if($buy_account2){
                $indata['direction'] = 2;
                $indata['price_usdt'] = $post['price_usdt2'];
                $indata['account'] = $buy_account2;
                if($pro['ex_sx_fee'] > 0){
                    $indata['account_sxf'] = bc_mul($indata['price_usdt'],$pro['ex_sx_fee']);
                    $indata['account_sxf_tit'] = $productBase['title'];
                }else{
                    $indata['account_sxf'] = 0;
                    $indata['account_sxf_tit'] = $productBase['title'];
                }
                $indata['account_product'] = bc_sub($indata['price_usdt'],$indata['account_sxf']);
                $indata['title'] = $pro['title'].'/'.$productBase['title'];
                $indata['product_id'] = $pro['id'];
                $indata['uid'] = $this->memberInfo['id'];
                $user_pro_wallet = $this->modelwallet->where('product_id',$pro['id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                if($indata['account'] > $user_pro_wallet['ex_money']){
                    return $this->error(lang('public.do_fail'));
                }
                // p($indata);exit;
                try {
                    $save = $this->model->save($indata);
                    if($save){
                        $lastId = $this->model->id;
                        $now_ex_money = bc_sub($user_pro_wallet['ex_money'],$indata['account']);
                        $prowallet = $this->modelwallet->where('id',$user_pro_wallet['id'])->where('uid',$this->memberInfo['id'])->update(['ex_money'=>$now_ex_money]);
                        if($prowallet){
                            $logdata['account'] = $indata['account'];
                            $logdata['wallet_id'] = $user_pro_wallet['id'];
                            $logdata['product_id'] = $user_pro_wallet['product_id'];
                            $logdata['uid'] = session('member.id');
                            $logdata['is_test'] = session('member.is_test');
                            $logdata['before'] = $user_pro_wallet['ex_money'];
                            $logdata['after'] = bc_sub($user_pro_wallet['ex_money'],$indata['account']);
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                            $logdata['type'] = 4;
                            $logdata['title'] = $indata['title'];
                            $logdata['order_type'] = 2;
                            $logdata['order_id'] = $lastId;
                            $this->modellog->save($logdata);
                        }
                    }
                } catch (\Exception $e) {
                    return $this->error(lang('public.do_fail'));
                }
                if($save){
                    return json(['code'=>1,'msg'=>lang('deal_trade.order_success'),'id'=>$lastId]);
                }else{
                    return $this->error(lang('public.do_fail'));
                }
            }else if($buy_account3){
                $indata['direction'] = 1;
                $indata['price_usdt'] = $post['price_usdt3'];
                $indata['account'] = $buy_account3;
                if($pro['ex_sx_fee'] > 0){
                    $indata['account_sxf'] = bc_mul($buy_account3,$pro['ex_sx_fee']);
                    $indata['account_sxf_tit'] = $pro['title'];
                }else{
                    $indata['account_sxf'] = 0;
                    $indata['account_sxf_tit'] = $pro['title'];
                }
                $indata['account_product'] = bc_sub($indata['account'],$indata['account_sxf']);
                $indata['title'] = $pro['title'].'/'.$productBase['title'];
                $indata['product_id'] = $pro['id'];
                $indata['uid'] = $this->memberInfo['id'];
                $indata['price_product'] = $pro['close'];
                $user_base_wallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                if($indata['price_usdt'] > $user_base_wallet['ex_money']){
                    return $this->error(fox_all_replace(lang('deal_trade.usdt_buy_num_no'),$user_base_wallet['ex_money']));
                }
                $user_pro_wallet = $this->modelwallet->where('product_id',$pro['id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                try {
                    $save = $this->model->save($indata);
                    if($save){
                        $lastId = $this->model->id;
                        $now_ex_money = bc_sub($user_base_wallet['ex_money'],$indata['price_usdt']);
                        $basewallet = $this->modelwallet->where('id',$user_base_wallet['id'])->update(['ex_money'=>$now_ex_money]);
                        if($basewallet){
                            $logdata['account'] = $indata['price_usdt'];
                            $logdata['wallet_id'] = $user_base_wallet['id'];
                            $logdata['product_id'] = $user_base_wallet['product_id'];
                            $logdata['uid'] = session('member.id');
                            $logdata['is_test'] = session('member.is_test');
                            $logdata['before'] = $user_base_wallet['ex_money'];
                            $logdata['after'] = bc_sub($user_base_wallet['ex_money'],$indata['price_usdt']);
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                            $logdata['type'] = 4;
                            $logdata['title'] = $indata['title'];
                            $logdata['order_type'] = 1;//买入失
                            $logdata['order_id'] = $lastId;
                            $inlog = $this->modellog->save($logdata);
                            if($inlog){
                                $now_money = bc_add($user_pro_wallet['ex_money'],$indata['account_product']);
                                if($this->modelwallet->where('id',$user_pro_wallet['id'])->update(['ex_money'=>$now_money])){
                                    $this->model->where('id', $lastId)->update(['status'=>2,'update_time'=>time()]);
                                    $lgdata['account'] = $indata['account_product'];
                                    $lgdata['wallet_id'] = $user_pro_wallet['id'];
                                    $lgdata['product_id'] = $user_pro_wallet['product_id'];
                                    $lgdata['uid'] = session('member.id');
                                    $lgdata['is_test'] = session('member.is_test');
                                    $lgdata['before'] = $user_pro_wallet['ex_money'];
                                    $lgdata['after'] = bc_add($user_pro_wallet['ex_money'],$indata['account_product']);
                                    $lgdata['account_sxf'] = 0;
                                    $lgdata['all_account'] = bc_sub($lgdata['account'],$lgdata['account_sxf']);
                                    $lgdata['type'] = 4;
                                    $lgdata['title'] = $indata['title'];
                                    $lgdata['order_type'] = 11;//买得
                                    $lgdata['order_id'] = $lastId;
                                    $this->modellogs = new \app\admin\model\MemberWalletLog();
                                    $lastlog = $this->modellogs->save($lgdata);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    return $this->error(lang('public.do_fail'));
                }
                if($save && $basewallet && $inlog && $lastlog){
                    return json(['code'=>1,'msg'=>lang('deal_trade.order_success'),'id'=>$lastId]);
                }else{
                    return $this->error(lang('public.do_fail'));
                }
            }else if($buy_account4){
                $indata['direction'] = 2;
                $indata['price_usdt'] = $post['price_usdt4'];
                $indata['account'] = $buy_account4;
                if($pro['ex_sx_fee'] > 0){
                    $indata['account_sxf'] = bc_mul($indata['price_usdt'],$pro['ex_sx_fee']);
                    $indata['account_sxf_tit'] = $productBase['title'];
                }else{
                    $indata['account_sxf'] = 0;
                    $indata['account_sxf_tit'] = $productBase['title'];
                }
                $indata['account_product'] = bc_sub($indata['price_usdt'],$indata['account_sxf']);
                $indata['title'] = $pro['title'].'/'.$productBase['title'];
                $indata['product_id'] = $pro['id'];
                $indata['price_product'] = $pro['close'];
                $indata['uid'] = $this->memberInfo['id'];
                $user_pro_wallet = $this->modelwallet->where('product_id',$pro['id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                if($indata['account'] > $user_pro_wallet['ex_money']){
                    return $this->error(lang('public.do_fail'));
                }
                try {
                    $save = $this->model->save($indata);
                    if($save){
                        $lastId = $this->model->id;
                        $now_ex_money = bc_sub($user_pro_wallet['ex_money'],$indata['account']);
                        $prowallet = $this->modelwallet->where('id',$user_pro_wallet['id'])->where('uid',$this->memberInfo['id'])->update(['ex_money'=>$now_ex_money]);
                        if($prowallet){
                            $logdata['account'] = $indata['account'];
                            $logdata['wallet_id'] = $user_pro_wallet['id'];
                            $logdata['product_id'] = $user_pro_wallet['product_id'];
                            $logdata['uid'] = session('member.id');
                            $logdata['is_test'] = session('member.is_test');
                            $logdata['before'] = $user_pro_wallet['ex_money'];
                            $logdata['after'] = bc_sub($user_pro_wallet['ex_money'],$indata['account']);
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                            $logdata['type'] = 4;
                            $logdata['title'] = $indata['title'];
                            $logdata['order_type'] = 2;//卖出失
                            $logdata['order_id'] = $lastId;
                            $inlog = $this->modellog->save($logdata);
                            if($inlog){
                                $base_wallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                                $now_ex_money = bc_add($base_wallet['ex_money'],$indata['account_product']);
                                if($this->modelwallet->where('id',$base_wallet['id'])->update(['ex_money'=>$now_ex_money])){
                                    $this->model->where('id', $lastId)->update(['status'=>2,'update_time'=>time()]);
                                    $lgdata['account'] = $indata['account_product'];
                                    $lgdata['wallet_id'] = $base_wallet['id'];
                                    $lgdata['product_id'] = $base_wallet['product_id'];
                                    $lgdata['uid'] = session('member.id');
                                    $lgdata['is_test'] = session('member.is_test');
                                    $lgdata['before'] = $base_wallet['ex_money'];
                                    $lgdata['after'] = bc_add($base_wallet['ex_money'],$indata['account_product']);
                                    $lgdata['account_sxf'] = 0;
                                    $lgdata['all_account'] = bc_sub($lgdata['account'],$lgdata['account_sxf']);
                                    $lgdata['type'] = 4;
                                    $lgdata['title'] = $indata['title'];
                                    $lgdata['order_type'] = 22;//卖出得
                                    $lgdata['order_id'] = $lastId;
                                    $this->modellogs = new \app\admin\model\MemberWalletLog();
                                    $this->modellogs->save($lgdata);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    return $this->error(lang('public.do_fail'));
                }
                if($save){
                    return json(['code'=>1,'msg'=>lang('deal_trade.order_success'),'id'=>$lastId]);
                }else{
                    return $this->error(lang('public.do_fail'));
                }
            }
            
        }
    }
    
    public function doseconds()
    {
        if(request()->isPost()){
            $this->model = new \app\admin\model\OrderSeconds();
            $this->modelwallet = new \app\admin\model\MemberWallet();
            $this->modellog = new \app\admin\model\MemberWalletLog();
            $post = $this->request->post(null,'','trim');
            $indata = [];
            $pro = \app\admin\model\ProductLists::where('code',$post['code'])->where('status',1)->field('id,title,close,op_play_time,op_play_prop,op_play_max,op_order_min,op_order_max,op_play_down')->find();
            $productBase = \app\admin\model\ProductLists::where('base',1)->field('id,title')->find();
            $user_base_wallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->field('id,op_money')->find();
            $indata['play_time'] = $post['play_time'];
            $indata['start_price'] = $post['start_price'];
            $indata['op_number'] = $post['op_number'];
            $indata['op_style'] = $post['op_style'];
            $indata['orders_time'] = time()+$indata['play_time'];
            // if($pro['op_order_min'] >0){
            //     if($indata['op_number'] < $pro['op_order_min']){
            //         return $this->error(fox_all_replace(lang('seconds_trade.num_min_check'),$pro['op_order_min']));
            //     }
            // }
            // if($pro['op_order_max'] >0){
            //     if($indata['op_number'] > $pro['op_order_max']){
            //         return $this->error(fox_all_replace(lang('seconds_trade.num_max_check'),$pro['op_order_max']));
            //     }
            // }
            if($indata['op_number']<=0){
                return $this->error(lang('seconds_trade.check_op_number'));
            }
            if(empty($pro['op_play_down'])){
                return $this->error(lang('seconds_trade.op_order_down_no'));
            }
            if($indata['op_number']>$user_base_wallet['op_money']){
                return $this->error(fox_all_replace(lang('seconds_trade.check_op_number_noenough'),floatVal($user_base_wallet['op_money'])));
            }
            $indata['product_id'] = $pro['id'];
            $op_play_time = explode(',',$pro['op_play_time']);
            $op_play_prop = explode(',',$pro['op_play_prop']);
            $op_play_down = explode(',',$pro['op_play_down']);
            $key = array_search($indata['play_time'],$op_play_time);
            $indata['play_prop'] = $op_play_prop[$key];
            $op_play_down_can = $op_play_down[$key];
            if($op_play_down_can >0 && $indata['op_number'] < $op_play_down_can){
                $play = foxmat_seconds($indata['play_time']);
                return $this->error(lang('seconds_trade.op_play_down_no',['play'=>$play,'num'=>$op_play_down_can]));
            }
            $indata['uid'] = session('member.id');
            try {
                $save = $this->model->save($indata);
                if($save){
                    $lastId = $this->model->id;
                    $now_op_money = bc_sub($user_base_wallet['op_money'],$indata['op_number']);
                    $prowallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->update(['op_money'=>$now_op_money]);
                    if($prowallet){
                        //分销返佣开始
                        FoxCommon::level_send_member($indata['uid'],$indata['op_number'],$lastId,12);
                        //分销返佣结束
                        $logdata['account'] = $indata['op_number'];
                        $logdata['wallet_id'] = $user_base_wallet['id'];
                        $logdata['product_id'] = $pro['id'];
                        $logdata['uid'] = session('member.id');
                        $logdata['is_test'] = session('member.is_test');
                        $logdata['before'] = $user_base_wallet['op_money'];
                        $logdata['after'] = $now_op_money;
                        $logdata['account_sxf'] = 0;
                        $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                        $logdata['type'] = 6;//期权订单
                        $logdata['title'] = $productBase['title'];
                        $logdata['order_type'] = 1;//下单
                        $logdata['order_id'] = $lastId;
                        $inlog = $this->modellog->save($logdata);
                    }
                }
            }catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save && $inlog){
                return json(['code'=>1,'msg'=>lang('seconds_trade.order_success'),'id'=>$lastId]);
            }else{
                return $this->error(lang('public.do_fail'));
            }
        }
    }

    public function findseconds()
    {
        if(request()->isPost()){
            $id = request()->post('id','0',"int");
            if($id){
                $m_order = new \app\admin\model\OrderSeconds();
                try{
                    $order = $m_order->where('id', $id)->find();
                    if($order['op_status']==1){
                        $outdata['win'] = $order['is_win'];
                        $outdata['price'] = $order['end_price'];
                        $outdata['fee'] = $order['true_fee'];
                        return json(['code'=>1,'data'=>$outdata]);
                    }else{
                        if($order['uid'] <> session('member.id')){
                            return json(['code'=>0]);
                        }
                        $m_product = new \app\admin\model\ProductLists();
                        $m_wallet = new \app\admin\model\MemberWallet();
                        $m_user = new \app\admin\model\MemberUser();
                        $m_log = new \app\admin\model\MemberWalletLog();
                        $is_test = $m_user->where('id',$order['uid'])->value('is_test');
                        $productBase = $m_product->where('base',1)->field('id,title')->find();
                        $pro = $m_product->where('id',$order['product_id'])->field('id,close,op_kong_min,op_kong_max,op_sx_fee,op_order_kong')->find();
                        $user_wallet = $m_wallet->where('product_id',$productBase['id'])->where('uid',$order['uid'])->field('id,product_id,op_money')->find();
                        $op_k_num = FoxCommon::kong_generateRand($pro['op_kong_min'],$pro['op_kong_max']);
                        $u_op_order_kong = $m_user->where('id',$order['uid'])->value('op_order_kong');
                        $op_order_kong = 50;
                        if($pro['op_order_kong']>0){
                            $op_order_kong = $pro['op_order_kong'];
                        }
                        if($u_op_order_kong>0){
                            $op_order_kong = $u_op_order_kong;
                        }
                        $new_rand = mt_rand(0,100);
                        if($new_rand <= $op_order_kong){ //赢
                            if($order['op_style']==1){//买涨
                                $odata['end_price'] = bc_add($order['start_price'], $op_k_num);
                            }else if($order['op_style']==2){//买跌
                                $odata['end_price'] = bc_sub($order['start_price'], $op_k_num);
                            }
                            $odata['update_time'] = time();
                            $odata['is_win'] = 1;
                            $odata['op_status'] = 1;
                            $odata['true_fee'] = bc_add($order['op_number'],bc_mul($order['op_number'],$order['play_prop']/100));
                            $odata['sx_fee'] = bc_mul($odata['true_fee'],$pro['op_sx_fee']);
                            $odata['all_fee'] = bc_sub($odata['true_fee'],$odata['sx_fee']);
                            $now_money = bc_add($user_wallet['op_money'],$odata['all_fee']);
                            if($m_order->where('id',$order['id'])->update($odata)){
                                $m_wallet->where('id',$user_wallet['id'])->update(['op_money'=>$now_money]);
                                $lgdata['account'] = $order['op_number'];
                                $lgdata['wallet_id'] = $user_wallet['id'];
                                $lgdata['product_id'] = $user_wallet['product_id'];
                                $lgdata['uid'] = $order['uid'];
                                $lgdata['is_test'] = $is_test;
                                $lgdata['before'] = $user_wallet['op_money'];
                                $lgdata['after'] = $now_money;
                                $lgdata['account_sxf'] = $odata['sx_fee'];
                                $lgdata['all_account'] = $odata['all_fee'];
                                $lgdata['type'] = 6;
                                $lgdata['title'] = $productBase['title'];
                                $lgdata['order_type'] = 2;//赢返
                                $lgdata['order_id'] = $order['id'];
                                $m_log->save($lgdata);
                            }
                        }else{
                            if($order['op_style']==1){//买涨
                                $odata['end_price'] = bc_sub($order['start_price'], $op_k_num);
                            }else if($order['op_style']==2){//买跌
                                $odata['end_price'] = bc_add($order['start_price'], $op_k_num);
                            }
                            $odata['update_time'] = time();
                            $odata['is_win'] = 2;
                            $odata['op_status'] = 1;
                            $odata['true_fee'] = $order['op_number'];
                            $odata['sx_fee'] = 0;
                            $odata['all_fee'] = $order['op_number'];
                            $m_order->where('id',$order['id'])->update($odata);
                        }
                        return json(['code'=>0]);
                    }
                    return json(['code'=>0]);
                } catch (\Exception $e) {
                    return json(['code'=>0]);
                } 
                return json(['code'=>0]);
            }
        }
        return json(['code'=>0]);
    }

}

