<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-01 16:41:46
 * @LastEditTime: 2021-08-21 13:19:10
 * @Description: Forward, no stop
 */
namespace app\api\controller;

use app\common\controller\ApiController;
use think\App;
use think\facade\Env;

class Index extends ApiController
{
    public function index()
    {
        $this->data['product'] = \app\admin\model\ProductLists::where('status',1)->where('base',0)->where('ishome',1)->order('sort','desc')->select();
        return $this->result($this->data,1,'获取成功');
    }
}

