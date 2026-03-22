<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-29 00:29:47
 * @LastEditTime: 2021-08-05 02:51:35
 * @Description: Forward, no stop
 */

namespace app\admin\controller\order;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="订单：认购订单管理")
 */
class Ieorg extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;
    
    protected $allowModifyFields = [
        'status', 'remark', 'is_delete'
    ];

    protected $sort = [
        'create_time' => 'desc',
        'id'   => 'desc',
    ];
    
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\OrderIeorg();
        
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
                ->withJoin(['productLists', 'ieoLists', 'memberUser'], 'LEFT')
                ->where($where)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'ieoLists', 'memberUser'], 'LEFT')
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
     * @NodeAnotation(title="释放认购")
     */
    public function release($id)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->find($id);
            empty($row) && $this->error('数据不存在');
            $data['type'] = 2;
            try {
                $save = $this->model->update($data,['id'=>$id]);
                if($save){
                    $m_wallet = new \app\admin\model\MemberWallet();
                    $m_walletlog = new \app\admin\model\MemberWalletLog();
                    $m_user = new \app\admin\model\MemberUser();
                    $user_wallet = $m_wallet->where('product_id',$row['product_id'])->where('uid',$row['uid'])->field('id,ex_money,lock_ex_money')->find();
                    $user_lock_ex_money = $user_wallet['lock_ex_money']-$row['money'];
                    $user_now_ex_money = $user_wallet['ex_money']+$row['money'];
                    $is_test = $m_user->where('id',$row['uid'])->value('is_test');
                    $dowallet = $m_wallet->update(['ex_money'=>$user_now_ex_money,'lock_ex_money'=>$user_lock_ex_money],['id'=>$user_wallet['id']]);
                    if($dowallet){
                        $logdata['account'] = $row['money'];
                        $logdata['wallet_id'] = $user_wallet['id'];
                        $logdata['product_id'] = $row['product_id'];
                        $logdata['uid'] = $row['uid'];
                        $logdata['is_test'] = $is_test;
                        $logdata['before'] = $user_wallet['ex_money'];
                        $logdata['after'] = $user_now_ex_money;
                        $logdata['account_sxf'] = 0;
                        $logdata['all_account'] = bc_sub($logdata['account'],$logdata['account_sxf']);
                        $logdata['type'] = 8;//认购IEO
                        $logdata['status'] = 22;//认购释放
                        $logdata['order_type'] = 2;//认购释放
                        $logdata['order_id'] = $row['id'];
                        $inlog = $m_walletlog->save($logdata);
                    }
                }
            } catch (\Exception $e) {
                $this->error('释放失败');
            }
            $save ? $this->success('释放成功') : $this->error('释放失败');
        }
    }

}