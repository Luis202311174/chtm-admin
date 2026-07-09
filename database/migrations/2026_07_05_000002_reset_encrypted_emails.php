<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Reset encrypted emails to null since they were encrypted with a different key.
     * Users will need to re-enter their emails.
     */
    public function up(): void
    {
        // Reset all encrypted emails to null - they were encrypted with a different key
        // and cannot be decrypted. Users will need to re-enter their emails.
        DB::table('users')->update(['email' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore the original encrypted values
    }
};