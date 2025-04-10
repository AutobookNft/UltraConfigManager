<?php

/**
 * 📜 Oracode Class: UConfigServiceProvider
 *
 * @package         Ultra\UltraConfigManager\Providers
 * @version         1.0.0
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Ultra\UltraConfigManager\Console\Commands\UConfigInitializeCommand;
use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;
use Ultra\UltraConfigManager\Dao\EloquentConfigDao;
use Ultra\UltraConfigManager\Http\Middleware\CheckConfigManagerRole;
use Ultra\UltraConfigManager\Services\VersionManager;
use Ultra\UltraConfigManager\UltraConfigManager;
// Import Facades used for type hinting or internal logic if necessary
// use Illuminate\Support\Facades\Log; // Replaced potentially by UltraLog implicitly via Manager/DAO
// use Illuminate\Support\Facades\Route; // Resolved via Router injection/app access

/**
 * 🎯 Purpose: Registers the UltraConfigManager (UCM) services, resources, and configurations
 *    within the Laravel application lifecycle. Acts as the central nervous system entry point
 *    for the UCM package.
 *
 * 🧱 Structure:
 *    - `register()`: Binds core services (Manager, DAO) to the IoC container.
 *    - `boot()`: Loads resources (translations, views), registers routes/middleware,
 *      publishes assets, and registers console commands. Handles conditional booting.
 *    - Helper methods: `shouldSkipBoot`, `loadRoutes`, `registerMiddleware`,
 *      `publishResources`, `publishMigrations`.
 *
 * 🧩 Context: Operates during Laravel's bootstrap phase. Integrates UCM with the host application.
 *
 * 🛠️ Usage: Automatically loaded by Laravel's service provider discovery or explicitly
 *    registered in `config/app.php`. No direct manual instantiation needed.
 *
 * 💾 State: Does not maintain internal state; interacts with the Laravel application ($this->app)
 *    and filesystem for resource loading/publishing.
 *
 * 🗝️ Key Methods:
 *    - `register`: Core service binding.
 *    - `boot`: Resource loading and integration logic.
 *    - `publishResources`: Makes assets available to the host application.
 *
 * 🚦 Signals: Publishes assets under the 'uconfig-resources' tag. Registers console command
 *    `uconfig:initialize`. Registers middleware alias `uconfig.check_role`.
 *
 * 🛡️ Privacy (GDPR): This Service Provider itself does not handle PII. However, it registers
 *    services (like UCM Manager/DAO) and publishes resources (like migrations for audit tables)
 *    that *are* involved in handling potentially sensitive configuration data and user IDs
 *    for auditing. Compliance is delegated to the components it registers/enables.
 *    - `@privacy-delegated`: Responsibility for data handling lies in registered services (UCM, DAO) and published migrations/models.
 *
 * 🤝 Dependencies:
 *    - `Laravel IoC Container ($this->app)`: For binding and resolving services.
 *    - `Illuminate\Routing\Router`: For route loading and middleware registration.
 *    - `Illuminate\Filesystem\Filesystem`: Implicitly for resource loading/publishing paths.
 *    - `Ultra\UltraConfigManager Components`: Depends on DAO, Manager, Services, Constants, Middleware, Command classes internally.
 *    - `Ultra\UltraLogManager`: Potentially used implicitly by Manager/DAO for logging (should be confirmed/made explicit if critical).
 *    - `Ultra\ErrorManager`: Potentially used implicitly by Manager/DAO for error handling.
 *
 * 🧪 Testing: Tested via integration tests that simulate Laravel application boot process
 *    and check if services are bound, resources are published/loaded correctly, and
 *    routes/middleware are registered. Unit testing is less applicable here.
 *    Focus on asserting the *results* of the registration and boot processes.
 *
 * 💡 Logic:
 *    - Uses singleton bindings for Manager and DAO for efficiency.
 *    - Binds DAO interface to a specific implementation (Eloquent).
 *    - Conditional boot logic (`shouldSkipBoot`) to avoid issues in specific environments (e.g., queue workers).
 *    - Careful ordering of migration publishing (`publishMigrations`) to respect FK constraints.
 *    - Uses `$app->booted()` closure for route loading to ensure router availability.
 *    - Applies 'web' middleware group to UCM routes for session/CSRF support.
 *    - Standard Laravel resource publishing mechanisms.
 *
 * @package     Ultra\UltraConfigManager\Providers
 */
class UConfigServiceProvider extends ServiceProvider
{
    /**
     * 🎯 Purpose: Binds the core UltraConfigManager service and its primary dependencies
     *    (specifically the Data Access Object implementation) into the Laravel service container.
     *    Ensures that UCM is available as a singleton throughout the application.
     *
     * 🛠️ Usage: Called automatically by Laravel during the 'register' phase of the request lifecycle.
     *
     * 💾 State: Modifies the application's service container bindings.
     *
     * 🤝 Dependencies:
     *    - `$this->app` (Laravel Application instance / IoC Container)
     *    - `Ultra\UltraConfigManager\UltraConfigManager`
     *    - `Ultra\UltraConfigManager\Constants\GlobalConstants`
     *    - `Ultra\UltraConfigManager\Services\VersionManager`
     *    - `Ultra\UltraConfigManager\Dao\ConfigDaoInterface`
     *    - `Ultra\UltraConfigManager\Dao\EloquentConfigDao`
     *
     * 💡 Logic:
     *    - Binds the alias 'uconfig' to a singleton instance of `UltraConfigManager`.
     *      - Injects concrete `GlobalConstants` and `VersionManager`. Consider making these container-managed if they gain complexity/dependencies.
     *      - Resolves `ConfigDaoInterface` via the container, allowing flexibility.
     *    - Binds the `ConfigDaoInterface` to the concrete `EloquentConfigDao` implementation as a singleton.
     *
     * @return void
     * @sideEffect Modifies IoC container bindings.
     */
    public function register(): void
    {
        // Bind main UConfig service as a singleton
        $this->app->singleton('uconfig', function (Application $app) {
            return new UltraConfigManager(
                // Consider resolving these via $app->make() if they become complex
                new GlobalConstants(),
                new VersionManager(),
                $app->make(ConfigDaoInterface::class) // Correct: Resolve DAO via interface
            );
        });

        // Bind the DAO interface to the Eloquent implementation as a singleton
        $this->app->singleton(ConfigDaoInterface::class, fn () => new EloquentConfigDao());

        // Optional: Merge default config if the package provides one
        // $this->mergeConfigFrom(__DIR__.'/../../config/uconfig.php', 'uconfig');
    }

    /**
     * 🎯 Purpose: Performs bootstrapping tasks for UCM after all other service providers
     *    have been registered. This includes loading essential resources (translations, views),
     *    registering routes and middleware, handling asset publishing, and registering commands.
     *
     * 🛠️ Usage: Called automatically by Laravel during the 'boot' phase.
     *
     * 💾 State: Interacts with filesystem (loading/publishing), routing table, middleware registry, console kernel.
     *
     * 🤝 Dependencies:
     *    - `$this->app`
     *    - Filesystem paths relative to `__DIR__`.
     *    - `Ultra\UltraConfigManager\Console\Commands\UConfigInitializeCommand`
     *    - Relies on `register()` having been called first.
     *
     * 💡 Logic:
     *    - Includes a check (`shouldSkipBoot`) to prevent execution in certain contexts (e.g., queue workers).
     *    - Loads namespaced translations and views.
     *    - Calls helper methods to load routes and register middleware.
     *    - Conditionally (if running in console) publishes resources and registers commands.
     *
     * @return void
     * @sideEffect Loads files, registers routes/middleware/commands, potentially publishes files.
     */
    public function boot(): void
    {
        // Prevent boot logic in specific environments like queue workers
        if ($this->shouldSkipBoot()) {
            return;
        }

        // Load package resources
        $this->loadTranslationsFrom(dirname(__DIR__, 2) . '/resources/lang', 'uconfig');
        $this->loadViewsFrom(dirname(__DIR__, 2) . '/resources/views', 'uconfig');

        // Register routes and middleware
        $this->loadRoutes();
        $this->registerMiddleware();

        // Console-specific tasks (publishing, commands)
        if ($this->app->runningInConsole()) {
            $this->publishResources();
            $this->commands([
                UConfigInitializeCommand::class,
            ]);
        }
    }

    /**
     * 🎯 Purpose: Determines if the boot logic should be skipped, typically for performance
     *    or stability reasons in non-HTTP request contexts like queue workers.
     *
     * 💡 Logic: Checks the second command-line argument (`$_SERVER['argv'][1]`) against a list
     *    of known queue commands.
     *    ❗ *Note:* This method might be fragile. Consider alternatives like checking
     *      `$this->app->runningInConsole()` combined with specific environment checks
     *      if more robustness is needed (e.g., for Octane, schedulers).
     *
     * @return bool True if booting should be skipped, false otherwise.
     * @contextual Check relevant only in specific execution contexts.
     */
    protected function shouldSkipBoot(): bool
    {
        // Basic check for standard Laravel queue worker commands
        $arg = $_SERVER['argv'][1] ?? null;
        return in_array($arg, ['queue:work', 'queue:listen'], true);
        // Alternative/Additional checks could involve:
        // return $this->app->runningInConsole() && !$this->app->runningUnitTests() && ...;
    }

    /**
     * 🎯 Purpose: Loads the package's web routes from the published location within the
     *    host application, ensuring they are available to handle UCM UI requests.
     *
     * 🛠️ Usage: Called internally by the `boot()` method.
     *
     * 💾 State: Modifies the application's route collection.
     *
     * 🤝 Dependencies:
     *    - `$this->app` (to resolve the Router and check application state)
     *    - `Illuminate\Routing\Router`
     *
     * 💡 Logic:
     *    - Uses `$this->app->booted()` to ensure the Router service is ready.
     *    - Checks if the routes file exists at `base_path('routes/uconfig.php')` (the published location).
     *    - Groups the routes under the 'web' middleware to enable session, CSRF, etc.
     *
     * @return void
     * @sideEffect Registers routes if the route file exists.
     */
    protected function loadRoutes(): void
    {
        $this->app->booted(function () {
            /** @var Router $router */
            $router = $this->app->make(Router::class);
            $routesPath = base_path('routes/uconfig.php');

            if (file_exists($routesPath)) {
                $router->middleware(['web']) // Apply web middleware group
                       ->group($routesPath);
            }
        });
    }

    /**
     * 🎯 Purpose: Registers the custom middleware alias used for checking UCM-specific
     *    roles or permissions within the application's routing layer.
     *
     * 🛠️ Usage: Called internally by the `boot()` method.
     *
     * 💾 State: Modifies the application's middleware alias registry.
     *
     * 🤝 Dependencies:
     *    - `$this->app['router']` (accessing Router via array access)
     *    - `Ultra\UltraConfigManager\Http\Middleware\CheckConfigManagerRole`
     *
     * 💡 Logic: Assigns the alias 'uconfig.check_role' to the `CheckConfigManagerRole` class.
     *    ❗ Ensure the alias 'uconfig.check_role' is unique within the application.
     *
     * @return void
     * @sideEffect Registers a middleware alias.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('uconfig.check_role', CheckConfigManagerRole::class);
    }

    /**
     * 🎯 Purpose: Defines and registers the publishable assets (migrations, config, views, etc.)
     *    of the UCM package, allowing users to customize them within their application.
     *
     * 🛠️ Usage: Called internally by `boot()` when running in the console. Triggered by the
     *    `php artisan vendor:publish --tag=uconfig-resources` command.
     *
     * 💾 State: Makes files available for copying to the host application's structure.
     *
     * 🤝 Dependencies:
     *    - `$this->app` (for resolving paths like `databasePath`, `configPath`, etc.)
     *    - Filesystem structure of the package itself.
     *
     * 💡 Logic:
     *    - Uses a common tag 'uconfig-resources' for all publishable assets.
     *    - Calculates the package base directory correctly.
     *    - Calls `publishMigrations` to handle migrations separately (due to ordering).
     *    - Defines source (package) and destination (application) paths for each resource type.
     *    ❗ Publishing the Seeder stub might overwrite an existing `PermissionSeeder.php` in the user's app. Consider a different naming or strategy if this is a concern.
     *
     * @return void
     * @sideEffect Makes resources available for publishing via Artisan command.
     */
    protected function publishResources(): void
    {
        $timestamp = now()->format('Y_m_d_His_u'); // Used for unique migration filenames
        $baseDir = dirname(__DIR__, 2); // Package root directory

        // Handle migration publishing separately for order control
        $this->publishMigrations($timestamp, $baseDir);

        // Publish other resources under the 'uconfig-resources' tag
        $this->publishes([
            // Config file
            $baseDir . '/config/uconfig.php' => $this->app->configPath('uconfig.php'),
            // Views
            $baseDir . '/resources/views' => resource_path('views/vendor/uconfig'),
            // Translations
            $baseDir . '/resources/lang' => resource_path('lang/vendor/uconfig'),
            // Routes file
            $baseDir . '/routes/web.php' => base_path('routes/uconfig.php'),
            // Optional Seeder stub
            // Potential Overwrite Warning: This might replace an existing seeder.
            $baseDir . '/database/seeders/stubs/PermissionSeeder.php.stub' => $this->app->databasePath("seeders/PermissionSeeder.php"),
        ], 'uconfig-resources');
    }

    /**
     * 🎯 Purpose: Publishes the package's database migrations in a specific, controlled order
     *    to ensure that foreign key constraints are respected during migration runs.
     *
     * 🛠️ Usage: Called internally by `publishResources()`.
     *
     * 💾 State: Makes migration files available for copying to the application's `database/migrations` directory.
     *
     * 🤝 Dependencies:
     *    - `$this->app` (for resolving `databasePath`)
     *
     * 💡 Logic:
     *    - Defines an explicit order for migrations (`uconfig` table first, then `versions` and `audit`).
     *    - Uses numeric prefixes in the destination filename (combined with timestamp) to enforce this order.
     *    - Publishes each migration under the same 'uconfig-resources' tag for consistency.
     *
     * @param string $timestamp A unique timestamp string for migration filenames.
     * @param string $baseDir   The root directory path of the package.
     * @return void
     * @sideEffect Makes migration files available for publishing.
     * @critical FK Constraint Dependency: Order is vital here.
     */
    protected function publishMigrations(string $timestamp, string $baseDir): void
    {
        // Define migrations in the required order of execution
        $migrations = [
            '0_create_uconfig_table.php'          => 'create_uconfig_table.php.stub',          // Base table
            '1_create_uconfig_versions_table.php' => 'create_uconfig_versions_table.php.stub', // Depends on uconfig
            '2_create_uconfig_audit_table.php'    => 'create_uconfig_audit_table.php.stub',    // Depends on uconfig
        ];

        foreach ($migrations as $orderedName => $stubFilename) {
            $this->publishes([
                $baseDir . "/database/migrations/{$stubFilename}" =>
                    $this->app->databasePath("migrations/{$timestamp}_{$orderedName}"), // Enforces order
            ], 'uconfig-resources'); // Use the same tag as other resources
        }
    }
}