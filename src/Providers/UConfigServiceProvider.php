<?php

namespace Ultra\UltraConfigManager\Providers;

use Ultra\UltraConfigManager\Console\Commands\UConfigInitializeCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;
use Ultra\UltraConfigManager\Dao\EloquentConfigDao;
use Ultra\UltraConfigManager\Http\Middleware\CheckConfigManagerRole;
use Ultra\UltraConfigManager\Services\VersionManager;
use Ultra\UltraConfigManager\UltraConfigManager;
use Ultra\UltraConfigManager\Facades\UConfig;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Foundation\AliasLoader;

class UConfigServiceProvider extends ServiceProvider
{
    /**
     * Register bindings and core services.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind main UConfig service
        $this->app->singleton('uconfig', function ($app) {
            return new UltraConfigManager(
                new GlobalConstants(),
                new VersionManager(),
                $app->make(ConfigDaoInterface::class)
            );
        });

        // Bind DAO implementation
        $this->app->singleton(ConfigDaoInterface::class, fn () => new EloquentConfigDao());

    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->shouldSkipBoot()) return;

        $this->loadTranslationsFrom(__DIR__ . './../../resources/lang', 'uconfig');
        $this->loadRoutes();
        $this->registerMiddleware();

        if ($this->app->runningInConsole()) {
            $this->publishResources();
            $this->commands([
                UConfigInitializeCommand::class,
            ]);
        }
    }

    /**
     * Prevents boot logic during queue operations.
     *
     * @return bool
     */
    protected function shouldSkipBoot(): bool
    {
        $arg = $_SERVER['argv'][1] ?? null;
        return in_array($arg, ['queue:work', 'queue:listen'], true);
    }

    /**
     * Load default or user-defined routes.
     *
     * @return void
     */
    protected function loadRoutes(): void
    {
        $customRoute = base_path('routes/uconfig.php');
        $defaultRoute = __DIR__ . './../../routes/web.php';

        $this->loadRoutesFrom(file_exists($customRoute) ? $customRoute : $defaultRoute);
    }

    /**
     * Register the custom role-checking middleware.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('uconfig.check_role', CheckConfigManagerRole::class);
    }

    /**
     * Publish all configurable package resources.
     *
     * @return void
     */
    protected function publishResources(): void
    {
        $timestamp = now()->format('Y_m_d_His_u');
        $baseDir = dirname(__DIR__, 2); // ← sale da src/Providers a root del pacchetto

        $this->publishes([
            // Migrations
            $baseDir . '/database/migrations/create_uconfig_table.php.stub' =>
                $this->app->databasePath("migrations/{$timestamp}_create_uconfig_table.php"),

            $baseDir . '/database/migrations/create_uconfig_versions_table.php.stub' =>
                $this->app->databasePath("migrations/{$timestamp}_create_uconfig_versions_table.php"),

            $baseDir . '/database/migrations/create_uconfig_audit_table.php.stub' =>
                $this->app->databasePath("migrations/{$timestamp}_create_uconfig_audit_table.php"),

            // Seeder
            $baseDir . '/database/seeders/stubs/PermissionSeeder.php.stub' =>
                $this->app->databasePath("seeders/PermissionSeeder.php"),

            // Views
            $baseDir . '/resources/views' =>
                resource_path('views/vendor/uconfig'),

            // Config
            $baseDir . '/config/uconfig.php' =>
                $this->app->configPath('uconfig.php'),

            // Routes
            $baseDir . '/routes/web.php' =>
                base_path('routes/uconfig.php'),

            // Aliases
            $baseDir . '/config/aliases.php' =>
                base_path('bootstrap/aliases.php'),

            // Translations
            $baseDir . '/resources/lang' =>
                resource_path('lang/vendor/uconfig'),
        ], 'uconfig-resources');
    }

}
