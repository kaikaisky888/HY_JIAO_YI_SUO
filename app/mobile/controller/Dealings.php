<?php

/*
 * @Author: Fox Blue
 * @Date: 2021-06-01 16:41:46
 * @LastEditTime: 2021-10-11 23:07:50
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use think\App;
use think\facade\Env;
use think\facade\Db;
use app\common\FoxCommon;

class Dealings extends MobileController
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

            // 至少填写一个提现地址
            $addr_fields = ['withdraw_erc_address', 'withdraw_trc_address', 'withdraw_omni_address', 'withdraw_bsc_address', 'withdraw_pol_address', 'withdraw_base_address'];
            $has_one = false;
            foreach ($addr_fields as $f) {
                if (!empty($post[$f])) {
                    $has_one = true;
                    break;
                }
            }
            if (!$has_one) {
                return $this->error('请至少填写一个提现地址');
            }

            $save = $this->wallet_model->update($post, ['uid' => $users->id, 'product_id' => intval($post['product_id'])]);
            if ($save) {
                $users->save(['withdraw_time' => time()]);
                return $this->success(lang('dealings.setaddress_ok'), [], (string) url('dealings/setaddress', ['coin_id' => $post['product_id']]));
            }
            return $this->error(lang('public.do_fail'));
        }

        $wlist = [];
        $coin_id = $this->request->get('coin_id', '0', 'int');
        $pro = $this->pro_model->where('status', 1)->where('withdraw_member', 1)->where(function ($query) {
            $query->whereOr('withdraw_erc_sxf', '>', 0)
                ->whereOr('withdraw_trc_sxf', '>', 0)
                ->whereOr('withdraw_omni_sxf', '>', 0)
                ->whereOr('withdraw_bsc_sxf', '>', 0)
                ->whereOr('withdraw_pol_sxf', '>', 0)
                ->whereOr('withdraw_base_sxf', '>', 0);
        })->field('id,title')->order('base', 'desc')->select();

        if ($coin_id == 0) {
            $coin_id = $this->pro_model->where('base', 1)->value('id');
        }

        $plist = $this->pro_model->where('id', $coin_id)->where('status', 1)->field('id,title,withdraw_erc_sxf,withdraw_trc_sxf,withdraw_omni_sxf,withdraw_bsc_sxf,withdraw_pol_sxf,withdraw_base_sxf')->find();
        $wlist = $this->wallet_model->where('product_id', $coin_id)->where('uid', $this->memberInfo->id)->field('withdraw_erc_address,withdraw_trc_address,withdraw_omni_address,withdraw_bsc_address,withdraw_pol_address,withdraw_base_address')->find();

        $this->assign(['pro' => $pro, 'coin_id' => $coin_id, 'plist' => $plist, 'wlist' => $wlist]);
        return $this->fetch();
    }

    public function recharge()
    {
        $coin_id = $this->request->get('coin_id', '0', 'int');
        $pro = $this->pro_model->where('status', 1)->where(function ($query) {
            $query->whereOr('erc_address', '<>', '')
                ->whereOr('trc_address', '<>', '')
                ->whereOr('omni_address', '<>', '')
                ->whereOr('pay_address', '<>', '')
                ->whereOr('bsc_address', '<>', '')
                ->whereOr('pol_address', '<>', '')
                ->whereOr('base_address', '<>', '');
        })->field('id,title')->order('base', 'desc')->select();

        if ($coin_id == 0) {
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
        return $this->fetch();
    }

    /**
     * 提现页面
     */
    public function withdraw()
    {
        $card_status = \app\admin\model\MemberCard::where('uid', $this->memberInfo['id'])->value('status');
        if ($card_status != 1) {
            $this->redirect((string) url('member/authset', ['auth' => 'wno']));
        }

        $coin_id = $this->request->get('coin_id', '0', 'int');
        $pro = $this->getWithdrawProductList();
        $data = $this->buildWithdrawPageData($coin_id);

        $this->assign([
            'pro'       => $pro,
            'coin_id'   => $data['coin_id'],
            'plist'     => $data['plist'],
            'wlist'     => $data['wlist'],
            'networks'  => $data['networks'],
        ]);

        return $this->fetch();
    }

    /**
     * AJAX：同页切换提现币种数据
     */
    public function withdrawdata()
    {
        if (!$this->request->isAjax()) {
            return $this->error(lang('public.do_fail'));
        }

        $card_status = \app\admin\model\MemberCard::where('uid', $this->memberInfo['id'])->value('status');
        if ($card_status != 1) {
            return $this->error(lang('public.do_fail'), [], (string) url('member/authset', ['auth' => 'wno']));
        }

        $coin_id = $this->request->post('coin_id', 0, 'int');
        $data = $this->buildWithdrawPageData($coin_id);

        if (empty($data['plist'])) {
            return $this->error('币种不存在或暂不可提现');
        }

        return $this->success('ok', $data);
    }

    /**
     * 获取可提现币种列表
     */
    private function getWithdrawProductList()
    {
        return $this->pro_model
            ->where('status', 1)
            ->where('withdraw_member', 1)
            ->where(function ($query) {
                $query->whereOr('withdraw_erc_sxf', '>', 0)
                    ->whereOr('withdraw_trc_sxf', '>', 0)
                    ->whereOr('withdraw_omni_sxf', '>', 0)
                    ->whereOr('withdraw_bsc_sxf', '>', 0)
                    ->whereOr('withdraw_pol_sxf', '>', 0)
                    ->whereOr('withdraw_base_sxf', '>', 0);
            })
            ->field('id,title,base')
            ->order('base', 'desc')
            ->order('id', 'asc')
            ->select();
    }

    /**
     * 获取默认提现币种
     */
    private function getDefaultWithdrawCoinId()
    {
        $base_id = $this->pro_model
            ->where('status', 1)
            ->where('withdraw_member', 1)
            ->where('base', 1)
            ->where(function ($query) {
                $query->whereOr('withdraw_erc_sxf', '>', 0)
                    ->whereOr('withdraw_trc_sxf', '>', 0)
                    ->whereOr('withdraw_omni_sxf', '>', 0)
                    ->whereOr('withdraw_bsc_sxf', '>', 0)
                    ->whereOr('withdraw_pol_sxf', '>', 0)
                    ->whereOr('withdraw_base_sxf', '>', 0);
            })
            ->value('id');

        if (!empty($base_id)) {
            return intval($base_id);
        }

        $first = $this->getWithdrawProductList();
        if ($first && count($first) > 0) {
            return intval($first[0]['id']);
        }

        return 0;
    }

    /**
     * 构建提现页面/接口返回数据
     */
    private function buildWithdrawPageData($coin_id = 0)
    {
        $coin_id = intval($coin_id);
        if ($coin_id <= 0) {
            $coin_id = $this->getDefaultWithdrawCoinId();
        }

        $plist = $this->pro_model
            ->where('id', $coin_id)
            ->where('status', 1)
            ->where('withdraw_member', 1)
            ->field('id,title,withdraw_erc_sxf,withdraw_trc_sxf,withdraw_omni_sxf,withdraw_bsc_sxf,withdraw_pol_sxf,withdraw_base_sxf,withdraw_num_max,withdraw_num_sxf')
            ->find();

        if (empty($plist)) {
            $coin_id = $this->getDefaultWithdrawCoinId();
            $plist = $this->pro_model
                ->where('id', $coin_id)
                ->where('status', 1)
                ->where('withdraw_member', 1)
                ->field('id,title,withdraw_erc_sxf,withdraw_trc_sxf,withdraw_omni_sxf,withdraw_bsc_sxf,withdraw_pol_sxf,withdraw_base_sxf,withdraw_num_max,withdraw_num_sxf')
                ->find();
        }

        if (empty($plist)) {
            return [
                'coin_id'  => 0,
                'plist'    => [],
                'wlist'    => [],
                'networks' => [],
            ];
        }

        if (is_object($plist)) {
            $plist = $plist->toArray();
        }

        $wlist = $this->wallet_model
            ->where('product_id', $coin_id)
            ->where('uid', $this->memberInfo->id)
            ->field('id,ex_money,withdraw_erc_address,withdraw_trc_address,withdraw_omni_address,withdraw_bsc_address,withdraw_pol_address,withdraw_base_address')
            ->find();

        if (empty($wlist)) {
            $wlist = [
                'id'                    => 0,
                'ex_money'              => 0,
                'withdraw_erc_address'  => '',
                'withdraw_trc_address'  => '',
                'withdraw_omni_address' => '',
                'withdraw_bsc_address'  => '',
                'withdraw_pol_address'  => '',
                'withdraw_base_address' => '',
            ];
        } else {
            $wlist = is_object($wlist) ? $wlist->toArray() : $wlist;
        }

        $networks = [];

        if (floatval($plist['withdraw_erc_sxf']) > 0) {
            $networks[] = [
                'lay_id'  => '3',
                'type'    => 'erc',
                'title'   => lang('dealings.erc_title'),
                'sxf'     => $plist['withdraw_erc_sxf'],
                'address' => $wlist['withdraw_erc_address'],
            ];
        }
        if (floatval($plist['withdraw_trc_sxf']) > 0) {
            $networks[] = [
                'lay_id'  => '2',
                'type'    => 'trc',
                'title'   => lang('dealings.trc_title'),
                'sxf'     => $plist['withdraw_trc_sxf'],
                'address' => $wlist['withdraw_trc_address'],
            ];
        }
        if (floatval($plist['withdraw_bsc_sxf']) > 0) {
            $networks[] = [
                'lay_id'  => '5',
                'type'    => 'bsc',
                'title'   => 'BSC',
                'sxf'     => $plist['withdraw_bsc_sxf'],
                'address' => $wlist['withdraw_bsc_address'],
            ];
        }
        if (floatval($plist['withdraw_omni_sxf']) > 0) {
            $networks[] = [
                'lay_id'  => '1',
                'type'    => 'omni',
                'title'   => lang('dealings.omni_title'),
                'sxf'     => $plist['withdraw_omni_sxf'],
                'address' => $wlist['withdraw_omni_address'],
            ];
        }
        if (floatval($plist['withdraw_pol_sxf']) > 0) {
            $networks[] = [
                'lay_id'  => '6',
                'type'    => 'pol',
                'title'   => 'POL',
                'sxf'     => $plist['withdraw_pol_sxf'],
                'address' => $wlist['withdraw_pol_address'],
            ];
        }
        if (floatval($plist['withdraw_base_sxf']) > 0) {
            $networks[] = [
                'lay_id'  => '7',
                'type'    => 'base',
                'title'   => 'Base',
                'sxf'     => $plist['withdraw_base_sxf'],
                'address' => $wlist['withdraw_base_address'],
            ];
        }

        return [
            'coin_id'  => intval($coin_id),
            'plist'    => $plist,
            'wlist'    => $wlist,
            'networks' => $networks,
        ];
    }
}