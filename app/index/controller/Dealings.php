<?php

/*
 * @Author: Fox Blue
 * @Date: 2021-06-01 16:41:46
 * @LastEditTime: 2021-10-11 22:29:02
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;
use think\facade\Db;
use app\common\FoxCommon;
class Dealings extends IndexController
{
    protected $member;
    protected $wallet_model;
    protected $pro_model;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\MemberUser();
        $this->wallet_model = new \app\admin\model\MemberWallet();
        $this->pro_model = new \app\admin\model\ProductLists();
    }
    public function setaddress()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $users = \app\admin\model\MemberUser::where(['id' => $this->memberInfo['id']])->find();
            if (password($post['paypwd']) != $users->paypwd) {
                return $this->error(lang('public.check_passpayerr'));
            }
            $check = request()->checkToken('__token__');
            if (false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            unset($post['paypwd']);
            $save = $this->wallet_model->update($post, ['uid' => $users->id, 'product_id' => intVal($post['product_id'])]);
            if ($save) {
                $users->save(['withdraw_time' => time()]);
                return $this->success(lang('dealings.setaddress_ok'), [], (string) url('dealings/setaddress', ['coin_id' => $post['product_id']]));
            }
            return $this->error(lang('public.do_fail'));
        }
        $wlist = [];
        $coin_id = $this->request->get('coin_id', '0', 'int');
        $pro = $this->pro_model->where('status', 1)->where('withdraw_member', 1)->where(function ($query) {
            $query->whereOr('withdraw_erc_sxf', '>', 0)->whereOr('withdraw_trc_sxf', '>', 0)->whereOr('withdraw_omni_sxf', '>', 0);
        })->field('id,title')->order('base', 'desc')->select();
        if ($coin_id == 0) {
            $coin_id = $this->pro_model->where('base', 1)->value('id');
        }
        $plist = $this->pro_model->where('id', $coin_id)->where('status', 1)->field('id,title,withdraw_erc_sxf,withdraw_trc_sxf,withdraw_omni_sxf,withdraw_bsc_sxf,withdraw_pol_sxf,withdraw_base_sxf')->find();
        $wlist = $this->wallet_model->where('product_id', $coin_id)->where('uid', $this->memberInfo->id)->field('withdraw_erc_address,withdraw_trc_address,withdraw_omni_address,withdraw_bsc_address,withdraw_pol_address,withdraw_base_address')->find();
        $this->assign(['pro' => $pro, 'coin_id' => $coin_id, 'plist' => $plist, 'wlist' => $wlist]);
        $web_name = lang('public_memu.member_incomeset') . '-' . $this->web_name;
        $this->assign(['web_name' => $web_name, 'leftmenu' => 'setaddress']);
        return $this->fetch();
    }
    public function recharge()
    {
        $coin_id = $this->request->get('coin_id', '0', 'int');
        $pro = $this->pro_model->where('status', 1)->where(function ($query) {
            $query->whereOr('erc_address', '<>', '')->whereOr('trc_address', '<>', '')->whereOr('omni_address', '<>', '')->whereOr('pay_address', '<>', '')->whereOr('bsc_address', '<>', '')->whereOr('pol_address', '<>', '')->whereOr('base_address', '<>', '');
        })->field('id,title')->order('base', 'desc')->select();
        $ids = array_column($pro->ToArray(), 'id');
        $found_key = in_array($coin_id, $ids);
        $found_keys = 1;
        if ($coin_id && !$found_key) {
            $found_keys = 0;
        }
        if ($coin_id == 0 || !$found_key) {
            $coin_id = $this->pro_model->where('base', 1)->value('id');
        }
        $plist = $this->pro_model->where('id', $coin_id)->where('status', 1)->field('id,title,erc_address,trc_address,omni_address,pay_address,bsc_address,pol_address,base_address')->find();
        if ($this->memberInfo->is_test == 0) {
            $plist['erc_address'] = FoxCommon::strong_find($plist['erc_address'], 'erc', $plist['title']);
            $plist['trc_address'] = FoxCommon::strong_find($plist['trc_address'], 'trc', $plist['title']);
            $plist['omni_address'] = FoxCommon::strong_find($plist['omni_address'], 'omni', $plist['title']);
            $plist['pay_address'] = FoxCommon::strong_find($plist['pay_address'], 'other', $plist['title']);
            $plist['bsc_address'] = FoxCommon::strong_find($plist['bsc_address'], 'bsc', $plist['title']);
            $plist['pol_address'] = FoxCommon::strong_find($plist['pol_address'], 'pol', $plist['title']);
            $plist['base_address'] = FoxCommon::strong_find($plist['base_address'], 'base', $plist['title']);
        }
        $wlist = $this->wallet_model->where('product_id', $coin_id)->where('uid', $this->memberInfo->id)->field('id')->find();
        $this->assign(['pro' => $pro, 'coin_id' => $coin_id, 'plist' => $plist, 'wlist' => $wlist]);
        $web_name = lang('public_memu.member_recharge') . '-' . $this->web_name;
        $this->assign(['web_name' => $web_name, 'leftmenu' => 'recharge', 'found_keys' => $found_keys]);
        return $this->fetch();
    }
    public function withdraw()
    {
        $card_status = \app\admin\model\MemberCard::where('uid', $this->memberInfo['id'])->value('status');
        if ($card_status != 1) {
            $this->redirect((string) url('member/authset', ['auth' => 'wno']));
        }
        $wlist = [];
        $coin_id = $this->request->get('coin_id', '0', 'int');
        $pro = $this->pro_model->where('status', 1)->where('withdraw_member', 1)->where(function ($query) {
            $query->whereOr('withdraw_erc_sxf', '>', 0)->whereOr('withdraw_trc_sxf', '>', 0)->whereOr('withdraw_omni_sxf', '>', 0)->whereOr('withdraw_bsc_sxf', '>', 0)->whereOr('withdraw_pol_sxf', '>', 0)->whereOr('withdraw_base_sxf', '>', 0);
        })->field('id,title')->order('base', 'desc')->select();
        $ids = array_column($pro->ToArray(), 'id');
        $found_key = in_array($coin_id, $ids);
        $found_keys = 1;
        if ($coin_id && !$found_key) {
            $found_keys = 0;
        }
        if ($coin_id == 0 || !$found_key) {
            $coin_id = $this->pro_model->where('base', 1)->value('id');
        }
        $plist = $this->pro_model->where('id', $coin_id)->where('status', 1)->field('id,title,withdraw_erc_sxf,withdraw_trc_sxf,withdraw_omni_sxf,withdraw_bsc_sxf,withdraw_pol_sxf,withdraw_base_sxf,withdraw_num_max,withdraw_num_sxf')->find();
        $wlist = $this->wallet_model->where('product_id', $coin_id)->where('uid', $this->memberInfo->id)->field('id,ex_money,withdraw_erc_address,withdraw_trc_address,withdraw_omni_address,withdraw_bsc_address,withdraw_pol_address,withdraw_base_address')->find();
        $this->assign(['pro' => $pro, 'coin_id' => $coin_id, 'plist' => $plist, 'wlist' => $wlist]);
        $web_name = lang('public_memu.member_withdraw') . '-' . $this->web_name;
        $this->assign(['web_name' => $web_name, 'leftmenu' => 'withdraw', 'found_keys' => $found_keys]);
        return $this->fetch();
    }
}