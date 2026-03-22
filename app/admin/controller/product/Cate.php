<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 23:20:04
 * @LastEditTime: 2021-06-25 22:21:33
 * @Description: Forward, no stop
 */

namespace app\admin\controller\product;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="功能：产品分类管理")
 */
class Cate extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\ProductCate();
        
    }

    
}