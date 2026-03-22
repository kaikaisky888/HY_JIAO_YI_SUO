<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-10-13 10:17:08
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use think\App;
use think\facade\Env;
use app\common\FoxCommon;
use app\common\FoxKline;

class Winer extends MobileController
{
    
    public function index()
    {
        $this->model = new \app\admin\model\MinerLists();
        $winers = $this->model
            ->where('status',1)
            ->order('sort','desc')
            ->select();
        if($winers){
            $pro_id = 0;
            foreach($winers as $k => $v){
                $winers[$k]['coin'] = \app\admin\model\ProductLists::where('id',$v['product_id'])->value('title');
                if($k == 0){
                    $pro_id = $v['id'];
                }
            }
        }
        $this->assign(['winers'=>$winers,'pro_id'=>$pro_id]);
        $web_name = lang('winer.title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'winer']);
        $productBase = \app\admin\model\ProductLists::where('base',1)->field('id,title')->find();
        $user_wallet['title'] = $productBase['title'];
        $user_wallet['ex_money'] = \app\admin\model\MemberWallet::where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->value('ex_money');
        $this->assign('user_wallet',$user_wallet);
        return $this->fetch();
    }

    public function lists()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $page = $post['page'];
            $limit = 10;
            $this->m_order = new \app\admin\model\OrderWiner();
            $list = $this->m_order->where('uid',$this->memberInfo['id'])
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->m_order->where('uid',$this->memberInfo['id'])
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['title'] = \app\admin\model\MinerLists::where('id',$v['winer_id'])->value('title');
                    if($v['lock'] > 0){
                        $list[$k]['lock'] = $v['lock'];
                        if($v['status']==1){
                            $list[$k]['upstatus'] = '<span class="color-green">'.lang('winer.status_1').'</span>';
                        }else if($v['status']==2){
                            $list[$k]['upstatus'] = '<span class="color-red">'.lang('winer.status_2').'</span>';
                        }
                    }else{
                        $list[$k]['upstatus'] = '<span class="color-red">'.lang('winer.status_0').'</span>';
                        $list[$k]['lock'] = '------';
                    }
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
        $web_name = lang('winer.lists').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'winer']);
        return $this->fetch();
    }

    public function listlog()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $page = $post['page'];
            $limit = 10;
            $this->m_order = new \app\admin\model\MemberWalletLog();
            $list = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('type',9)
                ->where('status',33)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            $count = $this->m_order->where('uid',$this->memberInfo['id'])
                ->where('type',9)
                ->where('status',33)
                ->count('id');
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['title'] = $v['remark'].'-'.$v['order_id'];
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
    }
    
    public function get_rate()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','int');
            $winer_id = $post['winer_id'];
            $info = [];
            if($winer_id){
                $winerInfo = \app\admin\model\MinerLists::where('id',$winer_id)->where('status',1)->field('max_rate,play_time')->find();
                $info['rate'] = $winerInfo['max_rate'];
                $info['play_time'] = explode(',',$winerInfo['play_time']);
                return json(['code'=>1,'data'=>$info]);
            }
            return json(['code'=>0]);
        }
        return json(['code'=>0]);
    }

    public function get_num()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $num = $post['num'];
            $winer_id = $post['winer_id'];
            $time = $post['time'];
            $info = [];
            if($winer_id && $num){
                $winer = \app\admin\model\MinerLists::where('id',$winer_id)->field('product_id,max_rate')->find();
                $pro = \app\admin\model\ProductLists::where('id',$winer['product_id'])->field('title,close')->find();
                //价格换算
                $pprice = str_replace(',','',FoxKline::get_me_price_usdt_to_usd($pro['close'],8));
                $info['guess_rate'] = bc_mul(bc_mul(bc_div($num, $pprice), $winer['max_rate']),$time).' '.$pro['title'];
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
            $indata = [];
            $indata['winer_id'] = $post['winer_id'];
            $indata['time'] = $post['time'];
            $indata['buy_account'] = $post['buy_account'];
            $paypwd = $post['paypwd'];
            $users = \app\admin\model\MemberUser::where(['id'=>$this->memberInfo['id']])->find();
            if (password($paypwd) != $users->paypwd) {
                return $this->error(lang('winer.member_check_passpayerr'));
            }
            $indata['uid'] = session('member.id');
            $indata['lock'] = $indata['time'];
            $winer = \app\admin\model\MinerLists::where('id',$indata['winer_id'])->field('product_id,min_rate,max_rate,can_buy')->find();
            if(!$winer){
                return $this->error(lang('public.do_fail'));
            }
            if($winer['can_buy'] > 0){
                $this->m_order = new \app\admin\model\OrderWiner();
                $count = $this->m_order->where('uid',$this->memberInfo['id'])->where('winer_id',$indata['winer_id'])->where('product_id',$winer['product_id'])->where('lock','>',0)->count('id');
                if($count >= $winer['can_buy']){
                    return $this->error(lang('winer.can_buy_num',['num'=>$winer['can_buy']]));
                }
            }
            $this->modelwallet = new \app\admin\model\MemberWallet();
            if($indata['buy_account'] <= 0){
                return $this->error(lang('winer.check_buy_number'));
            } 
            $productBase = \app\admin\model\ProductLists::where('base',1)->field('id,title')->find();
            $user_base_wallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->field('id,ex_money')->find();
            if($indata['buy_account'] > $user_base_wallet['ex_money']){
                return $this->error(fox_all_replace(lang('winer.check_buy_money'),floatVal($user_base_wallet['ex_money'])));
            }
            $t = strtotime(date("Y-m-d",strtotime("+1 day")));
            $indata['product_id'] = $winer['product_id'];
            $indata['min_rate'] = $winer['min_rate'];
            $indata['max_rate'] = $winer['max_rate'];
            $indata['type'] = 1;
            $indata['status'] = 1;
            $indata['lock_time'] = $t-60;
            $protit = \app\admin\model\ProductLists::where('id',$winer['product_id'])->value('title');
            $indata['remark'] = $protit;
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $this->morder = new \app\admin\model\OrderWiner();
                $this->modellog = new \app\admin\model\MemberWalletLog();
                $save = $this->morder->save($indata);
                if($save){
                    $lastId = $this->morder->id;
                    $now_ex_money = bc_sub($user_base_wallet['ex_money'],$indata['buy_account']);
                    $prowallet = $this->modelwallet->where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->update(['ex_money'=>$now_ex_money]);
                    if($prowallet){
                        //分销返佣开始
                        FoxCommon::level_send_member($indata['uid'],$indata['buy_account'],$lastId,11);
                        //分销返佣结束
                        $logdata['account'] = $indata['buy_account'];
                        $logdata['wallet_id'] = $user_base_wallet['id'];
                        $logdata['product_id'] = $productBase['id'];
                        $logdata['uid'] = session('member.id');
                        $logdata['is_test'] = session('member.is_test');
                        $logdata['before'] = $user_base_wallet['ex_money'];
                        $logdata['after'] = $now_ex_money;
                        $logdata['account_sxf'] = 0;
                        $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                        $logdata['type'] = 9;//挖矿购买
                        $logdata['title'] = $productBase['title'];
                        $logdata['remark'] = $protit;
                        $logdata['status'] = 31;//挖矿冻结
                        $logdata['order_type'] = 1;//挖矿冻结
                        $logdata['order_id'] = $lastId;
                        $inlog = $this->modellog->save($logdata);
                    }
                }
            } catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save && $prowallet && $inlog){
                $url = (string)url('winer/lists');
                return $this->success(lang('winer.buy_account_ok'),[],$url);
            }
            
            return $this->error(lang('public.do_fail'));
        }
    }

}

