<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:52:49
 * @LastEditTime: 2021-10-12 11:56:13
 * @Description: Forward, no stop
 */

namespace app\admin\controller\member;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="财务：用户钱包日志")
 */
class WalletLog extends AdminController
{
    protected $sort = [
        'update_time' => 'desc',
        'id'   => 'desc',
    ];

    protected $relationSearch = true;
    protected $modelwallet;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\MemberWalletLog();
        $this->modelwallet = new \app\admin\model\MemberWallet();
    }

    
    /**
     * @NodeAnotation(title="充值列表")
     */
    public function recharge()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',1)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',1)
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
     * @NodeAnotation(title="充值处理")
     */
    public function erecharge($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        $row['coin'] = \app\admin\model\ProductLists::where('id',$row['product_id'])->value('title');
        $row['ex_money'] = \app\admin\model\MemberWallet::where('id',$row['wallet_id'])->value('ex_money');
        $walletRow = \app\admin\model\MemberWallet::where('id',$row['wallet_id'])->find();
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post['do_uid'] = $this->adminInfo['id'];
            $post['do_time'] = time();
            if($post['status']==2){
                $post['before'] = $row['ex_money'];
                $after = bc_add($row['ex_money'],$row['all_account']);
                $post['after'] = $after;
            }
            try {
                $save = $row->save($post);
                if($post['status']==2){
                    $this->modelwallet->update([
                        'ex_money'=>bc_add($walletRow['ex_money'],$row['all_account']),
                    ],['id'=>$row['wallet_id']]);
                }
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            if ($save) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $wallet_log_status = \think\facade\Config::get('allset.wallet_log_status');
        $this->assign('wallet_log_status', $wallet_log_status);
        $this->assign([
            'id'          => $id,
            'row'         => $row,
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="查看处理")
     */
    public function orecharge($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        $row['coin'] = \app\admin\model\ProductLists::where('id',$row['product_id'])->value('title');
        $row['ex_money'] = \app\admin\model\MemberWallet::where('id',$row['wallet_id'])->value('ex_money');
        
        $wallet_log_status = \think\facade\Config::get('allset.wallet_log_status');
        $this->assign('wallet_log_status', $wallet_log_status);
        $this->assign([
            'id'          => $id,
            'row'         => $row,
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="提现列表")
     */
    public function withdraw()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',2)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',2)
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
     * @NodeAnotation(title="提现处理")
     */
    public function ewithdraw($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        $row['coin'] = \app\admin\model\ProductLists::where('id',$row['product_id'])->value('title');
        $walletinfo = \app\admin\model\MemberWallet::where('id',$row['wallet_id'])->field('ex_money,lock_ex_money')->find();
        $row['ex_money'] =$walletinfo['ex_money'];
        $row['lock_ex_money'] =$walletinfo['lock_ex_money'];
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post['do_uid'] = $this->adminInfo['id'];
            $post['do_time'] = time();
            if($post['status']==2){
                $post['before'] = bc_add($row['ex_money'],$row['account']);
                $post['after'] = $row['ex_money'];
            }
            if($post['status']==3){
                $post['before'] = bc_add($row['ex_money'],$row['account']);
                $post['after'] = bc_add($row['ex_money'],$row['account']);
            }
            $after = bc_sub($row['lock_ex_money'],$row['account']);
            try {
                if($save = $row->save($post)){
                    if($post['status']==2){
                        $this->modelwallet->update(['lock_ex_money'=>$after],['id'=>$row['wallet_id']]);
                    }
                    if($post['status']==3){
                        $back = bc_add($row['ex_money'],$row['account']);
                        $this->modelwallet->update(['ex_money'=>$back,'lock_ex_money'=>$after],['id'=>$row['wallet_id']]);
                    }
                }
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            if ($save) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $wallet_log_status = \think\facade\Config::get('allset.wallet_log_status');
        $this->assign('wallet_log_status', $wallet_log_status);
        $this->assign([
            'id'          => $id,
            'row'         => $row,
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="划转明细")
     */
    public function transfer()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',3)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',3)
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
     * @NodeAnotation(title="币币订单日志")
     */
    public function orders()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',4)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',4)
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
     * @NodeAnotation(title="合约订单日志")
     */
    public function leverdeal()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',5)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',5)
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
     * @NodeAnotation(title="期权订单日志")
     */
    public function seconds()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',6)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',6)
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
     * @NodeAnotation(title="理财订单日志")
     */
    public function good()
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
                ->withJoin(['memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',7)
                ->count();
            $list = $this->model
                ->withJoin(['memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',7)
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
     * @NodeAnotation(title="认购订单日志")
     */
    public function ieorg()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',8)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',8)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            if($list){
                foreach($list as $k => $v){
                    $ieoid = \app\admin\model\OrderIeorg::where('id',$v['order_id'])->value('ieo_id');
                    $list[$k]['ieotitle'] = \app\admin\model\IeoLists::where('id',$ieoid)->value('title');
                }
            }
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
     * @NodeAnotation(title="挖矿订单日志")
     */
    public function winer()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',9)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',9)
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
     * @NodeAnotation(title="期权返佣日志")
     */
    public function cmseconds()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',12)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',12)
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
     * @NodeAnotation(title="理财返佣日志")
     */
    public function cmgoods()
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
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',11)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser', 'memberWallet'], 'LEFT')
                ->where($where)
                ->where('type',11)
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

}