## 安装
- [Laravel](#laravel)
- [Lumen](#lumen)

### Laravel

该软件包可用于 Laravel 5.6 或更高版本。

您可以通过 composer 安装软件包：

``` bash
composer require starrysea/gosstone
```

在 Laravel 5.6 中，服务提供商将自动注册。在旧版本的框架中，只需在 config/app.php 文件中添加服务提供程序：

```php
'providers' => [
    // ...
    Starrysea\Gosstone\SmsServiceProvider::class,
];

'aliases' => [
    // ...
    'GosstoneSms' => Starrysea\Gosstone\Sms::class,
];
```

### Lumen

您可以通过 composer 安装软件包：

``` bash
composer require starrysea/gosstone
```

注册服务提供者和门面：

```bash
$app->register(Starrysea\Gosstone\SmsServiceProvider::class); // 注册 GosstoneSms 服务提供者

class_alias(Starrysea\Gosstone\Sms::class, 'GosstoneSms'); // 添加 GosstoneSms 门面
```

## 用法

```php
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
```
