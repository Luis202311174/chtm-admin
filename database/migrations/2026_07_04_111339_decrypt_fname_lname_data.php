<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\Encryption\Aes256GcmEncrypter;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Decrypts existing fname/lname columns from AES-256-GCM back to plaintext.
     */
    public function up(): void
    {
        $encrypter = Aes256GcmEncrypter::fromConfiguration();

        // Process users table
        DB::table('users')->orderBy('id')->chunk(100, function ($users) use ($encrypter) {
            foreach ($users as $user) {
                $updates = [];

                // Decrypt fname if it looks encrypted (starts with base64 chars)
                if (!empty($user->fname) && !str_contains($user->fname, '@')) {
                    try {
                        $decrypted = $encrypter->decrypt($user->fname);
                        if ($decrypted !== null && $decrypted !== $user->fname) {
                            $updates['fname'] = $decrypted;
                        }
                    } catch (\Throwable $e) {
                        // Already plaintext, leave as-is
                    }
                }

                // Decrypt lname if it looks encrypted
                if (!empty($user->lname) && !str_contains($user->lname, '@')) {
                    try {
                        $decrypted = $encrypter->decrypt($user->lname);
                        if ($decrypted !== null && $decrypted !== $user->lname) {
                            $updates['lname'] = $decrypted;
                        }
                    } catch (\Throwable $e) {
                        // Already plaintext, leave as-is
                    }
                }

                if (!empty($updates)) {
                    $updates['updated_at'] = now();
                    DB::table('users')->where('id', $user->id)->update($updates);
                }
            }
        });

        // Process archived_bookings table
        DB::table('archived_bookings')->orderBy('id')->chunk(100, function ($bookings) use ($encrypter) {
            foreach ($bookings as $booking) {
                $updates = [];

                if (!empty($booking->guest_fname) && !str_contains($booking->guest_fname, '@')) {
                    try {
                        $decrypted = $encrypter->decrypt($booking->guest_fname);
                        if ($decrypted !== null && $decrypted !== $booking->guest_fname) {
                            $updates['guest_fname'] = $decrypted;
                        }
                    } catch (\Throwable $e) {}
                }

                if (!empty($booking->guest_lname) && !str_contains($booking->guest_lname, '@')) {
                    try {
                        $decrypted = $encrypter->decrypt($booking->guest_lname);
                        if ($decrypted !== null && $decrypted !== $booking->guest_lname) {
                            $updates['guest_lname'] = $decrypted;
                        }
                    } catch (\Throwable $e) {}
                }

                if (!empty($updates)) {
                    DB::table('archived_bookings')->where('id', $booking->id)->update($updates);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     * This is destructive - we cannot re-encrypt as we no longer store the original encrypted values.
     */
    public function down(): void
    {
        // No reversal possible - data is now plaintext
    }
};