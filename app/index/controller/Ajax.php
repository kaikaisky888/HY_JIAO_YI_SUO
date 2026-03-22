<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-02 11:57:21
 * @LastEditTime: 2021-07-17 17:05:42
 * @Description: Forward, no stop
 */

namespace app\index\controller;

use app\common\controller\IndexController;
use app\index\service\TriggerService;
use think\db\Query;
use think\facade\Cache;
use app\admin\model\SystemUploadfile;
use EasyAdmin\upload\Uploadfile;
use app\common\FoxKline;

class Ajax extends IndexController
{

    /**
     * @Title: 验证会员
     */    
    public function check_member(){
        if(!session('member')){
            return $this->error(lang('public.do_login'));
        }
    }
    
    

    /**
     * 切换语言包
     */
    public function lang()
    {
        $lang = $this->request->param('lang');
        if (!empty($lang) && in_array($lang, $this->allow_lang_list)) {
            TriggerService::setLang($lang);
            return json(['code'=>1]);
		}
        return json(['code'=>0]);
    }

    public function theme()
    {
        $theme = $this->request->param('theme');
        if (!empty($theme)) {
            TriggerService::updateTheme($theme);
            return json(['code'=>1]);
		}
        return json(['code'=>0]);
    }

    public function get_product()
    {
        if(request()->isPost()){
            $code = request()->post('code/s','',"trim");
            $pages = request()->post('pages/s','',"trim");
            $cate_id = request()->post('cate_id/d',0);
            if($code){
                // 根据cate_id精确查询
                $query = \app\admin\model\ProductLists::where('code',$code)->where('status',1);
                if($cate_id > 0){
                    // 如果传了cate_id，精确匹配
                    $pro = $query->where('cate_id',$cate_id)->field('title,cate_id,open,close,high,low,change,volume')->find();
                }else{
                    $pro = $query->field('title,cate_id,open,close,high,low,change,volume')->find();
                }
                if($pro){
                    $pro['open'] = (float)$pro['open'];
                    $pro['close'] = (float)$pro['close'];
                    $pro['change'] = (float)$pro['change'];
                    $pro['high'] = (float)$pro['high'];
                    $pro['low'] = (float)$pro['low'];
                    $pro['vol'] = number_format($pro['volume'],4);
                    $pro['volume'] = number_format($pro['volume'],4);
                    $pro['usd'] = FoxKline::get_me_price_usdt_to_usd($pro['close']);
                    return json(['code'=>1,'data'=>$pro]);
                }
                return json(['code'=>0]);
            }
        }
        return json(['code'=>0]);
    }
    
}