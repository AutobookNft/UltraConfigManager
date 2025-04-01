<?php

namespace Ultra\UltraConfigManager\Constants;

use Ultra\UltraLogManager\Facades\UltraLog;

/**
 * GlobalConstants
 *
 * Defines global constants for the UltraConfigManager system and provides
 * safe accessor methods to retrieve or validate constant values.
 *
 * This class acts as a centralized definition point for shared values across
 * the UCM ecosystem and logs improper access attempts.
 */
class GlobalConstants
{
    /**
     * Represents an unknown or anonymous user ID.
     *
     * Used in audit logs and version tracking when no authenticated user is available.
     *
     * @var int
     */
    public const NO_USER = 0;

    /**
     * Default configuration category when none is provided.
     *
     * Helps avoid hardcoded strings in multiple places.
     *
     * @var string
     */
    public const DEFAULT_CATEGORY = 'general';

    /**
     * Retrieve the value of a constant by name.
     *
     * This method checks if the given constant exists in the class and returns its value.
     * If it does not exist, it logs a warning (unless suppressed) and returns a default fallback value.
     *
     * @param string $name     The name of the constant to retrieve
     * @param mixed  $default  The fallback value if the constant is not defined
     * @param bool   $silent   If true, suppress logging when constant is not found
     * @return mixed           The constant value or the fallback default
     */
    public static function getConstant(string $name, mixed $default = null, bool $silent = false): mixed
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        if (array_key_exists($name, $constants)) {
            return $constants[$name];
        }

        if (!$silent) {
            UltraLog::warning('UCM Action', "Attempted to access undefined constant: {$name}");
        }

        return $default;
    }

    /**
     * Validate if a constant exists and throw an exception if not.
     *
     * This method ensures that only defined constants are used and logs
     * any invalid attempts. In case of failure, the exception message
     * includes a suggestion list of valid constants.
     *
     * @param string $name  The name of the constant to validate
     * @throws \InvalidArgumentException if the constant is not defined
     * @return void
     */
    public static function validateConstant(string $name): void
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        if (!array_key_exists($name, $constants)) {
            UltraLog::error('UCM Action', "Invalid constant accessed: {$name}");

            $valid = implode(', ', array_keys($constants));
            throw new \InvalidArgumentException("Constant {$name} does not exist in GlobalConstants. Valid options are: [{$valid}]");
        }
    }
}
