<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-19 23:03:11
 * @LastEditTime: 2021-06-25 22:21:19
 * @Description: Forward, no stop
 */

namespace app\admin\controller\news;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="功能：新闻分类")
 */
class Cate extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\NewsCate();
        
    }

    
}