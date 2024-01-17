<?php

namespace Overtrue\LaravelFavorite;

use Illuminate\Support\ServiceProvider;

class FavoriteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (app()->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__ . '/../migrations/' => database_path('migrations'),
            ], 'favorite-migrations');

            $this->publishes([
                __DIR__ . '/../config/favorite.php' => config_path('favorite.php'),
            ], 'favorite-config');

        }
    }

    protected function registerMigrations()
    {
        if (Favorite::shouldRunMigrations()) {
            return $this->loadMigrationsFrom(\dirname(__DIR__) . '/migrations/');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            \dirname(__DIR__) . '/config/favorite.php',
            'favorite'
        );
    }
}
