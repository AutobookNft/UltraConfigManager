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
use Illuminate\Routing\Router;

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

        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'uconfig');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'uconfig');

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
     * Load the package's routes.
     *
     * @return void
     */
    protected function loadRoutes(): void
    {
        $this->app->booted(function () {
            $router = $this->app->make(Router::class);
    
            if (file_exists(base_path('routes/uconfig.php'))) {
                $router->middleware(['web']) // ✅ aggiunto 'web'
                       ->group(base_path('routes/uconfig.php'));
            }
        });
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
        
        // Publish migrations
        $this->publishMigrations($timestamp, $baseDir);
                        
        $this->publishes([
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
            
            // Translations
            $baseDir . '/resources/lang' =>
                resource_path('lang/vendor/uconfig'),
        ], 'uconfig-resources');
    }

    /**
     * Publish migrations with controlled filename order.
     *
     * @param string $timestamp
     * @param string $baseDir
     * @return void
     * 
     * Publishing migrations with controlled filename order.
     * UConfig must be created before versions and audit (foreign key dependency).
     */

    protected function publishMigrations(string $timestamp, string $baseDir): void
    {
        $migrations = [
            '0_create_uconfig_table.php' => 'create_uconfig_table.php.stub',
            '1_create_uconfig_versions_table.php' => 'create_uconfig_versions_table.php.stub',
            '2_create_uconfig_audit_table.php' => 'create_uconfig_audit_table.php.stub',
        ];
        
        foreach ($migrations as $orderedName => $stub) {
            $this->publishes([
                $baseDir . "/database/migrations/{$stub}" =>
                    $this->app->databasePath("migrations/{$timestamp}_{$orderedName}"),
            ], 'uconfig-resources');
        }
    }
}
