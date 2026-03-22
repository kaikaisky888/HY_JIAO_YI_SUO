<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-08-21 12:38:49
 * @LastEditTime: 2021-08-21 13:29:52
 * @Description: Forward, no stop
 */
/**
 * 老王
 *
 **/

namespace app\api\middleware;

use app\api\common\JwtAuth;
use think\facade\Request;
use think\Response;
use think\exception\HttpResponseException;

class Api
{
    
    public function handle($request, \Closure $next)
    {
        $apiConfig = config('api');
        $currentController = parse_name($request->controller());
        
        $token = Request::header('token');
        if (!in_array($currentController, $apiConfig['no_jwt_controller'])) {
            if($token){
                if(count(explode('.', $token)) <> 3) {
                    $this->result([], -1, '错误的token身份信息,请重新登录');
                }

                //获取JwtAuth的句柄
                $jwtAuth = JwtAuth::getInstance();
                //设置token
                $jwtAuth->setToken($token);
                //验证token
                if ($jwtAuth->validate() && $jwtAuth->verify()) {
                    return $next($request);
                } else {
                    return $this->result([], -1, '登录身份已过期');
                }
            } else {
                return $this->result([], -1, '请先登录');
            }
        }
        return $next($request);
    }

    protected function result($list,int $code=0,string $msg=''){
    	return json(['code'=>$code,'msg'=>$msg,'data'=>$list]);
	}
}
    