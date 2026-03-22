<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-09-12 00:04:02
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;
use app\common\FoxKline;
use app\common\FoxCommon;
use app\common\service\ElasticService;
use app\common\service\HuobiRedis;
use think\facade\Cache;

class Test extends IndexController
{
    public function me()
    {
        $me = Cache('me');
        if (empty($me)) {
            $me = time();
            cache('site_me', $me);
            Cache::set('me', $me, 3600);
        }
        return $me;
    }

    public function do()
    {
        pp(\foxmat_seconds(30));
        exit;
        $today=strtotime(date("Y-m-d"));
        pp($today);
        $m_order = new \app\admin\model\OrderGood();
        $m_wallet = new \app\admin\model\MemberWallet();
        $m_user = new \app\admin\model\MemberUser();
        $m_log = new \app\admin\model\MemberWalletLog();
        $orderlist = $m_order->field('id')->where('lock_time','<=',$today)->where('status',1)->limit(50)->select();
        $productBase = \app\admin\model\ProductLists::where('base',1)->field('id,title')->find();
        if($orderlist){
            p($orderlist);
        }
    }
    
    public function index()
    {
        $data = [
            'trc','etc','omni','other'
        ];
        $ct = count($data)-1;
        $n = rand(0,$ct);
        p(FoxCommon::strong_find('is me',$data[$n],'usdt'));
        exit;
        $invite_code_url = server_url().(string)url('wicket/register',['code'=>$this->memberInfo['invite_code']]);
        $invite_code_img = phpqrcode( $invite_code_url,'invite_code_'.$this->memberInfo['id']);
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
          echo createPoster($config);
        exit;
        pp(first_last_this_month());
        pp(this_month_day());
        exit;
        $levle = FoxCommon::level_send_member(33,1800,11,12);
        p($levle);exit;
        $rate = FoxCommon::find_upgood_rate(1);
        $rates = FoxCommon::find_seconds_rate(11);
        pp($rate);pp($rates);exit;
        $code = FoxCommon::only_invite_code(3);
        pp($code);
        $rate = FoxCommon::create_invite_code(3);
        $rates = FoxCommon::decode_invite_code($rate);
        pp($rate);pp($rates);exit;
        $t = strtotime(date("Y-m-d",strtotime("+1 day")));
        $today=strtotime(date("Y-m-d"));
        pp('今天'.$today);
        pp('明天'.$t);
        pp('相差'.($t-$today));
        pp(date("Y-m-d"));
        pp(date("Y-m-d",strtotime("+1 day")));
        exit;
        $a = '0.00001000';
        $b = '0.00019000';
        $c = FoxCommon::generateRand($a,$b);
        $cc = FoxCommon::kong_generateRand($a,$b);
        // $d = $this->num_mid_rand($a,$b);
        // print strlen(substr(strrchr($a, "."), 1));
        // var_dump($c);
        // var_dump($d);
        var_dump(bc_add($a,$b));
        var_dump($c);
        var_dump($cc);
        exit;
        // $a = '0.01235';
        // $b = '0.01225';
        // pp(floatcmp($a,$b));exit;
        // pp(FoxCommon::kline_k_prices(0.00066,4));
        // pp(FoxCommon::kline_k_prices(10.066,4));
        // pp(FoxCommon::kline_k_prices(100.0066,4));
        // pp(FoxCommon::kline_k_prices(1000.00066,4));
        // pp(FoxCommon::kline_k_prices(1000.0066,4));
        // exit;
        // pp(FoxKline::get_huobi_market_tickers());
        // FoxKline::get_huobi_exchange_rate();
        // exit;
        $setredis = \think\facade\Config::get('cache.stores.redis');
        $hbrds= new HuobiRedis($setredis['host'],$setredis['port'],$setredis['password']);
        $elastic = new ElasticService();
        // $stable = 'tradelog_btcusdt';
    
        // $sinfo = $hbrds->zrange($stable);
        // p($sinfo);
        // $stable = 'depthlist_btcusdt';
    
        // $sinfo = $hbrds->read($stable);
        // p(json_decode($sinfo['bid'],true));
        // p(json_decode($sinfo['ask'],true));
        // p($sinfo);
        // $msg = [];
        // $symbol = 'btcusdt';
        // $table = $symbol.'_tickers';
        // try {
        //     $insetinfo = $hbrds->read($table);
        // } catch (\Exception $e) {
        //     var_dump($e->getMessage());
        // }
        // if($insetinfo){
        //     $msg['market'] = $symbol;
        //     $msg['tick'] = [
        //         'open'=>(float)$insetinfo['close'],
        //         'close'=>(float)$insetinfo['close'],
        //         'high'=>(float)$insetinfo['high'],
        //         'low'=>(float)$insetinfo['low'],
        //         'change'=>(float)$insetinfo['change'],
        //         'amount'=>(float)$insetinfo['amount'],
        //         'count'=>(int)$insetinfo['count'],
        //         'id'=>(int)$insetinfo['id'],
        //         'vol'=>$insetinfo['volume'],
        //         'volume'=>$insetinfo['volume'],
        //     ];
        // }
        // pp($msg);
        // $table = 'market.btcusdt.kline.1min';
        $zero_table = 'market.otgusdt.kline.1min';
        $zero_time = strtotime(date("Y-m-d"),time());
        // p($elastic->get_map('market.btcusdt.kline.1min'));
        if($elastic->exist_index($zero_table)){
            pp($elastic->search_day($zero_table));
        }
        
        // p($elastic->search_svg($zero_table,'5min'));
        // pp($elastic->search_one($zero_table,$zero_time));
        // $zero_data = $elastic->search_one($zero_table,$zero_time);
        // if(isset($zero_data[0])){
        //     $zero_open = $zero_data[0]['close'];
        // }else{
        //     $zero_data_day = $elastic->search_one_day($zero_table,$zero_time);
        //     $zero_open = $zero_data_day[0]['close'];
        // }
        // p($zero_open);
        // $arrr['req'] = $table;
        //     $arrr['id'] = 'id10';
        //     $arrr['data'] = $elastic->search($table);
        //     pp($arrr);
        // $insetinfo = $hbrds->read_list($table);
        // pp($insetinfo);
        // p(FoxKline::get_huobi_kline('btcusdt'));
        // var_dump(FoxKline::get_mifeng_exchange_rate_usdt());
        // var_dump(FoxKline::get_mifeng_price('ETH'));
        // var_dump(FoxKline::get_me_exchange_rate_usdt());
        // var_dump(FoxKline::get_me_price_to_usdt(1,'ethusdt'));
        // var_dump(FoxKline::get_me_price_usdt_to(0.00038758,'ethusdt'));
        // var_dump(FoxKline::get_me_price_usdt_to_usd(123));
        // FoxCommon::WriteMyfile('me','kwgkwgtt');
        // p(FoxCommon::ReadMyfile('me'));
        // p(FoxCommon::TimeMyfile('me'));
        // p(FoxCommon::DelMyfile('me'));
    }
    
}

