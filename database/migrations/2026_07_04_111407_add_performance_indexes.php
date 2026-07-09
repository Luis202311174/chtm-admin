<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Disable transaction wrapping since Supabase pooled connections
     * don't support CREATE/DROP INDEX within transactions.
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     * Note: Supabase pooled connections don't have CREATE INDEX permission.
     * If this fails, run the SQL manually in Supabase Dashboard → SQL Editor.
     */
    public function up(): void
    {
        $statements = [
            'CREATE INDEX IF NOT EXISTS idx_bookings_status ON public.bookings (status)',
            'CREATE INDEX IF NOT EXISTS idx_bookings_start_at ON public.bookings (start_at)',
            'CREATE INDEX IF NOT EXISTS idx_bookings_end_at ON public.bookings (end_at)',
            'CREATE INDEX IF NOT EXISTS idx_bookings_room_id ON public.bookings (room_id)',
            'CREATE INDEX IF NOT EXISTS idx_bookings_room_status_dates ON public.bookings (room_id, status, start_at, end_at)',
            'CREATE INDEX IF NOT EXISTS idx_rooms_status ON public.rooms (status)',
            'CREATE INDEX IF NOT EXISTS idx_users_role ON public.users (role)',
            'CREATE INDEX IF NOT EXISTS idx_archived_bookings_status ON public.archived_bookings (status)',
            'CREATE INDEX IF NOT EXISTS idx_archived_bookings_user_id ON public.archived_bookings (user_id)',
        ];

        foreach ($statements as $sql) {
            try {
                DB::connection('pgsql')->statement($sql);
            } catch (\Throwable $e) {
                echo "  ⚠ " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $statements = [
            'DROP INDEX IF EXISTS idx_bookings_status',
            'DROP INDEX IF EXISTS idx_bookings_start_at',
            'DROP INDEX IF EXISTS idx_bookings_end_at',
            'DROP INDEX IF EXISTS idx_bookings_room_id',
            'DROP INDEX IF EXISTS idx_bookings_room_status_dates',
            'DROP INDEX IF EXISTS idx_rooms_status',
            'DROP INDEX IF EXISTS idx_users_role',
            'DROP INDEX IF EXISTS idx_archived_bookings_status',
            'DROP INDEX IF EXISTS idx_archived_bookings_user_id',
        ];

        foreach ($statements as $sql) {
            try {
                DB::connection('pgsql')->statement($sql);
            } catch (\Throwable $e) {
                echo "  ⚠ " . $e->getMessage() . "\n";
            }
        }
    }
};