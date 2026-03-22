<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-06-01 02:13:52
 * @Description: Forward, no stop
 */

namespace app\admin\middleware;

use app\common\service\AuthService;
use think\Request;

/**
 * 检测用户登录和节点权限
 * Class CheckAdmin
 * @package app\admin\middleware
 */
class CheckAdmin
{

    use \app\common\traits\JumpTrait;

    public function handle(Request $request, \Closure $next)
    {
        $adminConfig = config('admin');
        $adminId = session('admin.id');
        $expireTime = session('admin.expire_time');
        /** @var AuthService $authService */
        $authService = app(AuthService::class, ['adminId' => $adminId]);
        $currentNode = $authService->getCurrentNode();
        $currentController = parse_name($request->controller());

        // 验证登录
        if (!in_array($currentController, $adminConfig['no_login_controller']) &&
            !in_array($currentNode, $adminConfig['no_login_node'])) {
            empty($adminId) && $this->error('请先登录后台', [], __url('login/index'));

            // 判断是否登录过期
            if ($expireTime !== true && time() > $expireTime) {
                session('admin', null);
                $this->error('登录已过期，请重新登录', [], __url('login/index'));
            }
        }

        // 验证权限
        if (!in_array($currentController, $adminConfig['no_auth_controller']) &&
            !in_array($currentNode, $adminConfig['no_auth_node'])) {
            $check = $authService->checkNode($currentNode);
            !$check && $this->error('无权限访问');

            // 判断是否为演示环境
            if(env('easyadmin.is_demo', false) && $request->isPost()){
                $this->error('演示环境下不允许修改');
            }

        }

        return $next($request);
    }

}