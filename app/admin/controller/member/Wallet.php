<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:52:49
 * @LastEditTime: 2021-10-09 21:08:07
 * @Description: Forward, no stop
 */

namespace app\admin\controller\member;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="会员：用户钱包")
 */
class Wallet extends AdminController
{
    protected $sort = [
        'uid' => 'desc',
        'id'   => 'desc',
    ];

    protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\MemberWallet();
        $this->model_data = new \app\admin\model\MemberWalletData();
        $this->modellog = new \app\admin\model\MemberWalletLog();
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
     * @NodeAnotation(title="操作钱包")
     */
    public function dowallet($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $wdata = [];
            $wdata['uid'] = $row['uid'];
            $wdata['wallet_id'] = $row['id'];
            $wdata['product_id'] = $row['product_id'];
            $wdata['type'] = $post['type'];
            $wdata['num'] = $post['num'];
            $wdata['data_type'] = $post['data_type'];
            $wdata['remark'] = $post['remark'];
            $wdata['douid'] = $this->adminInfo['id'];
            try {
                $save = $this->model_data->save($wdata);
                $user = \app\admin\model\MemberUser::where('id',$wdata['uid'])->field('is_test')->find();
                $cointitle = \app\admin\model\ProductLists::where('id',$wdata['product_id'])->value('title');
                $walletinfo = \app\admin\model\MemberWallet::where('product_id',$wdata['product_id'])->where('uid',$wdata['uid'])->field('ex_money,le_money,op_money,up_money,cm_money,id')->find();
                if($save && !strstr($wdata['data_type'], "lock_")){
                    $indata = [];
                    $indata['account'] = $wdata['num'];
                    $indata['product_id'] = $wdata['product_id'];
                    $indata['wallet_id'] = $wdata['wallet_id'];
                    $indata['uid'] = $wdata['uid'];
                    $indata['is_test'] = $user['is_test'];
                    $indata['status'] = 2;
                    $indata['account_sxf'] = 0;
                    $indata['before'] = $walletinfo[''.$wdata['data_type'].''];
                    if($wdata['type'] == 1){
                        $indata['title'] = $cointitle.':加款';
                        $indata['all_account'] = bc_add($indata['account'],$indata['account_sxf']);
                        $indata['after'] = bc_add($indata['before'],$indata['all_account']);
                        $indata['order_type'] = "55".$wdata['type'];
                    }else{
                        $indata['title'] = $cointitle.':减款';
                        $indata['all_account'] = bc_sub($indata['account'],$indata['account_sxf']);
                        $indata['after'] = bc_sub($indata['before'],$indata['all_account']);
                        $indata['order_type'] = "55".$wdata['type'];
                    }
                    if(strstr($wdata['data_type'], "ex_")){
                        $indata['type'] = 4;
                    }
                    if(strstr($wdata['data_type'], "le_")){
                        $indata['type'] = 5;
                    }
                    if(strstr($wdata['data_type'], "op_")){
                        $indata['type'] = 6;
                    }
                    if(strstr($wdata['data_type'], "up_")){
                        $indata['type'] = 7;
                    }
                    if($this->modellog->save($indata)){
                        $this->model->update([''.$wdata['data_type'].''=>$indata['after']],['id'=>$indata['wallet_id']]);
                    }
                }else{
                    if($wdata['type'] == 1){
                        $indata['after'] = bc_add($walletinfo[''.$wdata['data_type'].''],$wdata['num']);
                    }else{
                        $indata['after'] = bc_sub($walletinfo[''.$wdata['data_type'].''],$wdata['num']);
                    }
                    $this->model->update([''.$wdata['data_type'].''=>$indata['after']],['id'=>$indata['wallet_id']]);
                }
            } catch (\Exception $e) {
                $this->error('调整失败');
            }
            $save ? $this->success('调整成功') : $this->error('调整失败');
        }
        $row['member'] = \app\admin\model\MemberUser::where('id',$row['uid'])->value('username');
        $row['coin'] = \app\admin\model\ProductLists::where('id',$row['product_id'])->value('title');
        $types = \app\admin\model\ProductLists::where('id',$row['product_id'])->value('types');
        $row['types'] = explode(',',$types);
        $this->assign('row', $row);
        return $this->fetch();
    }
}