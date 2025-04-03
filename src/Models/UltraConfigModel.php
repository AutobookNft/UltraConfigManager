<?php

namespace Ultra\UltraConfigManager\Models;

use Ultra\UltraConfigManager\Casts\EncryptedCast;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraLogManager\Facades\UltraLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * UltraConfigModel - Represents a configuration entry in the Ultra ecosystem.
 *
 * This model handles the storage and retrieval of configuration key-value pairs,
 * with encryption, versioning, and audit support. It uses soft deletes to ensure
 * historical data is preserved.
 *
 * @property int $id The unique identifier of the configuration.
 * @property string $key The unique key identifying the configuration.
 * @property string|null $value The encrypted value of the configuration.
 * @property string|null $category The category of the configuration (optional).
 * @property string|null $note Additional notes about the configuration (optional).
 * @property \Illuminate\Support\Carbon|null $created_at Creation timestamp.
 * @property \Illuminate\Support\Carbon|null $updated_at Last update timestamp.
 * @property \Illuminate\Support\Carbon|null $deleted_at Soft delete timestamp.
 */
class UltraConfigModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'uconfig';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'key',
        'value',
        'category',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => EncryptedCast::class,
        'category' => CategoryEnum::class, // Cast al nostro Enum
    ];

    /**
     * Get the versions associated with this configuration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versions()
    {
        return $this->hasMany(UltraConfigVersion::class, 'uconfig_id');
    }

    /**
     * Set the configuration key attribute with validation.
     *
     * Ensures the key adheres to strict formatting rules to prevent invalid or
     * malicious input from being stored.
     *
     * @param string $value The key to set.
     * @throws \InvalidArgumentException If the key format is invalid.
     * @return void
     */
    public function setKeyAttribute($value)
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $value)) {
            UltraLog::error('UCM Action', "Invalid configuration key format: {$value}");
            throw new \InvalidArgumentException("Configuration key must be alphanumeric with allowed characters: _ . -");
        }
        $this->attributes['key'] = $value;
    }

    /**
     * Boot the model and add global scope protections.
     *
     * This method ensures that only valid configurations are manipulated and logs
     * any anomalies during the model's lifecycle.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Log creation attempts for auditing
        static::creating(function ($model) {
            if (empty($model->key)) {
                UltraLog::error('UCM Action', 'Attempt to create configuration without a key');
                throw new \InvalidArgumentException('Configuration key cannot be empty');
            }
            UltraLog::info('UCM Action', "Creating configuration with key: {$model->key}");
        });

        // Log updates for traceability
        static::updating(function ($model) {
            UltraLog::info('UCM Action', "Updating configuration with key: {$model->key}");
        });

        /**
         * 🔐 Protection of the `key` field
         *
         * Hook on model save to prevent changes to the `key` field after creation.
         *
         * ✔ During creation (`$model->exists === false`), the `key` can be set.
         * ❌ After creation, any attempt to modify it triggers a `LogicException`.
         *
         * ✅ Goal:
         *   - Maintain the logical integrity of the configuration.
         *   - Avoid traceability issues with versioning and auditing.
         *   - Prevent "silent" changes that could corrupt dependencies.
         *
         * 🪵 Logging:
         *   - Every blocked attempt is logged with `UltraLog::debug()` for traceability.
         *
         * 📚 See also:
         *   - UCMModelTest.php → Automated test to ensure the behavior.
         *
         * 🏷️ Tags:
         *   #laravel #model-hook #immutability #ucm #logic-guard #ultraecosystem
         */
        static::saving(function ($model) {
            if (!$model->exists && $model->isDirty('key')) {
                // Allow setting key on first save
                return;
            }
        
            if ($model->isDirty('key')) {
                UltraLog::debug('UCM Model', 'Saving model', [
                    'exists' => $model->exists,
                    'dirty' => $model->getDirty(),
                    'wasRecentlyCreated' => $model->wasRecentlyCreated,
                ]);
                throw new \LogicException('Configuration key cannot be modified after creation');
            }
        });
        
    }
}