<?php

/*
 * @Author: bluefox
 * @Motto: Running fox looking for dreams
 * @Date: 2021-01-07 15:59:02
 * @LastEditTime: 2021-10-11 17:36:39
 */
namespace app\push\controller;

use app\common\controller\PushController;
use think\facade\Db;
use GatewayWorker\Lib\Gateway;
use app\common\FoxCommon;
use app\common\FoxKline;
use app\common\service\HuobiRedis;
use app\common\service\ElasticService;
use app\common\service\KlineService;
class Doing extends PushController
{
    public static function elastic()
    {
        $obj = new ElasticService();
        return $obj;
    }
    public static function hbrds()
    {
        $setredis = \think\facade\Config::get('cache.stores.redis');
        $hbrds = new HuobiRedis($setredis['host'], $setredis['port'], $setredis['password']);
        return $hbrds;
    }
    /**
     * @Title: 根据用户位置推送数据
     * @param {*} $client_id
     * @param {*} $type
     */
    public static function find_product_list()
    {
        $product = \app\admin\model\ProductLists::where('status', 1)->where('base', 0)->order('sort', 'desc')->select();
        $msgs = [];
        if ($product) {
            $msgs['type'] = 'allticker';
            foreach ($product as $k => $v) {
                $zero_table = 'market_' . $v['code'] . '_kline_1min';
                $msgs['ticker'][$k] = ['market' => $v['code'], 'open' => (double) $v['open'], 'close' => (double) $v['close'], 'high' => (double) $v['high'], 'low' => (double) $v['low'], 'change' => (double) $v['change'], 'amount' => (double) $v['amount'], 'count' => (int) $v['count'], 'vol' => (double) $v['volume'], 'volume' => (double) $v['volume'], 'canvas' => KlineService::search_svg($zero_table, '1min', 30), 'usd' => FoxKline::get_me_price_usdt_to_usd($v['close'])];
            }
            Gateway::sendToAll(json_encode($msgs));
        }
    }
    /**
     * @Title: 给用户推送TICK
     * @param {*} $client_id
     * @param {*} $code
     * @param {*} $type
     * @param {*} $deal
     */
    public static function find_product_tick($client_id, $find, $code, $type, $uid, $deal = 1)
    {
        if ($client_id) {
            $msgs = [];
            $zero_table = 'market_' . $code . '_kline_1min';
            $kinsetinfos = KlineService::search($zero_table, null, null, $type, 1);
            if (isset($kinsetinfos[0])) {
                $kinsetinfo = $kinsetinfos[0];
                $msgs['market'] = $code;
                $product = \app\admin\model\ProductLists::where('code', $code)->field('close,change')->find();
                $msgs['tick'] = ['open' => (double) $kinsetinfo['close'], 'close' => (double) $product['close'], 'high' => (double) $kinsetinfo['high'], 'low' => (double) $kinsetinfo['low'], 'change' => (double) $product['change'], 'amount' => (double) $kinsetinfo['amount'], 'count' => (int) $kinsetinfo['count'], 'id' => (int) $kinsetinfo['time'], 'vol' => (double) $kinsetinfo['vol'], 'volume' => (double) $kinsetinfo['vol']];
                if ($uid && $find == 'leverdeal') {
                    $memberId = intVal($uid);
                    if ($memberId > 0) {
                        $productwhere[] = ['types', 'like', '%2%'];
                        $productwhere[] = ['status', '=', '1'];
                        $product = \app\admin\model\ProductLists::where($productwhere)->field('id,code,last_price')->order('sort', 'desc')->select();
                        $m_order = new \app\admin\model\OrderLeverdeal();
                        if ($product) {
                            foreach ($product as $k => $v) {
                                $money = \app\admin\model\MemberWallet::where('product_id', $v['id'])->where('uid', $memberId)->value('le_money');
                                $user_order = \app\admin\model\OrderLeverdeal::where('product_id', $v['id'])->where('uid', $memberId)->where('status', 1)->field('id')->select();
                                if ($user_order) {
                                    foreach ($user_order as $uk => $uv) {
                                        $order = $m_order->where('id', $uv['id'])->find();
                                        //加锁
                                        $rate = bc_mul(bc_mul($order['buy_price'], $order['account']), $order['play_rate']);
                                        $coin_rate = bc_div($rate, $order['buy_price']);
                                        //化为币
                                        $salf = bc_mul(bc_sub($v['last_price'], $order['buy_price']), $order['account']);
                                        $coin_salf = bc_div($salf, $v['last_price']);
                                        //化为币
                                        $deal_salf = bc_mul(bc_sub($coin_salf, $coin_rate), $order['account']);
                                        $long = bc_sub($v['last_price'], $order['buy_price']);
                                        if ($order['style'] == 1 && $long > 0) {
                                            //买涨实涨：盈
                                            if ($deal_salf < 0) {
                                                $deal_salf = 0 - $deal_salf;
                                            }
                                        } else {
                                            if ($order['style'] == 1 && $long < 0) {
                                                //买涨实跌：亏
                                                if ($deal_salf > 0) {
                                                    $deal_salf = 0 - $deal_salf;
                                                }
                                            } else {
                                                if ($order['style'] == 2 && $long > 0) {
                                                    //买跌实涨：亏
                                                    if ($deal_salf > 0) {
                                                        $deal_salf = 0 - $deal_salf;
                                                    }
                                                } else {
                                                    if ($order['style'] == 2 && $long < 0) {
                                                        //买跌实跌：盈
                                                        if ($deal_salf < 0) {
                                                            $deal_salf = 0 - $deal_salf;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $money += $deal_salf;
                                    }
                                }
                                $money = round_pad_zero($money, 8);
                                $msgs['usermoney'][$v['code']] = $money;
                            }
                        }
                    }
                }
                if (Gateway::isOnline($client_id)) {
                    Gateway::sendToClient($client_id, json_encode($msgs));
                }
            }
        }
    }
    /**
     * @Title: 推送TRADE
     * @param {*} $client_id
     * @param {*} $code
     */
    public static function find_product_trade($client_id, $find, $code)
    {
        $msg = [];
        $msgs = [];
        $stable = 'tradelogs_' . $code;
        $sinfo = self::hbrds()->read($stable);
        $dtable = 'depthlist_' . $code;
        $dinfo = self::hbrds()->read($dtable);
        if (!$dinfo || !$sinfo) {
            return;
        }
        $msg['bid'] = json_decode($dinfo['bid'] ?? '[]', true);
        $msg['ask'] = json_decode($dinfo['ask'] ?? '[]', true);
        $msgs['tradelog'] = json_decode($sinfo['data'] ?? '[]', true);
        $msgs['market'] = $code;
        $msgs['depthlist'] = $msg;
        if (Gateway::isOnline($client_id)) {
            Gateway::sendToClient($client_id, json_encode($msgs));
        }
    }
    public static function foxRand($pro = ['40' => 40, '60' => 60])
    {
        $ret = '';
        $sum = array_sum($pro);
        foreach ($pro as $k => $v) {
            $r = mt_rand(1, $sum);
            if ($r <= $v) {
                $ret = $k;
                break;
            } else {
                $sum = max(0, $sum - $v);
            }
        }
        return $ret;
    }
    /**
     * @Title: 生成空气币K线
     */
    public static function kong_kline()
    {
        $day = date("d", time());
        $dh = date("H", time());
        $di = date("i", time());
        $ds = date("s", time());
        $product = \app\admin\model\ProductLists::where('status', 1)->where('is_kong', 1)->field('id,code,kong_min,kong_max,last_price,volume,kong_zero,kong_type')->find();
        if ($product) {
            $_new_rand = self::foxRand();
            $where[] = ['code', '=', $product->code];
            if ($product->last_price == 0) {
                $open_price = $product->kong_min;
                $close_price = FoxCommon::generateRand($product->kong_min, $product->kong_max);
            } else {
                $o_new_rand = rand(0, 9);
                if ($product->kong_min < $product->kong_max) {
                    //价格范围为涨
                    if ($product->last_price < $product->kong_min) {
                        $open_price = $product->kong_min;
                        $close_price = $open_price + FoxCommon::kline_k_price($open_price);
                        Db::name('product_lists')->where($where)->update(['kong_type' => 1]);
                        //涨
                    } else {
                        if ($product->last_price > $product->kong_max) {
                            $open_price = $product->kong_max;
                            $close_price = $open_price - FoxCommon::kline_k_price($open_price);
                            Db::name('product_lists')->where($where)->update(['kong_type' => 2]);
                            //跌
                        } else {
                            $open_price = $product->last_price;
                            if ($o_new_rand % 2 == 0) {
                                $close_price = $open_price - FoxCommon::kline_k_price($open_price);
                            } else {
                                $close_price = $open_price + FoxCommon::kline_k_price($open_price);
                            }
                        }
                    }
                } else {
                    if ($product->kong_min > $product->kong_max) {
                        //价格范围为跌
                        if ($product->last_price > $product->kong_min) {
                            $open_price = $product->kong_min;
                            $close_price = $open_price - FoxCommon::kline_k_price($open_price);
                            Db::name('product_lists')->where($where)->update(['kong_type' => 2]);
                            //跌
                        } else {
                            if ($product->last_price < $product->kong_max) {
                                $open_price = $product->kong_max;
                                $close_price = $open_price + FoxCommon::kline_k_price($open_price);
                                Db::name('product_lists')->where($where)->update(['kong_type' => 1]);
                                //涨
                            } else {
                                $open_price = $product->last_price;
                                if ($o_new_rand % 2 == 0) {
                                    $close_price = $open_price - FoxCommon::kline_k_price($open_price);
                                } else {
                                    $close_price = $open_price + FoxCommon::kline_k_price($open_price);
                                }
                            }
                        }
                    } else {
                        //万一出现傻B弄成一样呢
                        $open_price = $product->kong_min;
                        $close_price = FoxCommon::generateRand($product->kong_min, $product->kong_max);
                    }
                }
            }
            $dtime = strtotime(date('Y-m-d H:i'));
            if ($ds == '00' || $ds == '01') {
                Db::name('product_lists')->where($where)->update(['vol_rand' => 0]);
                $vol_rand = 0;
            } else {
                $vol_rand = \app\admin\model\ProductLists::where('status', 1)->where('is_kong', 1)->value('vol_rand');
            }
            if ($open_price <= 0) {
                $open_price = FoxCommon::generateRand($product->kong_min, $product->kong_max);
            }
            if ($close_price <= 0) {
                $close_price = FoxCommon::generateRand($product->kong_min, $product->kong_max);
            }
            $es_table = 'market_' . $product->code . '_kline_1min';
            $dvolume = FoxCommon::generateRand(1000.0001, 99999.0009, 8);
            $damount = FoxCommon::generateRand(1000.0001, 99999.0009, 8);
            $dcount = rand(1000, 99999);
            if ($einfo = KlineService::search_day($es_table)) {
                $high = $einfo['high'];
                $low = $einfo['low'];
                $volume = $einfo['volume'] + $vol_rand;
                $amount = $einfo['amount'] + $vol_rand;
                $count = $einfo['count'] + $vol_rand;
            } else {
                if ($open_price > $close_price) {
                    $high = $open_price;
                    $low = $close_price;
                } else {
                    $high = $close_price;
                    $low = $open_price;
                }
                $volume = $dvolume;
                $amount = $damount;
                $count = $dcount;
            }
            //数组控制
            $open_price = round_pad_zero($open_price, $product['kong_zero']);
            $close_price = round_pad_zero($close_price, $product['kong_zero']);
            $high = round_pad_zero($high, $product['kong_zero']);
            $low = round_pad_zero($low, $product['kong_zero']);
            //结束
            $msg['type'] = "tradingvew";
            $msg['ch'] = str_replace('_', '.', $es_table);
            $msg['symbol'] = $product->code;
            //火币对
            $msg['period'] = '1min';
            //分期
            $msg['open'] = $open_price;
            $msg['close'] = $close_price;
            $msg['low'] = round_pad_zero($open_price - FoxCommon::kline_k_price($open_price), $product['kong_zero']);
            $msg['vol'] = $dvolume;
            $msg['high'] = round_pad_zero($open_price + FoxCommon::kline_k_price($open_price), $product['kong_zero']);
            $msg['count'] = $dcount;
            $msg['amount'] = $damount;
            $msg['time'] = $dtime;
            $msg['ranges'] = fox_time($dtime);
            KlineService::save($es_table, $msg);
            $zero_time = strtotime(date("Y-m-d"), time());
            $zero_open = $open_price;
            $zero_data = KlineService::search_one($es_table, $zero_time);
            if (isset($zero_data[0])) {
                $zero_open = $zero_data[0]['close'];
            } else {
                $zero_data_day = KlineService::search_one_day($es_table, $zero_time);
                if (isset($zero_data_day[0])) {
                    $zero_open = $zero_data_day[0]['close'];
                }
            }
            $ck = 1;
            $cc = $close_price * $ck;
            $co = $zero_open * $ck;
            $change = round(($cc - $co) / $co * 100, 4);
            $ladata = ['open' => $open_price, 'close' => $close_price, 'high' => $high, 'low' => $low, 'change' => $change, 'amount' => $amount, 'count' => $count, 'volume' => $volume, 'last_price' => $close_price, 'vol_rand' => $vol_rand + FoxCommon::generateRand(0.0001, 1.9999, 8)];
            Db::name('product_lists')->where($where)->update($ladata);
            //入库
            //depth
            $depth['type'] = "depthlist";
            $depth['market'] = $product->code;
            //火币对
            $depth['bid'] = [];
            //买入
            $depth['ask'] = [];
            //卖出
            $bids = 20;
            $df = FoxCommon::generateRand(0.0001, 3.0009, 4);
            $dfs = FoxCommon::generateRand(0.0001, 3.0009, 4);
            for ($i = 0; $i < $bids; $i++) {
                //出价  买入
                $depth['bid'][$i]['id'] = $i;
                $depth['bid'][$i]['price'] = $close_price;
                $depth['bid'][$i]['quantity'] = $df;
                $depth['bid'][$i]['total'] = $df * ($i + 1);
                $depth['ask'][$i]['id'] = $i;
                $depth['ask'][$i]['price'] = $close_price;
                $depth['ask'][$i]['quantity'] = $df;
                $depth['ask'][$i]['total'] = $dfs * ($i + 1);
            }
            $msgs['bid'] = json_encode($depth['bid']);
            $msgs['ask'] = json_encode($depth['ask']);
            $stable = $depth['type'] . '_' . $depth['market'];
            self::hbrds()->write($stable, $msgs);
            //trade
            $trade['market'] = $product->code;
            //货币对
            $trade['id'] = time() * 1000;
            $trade['price'] = (double) $close_price;
            $td = FoxCommon::generateRand(10.0001, 30.0009, 4);
            $trade['tradeId'] = time() * 100;
            $new_rand = rand(0, 9);
            if ($new_rand % 2 == 0) {
                $trade['trade_type'] = 2;
                $trade['num'] = $td + FoxCommon::generateRand(0.0001, 3.0009, 4);
            } else {
                $trade['trade_type'] = 1;
                $trade['num'] = $td - FoxCommon::generateRand(0.0001, 3.0009, 4);
            }
            $trade['time'] = (string) time();
            $msgt['type'] = "tradelog";
            $stable = 'tradelogs_' . $trade['market'];
            $msgt['data'] = json_encode($trade);
            self::hbrds()->write($stable, $msgt);
        }
    }
    public static function do_deal_order()
    {
        $m_order = new \app\admin\model\OrderDeal();
        $deal_orders = $m_order->field('id')->where('type', 1)->where('status', 1)->order('create_time', 'desc')->limit(20)->select();
        if ($deal_orders) {
            foreach ($deal_orders as $k => $v) {
                $m_order->startTrans();
                //开启事务
                try {
                    $m_product = new \app\admin\model\ProductLists();
                    $m_wallet = new \app\admin\model\MemberWallet();
                    $m_user = new \app\admin\model\MemberUser();
                    $m_log = new \app\admin\model\MemberWalletLog();
                    $order = $m_order->lock(true)->where('id', $v['id'])->field('id,uid,price,account_product,title,direction,product_id')->find();
                    //加锁
                    $is_test = $m_user->where('id', $order['uid'])->value('is_test');
                    $pro = $m_product->where('id', $order['product_id'])->field('id,close')->find();
                    $user_wallet = $m_wallet->where('product_id', $pro['id'])->where('uid', $order['uid'])->field('id,product_id,ex_money')->find();
                   /* if (floatcmp($order['price'], $pro['close'])) {
                        if ($order['direction'] == 1) {
                            //买入
                            $now_money = bc_add($user_wallet['ex_money'], $order['account_product']);
                            if ($m_wallet->where('id', $user_wallet['id'])->update(['ex_money' => $now_money])) {
                                $m_order->where('id', $order['id'])->update(['status' => 2, 'update_time' => time(), 'price_product' => $pro['close']]);
                                $lgdata['account'] = $order['account_product'];
                                $lgdata['wallet_id'] = $user_wallet['id'];
                                $lgdata['product_id'] = $user_wallet['product_id'];
                                $lgdata['uid'] = $order['uid'];
                                $lgdata['is_test'] = $is_test;
                                $lgdata['before'] = $user_wallet['ex_money'];
                                $lgdata['after'] = bc_sub($user_wallet['ex_money'], $order['account_product']);
                                $lgdata['account_sxf'] = 0;
                                $lgdata['all_account'] = bc_sub($lgdata['account'], $lgdata['account_sxf']);
                                $lgdata['type'] = 4;
                                $lgdata['title'] = $order['title'];
                                $lgdata['order_type'] = 11;
                                //买得
                                $lgdata['order_id'] = $order['id'];
                                $m_log->save($lgdata);
                            }
                        } else {
                            if ($order['direction'] == 2) {
                                //卖出
                                $productBase = $m_product->where('base', 1)->field('id,title')->find();
                                $base_wallet = $m_wallet->where('product_id', $productBase['id'])->where('uid', $order['uid'])->field('id,product_id,ex_money')->find();
                                $now_ex_money = bc_add($base_wallet['ex_money'], $order['account_product']);
                                if ($m_wallet->where('id', $base_wallet['id'])->update(['ex_money' => $now_ex_money])) {
                                    $m_order->where('id', $order['id'])->update(['status' => 2, 'update_time' => time(), 'price_product' => $pro['close']]);
                                    $lgdata['account'] = $order['account_product'];
                                    $lgdata['wallet_id'] = $base_wallet['id'];
                                    $lgdata['product_id'] = $base_wallet['product_id'];
                                    $lgdata['uid'] = $order['uid'];
                                    $lgdata['is_test'] = $is_test;
                                    $lgdata['before'] = $base_wallet['ex_money'];
                                    $lgdata['after'] = bc_add($base_wallet['ex_money'], $order['account_product']);
                                    $lgdata['account_sxf'] = 0;
                                    $lgdata['all_account'] = bc_sub($lgdata['account'], $lgdata['account_sxf']);
                                    $lgdata['type'] = 4;
                                    $lgdata['title'] = $order['title'];
                                    $lgdata['order_type'] = 22;
                                    //卖出得
                                    $lgdata['order_id'] = $order['id'];
                                    $m_log->save($lgdata);
                                }
                            }
                        }
                    }*/
                    
                    if ($order['direction'] == 1) {
                            if ($pro['close']>=$order['price']) {
                            //买入
                            $now_money = bc_add($user_wallet['ex_money'], $order['account_product']);
                            if ($m_wallet->where('id', $user_wallet['id'])->update(['ex_money' => $now_money])) {
                                $m_order->where('id', $order['id'])->update(['status' => 2, 'update_time' => time(), 'price_product' => $pro['close']]);
                                $lgdata['account'] = $order['account_product'];
                                $lgdata['wallet_id'] = $user_wallet['id'];
                                $lgdata['product_id'] = $user_wallet['product_id'];
                                $lgdata['uid'] = $order['uid'];
                                $lgdata['is_test'] = $is_test;
                                $lgdata['before'] = $user_wallet['ex_money'];
                                $lgdata['after'] = bc_sub($user_wallet['ex_money'], $order['account_product']);
                                $lgdata['account_sxf'] = 0;
                                $lgdata['all_account'] = bc_sub($lgdata['account'], $lgdata['account_sxf']);
                                $lgdata['type'] = 4;
                                $lgdata['title'] = $order['title'];
                                $lgdata['order_type'] = 11;
                                //买得
                                $lgdata['order_id'] = $order['id'];
                                $m_log->save($lgdata);
                            }
                        } else if($order['direction'] == 2){
                            if ($pro['close']<=$order['price']) {
                                //卖出
                                $productBase = $m_product->where('base', 1)->field('id,title')->find();
                                $base_wallet = $m_wallet->where('product_id', $productBase['id'])->where('uid', $order['uid'])->field('id,product_id,ex_money')->find();
                                $now_ex_money = bc_add($base_wallet['ex_money'], $order['account_product']);
                                if ($m_wallet->where('id', $base_wallet['id'])->update(['ex_money' => $now_ex_money])) {
                                    $m_order->where('id', $order['id'])->update(['status' => 2, 'update_time' => time(), 'price_product' => $pro['close']]);
                                    $lgdata['account'] = $order['account_product'];
                                    $lgdata['wallet_id'] = $base_wallet['id'];
                                    $lgdata['product_id'] = $base_wallet['product_id'];
                                    $lgdata['uid'] = $order['uid'];
                                    $lgdata['is_test'] = $is_test;
                                    $lgdata['before'] = $base_wallet['ex_money'];
                                    $lgdata['after'] = bc_add($base_wallet['ex_money'], $order['account_product']);
                                    $lgdata['account_sxf'] = 0;
                                    $lgdata['all_account'] = bc_sub($lgdata['account'], $lgdata['account_sxf']);
                                    $lgdata['type'] = 4;
                                    $lgdata['title'] = $order['title'];
                                    $lgdata['order_type'] = 22;
                                    //卖出得
                                    $lgdata['order_id'] = $order['id'];
                                    $m_log->save($lgdata);
                                }
                            }
                        }
                    }
                    
                    /*$m_order->where('id', $order['id'])->update(['status' => 2, 'price_product' => $order['price'], 'update_time' => time()]);*/
                    $m_order->commit();
                    //事务提交
                } catch (\Throwable $e) {
                    $m_order->rollback();
                }
            }
        }
    }

    /**
     * @Title: 期权订单结算
     */
    /**
     * @Title: 期权订单结算
     */
    public static function do_seconds_order()
    {
        $seconds_kong_num = sysconfig('trade', 'seconds_kong_num');
        $m_order = new \app\admin\model\OrderSeconds();
        $nowtime = time();
        $s_rand = rand(6, 9);

        $map[] = ['op_status', '=', 0];
        $map[] = ['orders_time', '<', $nowtime + $s_rand];
        $orderlist = $m_order->field('id')->where($map)->limit(20)->select();

        if ($orderlist) {
            foreach ($orderlist as $k => $v) {
                try {
                    $m_order->startTrans();
                    // 开启事务
                    $m_product = new \app\admin\model\ProductLists();
                    $m_wallet = new \app\admin\model\MemberWallet();
                    $m_user = new \app\admin\model\MemberUser();
                    $m_log = new \app\admin\model\MemberWalletLog();

                    $order = $m_order->lock(true)->where('id', $v['id'])->find();
                    // 加锁
                    if (!$order || $order['op_status'] != 0) {
                        $m_order->rollback();
                        continue;
                    }

                    $is_test = $m_user->where('id', $order['uid'])->value('is_test');
                    $productBase = $m_product->where('base', 1)->field('id,title')->find();
                    $pro = $m_product->where('id', $order['product_id'])->field('id,close,op_kong_min,op_kong_max,op_sx_fee,op_order_kong')->find();
                    $user_wallet = $m_wallet->where('product_id', $productBase['id'])->where('uid', $order['uid'])->field('id,product_id,op_money')->find();

                    if (!$productBase || !$pro || !$user_wallet) {
                        $m_order->rollback();
                        continue;
                    }

                    $op_k_num = bc_mul($pro['close'], bc_div(FoxCommon::kong_generateRand($pro['op_kong_min'], $pro['op_kong_max']), 100));
                    $now_num_price = bc_sub($pro['close'], $order['start_price']);

                    if ($now_num_price < 0) {
                        $num_aa = 0 - $now_num_price;
                    } else {
                        $num_aa = $now_num_price;
                    }

                    $num_bb = bc_mul($order['start_price'], $seconds_kong_num / 100);

                    // 利润金额
                    $profit_money = bc_mul($order['op_number'], bc_div($order['play_prop'], 100, 8), 8);
                    // 手续费只按本金计算（存正数）
                    $principal_sx_fee = bc_mul($order['op_number'], $pro['op_sx_fee'], 8);

                    if ($num_bb > 0 && $num_aa > $num_bb) {
                        $odata = [];
                        $odata['end_price'] = $pro['close'];
                        $odata['op_status'] = 1;
                        $odata['update_time'] = time();

                        if ($order['op_style'] == 1) {
                            // 买涨
                            if ($now_num_price > 0) {
                                $odata['is_win'] = 1;
                            } else {
                                $odata['is_win'] = 2;
                            }
                        } elseif ($order['op_style'] == 2) {
                            // 买跌
                            if ($now_num_price > 0) {
                                $odata['is_win'] = 2;
                            } else {
                                $odata['is_win'] = 1;
                            }
                        }

                        if ($odata['is_win'] == 1) {
                            // 赢了：手续费只按本金计算
                            $odata['true_fee'] = bc_add($order['op_number'], $profit_money, 8);
                            $odata['sx_fee'] = $principal_sx_fee; // 存正数
                            $odata['all_fee'] = bc_sub($odata['true_fee'], $odata['sx_fee'], 8);

                            $now_money = bc_add($user_wallet['op_money'], $odata['all_fee'], 8);

                            if ($m_order->where('id', $order['id'])->update($odata)) {
                                $m_wallet->where('id', $user_wallet['id'])->update(['op_money' => $now_money]);

                                $lgdata = [];
                                $lgdata['account'] = $order['op_number'];
                                $lgdata['wallet_id'] = $user_wallet['id'];
                                $lgdata['product_id'] = $user_wallet['product_id'];
                                $lgdata['uid'] = $order['uid'];
                                $lgdata['is_test'] = $is_test;
                                $lgdata['before'] = $user_wallet['op_money'];
                                $lgdata['after'] = $now_money;
                                $lgdata['account_sxf'] = $odata['sx_fee']; // 存正数
                                $lgdata['all_account'] = $odata['all_fee'];
                                $lgdata['type'] = 6;
                                $lgdata['title'] = $productBase['title'];
                                $lgdata['order_type'] = 2;
                                // 赢返
                                $lgdata['order_id'] = $order['id'];
                                $m_log->save($lgdata);
                            }
                        } else {
                            // 亏损：全亏逻辑不变，但金额减去手续费
                            $odata['true_fee'] = $order['op_number'];
                            $odata['sx_fee'] = $principal_sx_fee; // 存正数
                            $odata['all_fee'] = bc_sub($order['op_number'], $principal_sx_fee, 8);
                            $m_order->where('id', $order['id'])->update($odata);
                        }
                    } else {
                        if ($order['kong_type'] == 1) {
                            // 控赢
                            $odata = [];
                            if ($order['op_style'] == 1) {
                                // 买涨
                                $odata['end_price'] = bc_add($order['start_price'], $op_k_num, 8);
                            } elseif ($order['op_style'] == 2) {
                                // 买跌
                                $odata['end_price'] = bc_sub($order['start_price'], $op_k_num, 8);
                            }

                            $odata['update_time'] = time();
                            $odata['is_win'] = 1;
                            $odata['op_status'] = 1;
                            $odata['true_fee'] = bc_add($order['op_number'], $profit_money, 8);
                            $odata['sx_fee'] = $principal_sx_fee; // 存正数
                            $odata['all_fee'] = bc_sub($odata['true_fee'], $odata['sx_fee'], 8);

                            $now_money = bc_add($user_wallet['op_money'], $odata['all_fee'], 8);
                            if ($m_order->where('id', $order['id'])->update($odata)) {
                                $m_wallet->where('id', $user_wallet['id'])->update(['op_money' => $now_money]);

                                $lgdata = [];
                                $lgdata['account'] = $order['op_number'];
                                $lgdata['wallet_id'] = $user_wallet['id'];
                                $lgdata['product_id'] = $user_wallet['product_id'];
                                $lgdata['uid'] = $order['uid'];
                                $lgdata['is_test'] = $is_test;
                                $lgdata['before'] = $user_wallet['op_money'];
                                $lgdata['after'] = $now_money;
                                $lgdata['account_sxf'] = $odata['sx_fee']; // 存正数
                                $lgdata['all_account'] = $odata['all_fee'];
                                $lgdata['type'] = 6;
                                $lgdata['title'] = $productBase['title'];
                                $lgdata['order_type'] = 2;
                                // 赢返
                                $lgdata['order_id'] = $order['id'];
                                $m_log->save($lgdata);
                            }
                        } elseif ($order['kong_type'] == 2) {
                            // 控亏
                            $odata = [];
                            if ($order['op_style'] == 1) {
                                // 买涨
                                $odata['end_price'] = bc_sub($order['start_price'], $op_k_num, 8);
                            } elseif ($order['op_style'] == 2) {
                                // 买跌
                                $odata['end_price'] = bc_add($order['start_price'], $op_k_num, 8);
                            }

                            $odata['update_time'] = time();
                            $odata['is_win'] = 2;
                            $odata['op_status'] = 1;
                            $odata['true_fee'] = $order['op_number'];
                            $odata['sx_fee'] = $principal_sx_fee; // 存正数
                            $odata['all_fee'] = bc_sub($order['op_number'], $principal_sx_fee, 8);
                            $m_order->where('id', $order['id'])->update($odata);
                        } else {
                            // 不控
                            $u_op_order_kong = $m_user->where('id', $order['uid'])->value('op_order_kong');
                            $op_order_kong = 50;
                            if ($pro['op_order_kong'] > 0) {
                                $op_order_kong = $pro['op_order_kong'];
                            }
                            if ($u_op_order_kong > 0) {
                                $op_order_kong = $u_op_order_kong;
                            }

                            $new_rand = mt_rand(0, 100);
                            if ($new_rand <= $op_order_kong) {
                                // 赢
                                $odata = [];
                                if ($order['op_style'] == 1) {
                                    // 买涨
                                    $odata['end_price'] = bc_add($order['start_price'], $op_k_num, 8);
                                } elseif ($order['op_style'] == 2) {
                                    // 买跌
                                    $odata['end_price'] = bc_sub($order['start_price'], $op_k_num, 8);
                                }

                                $odata['update_time'] = time();
                                $odata['is_win'] = 1;
                                $odata['op_status'] = 1;
                                $odata['true_fee'] = bc_add($order['op_number'], $profit_money, 8);
                                $odata['sx_fee'] = $principal_sx_fee; // 存正数
                                $odata['all_fee'] = bc_sub($odata['true_fee'], $odata['sx_fee'], 8);

                                $now_money = bc_add($user_wallet['op_money'], $odata['all_fee'], 8);
                                if ($m_order->where('id', $order['id'])->update($odata)) {
                                    $m_wallet->where('id', $user_wallet['id'])->update(['op_money' => $now_money]);

                                    $lgdata = [];
                                    $lgdata['account'] = $order['op_number'];
                                    $lgdata['wallet_id'] = $user_wallet['id'];
                                    $lgdata['product_id'] = $user_wallet['product_id'];
                                    $lgdata['uid'] = $order['uid'];
                                    $lgdata['is_test'] = $is_test;
                                    $lgdata['before'] = $user_wallet['op_money'];
                                    $lgdata['after'] = $now_money;
                                    $lgdata['account_sxf'] = $odata['sx_fee']; // 存正数
                                    $lgdata['all_account'] = $odata['all_fee'];
                                    $lgdata['type'] = 6;
                                    $lgdata['title'] = $productBase['title'];
                                    $lgdata['order_type'] = 2;
                                    // 赢返
                                    $lgdata['order_id'] = $order['id'];
                                    $m_log->save($lgdata);
                                }
                            } else {
                                // 亏
                                $odata = [];
                                if ($order['op_style'] == 1) {
                                    // 买涨
                                    $odata['end_price'] = bc_sub($order['start_price'], $op_k_num, 8);
                                } elseif ($order['op_style'] == 2) {
                                    // 买跌
                                    $odata['end_price'] = bc_add($order['start_price'], $op_k_num, 8);
                                }

                                $odata['update_time'] = time();
                                $odata['is_win'] = 2;
                                $odata['op_status'] = 1;
                                $odata['true_fee'] = $order['op_number'];
                                $odata['sx_fee'] = $principal_sx_fee; // 存正数
                                $odata['all_fee'] = bc_sub($order['op_number'], $principal_sx_fee, 8);
                                $m_order->where('id', $order['id'])->update($odata);
                            }
                        }
                    }

                    $m_order->commit();
                    // 事务提交
                } catch (\Throwable $e) {
                    $m_order->rollback();
                }
            }
        }
    }
    /**
     * @Title: 理财订单结算
     */
    public static function do_good_order()
    {
        $today = strtotime(date("Y-m-d H:i:s"));
        $m_order = new \app\admin\model\OrderGood();
        $orderlist = $m_order->field('id')->where('lock_time', '<', $today)->where('status', 1)->limit(50)->select();
        $productBase = \app\admin\model\ProductLists::where('base', 1)->field('id,title')->find();
        if ($orderlist) {
            foreach ($orderlist as $k => $v) {
                $m_order->startTrans();
                //开启事务
                try {
                    $m_wallet = new \app\admin\model\MemberWallet();
                    $m_user = new \app\admin\model\MemberUser();
                    $m_log = new \app\admin\model\MemberWalletLog();
                    $m_good = new \app\admin\model\GoodLists();
                    $order = $m_order->lock(true)->where('id', $v['id'])->find();
                    //加锁
                    $user_base_wallet = $m_wallet->where('product_id', $productBase['id'])->where('uid', $order['uid'])->field('id,up_money')->find();
                    $is_test = $m_user->where('id', $order['uid'])->value('is_test');
                    $account = $order['buy_account'] * $order['rate'];
                    //收益
                    $rate_account = $order['rate_account'] + $account;
                    $lock = $order['lock'] - 1;
                    $t = $order['lock_time'] + 60 * 60 * 24;
                    $good_tit = $m_good->where('id', $order['good_id'])->value('title');
                    if ($lock >= 0) {
                        $update = $m_order->update(['lock' => $lock, 'rate_account' => $rate_account, 'lock_time' => $t], ['id' => $order['id']]);
                        if ($update) {
                            $now_up_money = bc_add($user_base_wallet['up_money'], $rate_account);
                            $m_wallet->where('id', $user_base_wallet['id'])->update(['up_money' => $now_up_money]);
                            $logdata['account'] = $account;
                            $logdata['wallet_id'] = $user_base_wallet['id'];
                            $logdata['product_id'] = $productBase['id'];
                            $logdata['uid'] = $order['uid'];
                            $logdata['is_test'] = $is_test;
                            $logdata['before'] = $user_base_wallet['up_money'];
                            $logdata['after'] = $now_up_money;
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = bc_sub($logdata['account'], $logdata['account_sxf']);
                            $logdata['type'] = 7;
                            //购买理财
                            $logdata['title'] = $productBase['title'];
                            $logdata['remark'] = $good_tit;
                            $logdata['order_type'] = 2;
                            //收益返息
                            $logdata['order_id'] = $order['id'];
                            $inlog = $m_log->save($logdata);
                        }
                    } else {
                        $update = $m_order->update(['lock' => $lock, 'status' => 2, 'lock_time' => time()], ['id' => $order['id']]);
                        if ($update) {
                            $now_up_money = bc_add($user_base_wallet['up_money'], $order['buy_account']);
                            $m_wallet->where('id', $user_base_wallet['id'])->update(['up_money' => $now_up_money]);
                            $logdata['account'] = $order['buy_account'];
                            $logdata['wallet_id'] = $user_base_wallet['id'];
                            $logdata['product_id'] = $productBase['id'];
                            $logdata['uid'] = $order['uid'];
                            $logdata['is_test'] = $is_test;
                            $logdata['before'] = $user_base_wallet['up_money'];
                            $logdata['after'] = $now_up_money;
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = bc_sub($logdata['account'], $logdata['account_sxf']);
                            $logdata['type'] = 7;
                            //购买理财
                            $logdata['title'] = $productBase['title'];
                            $logdata['remark'] = $good_tit;
                            $logdata['order_type'] = 3;
                            //理财返本
                            $logdata['order_id'] = $order['id'];
                            $inlog = $m_log->save($logdata);
                        }
                    }
                    $m_order->commit();
                    //事务提交
                } catch (\Throwable $e) {
                    $m_order->rollback();
                }
            }
        }
    }
    /**
     * @Title: 处理挖矿
     */
    public static function do_winer_order()
    {
        $today = strtotime(date("Y-m-d"));
        $m_order = new \app\admin\model\OrderWiner();
        $orderlist = $m_order->field('id')->where('lock_time', '<', $today)->where('status', 1)->limit(50)->select();
        $productBase = \app\admin\model\ProductLists::where('base', 1)->field('id,title')->find();
        if ($orderlist) {
            foreach ($orderlist as $k => $v) {
                $m_order->startTrans();
                //开启事务
                try {
                    $m_wallet = new \app\admin\model\MemberWallet();
                    $m_user = new \app\admin\model\MemberUser();
                    $m_log = new \app\admin\model\MemberWalletLog();
                    $m_pro = new \app\admin\model\ProductLists();
                    $order = $m_order->lock(true)->where('id', $v['id'])->find();
                    //加锁
                    $is_test = $m_user->where('id', $order['uid'])->value('is_test');
                    $rate = FoxCommon::generateRand($order['min_rate'], $order['max_rate']);
                    $pro = $m_pro->where('id', $order['product_id'])->field('id,close,title')->find();
                    $user_pro_wallet = $m_wallet->where('product_id', $order['product_id'])->where('uid', $order['uid'])->field('id,ex_money')->find();
                    //价格换算
                    $pprice = str_replace(',', '', FoxKline::get_me_price_usdt_to_usd($pro['close'], 8));
                    $account = bc_mul(bc_div($order['buy_account'], $pprice), $rate);
                    //币的收益
                    $rate_account = $order['rate_account'] + $account;
                    $lock = $order['lock'] - 1;
                    if ($lock >= 0) {
                        $update = $m_order->update(['lock' => $lock, 'rate_account' => $rate_account, 'lock_time' => time()], ['id' => $order['id']]);
                        if ($update) {
                            $now_ex_money = bc_add($user_pro_wallet['ex_money'], $rate_account);
                            $m_wallet->where('id', $user_pro_wallet['id'])->update(['ex_money' => $now_ex_money]);
                            $logdata['account'] = $account;
                            $logdata['wallet_id'] = $user_pro_wallet['id'];
                            $logdata['product_id'] = $productBase['id'];
                            $logdata['uid'] = $order['uid'];
                            $logdata['is_test'] = $is_test;
                            $logdata['before'] = $user_pro_wallet['ex_money'];
                            $logdata['after'] = $now_ex_money;
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = bc_sub($logdata['account'], $logdata['account_sxf']);
                            $logdata['type'] = 9;
                            //矿机
                            $logdata['title'] = $productBase['title'];
                            $logdata['remark'] = $pro['title'];
                            $logdata['order_type'] = 2;
                            //返息
                            $logdata['status'] = 33;
                            //挖矿回报
                            $logdata['order_id'] = $order['id'];
                            $inlog = $m_log->save($logdata);
                        }
                    } else {
                        $update = $m_order->update(['lock' => $lock, 'status' => 2, 'lock_time' => time()], ['id' => $order['id']]);
                        if ($update) {
                            $productBase = \app\admin\model\ProductLists::where('base', 1)->field('id,title')->find();
                            $user_base_wallet = $m_wallet->where('product_id', $productBase['id'])->where('uid', $order['uid'])->field('id,ex_money')->find();
                            $now_up_money = bc_add($user_base_wallet['ex_money'], $order['buy_account']);
                            $m_wallet->where('id', $user_base_wallet['id'])->update(['ex_money' => $now_up_money]);
                            $logdata['account'] = $order['buy_account'];
                            $logdata['wallet_id'] = $user_base_wallet['id'];
                            $logdata['product_id'] = $productBase['id'];
                            $logdata['uid'] = $order['uid'];
                            $logdata['is_test'] = $is_test;
                            $logdata['before'] = $user_base_wallet['ex_money'];
                            $logdata['after'] = $now_up_money;
                            $logdata['account_sxf'] = 0;
                            $logdata['all_account'] = bc_sub($logdata['account'], $logdata['account_sxf']);
                            $logdata['type'] = 9;
                            //挖矿
                            $logdata['title'] = $productBase['title'];
                            $logdata['remark'] = $pro['title'];
                            $logdata['order_type'] = 3;
                            //释放
                            $logdata['status'] = 32;
                            //挖矿释放
                            $logdata['order_id'] = $order['id'];
                            $inlog = $m_log->save($logdata);
                        }
                    }
                    $m_order->commit();
                    //事务提交
                } catch (\Throwable $e) {
                    $m_order->rollback();
                }
            }
        }
    }
    public static function do_leverdeal_order()
    {
        $orderlist = \app\admin\model\OrderLeverdeal::where('status', 1)->group('uid')->field('uid')->limit(50)->select();
        if ($orderlist) {
            $m_order = new \app\admin\model\OrderLeverdeal();
            $m_user = new \app\admin\model\MemberUser();
            $m_log = new \app\admin\model\MemberWalletLog();

            // 统一使用 USDT 基础钱包
            $usdt_pid = \app\admin\model\ProductLists::where('base',1)->value('id');

            foreach ($orderlist as $k => $v) {
                $pro_order = \app\admin\model\OrderLeverdeal::where('uid', $v['uid'])
                    ->group('product_id')
                    ->where('status', 1)
                    ->field('product_id,uid')
                    ->select();

                foreach ($pro_order as $pk => $pv) {
                    // 必须按 uid + product_id + status 查询
                    $user_order = \app\admin\model\OrderLeverdeal::where('product_id', $pv['product_id'])
                        ->where('uid', $pv['uid'])
                        ->where('status', 1)
                        ->field('id')
                        ->select();

                    $win = 0;
                    $close_price = \app\admin\model\ProductLists::where('id', $pv['product_id'])->value('last_price');

                    if (!$close_price || $close_price <= 0) {
                        continue;
                    }

                    foreach ($user_order as $uk => $uv) {
                        $order = $m_order->where('id', $uv['id'])->where('status',1)->find();
                        if (!$order) {
                            continue;
                        }

                        // ===== 原盈亏公式保持不动 =====
                        $rate = bc_mul(bc_mul($order['buy_price'], $order['account']), $order['play_rate']);
                        $coin_rate = bc_div($rate, $order['buy_price']);

                        $salf = bc_mul(bc_sub($close_price, $order['buy_price']), $order['account']);
                        $coin_salf = bc_div($salf, $close_price);

                        $deal_salf = bc_mul(bc_sub($coin_salf, $coin_rate), $order['account']);
                        $long = bc_sub($close_price, $order['buy_price']);

                        if ($order['style'] == 1 && $long > 0) {
                            // 买涨实涨：盈
                            if ($deal_salf < 0) {
                                $deal_salf = 0 - $deal_salf;
                            }
                            $m_order->where('id', $order['id'])->update([
                                'is_win' => 1,
                                'win_account' => $deal_salf,
                                'now_price' => $close_price
                            ]);
                        } else {
                            if ($order['style'] == 1 && $long < 0) {
                                // 买涨实跌：亏
                                if ($deal_salf > 0) {
                                    $deal_salf = 0 - $deal_salf;
                                }
                                $m_order->where('id', $order['id'])->update([
                                    'is_win' => 2,
                                    'win_account' => $deal_salf,
                                    'now_price' => $close_price
                                ]);
                            } else {
                                if ($order['style'] == 2 && $long > 0) {
                                    // 买跌实涨：亏
                                    if ($deal_salf > 0) {
                                        $deal_salf = 0 - $deal_salf;
                                    }
                                    $m_order->where('id', $order['id'])->update([
                                        'is_win' => 2,
                                        'win_account' => $deal_salf,
                                        'now_price' => $close_price
                                    ]);
                                } else {
                                    if ($order['style'] == 2 && $long < 0) {
                                        // 买跌实跌：盈
                                        if ($deal_salf < 0) {
                                            $deal_salf = 0 - $deal_salf;
                                        }
                                        $m_order->where('id', $order['id'])->update([
                                            'is_win' => 1,
                                            'win_account' => $deal_salf,
                                            'now_price' => $close_price
                                        ]);
                                    } else {
                                        // 价格相等时也刷新 now_price
                                        $m_order->where('id', $order['id'])->update([
                                            'now_price' => $close_price,
                                            'win_account' => $deal_salf
                                        ]);
                                    }
                                }
                            }
                        }

                        // 重新查一次最新订单
                        $freshOrder = $m_order->where('id', $order['id'])->find();

                        // ===== 止盈 / 止损触发 =====
                        $closeType = 0;
                        if (self::checkLeverStopProfit($freshOrder, $close_price)) {
                            $closeType = 2; // 止盈
                        } elseif (self::checkLeverStopLoss($freshOrder, $close_price)) {
                            $closeType = 3; // 止损
                        }

                        // 触发自动平仓
                        if ($closeType > 0) {
                            $user_wallet = \app\admin\model\MemberWallet::where('product_id',$usdt_pid)
                                ->where('uid',$freshOrder['uid'])
                                ->field('id,le_money')
                                ->find();

                            if ($user_wallet) {
                                $is_test = $m_user->where('id', $freshOrder['uid'])->value('is_test');
                                $now_le_money = bc_add($user_wallet['le_money'], $freshOrder['win_account']);

                                $m_order->where('id', $freshOrder['id'])->where('status',1)->update([
                                    'close_price' => $close_price,
                                    'status' => 2,
                                    'is_lock' => 2,
                                    'close_type' => $closeType
                                ]);

                                \app\admin\model\MemberWallet::where('id', $user_wallet['id'])->update([
                                    'le_money' => $now_le_money
                                ]);

                                $logdata = [];
                                $logdata['account'] = $freshOrder['win_account'];
                                $logdata['wallet_id'] = $user_wallet['id'];
                                $logdata['product_id'] = $usdt_pid;
                                $logdata['uid'] = $freshOrder['uid'];
                                $logdata['is_test'] = $is_test;
                                $logdata['before'] = $user_wallet['le_money'];
                                $logdata['after'] = $now_le_money;
                                $logdata['account_sxf'] = 0;
                                $logdata['all_account'] = $freshOrder['win_account'];
                                $logdata['type'] = 5;
                                $logdata['title'] = $freshOrder['title'];
                                $logdata['order_type'] = ($closeType == 2 ? 13 : 14); // 13止盈 14止损
                                $logdata['order_id'] = $freshOrder['id'];
                                $m_log->save($logdata);
                            }

                            // 已自动平仓，不再进入后面的强平累计
                            continue;
                        }

                        $win += $deal_salf;
                    }

                    // 剩余未平仓单，继续走强平逻辑
                    $user_wallet = \app\admin\model\MemberWallet::where('product_id', $usdt_pid)
                        ->where('uid', $pv['uid'])
                        ->field('le_money,id')
                        ->find();

                    if ($user_wallet && bc_add($win, $user_wallet['le_money']) <= 0) {
                        $logdata = [];

                        // 这里只查当前仍然未平的单
                        $remain_order = \app\admin\model\OrderLeverdeal::where('product_id', $pv['product_id'])
                            ->where('uid', $pv['uid'])
                            ->where('status', 1)
                            ->field('id')
                            ->select();

                        foreach ($remain_order as $ruk => $ruv) {
                            $info = \app\admin\model\OrderLeverdeal::where('id', $ruv['id'])->find();
                            if (!$info) {
                                continue;
                            }

                            $is_test = $m_user->where('id', $info['uid'])->value('is_test');
                            $ouser_wallet = \app\admin\model\MemberWallet::where('product_id', $usdt_pid)
                                ->where('uid', $info['uid'])
                                ->field('id,le_money')
                                ->find();

                            $now_le_money = bc_add($ouser_wallet['le_money'], $info['win_account']);

                            $m_order->where('id', $info['id'])->where('status',1)->update([
                                'close_price' => $close_price,
                                'status' => 2,
                                'is_lock' => 2,
                                'close_type' => 4
                            ]);

                            $logdata[$ruk]['account'] = $info['win_account'];
                            $logdata[$ruk]['wallet_id'] = $ouser_wallet['id'];
                            $logdata[$ruk]['product_id'] = $usdt_pid;
                            $logdata[$ruk]['uid'] = $info['uid'];
                            $logdata[$ruk]['is_test'] = $is_test;
                            $logdata[$ruk]['before'] = $ouser_wallet['le_money'];
                            $logdata[$ruk]['after'] = $now_le_money;
                            $logdata[$ruk]['account_sxf'] = 0;
                            $logdata[$ruk]['all_account'] = $info['win_account'];
                            $logdata[$ruk]['type'] = 5;
                            $logdata[$ruk]['title'] = $info['title'];
                            $logdata[$ruk]['order_type'] = 15; // 强平
                            $logdata[$ruk]['order_id'] = $info['id'];
                        }

                        if (!empty($logdata)) {
                            $m_log->saveAll($logdata);
                        }

                        \app\admin\model\MemberWallet::where('id', $user_wallet['id'])->update(['le_money' => 0]);
                    }
                }
            }
        }
    }

    private static function checkLeverStopProfit($order, $closePrice)
    {
        if (!$order || empty($order['stop_profit_price']) || $order['stop_profit_price'] <= 0) {
            return false;
        }

        // 买涨：现价 >= 止盈价
        if ((int)$order['style'] === 1) {
            return bccomp($closePrice, $order['stop_profit_price'], 8) >= 0;
        }

        // 买跌：现价 <= 止盈价
        if ((int)$order['style'] === 2) {
            return bccomp($closePrice, $order['stop_profit_price'], 8) <= 0;
        }

        return false;
    }

    private static function checkLeverStopLoss($order, $closePrice)
    {
        if (!$order || empty($order['stop_loss_price']) || $order['stop_loss_price'] <= 0) {
            return false;
        }

        // 买涨：现价 <= 止损价
        if ((int)$order['style'] === 1) {
            return bccomp($closePrice, $order['stop_loss_price'], 8) <= 0;
        }

        // 买跌：现价 >= 止损价
        if ((int)$order['style'] === 2) {
            return bccomp($closePrice, $order['stop_loss_price'], 8) >= 0;
        }

        return false;
    }

    /**
     * @Title: 贷款订单逾期处理
     */
    public static function do_loan_order()
    {
        $now = time();

        $orderList = \app\admin\model\LoanOrder::where('status', \app\common\LoanStatus::PASS)
            ->where('due_time', '>', 0)
            ->where('due_time', '<', $now)
            ->limit(50)
            ->select();

        if ($orderList) {
            foreach ($orderList as $k => $v) {
                try {
                    \app\admin\model\LoanOrder::where('id', $v['id'])
                        ->where('status', \app\common\LoanStatus::PASS)
                        ->update([
                            'status' => \app\common\LoanStatus::OVERDUE,
                            'overdue_time' => $now,
                            'update_time' => $now,
                        ]);
                } catch (\Throwable $e) {
                }
            }
        }
    }
}