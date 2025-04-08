<?php

namespace Ultra\UltraConfigManager\Facades;

use Illuminate\Support\Facades\Facade;
use Ultra\UltraConfigManager\UltraConfigManager;
use Ultra\UltraConfigManager\Models\UltraConfigModel;

/**
 * Facade for UltraConfigManager
 *
 * Provides a typed interface for managing system-wide configurations,
 * with support for caching, versioning, audit logging and validation.
 */
class UConfig extends Facade
{
    /**
     * Get the registered name of the component in the service container.
     *
     * @return string
     */
// TODO: Add semantic annotations (@param, @return) to 'getFacadeAccessor'
    protected static function getFacadeAccessor(): string
    {
        return 'uconfig';
    }

    /**
     * Check if a configuration key exists.
     *
     * @param string $key
     * @return bool
     */
/**
 * TODO: [UDP] Describe purpose of 'has'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
    public static function has(string $key): bool
    {
        return static::getFacadeRoot()->has($key);
    }

    /**
     * Retrieve a configuration value.
     *
     * @param string $key
     * @param mixed $default
     * @param bool $silent
     * @return mixed
     */
/**
 * TODO: [UDP] Describe purpose of 'get'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
    public static function get(string $key, mixed $default = null, bool $silent = false): mixed
    {
        return static::getFacadeRoot()->get($key, $default, $silent);
    }

    /**
     * Set a configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $category
     * @param object|null $user
     * @param bool $version
     * @param bool $audit
     * @return void
     */
/**
 * TODO: [UDP] Describe purpose of 'set'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
    public static function set(string $key, mixed $value, ?string $category = null, ?object $user = null, bool $version = true, bool $audit = true): void
    {
        static::getFacadeRoot()->set($key, $value, $category, $user, $version, $audit);
    }

    /**
     * Delete a configuration key.
     *
     * @param string $key
     * @param bool $version
     * @param bool $audit
     * @return void
     */
/**
 * TODO: [UDP] Describe purpose of 'delete'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
    public static function delete(string $key, bool $version = true, bool $audit = true): void
    {
        static::getFacadeRoot()->delete($key, $version, $audit);
    }

    /**
     * Get all configuration values.
     *
     * @return array<string, mixed>
     */
// TODO: Add semantic annotations (@param, @return) to 'all'
    public static function all(): array
    {
        return static::getFacadeRoot()->all();
    }

    /**
     * Load the configuration into memory.
     *
     * @return void
     */
// TODO: Add semantic annotations (@param, @return) to 'loadConfig'
    public static function loadConfig(): void
    {
        static::getFacadeRoot()->loadConfig();
    }

    /**
     * Refresh the cached configuration.
     *
     * @param string|null $key
     * @return void
     */
/**
 * TODO: [UDP] Describe purpose of 'refreshConfigCache'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
    public static function refreshConfigCache(?string $key = null): void
    {
        static::getFacadeRoot()->refreshConfigCache($key);
    }

    /**
     * Reload configuration directly from the database.
     *
     * @return void
     */
// TODO: Add semantic annotations (@param, @return) to 'reload'
    public static function reload(): void
    {
        static::getFacadeRoot()->reload();
    }

    /**
     * Validate a constant from GlobalConstants.
     *
     * @param string $name
     * @return void
     *
     * @throws \InvalidArgumentException
     */
/**
 * TODO: [UDP] Describe purpose of 'validateConstant'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
    public static function validateConstant(string $name): void
    {
        static::getFacadeRoot()->validateConstant($name);
    }
}
