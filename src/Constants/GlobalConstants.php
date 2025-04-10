<?php

/**
 * 📜 Oracode Constants: GlobalConstants
 *
 * @package         Ultra\UltraConfigManager\Constants
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Constants;

use InvalidArgumentException; // PHP Standard Exception
use ReflectionClass; // Per introspection delle costanti

/**
 * 🎯 Purpose: Defines globally accessible constant values used throughout the
 *    UltraConfigManager package. Provides a centralized, single source of truth for
 *    magic numbers or strings, improving maintainability and readability. Includes
 *    helper methods for safe retrieval and validation of defined constants.
 *
 * 🧱 Structure: Contains public constants (`NO_USER`, `DEFAULT_CATEGORY`).
 *    Provides static methods `getConstant` and `validateConstant` using reflection.
 *
 * 🧩 Context: Used by various components within UCM (e.g., `UltraConfigManager`, DAOs, Models)
 *    to refer to standard values consistently.
 *
 * 🛠️ Usage: `GlobalConstants::NO_USER`, `GlobalConstants::validateConstant('NO_USER')`.
 *
 * 💾 State: Stateless. Holds only constant definitions.
 *
 * 🗝️ Key Constants:
 *    - `NO_USER`: Represents the ID for an unknown or system user (typically 0).
 *    - `DEFAULT_CATEGORY`: Fallback category identifier (e.g., 'general', could align with CategoryEnum::None).
 *      ORCD: Nota Padmin: Valutare se DEFAULT_CATEGORY è ancora necessario o se usare CategoryEnum::None.value direttamente. Per ora mantenuto come nell'originale.
 *
 * 🚦 Signals:
 *    - `getConstant`: Returns constant value or default.
 *    - `validateConstant`: Returns void on success, throws `InvalidArgumentException` on failure.
 *
 * 🛡️ Privacy (GDPR): Constants themselves are typically non-sensitive identifiers.
 *    - `@privacy-safe`: Constants defined here are not considered PII.
 *
 * 🤝 Dependencies: None beyond standard PHP Reflection API.
 *
 * 🧪 Testing:
 *    - Unit test `getConstant` for defined and undefined constants, checking return values.
 *    - Unit test `validateConstant` ensuring it throws `InvalidArgumentException` for invalid names and does nothing for valid names.
 *
 * 💡 Logic: Uses PHP's Reflection API to dynamically access constants, making the helper
 *    methods automatically aware of any new constants added to the class without needing updates.
 *
 * @package Ultra\UltraConfigManager\Constants
 */
final class GlobalConstants // Class marked as final as it only contains constants and static methods
{
    /**
     * 👤 Identifier for an unknown, anonymous, or system user.
     * Used in audit/version logs when a specific user context is unavailable.
     * @var int
     */
    public const NO_USER = 0;

    /**
     * 🏷️ Default configuration category identifier (if needed as fallback).
     * Consider using `CategoryEnum::None->value` instead if applicable.
     * @var string
     */
    public const DEFAULT_CATEGORY = 'general'; // Original value kept

    /**
     * 🚫 Private constructor to prevent instantiation of this utility class.
     */
    private function __construct()
    {
        // Cannot be instantiated.
    }

    /**
     * 📡 Safely retrieves the value of a defined constant by its name.
     * Uses reflection to access constants dynamically.
     *
     * @param string $name The case-sensitive name of the constant (e.g., 'NO_USER').
     * @param mixed $default The value to return if the constant is not defined. Defaults to null.
     *
     * @return mixed The value of the constant if found, otherwise the `$default` value.
     * @static
     * @readOperation Reads class constants.
     */
    public static function getConstant(string $name, mixed $default = null): mixed
    {
        try {
            $reflection = new ReflectionClass(self::class);
            // getConstants(ReflectionClassConstant::IS_PUBLIC) ensures only public constants
            $constants = $reflection->getConstants(\ReflectionClassConstant::IS_PUBLIC);

            return $constants[$name] ?? $default; // Return constant value or default

        } catch (\ReflectionException $e) {
             // Should not happen if self::class is valid
             error_log("Error reflecting GlobalConstants: " . $e->getMessage());
             return $default; // Fallback if reflection fails
        }
    }

    /**
     * ✅ Validates if a constant with the given name is defined in this class.
     * Throws an exception if the constant is not found. Uses reflection.
     *
     * @param string $name The case-sensitive name of the constant to validate.
     * @return void
     *
     * @throws InvalidArgumentException If the constant `$name` is not defined in `GlobalConstants`.
     * @static
     * @validation Checks constant existence.
     */
    public static function validateConstant(string $name): void
    {
        try {
            $reflection = new ReflectionClass(self::class);
            $constants = $reflection->getConstants(\ReflectionClassConstant::IS_PUBLIC);

            if (!array_key_exists($name, $constants)) {
                $valid = implode(', ', array_keys($constants));
                throw new InvalidArgumentException(
                    "Constant '{$name}' does not exist in GlobalConstants. Valid public constants are: [{$valid}]"
                );
            }
            // Constant exists, validation passes.
        } catch (\ReflectionException $e) {
             // Wrap reflection error in a standard exception
             throw new \RuntimeException("Error validating constants via reflection: " . $e->getMessage(), 0, $e);
        }
    }
}