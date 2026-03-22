<?php
/*
 * @Author: OpenAI
 * @Description: 贷款申请管理
 */

namespace app\admin\controller\loan;

use app\common\controller\AdminController;
use app\common\LoanStatus;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="功能：贷款申请管理")
 */
class Order extends AdminController
{
    use \app\admin\traits\Curd;

    protected $sort = [
        'id' => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\LoanOrder();
        $this->loanModel = new \app\admin\model\LoanLists();
        $this->walletModel = new \app\admin\model\MemberWallet();
        $this->walletLogModel = new \app\admin\model\MemberWalletLog();
        $this->memberModel = new \app\admin\model\MemberUser();
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

            list($page, $limit, $where) = $this->buildTableParames();

            $query = $this->model
                ->alias('a')
                ->leftJoin('loan_lists l', 'a.loan_id = l.id')
                ->leftJoin('member_user u', 'a.uid = u.id')
                ->field('a.*,l.name as loan_name,u.username');

            $count = $query->where($where)->count();

            $list = $query->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();

            if ($list) {
                foreach ($list as $k => $v) {
                    $list[$k]['status_text'] = $this->getStatusText($v['status']);
                    $list[$k]['review_admin_name'] = '';
                    if (!empty($v['admin_id'])) {
                        $list[$k]['review_admin_name'] = \app\admin\model\SystemAdmin::where('id', $v['admin_id'])->value('username');
                    }
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
     * @NodeAnotation(title="详情")
     */
    public function detail($id)
    {
        $row = $this->model
            ->alias('a')
            ->leftJoin('loan_lists l', 'a.loan_id = l.id')
            ->leftJoin('member_user u', 'a.uid = u.id')
            ->field('a.*,l.name as loan_name,l.description as loan_description,u.username')
            ->where('a.id', $id)
            ->find();

        empty($row) && $this->error('数据不存在');

        $row['status_text'] = $this->getStatusText($row['status']);
        $row['review_admin_name'] = '';
        if (!empty($row['admin_id'])) {
            $row['review_admin_name'] = \app\admin\model\SystemAdmin::where('id', $row['admin_id'])->value('username');
        }

        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="审核")
     */
    public function review($id)
    {
        $row = $this->model
            ->alias('a')
            ->leftJoin('loan_lists l', 'a.loan_id = l.id')
            ->leftJoin('member_user u', 'a.uid = u.id')
            ->field('a.*,l.name as loan_name,u.username')
            ->where('a.id', $id)
            ->find();

        empty($row) && $this->error('数据不存在');

        if ($row['status'] != LoanStatus::WAIT) {
            $this->error('当前状态不可审核');
        }

        if ($this->request->isAjax()) {
            $post = $this->request->post();

            $status = intval($post['status']);
            $reviewRemark = trim($post['review_remark']);

            if (!in_array($status, [LoanStatus::PASS, LoanStatus::REJECT])) {
                $this->error('审核状态错误');
            }

            if ($status == LoanStatus::REJECT && $reviewRemark === '') {
                $this->error('拒绝时请输入审核备注');
            }

            Db::startTrans();
            try {
                $loanOrder = $this->model->lock(true)->where('id', $id)->find();
                if (empty($loanOrder)) {
                    throw new \Exception('贷款申请不存在');
                }
                if ($loanOrder['status'] != LoanStatus::WAIT) {
                    throw new \Exception('该申请已处理，请勿重复审核');
                }

                $updateData = [
                    'status' => $status,
                    'admin_id' => session('admin.id'),
                    'review_remark' => $reviewRemark,
                    'review_time' => time(),
                    'update_time' => time(),
                ];

                if ($status == LoanStatus::PASS) {
                    $moneyInfo = $this->calcLoanMoney(
                        $loanOrder['amount'],
                        $loanOrder['interest_rate'],
                        $loanOrder['repayment_period']
                    );

                    $loanTime = time();
                    $dueTime = strtotime('+' . intval($loanOrder['repayment_period']) . ' day', $loanTime);

                    $updateData['loan_time'] = $loanTime;
                    $updateData['due_time'] = $dueTime;
                    $updateData['total_interest'] = $moneyInfo['total_interest'];
                    $updateData['total_repayment'] = $moneyInfo['total_repayment'];

                    $this->model->where('id', $id)->update($updateData);

                    $this->doDisburseLoan($loanOrder, $moneyInfo['total_interest'], $moneyInfo['total_repayment']);
                } else {
                    $this->model->where('id', $id)->update($updateData);
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            $this->success('审核成功');
        }

        $row['status_text'] = $this->getStatusText($row['status']);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->find($id);
            empty($row) && $this->error('数据不存在');

            try {
                $save = $row->delete();
            } catch (\Exception $e) {
                $this->error('删除失败');
            }

            $save ? $this->success('删除成功') : $this->error('删除失败');
        }
    }

    private function doDisburseLoan($loanOrder, $totalInterest, $totalRepayment)
    {
        $baseProduct = \app\admin\model\ProductLists::where('base', 1)->field('id,title')->find();
        if (empty($baseProduct)) {
            throw new \Exception('未找到基础钱包币种配置');
        }

        $wallet = $this->walletModel
            ->where('product_id', $baseProduct['id'])
            ->where('uid', $loanOrder['uid'])
            ->lock(true)
            ->find();

        if (empty($wallet)) {
            throw new \Exception('用户钱包不存在');
        }

        $userInfo = $this->memberModel->where('id', $loanOrder['uid'])->find();
        if (empty($userInfo)) {
            throw new \Exception('用户不存在');
        }

        $beforeMoney = $wallet['ex_money'];
        $afterMoney = bcadd($beforeMoney, $loanOrder['amount'], 4);

        $walletRes = $this->walletModel
            ->where('id', $wallet['id'])
            ->update(['up_money' => $afterMoney]);

        if (!$walletRes) {
            throw new \Exception('放款失败，钱包更新失败');
        }

        $logdata = [];
        $logdata['account'] = $loanOrder['amount'];
        $logdata['wallet_id'] = $wallet['id'];
        $logdata['product_id'] = $baseProduct['id'];
        $logdata['uid'] = $loanOrder['uid'];
        $logdata['is_test'] = $userInfo['is_test'];
        $logdata['before'] = $beforeMoney;
        $logdata['after'] = $afterMoney;
        $logdata['account_sxf'] = 0;
        $logdata['all_account'] = $loanOrder['amount'];
        $logdata['type'] = 13; // 贷款放款
        $logdata['title'] = $baseProduct['title'];
        $logdata['remark'] = '贷款放款';
        $logdata['order_type'] = 1; // 放款
        $logdata['order_id'] = $loanOrder['id'];

        $logRes = $this->walletLogModel->save($logdata);
        if (!$logRes) {
            throw new \Exception('放款失败，流水记录失败');
        }
    }

    private function calcLoanMoney($amount, $interestRate, $days)
    {
        $dailyRate = bc_div($interestRate, 100, 8);
        $dailyRate = bc_div($dailyRate, 365, 8);
        $totalInterest = bcmul(bcmul($amount, $dailyRate, 8), $days, 4);
        $totalRepayment = bcadd($amount, $totalInterest, 4);

        return [
            'total_interest' => $totalInterest,
            'total_repayment' => $totalRepayment,
        ];
    }

    private function getStatusText($status)
    {
        $map = LoanStatus::getMap();
        return isset($map[$status]) ? $map[$status] : '未知状态';
    }

    /**
     * @NodeAnotation(title="还款")
     */
    public function repay($id)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->find($id);
            empty($row) && $this->error('数据不存在');

            if (!in_array($row['status'], [\app\common\LoanStatus::PASS, \app\common\LoanStatus::OVERDUE])) {
                $this->error('当前状态不可执行还款');
            }

            try {
                $save = $this->model->where('id', $id)->update([
                    'status' => \app\common\LoanStatus::FINISH,
                    'repayment_time' => time(),
                    'update_time' => time(),
                ]);
            } catch (\Exception $e) {
                $this->error('还款处理失败');
            }

            $save ? $this->success('还款处理成功') : $this->error('还款处理失败');
        }
    }
}