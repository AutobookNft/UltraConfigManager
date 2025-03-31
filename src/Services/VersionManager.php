<?php

namespace Ultra\UltraConfigManager\Services;

use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraLogManager\Facades\UltraLog;
use Illuminate\Database\QueryException;

/**
 * VersionManager - Handles versioning of configurations in the Ultra ecosystem.
 *
 * This class provides methods to manage version numbers for configuration entries,
 * ensuring that each change is tracked with an incremental version.
 */
class VersionManager
{
    /**
     * Get the next version number for a configuration.
     *
     * Calculates the next version number by finding the highest existing version
     * for the given configuration ID and incrementing it.
     *
     * @param int $configId The ID of the configuration.
     * @return int The next version number.
     * @throws \InvalidArgumentException If the config ID is invalid.
     * @throws \Exception If there is a database error.
     */
    public function getNextVersion(int $configId): int
    {
        try {
            // Validate that the ID is positive
            if ($configId <= 0) {
                UltraLog::error('UCM Action', "Invalid config ID for versioning: {$configId}");
                throw new \InvalidArgumentException("The configuration ID must be a positive integer.");
            }

            // Get the highest version for the specific configuration
            $latestVersion = UltraConfigVersion::where('uconfig_id', $configId)->max('version');

            // Increment by one (returns 1 if no versions exist)
            $nextVersion = $latestVersion ? $latestVersion + 1 : 1;
            UltraLog::debug('UCM Action', "Next version for config ID {$configId}: {$nextVersion}");
            return $nextVersion;
        } catch (QueryException $e) {
            UltraLog::error('UCM Action', "Database error calculating version for config_id {$configId}: {$e->getMessage()}");
            throw new \Exception("Error calculating version. Please try again later.");
        } catch (\Exception $e) {
            UltraLog::error('UCM Action', "Generic error calculating version: {$e->getMessage()}");
            throw $e;
        }
    }
}