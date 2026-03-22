<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 22:18:49
 * @LastEditTime: 2021-06-25 22:41:32
 * @Description: Forward, no stop
 */

namespace app\admin\controller\cpm;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="功能：弹窗及轮播")
 */
class Banner extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CpmBanner();
        $this->assign('lang_list',$this->lang_list);
        $cpm_types = \think\facade\Config::get('allset.cpm_types');
        $this->assign('cpm_types', $cpm_types);
        $cpm_names = \think\facade\Config::get('allset.cpm_names');
        $this->assign('cpm_names', $cpm_names);
        
    }

    
}