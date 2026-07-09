<?php

namespace App\Casts;

use App\Services\Encryption\Aes256GcmEncrypter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * @implements CastsAttributes<string|null, string|null>
 */
class Aes256GcmEncrypted implements CastsAttributes
{
    /**
     * Cast the given value from database storage back to application state.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = (string) $value;

        // 1. Check if it's a standard Laravel framework JSON-serialized envelope (Row 2 pattern)
        if (str_starts_with($stringValue, 'ey')) {
            try {
                // Use Laravel's Crypt facade to decrypt - it handles key derivation correctly
                return Crypt::decrypt($stringValue);
            } catch (\Throwable) {
                // Decryption failed - the data was encrypted with a different key
                // Return null instead of the encrypted string
                return null;
            }
        }

        // 2. Default Fallback: Process using custom unpacking binary architecture (Row 1 pattern)
        try {
            return Aes256GcmEncrypter::fromConfiguration()->decrypt($stringValue);
        } catch (\Throwable) {
            // Return null if decryption fails completely
            return null;
        }
    }

    /**
     * Prepare the given value for database storage.
     * * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [$key => null];
        }

        // Match true base64 payload characteristics securely
        if (is_string($value) && preg_match('/^[a-zA-Z0-9\/+]+={0,2}$/', $value) && strlen($value) % 4 === 0) {
            // Double check if it decrypts successfully. If it does, it's already encrypted!
            try {
                Aes256GcmEncrypter::fromConfiguration()->decrypt($value);
                return [$key => $value];
            } catch (\Throwable) {
                // Processing failed; it's plain text that just looked like base64
            }
        }

        $encrypted = Aes256GcmEncrypter::fromConfiguration()->encrypt((string) $value);

        return [$key => $encrypted];
    }
}
