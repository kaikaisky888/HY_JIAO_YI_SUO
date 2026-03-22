<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-08-05 02:54:44
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;
use app\common\FoxKline;

class Ieorg extends IndexController
{
    protected $lang;
    
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->lang = $this->request->cookie('think_lang') ?: 'zh-cn';
    }
    
    public function index()
    {
        $product = \app\admin\model\IeoLists::where('status',1)->order('sort','desc')->select();
        if($product){
            foreach($product as $k => $v){
                $product[$k]['info'] = \app\admin\model\LangLists::where('item','ieo')->where('item_id', $v['id'])->where('lang', $this->lang)->find();
            }
        }
        $this->assign('product',$product);
        $web_name = lang('ieorg.title').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name,'topmenu'=>'ieorg']);
        return $this->fetch();
    }

    public function show()
    {
        $id = request()->param('id/d','','intval');
        if($id){
            $row = \app\admin\model\IeoLists::where('status',1)->where('id', $id)->find();
            if(!$row){
                $this->redirect(server_url());
            }
            $row['info'] = \app\admin\model\LangLists::where('item','ieo')->where('item_id', $id)->where('lang', $this->lang)->find();
            $web_name = $row['title'].'-'.$this->web_name;
            $this->assign(['web_name'=>$web_name,'row'=>$row,'topmenu'=>'ieorg']);
            $can_products = \app\admin\model\ProductLists::where('isIeorg',1)->field('id,title')->select();
            $this->assign('can_products',$can_products);
            return $this->fetch();
        }
    }

    public function get_wallet()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','int');
            $product_id = $post['product_id'];
            $good_id = $post['good_id'];
            $info = [];
            if($product_id && $good_id){
                $goods =  \app\admin\model\IeoLists::where('status',1)->field('coin_title,ieo_usdt_price,ieo_btc_price,ieo_eth_price')->where('id', $good_id)->find();
                $info['title'] = \app\admin\model\ProductLists::where('id',$product_id)->value('title');
                $info['money'] = \app\admin\model\MemberWallet::where('product_id',$product_id)->where('uid',$this->memberInfo['id'])->value('ex_money');
                $info['equal'] = $goods['ieo_'.strtolower($info['title']).'_price'];
                $info['equal_tit'] = '1 '.$goods['coin_title'].' = '.$info['equal'].' '.$info['title'];
                $info['money_tit'] = lang('ieorg.can_use_money').':'.$info['money'];
                $info['buy_tit'] = lang('ieorg.buy_use_money').$info['title'].':';
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
            $equal = $post['equal'];
            $info = [];
            if($equal && $num){
                $info['donum'] = bc_mul($num, $equal);
                return json(['code'=>1,'data'=>$info]);
            }
            return json(['code'=>0]);
        }
        return json(['code'=>0]);
    }

    public function get_nums()
    {
        if(request()->isPost()){
            $post = $this->request->post(null,'','trim');
            $money = $post['money'];
            $equal = $post['equal'];
            $info = [];
            if($equal && $money){
                $info['donum'] = bc_div($money, $equal);
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
            $indata['product_id'] = $post['product_id'];
            $indata['buy_account'] = $post['buy_account'];
            $indata['ieo_id'] = $post['ieo_id'];
            $indata['money'] = $post['money'];
            $indata['uid'] = session('member.id');
            $indata['type'] = 1;//认购冻结
            $m_wallet = new \app\admin\model\MemberWallet();
            $m_ieo = new \app\admin\model\IeoLists();
            $m_order = new \app\admin\model\OrderIeorg();
            $m_walletlog = new \app\admin\model\MemberWalletLog();
            $ieo_num = $m_ieo->where('id',$indata['ieo_id'])->value('ieo_num');
            if($indata['buy_account'] > $ieo_num){
                return $this->error(fox_all_replace(lang('ieorg.check_max_ieo_num'),floatVal($ieo_num)));
            }
            $user_wallet = $m_wallet->where('product_id',$indata['product_id'])->where('uid',$this->memberInfo['id'])->field('id,ex_money,lock_ex_money')->find();
            if($indata['money'] > $user_wallet['ex_money']){
                return $this->error(fox_all_replace(lang('ieorg.check_max_money_num'),floatVal($user_wallet['ex_money'])));
            }
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $save = $m_order->save($indata);
                if($save){
                    $lastId = $m_order->id;
                    $user_lock_ex_money = $user_wallet['lock_ex_money']+$indata['money'];
                    $user_now_ex_money = $user_wallet['ex_money']-$indata['money'];
                    $dowallet = $m_wallet->update(['ex_money'=>$user_now_ex_money,'lock_ex_money'=>$user_lock_ex_money],['id'=>$user_wallet['id']]);
                    if($dowallet){
                        $logdata['account'] = $indata['money'];
                        $logdata['wallet_id'] = $user_wallet['id'];
                        $logdata['product_id'] = $indata['product_id'];
                        $logdata['uid'] = session('member.id');
                        $logdata['is_test'] = session('member.is_test');
                        $logdata['before'] = $user_wallet['ex_money'];
                        $logdata['after'] = $user_now_ex_money;
                        $logdata['account_sxf'] = 0;
                        $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                        $logdata['type'] = 8;//认购IEO
                        $logdata['status'] = 21;//认购冻结
                        $logdata['order_type'] = 1;//认购冻结
                        $logdata['order_id'] = $lastId;
                        $inlog = $m_walletlog->save($logdata);
                    }
                }
            }catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save && $dowallet && $inlog){
                return json(['code'=>1,'msg'=>lang('ieorg.order_success'),'id'=>$lastId]);
            }
            return $this->error(lang('public.do_fail'));
        }
    }

}

