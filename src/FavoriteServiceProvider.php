<?php

namespace Overtrue\LaravelFavorite;

use Illuminate\Support\ServiceProvider;

class FavoriteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            \dirname(__DIR__).'/config/favorite.php' => config_path('favorite.php'),
        ], 'favorite-config');

        $this->publishes([
            \dirname(__DIR__).'/migrations/' => database_path('migrations'),
        ], 'favorite-migrations');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(\dirname(__DIR__).'/migrations/');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            \dirname(__DIR__).'/config/favorite.php',
            'favorite'
        );
    }
}
