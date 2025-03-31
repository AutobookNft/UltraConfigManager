<?php

namespace Ultra\UltraConfigManager\Dao;

use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Ultra\UltraLogManager\Facades\UltraLog;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\ErrorManager\Facades\TestingConditions;
use Illuminate\Support\Facades\DB;

class EloquentConfigDao implements ConfigDaoInterface
{
    public function getAllConfigs(): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return UltraConfigModel::all();
        } catch (\Exception $e) {
            return UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'operation' => 'getAllConfigs',
            ], $e);
        }
    }

    public function getConfigById(int $id): UltraConfigModel
    {
        try {
            // Simulazione errore UCM_NOT_FOUND
            if (TestingConditions::isTesting('UCM_NOT_FOUND')) {
                UltraLog::info('UCM DAO', 'Simulating UCM_NOT_FOUND error', [
                    'test_condition' => 'UCM_NOT_FOUND',
                    'id' => $id,
                ]);
                $simulatedException = new \Exception("Simulated UCM_NOT_FOUND for testing");
                return UltraError::handle('UCM_NOT_FOUND', [
                    'id' => $id,
                ], $simulatedException);
            }

            return UltraConfigModel::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return UltraError::handle('UCM_NOT_FOUND', [
                'id' => $id,
            ], $e);
        } catch (\Exception $e) {
            return UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'operation' => 'getConfigById',
                'id' => $id,
            ], $e);
        }
    }

    public function getConfigByKey(string $key): UltraConfigModel
    {
        try {
            // Validazione dell'input
            if (empty($key) || !is_string($key)) {
                $exception = new \Exception('Invalid or missing key');
                return UltraError::handle('INVALID_INPUT', [
                    'param' => 'key',
                    'value' => $key,
                ], $exception);
            }

            // Simulazione errore UCM_NOT_FOUND
            if (TestingConditions::isTesting('UCM_NOT_FOUND')) {
                UltraLog::info('UCM DAO', 'Simulating UCM_NOT_FOUND error', [
                    'test_condition' => 'UCM_NOT_FOUND',
                    'key' => $key,
                ]);
                $simulatedException = new \Exception("Simulated UCM_NOT_FOUND for testing");
                return UltraError::handle('UCM_NOT_FOUND', [
                    'key' => $key,
                ], $simulatedException);
            }

            // Recupera la configurazione per chiave
            $config = UltraConfigModel::where('key', $key)->firstOrFail();
            UltraLog::info('UCM DAO', "Retrieved configuration with key: {$key}");
            return $config;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return UltraError::handle('UCM_NOT_FOUND', [
                'key' => $key,
            ], $e);
        } catch (\Exception $e) {
            return UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'operation' => 'getConfigByKey',
                'key' => $key,
            ], $e);
        }
    }

    public function createConfig(array $data): UltraConfigModel
    {
        try {
            // Simulazione errore UCM_DUPLICATE_KEY
            if (TestingConditions::isTesting('UCM_DUPLICATE_KEY')) {
                UltraLog::info('UCM DAO', 'Simulating UCM_DUPLICATE_KEY error', [
                    'test_condition' => 'UCM_DUPLICATE_KEY',
                    'key' => $data['key'],
                ]);
                $simulatedException = new \Exception("Simulated UCM_DUPLICATE_KEY for testing");
                return UltraError::handle('UCM_DUPLICATE_KEY', [
                    'key' => $data['key'],
                ], $simulatedException);
            }

            return DB::transaction(function () use ($data) {
                $config = UltraConfigModel::create($data);
                UltraLog::info('UCM DAO', "Created configuration with key: {$config->key}");
                return $config;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return UltraError::handle('UCM_DUPLICATE_KEY', [
                    'key' => $data['key'],
                ], $e);
            }
            return UltraError::handle('UCM_CREATE_FAILED', [
                'key' => $data['key'],
                'message' => $e->getMessage(),
                'operation' => 'createConfig',
            ], $e);
        } catch (\Exception $e) {
            return UltraError::handle('UCM_CREATE_FAILED', [
                'key' => $data['key'],
                'message' => $e->getMessage(),
                'operation' => 'createConfig',
            ], $e);
        }
    }

    public function updateConfig(UltraConfigModel $config, array $data): UltraConfigModel
    {
        try {
            // Simulazione errore UCM_UPDATE_FAILED
            if (TestingConditions::isTesting('UCM_UPDATE_FAILED')) {
                UltraLog::info('UCM DAO', 'Simulating UCM_UPDATE_FAILED error', [
                    'test_condition' => 'UCM_UPDATE_FAILED',
                    'key' => $config->key,
                ]);
                $simulatedException = new \Exception("Simulated UCM_UPDATE_FAILED for testing");
                return UltraError::handle('UCM_UPDATE_FAILED', [
                    'key' => $config->key,
                ], $simulatedException);
            }

            return DB::transaction(function () use ($config, $data) {
                $config->update($data);
                UltraLog::info('UCM DAO', "Updated configuration with key: {$config->key}");
                return $config;
            });
        } catch (\Exception $e) {
            return UltraError::handle('UCM_UPDATE_FAILED', [
                'key' => $config->key,
                'message' => $e->getMessage(),
                'operation' => 'updateConfig',
            ], $e);
        }
    }

    public function deleteConfig(UltraConfigModel $config): void
    {
        try {
            // Simulazione errore UCM_DELETE_FAILED
            if (TestingConditions::isTesting('UCM_DELETE_FAILED')) {
                UltraLog::info('UCM DAO', 'Simulating UCM_DELETE_FAILED error', [
                    'test_condition' => 'UCM_DELETE_FAILED',
                    'key' => $config->key,
                ]);
                $simulatedException = new \Exception("Simulated UCM_DELETE_FAILED for testing");
                UltraError::handle('UCM_DELETE_FAILED', [
                    'key' => $config->key,
                ], $simulatedException);
                return; // Interrompiamo l'esecuzione senza restituire un valore
            }

            DB::transaction(function () use ($config) {
                $config->delete();
                UltraLog::info('UCM DAO', "Deleted configuration with key: {$config->key}");
            });
        } catch (\Exception $e) {
            UltraError::handle('UCM_DELETE_FAILED', [
                'key' => $config->key,
                'message' => $e->getMessage(),
                'operation' => 'deleteConfig',
            ], $e);
        }
    }

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
            return UltraError::handle('UCM_CREATE_FAILED', [
                'key' => $config->key,
                'message' => $e->getMessage(),
                'operation' => 'createVersion',
            ], $e);
        }
    }

    public function getLatestVersion(int $configId): int
    {
        try {
            $latestVersion = UltraConfigVersion::where('uconfig_id', $configId)->max('version');
            return $latestVersion ? $latestVersion : 0;
        } catch (\Exception $e) {
            return UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'operation' => 'getLatestVersion',
                'configId' => $configId,
            ], $e);
        }
    }

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
            return UltraError::handle('UCM_CREATE_FAILED', [
                'message' => $e->getMessage(),
                'operation' => 'createAudit',
                'configId' => $configId,
            ], $e);
        }
    }

    public function getAuditsByConfigId(int $configId): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return UltraConfigAudit::where('uconfig_id', $configId)->get();
        } catch (\Exception $e) {
            return UltraError::handle('UNEXPECTED_ERROR', [
                'message' => $e->getMessage(),
                'operation' => 'getAuditsByConfigId',
                'configId' => $configId,
            ], $e);
        }
    }
}