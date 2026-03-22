<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-10-12 12:03:50
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use think\App;
use think\facade\Env;
use think\facade\Db;
use app\common\FoxKline;
use app\common\FoxCommon;

class Finance extends MobileController
{
    protected $modellog;
    protected $modelwallet;
    protected $m_pro;
    protected $modelpro;
    protected $walletlog;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $sum_ex = 0;
        $sum_le = 0;
        $sum_op = 0;
        $sum_up = 0;
        $sum_cm = 0;
        $all_ex = Db::name('member_wallet')
        ->alias('a')
        ->where('a.uid',session('member.id'))
        ->where('a.ex_money','>',0)
        ->join('product_lists p ','p.id= a.product_id')
        ->field('a.ex_money,p.close,p.base')
        ->select();
        foreach($all_ex as $k => $v){
            if($v['base'] == 1){
                $sum_ex = $sum_ex + $v['ex_money'];
            } else {
                $sum_ex = $sum_ex+ FoxKline::get_me_price_usdt_to_usd_close($v['ex_money'],$v['close'],8);
            }
        }
        $all_le = Db::name('member_wallet')
        ->alias('a')
        ->where('a.uid',session('member.id'))
        ->where('a.le_money','>',0)
        ->join('product_lists p ','p.id= a.product_id')
        ->field('a.le_money,p.close,p.base')
        ->select();
        foreach($all_le as $k => $v){
            if($v['base'] == 1){
                $sum_le = $sum_le + $v['le_money'];
            } else {
                $sum_le = $sum_le+FoxKline::get_me_price_usdt_to_usd_close($v['le_money'],$v['close'],8);
            }
        }
        $all_op = Db::name('member_wallet')
        ->alias('a')
        ->where('a.uid',session('member.id'))
        ->where('a.op_money','>',0)
        ->join('product_lists p ','p.id= a.product_id')
        ->field('a.op_money,p.close,p.base')
        ->select();
        foreach($all_op as $k => $v){
            if($v['base'] == 1){
                $sum_op = $sum_op + $v['op_money'];
            } else {
                $sum_op = $sum_op+FoxKline::get_me_price_usdt_to_usd_close($v['op_money'],$v['close'],8);
            }
        }
        $all_up = Db::name('member_wallet')
        ->alias('a')
        ->where('a.uid',session('member.id'))
        ->where('a.up_money','>',0)
        ->join('product_lists p ','p.id= a.product_id')
        ->field('a.up_money,p.close,p.base')
        ->select();
        foreach($all_up as $k => $v){
            if($v['base'] == 1){
                $sum_up = $sum_up + $v['up_money'];
            } else {
                $sum_up = $sum_up+FoxKline::get_me_price_usdt_to_usd_close($v['up_money'],$v['close'],8);
            }
        }
        $all_cm = Db::name('member_wallet')
        ->alias('a')
        ->where('a.uid',session('member.id'))
        ->where('a.cm_money','>',0)
        ->join('product_lists p ','p.id= a.product_id')
        ->field('a.cm_money,p.close,p.base')
        ->select();
        foreach($all_cm as $k => $v){
            if($v['base'] == 1){
                $sum_cm = $sum_cm + $v['cm_money'];
            } else {
                $sum_cm = $sum_cm+FoxKline::get_me_price_usdt_to_usd_close($v['cm_money'],$v['close'],8);
            }
        }
        $all_sum_usd = $sum_ex+$sum_le+$sum_op+$sum_up+$sum_cm;
        $this->assign('all_sum_usd',$all_sum_usd);
        $this->assign('footmenu','finance');
    }
    
    public function fund()
    {
        $walletlist = Db::name('member_wallet')
        ->alias('a')
        ->join('member_user m ','m.id= a.uid')
        ->where('a.uid',session('member.id'))
        ->join('product_lists p ','p.id= a.product_id')
        ->where('p.status','=',1)
        ->order('p.popularity_sort','desc')
        ->order('p.base','desc')
        ->field('a.*,p.title,p.base,p.code,p.close,p.popularity_sort')
        ->select();

        if(isset($walletlist)){
            $walletlist = $walletlist->ToArray();
            $sum_usd = 0;
            foreach($walletlist as $k => $v){
                $total_money = bc_add(bc_add(bc_add(bc_add($v['ex_money'],$v['le_money']),$v['op_money']),$v['up_money']),$v['cm_money']);
                $walletlist[$k]['total_money'] = $total_money;
                if($v['base'] == 1){
                    $walletlist[$k]['total_usd'] = $total_money;
                } else {
                    $walletlist[$k]['total_usd'] = FoxKline::get_me_price_usdt_to_usd_close($total_money,$v['close'],8);
                }
                $sum_usd = $sum_usd+$walletlist[$k]['total_usd'];
                // 过滤掉所有余额为0的资产
                if($total_money <= 0){
                    // 但保留主流币种（流行度排序>0的）
                    if($v['popularity_sort'] <= 0){
                        unset($walletlist[$k]);
                    }
                }
            }
            $walletlist = array_values($walletlist);
        }
        $this->assign('UID',session('member.id'));
        $this->assign('walletlist',$walletlist);
        $this->assign('sum_usd',$sum_usd);
        $web_name = lang('mobile_finance.fund_title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'finance','leftmenu'=>'fund']);
        return $this->fetch();
    }

    public function index()
    {
        $walletlist = Db::name('member_wallet')
        ->alias('a')
        ->join('member_user m ','m.id= a.uid')
        ->where('a.uid',session('member.id'))
        ->join('product_lists p ','p.id= a.product_id')
        ->where('p.types','like','%1%')
        ->order('p.popularity_sort','desc')
        ->order('p.base','desc')
        ->field('a.*,p.title,p.base,p.code,p.pay_address,p.omni_address,p.trc_address,p.erc_address,p.bsc_address,p.pol_address,p.base_address,p.withdraw_member,p.withdraw_sx_fee,p.close,p.withdraw_erc_sxf,p.withdraw_trc_sxf,p.withdraw_omni_sxf')
        ->select();

        if(isset($walletlist)){
            $walletlist = $walletlist->ToArray();
            $sum_usd = 0;
            foreach($walletlist as $k => $v){
                if($v['base'] == 1){
                    $walletlist[$k]['ex_usd'] = $v['ex_money'];
                } else {
                    $walletlist[$k]['ex_usd'] = FoxKline::get_me_price_usdt_to_usd_close($v['ex_money'],$v['close'],8);
                }
                $sum_usd = $sum_usd+$walletlist[$k]['ex_usd'];
            }
        }
        $cando = $this->request->get('cando','0','int');
        $this->assign('cando',$cando);
        $this->assign('UID',session('member.id'));
        $this->assign('walletlist',$walletlist);
        $this->assign('sum_usd',$sum_usd);
        $web_name = lang('finance.ex_title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'finance','leftmenu'=>'ex']);
        return $this->fetch();
    }

    public function lefinance()
    {
        $walletlist = Db::name('member_wallet')
        ->alias('a')
        ->join('member_user m ','m.id= a.uid')
        ->where('a.uid',session('member.id'))
        ->join('product_lists p ','p.id= a.product_id')
        ->where(function($query){
            $query->where('p.types','like','%2%')->whereOr('p.base','=',1);
        })
        ->order('p.popularity_sort','desc')
        ->order('p.base','desc')
        ->field('a.*,p.title,p.code,p.close')
        ->select();

        if(isset($walletlist)){
            $walletlist = $walletlist->ToArray();
            $sum_usd = 0;
            foreach($walletlist as $k => $v){
                $walletlist[$k]['le_usd'] = FoxKline::get_me_price_usdt_to_usd_close($v['le_money'],$v['close'],8);
                $sum_usd = $sum_usd+$walletlist[$k]['le_usd'];
            }
        }
        $this->assign('UID',session('member.id'));
        $this->assign('walletlist',$walletlist);
        $this->assign('sum_usd',$sum_usd);
        $web_name = lang('finance.le_title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'finance','leftmenu'=>'le']);
        return $this->fetch();
    }

    public function opfinance()
    {
        $walletlist = Db::name('member_wallet')
        ->alias('a')
        ->join('member_user m ','m.id= a.uid')
        ->where('a.uid',session('member.id'))
        ->join('product_lists p ','p.id= a.product_id')
        ->where('p.types','like','%3%')
        ->where('p.base','=','1')
        ->order('p.popularity_sort','desc')
        ->order('p.base','desc')
        ->field('a.*,p.title,p.code,p.close')
        ->select();

        if(isset($walletlist)){
            $walletlist = $walletlist->ToArray();
            $sum_usd = 0;
            foreach($walletlist as $k => $v){
                $walletlist[$k]['op_usd'] = FoxKline::get_me_price_usdt_to_usd_close($v['op_money'],$v['close'],8);
                $sum_usd = $sum_usd+$walletlist[$k]['op_usd'];
            }
        }
        $this->assign('UID',session('member.id'));
        $this->assign('walletlist',$walletlist);
        $this->assign('sum_usd',$sum_usd);
        $web_name = lang('finance.op_title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'finance','leftmenu'=>'op']);
        return $this->fetch();
    }

    public function upfinance()
    {
        $walletlist = Db::name('member_wallet')
        ->alias('a')
        ->join('member_user m ','m.id= a.uid')
        ->where('a.uid',session('member.id'))
        ->join('product_lists p ','p.id= a.product_id')
        ->where('p.types','like','%4%')
        ->order('p.popularity_sort','desc')
        ->order('p.base','desc')
        ->field('a.*,p.title,p.code,p.close')
        ->select();

        if(isset($walletlist)){
            $walletlist = $walletlist->ToArray();
            $sum_usd = 0;
            foreach($walletlist as $k => $v){
                $walletlist[$k]['up_usd'] = FoxKline::get_me_price_usdt_to_usd_close($v['up_money'],$v['close'],8);
                $sum_usd = $sum_usd+$walletlist[$k]['up_usd'];
            }
        }
        $this->assign('UID',session('member.id'));    
        $this->assign('walletlist',$walletlist);
        $this->assign('sum_usd',$sum_usd);
        $web_name = lang('finance.up_title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'finance','leftmenu'=>'up']);
        return $this->fetch();
    }

    public function cmfinance()
    {
        $walletlist = Db::name('member_wallet')
        ->alias('a')
        ->join('member_user m ','m.id= a.uid')
        ->where('a.uid',session('member.id'))
        ->join('product_lists p ','p.id= a.product_id')
        ->where('p.base','=','1')
        ->order('p.popularity_sort','desc')
        ->order('p.base','desc')
        ->field('a.*,p.title,p.code,p.close')
        ->select();

        if(isset($walletlist)){
            $walletlist = $walletlist->ToArray();
            $sum_usd = 0;
            foreach($walletlist as $k => $v){
                $walletlist[$k]['cm_usd'] = FoxKline::get_me_price_usdt_to_usd_close($v['cm_money'],$v['close'],8);
                $sum_usd = $sum_usd+$walletlist[$k]['cm_usd'];
            }
        }
        $this->assign('UID',session('member.id'));
        $this->assign('walletlist',$walletlist);
        $this->assign('sum_usd',$sum_usd);
        $web_name = lang('finance.cm_title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'finance','leftmenu'=>'cm']);
        return $this->fetch();
    }

    public function transfer()
    {
        if(request()->isPost()){
            $post = $this->request->post();
            $indata = [];
            $indata['account'] = $post['account'];
            $indata['from'] = $post['before_type'];
            $indata['to'] = $post['after_type'];
            $indata['product_id'] = $post['product_id'];
            if($indata['account'] <= 0){
                return $this->error(lang('finance.transfer_num_check'));
            }
            if(!$indata['product_id']){
                return $this->error(lang('finance.transfer_product_check'));
            }
            $cointitle = \app\admin\model\ProductLists::where('id',$indata['product_id'])->value('title');
            if($indata['from'] == 1){
                $walletinfo = \app\admin\model\MemberWallet::where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                if($walletinfo['ex_money'] < $indata['account']){
                    return $this->error(lang('coin_wallet.'.$indata['from']).fox_all_replace(lang('finance.transfer_account_noenough'),$indata['account'].$cointitle));
                }
            }else if($indata['from'] == 2){
                $walletinfo = \app\admin\model\MemberWallet::where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                if($walletinfo['le_money'] < $indata['account']){
                    return $this->error(lang('coin_wallet.'.$indata['from']).fox_all_replace(lang('finance.transfer_account_noenough'),$indata['account'].$cointitle));
                }
            }else if($indata['from'] == 3){
                $walletinfo = \app\admin\model\MemberWallet::where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                if($walletinfo['op_money'] < $indata['account']){
                    return $this->error(lang('coin_wallet.'.$indata['from']).fox_all_replace(lang('finance.transfer_account_noenough'),$indata['account'].$cointitle));
                }
            }else if($indata['from'] == 4){
                $walletinfo = \app\admin\model\MemberWallet::where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                if($walletinfo['up_money'] < $indata['account']){
                    return $this->error(lang('coin_wallet.'.$indata['from']).fox_all_replace(lang('finance.transfer_account_noenough'),$indata['account'].$cointitle));
                }
            }else if($indata['from'] == 5){
                $walletinfo = \app\admin\model\MemberWallet::where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                if($walletinfo['cm_money'] < $indata['account']){
                    return $this->error(lang('coin_wallet.'.$indata['from']).fox_all_replace(lang('finance.transfer_account_noenough'),$indata['account'].$cointitle));
                }
            }
            $indata['wallet_id'] = $walletinfo['id'];
            $indata['uid'] = session('member.id');
            $indata['is_test'] = session('member.is_test');
            $indata['type'] = 3;
            $indata['status'] = 2;
            $indata['title'] = $cointitle.':'.lang('coin_wallet.'.$indata['from']).'-'.lang('coin_wallet.'.$indata['to']);
            $indata['account_sxf'] = 0;
            $indata['all_account'] = bc_sub($indata['account'],$indata['account_sxf']);
            
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $this->modellog = new \app\admin\model\MemberWalletLog();
                $save = $this->modellog->save($indata);
                if($save){
                    $this->modelwallet = new \app\admin\model\MemberWallet();
                    if($indata['from'] == 1){
                        $a = bc_sub($walletinfo['ex_money'],$indata['all_account']);
                        if($indata['to'] == 2){
                            $b = bc_add($walletinfo['le_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['ex_money'=>$a,'le_money'=>$b],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 3){
                            $b = bc_add($walletinfo['op_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['ex_money'=>$a,'op_money'=>$b],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 4){
                            $b = bc_add($walletinfo['up_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['ex_money'=>$a,'up_money'=>$b],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 5){
                            $b = bc_add($walletinfo['cm_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['ex_money'=>$a,'cm_money'=>$b],['id'=>$indata['wallet_id']]);
                        }
                    }else if($indata['from'] == 2){
                        $a = bc_sub($walletinfo['le_money'],$indata['all_account']);
                        if($indata['to'] == 1){
                            $b = bc_add($walletinfo['ex_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['le_money'=>$a,'ex_money'=>$b],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 3){
                            $b = bc_add($walletinfo['op_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['le_money'=>$a,'op_money'=>$b],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 4){
                            $b = bc_add($walletinfo['up_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['le_money'=>$a,'up_money'=>$b],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 5){
                            $b = bc_add($walletinfo['cm_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['le_money'=>$a,'cm_money'=>$b],['id'=>$indata['wallet_id']]);
                        }
                    }else if($indata['from'] == 3){
                        $a = bc_sub($walletinfo['op_money'],$indata['all_account']);
                        if($indata['to'] == 1){
                            $b = bc_add($walletinfo['ex_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['ex_money'=>$b,'op_money'=>$a],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 2){
                            $b = bc_add($walletinfo['le_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['le_money'=>$b,'op_money'=>$a],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 4){
                            $b = bc_add($walletinfo['up_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['up_money'=>$b,'op_money'=>$a],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 5){
                            $b = bc_add($walletinfo['cm_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['cm_money'=>$b,'op_money'=>$a],['id'=>$indata['wallet_id']]);
                        }
                    }else if($indata['from'] == 4){
                        $a = bc_sub($walletinfo['up_money'],$indata['all_account']);
                        if($indata['to'] == 1){
                            $b = bc_add($walletinfo['ex_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['ex_money'=>$b,'up_money'=>$a],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 2){
                            $b = bc_add($walletinfo['le_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['le_money'=>$b,'up_money'=>$a],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 3){
                            $b = bc_add($walletinfo['op_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['op_money'=>$b,'up_money'=>$a],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 5){
                            $b = bc_add($walletinfo['cm_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['cm_money'=>$b,'up_money'=>$a],['id'=>$indata['wallet_id']]);
                        }
                    }else if($indata['from'] == 5){
                        $a = bc_sub($walletinfo['cm_money'],$indata['all_account']);
                        if($indata['to'] == 1){
                            $b = bc_add($walletinfo['ex_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['ex_money'=>$b,'cm_money'=>$a],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 2){
                            $b = bc_add($walletinfo['le_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['le_money'=>$b,'cm_money'=>$a],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 3){
                            $b = bc_add($walletinfo['op_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['op_money'=>$b,'cm_money'=>$a],['id'=>$indata['wallet_id']]);
                        }else if($indata['to'] == 4){
                            $b = bc_add($walletinfo['up_money'],$indata['all_account']);
                            $test = $this->modelwallet->update(['up_money'=>$b,'cm_money'=>$a],['id'=>$indata['wallet_id']]);
                        }
                    }
                }
            } catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save && $test){
                if($indata['to'] == 1){
                    $url = (string)url('finance/index');
                }else if($indata['to'] == 2){
                    $url = (string)url('finance/lefinance');
                }else if($indata['to'] == 3){
                    $url = (string)url('finance/opfinance');
                }else if($indata['to'] == 4){
                    $url = (string)url('finance/upfinance');
                }else if($indata['to'] == 5){
                    $url = (string)url('finance/cmfinance');
                }
                return $this->success(lang('finance.transfer_account_ok'),[],$url);
            }
            return $this->error(lang('public.do_fail'));
        }
        $type = $this->request->get('type','','int');
        $this->assign('type',$type);
        $web_name = lang('finance.tf_title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'finance','leftmenu'=>'tf']);
        return $this->fetch();
    }

    public function transferlog()
    {
        $web_name = lang('finance.transfer_logs').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'finance','leftmenu'=>'tf']);
        return $this->fetch();
    }

    public function get_product()
    {
        if(request()->isPost()){
            $post = $this->request->post();
            $from = !empty($post['from'])?$post['from']:'';
            $to = !empty($post['to'])?$post['to']:'';
            if($from && $to){
                $this->m_pro = new \app\admin\model\ProductLists();
                if($from){
                    if($from >4){
                        $fromwhere[] = ['status','=','1'];
                        $fromwhere[] = ['base','=','1'];
                    }else{
                        $fromwhere[] = ['types','like','%'.$from.'%'];
                        $fromwhere[] = ['status','=','1'];
                    }
                    $fromproduct = $this->m_pro->where($fromwhere)->order('sort','desc')->field('id,title')->select();
                }
                if($to){
                    if($to >4){
                        $towhere[] = ['status','=','1'];
                        $towhere[] = ['base','=','1'];
                    }else{
                        $towhere[] = ['types','like','%'.$to.'%'];
                        $towhere[] = ['status','=','1'];
                    }
                    $toproduct = $this->m_pro->where($towhere)->order('sort','desc')->field('id,title')->select();
                }
                // 计算 from 和 to 的交集
                $new_array = [];
                if($fromproduct && $toproduct){
                    $fromproduct = $fromproduct->ToArray();
                    $toproduct = $toproduct->ToArray();
                    $new_array = array_values(array_filter($fromproduct, function ($v, $k) use ($toproduct) {
                        foreach ($toproduct as $key => $val) {
                            if ($val['id'] == $v['id']) {
                                return true;
                            }
                        }
                        return false;
                    }, ARRAY_FILTER_USE_BOTH));
                }
                // 强制加入USDT（无论交集是否含有，始终显示）
                $usdt_list = (new \app\admin\model\ProductLists())->where('status','1')->where('title','USDT')->field('id,title')->select()->toArray();
                foreach($usdt_list as $usdt){
                    $already = false;
                    foreach($new_array as $item){
                        if($item['id'] == $usdt['id']){ $already = true; break; }
                    }
                    if(!$already){ $new_array[] = $usdt; }
                }
                // 常用货币优先排序：USDT > BTC > ETH > 其余字母序
                $priority = ['USDT'=>1,'BTC'=>2,'ETH'=>3];
                usort($new_array, function($a, $b) use ($priority){
                    $pa = isset($priority[$a['title']]) ? $priority[$a['title']] : 999;
                    $pb = isset($priority[$b['title']]) ? $priority[$b['title']] : 999;
                    if($pa !== $pb) return $pa - $pb;
                    return strcmp($a['title'], $b['title']);
                });
                return json(['code'=>1,'data'=>array_values($new_array)]);
            }else{
                return json(['code'=>0]);
            }
        }
        return json(['code'=>0]);
    }

    public function before_coin_type(){
        if(request()->isPost()){
            $post = $this->request->post();
            $type = !empty($post['type'])?$post['type']:'';
            $to = !empty($post['to'])?$post['to']:'0';
            if($type){
                $coin_types = lang('coin_wallet');
                $types = [];
                foreach($coin_types as $k => $v){
                    if($to == 1){
                        if($k <> $type){
                            $types[$k]['key'] = $k;
                            $types[$k]['vol'] = $v;
                        }
                    }else{
                        $types[$k]['key'] = $k;
                        $types[$k]['vol'] = $v;
                    }
                }
                $types = array_values($types);
                return json(['code'=>1,'data'=>$types]);
            }else{
                return json(['code'=>0]);
            }
        }
        return json(['code'=>0]);
    }

    public function after_coin_type(){
        if(request()->isPost()){
            $post = $this->request->post();
            $type = !empty($post['type'])?$post['type']:'';
            $to = !empty($post['to'])?$post['to']:'0';
            if($type){
                $coin_types = lang('coin_wallet');
                $types = [];
                foreach($coin_types as $k => $v){
                    if($to == 1){
                        if($k <> $type){
                            $types[$k]['key'] = $k;
                            $types[$k]['vol'] = $v;
                        }
                    }else{
                        $types[$k]['key'] = $k;
                        $types[$k]['vol'] = $v;
                    }
                }
                $types = array_values($types);
                return json(['code'=>1,'data'=>$types]);
            }else{
                return json(['code'=>0]);
            }
        }
        return json(['code'=>0]);
    }

    public function get_wallet()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','int');
            $product_id = $post['product_id'];
            $from = $post['from'];
            $to = $post['to'];
            $info = [];
            if($product_id){
                $info['title'] = \app\admin\model\ProductLists::where('id',$product_id)->value('title');
                if($from == 1){
                    $info['money'] = \app\admin\model\MemberWallet::where('product_id',$product_id)->where('uid',$this->memberInfo['id'])->value('ex_money');
                }else if($from == 2){
                    $info['money'] = \app\admin\model\MemberWallet::where('product_id',$product_id)->where('uid',$this->memberInfo['id'])->value('le_money');
                }else if($from == 3){
                    $info['money'] = \app\admin\model\MemberWallet::where('product_id',$product_id)->where('uid',$this->memberInfo['id'])->value('op_money');
                }else if($from == 4){
                    $info['money'] = \app\admin\model\MemberWallet::where('product_id',$product_id)->where('uid',$this->memberInfo['id'])->value('up_money');
                }else if($from == 5){
                    $info['money'] = \app\admin\model\MemberWallet::where('product_id',$product_id)->where('uid',$this->memberInfo['id'])->value('cm_money');
                }
                return json(['code'=>1,'data'=>$info]);
            }
            return json(['code'=>0]);
        }
        return json(['code'=>0]);
    }

    public function recharge()
    {
        if(request()->isPost()){
            $post = $this->request->post();
            $indata = [];
            $indata['account'] = $post['recharge_account'];
            if($indata['account'] <= 0){
                return $this->error(lang('finance.recharge_num_check'));
            }
            $indata['wallet_id'] = $post['wallet_id'];
            $indata['product_id'] = $post['product_id'];
            $indata['up_pic'] = $post['recharge_pic'];
            if(empty($indata['up_pic'])){
                return $this->error(lang('finance.recharge_pic_check'));
            }
            $usdt_recharge_type = !empty($post['usdt_recharge_type'])?$post['usdt_recharge_type']:'';
            if($usdt_recharge_type ==1){
                $indata['address'] = $post['omni_address'];
                $indata['title'] = 'Omni';
            }else if($usdt_recharge_type ==2){
                $indata['address'] = $post['trc_address'];
                $indata['title'] = 'TRC20';
            }else if($usdt_recharge_type ==3){
                $indata['address'] = $post['erc_address'];
                $indata['title'] = 'ERC20';
            }else if($usdt_recharge_type ==5){
                $indata['address'] = $post['bsc_address'];
                $indata['title'] = 'BSC';
            }else if($usdt_recharge_type ==6){
                $indata['address'] = $post['pol_address'];
                $indata['title'] = 'POL';
            }else if($usdt_recharge_type ==7){
                $indata['address'] = $post['base_address'];
                $indata['title'] = 'Base';
            }else{
                $indata['address'] = $post['pay_address'];
                $indata['title'] = 'OTHER';
            }
            $indata['uid'] = session('member.id');
            $indata['is_test'] = session('member.is_test');
            $this->modelwallet = new \app\admin\model\MemberWallet();
            $walletinfo = $this->modelwallet->where('id',$indata['wallet_id'])->find();
            $indata['before'] = $walletinfo['ex_money'];
            $indata['account_sxf'] = 0;
            $indata['all_account'] = bc_sub($indata['account'],$indata['account_sxf']);
            $indata['type'] = 1;
            $indata['status'] = 1;
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $this->modellog = new \app\admin\model\MemberWalletLog();
                $save = $this->modellog->save($indata);
                if($save && session('member.is_test')==1){
                    $lastId = $this->modellog->id;
                    $after = bc_add($indata['before'],$indata['all_account']);
                    if($this->modellog->update(['status'=>2,'after'=>$after],['id'=>$lastId])){
                        $this->modelwallet->update([
                            'ex_money'=>bc_add($walletinfo['ex_money'],$indata['all_account']),
                            'le_money'=>bc_add($walletinfo['le_money'],$indata['all_account']),
                            'op_money'=>bc_add($walletinfo['op_money'],$indata['all_account']),
                            'up_money'=>bc_add($walletinfo['up_money'],$indata['all_account']),
                            'cm_money'=>bc_add($walletinfo['cm_money'],$indata['all_account']),
                        ],['id'=>$indata['wallet_id']]);
                    }
                }
            } catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save && session('member.is_test')==1){
                return $this->success(lang('finance.recharge_success'),[],(string)url('dealings/recharge',['coin_id'=>$indata['product_id']]));
            }
            if($save){
                return $this->success(lang('finance.recharge_sub'),[],(string)url('dealings/recharge',['coin_id'=>$indata['product_id']]));
            }
            return $this->error(lang('public.do_fail'));
        }
    }

    public function withdraw(){
        if(request()->isPost()){
            $post = $this->request->post();
            $indata = [];
            $indata['account'] = $post['withdraw_account'];
            if($indata['account'] <= 0){
                return $this->error(lang('finance.withdraw_num_check'));
            }
            if(floor($indata['account']) != $indata['account']){
                return $this->error('提现数量必须为整数');
            }
            $type = $post['type'];
            $withdraw_address = $post['withdraw_address'][$type];
            if(empty($withdraw_address)){
                return $this->error(lang('finance.withdraw_address_check'));
            }
            $indata['title'] = $type;
            $indata['wallet_id'] = $post['wallet_id'];
            $indata['product_id'] = $post['product_id'];
            $indata['uid'] = session('member.id');
            $indata['is_test'] = session('member.is_test');
            $this->modelwallet = new \app\admin\model\MemberWallet();
            $walletinfo = $this->modelwallet->where('id',$indata['wallet_id'])->find();
            $this->modelpro = new \app\admin\model\ProductLists();
            $proinfo = $this->modelpro->where('id',$indata['product_id'])->field('withdraw_sx_fee,withdraw_trc_sxf,withdraw_erc_sxf,withdraw_omni_sxf,withdraw_bsc_sxf,withdraw_pol_sxf,withdraw_base_sxf,withdraw_min_num,withdraw_max_num,withdraw_day_num,withdraw_num_max,withdraw_num_sxf')->find();
            $this->modellog = new \app\admin\model\MemberWalletLog();
            $withdraw_day_limit = 10;
            $t = strtotime(date('Y-m-d'));
            $today_count = $this->modellog->where('create_time','>',$t)->where('type',2)->where('uid',session('member.id'))->count('id');
            if($today_count >= $withdraw_day_limit){
                return $this->error(lang('finance.withdraw_today_can_num',['can'=>$withdraw_day_limit,'num'=>$today_count]));
            }
            $sxf = 1;
            $indata['before'] = $walletinfo['ex_money'];
            $indata['account_sxf'] = $sxf;
            $indata['all_account'] = bc_sub($indata['account'],$indata['account_sxf']);
            $indata['type'] = 2;
            $indata['status'] = 1;
            $indata['address'] = $withdraw_address;
            if($walletinfo['ex_money'] < $indata['account']){
                return $this->error(fox_all_replace(lang('finance.withdraw_enugh_num'),$walletinfo['ex_money']));
            }
            if($proinfo['withdraw_max_num']>0){
                if($indata['account'] > $proinfo['withdraw_max_num']){
                    return $this->error(fox_all_replace(lang('finance.withdraw_max_num'),$proinfo['withdraw_max_num']));
                }
            }
            $withdraw_min_num = 10;
            if($indata['account'] < $withdraw_min_num){
                return $this->error(fox_all_replace(lang('finance.withdraw_min_num'),$withdraw_min_num));
            }
            $paypwd = $this->memberInfo['paypwd'];
            if($paypwd <> password($post['paypwd'])){
                return $this->error(lang('finance.withdraw_paypwd_wrong'));
            }
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $save = $this->modellog->save($indata);
                if($save){
                    $lastId = $this->modellog->id;
                    $after = bc_sub($indata['before'],$indata['account']);
                    $lock = bc_add($walletinfo['lock_ex_money'],$indata['account']);
                    if($this->memberInfo['is_test']==1){
                        if($this->modellog->update(['status'=>2,'after'=>$after],['id'=>$lastId])){
                            $test = $this->modelwallet->update(['ex_money'=>$after],['id'=>$indata['wallet_id']]);
                        }
                    }else{
                        $saves = $this->modelwallet->update(['ex_money'=>$after,'lock_ex_money'=>$lock],['id'=>$indata['wallet_id']]);
                    }
                }
            } catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if(isset($test)){
                return $this->success(lang('finance.withdraw_success'),[],(string)url('dealings/withdraw',['coin_id'=>$indata['product_id']]));
            }
            if($saves){
                return $this->success(lang('finance.withdraw_sub'),[],(string)url('dealings/withdraw',['coin_id'=>$indata['product_id']]));
            }
            return $this->error(lang('public.do_fail'));
        }
    }

    public function findlog()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'0','int');
            $id = $post['id'];
            $page = $post['page'];
            $type = $post['type'];
            $from = (!empty($post['from']))?$post['from']:'';
            $to = (!empty($post['to']))?$post['to']:'';
            $cm = (!empty($post['cm']))?$post['cm']:'';
            
            $limit = 6;
            $this->walletlog = new \app\admin\model\MemberWalletLog();
            if($type==1){
                $where[] = ['type','in','1,2,3,8,9,13,14'];
                $list = $this->walletlog->where('wallet_id',$id)
                    ->where('uid',$this->memberInfo['id'])
                    ->where($where)
                    ->page($page, $limit)
                    ->field('create_time,account,account_sxf,all_account,status,status_msg,type,title,remark')
                    ->order('create_time','desc')
                    ->select();
                $count = $this->walletlog->where('wallet_id',$id)
                    ->where('uid',$this->memberInfo['id'])
                    ->where($where)
                    ->count('id');
            }
            if($type==2){
                $where[] = ['type','=',$type];
                $list = $this->walletlog->where('wallet_id',$id)
                    ->where('uid',$this->memberInfo['id'])
                    ->where($where)
                    ->page($page, $limit)
                    ->field('create_time,account,account_sxf,all_account,status,status_msg,type,title,remark')
                    ->order('create_time','desc')
                    ->select();
                $count = $this->walletlog->where('wallet_id',$id)
                    ->where('uid',$this->memberInfo['id'])
                    ->where($where)
                    ->count('id');
            }
            if($type>2){
                if($cm==1){
                    $map1 = [
                        ['type','=',$type],
                        ['from', '=', $from],
                        ['uid', '=', $this->memberInfo['id']],
                        ['wallet_id', '=', $id]
                    ];
                    $map2 = [
                        ['type','=',$type],
                        ['to', '=', $to],
                        ['uid', '=', $this->memberInfo['id']],
                        ['wallet_id', '=', $id]
                    ];
                    $map3 = [
                        ['type','in','11,12'],
                        ['to', '=', $this->memberInfo['id']],
                        ['uid', '=', $this->memberInfo['id']],
                        ['wallet_id', '=', $id]
                    ];
                    $list = $this->walletlog->whereOr([ $map1, $map2, $map3 ])
                        ->page($page, $limit)
                        ->field('create_time,account,account_sxf,all_account,status,status_msg,type,title,remark')
                        ->order('create_time','desc')
                        ->select();
                    $count = $this->walletlog->whereOr([ $map1, $map2, $map3 ])
                        ->count('id');
                }else{
                    $map1 = [
                        ['type','=',$type],
                        ['from', '=', $from],
                        ['uid', '=', $this->memberInfo['id']],
                        ['wallet_id', '=', $id]
                    ];
                    $map2 = [
                        ['type','=',$type],
                        ['to', '=', $to],
                        ['uid', '=', $this->memberInfo['id']],
                        ['wallet_id', '=', $id]
                    ];
                    $list = $this->walletlog->whereOr([ $map1, $map2 ])
                        ->page($page, $limit)
                        ->field('create_time,account,account_sxf,all_account,status,status_msg,type,title,remark')
                        ->order('create_time','desc')
                        ->select();
                    $count = $this->walletlog->whereOr([ $map1, $map2 ])
                        ->count('id');
                }
                
            }
            
            $lists = [];
            if($list){
                foreach($list as $k => $v){
                    $lists[$k]['create_time'] = $v['create_time'];
                    if($v['type']==3){
                        $lists[$k]['title'] = $v['title'];
                    }else{
                        $lists[$k]['title'] = $v['account'];
                    }
                    if($v['type']==2){
                        $lists[$k]['account_sxf'] = $v['account_sxf'];
                    }else{
                        $lists[$k]['account_sxf'] = '------';
                    }
                    if($v['type']>10){
                        if($v['remark']){
                            $lists[$k]['remark'] = $v['remark'].'%';
                        }else{
                            $lists[$k]['remark'] = '------';
                        }
                    }else{
                        $lists[$k]['remark'] = '------';
                    }
                    $lists[$k]['all_account'] = $v['all_account'];
                    $lists[$k]['status'] = lang('wallet_log_status.'.$v['status']);
                    $lists[$k]['status_msg'] = '';
                    if($v['status']==3 && !empty($v['status_msg'])){
                        $lists[$k]['status_msg'] = $v['status_msg'];
                        $lists[$k]['status'] = lang('wallet_log_status.'.$v['status']).'('.$v['status_msg'].')';
                    }
                    $lists[$k]['type'] = lang('wallet_log_type.'.$v['type']);
                }
            }
            return json(['code'=>1,'data'=>$lists,'pages'=>floor($count/$limit)]);
        }
    }

    public function findalllog()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'0','int');
            $page = $post['page'];
            $where[] = ['type','=',3];
            
            $limit = 10;
            $this->walletlog = new \app\admin\model\MemberWalletLog();
            $list = $this->walletlog->where('uid',$this->memberInfo['id'])
                ->where($where)
                ->page($page, $limit)
                ->field('create_time,account,account_sxf,all_account,status,type,title')
                ->order('create_time','desc')
                ->select();
            $count = $this->walletlog->where('uid',$this->memberInfo['id'])
            ->where($where)
            ->count('id');
            $lists = [];
            if($list){
                foreach($list as $k => $v){
                    $lists[$k]['create_time'] = $v['create_time'];
                    if($v['type']==3){
                        $lists[$k]['title'] = $v['title'];
                    }else{
                        $lists[$k]['title'] = $v['account'];
                    }
                    $lists[$k]['all_account'] = $v['all_account'];
                    $lists[$k]['status'] = lang('wallet_log_status.'.$v['status']);
                    $lists[$k]['type'] = lang('wallet_log_type.'.$v['type']);
                }
            }
            return json(['code'=>1,'data'=>$lists,'pages'=>floor($count/$limit)]);
        }
    }

    public function userwallet()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $pages = $post['pages'];
            $code = $post['code'];
            $money = [];
            if($code){
                $productBase = \app\admin\model\ProductLists::where('base',1)->field('id,title')->find();
                if($pages == 'deal'){
                    $productInfo = \app\admin\model\ProductLists::where('code',$code)->field('id,title,close,ex_buy_min,ex_sell_min')->find();
                    $money['money'] = \app\admin\model\MemberWallet::where('product_id',$productInfo['id'])->where('uid',$this->memberInfo['id'])->value('ex_money');
                    $money['usdt'] = \app\admin\model\MemberWallet::where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->value('ex_money');
                    $money['usdt_tit'] = $productBase['title'];
                    $money['pro_tit'] = $productInfo['title'];
                    // $money['usdt'] = floatVal($money['usdt']);
                    // $money['money'] = floatVal($money['money']);
                    if($productInfo['close'] > 0){
                        $money['buy_max'] = bc_div($money['usdt'],$productInfo['close']);
                    }else{
                        $money['buy_max'] = 0;
                    }
                    $money['sell_max'] = $money['money'];
                    $money['ex_buy_min'] = $productInfo['ex_buy_min'];
                    $money['ex_sell_min'] = $productInfo['ex_sell_min'];
                }else if($pages == 'leverdeal'){
                    $productInfo = \app\admin\model\ProductLists::where('code',$code)->field('id,title,close,le_sx_fee,le_day_sx_fee,le_no_sx_fee,le_play_time,le_play_type,le_order_rate')->find();
                    // 合约可用余额统一使用 USDT 钱包的 le_money（与下单扣款一致）
                    $usdt_wallet = \app\admin\model\MemberWallet::where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money')->find();
                    $money['money'] = $usdt_wallet ? floatval($usdt_wallet['le_money']) : 0;
                    // USDT总余额使用 USDT 钱包所有子账户之和
                    $money['usdt_balance'] = $usdt_wallet ? floatval($usdt_wallet['ex_money']) + floatval($usdt_wallet['le_money']) + floatval($usdt_wallet['op_money']) + floatval($usdt_wallet['up_money']) + floatval($usdt_wallet['cm_money']) : 0;
                    $money['close_price'] = $productInfo['close'] ? $productInfo['close'] : 0;
                    $money['pro_tit'] = $productInfo['title'];
                    $money['balance_tit'] = 'USDT';
                    $money['le_play_time'] = $productInfo['le_play_time'];
                    $money['le_sx_fee'] = $productInfo['le_sx_fee'];
                    $money['le_sx_fee_100'] = round_pad_zero($productInfo['le_sx_fee']*100,2) .'%';
                    $money['le_order_rate'] = $productInfo['le_order_rate'];
                }else if($pages == 'seconds'){
                    $productInfo = \app\admin\model\ProductLists::where('code',$code)->field('id,title,close,op_sx_fee,op_play_time,op_play_price,op_play_prop,op_play_max,op_order_min,op_order_max')->find();
                    $money['money'] = \app\admin\model\MemberWallet::where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->value('op_money');
                    $money['pro_tit'] = $productBase['title'];
                    $money['op_play_price'] = explode(',',$productInfo['op_play_price']);
                    $money['op_play_time'] = explode(',',$productInfo['op_play_time']);
                    $money['op_play_prop'] = explode(',',$productInfo['op_play_prop']);
                }
                
                return json(['code'=>1,'data'=>$money]);
            }
        }
    }

    /**
     * @Title: 上传回调
     * @param {int} $code
     * @param {*} $msg
     * @param {*} $data
     */
    public function up_message(int $code = 0, $msg = '', $data = '')
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];

        return json($result);
    }
    /**
     * @Title: 上传证照
     */
    public function upload_pic()
    {
        $data = [
            'file' => request()->file('file'),
        ];
        $data['paths'] = 'upload/pic/'.$this->memberInfo['id'];
        try {
            $this->validate($data,'Pic');
        } catch (\Exception $e) {
            return $this->up_message(1,$e->getMessage());
        }
        $fileName = date('YmdHis').rand(1000,9999);
        $savename = \think\facade\Filesystem::disk('public')->putFile($data['paths'], $data['file'], function() use ($fileName){
            return $fileName;
        });

        if($savename){
            $path = '/'.$savename;
            $rdata = [];
            $rdata['url'] = $path;
            $rdata['src'] = $path;
            return $this->up_message(0,lang('public.upload_ok'),$rdata);
        }else{
            return $this->up_message(1,lang('public.upload_fail'));
        }
    }
    
}

