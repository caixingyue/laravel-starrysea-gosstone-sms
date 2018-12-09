<?php

namespace Starrysea\Gosstone;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/gosstonesms.php' => config_path('gosstonesms.php'),
        ],'config');

        if (!class_exists('CreateSmsoutboxTable')){
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/create_smsoutbox_table.php.stub' => database_path("migrations/{$timestamp}_create_smsoutbox_table.php"),
            ], 'migrations');
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('GosstoneSms', function () {
            return new Sms();
        });
    }
}
