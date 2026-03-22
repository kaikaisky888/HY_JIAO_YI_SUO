<?php


namespace app\api\common;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\ValidationData;

/**
 * 单例模式
 * 一次请求中所有使用到jwt的地方都是一个用户
 * Class JwtAuth
 * @package app\common\lib\auth
 * 1. 接口鉴权
 * 2. 获取用户身份
 */
class JwtAuth
{
    /**
     * @var
     */
    private $token;
    /**
     * @var
     */
    private $decodeToken;
    /**
     * claim iss
     * @var string
     */
    private $iss = 'fox.com';
    /**
     * claim aud
     * @var string
     */
    private $aud = 'tp6_server_app';
    /**
     * 身份 uid
     * @var string
     */
    private $uid;

    /**
     * @var string
     */
    private $secret = 'TP6&*fox1988#$LJL*&^&*1989';

    /**
     * @var null
     */
    private static $instance = null;
    /*
     * 私有化 构造函数
     */
    private function __construct()
    {
    }
    /*
     *
     */
    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    /**
     * 单例模式 获取jwtAuth句柄
     * @return JwtAuth|null
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)){
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param $uid
     * @return $this
     * 设置身份信息
     */
    public function setUid($uid){
        $this->uid = $uid;
        return $this;
    }

    /**
     * @param $token
     * 设置token
     */
    public function setToken($token){
        $this->token = $token;
        return $this;
    }
    /**
     * @param $token
     */
    public function decode(){

        if (!$this->decodeToken){
            // 把字符串转成Token对象
            $this->decodeToken = (new Parser())->parse((string)$this->token);
            $this->uid = $this->decodeToken->getClaim('uid');
        }

        return $this->decodeToken;
    }

    /**
     * 校验signature, 判断token是否过期或者被篡改
     * @return bool
     */
    public function verify(){
        $signer = new Sha256();
        $privateKey = new Key($this->secret);

        $result = $this->decode()->verify($signer, $privateKey);

        return $result;
    }
    /**
     * 校验参数
     * @return bool
     */
    public function validate(){

        $data = new ValidationData();
        $data->setIssuer($this->iss);
        $data->setAudience($this->aud);

        return $this->decode()->validate($data);
    }

    /**
     * @return $this
     */
    public function encode(){

        $signer = new Sha256();
        $privateKey = new Key($this->secret);
        $time = time(); // 颁发时间

        $this->token = (new Builder())
            ->withHeader('alg', 'HS256')
            ->issuedBy($this->iss)
            ->permittedFor($this->aud)
            ->issuedAt($time)
            ->expiresAt($time + 3600)  // 过期时间
            ->withClaim('uid', $this->uid)  // 自定义参数
            ->getToken($signer, $privateKey);

        return $this;
    }

    /**
     * 获取token
     * @return string
     */
    public function getToken(){

        return (string)$this->token;

    }

    /**
     * @return string
     */
    public function getUid(){
        return $this->uid;
    }

}
