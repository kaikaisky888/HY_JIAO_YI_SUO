<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-08-27 19:02:02
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use think\App;
use think\facade\Env;
use app\common\FoxKline;

class Deal extends MobileController
{
    
    public function index()
    {
        $productwhere[] = ['types','like','%1%'];
        $productwhere[] = ['status','=','1'];
        $productwhere[] = ['base','=','0'];
        $product = \app\admin\model\ProductLists::where($productwhere)->order('sort','desc')->select();
        $this->assign('product',$product);
        $web_name = lang('deal.title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'deal']);
        $this->assign(['footmenu'=>'deal']);
        return $this->fetch();
    }

    public function historylist()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $page = $post['page'];
            $code = $post['code'];
            $product_id = \app\admin\model\ProductLists::where('code',$code)->value('id');
            $limit = 5;
            $this->m_order = new \app\admin\model\OrderDeal();
            $list = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('product_id',$product_id)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('product_id',$product_id)
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    if($v['type']==2){
                        $list[$k]['typelist'] = '<span class="">'.lang('deal_trade.type_'.$v['type']).'</span>';
                    }else{
                        $list[$k]['typelist'] = '<span class="">'.lang('deal_trade.type_'.$v['type']).'</span>';
                    }
                    if($v['direction']==2){
                        $list[$k]['directionlist'] = '<span class="color-red">'.lang('deal_trade.direction_'.$v['direction']).'</span>';
                    }else{
                        $list[$k]['directionlist'] = '<span class="color-green">'.lang('deal_trade.direction_'.$v['direction']).'</span>';
                    }
                    if($v['status']==3){
                        $list[$k]['statuslist'] = '<a href="javascript:void(0);" onclick="showDeal(this);"><span class="color-red show-deal-con">'.lang('deal_trade.status_'.$v['status']).' <i class="fa fa-chevron-right" aria-hidden="true"></i></span></a>';
                    }else if($v['status']==2){
                        $list[$k]['statuslist'] = '<a href="javascript:void(0);" onclick="showDeal(this);"><span class="color-green show-deal-con">'.lang('deal_trade.status_'.$v['status']).' <i class="fa fa-chevron-right" aria-hidden="true"></i></span></a>';
                    }else{
                        $list[$k]['statuslist'] = '<a href="javascript:void(0);" onclick="showDeal(this);"><span class="color-blue show-deal-con">'.lang('deal_trade.status_'.$v['status']).' <i class="fa fa-chevron-right" aria-hidden="true"></i></span></a>';
                    }
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
    }

    public function findnow()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'0','int');
            
            $id = $post['id'];
            $this->omodel = new \app\admin\model\OrderDeal();
            $deal_order = $this->omodel->where('id',$id)->where('uid',$this->memberInfo['id'])->find();
            if($deal_order){
                if($deal_order['type']==2){
                    $deal_order['typelist'] = '<span class="">'.lang('deal_trade.type_'.$deal_order['type']).'</span>';
                }else{
                    $deal_order['typelist'] = '<span class="">'.lang('deal_trade.type_'.$deal_order['type']).'</span>';
                }
                if($deal_order['direction']==2){
                    $deal_order['directionlist'] = '<span class="color-red">'.lang('deal_trade.direction_'.$deal_order['direction']).'</span>';
                }else{
                    $deal_order['directionlist'] = '<span class="color-green">'.lang('deal_trade.direction_'.$deal_order['direction']).'</span>';
                }
                if($deal_order['status']==3){
                    $deal_order['statuslist'] = '<a href="javascript:void(0);" onclick="showDeal(this);"><span class="color-red show-deal-con">'.lang('deal_trade.status_'.$deal_order['status']).' <i class="fa fa-chevron-right" aria-hidden="true"></i></span></a>';
                }else if($deal_order['status']==2){
                    $deal_order['statuslist'] = '<a href="javascript:void(0);" onclick="showDeal(this);"><span class="color-green show-deal-con">'.lang('deal_trade.status_'.$deal_order['status']).' <i class="fa fa-chevron-right" aria-hidden="true"></i></span></a>';
                }else{
                    $deal_order['statuslist'] = '<a href="javascript:void(0);" onclick="showDeal(this);"><span class="color-blue show-deal-con">'.lang('deal_trade.status_'.$deal_order['status']).' <i class="fa fa-chevron-right" aria-hidden="true"></i></span></a>';
                }
                return json(['code'=>1,'data'=>$deal_order]);
            }
        }
    }

    public function findback()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'0','int');
            $id = $post['id'];
            $this->model = new \app\admin\model\OrderDeal();
            $this->modelwallet = new \app\admin\model\MemberWallet();
            $this->modellog = new \app\admin\model\MemberWalletLog();
            $deal_order = $this->model->where('id',$id)->where('status',1)->find();
            if($deal_order){
                if($deal_order['direction']==1){//买入
                    $productBase = \app\admin\model\ProductLists::where('base',1)->field('id,title')->find();
                    $user_base_wallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                    try{
                        if($this->model->where('id',$deal_order['id'])->update(['status'=>3])){
                            $now_ex_money = bc_add($user_base_wallet['ex_money'],$deal_order['price_usdt']);
                            $basewallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->update(['ex_money'=>$now_ex_money]);
                            if($basewallet){
                                $logdata['account'] = $deal_order['price_usdt'];
                                $logdata['wallet_id'] = $user_base_wallet['id'];
                                $logdata['product_id'] = $user_base_wallet['product_id'];
                                $logdata['uid'] = session('member.id');
                                $logdata['is_test'] = session('member.is_test');
                                $logdata['before'] = $user_base_wallet['ex_money'];
                                $logdata['after'] = bc_add($user_base_wallet['ex_money'],$deal_order['price_usdt']);
                                $logdata['account_sxf'] = 0;
                                $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                                $logdata['type'] = 4; //币币单
                                $logdata['title'] = $deal_order['title'];
                                $logdata['order_type'] = 111;//撤单
                                $logdata['order_id'] = $deal_order['id'];
                                $log = $this->modellog->save($logdata);
                            }
                        }
                        
                    } catch (\Exception $e) {
                        
                        return $this->error(lang('public.do_fail'));
                    }
                    if($basewallet && $log){
                        return json(['code'=>1,'msg'=>lang('deal_trade.deal_back_ok'),'id'=>0]);
                    }else{
                       
                        return $this->error(lang('public.do_fail'));
                    }
                }else if($deal_order['direction']==2){//卖出
                    $user_pro_wallet = $this->modelwallet->where('product_id',$deal_order['product_id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                    try {
                        if($this->model->where('id',$deal_order['id'])->update(['status'=>3])){
                            $now_ex_money = bc_add($user_pro_wallet['ex_money'],$deal_order['account']);
                            $prowallet = $this->modelwallet->where('id',$user_pro_wallet['id'])->update(['ex_money'=>$now_ex_money]);
                            if($prowallet){
                                $logdata['account'] = $deal_order['account'];
                                $logdata['wallet_id'] = $user_pro_wallet['id'];
                                $logdata['product_id'] = $user_pro_wallet['product_id'];
                                $logdata['uid'] = session('member.id');
                                $logdata['is_test'] = session('member.is_test');
                                $logdata['before'] = $user_pro_wallet['ex_money'];
                                $logdata['after'] = bc_add($user_pro_wallet['ex_money'],$deal_order['account']);
                                $logdata['account_sxf'] = 0;
                                $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                                $logdata['type'] = 4; //币币单
                                $logdata['title'] = $deal_order['title'];
                                $logdata['order_type'] = 222;//撤单
                                $logdata['order_id'] = $deal_order['id'];
                                $log = $this->modellog->save($logdata);
                            }
                        }
                    } catch (\Exception $e) {
                        return $this->error(lang('deal_trade.deal_back_error'));
                    }
                    if($prowallet && $log){
                        return json(['code'=>1,'msg'=>lang('deal_trade.deal_back_ok'),'id'=>0]);
                    }else{
                        return $this->error(lang('deal_trade.deal_back_error'));
                    }
                }
            }
            return $this->error(lang('deal_trade.deal_back_error'));
        }
    }
    
}

