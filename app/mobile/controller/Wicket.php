<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2021-10-06 19:18:01
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use think\App;
use think\facade\Env;
use think\facade\Session;
use app\common\FoxCommon;

class Wicket extends MobileController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\MemberUser();
    }

    public function check_member(){
        if(session('member')){
            $this->redirect(url('index/index'));
        }
    }
    
    public function register()
    {
        $this->check_member();
        $reg=request()->get('reg/s','',"trim");
        $this->assign(['reg'=>$reg]);
        $incode=request()->get('code/s','',"/^[a-zA-Z0-9]+$/u");
        if(!$incode){
            $incode = Session::has('incode')?Session::get('incode'):sysconfig('base','invite_code');
        }else{
            Session::set('incode', $incode);
        }
        $this->assign(['incode'=>$incode]);
        $web_name = lang('public_memu.register').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name]);
        $prefix_code = \think\facade\Config::get('phone.prefix_code');
        $this->assign(['prefix_code'=>$prefix_code]);
        if(request()->isPost()){
            $data=request()->post();
            $agree=(isset($data['agree']) && $data['agree']=="on")?'1':'';
            if(!$agree){
                return $this->error(lang('wicket_page.Validate_agree'));
            }
            
            try {
                $this->validate($data,'Wicket.register');
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
            $username = $data['username'];
            $code = $data['code'];
            $incode = $data['incode'];
            $password = $data['password'];
            $prefix = $data['prefix'];
            $users = \app\admin\model\MemberUser::where(['username'=>$username])->find();
            if($users){
                return $this->error(lang('wicket_page.Validate_member_isuser'));
            }
            $codes = \app\admin\model\SystemCode::where(['phone'=>$username,'code'=>$code,'useable'=>0])->order('create_time','desc')->limit(1)->select()->ToArray();
            if(!$codes){
                return $this->error(lang('wicket_page.Validate_code_isnot'));
            }
            $is_member = \app\admin\model\MemberUser::where(['invite_code'=>$incode])->field('id,level_ids,holder_id,admin_id')->find();
            if(!$is_member){
                return $this->error(lang('wicket_page.Validate_member_isnot'));
            }else{
                $indata['level_id'] = $is_member['id'];
                if($is_member['admin_id'] > 0){
                    $indata['level_ids'] = $is_member['level_ids'].','.$is_member['admin_id'];
                }else{
                    $indata['level_ids'] = $is_member['level_ids'];
                }
                $indata['holder_id'] = $is_member['holder_id'];
            }
            $indata['username'] = $username;
            $indata['prefix'] = $prefix;
            $indata['password'] = password($password);
            $indata['group_id'] = sysconfig('base','member_group');
            $indata['head_img'] = sysconfig('base','member_avatar');
            $checkmail = "/^([\.a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/";//定义正则表达式
            if(preg_match($checkmail,$username)){
                $indata['email'] = $username;
            }else{
                $indata['phone'] = $username;
                $indata['phone_time'] = time();
            }
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $save = $this->model->save($indata);
                $lastId = $this->model->id;
                $invite_code =FoxCommon::only_invite_code($lastId);
                $this->model->update(['invite_code'=>$invite_code,'id'=>$lastId]);
                \app\admin\model\SystemCode::update(['useable'=>1],['id'=>$codes[0]['id']]);
                FoxCommon::check_member_wallet($lastId);
            } catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save){
                unset($indata['password']);
                $member = $indata;
                $member['id'] = $lastId;
                $member['expire_time'] = time() + 7200;
                session('member', $member);
                return $this->success(lang('wicket_page.register_member_ok'),[],(string)url('index/index'));
            }
            return $this->error(lang('public.do_fail'));
        }
        return $this->fetch();
    }

    public function login()
    {
        $this->check_member();
        $web_name = lang('public_memu.login').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name]);
        if(request()->isPost()){
            $data=request()->post();
            try {
                $this->validate($data,'Wicket.login');
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
            $username = $data['username'];
            $password = $data['password'];
            $users = \app\admin\model\MemberUser::where(['username'=>$username])->find();
            if(empty($users)){
                return $this->error(lang('wicket_page.Validate_member_nouser'));
            }
            if (password($password) != $users->password) {
                $this->error(lang('wicket_page.Validate_login_passerr'));
            }
            if ($users->status == 0) {
                return $this->error(lang('wicket_page.Validate_member_statusno'));
            }
            
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            $users->login_num += 1;
            $users->save();
            $users = $users->toArray();
            FoxCommon::check_member_wallet($users['id']);
            unset($users['password']);
            $users['expire_time'] = time() + 7200;
            session('member', $users);
            return $this->success(lang('wicket_page.login_member_ok'),[],(string)url('index/index'));
        }
        return $this->fetch();
    }

    public function forget()
    {
        $this->check_member();
        $web_name = lang('public_memu.forget').'-'.$this->web_name;
        $this->assign(['web_name'=>$web_name]);
        $prefix_code = \think\facade\Config::get('phone.prefix_code');
        $this->assign(['prefix_code'=>$prefix_code]);
        if(request()->isPost()){
            $data=request()->post();
            try {
                $this->validate($data,'Wicket.register');
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
            $username = $data['username'];
            $code = $data['code'];
            $password = $data['password'];
            $prefix = $data['prefix'];
            $users = \app\admin\model\MemberUser::where(['username'=>$username])->find();
            if(empty($users)){
                return $this->error(lang('wicket_page.Validate_member_nouser'));
            }
            if ($users->status == 0) {
                return $this->error(lang('wicket_page.Validate_member_statusno'));
            }
            $codes = \app\admin\model\SystemCode::where(['phone'=>$username,'code'=>$code,'useable'=>0])->order('create_time','desc')->limit(1)->select()->ToArray();
            if(!$codes){
                return $this->error(lang('wicket_page.Validate_code_isnot'));
            }
            $indata['prefix'] = $prefix;
            $indata['password'] = password($password);
            $check = request()->checkToken('__token__');
            if(false === $check) {
                return $this->error(lang('public.do_fail'));
            }
            try {
                $save = $this->model->update($indata,['id'=>$users['id']]);
                \app\admin\model\SystemCode::update(['useable'=>1],['id'=>$codes[0]['id']]);
            } catch (\Exception $e) {
                return $this->error(lang('public.do_fail'));
            }
            if($save){
                unset($indata['password']);
                $member = $indata;
                $member['expire_time'] = time() + 7200;
                session('member', $member);
                return $this->success(lang('wicket_page.forget_member_ok'),[],(string)url('wicket/login'));
            }
            return $this->error(lang('public.do_fail'));
        }
        return $this->fetch();
    }

    public function getcode()
    {
        
        if(request()->isPost()){
            $data=request()->post();
            $tousername = $data['username'];
            $prefix = $data['prefix'];
            $checkmail = "/^([\.a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/";//定义正则表达式
            $sms_send_count = sysconfig('base','send_count');
            $count=\app\admin\model\SystemCode::where(['phone'=>$tousername,'useable'=>0])->count('id');
            if($sms_send_count > 0 && $count > $sms_send_count){
                return $this->error(fox_all_replace(lang('wicket_page.code_send_num'),$count));
            }
            //郵箱或手機
            if(preg_match($checkmail,$tousername)){	
                $code = rand(1000,9999);
                $title = lang('send_email.title');
                $content = fox_all_replace(lang('send_email.content'), $code);
                if($tousername && $title && $content){
                    $cdata['phone'] = $tousername;
                    $cdata['code'] = $code;
                    $cdata['useable'] = 0;
                    $cdata['ip'] = getRealIp();
                    if(sysconfig('site','site_type')=='online'){

                        if(FoxCommon::sendMail($tousername,$title,$content)){
                            \app\admin\model\SystemCode::create($cdata);
                            return $this->success(lang('send_email.send_ok'));
                        }else{
                            return $this->success(lang('send_email.send_no'));
                        }
                    }else{
                        \app\admin\model\SystemCode::create($cdata);
                        return $this->success(lang('send_email.send_ok'));
                    }
                }
            }else{
                if(!$prefix){
                    return $this->success(lang('send_email.send_prefix_no'));
                }
                $cdata['phone'] = $tousername;
                $cdata['code'] = rand(1000,9999);
                $cdata['useable'] = 0;
                $cdata['ip'] = getRealIp();
                if(sysconfig('site','site_type')=='online'){
                    if(FoxCommon::feige_send($cdata['code'],$cdata['phone'],$prefix)){
                        \app\admin\model\SystemCode::create($cdata);
                        return $this->success(lang('send_email.sendsms_ok'));
                    }else{
                        return $this->success(lang('send_email.sendsms_no'));
                    }
                }else{
                    \app\admin\model\SystemCode::create($cdata);
                    return $this->success(lang('send_email.sendsms_ok'));
                }
            }
        }
    }

    public function getcodes()
    {
        if(request()->isPost()){
            $data=request()->post();
            $tousername = $data['username'];
            $prefix = $data['prefix'];
            $users = \app\admin\model\MemberUser::where(['username'=>$tousername])->find();
            if(empty($users)){
                return $this->error(lang('wicket_page.Validate_member_nouser'));
            }
            if ($users->status == 0) {
                return $this->error(lang('wicket_page.Validate_member_statusno'));
            }
            $checkmail = "/^([\.a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/";//定义正则表达式
            $sms_send_count = sysconfig('base','send_count');
            $count=\app\admin\model\SystemCode::where(['phone'=>$tousername,'useable'=>0])->count('id');
            if($sms_send_count > 0 && $count > $sms_send_count){
                return $this->error(fox_all_replace(lang('wicket_page.code_send_num'),$count));
            }
            //郵箱或手機
            if(preg_match($checkmail,$tousername)){	
                $code = rand(1000,9999);
                $title = lang('send_email.title');
                $content = fox_all_replace(lang('send_email.content'), $code);
                if($tousername && $title && $content){
                    $cdata['phone'] = $tousername;
                    $cdata['code'] = $code;
                    $cdata['useable'] = 0;
                    $cdata['ip'] = getRealIp();
                    if(sysconfig('site','site_type')=='online'){
                        if(FoxCommon::sendMail($tousername,$title,$content)){
                            \app\admin\model\SystemCode::create($cdata);
                            return $this->success(lang('send_email.send_ok'));
                        }else{
                            return $this->success(lang('send_email.send_no'));
                        }
                    }else{
                        \app\admin\model\SystemCode::create($cdata);
                        return $this->success(lang('send_email.send_ok'));
                    }
                }
            }else{
                if(!$prefix){
                    return $this->success(lang('send_email.send_prefix_no'));
                }
                $cdata['phone'] = $tousername;
                $cdata['code'] = rand(1000,9999);
                $cdata['useable'] = 0;
                $cdata['ip'] = getRealIp();
                if(sysconfig('site','site_type')=='online'){
                    if(FoxCommon::feige_send($cdata['code'],$cdata['phone'],$prefix)){
                        \app\admin\model\SystemCode::create($cdata);
                        return $this->success(lang('send_email.sendsms_ok'));
                    }else{
                        return $this->success(lang('send_email.sendsms_no'));
                    }
                }else{
                    \app\admin\model\SystemCode::create($cdata);
                    return $this->success(lang('send_email.sendsms_ok'));
                }
            }
        }
    }

}