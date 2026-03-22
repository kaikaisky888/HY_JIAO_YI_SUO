<?php

namespace app\common\controller;


use app\BaseController;
use EasyAdmin\tool\CommonTool;
use think\facade\Env;
use think\Model;
use think\exception\ValidateException;
use think\Validate;

/**
 * Class AdminController
 * @package app\common\controller
 */
class ApiController extends BaseController
{
    /**
     * 当前模型
     * @Model
     * @var object
     */
    protected $model;

    /**
     * 字段排序
     * @var array
     */
    protected $sort = [
        'id' => 'desc',
    ];

    /**
     * 允许修改的字段
     * @var array
     */
    protected $allowModifyFields = [
        'status',
        'sort',
        'remark',
        'is_delete',
        'is_auth',
        'title',
    ];

    /**
     * 不导出的字段信息
     * @var array
     */
    protected $noExportFields = ['delete_time', 'update_time'];

    /**
     * 下拉选择条件
     * @var array
     */
    protected $selectWhere = [];

    /**
     * 是否关联查询
     * @var bool
     */
    protected $relationSearch = false;

    /**
     * 模板布局, false取消
     * @var string|bool
     */
    protected $layout = 'layout/default';

    /**
     * 是否为演示环境
     * @var bool
     */
    protected $isDemo = false;

    protected $langJs = false;

    protected $member=[];

    protected $memberInfo;

    protected $allow_lang_list = [];

    protected $web_name;

    protected $data = [];

    protected $api = [
        '\app\api\middleware\Api' => [
            'except' => ['Index']
        ],
    ];
    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();
        $this->allow_lang_list = \think\facade\Config::get('lang.allow_lang_list');
        $data = [];
        if(!$this->lang =$this->app->lang->getLangSet()){
			$lang = \think\facade\Config::get('lang.default_lang');
			$this->app->lang->setLangSet($lang);
			$this->app->lang->saveToCookie($this->app->cookie);
		}
        $memberId = session('member.id');
        if($memberId){
            $this->memberInfo = \app\admin\model\MemberUser::where('id',$memberId)->find();
        }
        $lang_img = \think\facade\Config::get('lang.img_list');
        $langlist = lang('lang_list');
        $this->web_name = sysconfig('site','site_name');
        $this->langJs = file_exists(root_path('public')."static/lang/{$this->lang}.js") ? "/static/lang/{$this->lang}.js" : false;
        $this->data['lang']=$this->lang;
        $this->data['langJs']=$this->langJs;
        $this->data['lang_img']=$lang_img;
        $this->data['langlist']=$langlist;
        $this->data['allow_lang_list']=$this->allow_lang_list;
        $this->data['web_name']=$this->web_name;
        $this->data['topmenu']='home';
    }

    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed $value 变量值
     * @return mixed
     */
    public function assign($name, $value = null)
    {
        return $this->app->view->assign($name, $value);
    }

    /**
     * 解析和获取模板内容 用于输出
     * @param string $template
     * @param array $vars
     * @return mixed
     */
    public function fetch($template = '', $vars = [])
    {
        return $this->app->view->fetch($template, $vars);
    }

    protected function showList($list,int $count=0,int $code=0,string $msg=''){
    	return ['code'=>$code,'msg'=>$msg,'count'=>$count,'data'=>$list];
	}

	protected function pagesList($list,float $pages=0,int $code=0,string $msg=''){
    	return json(['code'=>$code,'msg'=>$msg,'pages'=>$pages,'data'=>$list]);
	}

	protected function klineList($data,$nodata,int $code=0,string $msg=''){
    	return json(['code'=>$code,'data'=>$data,'nodata'=>$nodata,'msg'=>$msg]);
	}

    protected function result($list,int $code=0,string $msg=''){
    	return json(['code'=>$code,'msg'=>$msg,'data'=>$list]);
	}

}