<?php

/*
 * @Author: bluefox
 * @Motto: Running fox looking for dreams
 * @Date: 2020-12-30 12:52:34
 * @LastEditTime: 2021-10-10 11:36:24
 */
namespace app\common;

use think\facade\Db;
use think\facade\Cookie;
use think\facade\Cache;
class FoxKline
{
    private $url = 'https://api.hadax.com';
    private $mifeng = 'ZOGKVDLZSZVDOFJZMG29P4AHZIUEPRHNKJU3DT68';
    private $mifengurl = 'https://data.mifengcha.com/api';
    public static function huobi()
    {
        $obj = new self();
        return $obj->url;
    }
    public static function mifeng()
    {
        $obj = new self();
        return $obj->mifeng;
    }
    public static function mifengurl()
    {
        $obj = new self();
        return $obj->mifengurl;
    }
    public static function curlfun($url, $params = array(), $method = 'GET')
    {
        $header = array();
        $opts = array(CURLOPT_TIMEOUT => 10, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_HTTPHEADER => $header);
        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                $opts[CURLOPT_URL] = substr($opts[CURLOPT_URL], 0, -1);
                break;
            case 'POST':
                //判断是否传输文件
                $params = http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            $data = null;
        }
        return $data;
    }
    /**
     * @Title: 蜜蜂查获取汇率
     */
    public static function get_mifeng_exchange_rate()
    {
        if (Cache::get('mifeng_rate')) {
            return Cache::get('mifeng_rate');
        } else {
            $api_key = self::mifeng();
            $api_url = self::mifengurl();
            $url = $api_url . "/v3/exchange_rate?api_key=" . $api_key;
            $getdata = self::curlfun($url);
            $res = json_decode($getdata, 1);
            if ($res && is_array($res)) {
                $l = ['USD', 'USDT', 'CNY', 'HKD', 'KRW', 'JPY'];
                $ex = [];
                foreach ($res as $k => $v) {
                    // 确保 $v 是数组且包含 'c' 和 'r' 键
                    if (is_array($v) && isset($v['c']) && isset($v['r']) && in_array($v['c'], $l)) {
                        // $ex[][strtolower('usdt'.$v['c'])] = 1/$v['r'];
                        $ex[$v['c']] = $v['r'];
                    }
                }
                // 确保必需的汇率数据存在
                if (isset($ex['USD']) && isset($ex['USDT']) && $ex['USDT'] != 0) {
                    $n = $ex['USD'] / $ex['USDT'];
                    $o = [
                        'en' => $ex['USD'] * $n, 
                        'cn' => isset($ex['CNY']) ? $ex['CNY'] * $n : 7.2 * $n, 
                        'hk' => isset($ex['HKD']) ? $ex['HKD'] * $n : 7.8 * $n, 
                        'ja' => isset($ex['JPY']) ? $ex['JPY'] * $n : 150 * $n, 
                        'ko' => isset($ex['KRW']) ? $ex['KRW'] * $n : 1300 * $n
                    ];
                    if ($o) {
                        Cache::set('mifeng_rate', $o, 1800);
                    }
                    return Cache::get('mifeng_rate');
                }
                // API数据不完整，返回默认汇率
                return ['en' => 1, 'cn' => 7.2, 'hk' => 7.8, 'ja' => 150, 'ko' => 1300];
            }
        }
    }
    /**
     * @Title: 蜜蜂查获取汇率USD/USDT
     * 1USD = X USDT
     */
    public static function get_mifeng_exchange_rate_usdt()
    {
        if (Cache::get('mifeng_usdt')) {
            return Cache::get('mifeng_usdt');
        } else {
            $api_key = self::mifeng();
            $api_url = self::mifengurl();
            $url = $api_url . "/v3/exchange_rate?api_key=" . $api_key;
            $getdata = self::curlfun($url);
            $res = json_decode($getdata, 1);
            if ($res && is_array($res)) {
                $l = ['USD', 'USDT'];
                $ex = [];
                foreach ($res as $k => $v) {
                    if (is_array($v) && isset($v['c']) && isset($v['r']) && in_array($v['c'], $l)) {
                        $ex[$v['c']] = $v['r'];
                    }
                }
                $n = isset($ex['USDT']) ? $ex['USDT'] : 1;
                if ($n) {
                    Cache::set('mifeng_usdt', $n, 180);
                }
                return Cache::get('mifeng_usdt') ?: 1;
            }
            return 1; // 默认返回1
        }
    }
    /**
     * @Title: 获取蜜蜂币种价格U/USDT
     * 1 CION = X USDT = USD * USDT
     */
    public static function get_mifeng_price($symbol = 'ETC')
    {
        if (Cache::get('mifeng_price_' . $symbol)) {
            return Cache::get('mifeng_price_' . $symbol);
        } else {
            $api_key = self::mifeng();
            $api_url = self::mifengurl();
            $url = $api_url . "/v3/price?symbol=" . $symbol . "&api_key=" . $api_key;
            $getdata = self::curlfun($url);
            $res = json_decode($getdata, 1);
            if (!empty($res) && count($res) != count($res, 1)) {
                $re = $res[0];
                $ex = [];
                $usdt = self::get_mifeng_exchange_rate_usdt();
                $ex['usd'] = $re['u'];
                $ex['usdt'] = round_pad_zero($re['u'] * $usdt, 8);
                if ($ex) {
                    Cache::set('mifeng_price_' . $symbol, $ex, 180);
                }
                return Cache::get('mifeng_price_' . $symbol);
            }
        }
    }
    /**
     * @Title: 币转USDT，过渡为USD
     * @param {*} $num
     * @param {*} $symbol
     */
    public static function get_mifeng_price_to_usdt($num = 1, $symbol = 'ETC')
    {
        $info = self::get_mifeng_price($symbol);
        if ($info) {
            $usdt = round_pad_zero($num * $info['usdt'], 8);
            return $usdt;
        } else {
            return 0;
        }
    }
    /**
     * @Title: USDT转币，过渡为USD
     * @param {*} $num
     * @param {*} $symbol
     */
    public static function get_mifeng_price_usdt_to($num = 1, $symbol = 'ETC')
    {
        $info = self::get_mifeng_price($symbol);
        if ($info) {
            $usdtc = bcdiv(1, $info['usdt'], 20);
            $usdt = number_format($num * $usdtc, 4);
            return $usdt;
        } else {
            return 0;
        }
    }
    // 以上为蜜蜂接口函数
    /**
     * @Title: 模拟汇率usd/usdt
     */
    public static function get_me_exchange_rate_usdt()
    {
        $usd_usdt = Cache('usd_usdt');
        if (empty($usd_usdt)) {
            $usd_usdt = sysconfig('base', 'usd_usdt') . rand(100000, 999999);
            Cache::set('usd_usdt', $usd_usdt, 60);
        }
        return $usd_usdt;
    }
    /**
     * @Title: 币转USDT，过渡为USD
     * @param {*} $num
     * @param {*} $code
     */
    public static function get_me_price_to_usdt($num = 1, $code = 'etcusdt')
    {
        $info = Db::name('product_lists')->where('code', $code)->value('last_price');
        if ($info) {
            $usdtc = bcmul(1, $info, 20);
            //精度相乘
            $usdt = round_pad_zero($num * $usdtc, 8);
            return $usdt;
        } else {
            return 0;
        }
    }
    /**
     * @Title: USDT转币，过渡为USD
     * @param {*} $num
     * @param {*} $code
     */
    public static function get_me_price_usdt_to($num = 1, $code = 'etcusdt')
    {
        $info = Db::name('product_lists')->where('code', $code)->value('last_price');
        if ($info) {
            $usdtc = bcdiv(1, $info, 20);
            $usdt = number_format($num * $usdtc, 8);
            return $usdt;
        } else {
            return 0;
        }
    }
    /**
     * @Title: 直得结果USD
     * @param {*} $info
     */
    public static function get_me_price_usdt_to_usd($info = 0, $n = 6)
    {
        $usd = self::get_me_exchange_rate_usdt();
        if ($info && $usd) {
            $usdtc = bcdiv($info, $usd, 20);
            $usdt = number_format($usdtc, $n);
            return $usdt;
        } else {
            return 0;
        }
    }
    public static function get_me_price_usdt_to_usd_close($info = 0, $close = 0, $n = 4)
    {
        $usd = self::get_me_exchange_rate_usdt();
        if ($info && $usd) {
            $usdtc = bcdiv($info, $usd);
            if ($close > 0) {
                $usdt = bc_mul($usdtc, $close);
            } else {
                $usdt = $usdtc;
            }
            return $usdt;
        } else {
            return 0;
        }
    }
    //以上为模拟操作
    public static function get_huobi_market_tickers($allcode = [])
    {
        $tourl = self::huobi();
        $url = $tourl . "/market/tickers";
        $getdata = self::curlfun($url);
        $res = json_decode($getdata, 1);
        pp($res);
    }
    public static function get_huobi_exchange_rate($allcode = [])
    {
        $tourl = self::huobi();
        $url = $tourl . "/v1/stable_coin/exchange_rate";
        $getdata = self::curlfun($url);
        $res = json_decode($getdata, 1);
        pp($res);
    }
    /**
     * @Title: K线
     */
    public static function get_huobi_kline($code = null, $num = 30, $type = '1min')
    {
        if (!$code) {
            return false;
        }
        $tourl = self::huobi();
        $url = $tourl . "/market/history/kline?symbol=" . $code . "&period=" . $type . "&size=" . $num . "";
        $getdata = self::curlfun($url);
        $res = json_decode($getdata, 1);
        if ($res) {
            $rest = [];
            $rest['ch'] = $res['ch'];
            if ($res['data']) {
                $res['data'] = array_reverse($res['data']);
                foreach ($res['data'] as $k => $v) {
                    $rest['data'][$k]['amount'] = $v['open'];
                    $rest['data'][$k]['open'] = $v['open'];
                    $rest['data'][$k]['high'] = $v['high'];
                    $rest['data'][$k]['low'] = $v['low'];
                    $rest['data'][$k]['close'] = $v['close'];
                    $rest['data'][$k]['id'] = $v['id'];
                    $rest['data'][$k]['vol'] = $v['vol'];
                    $rest['data'][$k]['volume'] = $v['vol'];
                    $rest['data'][$k]['isBarClosed'] = true;
                    $rest['data'][$k]['isLastBar'] = false;
                    $rest['data'][$k]['time'] = $v['id'] * 1000;
                }
            }
            return $rest;
        }
    }
}