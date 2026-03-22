<?php

namespace app\admin\controller;

use app\admin\model\SystemAdmin;
use app\admin\model\MemberUser;
use app\common\controller\AdminController;
use think\App;
use think\facade\Env;
use think\facade\Db;

class Index extends AdminController
{

    /**
     * 后台主页
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        return $this->fetch('', [
            'admin' => session('admin'),
        ]);
    }

    /**
     * 后台欢迎页
     * @return string
     * @throws \Exception
     */
    public function welcome()
    {
        $gcount = 0;
        $ucount = 0;
        $bcount = 0;
        if(session('admin.is_team')==0){
            $gcount = \app\admin\model\SystemAdmin::where('is_team',1)->where('holder_id',0)->count('id');
            $ucount = \app\admin\model\MemberUser::where('admin_id',0)->count('id');
            $bcount = \app\admin\model\MemberUser::where('admin_id','>',0)->count('id');
        }elseif(session('admin.is_team')==1 && session('admin.member_id') == 0){
            $gcount = \app\admin\model\SystemAdmin::where('is_team',1)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->count('id');
            $ucount = \app\admin\model\MemberUser::where('admin_id',0)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->count('id');
            $bcount = \app\admin\model\MemberUser::where('admin_id','>',0)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->count('id');
        }elseif(session('admin.is_team')==1 && session('admin.member_id') > 0){
            $gcount = \app\admin\model\SystemAdmin::where('is_team',1)->where('holder_id',0)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->count('id');
            $ucount = \app\admin\model\MemberUser::where('admin_id',0)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->count('id');
            $bcount = \app\admin\model\MemberUser::where('admin_id','>',0)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->count('id');
        }
        $this->assign(['gcount'=>$gcount,'ucount'=>$ucount,'bcount'=>$bcount]);
        $this->assign('admin', session('admin'));
        return $this->fetch();
    }

    /**
     * 修改管理员信息
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editAdmin()
    {
        $id = session('admin.id');
        $row = (new SystemAdmin())
            ->withoutField('password')
            ->find($id);
        empty($row) && $this->error('用户信息不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $this->isDemo && $this->error('演示环境下不允许修改');
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $row
                    ->allowField(['head_img', 'phone', 'remark', 'update_time'])
                    ->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * 修改密码
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editPassword()
    {
        $id = session('admin.id');
        $row = (new SystemAdmin())
            ->withoutField('password')
            ->find($id);
        if (!$row) {
            $this->error('用户信息不存在');
        }
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $this->isDemo && $this->error('演示环境下不允许修改');
            $rule = [
                'password|登录密码'       => 'require',
                'password_again|确认密码' => 'require',
            ];
            $this->validate($post, $rule);
            if ($post['password'] != $post['password_again']) {
                $this->error('两次密码输入不一致');
            }

            // 判断是否为演示站点
            $example = Env::get('easyadmin.example', 0);
            $example == 1 && $this->error('演示站点不允许修改密码');

            try {
                $save = $row->save([
                    'password' => password($post['password']),
                ]);
                if($row['member_id'] > 0){
                    $modelu = new MemberUser();
                    $modelu->update(['password' => password($post['password']),'id'=>$row['member_id']]);
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
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * 检测新通知（充值/订单）
     * @return \think\response\Json
     */
    public function checkNewNotify()
    {
        // 获取上次检查时间
        $lastCheck = session('last_notify_check') ?: time();
        $currentTime = time();
        
        // 更新检查时间
        session('last_notify_check', $currentTime);
        
        $newRecharge = 0;
        $newWithdraw = 0;  // 提现申请
        $newKyc = 0;       // 实名认证
        $newSeconds = 0;   // 期权订单
        $newLeverdeal = 0; // 合约订单
        $newDeal = 0;      // 币币订单
        
        // 根据管理员权限查询
        $adminInfo = session('admin');
        $timeCondition = $lastCheck - 30;  // 最近30秒内的新记录
        
        // 检查新充值申请（处理中状态 status=1）
        $rechargeQuery = Db::name('member_wallet_log')
            ->where('type', 1)  // 充值
            ->where('status', 1)  // 处理中
            ->where('create_time', '>', $timeCondition);
        
        // 检查新提现申请（处理中状态 status=1）
        $withdrawQuery = Db::name('member_wallet_log')
            ->where('type', 2)  // 提现
            ->where('status', 1)  // 处理中
            ->where('create_time', '>', $timeCondition);
        
        // 检查新实名认证申请（待审核状态 status=0）
        $kycQuery = Db::name('member_card')
            ->where('status', 0)  // 待审核（身份证审核保持0）
            ->where('create_time', '>', $timeCondition);
        
        // 检查新期权订单
        $secondsQuery = Db::name('order_seconds')
            ->where('create_time', '>', $timeCondition);
        
        // 检查新合约订单
        $leverdealQuery = Db::name('order_leverdeal')
            ->where('create_time', '>', $timeCondition);
        
        // 检查新币币订单
        $dealQuery = Db::name('order_deal')
            ->where('create_time', '>', $timeCondition);

        // 检查新高级认证申请（待审核状态 advanced_status=1）
        $advancedKycQuery = Db::name('member_card')
            ->where('advanced_status', 1)
            ->where('advanced_time', '>', $timeCondition);
        
        // 如果是团队管理员，只查看其权限范围内的数据
        if ($adminInfo['is_team'] == 1) {
            $teamFilter = 'FIND_IN_SET(' . $adminInfo['id'] . ', (SELECT level_ids FROM fox_member_user WHERE id = user_id))';
            $rechargeQuery->whereRaw($teamFilter);
            $withdrawQuery->whereRaw($teamFilter);
            $secondsQuery->whereRaw($teamFilter);
            $leverdealQuery->whereRaw($teamFilter);
            $dealQuery->whereRaw($teamFilter);
        }
        
        $newRecharge = $rechargeQuery->count();
        $newWithdraw = $withdrawQuery->count();
        $newKyc = $kycQuery->count();
        $newSeconds = $secondsQuery->count();
        $newSeconds = 0;
        $newLeverdeal = $leverdealQuery->count();
        $newDeal = $dealQuery->count();
        
        $totalOrders = $newSeconds + $newLeverdeal + $newDeal;
        $newAdvancedKyc = $advancedKycQuery->count();
        
        return json([
            'code' => 0,
            'data' => [
                'newRecharge' => $newRecharge,
                'newWithdraw' => $newWithdraw,    // 提现申请
                'newKyc' => $newKyc,              // 实名认证
                'newAdvancedKyc' => $newAdvancedKyc,
                'newSeconds' => $newSeconds,      // 期权订单
                'newLeverdeal' => $newLeverdeal,  // 合约订单
                'newDeal' => $newDeal,            // 币币订单
                'totalOrders' => $totalOrders,
                'hasNew' => ($newRecharge > 0 || $newWithdraw > 0 || $newKyc > 0 || $totalOrders > 0)
            ]
        ]);
    }

}
