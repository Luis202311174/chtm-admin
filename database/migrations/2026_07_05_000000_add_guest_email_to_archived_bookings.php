<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add guest_email column to archived_bookings table
        DB::statement("
            ALTER TABLE public.archived_bookings 
            ADD COLUMN IF NOT EXISTS guest_email TEXT
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE public.archived_bookings 
            DROP COLUMN IF EXISTS guest_email
        ");
    }
};