<?php

namespace App\Http\Controllers;

use App\Models\ArchivedBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArchivedController extends Controller
{
    public function index(Request $request): Response
    {
        $archived = ArchivedBooking::query()
            ->with([
                'user:id,fname,lname,email,phone,address',
                'approvedByUser:id,fname,lname',
                'rejectedByUser:id,fname,lname',
                'checkedInByUser:id,fname,lname',
                'checkedOutByUser:id,fname,lname',
            ])
            ->orderByDesc('checked_out_at')
            ->limit(100)
            ->get()
            ->map(function ($booking) {
                $userData = null;
                if ($booking->user) {
                    $user = $booking->user;
                    $userData = [
                        'id'        => (string) $user->getKey(),
                        'full_name' => $user->full_name ?? 'Unknown Guest',
                        'email'     => $user->email ?? '',
                        'phone'     => $user->phone ?? null,
                        'address'   => $user->address ?? null,
                    ];
                } else {
                    // Fallback to archived guest data when user is not available
                    $userData = [
                        'id'        => null,
                        'full_name' => trim(($booking->guest_fname ?? '') . ' ' . ($booking->guest_lname ?? '')) ?: 'Archived Guest',
                        'email'     => '',
                        'phone'     => null,
                        'address'   => null,
                    ];
                }

                // Calculate guest counts: adults = guests - (child + pwd + senior)
                $adults = max(0, (int) ($booking->guests ?? 1) - ((int) ($booking->has_child ? 1 : 0) + (int) ($booking->has_pwd ? 1 : 0) + (int) ($booking->has_senior ? 1 : 0)));

                return [
                    'id'                 => $booking->id,
                    'original_booking_id'  => $booking->original_booking_id,
                    'status'             => $booking->status,
                    'total_amount'       => $booking->total_amount ? number_format((float) $booking->total_amount, 2, '.', '') : null,
                    'price_at_booking'   => $booking->price_at_booking ? number_format((float) $booking->price_at_booking, 2, '.', '') : null,
                    'has_child'          => $booking->has_child,
                    'child_age_group'      => $booking->child_age_group,
                    'has_pwd'            => $booking->has_pwd,
                    'has_senior'         => $booking->has_senior,
                    'guests'             => $booking->guests ?? 1,
                    'adults'             => $adults,
                    'extra_beds'         => $booking->extra_beds ?? 0,
                    'message'            => $booking->message ?? '',
                    'payment_method'     => $booking->payment_method ?? '',
                    'start_at'           => $booking->start_at?->toIso8601String(),
                    'end_at'             => $booking->end_at?->toIso8601String(),
                    'start_at_formatted' => $booking->start_at ? Carbon::parse($booking->start_at)->format('M d, Y h:i A') : '—',
                    'end_at_formatted'   => $booking->end_at ? Carbon::parse($booking->end_at)->format('M d, Y h:i A') : '—',
                    'checked_in_at'      => $booking->checked_in_at?->toIso8601String(),
                    'checked_out_at'     => $booking->checked_out_at?->toIso8601String(),
                    'user'               => $userData,
                    'room'               => [
                        'id'            => $booking->room_id,
                        'room_number'   => $booking->room_number,
                        'floor'         => $booking->room_floor,
                        'room_type'     => [
                            'id'       => $booking->room_type_id,
                            'name'     => $booking->room_type_name,
                            'capacity' => $booking->room_capacity,
                            'base_price' => $booking->room_base_price,
                        ],
                    ],
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
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Archived', [
            'title' => 'Archived Bookings',
            'archivedBookings' => $archived,
        ]);
    }
}