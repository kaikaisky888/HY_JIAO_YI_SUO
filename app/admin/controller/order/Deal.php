<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-29 00:29:47
 * @LastEditTime: 2021-08-23 04:22:55
 * @Description: Forward, no stop
 */

namespace app\admin\controller\order;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="订单：币币订单管理")
 */
class Deal extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;
    
    protected $allowModifyFields = [
        'status', 'remark', 'is_delete'
    ];

    protected $sort = [
        'update_time' => 'desc',
        'id'   => 'desc',
    ];
    
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\OrderDeal();
        
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            if($this->adminInfo['is_team']==1){
                list($page, $limit, $where) = $this->buildTableParames([], $this->adminInfo['id'], 'memberUser');
            }else{
                list($page, $limit, $where) = $this->buildTableParames();
            }
            $count = $this->model
                ->withJoin(['productLists', 'memberUser'], 'LEFT')
                ->where($where)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser'], 'LEFT')
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="订单完成")
     */
    public function edeal($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $m_order = new \app\admin\model\OrderDeal();
            $m_product = new \app\admin\model\ProductLists();
            $m_wallet = new \app\admin\model\MemberWallet();
            $m_user = new \app\admin\model\MemberUser();
            $m_log = new \app\admin\model\MemberWalletLog();
    
            $order = $m_order->where('id', $id)->field('id,uid,price,account_product,title,direction,product_id')->find();//加锁
            $is_test = $m_user->where('id',$order['uid'])->value('is_test');
            $pro = $m_product->where('id',$order['product_id'])->field('id,close')->find();
            $user_wallet = $m_wallet->where('product_id',$pro['id'])->where('uid',$order['uid'])->field('id,product_id,ex_money')->find();
            if($order['direction']==1){//买入
                $now_money = bc_add($user_wallet['ex_money'],$order['account_product']);
                if($m_wallet->where('id',$user_wallet['id'])->update(['ex_money'=>$now_money])){
                    $m_order->where('id', $order['id'])->update(['status'=>2,'update_time'=>time(),'price_product'=>$pro['close']]);
                    $lgdata['account'] = $order['account_product'];
                    $lgdata['wallet_id'] = $user_wallet['id'];
                    $lgdata['product_id'] = $user_wallet['product_id'];
                    $lgdata['uid'] = $order['uid'];
                    $lgdata['is_test'] = $is_test;
                    $lgdata['before'] = $user_wallet['ex_money'];
                    $lgdata['after'] = bc_sub($user_wallet['ex_money'],$order['account_product']);
                    $lgdata['account_sxf'] = 0;
                    $lgdata['all_account'] = bc_sub($lgdata['account'],$lgdata['account_sxf']);
                    $lgdata['type'] = 4;
                    $lgdata['title'] = $order['title'];
                    $lgdata['order_type'] = 11;//买得
                    $lgdata['order_id'] = $order['id'];
                    $m_log->save($lgdata);
                }
            }else if($order['direction']==2){//卖出
                $productBase = $m_product->where('base',1)->field('id,title')->find();
                $base_wallet = $m_wallet->where('product_id',$productBase['id'])->where('uid',$order['uid'])->field('id,product_id,ex_money')->find();
                $now_ex_money = bc_add($base_wallet['ex_money'],$order['account_product']);
                if($m_wallet->where('id',$base_wallet['id'])->update(['ex_money'=>$now_ex_money])){
                    $m_order->where('id', $order['id'])->update(['status'=>2,'update_time'=>time(),'price_product'=>$pro['close']]);
                    $lgdata['account'] = $order['account_product'];
                    $lgdata['wallet_id'] = $base_wallet['id'];
                    $lgdata['product_id'] = $base_wallet['product_id'];
                    $lgdata['uid'] = $order['uid'];
                    $lgdata['is_test'] = $is_test;
                    $lgdata['before'] = $base_wallet['ex_money'];
                    $lgdata['after'] = bc_add($base_wallet['ex_money'],$order['account_product']);
                    $lgdata['account_sxf'] = 0;
                    $lgdata['all_account'] = bc_sub($lgdata['account'],$lgdata['account_sxf']);
                    $lgdata['type'] = 4;
                    $lgdata['title'] = $order['title'];
                    $lgdata['order_type'] = 22;//卖出得
                    $lgdata['order_id'] = $order['id'];
                    $m_log->save($lgdata);
                }
            }
            $m_order->where('id',$order['id'])->update(['status'=>2,'price_product'=>$order['price'],'update_time'=>time()]);
            
            $m_log ? $this->success('处理成功') : $this->error('处理失败');
        }
    }

}