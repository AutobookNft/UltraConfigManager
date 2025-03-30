<?php

namespace Ultra\UltraConfigManager\Models;

use Ultra\UltraConfigManager\Casts\EncryptedCast;
use Ultra\UltraLogManager\Facades\UltraLog;
use Illuminate\Database\Eloquent\Model;

/**
 * UltraConfigVersion - Version history entry for configurations in the Ultra ecosystem.
 *
 * This model tracks historical versions of configuration values, maintaining a secure
 * and encrypted record of changes over time.
 *
 * @property int $id The unique identifier of the version entry.
 * @property int $uconfig_id The ID of the related configuration.
 * @property int $version The version number of this entry.
 * @property string|null $key The configuration key.
 * @property string|null $category The category of the configuration.
 * @property string|null $note Additional notes about the version.
 * @property string $value The encrypted value of the configuration.
 * @property \Illuminate\Support\Carbon|null $created_at Creation timestamp.
 * @property \Illuminate\Support\Carbon|null $updated_at Last update timestamp.
 */
class UltraConfigVersion extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'uconfig_versions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'uconfig_id',
        'version',
        'key',
        'category',
        'note',
        'value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => EncryptedCast::class,
    ];

    /**
     * Get the configuration this version belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uconfig()
    {
        return $this->belongsTo(UltraConfigModel::class, 'uconfig_id');
    }

    /**
     * Boot the model and add versioning protections.
     *
     * Ensures that version entries are logged and validated during creation.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uconfig_id) || $model->version < 1) {
                UltraLog::error('UCM Action', "Invalid version entry: uconfig_id: {$model->uconfig_id}, version: {$model->version}");
                throw new \InvalidArgumentException('Version entry requires a valid uconfig_id and version >= 1');
            }
            if (empty($model->key)) {
                UltraLog::error('UCM Action', "Attempt to create version without key for uconfig_id: {$model->uconfig_id}");
                throw new \InvalidArgumentException('Version key cannot be empty');
            }
            UltraLog::info('UCM Action', "Version entry created for uconfig_id: {$model->uconfig_id}, version: {$model->version}");
        });
    }
}