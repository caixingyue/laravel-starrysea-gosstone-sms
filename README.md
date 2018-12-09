## 安装
- [Laravel](#laravel)
- [Lumen](#lumen)

### Laravel

该软件包可用于 Laravel 5.6 或更高版本。

您可以通过 composer 安装软件包：

``` bash
composer require starrysea/gosstone
```

在 Laravel 5.6 中，服务提供商将自动注册。在旧版本的框架中，只需在 `config/app.php` 文件中添加服务提供程序：

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

你可以通过以下方式 [发布迁移](https://github.com/caixingyue/laravel-starrysea-gosstone-sms/blob/master/database/migrations/create_smsoutbox_table.php.stub)：

```bash
php artisan vendor:publish --provider="Starrysea\Gosstone\SmsServiceProvider" --tag="migrations"
```

发布迁移后，你可以通过运行迁移来创建短信发件箱表：

```bash
php artisan migrate
```

你可以使用以下命令发布配置文件：

```bash
php artisan vendor:publish --provider="Starrysea\Gosstone\SmsServiceProvider" --tag="config"
```

发布时 `config/gosstonesms.php` [配置文件](https://github.com/caixingyue/laravel-starrysea-gosstone-sms/blob/master/config/gosstonesms.php) 包含：

```php
return [
    // 缓存时间(单位：秒)
    'cachetime' => 600,

    // 机构ID:用户名
    'username' => '',

    // 账户密码
    'password' => '',
];
```

### Lumen

您可以通过 composer 安装软件包：

``` bash
composer require starrysea/gosstone
```

复制所需的文件：

```bash
cp vendor/starrysea/gosstone/config/gosstonesms.php config/gosstonesms.php

cp vendor/starrysea/gosstone/database/migrations/create_smsoutbox_table.php.stub database/migrations/2019_01_01_000000_create_smsoutbox_table.php
```

现在,运行你的迁移：

```bash
php artisan migrate
```

注册服务提供者和门面：

```php
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
