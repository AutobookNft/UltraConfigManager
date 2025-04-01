<?php

namespace Ultra\UltraConfigManager\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * EncryptedCast
 *
 * This class is responsible for automatically encrypting and decrypting attribute values
 * when they are stored in or retrieved from the database using Eloquent models.
 *
 * Usage:
 * In your Eloquent model, use:
 * protected $casts = [
 *     'sensitive_field' => EncryptedCast::class,
 * ];
 */
class EncryptedCast implements CastsAttributes
{
    /**
     * Retrieve the decrypted value from the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model   The model instance
     * @param  string                               $key     The attribute name
     * @param  mixed                                $value   The stored value
     * @param  array                                $attributes The raw attribute array
     * @return mixed|null                           The decrypted value or original if not encrypted
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // If decryption fails (e.g., plain text), return the original value
            return $value;
        }
    }

    /**
     * Encrypt the value before storing it in the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model   The model instance
     * @param  string                               $key     The attribute name
     * @param  mixed                                $value   The value to be encrypted
     * @param  array                                $attributes The current model attributes
     * @return mixed|null                           The encrypted value or null
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString($value);
    }
}
