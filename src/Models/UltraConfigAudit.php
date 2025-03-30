<?php

namespace Ultra\UltraConfigManager\Models;

use Ultra\UltraConfigManager\Casts\EncryptedCast;
use Ultra\UltraLogManager\Facades\UltraLog;
use Illuminate\Database\Eloquent\Model;

/**
 * UltraConfigAudit - Audit log entry for configuration changes in the Ultra ecosystem.
 *
 * This model records every action (create, update, delete) performed on configurations,
 * ensuring full traceability with encrypted old and new values.
 *
 * @property int $id The unique identifier of the audit entry.
 * @property int|null $uconfig_id The ID of the related configuration.
 * @property string|null $action The action performed (e.g., 'created', 'updated', 'deleted').
 * @property string|null $old_value The previous value of the configuration (encrypted).
 * @property string|null $new_value The new value of the configuration (encrypted).
 * @property int|null $user_id The ID of the user who performed the action.
 * @property \Illuminate\Support\Carbon|null $created_at Creation timestamp.
 * @property \Illuminate\Support\Carbon|null $updated_at Last update timestamp.
 */
class UltraConfigAudit extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'uconfig_audit';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'uconfig_id',
        'action',
        'new_value',
        'old_value',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_value' => EncryptedCast::class,
        'new_value' => EncryptedCast::class,
    ];

    /**
     * Get the user who performed the action.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        return $this->belongsTo($userModel, 'user_id')->withDefault(['name' => 'Unknown User']);
    }

    /**
     * Boot the model and add auditing protections.
     *
     * Ensures that audit entries are logged and validated during creation.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->action)) {
                UltraLog::error('UCM Action', "Attempt to create audit entry without action for uconfig_id: {$model->uconfig_id}");
                throw new \InvalidArgumentException('Audit action cannot be empty');
            }
            if (!in_array($model->action, ['created', 'updated', 'deleted'])) {
                UltraLog::error('UCM Action', "Invalid audit action: {$model->action} for uconfig_id: {$model->uconfig_id}");
                throw new \InvalidArgumentException("Audit action must be 'created', 'updated', or 'deleted'");
            }
            UltraLog::info('UCM Action', "Audit entry created for uconfig_id: {$model->uconfig_id}, action: {$model->action}");
        });
    }
}