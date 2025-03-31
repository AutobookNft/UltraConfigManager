<?php

namespace Ultra\UltraConfigManager\Dao;

use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;

interface ConfigDaoInterface
{
    /**
     * Recupera tutte le configurazioni.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllConfigs(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Recupera una configurazione per ID.
     *
     * @param int $id
     * @return UltraConfigModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getConfigById(int $id): UltraConfigModel;

    /**
     * Crea una nuova configurazione.
     *
     * @param array $data
     * @return UltraConfigModel
     */
    public function createConfig(array $data): UltraConfigModel;

    /**
     * Aggiorna una configurazione esistente.
     *
     * @param UltraConfigModel $config
     * @param array $data
     * @return UltraConfigModel
     */
    public function updateConfig(UltraConfigModel $config, array $data): UltraConfigModel;

    /**
     * Elimina una configurazione (soft delete).
     *
     * @param UltraConfigModel $config
     * @return void
     */
    public function deleteConfig(UltraConfigModel $config): void;

    /**
     * Crea una nuova versione per una configurazione.
     *
     * @param UltraConfigModel $config
     * @param int $version
     * @return UltraConfigVersion
     */
    public function createVersion(UltraConfigModel $config, int $version): UltraConfigVersion;

    /**
     * Recupera l'ultima versione per una configurazione.
     *
     * @param int $configId
     * @return int
     */
    public function getLatestVersion(int $configId): int;

    /**
     * Crea un nuovo record di audit.
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
     * Recupera tutti i record di audit per una configurazione.
     *
     * @param int $configId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAuditsByConfigId(int $configId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Recupera una configurazione per chiave.
     *
     * @param string $key
     * @return UltraConfigModel|null
     */
    public function getConfigByKey(string $key): ?UltraConfigModel;

}