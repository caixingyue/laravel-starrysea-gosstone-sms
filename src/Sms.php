<?php

namespace Starrysea\Gosstone;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Starrysea\Arrays\Arrays;
use Starrysea\Curl\Curl;
use Starrysea\Usually\Convert;

class Sms
{
    private $phone     = ''; // 手机号码
    private $content   = ''; // 发送短信内容
    private $cachename = ''; // 生成缓存名称
    private $cacheinfo = ''; // 生成缓存信息
    private $taildata  = ''; // 短信小尾巴
    private $cachetime = ''; // 缓存生存时间
    private $username  = ''; // 短信账户名
    private $password  = ''; // 短信账户密码

    function __construct()
    {
        $this->cachetime = config('gosstonesms.cachetime');
        $this->username  = config('gosstonesms.username');
        $this->password  = config('gosstonesms.password');
    }

    /**
     * 初始化
     * @return Sms
     */
    public static function first()
    {
        return new self;
    }

    /**
     * 接收短信的手机号码
     * @param int $value 手机号码
     * @return $this
     */
    public function phone(int $value)
    {
        $this->phone = $value;

        return $this;
    }

    /**
     * 设置短信普通内容
     * @param string $value 短信内容
     * @return $this
     */
    public function setMessage(string $value)
    {
        $this->content .= $value;

        return $this;
    }

    /**
     * 设置短信验证码
     * @param string|bool $cacheName false => 不生成缓存
     * @param int $number 验证码位数, 默认6位数
     * @return $this
     */
    public function setVerifycode($cacheName = false, int $number = 6)
    {
        $data = Convert::randstr($number, 'S'); // 生成随机数字码
        if ($cacheName){
            $this->cachename = $cacheName . ':' . $data . $this->phone;
            $this->cacheinfo = $data;
        }

        return $this->setMessage($data); // 写上验证码
    }

    /**
     * 设置缓存有效时间
     * @param int $time 缓存有效时间, 默认600秒
     * @param bool $minute true => 分, false => 秒[默认]
     * @return $this
     */
    public function setCacheTime(int $time = 600, bool $minute = false)
    {
        if ($minute){
            $this->cachetime = $time * 60; // 分
        }else{
            $this->cachetime = $time; // 秒
        }

        return $this;
    }

    /**
     * 短信小尾巴
     * @param string $data 小尾巴内容
     * @return $this
     */
    public function tail(string $data = '为了您的账户安全，请勿泄露。')
    {
        $this->taildata = $data;

        return $this;
    }

    /**
     * 检查验证码是否正确
     * @param string $cacheName 缓存名称
     * @param int $phone 手机号码
     * @param int $verification 验证码
     * @return bool true => 正确, false => 错误
     */
    public static function proveVerifycode(string $cacheName, int $phone, int $verification)
    {
        $res = Redis::get($cacheName . ':' . $verification . $phone);
        return (int) $res === $verification;
    }

    /**
     * 删除验证码缓存
     * @param string $cacheName 缓存名称
     * @param int $phone 手机号码
     * @param int $verification 验证码
     * @return bool true => 删除成功, false => 删除失败
     */
    public static function delVerifycode(string $cacheName, int $phone, int $verification) : bool
    {
        return Redis::del($cacheName . ':' . $verification . $phone);
    }

    /**
     * 发送短信
     * @return bool true => 发送成功, false => 发送失败
     */
    public function execute()
    {
        if ($this->phone && $this->content){
            $conten = $this->content . $this->taildata;

            $result = Curl::first()->get('http://gateway.iems.net.cn/GsmsHttp', [
                'username' => $this->username, 'password' => $this->password,
                'to' => $this->phone, 'content' => iconv('UTF-8', 'GBK', $conten)
            ])->request();
            $cutdata = explode(':', $result);

            // 验证是否发送成功
            if ($cutdata[0]=='OK') {
                // 储存发件信息至数据表中
                DB::table('smsoutbox')->insert([
                    'messageId' => trim($cutdata[1]),
                    'phone'   => $this->phone,
                    'content' => $conten,
                    'fstimes' => time(),
                ]);

                // 生成缓存
                if ($this->cachename && $this->cacheinfo)
                    Redis::setex($this->cachename, $this->cachetime, $this->cacheinfo);
                return true;
            }
        }
        return false;
    }

    /**
     * 获取账户信息[状态, 账户余额,套餐,单价,中文签名,英文签名]
     * @return mixed|\SimpleXMLElement|string
     */
    public function GetUser()
    {
        $data = Curl::first()->get('http://gateway.iems.net.cn/GeneralSMS/GetAccountBalance?msgType=xml', [
            'username' => $this->username, 'password' => $this->password
        ])->request();
        $data = mb_convert_encoding($data, 'UTF-8', 'gbk');
        $data = str_replace('GBK','utf-8', $data);
        $data = simplexml_load_string($data);
        $data = json_encode($data);
        $data = json_decode($data,true);
        $data = $data['item'];
        array_set($data, 'statusName', $this->GetStatus(array_get($data, 'status')));
        return $data;
    }

    /**
     * 更新数据库短信状态
     * @return bool
     */
    public function MatchStatus()
    {
        try{
            $data = Curl::first()->get('http://gateway.iems.net.cn/GeneralSMS/GSmsHttpReport?getType=1&itemNum=999999999', [
                'username' => $this->username, 'password' => $this->password
            ])->request();
            $data = mb_convert_encoding($data,'UTF-8','gbk');
            $data = str_replace('GBK','utf-8',$data);
            $data = simplexml_load_string($data);
            $data = json_encode($data);
            $data = json_decode($data,true);
            $data = $data['item'];
            $data = Arrays::OneToTwo($data);
            foreach ($data as $value){
                DB::table('smsoutbox')->where('messageId', $value['messageId'])->update([
                    'state'  => $value['sendFlag'] == 3 ? 1 : 2,
                    'reason' => $value['report'],
                    'reasontimes' => Carbon::parse($value['reportTime'])->timestamp,
                ]);
            }
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * 数据获取错误原因资料
     * @param string $value 错误原因
     * @return mixed
     */
    public function GetStatus($value = '')
    {
        return array_get([
            'ok' => '正常',
            'OK' => '成功',
            'ePassword' => '用户不存在或者密码错误',
            'eStop'     => '用户已停用',
            'eDenyDate' => '帐户过期',
            'eBalance'  => '余额不足',
            'eError'    => '其他错误',
            'eFrequent' => '请求频繁',
            'nContent'  => 'HTTP请求中内容为空',
            'nFormatWrong'  => 'HTTP请求中参数格式错误',
            'eContentWrong' => '短信模板拦截',
            '网络错误!'  => '系统正在维护中',
            '数据库繁忙' => '表示数据库繁忙',
            '其它错误'   => '其它错误',
        ], $value, '未知错误：' . $value);
    }

    /**
     * 获取数据库短信状态
     * @param string $field 指定键名
     * @return array
     */
    public static function GetType(string $field)
    {
        $data = ['未知', '成功', '失败'];
        return data_get($data, $field, '未知');
    }
}
