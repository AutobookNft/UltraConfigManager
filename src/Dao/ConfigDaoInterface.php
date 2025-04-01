<?php

namespace Ultra\UltraConfigManager\Dao;

use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Illuminate\Database\Eloquent\Collection;

/**
 * ConfigDaoInterface
 *
 * Defines the expected contract for configuration data access.
 * Each method corresponds directly to the logic used within EloquentConfigDao,
 * supporting retrieval, creation, update, versioning, and auditing.
 */
interface ConfigDaoInterface
{
    /**
     * Retrieve all configuration entries.
     *
     * @return Collection<UltraConfigModel>
     */
    public function getAllConfigs(): Collection;

    /**
     * Retrieve a configuration by its ID.
     *
     * @param int $id
     * @return UltraConfigModel
     */
    public function getConfigById(int $id): UltraConfigModel;

    /**
     * Retrieve a configuration by its unique key.
     *
     * @param string $key
     * @return UltraConfigModel
     */
    public function getConfigByKey(string $key): UltraConfigModel;

    /**
     * Create a new configuration entry.
     *
     * @param array $data
     * @return UltraConfigModel
     */
    public function createConfig(array $data): UltraConfigModel;

    /**
     * Update an existing configuration.
     *
     * @param UltraConfigModel $config
     * @param array $data
     * @return UltraConfigModel
     */
    public function updateConfig(UltraConfigModel $config, array $data): UltraConfigModel;

    /**
     * Delete a configuration entry.
     *
     * @param UltraConfigModel $config
     * @return void
     */
    public function deleteConfig(UltraConfigModel $config): void;

    /**
     * Create a new version of a configuration.
     *
     * @param UltraConfigModel $config
     * @param int $version
     * @return UltraConfigVersion
     */
    public function createVersion(UltraConfigModel $config, int $version): UltraConfigVersion;

    /**
     * Get the latest version number for a configuration.
     *
     * @param int $configId
     * @return int
     */
    public function getLatestVersion(int $configId): int;

    /**
     * Create an audit log entry for a configuration change.
     *
     * @param int         $configId
     * @param string      $action
     * @param string|null $oldValue
     * @param string|null $newValue
     * @param int|null    $userId
     * @return UltraConfigAudit
     */
    public function createAudit(int $configId, string $action, ?string $oldValue, ?string $newValue, ?int $userId): UltraConfigAudit;

    /**
     * Retrieve all audits for a given configuration.
     *
     * @param int $configId
     * @return Collection<UltraConfigAudit>
     */
    public function getAuditsByConfigId(int $configId): Collection;
}
