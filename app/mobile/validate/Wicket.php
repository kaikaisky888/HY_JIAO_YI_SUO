<?php 
/*
 * @Author: Fox Blue
 * @Date: 2021-07-08 11:25:17
 * @LastEditTime: 2021-08-17 15:58:46
 * @Description: Forward, no stop
 */
namespace app\mobile\validate;

use think\Validate;

class Wicket extends Validate
{
    protected $rule =   [
        'username'  => 'require|min:4|max:30',
        'password'  => 'require|min:6|max:20',
        'code'  => 'require|min:4|max:6',
        'compassword'  => 'require|confirm:password'
    ];
    
    protected $message  =   [
        'username.require' => 'wicket_page.Validate_username_require',
        'username.min'     => 'wicket_page.Validate_username_min',
        'username.max'     => 'wicket_page.Validate_username_max',
        'password.require' => 'wicket_page.Validate_password_require',
        'password.min'     => 'wicket_page.Validate_password_min',
        'password.max'     => 'wicket_page.Validate_password_max',
        'code.require' => 'wicket_page.Validate_code_require',
        'code.min'     => 'wicket_page.Validate_code_min',
        'code.max'     => 'wicket_page.Validate_code_max',
        'compassword.require' => 'wicket_page.Validate_compassword_require',
        'compassword.confirm' => 'wicket_page.Validate_compassword_confirm',
    ];
    
    protected $scene = [
        'login'  =>  ['username','password'],
        'forget'  =>  ['username','code','password','compassword'],
        'register' => ['username','code','password','compassword'],
        'phoneset' => ['username','code'],
    ];    
}
