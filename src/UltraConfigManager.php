<?php

namespace Ultra\UltraConfigManager;

use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Ultra\UltraConfigManager\Services\VersionManager;
use Ultra\UltraLogManager\Facades\UltraLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UltraConfigManager – Oracoded Edition
 *
 * 🎯 Central orchestrator for secure, testable, and auditable config state.
 * 🧱 Fully modular, mutation-aware and cache-controllable.
 * 🧪 Extensively test-driven and injection-complete.
 * 🔥 Critical for consistency, rollback integrity and observability.
 * 🧩 Compliant with Oracode/UDP standards and semantic traceability.
 */
class UltraConfigManager
{
    /**
     * 🧱 @structural In-memory configuration array
     *
     * Holds the current state of the loaded configuration. This array is populated
     * during boot via `loadConfig()`, and optionally refreshed via `reload()` or
     * cache invalidation processes.
     *
     * @var array<string, array{value: mixed, category?: string}>
     */
    private array $config = [];

    /**
     * 🧱 @structural Global constants handler
     *
     * Provides access to shared constant values used across the configuration
     * logic, especially for fallback identity and system-wide markers.
     *
     * @var GlobalConstants
     */
    protected GlobalConstants $globalConstants;

    /**
     * 🧱 @structural Version manager
     *
     * Manages the generation and assignment of sequential version numbers
     * for persisted configuration changes.
     *
     * @var VersionManager
     */
    protected VersionManager $versionManager;

    /**
     * 🧩 @configurable Cache key used to store and retrieve serialized config
     *
     * This value is referenced by both runtime operations and test scenarios,
     * and must remain stable to avoid cache fragmentation.
     *
     * @var string
     */
    private const CACHE_KEY = 'ultra_config.cache';

    /**
     * 🧱 @structural Config DAO
     *
     * DAO abstraction used for retrieving and persisting configuration records
     * from the underlying database layer.
     *
     * @var ConfigDaoInterface
     */
    protected ConfigDaoInterface $configDao;

    /**
     * 🧪 Test Override: TTL value for cache simulation
     *
     * This field allows test classes to override the default TTL used when
     * caching configuration. It bypasses the call to `config('uconfig.cache.ttl')`,
     * which would fail outside of Laravel’s container.
     *
     * 🧩 Overrides dynamic behavior from env/config
     * 🔄 Alters cache control logic in test mode
     *
     * @var int|null
     * @configurable
     * @mutation
     */
    protected ?int $testCacheTtl = null;

    /**
     * ⛓️ Oracular Control Flag (Testing Only)
     *
     * Forces the cache behavior in test environments where Laravel's config() is unavailable.
     * 🧪 Only settable via testingForceCache()
     * 🔒 Not to be used in production
     * 🧱 Structural override point for testing scenarios
     *
     * @var bool|null
     * @test
     * @structural
     */
    protected ?bool $testCacheFlag = null;

    /**
     * 🔌 Log Toggle Flag (for Test Environments)
     *
     * Controls whether internal logging via UltraLog is allowed.
     * In standalone PHPUnit executions where Laravel’s Facade root
     * is not available, this flag can be disabled to prevent crashes.
     *
     * 🧪 Used to bypass UltraLog in pure test runners
     * 🧱 Structural control of side effects
     *
     * @var bool
     */
    protected bool $logEnabled = true;


     /**
     * 🎯 Entry Point: UltraConfigManager constructor
     *
     * Initializes the core configuration engine for the Ultra ecosystem.
     * Injects the required dependencies: GlobalConstants, VersionManager, and
     * ConfigDaoInterface. Immediately triggers a configuration load, including
     * database and environment merges, optionally cached.
     *
     * 🔐 Lifecycle entry for all configuration interactions
     * 🧪 Fully testable: all injected dependencies can be mocked
     * 🔄 Mutates internal config state via `loadConfig()`
     * 🧱 Structurally prepares logging and semantic availability
     *
     * @param GlobalConstants $globalConstants Global constant provider (identity fallback, etc.)
     * @param VersionManager $versionManager Versioning strategy manager
     * @param ConfigDaoInterface $configDao Abstraction for database interaction layer
     */
    public function __construct(
        GlobalConstants $globalConstants,
        VersionManager $versionManager,
        ConfigDaoInterface $configDao,
    ) {

        UltraLog::info('UCM Action', 'UltraConfigManager initialized');

        $this->globalConstants = $globalConstants;
        $this->versionManager = $versionManager;
        $this->configDao = $configDao;

        // ⛓️ Load config only if running inside a full Laravel context
        if (app()->bound('cache') && app()->bound('db')) {
            $this->loadConfig();
        }
    }

    /**
     * ⛓️ Oracular Utility: Testing-only Cache Flag Injection
     * Allows external control over the internal cache decision logic,
     * bypassing the Laravel config() call for test environments.
     *
     * 🔐 Usage Context: PHPUnit, no Laravel container
     * 🧪 Enables precise control of test scenario setup
     * 🎯 Target: isCacheEnabled() logic branch
     * 🧱 Structural: Supports modular testing isolation
     */
    public function testingForceCache(bool $enabled): void
    {
        // dd("testingForceCache", $enabled);
        $this->testCacheFlag = $enabled;
    }

    /**
     * ⛓️ Oracular Hook: Inject TTL for cache logic in test environment
     *
     * This method injects a TTL override value that replaces the Laravel
     * config-based TTL in test environments. Essential for running UCM tests
     * outside of the application context.
     *
     * 🔐 Used only in test scaffolding
     * 🔄 Alters the TTL retrieval logic
     *
     * @param int $ttl Override value for cache TTL
     * @configurable
     * @mutation
     * @test
     */
    public function testingForceCacheTtl(int $ttl): void
    {
        $this->testCacheTtl = $ttl;
    }

    /**
     * ⛓️ Oracular Resolution: Determine effective cache TTL
     *
     * Returns the TTL for config cache depending on test overrides.
     * Avoids calling Laravel's config() in non-container environments.
     *
     * 🧩 Branches on test state
     * 🔄 Modifies cache persistence logic
     *
     * @return int Effective TTL to use in cache layer
     * @configurable
     * @mutation
     * @signature loadConfig_uses_cache_when_enabled
     * @test
     */
    private function getCacheTtl(): int
    {
        return $this->testCacheTtl ?? 3600;
    }

    /**
     * ⛓️ Oracular Decision Gateway: Cache Strategy Resolution
     * Returns whether cache should be used based on testing override
     * or default Laravel configuration fallback.
     *
     * 🔐 Conditional source: testCacheFlag or config('uconfig.cache.enabled')
     * 🧠 Branching logic controlling cache path
     * 🧷 Fallback logic: defaults to true if config not available
     */
    private function isCacheEnabled(): bool
    {
        // dd('isCacheEnabled', $this->testCacheFlag);
        return $this->testCacheFlag ?? true;
    }

    /**
     * 🧷 Fallback Loader: Merge environment variables into in-memory config
     *
     * Iterates over the raw environment (`$_ENV`) and injects all keys
     * not already present in `$this->config`, preserving database priority.
     *
     * This method is used to ensure that environment-defined configuration
     * values are never lost, while still allowing override from persistent storage.
     *
     * 🔐 Used at boot as secondary config source
     * 🧪 Silent by design, but traceable if logging is enabled
     * 🧱 Structural fallback layer for config merge logic
     * 🔄 Mutates in-memory `$this->config`
     *
     * @return void
     */
    private function loadFromEnv(): void
    {
        foreach ($_ENV as $key => $value) {
            if (!array_key_exists($key, $this->config)) {
                $this->config[$key] = ['value' => $value];
            }
        }

        UltraLog::debug('UCM Action', 'Environment variables merged into configurations');

    }

    /**
     * 🔄 Configuration Bootstrap Loader
     *
     * Entry point for hydration of in-memory config. It determines whether
     * to use the cache or to rehydrate the configuration directly from the
     * database and environment. Also logs the method of retrieval.
     *
     * 🧠 Decides strategy via `isCacheEnabled()`
     * 🧩 Uses TTL determined by `getCacheTtl()`
     * 🧪 Supports test override paths for both logic branches
     * 🧷 Internally safe for Fake DAO and test environment bypass
     * 🧱 Mutates `$this->config` as primary result
     * 📦 May retrieve data from Laravel cache layer
     *
     * @return void
     * @entrypoint
     * @mutation
     * @cache
     * @fallback
     */
    public function loadConfig(): void
    {
        UltraLog::info('UCM Action', 'Loading configurations');

        $useCache = $this->isCacheEnabled();
        $cacheKey = self::CACHE_KEY;
        $ttl = $this->getCacheTtl();

        if ($useCache && app()->bound('cache')) {
            $cached = Cache::get($cacheKey);

            if (is_array($cached) && !empty($cached)) {
                $this->config = $cached;
                UltraLog::debug('UCM Action', 'Configurations loaded from cache');
            } else {
                $this->config = $this->loadFromDatabase();
                $this->loadFromEnv();
                Cache::put($cacheKey, $this->config, $ttl);
                UltraLog::debug('UCM Action', "Configurations loaded fresh and cached for TTL: {$ttl}");
            }
        } else {
            $this->config = $this->loadFromDatabase();
            $this->loadFromEnv();
            UltraLog::debug('UCM Action', 'Configurations loaded without cache');
        }
    }

    /**
     * ⛓️ Oracular Behavior: Load configurations from database
     *
     * Retrieves all persisted configurations from the DB and maps them to the
     * in-memory format. Handles missing table and null values gracefully.
     *
     * ⚠️ Laravel's `Schema::hasTable()` is a facade that requires the application
     * container and a bound DB connection. In test environments (e.g., CLI PHPUnit),
     * facades may not be initialized, resulting in a `Facade root not set` exception.
     * To prevent this, we check `app()->bound('db')` and `app()->bound('schema')`
     * before any access to facades inside closures.
     *
     * 🧷 Used as fallback when cache is disabled
     * 🔐 Safe for test execution without Laravel container
     * 🧱 Part of loadConfig() flow
     * 🧪 Covered by: loadConfig_pulls_from_database_when_cache_disabled
     * 🚨 Handles: table absence, DAO failure, test isolation
     *
     * @return array<string, array<string, mixed>> In-memory configuration map
     */
    private function loadFromDatabase(): array
    {
        UltraLog::info('UCM Action', 'Loading configurations from database');

        $configArray = [];

        // 🔐 Test-safe bypass for fakes and sandbox environments
        if ($this->configDao->shouldBypassSchemaChecks()) {
            UltraLog::debug('UCM Action', 'DAO requested schema bypass (likely test)');
        } elseif (!app()->bound('db') || !app()->bound('schema') || !Schema::hasTable('uconfig')) {
            UltraLog::warning('UCM Action', "Schema or DB not available, or 'uconfig' table missing – skipping database load");
            return $configArray;
        }

        try {
            $configs = $this->configDao->getAllConfigs();

            foreach ($configs as $config) {
                if ($config->value !== null) {
                    $configArray[$config->key] = [
                        'value' => $config->value,
                        'category' => $config->category,
                    ];
                } else {
                    UltraLog::warning('UCM Action', "Configuration with key {$config->key} has a null value and will be ignored");
                }
            }

            UltraLog::info('UCM Action', 'Configurations loaded from database successfully');
        } catch (\Exception $e) {
            UltraLog::error('UCM Action', "Error loading configurations from database: {$e->getMessage()}");
        }

        // 🔄 Commit loaded config to memory
        $this->config = $configArray;
        return $configArray;
    }

    /**
     * Determine if a given config key exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key, null, true) !== null;
    }


    /**
     * 🎯 Retrieve a configuration value from memory
     *
     * Accesses a config value by key from the in-memory `$this->config` map,
     * falling back to a default if the key is not found.
     *
     * Does **not** trigger database or cache access – expects hydration via `loadConfig()`.
     * Logs are suppressed if `app()->bound('log')` is false (common in test CLI).
     *
     * 🧠 Silent mode disables all log output
     * 🔐 Safe for CLI test execution (no facades unless bound)
     * 🧪 Covered by: loadConfig_uses_cache_when_enabled
     * 📦 Expects `$this->config` to be already populated
     *
     * @param string $key The configuration key to retrieve
     * @param mixed $default Default value if key is missing
     * @param bool $silent If true, suppresses log output
     * @return mixed Configuration value or default
     *
     * @readonly
     * @resolver
     * @log
     */
    public function get(string $key, mixed $default = null, bool $silent = false): mixed
    {
        $value = $this->config[$key]['value'] ?? $default;

        // 🧷 Log only if log facade is available (test-mode safe)
        if (!$silent && app()->bound('log')) {
            if ($value === $default) {
                UltraLog::info('UCM Action', "Config key '{$key}' not found. Using default: " . json_encode($default));
            } else {
                UltraLog::debug('UCM Action', "Retrieved config key '{$key}' with value: " . json_encode($value));
            }
        }

        return $value;
    }

    /**
     * Set a configuration value.
     *
     * Updates the in-memory configuration, persists it to the database, and optionally records
     * a version and audit entry. Ensures input validation and transactional integrity.
     *
     * @param string $key The configuration key (alphanumeric with _ . -).
     * @param mixed $value The value to set (scalar, array, or null).
     * @param string|null $category The category of the configuration (optional).
     * @param object|null $user The user performing the action (optional).
     * @param bool $version Whether to record a version entry (default: true).
     * @param bool $audit Whether to record an audit entry (default: true).
     * @throws \InvalidArgumentException If key or value is invalid.
     * @throws \Exception If the operation fails.
     * @return void
     */
    public function set(string $key, mixed $value, ?string $category = null, ?object $user = null, bool $version = true, bool $audit = true): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $key)) {
            UltraLog::error('UCM Action', "Invalid configuration key: {$key}");
            throw new \InvalidArgumentException("Configuration key must be alphanumeric with allowed characters: _ . -");
        }
        if (!is_scalar($value) && !is_null($value) && !is_array($value)) {
            UltraLog::error('UCM Action', "Invalid configuration value type for key: {$key}");
            throw new \InvalidArgumentException("Configuration value must be scalar, array, or null");
        }

        try {
            $this->config[$key] = ['value' => $value, 'category' => $category];
            $config = $this->saveToUConfig($key, $value, $category);
            if ($config) {
                if ($version) $this->saveVersion($config, $value);
                if ($audit) $this->saveAudit($config, 'updated', $value);
            } else {
                throw new \RuntimeException("Failed to persist configuration {$key}");
            }
            $this->refreshConfigCache($key);
            UltraLog::info('UCM Action', "Configuration set successfully: {$key}");
        } catch (\Exception $e) {
            UltraLog::error('UCM Action', "Failed to set configuration {$key}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Save a configuration to the database.
     *
     * Persists the configuration entry, creating or updating it as needed, with logging.
     *
     * @param string $key The configuration key.
     * @param mixed $value The configuration value.
     * @param string|null $category The configuration category.
     * @return UltraConfigModel|null The saved model instance or null on failure.
     */
    private function saveToUConfig(string $key, mixed $value, ?string $category): ?UltraConfigModel
    {
        try {
            $config = $this->configDao->getConfigByKey($key);
            $data = ['value' => $value, 'category' => $category];

            if ($config) {
                if ($config->trashed()) $config->restore();
                $config = $this->configDao->updateConfig($config, $data);
            } else {
                $data['key'] = $key;
                $config = $this->configDao->createConfig($data);
            }

            UltraLog::info('UCM Action', "Configuration saved to database: {$key}");
            return $config;
        } catch (\Exception $e) {
            UltraLog::error('UCM Action', "Error saving configuration {$key} to database: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Save a version entry for a configuration.
     *
     * Records a new version of the configuration with the provided value.
     *
     * @param UltraConfigModel $config The configuration model instance.
     * @param mixed $value The value to record.
     * @return void
     */
    private function saveVersion(UltraConfigModel $config, mixed $value): void
    {
        try {
            $this->configDao->createVersion($config, $this->versionManager->getNextVersion($config->id));
            UltraLog::info('UCM Action', "Version recorded for configuration: {$config->key}");
        } catch (\Exception $e) {
            UltraLog::error('UCM Action', "Error registering version for configuration {$config->key}: {$e->getMessage()}");
        }
    }


    /**
     * Save an audit entry for a configuration change.
     *
     * Logs the action performed on the configuration with old and new values.
     *
     * @param UltraConfigModel $config The configuration model instance.
     * @param string $action The action type (e.g., 'created', 'updated', 'deleted').
     * @param mixed $newValue The new value of the configuration.
     * @return void
     */
    private function saveAudit(UltraConfigModel $config, string $action, mixed $newValue): void
    {
        try {
            $oldValue = $this->get($config->key);
            $this->configDao->createAudit(
                $config->id,
                $action,
                $oldValue,
                $newValue,
                Auth::id() ?? $this->globalConstants::NO_USER
            );
            UltraLog::info('UCM Action', "Audit recorded for action {$action} on configuration: {$config->key}");
        } catch (\Exception $e) {
            UltraLog::error('UCM Action', "Error registering audit for configuration {$config->key}: {$e->getMessage()}");
        }
    }


    /**
     * Delete a configuration.
     *
     * Removes the configuration from memory and database (soft delete), with optional versioning and audit.
     *
     * @param string $key The configuration key to delete.
     * @param bool $version Whether to record a version entry (default: true).
     * @param bool $audit Whether to record an audit entry (default: true).
     * @return void
     */
    public function delete(string $key, bool $version = true, bool $audit = true): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $key)) {
            UltraLog::error('UCM Action', "Invalid configuration key for deletion: {$key}");
            throw new \InvalidArgumentException("Configuration key must be alphanumeric with allowed characters: _ . -");
        }

        unset($this->config[$key]);
        $config = $this->configDao->getConfigByKey($key);

        if ($config) {
            try {
                if ($audit) $this->saveAudit($config, 'deleted', null);
                if ($version) $this->saveVersion($config, null);
                $this->configDao->deleteConfig($config);
                $this->refreshConfigCache($key);
                UltraLog::info('UCM Action', "Configuration deleted: {$key}");
            } catch (\Exception $e) {
                UltraLog::error('UCM Action', "Error deleting configuration {$key}: {$e->getMessage()}");
                throw $e;
            }
        } else {
            UltraLog::warning('UCM Action', "No configuration found to delete for key: {$key}");
        }
    }

    /**
     * Retrieve all configuration values.
     *
     * Returns an array of all configuration values currently in memory.
     *
     * @return array<string, mixed> The configuration values.
     */
    public function all(): array
    {
        return array_map(fn($config) => $config['value'], $this->config);
    }

    /**
     * Refresh the configuration cache.
     *
     * Updates the cache with the latest in-memory configurations, using a lock to prevent race conditions.
     *
     * @return void
     * @throws \Exception If cache refresh fails.
     */
    public function refreshConfigCache(?string $key = null): void
    {
        $lock = Cache::lock('ultra_config_cache_lock', 10);
        try {
            if ($lock->get()) {
                if ($key) {
                    $config = $this->configDao->getConfigByKey($key);
                    $cachedConfig = Cache::get(self::CACHE_KEY, []);
                    if ($config) {
                        $cachedConfig[$key] = [
                            'value' => $config->value,
                            'category' => $config->category,
                        ];
                    } else {
                        unset($cachedConfig[$key]);
                    }
                    Cache::forever(self::CACHE_KEY, $cachedConfig);
                    UltraLog::info('UCM Action', "Incremental cache refresh for key: {$key}");
                } else {
                    $this->config = $this->loadFromDatabase();
                    Cache::forever(self::CACHE_KEY, $this->config);
                    UltraLog::info('UCM Action', 'Full configuration cache refreshed successfully');
                }
            } else {
                UltraLog::warning('UCM Action', 'Failed to acquire lock for cache refresh');
            }
        } catch (\Exception $e) {
            UltraLog::error('UCM Action', "Error refreshing cache: {$e->getMessage()}");
            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * 🔄 Reload configuration bypassing cache
     *
     * Forces a direct reload of all configurations from their primary
     * sources: database and environment. Useful in tests or emergency
     * scenarios when cache must be ignored.
     *
     * 🧪 Test-safe: avoids facades or Eloquent directly
     * 🧱 Uses same logic path as `loadConfig()` without caching
     * 🧷 Allows recovery from invalid state
     *
     * @return void
     */
    public function reload(): void
    {
        $this->config = $this->loadFromDatabase();
        $this->loadFromEnv();
    }


    /**
     * Validate that the given constant is defined in GlobalConstants.
     *
     * @param  string  $name
     * @throws \InvalidArgumentException if the constant is not defined
     * @return void
     */
    public function validateConstant(string $name): void
    {
        if (!defined(GlobalConstants::class . '::' . $name)) {
            UltraLog::warning('UCM Validation', "Invalid constant: {$name}");
            throw new \InvalidArgumentException("Invalid constant: {$name}");
        }
    }

}
