<?php

namespace Fabio\PerfectConfigManager\Providers;

use Fabio\PerfectConfigManager\ConfigManager;
use Illuminate\Support\ServiceProvider;

class ConfigManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Registra ConfigManager come singleton per poterlo utilizzare ovunque
        $this->app->singleton(ConfigManager::class, function ($app) {
            return new ConfigManager();
        });
    }

    public function boot()
    {
        // Qui pubblichiamo il file di configurazione per chi utilizza la libreria.
        $this->publishes([
            __DIR__ . '/../config/config_manager.php' => config_path('config_manager.php'),
        ], 'config');
    }
}
