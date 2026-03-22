<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-01 16:41:46
 * @LastEditTime: 2021-10-11 20:54:28
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;
use think\facade\Db;
use app\common\FoxCommon;

class Member extends IndexController
{
    protected $member;
    protected $card_model;
    protected $wallet_model;
    protected $modellog;
    protected $modellogs;
    protected $model_deal;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\MemberUser();
        $this->member = $this->model->where('id',session('member.id'))->find()->ToArray();
        if(!$this->member['head_img']){
            $this->member['head_img'] = sysconfig('base','member_avatar');
        }
        unset($this->member['password']);
        unset($this->member['paypwd']);
        $this->assign(['member'=>$this->member]);

        $this->card_model = new \app\admin\model\MemberCard();
        $this->wallet_model = new \app\admin\model\MemberWallet();
    }

    public function account()
    {
        $safe = 20;
        $safe_x = lang('member.member_safe_a');
        if($this->member['username'] == $this->member['phone'] || $this->member['phone_time']){
            $safe = $safe+20;
            $phone = 1;
        }else{
            $phone = 0;
        }
        if($this->member['paypwd_time']){
            $safe = $safe+20;
            $paypwd = 1;
        }else{
            $paypwd = 0;
        }
        $card = $this->card_model::where('uid',$this->member['id'])->where('status',1)->find();
        if($card){
            $safe = $safe+20;
        }
        $this->assign(['phone'=>$phone,'paypwd'=>$paypwd]);
        if($safe >= 80){
            $safe_x = lang('member.member_safe_c');
        }else if($safe >= 60){
            $safe_x = lang('member.member_safe_b');
        }
        $this->assign(['safe'=>$safe,'safe_x'=>$safe_x]);
        $web_name = lang('public_memu.member_account').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'leftmenu'=>'account']);
        $invite_code_url = server_url().(string)url('wicket/register',['code'=>$this->member['invite_code']]);
        $this->assign(['invite_code_url'=>$invite_code_url]);
        $invite_code_img = phpqrcode( $invite_code_url,'invite_code_'.$this->member['id']);
        $this->assign(['invite_code_img'=>$invite_code_img]);
        $prefix_code = \think\facade\Config::get('phone.prefix_code');
        $this->assign(['prefix_code'=>$prefix_code]);
        return $this->fetch();
    }

    public function team()
    {
        $web_name = lang('public_memu.member_team').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'leftmenu'=>'team']);
        $invite_code_url = server_url().(string)url('wicket/register',['code'=>$this->member['invite_code']]);
        $this->assign(['invite_code_url'=>$invite_code_url]);
        $invite_code_img = phpqrcode( $invite_code_url,'invite_code_'.$this->member['id']);
        $this->assign(['invite_code_img'=>$invite_code_img]);
        $prefix_code = \think\facade\Config::get('phone.prefix_code');
        $this->assign(['prefix_code'=>$prefix_code]);
        // 下级IDS
        $level_ids = FoxCommon::level_uids_arr($this->member['id'],1);
        $myteam_num = count($level_ids);
        // 下级IDS树
        $this->assign(['myteam_num'=>$myteam_num]);
        $level_idss = FoxCommon::level_uids($this->member['id'],1);
        $m_num =[];
        $set_level = sysconfig('base','member_level');
        $level_member = fox_abc_slice($set_level);
        foreach($level_member as $k => $v){
            $m_num[$k+1]['num'] = count(FoxCommon::find_level_uids($level_idss,($k+1)));
        }
        $this->assign(['m_num'=>$m_num,'level_member'=>$level_member]);
        return $this->fetch();
    }

    public function get_poster(){
        $invite_code_url = server_url().(string)url('wicket/register',['code'=>$this->member['invite_code']]);
        $invite_code_img = phpqrcode( $invite_code_url,'invite_code_'.$this->member['id']);
        $config = array(
            'image'=>array(
              array(
                'url' =>$invite_code_img,
                'stream'=>0,
                'left'=>272,
                'top'=>462,
                'right'=>0,
                'bottom'=>0,
                'width'=>180,
                'height'=>180,
                'opacity'=>100
              )
            ),
            'background'=>app()->getRootPath() . 'public/upload/poster/bg.jpg'          //背景图
        );
        
        $filename = app()->getRootPath() . 'public/upload/poster/'.time().'.jpg';
        //echo createPoster($config,$filename);
        return createPoster($config);
    }

    public function getcodes()
    {
        if(request()->isPost()){
            $data=request()->post();
            $tousername = $data['username'];
            $prefix = $data['prefix'];
            $users = \app\admin\model\MemberUser::where(function($query) use ($tousername){
                $query->where(['username'=>$tousername])->whereOr(['phone'=>$tousername]);
            })->find();
            if($users){
                return $this->error(lang('wicket_page.Validate_member_isphone'));
            }
            $sms_send_count = sysconfig('base','send_count');
            $count=\app\admin\model\SystemCode::where(['phone'=>$tousername,'useable'=>0])->count('id');
            if($sms_send_count > 0 && $count > $sms_send_count){
                return $this->error(fox_all_replace(lang('wicket_page.code_send_num'),$count));
            }
            
            if(!$prefix){
                return $this->success(lang('send_email.send_prefix_no'));
            }
            $cdata['phone'] = $tousername;
            $cdata['code'] = rand(1000,9999);
            $cdata['useable'] = 0;
            $cdata['ip'] = getRealIp();
            if(sysconfig('site','site_type')=='online'){
                if(FoxCommon::feige_send($cdata['code'],$cdata['phone'],$prefix)){
                    \app\admin\model\SystemCode::create($cdata);
                    return $this->success(lang('send_email.sendsms_ok'));
                }else{
                    return $this->success(lang('send_email.sendsms_no'));
                }
            }else{
                \app\admin\model\SystemCode::create($cdata);
                return $this->success(lang('send_email.sendsms_ok'));
            }
        }
    }

    public function setphone()
    {
        if(request()->isPost()){
            $username=request()->post('username','','trim');
            $prefix=request()->post('prefix','','trim');
            $code=request()->post('code','','trim');

            $userst = \app\admin\model\MemberUser::where(function($query) use ($username){
                $query->where(['username'=>$username])->whereOr(['phone'=>$username]);
            })->find();
            if($userst){
                return $this->error(lang('wicket_page.Validate_member_isphone'));
            }

            $codes = \app\admin\model\SystemCode::where(['phone'=>$username,'code'=>$code,'useable'=>0])->order('create_time','desc')->limit(1)->select()->ToArray();
            if(!$codes){
                return $this->error(lang('wicket_page.Validate_code_isnot'));
            }
            $indata['prefix'] = $prefix;
            $indata['phone'] = $username;
            $indata['phone_time'] = time();

            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $save = $this->model->update($indata,['id'=>$this->member['id']]);
            } catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save){
                return $this->success(lang('member.member_phone_setok'),[],(string)url('member/account'));
            }
            return $this->error(lang('public.do_fail'));
        }
    }

    public function setpass()
    {
        $users = \app\admin\model\MemberUser::where(['id'=>$this->member['id']])->find();
        if(request()->isPost()){
            $opass=request()->post('opass','','trim');
            $npass=request()->post('npass','','trim');
            $cpass=request()->post('cpass','','trim');

            if($npass <> $cpass){
                return $this->error(lang('wicket_page.register_check_pass'));
            }

            if (password($opass) != $users->password) {
                return $this->error(lang('member.member_check_passworderr'));
            }
            
            $indata['password'] = password($npass);

            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $save = $this->model->update($indata,['id'=>$this->member['id']]);
            } catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save){
                return $this->success(lang('member.member_password_setok'),[],(string)url('member/account'));
            }
            return $this->error(lang('public.do_fail'));
        }
    }

    public function setpaypass()
    {
        $users = \app\admin\model\MemberUser::where(['id'=>$this->member['id']])->find();
        if(request()->isPost()){
            $paypass=request()->post('paypass','','trim');
            $cpaypass=request()->post('cpaypass','','trim');
            $opaypass=request()->post('opaypass','','trim');
            $epaypass=request()->post('epaypass','','trim');
            $ecpaypass=request()->post('ecpaypass','','trim');
            
            if($opaypass){
                if($epaypass <> $ecpaypass){
                    return $this->error(lang('wicket_page.register_check_pass'));
                }
                if (password($opaypass) != $users->paypwd) {
                    return $this->error(lang('member.member_check_passpayerr'));
                }
                $indata['paypwd'] = password($epaypass);
            }else{
                if($paypass <> $cpaypass){
                    return $this->error(lang('wicket_page.register_check_pass'));
                }
                $indata['paypwd'] = password($paypass);
                $indata['paypwd_time'] = time();
            }
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $save = $this->model->update($indata,['id'=>$this->member['id']]);
            } catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save){
                return $this->success(lang('member.member_paypwd_setok'),[],(string)url('member/account'));
            }
            return $this->error(lang('public.do_fail'));
        }
    }
    
    public function tradelog()
    {
        $web_name = lang('public_memu.member_tradelog').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'leftmenu'=>'tradelog']);
        return $this->fetch();
    }

    public function incomeset()
    {
        if ($this->request->isAjax()) {
            $users = \app\admin\model\MemberUser::where(['id'=>$this->member['id']])->find();
            $post = $this->request->post();
            if (password($post['paypwd']) != $users->paypwd) {
                return $this->error(lang('public.check_passpayerr'));
            }
            if(sysconfig('base','withdraw_time')>0){
                $t = time();
                $set = FoxCommon::ReadMyfile($users->id.'_wallet_set');
                if(($t-$set) < sysconfig('base','withdraw_time')*60*60*24){
                    return $this->error(fox_all_replace(lang('incomeset.set_time'),sysconfig('base','withdraw_time')));
                }
            }
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            $indata = [];
            foreach($post['withdraw'] as $k => $v){
                if($v){
                    $kk = explode('_',$k);
                    $indata[$k]['id'] = $kk[0];
                    if(strstr($k, 'TRC')){
                        $indata[$k]['withdraw_trc_address'] = $v;
                    }
                    if(strstr($k, 'ERC')){
                        $indata[$k]['withdraw_erc_address'] = $v;
                    }
                    if(strstr($k, 'OMNI')){
                        $indata[$k]['withdraw_omni_address'] = $v;
                    }
                    $indata[$k]['withdraw_time'] = time();
                    FoxCommon::WriteMyfile($users->id.'_wallet_'.$k,$v);
                }
            }
            FoxCommon::WriteMyfile($users->id.'_wallet_set',time());
            $indata = array_reverse($indata);
            $save = $this->wallet_model->saveAll($indata);
            if($save){
                $users->save(['withdraw_time'=>time()]);
                return $this->success(lang('incomeset.set_ok'),[],(string)url('member/incomeset'));
            }
            return $this->error(lang('public.do_fail'));
        }
        $web_name = lang('public_memu.member_incomeset').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'leftmenu'=>'incomeset']);
        $where[] = ['productLists.withdraw_member','=',1];
        $where[] = ['uid','=',$this->member['id']];
        $wallet = $this->wallet_model
            ->withJoin(['productLists'], 'LEFT')
            ->where($where)
            ->order('base','desc')
            ->field('member_wallet.id,title,withdraw_erc_sxf,withdraw_trc_sxf,withdraw_omni_sxf,withdraw_erc_address,withdraw_trc_address,withdraw_omni_address')
            ->select();
        $wlist = [];
        if($wallet){
            foreach ($wallet as $k => $v){
                if($v['withdraw_erc_sxf'] > 0){
                    Array_push($wlist,['title'=>$v['title'].'/ERC','type'=>$v['id'].'_ERC','sxf'=>$v['withdraw_erc_sxf'],'withdraw_address'=>$v['withdraw_erc_address']]);
                }
                if($v['withdraw_trc_sxf'] > 0){
                    Array_push($wlist,['title'=>$v['title'].'/TRC','type'=>$v['id'].'_TRC','sxf'=>$v['withdraw_trc_sxf'],'withdraw_address'=>$v['withdraw_trc_address']]);
                }
                if($v['withdraw_omni_sxf'] > 0){
                    Array_push($wlist,['title'=>$v['title'].'/OMNI','type'=>$v['id'].'_OMNI','sxf'=>$v['withdraw_omni_sxf'],'withdraw_address'=>$v['withdraw_omni_address']]);
                }
            }
        }
        $this->assign(['wlist'=>$wlist]);
        return $this->fetch();
    }

    public function authset()
    {
        $auth = $this->request->get('auth','','trim');
        $this->assign(['auth'=>$auth]);
        $card = $this->card_model::where('uid',$this->member['id'])->find();
        $this->assign(['card'=>$card]);
        if(request()->isPost()){
            $data=request()->post();
            unset($data['file']);
            $data['status'] = 0;
            $data['uid'] = $this->member['id'];
            $data['update_time'] = time();
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            if($card){
                if($card['status']==1){
                    return $this->error(lang('authset.status_1'));
                }else{
                    try {
                        $save = $this->card_model::update($data,['id'=>$card['id']]);
                    } catch (\Exception $e) {
                        return $this->error(lang('public.do_fail'));
                    }
                    if($save){
                        return $this->success(lang('authset.update_ok'),[],(string)url('member/authset'));
                    }
                }
            }else{
                $data['create_time'] = time();
                try {
                    $save = $this->card_model::create($data);
                } catch (\Exception $e) {
                    return $this->error(lang('public.do_fail'));
                }
                if($save){
                    return $this->success(lang('authset.save_ok'),[],(string)url('member/authset'));
                }
            }
        }
        $web_name = lang('public_memu.member_authset').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'leftmenu'=>'authset']);
        return $this->fetch();
    }

    /**
     * @Title: 一键收集功能
     */    
    public function turnusdt()
    {
        if(sysconfig('member','turn_usdt') <> 'open'){
            return $this->error(lang('public.do_fail'));
        }
        $all_money = Db::name('member_wallet')
        ->alias('a')
        ->where('a.uid',session('member.id'))
        ->join('product_lists p ','p.id= a.product_id')
        ->field('a.ex_money,a.op_money,a.le_money,a.up_money,a.cm_money,p.close,p.title,p.base,p.id')
        ->where('p.status',1)
        ->order('p.base','desc')
        ->select();
        $money_list = [];
        if($all_money){
            foreach($all_money as $k => $v){
                if($v['ex_money'] > 0 || $v['op_money'] > 0 || $v['le_money'] > 0 || $v['up_money'] > 0 || $v['cm_money'] > 0){
                    $money_list[$k]['ex_money'] = $v['ex_money'];
                    $money_list[$k]['ex_title'] = lang('tradelog.tab_list_a');//币币
                    $money_list[$k]['op_money'] = $v['op_money'];
                    $money_list[$k]['op_title'] = lang('tradelog.tab_list_c');//期权
                    $money_list[$k]['le_money'] = $v['le_money'];
                    $money_list[$k]['le_title'] = lang('tradelog.tab_list_b');//合约
                    $money_list[$k]['up_money'] = $v['up_money'];
                    $money_list[$k]['up_title'] = lang('tradelog.tab_list_d');//理财
                    $money_list[$k]['cm_money'] = $v['cm_money'];
                    $money_list[$k]['cm_title'] = lang('tradelog.tab_list_f');//佣金
                    $money_list[$k]['title'] = $v['title'];
                    $money_list[$k]['base'] = $v['base'];
                }
            }
        }
        $counts = count($money_list);
        $web_name = lang('public_memu.member_turn_usdt').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'leftmenu'=>'turnusdt','money_list'=>$money_list,'counts'=>$counts]);
        return $this->fetch();
    }

    public function doturnusdt(){
        if(sysconfig('member','turn_usdt') <> 'open'){
            return $this->error(lang('public.do_fail'));
        }
        if(request()->isPost()){
            $all_money = Db::name('member_wallet')
            ->alias('a')
            ->where('a.uid',session('member.id'))
            ->join('product_lists p ','p.id= a.product_id')
            ->field('a.id as wallet_id,a.ex_money,a.op_money,a.le_money,a.up_money,a.cm_money,p.close,p.title,p.base,p.id as product_id')
            ->where('p.status',1)
            ->order('p.base','desc')
            ->select();
            if($all_money){
                //先转到币币
                foreach($all_money as $k => $v){
                    if($v['base']==0){
                        $indata = [];
                        $indata['to'] = 1;
                        $indata['product_id'] = $v['product_id'];
                        $indata['wallet_id'] = $v['wallet_id'];
                        $indata['uid'] = session('member.id');
                        $indata['is_test'] = session('member.is_test');
                        $indata['type'] = 3;
                        $indata['status'] = 2;
                        $cointitle = \app\admin\model\ProductLists::where('id',$indata['product_id'])->value('title');
                        if($v['op_money'] > 0){
                            $walletinfo = \app\admin\model\MemberWallet::where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                            $indata['from'] = 3;
                            $indata['account'] = $v['op_money'];
                            $indata['title'] = $cointitle.':'.lang('coin_wallet.'.$indata['from']).'-'.lang('coin_wallet.'.$indata['to']);
                            $indata['account_sxf'] = 0;
                            $indata['all_account'] = bc_sub($indata['account'],$indata['account_sxf']);
                            $this->modellog = new \app\admin\model\MemberWalletLog();
                            $save = $this->modellog->save($indata);
                            if($save){
                                $b = bc_add($walletinfo['ex_money'],$indata['all_account']);
                                \app\admin\model\memberwallet::update(['ex_money'=>$b,'op_money'=>0],['id'=>$indata['wallet_id']]);
                            }
                        }
                        if($v['le_money'] > 0){
                            $walletinfo = \app\admin\model\MemberWallet::where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                            $indata['from'] = 2;
                            $indata['account'] = $v['le_money'];
                            $indata['title'] = $cointitle.':'.lang('coin_wallet.'.$indata['from']).'-'.lang('coin_wallet.'.$indata['to']);
                            $indata['account_sxf'] = 0;
                            $indata['all_account'] = bc_sub($indata['account'],$indata['account_sxf']);
                            $this->modellog = new \app\admin\model\MemberWalletLog();
                            $save = $this->modellog->save($indata);
                            if($save){
                                $b = bc_add($walletinfo['ex_money'],$indata['all_account']);
                                \app\admin\model\memberwallet::update(['ex_money'=>$b,'le_money'=>0],['id'=>$indata['wallet_id']]);
                            }
                        }
                        if($v['up_money'] > 0){
                            $walletinfo = \app\admin\model\MemberWallet::where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                            $indata['from'] = 4;
                            $indata['account'] = $v['up_money'];
                            $indata['title'] = $cointitle.':'.lang('coin_wallet.'.$indata['from']).'-'.lang('coin_wallet.'.$indata['to']);
                            $indata['account_sxf'] = 0;
                            $indata['all_account'] = bc_sub($indata['account'],$indata['account_sxf']);
                            $this->modellog = new \app\admin\model\MemberWalletLog();
                            $save = $this->modellog->save($indata);
                            if($save){
                                $b = bc_add($walletinfo['ex_money'],$indata['all_account']);
                                \app\admin\model\memberwallet::update(['ex_money'=>$b,'up_money'=>0],['id'=>$indata['wallet_id']]);
                            }
                        }
                        if($v['cm_money'] > 0){
                            $indata['from'] = 5;
                            $walletinfo = \app\admin\model\MemberWallet::where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                            $indata['account'] = $v['cm_money'];
                            $indata['title'] = $cointitle.':'.lang('coin_wallet.'.$indata['from']).'-'.lang('coin_wallet.'.$indata['to']);
                            $indata['account_sxf'] = 0;
                            $indata['all_account'] = bc_sub($indata['account'],$indata['account_sxf']);
                            $this->modellog = new \app\admin\model\MemberWalletLog();
                            $save = $this->modellog->save($indata);
                            if($save){
                                $b = bc_add($walletinfo['ex_money'],$indata['all_account']);
                                \app\admin\model\memberwallet::update(['ex_money'=>$b,'cm_money'=>0],['id'=>$indata['wallet_id']]);
                            }
                        }
                    }
                }
            }
            $all_money_ex = Db::name('member_wallet')
            ->alias('a')
            ->where('a.uid',session('member.id'))
            ->join('product_lists p ','p.id= a.product_id')
            ->field('a.id as wallet_id,a.ex_money,p.close,p.title,p.base,p.id as product_id')
            ->where('p.status',1)
            ->order('p.base','desc')
            ->select();
            if($all_money_ex){
                $productBase = \app\admin\model\ProductLists::where('base',1)->field('id,title')->find();
                foreach($all_money_ex as $k => $v){
                    if($v['base']==0){
                        if($v['ex_money']>0){
                            $indatas = [];
                            $pro = \app\admin\model\ProductLists::where('id',$v['product_id'])->where('status',1)->field('id,title,close,ex_sx_fee')->find();
                            $indatas['type'] = 2;//卖出
                            $indatas['status'] = 2;//市价
                            $indatas['price'] = 0;
                            $indatas['last_price'] = $pro['close'];
                            $indatas['direction'] = 2;
                            $indatas['price_usdt'] = bc_mul($v['ex_money'],$pro['close']);
                            $indatas['account'] = $v['ex_money'];
                            $indatas['account_sxf'] = 0;
                            $indatas['account_sxf_tit'] = $productBase['title'];
                            $indatas['account_product'] = bc_sub($indatas['price_usdt'],$indatas['account_sxf']);
                            $indatas['title'] = $pro['title'].'/'.$productBase['title'];
                            $indatas['product_id'] = $pro['id'];
                            $indatas['price_product'] = $pro['close'];
                            $indatas['uid'] = $this->memberInfo['id'];
                            $user_pro_wallet = \app\admin\model\memberwallet::where('product_id',$pro['id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                            $this->model_deal = new \app\admin\model\OrderDeal();
                            $save = $this->model_deal->save($indatas);
                            $lastId = $this->model_deal->id;
                            $now_ex_money = bc_sub($user_pro_wallet['ex_money'],$indatas['account']);
                            $prowallet = \app\admin\model\memberwallet::where('id',$user_pro_wallet['id'])->where('uid',$this->memberInfo['id'])->update(['ex_money'=>$now_ex_money]);
                            if($prowallet){
                                $logdata['account'] = $indatas['account'];
                                $logdata['wallet_id'] = $user_pro_wallet['id'];
                                $logdata['product_id'] = $user_pro_wallet['product_id'];
                                $logdata['uid'] = session('member.id');
                                $logdata['is_test'] = session('member.is_test');
                                $logdata['before'] = $user_pro_wallet['ex_money'];
                                $logdata['after'] = bc_sub($user_pro_wallet['ex_money'],$indatas['account']);
                                $logdata['account_sxf'] = 0;
                                $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                                $logdata['type'] = 4;
                                $logdata['title'] = $indatas['title'];
                                $logdata['order_type'] = 2;//卖出失
                                $logdata['order_id'] = $lastId;
                                $this->modellog = new \app\admin\model\MemberWalletLog();
                                $inlog = $this->modellog->save($logdata);
                                $base_wallet = \app\admin\model\memberwallet::where('product_id',$productBase['id'])->where('uid',$this->memberInfo['id'])->field('id,product_id,ex_money')->find();
                                $now_ex_money = bc_add($base_wallet['ex_money'],$indatas['account_product']);
                                if(\app\admin\model\memberwallet::where('id',$base_wallet['id'])->update(['ex_money'=>$now_ex_money])){
                                    $this->model_deal->where('id', $lastId)->update(['status'=>2,'update_time'=>time()]);
                                    $lgdata = [];
                                    $lgdata['account'] = $indatas['account_product'];
                                    $lgdata['wallet_id'] = $base_wallet['id'];
                                    $lgdata['product_id'] = $base_wallet['product_id'];
                                    $lgdata['uid'] = session('member.id');
                                    $lgdata['is_test'] = session('member.is_test');
                                    $lgdata['before'] = $base_wallet['ex_money'];
                                    $lgdata['after'] = bc_add($base_wallet['ex_money'],$indatas['account_product']);
                                    $lgdata['account_sxf'] = 0;
                                    $lgdata['all_account'] = bc_sub($lgdata['account'],$lgdata['account_sxf']);
                                    $lgdata['type'] = 4;
                                    $lgdata['title'] = $indatas['title'];
                                    $lgdata['order_type'] = 22;//卖出得
                                    $lgdata['order_id'] = $lastId;
                                    $this->modellogs = new \app\admin\model\MemberWalletLog();
                                    $this->modellogs->save($lgdata);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->success(lang('public_memu.member_turn_ok'),[],(string)url('member/turnusdt'));
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
    public function upload_card()
    {
        $data = [
            'file' => request()->file('file'),
        ];
        $data['paths'] = 'upload/cards/'.$this->member['id'];
        $this->validate($data,'Card');
        try {
            $this->validate($data,'Card');
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

    /**
     * @Title: 上传视频
     */
    public function upload_video()
    {
        $file = request()->file('file');
        if(!$file){
            return $this->up_message(1,'请选择视频文件');
        }
        if($file->getSize() > 10 * 1024 * 1024){
            return $this->up_message(1,'视频文件不能超过10MB');
        }
        $ext = strtolower($file->getOriginalExtension());
        if($ext != 'mp4'){
            return $this->up_message(1,'仅支持MP4格式视频');
        }
        $paths = 'upload/videos/'.$this->member['id'];
        $fileName = date('YmdHis').rand(1000,9999);
        $savename = \think\facade\Filesystem::disk('public')->putFile($paths, $file, function() use ($fileName){
            return $fileName;
        });

        if($savename){
            $path = '/'.$savename;
            $rdata = [];
            $rdata['url'] = $path;
            $rdata['src'] = $path;
            return $this->up_message(0,'视频上传成功',$rdata);
        }else{
            return $this->up_message(1,'视频上传失败');
        }
    }

    /**
     * @Title: 高级认证 - 视频认证
     */
    public function advancedAuth()
    {
        $cardRow = $this->card_model::where('uid',$this->member['id'])->find();
        if(!$cardRow || $cardRow['status'] != 1){
            return $this->error('请先完成基础身份认证');
        }
        $card = $cardRow->toArray();
        if(!isset($card['advanced_status'])) $card['advanced_status'] = 0;
        if(!isset($card['advanced_video'])) $card['advanced_video'] = '';
        if(!isset($card['advanced_remark'])) $card['advanced_remark'] = '';

        $this->assign(['card'=>$card]);

        if(request()->isPost()){
            $data = request()->post();
            if(empty($data['advanced_video'])){
                return $this->error('请上传认证视频');
            }
            $updateData = [
                'advanced_video' => $data['advanced_video'],
                'advanced_status' => 1,
                'advanced_time' => time(),
                'advanced_do_time'  => 0,
                'advanced_remark' => '',
            ];
            try {
                $save = $this->card_model::update($updateData, ['id'=>$cardRow['id']]);
            } catch (\Exception $e) {
                return $this->error('提交失败');
            }
            if($save){
                return $this->success('高级认证已提交，预计7个工作日内完成审核',[],(string)url('member/authset'));
            }
            return $this->error('提交失败');
        }

            $web_name = '高级认证-'.$this->web_name;
            $this->assign(['web_name'=>$web_name,'leftmenu'=>'authset']);
            return $this->fetch('advancedauth');
        }
    }

