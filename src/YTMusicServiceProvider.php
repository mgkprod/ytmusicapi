<?php

namespace MGKProd\YTMusic;

use Illuminate\Support\ServiceProvider;

class YTMusicServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'ytmusicapi');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('ytmusicapi.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'ytmusicapi');

        // Register the main class to use with the facade
        $this->app->singleton('ytmusicapi', function () {
            return new YTMusic;
        });
    }
}
