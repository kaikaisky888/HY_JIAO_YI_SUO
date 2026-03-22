<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-01 16:41:46
 * @LastEditTime: 2021-08-20 14:19:13
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use app\common\FoxKline;
use think\App;
use think\facade\Db;
use think\facade\Env;

class Index extends MobileController
{
    public function index()
    {
        $all_sum_usd = 0;
        $all_sum_cny = 0;
        $rate_cn = 0;
        if (session('member')) {
            $sum_ex = 0;
            $sum_le = 0;
            $sum_op = 0;
            $sum_up = 0;
            $sum_cm = 0;
            $all_ex = Db::name('member_wallet')
                ->alias('a')
                ->where('a.uid', session('member.id'))
                ->where('a.ex_money', '>', 0)
                ->join('product_lists p ', 'p.id= a.product_id')
                ->field('a.ex_money,p.close,p.base')
                ->select();
            foreach ($all_ex as $v) {
                if ($v['base'] == 1) {
                    $sum_ex = $sum_ex + $v['ex_money'];
                } else {
                    $sum_ex = $sum_ex + FoxKline::get_me_price_usdt_to_usd_close($v['ex_money'], $v['close'], 8);
                }
            }
            $all_le = Db::name('member_wallet')
                ->alias('a')
                ->where('a.uid', session('member.id'))
                ->where('a.le_money', '>', 0)
                ->join('product_lists p ', 'p.id= a.product_id')
                ->field('a.le_money,p.close,p.base')
                ->select();
            foreach ($all_le as $v) {
                if ($v['base'] == 1) {
                    $sum_le = $sum_le + $v['le_money'];
                } else {
                    $sum_le = $sum_le + FoxKline::get_me_price_usdt_to_usd_close($v['le_money'], $v['close'], 8);
                }
            }
            $all_op = Db::name('member_wallet')
                ->alias('a')
                ->where('a.uid', session('member.id'))
                ->where('a.op_money', '>', 0)
                ->join('product_lists p ', 'p.id= a.product_id')
                ->field('a.op_money,p.close,p.base')
                ->select();
            foreach ($all_op as $v) {
                if ($v['base'] == 1) {
                    $sum_op = $sum_op + $v['op_money'];
                } else {
                    $sum_op = $sum_op + FoxKline::get_me_price_usdt_to_usd_close($v['op_money'], $v['close'], 8);
                }
            }
            $all_up = Db::name('member_wallet')
                ->alias('a')
                ->where('a.uid', session('member.id'))
                ->where('a.up_money', '>', 0)
                ->join('product_lists p ', 'p.id= a.product_id')
                ->field('a.up_money,p.close,p.base')
                ->select();
            foreach ($all_up as $v) {
                if ($v['base'] == 1) {
                    $sum_up = $sum_up + $v['up_money'];
                } else {
                    $sum_up = $sum_up + FoxKline::get_me_price_usdt_to_usd_close($v['up_money'], $v['close'], 8);
                }
            }
            $all_cm = Db::name('member_wallet')
                ->alias('a')
                ->where('a.uid', session('member.id'))
                ->where('a.cm_money', '>', 0)
                ->join('product_lists p ', 'p.id= a.product_id')
                ->field('a.cm_money,p.close,p.base')
                ->select();
            foreach ($all_cm as $v) {
                if ($v['base'] == 1) {
                    $sum_cm = $sum_cm + $v['cm_money'];
                } else {
                    $sum_cm = $sum_cm + FoxKline::get_me_price_usdt_to_usd_close($v['cm_money'], $v['close'], 8);
                }
            }
            $all_sum_usd = $sum_ex + $sum_le + $sum_op + $sum_up + $sum_cm;
            $rate = FoxKline::get_mifeng_exchange_rate();
            if ($rate && isset($rate['cn'])) {
                $rate_cn = $rate['cn'];
                $all_sum_cny = $all_sum_usd * $rate['cn'];
            }
        }
        $this->assign('all_sum_usd', round($all_sum_usd, 2));
        $this->assign('all_sum_cny', round($all_sum_cny, 2));
        $this->assign('rate_cn', $rate_cn);
        $product = \app\admin\model\ProductLists::where('status',1)->where('base',0)->where('ishome',1)->order('sort','desc')->select();
        $top_product = [];
        $top_seen = [];
        foreach ($product as $p) {
            $code = $p['code'] ?? '';
            if ($code === '' || isset($top_seen[$code])) {
                continue;
            }
            $top_product[] = $p;
            $top_seen[$code] = true;
            if (count($top_product) >= 3) {
                break;
            }
        }
        $this->assign('product',$product);
        $this->assign('top_product',$top_product);
        $down_ipa_url = sysconfig('base','down_ipa_url');
        $down_ipa = phpqrcode($down_ipa_url,'down_ipa');
        $down_apk_url = sysconfig('base','down_apk_url');
        $down_apk = phpqrcode($down_apk_url,'down_apk');
        $this->assign(['down_ipa'=>$down_ipa,'down_apk'=>$down_apk]);
        $banners = null;
        $bannersl = \app\admin\model\CpmBanner::where('status',1)->where('type',1)->where('name','home')->where('lang',$this->lang)->field('logo')->limit(5)->select();
        if(count($bannersl)){
            $banners = $bannersl;
        }
        $this->assign(['banners'=>$banners]);
        return $this->fetch();
    }

    public function loginout()
    {
        session('member', null);
        $this->redirect(url('index/index'));
    }
}

