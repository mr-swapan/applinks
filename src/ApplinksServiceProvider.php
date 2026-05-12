<?php

namespace MrSwapan\Applinks;

use Illuminate\Support\ServiceProvider;

class ApplinksServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('applinks', function () {
            return new Services\ApplinksService();
        });

        $this->mergeConfigFrom(
            __DIR__ . '/../config/applinks.php',
            'applinks'
        );
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->publishes([
            __DIR__ . '/../config/applinks.php' => config_path('applinks.php'),
        ], 'applinks-config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'applinks-migrations');
    }
}
