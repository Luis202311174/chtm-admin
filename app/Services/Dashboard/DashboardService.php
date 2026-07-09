<?php

namespace App\Services\Dashboard;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class DashboardService
{
    public function forUser(User $user): array
    {
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        // Cache room stats for 300 seconds — they don't change every request
        $roomStats = Cache::remember('dashboard.room_stats', 300, function () {
            return Room::query()
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'occupied' THEN 1 END) as occupied,
                    COUNT(CASE WHEN status = 'needs_cleaning' THEN 1 END) as needs_cleaning
                ")
                ->first();
        });

        $total = (int) ($roomStats->total ?? 0);
        $occupied = (int) ($roomStats->occupied ?? 0);
        $needsCleaning = (int) ($roomStats->needs_cleaning ?? 0);
        $available = max(0, $total - $occupied - $needsCleaning);

        // Cache occupied bookings for 120 seconds
        $occupiedBookings = Cache::remember('dashboard.occupied_bookings', 120, function () {
            return Booking::query()
                ->with(['user', 'room.roomType'])
                ->where('status', 'checked_in')
                ->get();
        });

        // Cache upcoming bookings for 120 seconds
        $upcomingBookings = Cache::remember('dashboard.upcoming_bookings', 120, function () use ($now) {
            return Booking::query()
                ->with(['user', 'room.roomType'])
                ->where('status', 'approved')
                ->where('start_at', '>', $now)
                ->orderBy('start_at')
                ->limit(8)
                ->get();
        });

        // Combine pending count and checkouts today into a single query
        $bookingCounts = Cache::remember('dashboard.booking_counts', 120, function () use ($todayStart, $todayEnd) {
            $pending = Booking::where('status', 'pending')->count();
            $checkouts = Booking::query()
                ->where('status', 'checked_in')
                ->whereBetween('end_at', [$todayStart, $todayEnd])
                ->count();
            return ['pending' => $pending, 'checkouts' => $checkouts];
        });

        // Cache recent activity for 120 seconds
        $recentActivity = Cache::remember('dashboard.recent_activity', 120, function () {
            return AuditLog::query()
                ->with('changer:id,fname,lname')
                ->latest()
                ->limit(6)
                ->get();
        });

        return [
            'user' => $user,
            'roomStatus' => [
                'total' => $total,
                'occupied' => $occupied,
                'available' => $available,
                'needsCleaning' => $needsCleaning,
                'occupancyPct' => $total > 0 ? (int) round(($occupied / $total) * 100) : 0,
            ],
            'occupiedRooms' => $this->mapOccupied($occupiedBookings),
            'upcomingBookings' => $this->mapUpcoming($upcomingBookings),
            'recentActivity' => $recentActivity,
            'pendingCount' => $bookingCounts['pending'],
            'checkoutsToday' => $bookingCounts['checkouts'],
        ];
    }

    private function mapOccupied(Collection $bookings): array
    {
        return $bookings->map(function (Booking $booking) {
            $checkedIn = $booking->checked_in_at;

            return [
                'id' => $booking->id,
                'guest_name' => $booking->user?->fullName ?? 'Unknown',
                'room_number' => $booking->room?->room_number ?? '—',
                'room_type' => $booking->room?->roomType?->name ?? 'Unknown',
                'start_at' => $booking->start_at,
                'end_at' => $booking->end_at,
                'checked_in_at' => $checkedIn,
                'nights_so_far' => $checkedIn
                    ? max(1, (int) $checkedIn->diffInDays(now()))
                    : 1,
            ];
        })->all();
    }

    private function mapUpcoming(Collection $bookings): array
    {
        return $bookings->map(function (Booking $booking) {
            $nights = $booking->start_at && $booking->end_at
                ? max(1, (int) $booking->start_at->diffInDays($booking->end_at))
                : 1;

            return [
                'id' => $booking->id,
                'guest_name' => $booking->user?->fullName ?? 'Unknown',
                'room_number' => $booking->room?->room_number ?? '—',
                'room_type' => $booking->room?->roomType?->name ?? 'Unknown',
                'start_at' => $booking->start_at,
                'end_at' => $booking->end_at,
                'nights' => $nights,
            ];
        })->all();
    }
}