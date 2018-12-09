<?php

namespace Starrysea\Gosstone\Tests;

use Starrysea\Gosstone\Sms;

class SmsGatherTest
{
    // send sms verify code
    public static function send()
    {
        return Sms::first()
            ->phone('13333339558') // set accept sms of phone code
            ->setMessage('你的登录验证码为：') // set sms content
            ->setVerifycode('login') // set verify code and in redis cache, cache name: login
            ->setMessage('，') // append sms content
            ->tail('千万千万不要告诉别人哦！') // set sms tail
            ->execute(); // send sms
    }

    // verify sms verify code
    public static function verifycode()
    {
        return Sms::proveVerifycode('login','13333339558','$code');
    }

    // del redis verify code
    public static function delverifycode()
    {
        return Sms::delVerifycode('login','13333339558','$code');
    }

    // get sms user info
    public static function getuser()
    {
        return Sms::first()->GetUser();
    }

    // update database sms status
    public static function upsmsstatus()
    {
        return Sms::first()->MatchStatus();
    }

    // get database sms status
    public static function gettype()
    {
        return Sms::GetType(0); // 未知
//        return GosstoneSms::GetType(1); // 成功
//        return GosstoneSms::GetType(2); // 失败
    }
}