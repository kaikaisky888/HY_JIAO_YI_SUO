<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:23:18
 * @LastEditTime: 2021-06-25 22:20:31
 * @Description: Forward, no stop
 */

namespace app\admin\controller\member;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="会员：用户组管理")
 */
class Group extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\MemberGroup();
        
    }

    
}