<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2026-03-12 00:00:00
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use think\App;
use think\facade\Env;
use think\facade\Db;
use app\common\FoxKline;

class Leverdeal extends MobileController
{
    protected $modellog;
    protected $modelwallet;
    protected $m_order;

    public function index()
    {
        $productwhere[] = ['types','like','%2%'];
        $productwhere[] = ['status','=','1'];
        $product = \app\admin\model\ProductLists::where($productwhere)->order('sort','desc')->select();
        $this->assign('product',$product);
        $web_name = lang('leverdeal.title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'leverdeal']);
        $this->assign(['footmenu'=>'leverdeal']);
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

    /**
     * 校验止盈止损价格
     * style: 1=买涨 2=买跌
     */
    private function checkStopPrice($style, $buyPrice, $stopProfitPrice, $stopLossPrice)
    {
        $buyPrice = (string)$buyPrice;
        $stopProfitPrice = ($stopProfitPrice === '' || $stopProfitPrice === null) ? '0' : (string)$stopProfitPrice;
        $stopLossPrice = ($stopLossPrice === '' || $stopLossPrice === null) ? '0' : (string)$stopLossPrice;

        if (bccomp($stopProfitPrice, '0', 8) < 0 || bccomp($stopLossPrice, '0', 8) < 0) {
            return '止盈止损价格不能小于0';
        }

        // 买涨：止盈 > 开仓价，止损 < 开仓价
        if ((int)$style === 1) {
            if (bccomp($stopProfitPrice, '0', 8) > 0 && bccomp($stopProfitPrice, $buyPrice, 8) <= 0) {
                return '买涨时，止盈价必须大于开仓价';
            }
            if (bccomp($stopLossPrice, '0', 8) > 0 && bccomp($stopLossPrice, $buyPrice, 8) >= 0) {
                return '买涨时，止损价必须小于开仓价';
            }
        }

        // 买跌：止盈 < 开仓价，止损 > 开仓价
        if ((int)$style === 2) {
            if (bccomp($stopProfitPrice, '0', 8) > 0 && bccomp($stopProfitPrice, $buyPrice, 8) >= 0) {
                return '买跌时，止盈价必须小于开仓价';
            }
            if (bccomp($stopLossPrice, '0', 8) > 0 && bccomp($stopLossPrice, $buyPrice, 8) <= 0) {
                return '买跌时，止损价必须大于开仓价';
            }
        }

        return '';
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
            $indata['stop_profit_price'] = !empty($post['stop_profit_price']) ? $post['stop_profit_price'] : 0;
            $indata['stop_loss_price'] = !empty($post['stop_loss_price']) ? $post['stop_loss_price'] : 0;
            $indata['close_type'] = 0;

            if($indata['account'] <= 0){
                return $this->error(lang('leverdeal.check_laccount_err'));
            }

            // 止盈止损校验
            $stopMsg = $this->checkStopPrice(
                $indata['style'],
                $indata['buy_price'],
                $indata['stop_profit_price'],
                $indata['stop_loss_price']
            );
            if($stopMsg){
                return json(['code'=>0,'msg'=>$stopMsg]);
            }

            $max_buy_num = bc_div($indata['account'],$indata['play_time']);
            if(bc_sub($money,$max_buy_num) < 0){
                return $this->error(lang('leverdeal.check_le_money_noenough',['tit'=>$proInfo['title'],'num'=>floatVal($indata['account'])]));
            }

            $indata['price_account'] = $max_buy_num;//实耗量
            $rate = bc_mul(bc_mul($indata['buy_price'],$indata['account']),$indata['play_rate']);
            $coin_rate = bc_div($rate,$indata['buy_price']);//保证金消耗（单位）
            $indata['rate_account'] = $coin_rate;//手续费
            $indata['title'] = $proInfo['title'];

            // 合约保证金统一操作 USDT 账户的 le_money
            $usdt_product_id = \app\admin\model\ProductLists::where('base',1)->value('id');

            $this->model = new \app\admin\model\OrderLeverdeal();
            $this->modellog = new \app\admin\model\MemberWalletLog();
            $this->modelwallet = new \app\admin\model\MemberWallet();

            // 重新获取 USDT 钱包
            $usdt_wallet = \app\admin\model\MemberWallet::where('product_id',$usdt_product_id)
                ->where('uid',$this->memberInfo['id'])
                ->field('id,le_money')
                ->find();

            if(!$usdt_wallet || bc_sub($usdt_wallet['le_money'],$max_buy_num) < 0){
                return $this->error(lang('leverdeal.check_le_money_noenough',['tit'=>'USDT','num'=>floatVal($indata['account'])]));
            }

            Db::startTrans();
            try {
                $save = $this->model->save($indata);
                if(!$save){
                    throw new \Exception('save order fail');
                }

                $lastId = $this->model->id;
                $now_le_money = bc_sub($usdt_wallet['le_money'],$indata['rate_account']);
                $prowallet = $this->modelwallet->where('id',$usdt_wallet['id'])->update(['le_money'=>$now_le_money]);
                if(!$prowallet){
                    throw new \Exception('update wallet fail');
                }

                $logdata['account'] = $indata['account'];
                $logdata['wallet_id'] = $usdt_wallet['id'];
                $logdata['product_id'] = $usdt_product_id;
                $logdata['uid'] = session('member.id');
                $logdata['is_test'] = session('member.is_test');
                $logdata['before'] = $usdt_wallet['le_money'];
                $logdata['after'] = $now_le_money;
                $logdata['account_sxf'] = 0;
                $logdata['all_account'] = $indata['rate_account'];
                $logdata['type'] = 5;//合约订单
                $logdata['title'] = $proInfo['title'];
                $logdata['order_type'] = 1;//手续费
                $logdata['order_id'] = $lastId;

                $inlog = $this->modellog->save($logdata);
                if(!$inlog){
                    throw new \Exception('save wallet log fail');
                }

                Db::commit();
                return json(['code'=>1,'msg'=>lang('leverdeal.order_success'),'id'=>$lastId]);
            } catch (\Throwable $e) {
                Db::rollback();
                return $this->error(lang('public.do_fail'));
            }
        }
        return json(['code'=>0,'msg'=>lang('public.do_fail')]);
    }

    public function order_this()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','int');
            $id = $post['id'];
            if($id){
                $info = \app\admin\model\OrderLeverdeal::where('id',$id)->find();
                if(!$info || $info['status'] <> 1){
                    return json(['code'=>0,'msg'=>lang('leverdeal.order_status_question')]);
                }

                Db::startTrans();
                try {
                    $this->model = new \app\admin\model\OrderLeverdeal();
                    $save = $this->model->where('id',$info['id'])->where('status',1)->update([
                        'status'=>2,
                        'is_lock'=>1,
                        'close_type'=>1,
                        'close_price'=>$info['now_price']
                    ]);
                    if(!$save){
                        throw new \Exception('close order fail');
                    }

                    $this->modellog = new \app\admin\model\MemberWalletLog();
                    $this->modelwallet = new \app\admin\model\MemberWallet();

                    // 合约平仓返回利润到 USDT 账户的 le_money
                    $usdt_pid = \app\admin\model\ProductLists::where('base',1)->value('id');
                    $user_wallet = \app\admin\model\MemberWallet::where('product_id',$usdt_pid)
                        ->where('uid',$this->memberInfo['id'])
                        ->field('id,le_money')
                        ->find();

                    if(!$user_wallet){
                        throw new \Exception('wallet not found');
                    }

                    $now_le_money = bc_add($user_wallet['le_money'],$info['win_account']);
                    $prowallet = $this->modelwallet->where('id',$user_wallet['id'])->update(['le_money'=>$now_le_money]);
                    if(!$prowallet){
                        throw new \Exception('update wallet fail');
                    }

                    $logdata['account'] = $info['win_account'];
                    $logdata['wallet_id'] = $user_wallet['id'];
                    $logdata['product_id'] = $usdt_pid;
                    $logdata['uid'] = session('member.id');
                    $logdata['is_test'] = session('member.is_test');
                    $logdata['before'] = $user_wallet['le_money'];
                    $logdata['after'] = $now_le_money;
                    $logdata['account_sxf'] = 0;
                    $logdata['all_account'] = $info['win_account'];
                    $logdata['type'] = 5;//合约订单
                    $logdata['title'] = $info['title'];
                    $logdata['order_type'] = $info['is_win'] + 10;//手动平仓
                    $logdata['order_id'] = $id;

                    $inlog = $this->modellog->save($logdata);
                    if(!$inlog){
                        throw new \Exception('save wallet log fail');
                    }

                    Db::commit();
                    return json(['code'=>1,'msg'=>lang('leverdeal.order_status_success'),'id'=>$id]);
                }catch (\Throwable $e) {
                    Db::rollback();
                    return $this->error(lang('public.do_fail'));
                }
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
                $info = \app\admin\model\OrderLeverdeal::where('id',$id)
                    ->where('status',1)
                    ->field('id,win_account,now_price,stop_profit_price,stop_loss_price')
                    ->find();
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
                    $list[$k]['stop_profit_text'] = (float)$v['stop_profit_price'] > 0 ? $v['stop_profit_price'] : '--';
                    $list[$k]['stop_loss_text'] = (float)$v['stop_loss_price'] > 0 ? $v['stop_loss_price'] : '--';
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
        return json(['code'=>0,'data'=>[],'pages'=>0]);
    }

    public function listb()
    {
        if(request()->isPost()){
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
                    $list[$k]['close_type_text'] = lang('leverdeal.close_type_'.$v['close_type']);
                }
            }
            return json(['code'=>1,'data'=>$list,'pages'=>floor($count/$limit)]);
        }
        return json(['code'=>0,'data'=>[],'pages'=>0]);
    }
}