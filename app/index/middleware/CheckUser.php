<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-07-26 01:03:22
 * @Description: Forward, no stop
 */

namespace app\index\middleware;

use think\Request;

/**
 * 检测用户登录
 * @package app\index\middleware
 */
class CheckUser
{

    use \app\common\traits\JumpTrait;
    
    public function handle(Request $request, \Closure $next)
    {
        $memberConfig = config('member');
        $memberId = session('member.id');
        $expireTime = session('member.expire_time');

        $currentController = parse_name($request->controller());

        // 增加保持？
        if($memberId && $expireTime){
            session('member.expire_time',time() + 7200);
        }

        // 验证登录
        if (!in_array($currentController, $memberConfig['no_login_controller'])) {
            empty($memberId) && $this->error(lang('public.do_login'), [],(string) __url('wicket/login'));

            // 判断是否登录过期
            if ($expireTime !== true && time() > $expireTime) {
                session('member', null);
                $this->error(lang('public.do_login_time'), [], (string)__url('wicket/login'));
            }
        }

        return $next($request);
    }

}