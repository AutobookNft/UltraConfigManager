<?php

namespace Ultra\UltraConfigManager\Models;

use Ultra\UltraLogManager\Facades\UltraLog;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * User - Represents an authenticated user in the UltraConfigManager system.
 *
 * This model defines users who interact with configurations, providing authentication,
 * role-based permissions via Spatie, and audit tracking integration. It serves as
 * the default user model for UltraConfigManager's audit and versioning features.
 *
 * @property int $id The unique identifier of the user.
 * @property string $name The user's display name.
 * @property string $email The user's email address (unique).
 * @property string $password The hashed password for authentication.
 * @property string|null $remember_token Token for "remember me" functionality.
 * @property \Illuminate\Support\Carbon|null $created_at Creation timestamp.
 * @property \Illuminate\Support\Carbon|null $updated_at Last update timestamp.
 */
class User extends Authenticatable
{
    use Notifiable, HasRoles;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the audit entries created by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function audits()
    {
        return $this->hasMany(UltraConfigAudit::class, 'user_id');
    }

    /**
     * Get the configuration versions created by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versions()
    {
        return $this->hasMany(UltraConfigVersion::class, 'user_id');
    }

    /**
     * Boot the model and add security protections.
     *
     * Ensures that user creation and updates are logged and validated to prevent
     * unauthorized or malformed data from being stored.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->name) || empty($model->email)) {
                UltraLog::error('UCM Action', "Attempt to create user without name or email");
                throw new \InvalidArgumentException('User must have a name and email');
            }
            if (!filter_var($model->email, FILTER_VALIDATE_EMAIL)) {
                UltraLog::error('UCM Action', "Invalid email format for user: {$model->email}");
                throw new \InvalidArgumentException('User email must be a valid email address');
            }
            UltraLog::info('UCM Action', "Creating user: {$model->email}");
        });

        static::updating(function ($model) {
            if ($model->isDirty('email') && !filter_var($model->email, FILTER_VALIDATE_EMAIL)) {
                UltraLog::error('UCM Action', "Invalid email update attempt for user: {$model->email}");
                throw new \InvalidArgumentException('User email must remain a valid email address');
            }
            UltraLog::info('UCM Action', "Updating user: {$model->email}");
        });
    }
}