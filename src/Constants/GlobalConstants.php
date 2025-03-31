<?php

namespace Ultra\UltraConfigManager\Constants;

use Ultra\UltraLogManager\Facades\UltraLog;

/**
 * GlobalConstants - Defines global constants for the UltraConfigManager system.
 *
 * This class provides a centralized set of constants used across the UltraConfigManager
 * package, ensuring consistency and security in configuration management operations.
 */
class GlobalConstants
{
    /**
     * Constant for representing an unknown or anonymous user ID.
     *
     * Used in audit and version logs when no authenticated user is present.
     *
     * @var int
     */
    public const NO_USER = 0;

    /**
     * Default permission for reading configurations.
     *
     * This is the baseline permission required for accessing configuration data.
     *
     * @var string
     */
    public const DEFAULT_PERMISSION = 'read-config';

    /**
     * Role identifier for administrative users.
     *
     * Users with this role have elevated privileges within the UltraConfigManager system.
     *
     * @var string
     */
    public const ADMIN_ROLE = 'admin';

    /**
     * Get the value of a constant by name.
     *
     * Provides a safe way to access constants with logging for invalid requests.
     *
     * @param string $name The name of the constant to retrieve.
     * @param mixed $default The default value if the constant is not found.
     * @return mixed The constant value or default if not found.
     */
    public static function getConstant(string $name, mixed $default = null): mixed
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        if (array_key_exists($name, $constants)) {
            return $constants[$name];
        }

        UltraLog::warning('UCM Action', "Attempted to access undefined constant: {$name}");
        return $default;
    }

    /**
     * Validate the usage of a constant.
     *
     * Ensures that a constant exists and logs any misuse attempts.
     *
     * @param string $name The name of the constant to validate.
     * @throws \InvalidArgumentException If the constant does not exist.
     * @return void
     */
    public static function validateConstant(string $name): void
    {
        $reflection = new \ReflectionClass(self::class);
        if (!array_key_exists($name, $reflection->getConstants())) {
            UltraLog::error('UCM Action', "Invalid constant accessed: {$name}");
            throw new \InvalidArgumentException("Constant {$name} does not exist in GlobalConstants");
        }
    }
}