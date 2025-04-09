<?php

namespace Ultra\UltraConfigManager\Dao;

use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Illuminate\Support\Collection;

/**
 * 🧱 ConfigDaoInterface
 *
 * Contract for accessing and manipulating configuration data within the UCM module.
 * Defines all semantic operations supported by DAOs, including CRUD, versioning, and auditing.
 *
 * 🧪 Used in both production and test doubles
 * 📦 Consumed by UltraConfigManager and related services
 * 🔐 Provides extensible access to persistent or mock config sources
 */
interface ConfigDaoInterface
{
    /**
     * ⛓️ Retrieve all configuration entries.
     *
     * @return Collection<UltraConfigModel>
     */
    public function getAllConfigs(): Collection;

    /**
     * 🕵️‍♀️ Retrieve a configuration by its ID.
     *
     * @param int $id
     * @return UltraConfigModel
     */
    public function getConfigById(int $id): UltraConfigModel;

    /**
     * 🕵️‍♀️ Retrieve a configuration by its unique key.
     *
     * @param string $key
     * @return UltraConfigModel
     */
    public function getConfigByKey(string $key): UltraConfigModel;

    /**
     * 🔄 Create a new configuration entry.
     *
     * @param array $data
     * @return UltraConfigModel
     */
    public function createConfig(array $data): UltraConfigModel;

    /**
     * 🔄 Update an existing configuration.
     *
     * @param UltraConfigModel $config
     * @param array $data
     * @return UltraConfigModel
     */
    public function updateConfig(UltraConfigModel $config, array $data): UltraConfigModel;

    /**
     * 🔥 Soft-delete a configuration and register an audit entry.
     *
     * @param UltraConfigModel $config The model to delete.
     * @param int|null $userId ID of the user performing the deletion.
     * @return void
     * @throws \Exception On failure to delete or audit
     */
    public function deleteConfig(UltraConfigModel $config, ?int $userId = null): void;

    /**
     * 🧬 Create a new version entry for a configuration.
     *
     * @param UltraConfigModel $config
     * @param int $version
     * @return UltraConfigVersion
     */
    public function createVersion(UltraConfigModel $config, int $version): UltraConfigVersion;

    /**
     * 🧠 Get the latest version number for a configuration.
     *
     * @param int $configId
     * @return int
     */
    public function getLatestVersion(int $configId): int;

    /**
     * 🪵 Create an audit log entry for a config mutation.
     *
     * @param int $configId
     * @param string $action
     * @param string|null $oldValue
     * @param string|null $newValue
     * @param int|null $userId
     * @return UltraConfigAudit
     */
    public function createAudit(int $configId, string $action, ?string $oldValue, ?string $newValue, ?int $userId): UltraConfigAudit;

    /**
     * 🧾 Retrieve all audit entries for a given configuration.
     *
     * @param int $configId
     * @return Collection<UltraConfigAudit>
     */
    public function getAuditsByConfigId(int $configId): Collection;

    /**
     * 🔐 Determine if schema checks should be bypassed (e.g. in test fakes).
     *
     * Enables DAO to signal that Schema::hasTable and other runtime DB assertions should be skipped.
     * This improves test safety and removes the need for test-specific instanceof logic in core classes.
     *
     * @return bool True if schema checks should be skipped.
     */
    public function shouldBypassSchemaChecks(): bool;
}
