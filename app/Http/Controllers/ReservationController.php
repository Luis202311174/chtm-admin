<?php

namespace App\Http\Controllers;

use App\Models\ArchivedBooking;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Booking\BookingNotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    private const TABLE_LIMIT = 50;

    public function index(Request $request): Response
    {
        $statuses = ['pending', 'approved', 'checked_in', 'checked_out'];
        $currentTab = $request->string('tab', 'pending')->toString();
        
        if (!in_array($currentTab, $statuses, true)) {
            $currentTab = 'pending';
        }

        $allBookings = Booking::with([
            'user:id,fname,lname,email,phone,address',
            'room.roomType:id,name,capacity,base_price',
            'approvedByUser:id,fname,lname',
            'rejectedByUser:id,fname,lname',
            'checkedInByUser:id,fname,lname',
            'checkedOutByUser:id,fname,lname',
        ])
            ->select('id', 'user_id', 'room_id', 'start_at', 'end_at', 'guests', 'extra_beds', 'status', 'total_amount', 'price_at_booking', 'has_child', 'has_pwd', 'has_senior', 'message', 'payment_method', 'created_at', 'checked_in_at', 'checked_out_at', 'approved_by', 'rejected_by', 'checked_in_by', 'checked_out_by', 'amount_paid', 'payment_choice', 'room_type')
            ->whereIn('status', $statuses)
            ->orderBy('start_at')
            ->limit(self::TABLE_LIMIT * count($statuses))
            ->get()
            ->map(function ($booking) {
                $userData = null;
                if ($booking->user) {
                    $user = $booking->user;
                    $userData = [
                        'id'        => (string) $user->getKey(),
                        'full_name' => $user->fullName ?? $user->full_name ?? 'Unknown Guest',
                        'email'     => $user->email ?? '',
                        'phone'     => $user->phone ?? null,
                        'address'   => $user->address ?? null,
                    ];
                }

                $isConflicted = false;
                if ($booking->start_at && $booking->end_at && $booking->status === 'pending') {
                    $isConflicted = Booking::where('room_id', $booking->room_id)
                        ->where('id', '!=', $booking->id)
                        ->whereIn('status', ['approved', 'checked_in'])
                        ->where('start_at', '<', $booking->end_at)
                        ->where('end_at', '>', $booking->start_at)
                        ->exists();
                }

                return [
                    'id'                 => $booking->id,
                    'status'             => $booking->status,
                    'total_amount'       => $booking->total_amount ? number_format((float) $booking->total_amount, 2, '.', '') : null,
                    'price_at_booking'   => $booking->price_at_booking ? number_format((float) $booking->price_at_booking, 2, '.', '') : null,
                    'amount_paid'        => $booking->amount_paid ? number_format((float) $booking->amount_paid, 2, '.', '') : null,
                    'has_child'          => $booking->has_child,
                    'child_age_group'    => $booking->child_age_group,
                    'has_pwd'            => $booking->has_pwd,
                    'has_senior'         => $booking->has_senior,
                    'guests'             => $booking->guests ?? 1,
                    'extra_beds'         => $booking->extra_beds ?? 0,
                    'message'            => $booking->message ?? '',
                    'payment_method'     => $booking->payment_method ?? '',
                    'payment_choice'     => $booking->payment_choice ?? '',
                    'room_type'          => $booking->room_type ?? '',
                    'start_at'           => $booking->start_at?->toIso8601String(),
                    'end_at'             => $booking->end_at?->toIso8601String(),
                    'start_at_formatted' => $booking->start_at ? Carbon::parse($booking->start_at)->format('M d, Y h:i A') : '—',
                    'end_at_formatted'   => $booking->end_at ? Carbon::parse($booking->end_at)->format('M d, Y h:i A') : '—',
                    'checked_in_at'      => $booking->checked_in_at?->toIso8601String(),
                    'checked_out_at'     => $booking->checked_out_at?->toIso8601String(),
                    'user'               => $userData,
                    'room'               => $booking->room ? [
                        'id'          => $booking->room->id,
                        'room_number' => $booking->room->room_number,
                        'floor'       => $booking->room->floor,
                        'room_type'   => $booking->room->roomType ? [
                            'id'       => $booking->room->roomType->id,
                            'name'     => $booking->room->roomType->name,
                            'capacity' => $booking->room->roomType->capacity,
                            'base_price' => $booking->room->roomType->base_price,
                        ] : null,
                    ] : null,
                    'approved_by'        => $booking->approvedByUser ? [
                        'id'        => (string) $booking->approvedByUser->getKey(),
                        'full_name' => $booking->approvedByUser->full_name ?? 'Unknown',
                    ] : null,
                    'rejected_by'        => $booking->rejectedByUser ? [
                        'id'        => (string) $booking->rejectedByUser->getKey(),
                        'full_name' => $booking->rejectedByUser->full_name ?? 'Unknown',
                    ] : null,
                    'checked_in_by'      => $booking->checkedInByUser ? [
                        'id'        => (string) $booking->checkedInByUser->getKey(),
                        'full_name' => $booking->checkedInByUser->full_name ?? 'Unknown',
                    ] : null,
                    'checked_out_by'     => $booking->checkedOutByUser ? [
                        'id'        => (string) $booking->checkedOutByUser->getKey(),
                        'full_name' => $booking->checkedOutByUser->full_name ?? 'Unknown',
                    ] : null,
                    'is_conflicted'      => $isConflicted,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Reservation', [
            'title' => 'Reservations Desk',
            'allBookings' => $allBookings,
            'currentTab' => $currentTab,
        ]);
    }

    public function approve(Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'pending') {
            return back()->with('error', 'Booking is not in pending status.');
        }

        $booking->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        Cache::forget('dashboard.booking_counts');

        try {
            app(BookingNotificationService::class)->sendStatusUpdate($booking);
        } catch (\Throwable $e) {
            Log::warning('Failed to send approval email', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking approved successfully.');
    }

    public function decline(Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'pending') {
            return back()->with('error', 'Booking is not in pending status.');
        }

        $booking->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
        ]);

        Cache::forget('dashboard.booking_counts');

        try {
            app(BookingNotificationService::class)->sendStatusUpdate($booking);
        } catch (\Throwable $e) {
            Log::warning('Failed to send rejection email', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking declined.');
    }

    public function checkin(Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'approved') {
            return back()->with('error', 'Booking must be approved first.');
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'checked_in',
                'checked_in_at' => now(),
                'checked_in_by' => auth()->id(),
            ]);

            if ($booking->room) {
                $booking->room->update(['status' => 'occupied']);
            }
        });

        Cache::forget('dashboard.booking_counts');
        Cache::forget('dashboard.room_stats');

        return back()->with('status', 'Guest checked in successfully.');
    }

    public function checkout(Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Booking must be checked in first.');
        }

        DB::transaction(function () use ($booking) {
            $booking->loadMissing(['user', 'room.roomType']);
            $room = $booking->room;
            $roomType = $room?->roomType;
            $user = $booking->user;

            // Create archived record
            ArchivedBooking::query()->create([
                'original_booking_id' => $booking->id,
                'user_id'             => $booking->user_id,
                'room_id'             => $booking->room_id,
                'room_number'         => $room->room_number ?? null,
                'room_type_name'      => $roomType->name ?? null,
                'room_type_id'        => $roomType->id ?? null,
                'room_capacity'       => $roomType->capacity ?? null,
                'room_base_price'     => $roomType->base_price ?? null,
                'room_floor'          => $room->floor ?? null,
                'start_at'            => $booking->start_at,
                'end_at'              => $booking->end_at,
                'checked_in_at'       => $booking->checked_in_at,
                'checked_out_at'      => now(),
                'guests'              => $booking->guests,
                'status'              => 'checked_out',
                'message'             => $booking->message,
                'payment_method'      => $booking->payment_method,
                'total_amount'        => $booking->total_amount,
                'extra_beds'          => $booking->extra_beds,
                'has_child'           => $booking->has_child,
                'has_pwd'             => $booking->has_pwd,
                'has_senior'          => $booking->has_senior,
                'child_age_group'     => $booking->child_age_group,
                'guest_fname'         => $user->fname ?? null,
                'guest_lname'         => $user->lname ?? null,
                'guest_email_hash'    => $user->email_hash ?? null,
                'approved_by'         => $booking->approved_by,
                'rejected_by'         => $booking->rejected_by,
                'checked_in_by'       => $booking->checked_in_by,
                'checked_out_by'      => auth()->id(),
            ]);

            // Update booking status
            $booking->update([
                'status' => 'checked_out',
                'checked_out_at' => now(),
                'checked_out_by' => auth()->id(),
            ]);

            // Free the room
            if ($room) {
                $room->update(['status' => 'needs_cleaning']);
            }
        });

        Cache::forget('dashboard.booking_counts');
        Cache::forget('dashboard.room_stats');
        Cache::forget('dashboard.occupied_bookings');

        return back()->with('status', 'Guest checked out successfully. Room marked for cleaning.');
    }
}