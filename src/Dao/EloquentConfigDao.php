<?php

namespace Ultra\UltraConfigManager\Dao;

use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Ultra\UltraLogManager\Facades\UltraLog;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\ErrorManager\Facades\TestingConditions;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

/**
 * EloquentConfigDao
 *
 * This is the default implementation of ConfigDaoInterface, based on Eloquent ORM.
 * It handles all persistence logic for configuration entries, including versioning and audit trails.
 */
class EloquentConfigDao implements ConfigDaoInterface
{
    /**
     * Retrieve all configuration entries.
     *
     * @return Collection<UltraConfigModel>
     */
    public function getAllConfigs(): Collection
    {
        try {
            return UltraConfigModel::all();
        } catch (\Exception $e) {
            UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'operation' => 'getAllConfigs',
            ], $e, true);
        }

        throw new \LogicException('Unreachable code in getAllConfigs');
    }


    /**
     * Retrieve a configuration by its ID.
     *
     * If TestingConditions::isTesting('UCM_NOT_FOUND') is enabled,
     * or if the config is not found in the database, the call will
     * be intercepted by UltraError::handle(), which will throw
     * an UltraErrorException if `throw => true` is active.
     *
     * @param int $id
     * @return UltraConfigModel
     */
    public function getConfigById(int $id): UltraConfigModel
    {
        if (TestingConditions::isTesting('UCM_NOT_FOUND')) {
            UltraError::handle('UCM_NOT_FOUND', ['id' => $id], new \Exception("Simulated UCM_NOT_FOUND"), true);
        }

        try {
            return UltraConfigModel::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            UltraError::handle('UCM_NOT_FOUND', ['id' => $id], $e, true);
        } catch (\Exception $e) {
            UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'operation' => 'getConfigById',
                'id' => $id,
            ], $e, true);
        }

        // Chiusura esplicita per completezza e analizzatori statici
        throw new \LogicException('Unreachable code in getConfigById');
    }

    /**
     * Retrieve a configuration entry by its unique key.
     *
     * Includes optional test error simulation.
     *
     * @param string $key
     * @return UltraConfigModel
     */
    public function getConfigByKey(string $key): UltraConfigModel
    {
        try {
            if (empty($key)) {
                return UltraError::handle('INVALID_INPUT', ['param' => 'key'], new \Exception('Missing key'));
            }

            if (TestingConditions::isTesting('UCM_NOT_FOUND')) {
                UltraLog::info('UCM DAO', 'Simulating UCM_NOT_FOUND error', ['key' => $key]);
                UltraError::handle('UCM_NOT_FOUND', ['key' => $key], new \Exception("Simulated"), true); // throw = true
            }

            $config = UltraConfigModel::where('key', $key)->firstOrFail();
            UltraLog::info('UCM DAO', "Retrieved configuration with key: {$key}");
            return $config;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return UltraError::handle('UCM_NOT_FOUND', ['key' => $key], $e);
        } catch (\Exception $e) {
            UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'operation' => 'getConfigByKey',
                'key' => $key,
            ], $e);
            throw $e;
        }
    }

    /**
     * Create a new configuration entry.
     *
     * @param array $data
     * @return UltraConfigModel
     */
    public function createConfig(array $data): UltraConfigModel
    {
        
        if (TestingConditions::isTesting('UCM_DUPLICATE_KEY')) {
            UltraLog::info('UCM DAO', 'Simulating duplicate key', ['key' => $data['key']]);
            UltraError::handle('UCM_DUPLICATE_KEY', ['key' => $data['key']], new \Exception("Simulated"), true);
        }
        try {
            return DB::transaction(function () use ($data) {
                $config = UltraConfigModel::create($data);
                UltraLog::info('UCM DAO', "Created configuration with key: {$config->key}");
                return $config;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                UltraError::handle('UCM_DUPLICATE_KEY', ['key' => $data['key']], $e, true);
            }

            UltraError::handle('UCM_CREATE_FAILED', ['key' => $data['key'], 'message' => $e->getMessage()], $e, true);
        } catch (\Exception $e) {
            UltraError::handle('UCM_CREATE_FAILED', ['key' => $data['key'], 'message' => $e->getMessage()], $e, true);
        }

        // Safeguard: all code paths must return or throw
        throw new \LogicException('Unreachable code in createConfig');
    }


   /**
     * Update a configuration entry.
     *
     * @param UltraConfigModel $config
     * @param array $data
     * @return UltraConfigModel
     */
    public function updateConfig(UltraConfigModel $config, array $data): UltraConfigModel
    {
        try {
            if (TestingConditions::isTesting('UCM_UPDATE_FAILED')) {
                UltraLog::info('UCM DAO', 'Simulating update failure', ['key' => $config->key]);
                UltraError::handle('UCM_UPDATE_FAILED', ['key' => $config->key], new \Exception("Simulated"), true);
            }

            return DB::transaction(function () use ($config, $data) {
                $config->update($data);
                UltraLog::info('UCM DAO', "Updated configuration with key: {$config->key}");
                return $config;
            });
        } catch (\Exception $e) {
            UltraError::handle('UCM_UPDATE_FAILED', [
                'key' => $config->key,
                'message' => $e->getMessage(),
            ], $e, true);
        }

        throw new \LogicException('Unreachable code in updateConfig');
    }

    /**
     * Delete a configuration entry.
     *
     * @param UltraConfigModel $config
     * @return void
     */
    public function deleteConfig(UltraConfigModel $config): void
    {
        try {
            if (TestingConditions::isTesting('UCM_DELETE_FAILED')) {
                UltraLog::info('UCM DAO', 'Simulating deletion failure', ['key' => $config->key]);
                UltraError::handle('UCM_DELETE_FAILED', ['key' => $config->key], new \Exception("Simulated"), true);
            }

            DB::transaction(function () use ($config) {
                $config->delete();
                UltraLog::info('UCM DAO', "Deleted configuration with key: {$config->key}");
            });
        } catch (\Exception $e) {
            UltraError::handle('UCM_DELETE_FAILED', [
                'key' => $config->key,
                'message' => $e->getMessage(),
            ], $e, true);
        }

        throw new \LogicException('Unreachable code in deleteConfig');
    }

   /**
     * Create a new version of the configuration entry.
     *
     * @param UltraConfigModel $config
     * @param int $version
     * @return UltraConfigVersion
     */
    public function createVersion(UltraConfigModel $config, int $version): UltraConfigVersion
    {
        try {
            return DB::transaction(function () use ($config, $version) {
                $versionRecord = UltraConfigVersion::create([
                    'uconfig_id' => $config->id,
                    'version' => $version,
                    'key' => $config->key,
                    'category' => $config->category,
                    'note' => $config->note,
                    'value' => $config->value,
                ]);
                UltraLog::info('UCM DAO', "Created version {$version} for configuration with key: {$config->key}");
                return $versionRecord;
            });
        } catch (\Exception $e) {
            UltraError::handle('UCM_CREATE_FAILED', [
                'key' => $config->key,
                'message' => $e->getMessage(),
            ], $e, true);
        }

        throw new \LogicException('Unreachable code in createVersion');
    }


    /**
     * Get the latest version number for the given configuration.
     *
     * If no version exists for the given configId, the internal error manager
     * will handle the error and may throw an UltraErrorException.
     *
     * Note: Although this method does not explicitly throw exceptions, it relies
     * on UltraError::handle(..., throw: true), which may interrupt flow.
     * Make sure to catch exceptions when calling this method if error handling is critical.
     *
     * @param int $configId Configuration identifier
     * @return int Latest version number, or 0 if none found
     */
    public function getLatestVersion(int $configId): int
    {
        try {
            $latestVersion = UltraConfigVersion::where('uconfig_id', $configId)->max('version');

            if (is_null($latestVersion)) {
                UltraError::handle('UCM_VERSION_NOT_FOUND', [
                    'config_id' => $configId,
                ], new \Exception("No version found"), true);
            }

            return $latestVersion;
        } catch (\Exception $e) {
            UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'operation' => 'getLatestVersion',
                'config_id' => $configId,
            ], $e, true);
        }

        throw new \LogicException('Unreachable code in getLatestVersion');
    }



    /**
     * Create an audit log entry for a config change.
     *
     * @param int $configId
     * @param string $action
     * @param string|null $oldValue
     * @param string|null $newValue
     * @param int|null $userId
     * @return UltraConfigAudit
     */
    public function createAudit(int $configId, string $action, ?string $oldValue, ?string $newValue, ?int $userId): UltraConfigAudit
    {
        try {
            return DB::transaction(function () use ($configId, $action, $oldValue, $newValue, $userId) {
                $audit = UltraConfigAudit::create([
                    'uconfig_id' => $configId,
                    'action' => $action,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'user_id' => $userId,
                ]);
                UltraLog::info('UCM DAO', "Created audit entry for config_id: {$configId}, action: {$action}");
                return $audit;
            });
        } catch (\Exception $e) {
            UltraError::handle('UCM_CREATE_FAILED', [
                'message' => $e->getMessage(),
                'configId' => $configId,
            ], $e, true);
        }

        throw new \LogicException('Unreachable code in createAudit');
    }


   /**
     * Retrieve all audit logs for a given configuration.
     *
     * @param int $configId
     * @return Collection<UltraConfigAudit>
     */
    public function getAuditsByConfigId(int $configId): Collection
    {
        try {
            return UltraConfigAudit::where('uconfig_id', $configId)->get();
        } catch (\Exception $e) {
            UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'configId' => $configId,
            ], $e, true);
        }

        throw new \LogicException('Unreachable code in getAuditsByConfigId');
    }

}
