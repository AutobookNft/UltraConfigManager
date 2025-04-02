<?php

namespace Ultra\UltraConfigManager\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Ultra\UltraConfigManager\Providers\UConfigServiceProvider;

use Illuminate\Foundation\AliasLoader;

abstract class TestCase extends Orchestra
{
    
    protected function getPackageProviders($app)
    {
        return [
            UConfigServiceProvider::class,
            \Ultra\UltraLogManager\Providers\UltraLogManagerServiceProvider::class, 
        ];
    }

    protected function defineEnvironment($app)
    {
        
        AliasLoader::getInstance()->alias('UltraLog', \Ultra\UltraLogManager\Facades\UltraLog::class);
        
        $app['config']->set('uconfig.use_spatie_permissions', false);
        $app['config']->set('cache.default', 'array');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getEnvironmentSetUp($app)
    {
        // eventualmente setup custom
    }
}
