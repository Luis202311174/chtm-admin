<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\Encryption\Aes256GcmEncrypter;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Restore emails from Supabase Auth data, encrypted with the current key.
     */
    public function up(): void
    {
        $encrypter = Aes256GcmEncrypter::fromConfiguration();
        
        // Map of user IDs to their emails (from Supabase Auth)
        $userEmails = [
            '60cb8d87-0097-4d6c-940d-c30e404f4bcd' => 'shinkohero@gmail.com',
            '23a08633-38a2-4b95-926d-ea9af49ce7f2' => 'siagordoncollege@gmail.com',
            '65d4f763-c25d-49e9-9791-01bc25df3ebd' => 'chtmadmin@gmail.com',
            '06e9e861-2aec-4121-8903-39ced9904679' => '202310508@gordoncollege.edu.ph',
            'ba444ea6-bb98-4024-ae0d-510fa3b10b43' => 'chtmfront@mail.com',
            '3e222f72-735a-4ba5-86dc-302c42c0e484' => 'chtmhk@mail.com',
            '3fdbc133-a252-44df-a5b2-ef19047919c9' => 'chtmreserv@mail.com',
            '8af6dc57-02a4-41db-937f-dccc6c78068c' => '202310951@gordoncollege.edu.ph',
            'b87deb15-8b04-46c1-b3d1-2b878541d34a' => 'cjbugero@gmail.com',
            'f28cf179-08e9-44cc-93df-06f683ef35ac' => 'kenjitv06@gmail.com',
        ];
        
        foreach ($userEmails as $userId => $email) {
            $emailHash = User::hashEmail($email);
            $encryptedEmail = $encrypter->encrypt($email);
            
            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'email' => $encryptedEmail,
                    'email_hash' => $emailHash,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset emails to NULL
        DB::table('users')->update(['email' => null, 'email_hash' => null]);
    }
};