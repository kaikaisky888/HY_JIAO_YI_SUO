<?php
/*
 * @Author: OpenAI
 * @Description: 前台贷款功能（已支持多语言）
 */

namespace app\mobile\controller;

use app\common\controller\MobileController;
use app\common\LoanStatus;
use think\facade\Db;

class Loan extends MobileController
{
    /**
     * 贷款产品语言兜底
     */
    protected function getLoanLangFallbacks()
    {
        return array_values(array_unique(array_filter([
            $this->lang,
            'zh-cn',
        ])));
    }

    /**
     * 获取贷款产品多语言
     */
    protected function getLoanLangInfo($loanId)
    {
        foreach ($this->getLoanLangFallbacks() as $lang) {
            $langInfo = \app\admin\model\LangLists::where('item', 'loan')
                ->where('item_id', $loanId)
                ->where('lang', $lang)
                ->find();

            if ($langInfo) {
                return $langInfo->toArray();
            }
        }

        return null;
    }

    /**
     * 给贷款产品补展示字段
     */
    protected function fillLoanShowFields(array $loan)
    {
        $langInfo = $this->getLoanLangInfo($loan['id']);

        if ($langInfo) {
            $loan['show_name'] = $langInfo['title'] ?? $loan['name'];
            $loan['show_logo'] = $langInfo['logo'] ?? $loan['logo'];
            $loan['show_description'] = $langInfo['content'] ?? $loan['description'];
            $loan['show_remark'] = $langInfo['remark'] ?? '';
        } else {
            $loan['show_name'] = $loan['name'];
            $loan['show_logo'] = $loan['logo'];
            $loan['show_description'] = $loan['description'];
            $loan['show_remark'] = '';
        }

        return $loan;
    }

    /**
     * 获取贷款产品当前语言名称
     */
    protected function getLoanShowName($loanId, $default = '--')
    {
        $loan = \app\admin\model\LoanLists::where('id', $loanId)
            ->field('id,name,logo,description')
            ->find();

        if (!$loan) {
            return $default;
        }

        $loan = $this->fillLoanShowFields($loan->toArray());
        return !empty($loan['show_name']) ? $loan['show_name'] : $default;
    }

    /**
     * 贷款首页
     */
    public function index()
    {
        $list = \app\admin\model\LoanLists::where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->select();

        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k] = $this->fillLoanShowFields($v->toArray());
            }
        }

        $web_name = lang('loan.title') . '-' . $this->web_name;
        $this->assign([
            'web_name' => $web_name,
            'topmenu'  => 'loan',
            'list'     => $list,
        ]);

        return $this->fetch();
    }

    /**
     * 贷款申请页
     */
    public function apply()
    {
        $loanId = request()->get('id/d', 0, 'int');

        $loan = \app\admin\model\LoanLists::where('id', $loanId)
            ->where('status', 1)
            ->find();

        empty($loan) && $this->error(lang('loan.error_product_not_exists'));

        $loan = $this->fillLoanShowFields($loan->toArray());

        $web_name = lang('loan.apply_title') . '-' . $this->web_name;
        $this->assign([
            'web_name' => $web_name,
            'topmenu'  => 'loan',
            'loan'     => $loan,
        ]);

        return $this->fetch();
    }

    /**
     * 提交贷款申请
     */
    public function doApply()
    {
        if (!request()->isPost()) {
            return $this->error(lang('public.do_fail'));
        }

        $loanId = request()->post('loan_id/d', 0, 'int');
        $amount = request()->post('amount', '0', 'floatval');
        $realName = trim(request()->post('real_name', '', 'trim'));
        $idNumber = trim(request()->post('id_number', '', 'trim'));
        $phone = trim(request()->post('phone', '', 'trim'));
        $idCardFront = trim(request()->post('id_card_front', '', 'trim'));
        $idCardBack = trim(request()->post('id_card_back', '', 'trim'));

        if (empty($loanId)) {
            return $this->error(lang('loan.error_choose_product'));
        }
        if ($amount <= 0) {
            return $this->error(lang('loan.error_amount'));
        }
        if ($realName === '') {
            return $this->error(lang('loan.error_real_name'));
        }
        if ($idNumber === '') {
            return $this->error(lang('loan.error_id_number'));
        }
        if ($phone === '') {
            return $this->error(lang('loan.error_phone'));
        }
        if ($idCardFront === '') {
            return $this->error(lang('loan.error_upload_front'));
        }
        if ($idCardBack === '') {
            return $this->error(lang('loan.error_upload_back'));
        }

        $check = request()->checkToken('__token__');
        if (false === $check) {
            return $this->error(lang('public.do_fail'));
        }

        $loan = \app\admin\model\LoanLists::where('id', $loanId)
            ->where('status', 1)
            ->find();

        if (empty($loan)) {
            return $this->error(lang('loan.error_product_not_exists'));
        }

        if (bccomp($amount, $loan['min_amount'], 4) < 0 || bccomp($amount, $loan['max_amount'], 4) > 0) {
            return $this->error(lang('loan.error_amount_range', [
                'min' => floatVal($loan['min_amount']),
                'max' => floatVal($loan['max_amount']),
            ]));
        }

        $waitOrder = \app\admin\model\LoanOrder::where('uid', $this->memberInfo['id'])
            ->where('status', LoanStatus::WAIT)
            ->find();

        if ($waitOrder) {
            return $this->error(lang('loan.error_wait_exists'));
        }

        $moneyInfo = $this->calcLoanMoney($amount, $loan['interest_rate'], $loan['repayment_period']);

        $indata = [];
        $indata['uid'] = $this->memberInfo['id'];
        $indata['loan_id'] = $loan['id'];
        $indata['amount'] = $amount;
        $indata['interest_rate'] = $loan['interest_rate'];
        $indata['repayment_period'] = $loan['repayment_period'];
        $indata['total_interest'] = $moneyInfo['total_interest'];
        $indata['total_repayment'] = $moneyInfo['total_repayment'];
        $indata['real_name'] = $realName;
        $indata['id_number'] = $idNumber;
        $indata['phone'] = $phone;
        $indata['id_card_front'] = $idCardFront;
        $indata['id_card_back'] = $idCardBack;
        $indata['status'] = LoanStatus::WAIT;
        $indata['create_time'] = time();
        $indata['update_time'] = time();

        try {
            $save = (new \app\admin\model\LoanOrder())->save($indata);
        } catch (\Exception $e) {
            return $this->error(lang('loan.error_submit_fail'));
        }

        if ($save) {
            $url = (string)url('loan/mylist');
            return $this->success(lang('loan.success_apply_wait'), [], $url);
        }

        return $this->error(lang('loan.error_submit_fail'));
    }

    /**
     * 我的贷款页面
     */
    public function mylist()
    {
        $web_name = lang('loan.mylist_title') . '-' . $this->web_name;
        $this->assign([
            'web_name' => $web_name,
            'topmenu'  => 'loan',
        ]);
        return $this->fetch();
    }

    /**
     * 异步获取我的贷款列表
     */
    public function listOrder()
    {
        if (request()->isPost()) {
            $page = request()->post('page/d', 1, 'int');
            $status = request()->post('status', '', 'trim');
            $limit = 10;

            $query = \app\admin\model\LoanOrder::alias('a')
                ->leftJoin('loan_lists l', 'a.loan_id = l.id')
                ->where('a.uid', $this->memberInfo['id'])
                ->field('a.*,l.name as loan_name');

            if ($status !== '' && $status !== 'all') {
                $query->where('a.status', intval($status));
            }

            $list = $query->page($page, $limit)
                ->order('a.id', 'desc')
                ->select();

            $countQuery = \app\admin\model\LoanOrder::alias('a')
                ->leftJoin('loan_lists l', 'a.loan_id = l.id')
                ->where('a.uid', $this->memberInfo['id']);

            if ($status !== '' && $status !== 'all') {
                $countQuery->where('a.status', intval($status));
            }

            $count = $countQuery->count();

            if ($list) {
                foreach ($list as $k => $v) {
                    $list[$k]['loan_name'] = $this->getLoanShowName($v['loan_id'], $v['loan_name'] ?: '--');
                    $list[$k]['status_text'] = $this->getStatusText($v['status']);
//                    $list[$k]['create_time_text'] = (!empty($v['create_time']) && is_numeric(trim((string)$v['create_time'])))
//                        ? date('Y-m-d H:i:s', intval($v['create_time']))
//                        : '';
                }
            }

            return json([
                'code' => 1,
                'data' => $list,
                'pages' => ceil($count / $limit),
            ]);
        }
    }

    /**
     * 贷款详情页
     */
    public function detail()
    {
        $id = request()->get('id/d', 0, 'int');

        $row = \app\admin\model\LoanOrder::alias('a')
            ->leftJoin('loan_lists l', 'a.loan_id = l.id')
            ->where('a.id', $id)
            ->where('a.uid', $this->memberInfo['id'])
            ->field('a.*,l.name as loan_name,l.description as loan_description')
            ->find();

        empty($row) && $this->error(lang('loan.error_apply_not_exists'));

        $langInfo = $this->getLoanLangInfo($row['loan_id']);
        if ($langInfo) {
            $row['loan_name'] = $langInfo['title'] ?? $row['loan_name'];
            $row['loan_description'] = $langInfo['content'] ?? $row['loan_description'];
        }

        $row['status_text'] = $this->getStatusText($row['status']);

        $web_name = lang('loan.detail_title') . '-' . $this->web_name;
        $this->assign([
            'web_name' => $web_name,
            'topmenu'  => 'loan',
            'row'      => $row,
        ]);

        return $this->fetch();
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
        $map = [
            -1 => lang('loan.status_cancel'),
            0  => lang('loan.status_wait'),
            1  => lang('loan.status_pass'),
            2  => lang('loan.status_reject'),
            4  => lang('loan.status_finish'),
            5  => lang('loan.status_overdue'),
        ];

        // 防止状态字段带空格、换行或非纯数字字符导致数组下标告警
        if (is_string($status)) {
            $status = trim($status);
        }

        // 只要不是纯数字且不是 -1 这种形式，就直接返回未知状态
        if (!is_numeric($status)) {
            return lang('loan.status_unknown');
        }

        $status = intval($status);

        return array_key_exists($status, $map) ? $map[$status] : lang('loan.status_unknown');
    }
    /**
     * 取消贷款申请
     */
    public function cancelApply()
    {
        if (!request()->isPost()) {
            return $this->error(lang('public.do_fail'));
        }

        $id = request()->post('id/d', 0, 'int');
        if (empty($id)) {
            return $this->error(lang('loan.error_param'));
        }

        $order = \app\admin\model\LoanOrder::where('id', $id)
            ->where('uid', $this->memberInfo['id'])
            ->find();

        if (empty($order)) {
            return $this->error(lang('loan.error_apply_not_exists'));
        }

        if ($order['status'] != LoanStatus::WAIT) {
            return $this->error(lang('loan.error_only_wait_cancel'));
        }

        try {
            $res = \app\admin\model\LoanOrder::where('id', $id)
                ->where('uid', $this->memberInfo['id'])
                ->update([
                    'status' => LoanStatus::CANCEL,
                    'update_time' => time(),
                ]);
        } catch (\Exception $e) {
            return $this->error(lang('loan.error_cancel_fail'));
        }

        $res ? $this->success(lang('loan.success_cancel')) : $this->error(lang('loan.error_cancel_fail'));
    }

    /**
     * 用户还款
     */
    public function doRepay()
    {
        if (!request()->isPost()) {
            return $this->error(lang('public.do_fail'));
        }

        $id = request()->post('id/d', 0, 'int');
        if (empty($id)) {
            return $this->error(lang('loan.error_param'));
        }

        $order = \app\admin\model\LoanOrder::where('id', $id)
            ->where('uid', $this->memberInfo['id'])
            ->find();

        if (empty($order)) {
            return $this->error(lang('loan.error_apply_not_exists'));
        }

        if (!in_array($order['status'], [LoanStatus::PASS, LoanStatus::OVERDUE])) {
            return $this->error(lang('loan.error_repay_status'));
        }

        $repayAmount = $order['total_repayment'];
        if (bccomp($repayAmount, '0', 4) <= 0) {
            return $this->error(lang('loan.error_repay_amount'));
        }

        $baseProduct = \app\admin\model\ProductLists::where('base', 1)->field('id,title')->find();
        if (empty($baseProduct)) {
            return $this->error(lang('loan.error_base_wallet_product'));
        }

        $wallet = \app\admin\model\MemberWallet::where('product_id', $baseProduct['id'])
            ->where('uid', $this->memberInfo['id'])
            ->find();
        if (empty($wallet)) {
            return $this->error(lang('loan.error_wallet_not_exists'));
        }

        if (bccomp($wallet['up_money'], $repayAmount, 4) < 0) {
            return $this->error(lang('loan.error_balance_not_enough'));
        }

        Db::startTrans();
        try {
            $lockOrder = \app\admin\model\LoanOrder::where('id', $id)
                ->where('uid', $this->memberInfo['id'])
                ->lock(true)
                ->find();

            if (empty($lockOrder)) {
                throw new \Exception(lang('loan.error_apply_not_exists'));
            }
            if (!in_array($lockOrder['status'], [LoanStatus::PASS, LoanStatus::OVERDUE])) {
                throw new \Exception(lang('loan.error_repay_status'));
            }

            $lockWallet = \app\admin\model\MemberWallet::where('id', $wallet['id'])->lock(true)->find();
            if (empty($lockWallet)) {
                throw new \Exception(lang('loan.error_wallet_not_exists'));
            }
            if (bccomp($lockWallet['up_money'], $repayAmount, 4) < 0) {
                throw new \Exception(lang('loan.error_balance_not_enough'));
            }

            $beforeMoney = $lockWallet['up_money'];
            $afterMoney = bcsub($beforeMoney, $repayAmount, 4);

            $walletRes = \app\admin\model\MemberWallet::where('id', $lockWallet['id'])
                ->update(['up_money' => $afterMoney]);

            if (!$walletRes) {
                throw new \Exception(lang('loan.error_repay_wallet_fail'));
            }

            $orderRes = \app\admin\model\LoanOrder::where('id', $id)
                ->where('uid', $this->memberInfo['id'])
                ->update([
                    'status' => LoanStatus::FINISH,
                    'repayment_time' => time(),
                    'update_time' => time(),
                ]);

            if (!$orderRes) {
                throw new \Exception(lang('loan.error_repay_order_fail'));
            }

            $logdata = [];
            $logdata['account'] = $repayAmount;
            $logdata['wallet_id'] = $lockWallet['id'];
            $logdata['product_id'] = $baseProduct['id'];
            $logdata['uid'] = $this->memberInfo['id'];
            $logdata['is_test'] = $this->memberInfo['is_test'];
            $logdata['before'] = $beforeMoney;
            $logdata['after'] = $afterMoney;
            $logdata['account_sxf'] = 0;
            $logdata['all_account'] = $repayAmount;
            $logdata['type'] = 14;
            $logdata['title'] = $baseProduct['title'];
            $logdata['remark'] = lang('loan.repay_log_remark');
            $logdata['order_type'] = 1;
            $logdata['order_id'] = $id;

            $logRes = (new \app\admin\model\MemberWalletLog())->save($logdata);
            if (!$logRes) {
                throw new \Exception(lang('loan.error_repay_log_fail'));
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }

        return $this->success(lang('loan.success_repay'), [], (string)url('loan/detail', ['id' => $id]));
    }
}