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
 * UltraConfigManager - Centralized and secure configuration management class.
 *
 * This class provides a robust interface for managing configurations in the Ultra ecosystem,
 * with features like encryption, versioning, audit logging, and secure caching. It ensures
 * that all configuration operations are validated, logged, and persisted safely.
 */
class UltraConfigManager
{
    /**
     * In-memory configuration array.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $config = [];

    /**
     * Global constants used across the system.
     *
     * @var GlobalConstants
     */
    protected GlobalConstants $globalConstants;

    /**
     * Version manager for handling configuration versioning.
     *
     * @var VersionManager
     */
    protected VersionManager $versionManager;

    /**
     * Cache key used to store configurations.
     *
     * @var string
     */
    private const CACHE_KEY = 'ultra_config.cache';

    /**
     * ConfigDao instance for database operations.
     *
     * @var ConfigDaoInterface
     */
    protected ConfigDaoInterface $configDao;

    /**
     * Constructor.
     *
     * Initializes the configuration manager with environment loader, constants, and version manager.
     * Loads configurations on instantiation.
     *
     * @param GlobalConstants $globalConstants Global constants for the system.
     * @param VersionManager $versionManager Manager for configuration versions.
     * @param ConfigDaoInterface $configDao Data access object for configuration operations.
     */
    public function __construct(
        GlobalConstants $globalConstants,
        VersionManager $versionManager,
        ConfigDaoInterface $configDao
    ) {
        $this->globalConstants = $globalConstants;
        $this->versionManager = $versionManager;
        $this->configDao = $configDao;
        $this->loadConfig();
        UltraLog::info('UCM Action', 'UltraConfigManager initialized');
    }

    /**
     * Load configurations from environment variables.
     *
     * Merges environment variables into the in-memory configuration array, skipping duplicates.
     *
     * @return void
     */

     private function loadFromEnv(): void
     {
         $envConfig = $_ENV; // Usa direttamente $_ENV
         foreach ($envConfig as $key => $value) {
             if (!array_key_exists($key, $this->config)) {
                 $this->config[$key] = ['value' => $value];
             }
         }
         UltraLog::debug('UCM Action', 'Environment variables merged into configurations');
     }

    /**
     * Load all configurations into memory.
     *
     * Loads configurations from database and environment variables, using cache if enabled.
     * Logs the operation for traceability.
     *
     * @return void
     */
    public function loadConfig(): void
    {
        UltraLog::info('UCM Action', 'Loading configurations');
        $useCache = config('uconfig.cache.enabled', true);

        if ($useCache) {
            $ttl = config('uconfig.cache.ttl', 3600);
            $this->config = Cache::remember(self::CACHE_KEY, $ttl, function () {
                $this->loadFromDatabase();
                $this->loadFromEnv();
                return $this->config;
            });
            UltraLog::debug('UCM Action', "Configurations loaded from cache with TTL: {$ttl}");
        } else {
            $this->loadFromDatabase();
            $this->loadFromEnv();
            UltraLog::debug('UCM Action', 'Configurations loaded without cache');
        }
    }


    /**
     * Load configurations from the database.
     *
     * Retrieves all configurations from the 'uconfig' table and populates the in-memory array.
     *
     * @return array<string, array<string, mixed>> The loaded configurations.
     */
    private function loadFromDatabase(): array
    {
        $configArray = [];
        if (!Schema::hasTable('uconfig')) {
            UltraLog::warning('UCM Action', "The 'uconfig' table does not exist");
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
     * Retrieve a configuration value.
     *
     * Fetches a value by key from the in-memory configuration, falling back to a default if not found.
     *
     * @param string $key The configuration key to retrieve.
     * @param mixed $default The default value if the key is not found.
     * @param bool $silent If true, suppresses logging for missing keys or tables.
     * @return mixed The configuration value or default.
     */
    public function get(string $key, mixed $default = null, bool $silent = false): mixed
    {
        if (!Schema::hasTable('uconfig')) {
            if (!$silent) {
                UltraLog::warning('UCM Action', "The 'uconfig' table does not exist. Returning default: " . json_encode($default));
            }
            return $default;
        }

        if (empty($this->config)) {
            $this->config = Cache::get(self::CACHE_KEY, []);
            if (!$silent) {
                UltraLog::debug('UCM Action', "Loaded configurations from cache for key: {$key}");
            }
        }

        $value = $this->config[$key]['value'] ?? $default;

        if ($value === $default && !$silent) {
            UltraLog::info('UCM Action', "Config key '{$key}' not found. Using default: " . json_encode($default));
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
     * Reloads the configuration cache directly from the database.
     *
     * Useful for tests or emergency reboots.
     */
    public function reload(): void
    {
        $this->config = UltraConfigModel::all()->keyBy('key')->toArray();
    }
}