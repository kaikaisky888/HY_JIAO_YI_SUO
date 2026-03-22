<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-01 16:41:46
 * @LastEditTime: 2021-07-21 16:29:40
 * @Description: Forward, no stop
 */
namespace app\index\controller;

use app\common\controller\IndexController;
use think\App;
use think\facade\Env;

class Index extends IndexController
{
    protected $lang;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->lang = $this->request->lang ?? 'zh-cn';
    }

    public function index()
    {
        $is_mobile = IndexController::is_mobile();
        if($is_mobile){
            $response = redirect((string)url('/mobile/index'));
            if($response){
                $response->send();
            }
        }
        
        $product = \app\admin\model\ProductLists::where('status',1)->where('base',0)->where('ishome',1)->order('sort','desc')->select();
        $this->assign('product',$product);
        $down_ipa_url = sysconfig('base','down_ipa_url');
        $down_ipa = phpqrcode($down_ipa_url,'down_ipa');
        $down_apk_url = sysconfig('base','down_apk_url');
        $down_apk = phpqrcode($down_apk_url,'down_apk');
        $this->assign(['down_ipa'=>$down_ipa,'down_apk'=>$down_apk,'down_ipa_url'=>$down_ipa_url,'down_apk_url'=>$down_apk_url]);
        
        // 获取公告（从滚屏分类读取，支持多语言）
        $notice = \app\admin\model\LangLists::where('item','news')->where('item_id', 27)->where('lang', $this->lang)->value('title');
        if(!$notice){
            $notice = \app\admin\model\LangLists::where('item','news')->where('item_id', 27)->value('title');
        }
        $this->assign('notice', $notice ? $notice : '');
        
        return $this->fetch();
    }

    public function loginout()
    {
        session('member', null);
        $this->redirect(url('index/index'));
    }
}

