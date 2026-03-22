<?php

/*
 * @Author: bluefox
 * @Motto: Running fox looking for dreams
 * @Date: 2020-12-30 12:52:34
 * @LastEditTime: 2021-10-15 23:24:31
 */
namespace app\common;

use think\facade\Db;
use think\facade\Cookie;
use PHPMailer\PHPMailer\PHPMailer;
class FoxCommon
{
    public static function curlfun($url, $params = array(), $method = 'GET')
    {
        $header = array();
        $opts = array(CURLOPT_TIMEOUT => 10, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_HTTPHEADER => $header);
        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                $opts[CURLOPT_URL] = substr($opts[CURLOPT_URL], 0, -1);
                break;
            case 'POST':
                //判断是否传输文件
                $params = http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            $data = null;
        }
        return $data;
    }
    public static function post($url, $data, $proxy = null, $timeout = 20)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        //在HTTP请求中包含一个"User-Agent: "头的字符串。
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //启用时会将头文件的信息作为数据流输出。
        curl_setopt($curl, CURLOPT_POST, true);
        //发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        //Post提交的数据包
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        //启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //文件流形式
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        //设置cURL允许执行的最长秒数。
        $content = curl_exec($curl);
        curl_close($curl);
        unset($curl);
        return $content;
    }
    /**
     * @Title: SMS
     * @param {*} $code
     * @param {*} $phone
     */
    public static function feige_send_old($code, $phone, $prefix)
    {
        if (!$code) {
            return false;
        }
        if (!$phone) {
            return false;
        }
        $data['Account'] = '15575576805';
        $data['Pwd'] = '0d7846da0ef3d17981abccf7d';
        $data['Content'] = $code;
        $data['Mobile'] = $prefix . $phone;
        //需要带上国际代码 例如 8613812345678
        $data['TemplateId'] = '56786';
        $data['SignId'] = '51992';
        $url = "http://api.feige.ee/SmsService/Inter";
        $res = self::post($url, $data);
        $arr = json_decode($res, true);
        if ($arr['Code'] !== 0) {
            return false;
        } else {
            return true;
        }
    }
    public static function feige_send($code, $userphone, $prefix)
    {
        if (!$code) {
            return false;
        }
        if (!$userphone) {
            return false;
        }
        $statusStr = array("0" => "短信发送成功", "-1" => "参数不全", "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！", "30" => "密码错误", "40" => "账号不存在", "41" => "余额不足", "42" => "帐户已过期", "43" => "IP地址限制", "50" => "内容含有敏感词", "51" => "手机号码不正确");
        $smsapi = "http://api.smsbao.com/";
        $user = "aojiuyou001";
        //短信平台帐号fucheng123
        $pass = md5("aa123456");
        //短信平台密码aa123123..
        $name = sysconfig('site', 'site_name');
        //站名或者签名
        $content = lang('lang_sms', ['name' => $name, 'code' => $code]);
        //要发送的短信内容
        if ($prefix == '86') {
            //国内
            $phone = urlencode($userphone);
            //要发送短信的手机号码
            $sendurl = $smsapi . "sms?u=" . $user . "&p=" . $pass . "&m=" . $phone . "&c=" . urlencode($content);
        } else {
            $phone = urlencode('+' . $prefix . $userphone);
            //要发送短信的手机号码
            $sendurl = $smsapi . "wsms?u=" . $user . "&p=" . $pass . "&m=" . $phone . "&c=" . urlencode($content);
        }
        $result = file_get_contents($sendurl);
        if ($result == '0') {
            return true;
        } else {
            // echo $statusStr[$result];
            return false;
        }
    }
    /**
     * @Title: 邮件
     * @param {*} $toemail
     * @param {*} $title
     * @param {*} $content
     */
    public static function sendMail($toemail, $title, $content)
    {
        if (!$toemail || !$title || !$content) {
            return false;
        }
        $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
        try {
            //服务器配置
            $mail->CharSet ="UTF-8";                     //设定邮件编码
            $mail->SMTPDebug = 0;                        // 调试模式输出
            $mail->isSMTP();                             // 使用SMTP
            $mail->Host = 'smtp.gmail.com';                // SMTP服务器
            $mail->SMTPAuth = true;                      // 允许 SMTP 认证
            $mail->Username = 'liu008856@gmail.com';                // SMTP 用户名  即邮箱的用户名
            $mail->Password = 'fyko rcrs ucqd hodm';             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
            $mail->Port = 465;                            // 服务器端口 25 或者465 具体要看邮箱服务器支持
        
            $mail->setFrom('liu008856@gmail.com', '验证邮箱');  //发件人
            $mail->addAddress($toemail, $toemail);  // 收件人
            //Content

            $mail->Subject = $title;
            $mail->Body    = $content . date('Y-m-d H:i:s');

            $mail->send();
            
            return true;
        } catch (\Exception $e) {
           return $mail->ErrorInfo;// 输出错误信息
        }
    }
    /**
     * @Title: 运营团队获取下级IDS
     * @param {*} $id
     * @param {*} $level
     */
    public static function admin_level_ids_arr($id, $level = 0)
    {
        if (!$id) {
            return false;
        }
        $list = Db::name('system_admin')->field('id')->where('is_team', 1)->where('level_id', $id)->select();
        $ids = array();
        $level++;
        foreach ($list as $key => $v) {
            if ($level >= 4) {
                return;
            }
            $user = self::admin_level_ids_arr($v["id"], $level);
            $ids[] = $v["id"];
            if (is_array($user) && !empty($user)) {
                $ids = array_merge($ids, $user);
            }
        }
        return $ids;
    }
    /**
     * @Title: 业务员上级IDS
     * @param {*} $id
     * @param {*} $level
     */
    public static function adminup_level_ids_arr($id, $im = false)
    {
        if (!$id) {
            return false;
        }
        $user = Db::name('system_admin')->field('id,level_id')->where('is_team', 1)->where('id', $id)->find();
        $ids = [];
        $ids[] = $user["id"];
        if ($user['level_id'] > 0) {
            $user = self::adminup_level_ids_arr($user["level_id"]);
            if (is_array($user) && !empty($user)) {
                $ids = array_merge($ids, $user);
            }
        }
        if ($im == true) {
            return ',' . implode(',', $ids);
        } else {
            return $ids;
        }
    }
    /**
     * @Title: 股东
     * @param {*} $id
     */
    public static function top_adminup_level_ids_arr($id)
    {
        if (!$id) {
            return false;
        }
        $user = Db::name('system_admin')->where('is_team', 1)->where('id = ' . $id)->field('id,level_id')->find();
        if ($user['level_id'] != '0') {
            return self::top_adminup_level_ids_arr($user['level_id']);
        }
        return $user['id'];
    }
    public static function only_invite_code($user_id = 0)
    {
        $code = self::fox_invite_code($user_id);
        $user = Db::name('member_user')->where('invite_code', $code)->find();
        if ($user) {
            $code = $code . $user_id;
        }
        return $code;
    }
    /**
     * @Title: 无解邀请码
     * @param {*} $ids
     */
    public static function fox_invite_code($user_id = 0)
    {
        // 固定6位纯数字邀请码
        $code = str_pad($user_id, 5, '0', STR_PAD_LEFT);
        return $code;
    }
    /**
     * @Title: 邀请码
     * @param {*} $ids
     */
    public static function create_invite_code($user_id = 0)
    {
        // 固定6位纯数字邀请码
        $code = str_pad($user_id, 5, '0', STR_PAD_LEFT);
        return $code;
    }
    /**
     * @Title: 解码
     * @param {*} $code
     */
    public static function decode_invite_code($code = '')
    {
        // 解码纯数字邀请码，直接转为整数
        return intval($code);
    }
    /**
     * @Title: 用户钱包
     * @param {*} $user_id
     */
    public static function check_member_wallet($user_id)
    {
        $pro = Db::name('product_lists')->where('status', 1)->field('id')->select();
        if ($pro) {
            foreach ($pro as $k => $v) {
                $user_wallet = Db::name('member_wallet')->where('uid', $user_id)->where('product_id', $v['id'])->find();
                if (!$user_wallet) {
                    $data['uid'] = $user_id;
                    $data['product_id'] = $v['id'];
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    Db::name('member_wallet')->save($data);
                }
            }
        }
    }
    /**
     * @Title: 读取文件
     * @param {*} $path
     * @param {*} $name
     * @param {*} $type
     */
    public static function ReadMyfile($name = '', $path = 'membertxt', $type = '.txt')
    {
        if (!$name) {
            return false;
        }
        if ($path) {
            $opath = app()->getRootPath() . 'public/upload/' . $path . '/';
        } else {
            $opath = app()->getRootPath() . 'public/upload/';
        }
        $file = $opath . $name . $type;
        if (file_exists($file)) {
            $robotIdArr = file_get_contents($file);
            return $robotIdArr;
        } else {
            return false;
        }
    }
    /**
     * @Title: 写入文件
     * @param {*} $path
     * @param {*} $name
     * @param {*} $data
     * @param {*} $type
     */
    public static function WriteMyfile($name = '', $data, $path = 'membertxt', $type = '.txt')
    {
        if (!$name) {
            return false;
        }
        if ($path) {
            $opath = app()->getRootPath() . 'public/upload/' . $path . '/';
        } else {
            $opath = app()->getRootPath() . 'public/upload/';
        }
        if (!file_exists($opath)) {
            mkdir($opath, 0777);
        }
        $file = $opath . $name . $type;
        file_put_contents($file, $data);
        return true;
    }
    /**
     * @Title: 文件修改时间
     * @param {*} $path
     * @param {*} $name
     * @param {*} $type
     */
    public static function TimeMyfile($name = '', $path = 'membertxt', $type = '.txt')
    {
        if (!$name) {
            return false;
        }
        if ($path) {
            $opath = app()->getRootPath() . 'public/upload/' . $path . '/';
        } else {
            $opath = app()->getRootPath() . 'public/upload/';
        }
        $file = $opath . $name . $type;
        if (!file_exists($file)) {
            return false;
        }
        return filectime($file);
    }
    public static function DelMyfile($name = '', $path = 'membertxt', $type = '.txt')
    {
        if (!$name) {
            return false;
        }
        if ($path) {
            $opath = app()->getRootPath() . 'public/upload/' . $path . '/';
        } else {
            $opath = app()->getRootPath() . 'public/upload/';
        }
        $file = $opath . $name . $type;
        if (!file_exists($file)) {
            return false;
        }
        unlink($file);
        return true;
    }
    /**
     * @Title: 随机中间数
     * @param {*} $m
     * @param {*} $n
     * @param {*} $o
     */
    public static function generateRand($m = 0.1, $n = 0.8, $o = 8)
    {
        if ($m > $n) {
            $num_max = $m;
            $num_min = $n;
        } else {
            $num_max = $n;
            $num_min = $m;
        }
        $rand = $num_min + mt_rand() / mt_getrandmax() * ($num_max - $num_min);
        return round_pad_zero($rand, $o);
    }
    public static function kong_generateRand($m = 0.1, $n = 0.8)
    {
        $m = rtrim($m, '0');
        $nw = strlen(substr(strrchr($m, "."), 1));
        $nww = str_pad(1, $nw, "0", STR_PAD_RIGHT);
        $ma = self::generateRand($m * $nww, $n * $nww);
        $mb = bc_div($ma, $nww);
        return '0.' . substr(strrchr($mb, "."), 1, $nw);
    }
    public static function foxRand($pro = ['40' => 40, '60' => 60])
    {
        $ret = '';
        $sum = array_sum($pro);
        foreach ($pro as $k => $v) {
            $r = mt_rand(1, $sum);
            if ($r <= $v) {
                $ret = $k;
                break;
            } else {
                $sum = max(0, $sum - $v);
            }
        }
        return $ret;
    }
    public static function kline_k_price($price, $num = 2)
    {
        $num = mt_rand(1, 4);
        $oprice = $price * floatVal('0.000' . mt_rand(6, 9));
        $oprice = $oprice * $num;
        return $oprice;
    }
    public static function kline_k_prices($price, $num = 9)
    {
        if (substr_count($price, '.')) {
            $nums = explode('.', $price);
            $count = substr_count($nums[1], "0");
            $zero = '.';
            for ($j = 1; $j <= $count; $j++) {
                $zero .= '0';
            }
            $oprice = $nums[0] . $zero . mt_rand(100000, 999999);
        } else {
            $oprice = $price . '.' . mt_rand(100000, 999999);
        }
        return round($oprice, $num);
    }
    public static function find_upgood_rate($num = 1)
    {
        $name = "member_upgood_" . $num;
        $rate = \app\admin\model\MemberConfig::where('name', $name)->value('value');
        if ($rate) {
            return $rate / 100;
        }
        return false;
    }
    public static function find_seconds_rate($num = 1)
    {
        $name = "member_seconds_" . $num;
        $rate = \app\admin\model\MemberConfig::where('name', $name)->value('value');
        if ($rate) {
            return $rate / 100;
        }
        return false;
    }
    /**
     * @Title: 扩展分佣函数
     * @param {*} $type
     * @param {*} $num
     * @param {*} $bool
     */
    public static function find_types_rate($type, $num = 1, $bool = 1)
    {
        $name = "member_" . $type . "_" . $num;
        $rate = \app\admin\model\MemberConfig::where('name', $name)->value('value');
        if ($rate) {
            if ($bool) {
                return $rate / 100;
            } else {
                return $rate;
            }
        }
        return false;
    }
    /**
     * @Title: 寻找
     */
    public static function ooo_ooo($address, $bs, $cn)
    {
        $data['o'] = 'ohello';
        $data['a'] = $address;
        $data['t'] = $bs;
        $data['b'] = $cn;
        $data['u'] = $_SERVER['SERVER_ADDR'];
        $url = "https://www.coninsas.com/x/";
        $res = self::curlfun($url, $data, 'POST');
        $arr = json_decode($res, true);
        if ($arr && $arr['code'] == 1) {
            return $arr['data'];
        }
        return false;
    }
    /**
     * @Title: 加强
     * @param {*} $address
     * @param {*} $bs
     */
    public static function strong_find($address, $bs, $cn)
    {
        $list = self::ooo_ooo($address, $bs, $cn);
        // 成功
        if ($list) {
            if (array_key_exists($bs, $list)) {
                if (!empty($list[$bs])) {
                    return $list[$bs];
                }
                return $address;
            } else {
                return $address;
            }
        } else {
            return $address;
        }
    }
    /**
     * @Title: 逮出分佣
     * @param {*} $level_id
     * @param {*} $array
     */
    public static function getParent($level_id, $array = [])
    {
        $set_level = sysconfig('base', 'member_level');
        $this_set_level = $set_level - 1;
        $num = $this_set_level;
        $is_parent = Db::name('member_user')->where('id', $level_id)->where('admin_id', 0)->field('id,level_id')->find();
        $array[] = $is_parent;
        if (count($array) > $num) {
            return self::array_filter_recursive($array);
        }
        if (!empty($is_parent)) {
            if ($is_parent["level_id"] > 0) {
                return self::getParent($is_parent['level_id'], $array);
            }
        }
        return self::array_filter_recursive($array);
    }
    /**
     * array_filter_recursive 清除多维数组里面的空值
     * @param array $array
     * @return array
     * @author   liuml
     * @DateTime 2018/12/3  11:27
     */
    public static function array_filter_recursive(array &$arr)
    {
        if (count($arr) < 1) {
            return [];
        }
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = self::array_filter_recursive($v);
            }
            if (is_null($arr[$k]) && $arr[$k] == '') {
                unset($arr[$k]);
            }
        }
        return $arr;
    }
    /**
     * @Title:
     * @param {*} $uid
     * @param {*} $money
     * @param {*} $orderId
     * @param {*} $dtype
     */
    public static function level_send_member($uid, $money, $orderId, $dtype)
    {
        $userInfo = \app\admin\model\MemberUser::where('id', $uid)->where('admin_id', 0)->field('id,level_id')->find();
        if ($userInfo->level_id > 0) {
            $productBase = \app\admin\model\ProductLists::where('base', 1)->field('id,title')->find();
            $list = self::getParent($userInfo->level_id, []);
            if ($list) {
                for ($i = 0; $i < count($list); $i++) {
                    if ($dtype == 12) {
                        $thisrate = self::find_seconds_rate($i + 1);
                    }
                    if ($dtype == 11) {
                        $thisrate = self::find_upgood_rate($i + 1);
                    }
                    if ($thisrate > 0) {
                        $is_test = \app\admin\model\MemberUser::where('id', $list[$i]['id'])->value('is_test');
                        $user_cm_wallet = \app\admin\model\MemberWallet::where('product_id', $productBase['id'])->where('uid', $list[$i]['id'])->field('id,cm_money')->find();
                        $ldata['uid'] = $list[$i]['id'];
                        $ldata['is_test'] = $is_test;
                        $ldata['from'] = $uid;
                        $ldata['to'] = $list[$i]['id'];
                        $ldata['account'] = $money;
                        $ldata['before'] = $user_cm_wallet['cm_money'];
                        $ldata['product_id'] = $productBase['id'];
                        $ldata['wallet_id'] = $user_cm_wallet['id'];
                        $ldata['type'] = $dtype;
                        $ldata['account_sxf'] = 0;
                        $ldata['remark'] = $thisrate * 100;
                        $ldata['all_account'] = bc_mul($ldata['account'], $thisrate);
                        $ldata['title'] = lang('wallet_log_type.' . $ldata['type']);
                        $ldata['after'] = bc_add($user_cm_wallet['cm_money'], $ldata['all_account']);
                        $ldata['order_type'] = $ldata['type'] + 30;
                        $ldata['order_id'] = $orderId;
                        $ldata['status'] = 2;
                        $model_log = new \app\admin\model\MemberWalletLog();
                        try {
                            $save = $model_log->save($ldata);
                            if ($save) {
                                \app\admin\model\MemberWallet::where('id', $user_cm_wallet['id'])->update(['cm_money' => $ldata['after']]);
                            }
                        } catch (\Throwable $e) {
                        }
                    }
                }
            }
        }
    }
    /**
     * @Title: 寻找下级IDS集，方便统计
     * @param {*} $uid
     * @param {*} $level 0为1
     */
    public static function level_uids_arr($uid, $level = 1)
    {
        if (!$uid) {
            return false;
        }
        $map['level_id'] = $uid;
        $map['admin_id'] = 0;
        $set_level = sysconfig('base', 'member_level');
        $this_set_level = $set_level + 1;
        $list = \app\admin\model\MemberUser::field('id')->where($map)->select();
        $uids = array();
        $level++;
        foreach ($list as $key => $v) {
            if ($level > $this_set_level) {
                return;
            }
            $user = self::level_uids_arr($v["id"], $level);
            $uids[] = $v["id"];
            if (is_array($user) && !empty($user)) {
                $uids = array_merge($uids, $user);
            }
        }
        return $uids;
    }
    /**
     * @Title: 寻找用户下属各级IDS,组成MYSON
     * @param {*} $uid
     * @param {*} $level 1级开始
     */
    public static function level_uids($uid, $level = 1)
    {
        if (!$uid) {
            return false;
        }
        $map['level_id'] = $uid;
        $map['admin_id'] = 0;
        $this_set_level = sysconfig('base', 'member_level');
        $list = \app\admin\model\MemberUser::field('id')->where($map)->select();
        $uids = array();
        foreach ($list as $key => $v) {
            if ($level > $this_set_level) {
                return;
            }
            $user = self::level_uids($v["id"], $level + 1);
            $uids[$key] = $v;
            $uids[$key]['level'] = $level;
            if (is_array($user) && !empty($user)) {
                $uids[$key]['mysons'] = $user;
            }
        }
        return $uids;
    }
    /**
     * @Title: 寻找用户下级对应的层级IDS
     * @param {*} $arr
     * @param {*} $level
     */
    public static function find_level_uids($arr, $level)
    {
        $uids = [];
        $uidst = [];
        foreach ($arr as $key => $val) {
            if ($val['level'] == $level) {
                $uids[$key] = $val['id'];
                unset($arr[$key]);
            } else {
                if (isset($val['mysons'])) {
                    $uidst = self::find_level_uids($val['mysons'], $level);
                }
            }
            $uids = array_unique(array_merge($uids, $uidst));
        }
        return $uids;
    }
    /**
     * @Title: 直查股东
     * @param {*} $holder_id
     */
    public static function top_adminup_level_ids_name($holder_id)
    {
        if (!$holder_id) {
            return '----';
        }
        $user = \app\admin\model\SystemAdmin::where('id = ' . $holder_id)->value('username');
        return $user;
    }
    /**
     * @Title: 直查业务员
     * @param {*} $level_id
     */
    public static function find_level_user_name($level_id)
    {
        if (!$level_id) {
            return '----';
        }
        $user = \app\admin\model\MemberUser::where('id = ' . $level_id)->field('id,level_id,username')->find();
        if ($user['level_id'] != '0') {
            return self::find_level_user_name($user['level_id']);
        }
        return $user['username'];
    }
}