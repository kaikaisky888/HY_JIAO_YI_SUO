<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-23 15:46:35
 * @LastEditTime: 2021-08-07 04:35:39
 * @Description: Forward, no stop
 */

namespace app\admin\controller\member;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="会员：分组配置")
 */
class Config extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'group' => 'desc',
        'sort'   => 'asc',
        'id'   => 'desc',
    ];

    protected $allowModifyFields = [
        'value', 'remark', 'sort'
    ];
    
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\MemberConfig();
        $member_groups = \think\facade\Config::get('allset.member_groups');
        $this->assign('member_groups', $member_groups);
        
    }

    
}