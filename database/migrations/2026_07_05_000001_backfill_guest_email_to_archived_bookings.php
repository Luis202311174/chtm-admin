<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ArchivedBooking;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfill guest_email for existing archived bookings by looking up the user's email.
     */
    public function up(): void
    {
        // Get all archived bookings that have a user_id but no guest_email
        $archivedBookings = ArchivedBooking::query()
            ->whereNotNull('user_id')
            ->whereNull('guest_email')
            ->get();

        foreach ($archivedBookings as $booking) {
            $user = User::find($booking->user_id);
            if ($user && $user->email) {
                $booking->update(['guest_email' => $user->email]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the guest_email column
        DB::table('archived_bookings')->update(['guest_email' => null]);
    }
};