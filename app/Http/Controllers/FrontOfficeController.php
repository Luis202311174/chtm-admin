<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PaymentReceipt;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FrontOfficeController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->string('tab', 'bookings')->toString();
        if (!in_array($tab, ['bookings', 'receipts'], true)) {
            $tab = 'bookings';
        }

        $bookingsPayload = collect();
        $roomsPayload = collect();
        $receiptsPayload = collect();
        $bookingStats = ['total' => 0, 'pending' => 0, 'approved' => 0];

        if ($tab === 'bookings') {
            $bookingStats = Cache::remember('frontoffice.booking_stats', 120, function () {
                return [
                    'total' => Booking::query()->count(),
                    'pending' => Booking::query()->where('status', 'pending')->count(),
                    'approved' => Booking::query()->where('status', 'approved')->count(),
                ];
            });

            $bookingsPayload = Booking::with([
                    'user:id,fname,lname,email',
                    'room.roomType:id,name',
                    'approvedByUser:id,fname,lname',
                    'checkedInByUser:id,fname,lname',
                    'checkedOutByUser:id,fname,lname',
                ])
                ->select('id', 'user_id', 'room_id', 'start_at', 'end_at', 'guests', 'status', 'total_amount', 'payment_method', 'created_at', 'has_child', 'child_age_group', 'has_pwd', 'has_senior', 'extra_beds', 'message', 'checked_in_at', 'checked_out_at', 'approved_by', 'checked_in_by', 'checked_out_by')
                ->orderByDesc('start_at')
                ->limit(100)
                ->get()
                ->map(function ($b) {
                    // Calculate guest counts: adults = guests - (child + pwd + senior)
                    $adults = max(0, (int) ($b->guests ?? 1) - ((int) ($b->has_child ? 1 : 0) + (int) ($b->has_pwd ? 1 : 0) + (int) ($b->has_senior ? 1 : 0)));

                    return [
                        'id' => $b->id,
                        'user_id' => $b->user_id,
                        'room_id' => $b->room_id,
                        'start_at' => $b->start_at?->toIso8601String(),
                        'end_at' => $b->end_at?->toIso8601String(),
                        'guests' => $b->guests,
                        'adults' => $adults,
                        'status' => $b->status,
                        'total_amount' => $b->total_amount,
                        'payment_method' => $b->payment_method,
                        'created_at' => $b->created_at?->toIso8601String(),
                        'has_child' => $b->has_child,
                        'child_age_group' => $b->child_age_group,
                        'has_pwd' => $b->has_pwd,
                        'has_senior' => $b->has_senior,
                        'extra_beds' => $b->extra_beds,
                        'message' => $b->message,
                        'checked_in_at' => $b->checked_in_at?->toIso8601String(),
                        'checked_out_at' => $b->checked_out_at?->toIso8601String(),
                        'user' => $b->user ? [
                            'id' => $b->user->id,
                            'fname' => $b->user->fname ?? '',
                            'lname' => $b->user->lname ?? '',
                            'full_name' => $b->user->full_name ?? 'Unknown',
                            'email' => $b->user->email ?? '',
                        ] : [
                            'id' => null,
                            'fname' => '',
                            'lname' => '',
                            'full_name' => 'Unknown',
                            'email' => '',
                        ],
                        'room' => $b->room ? [
                            'id' => $b->room->id,
                            'room_number' => $b->room->room_number,
                            'room_type' => $b->room->roomType ? ['name' => $b->room->roomType->name] : null,
                        ] : null,
                        'approved_by' => $b->approvedByUser ? [
                            'id' => $b->approvedByUser->id,
                            'full_name' => $b->approvedByUser->full_name ?? 'Unknown',
                        ] : null,
                        'checked_in_by' => $b->checkedInByUser ? [
                            'id' => $b->checkedInByUser->id,
                            'full_name' => $b->checkedInByUser->full_name ?? 'Unknown',
                        ] : null,
                        'checked_out_by' => $b->checkedOutByUser ? [
                            'id' => $b->checkedOutByUser->id,
                            'full_name' => $b->checkedOutByUser->full_name ?? 'Unknown',
                        ] : null,
                    ];
                })->values()->toArray();

            $roomsPayload = Cache::remember('frontoffice.rooms', 120, function () {
                return Room::query()
                    ->with('roomType:id,name')
                    ->select('id', 'room_type_id', 'room_number', 'status', 'floor')
                    ->orderBy('room_number')
                    ->get()
                    ->map(function ($r) {
                        return [
                            'id' => $r->id,
                            'room_number' => $r->room_number,
                            'status' => $r->status,
                            'floor' => $r->floor,
                            'room_type' => $r->roomType ? ['name' => $r->roomType->name] : null,
                        ];
                    })->toArray();
            });
        } elseif ($tab === 'receipts') {
            $receiptsPayload = PaymentReceipt::query()
                ->latest()
                ->limit(100)
                ->get()
                ->toArray();
        }

        return Inertia::render('FrontOffice', [
            'title' => 'Front Office',
            'tab' => $tab,
            'bookings' => $bookingsPayload,
            'rooms' => $roomsPayload,
            'receipts' => $receiptsPayload,
            'bookingStats' => $bookingStats,
        ]);
    }

    public function update(Request $request, int $booking): RedirectResponse
    {
        $validated = $request->validate([
            'guest_fname' => ['required', 'string', 'max:255'],
            'guest_lname' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'room_id' => ['required', 'exists:rooms,id'],
            'guests' => ['required', 'integer', 'min:1'],
            'extra_beds' => ['nullable', 'integer', 'min:0', 'max:2'],
            'has_child' => ['nullable', 'boolean'],
            'child_age_group' => ['nullable', 'string'],
            'has_pwd' => ['nullable', 'boolean'],
            'has_senior' => ['nullable', 'boolean'],
        ]);

        $bookingModel = Booking::with('user')->findOrFail($booking);
        
        // Update user information
        if ($bookingModel->user) {
            $bookingModel->user->update([
                'fname' => $validated['guest_fname'],
                'lname' => $validated['guest_lname'],
                'email' => $validated['guest_email'],
            ]);
        }
        
        // Update booking information
        $bookingModel->update([
            'room_id' => $validated['room_id'],
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'guests' => $validated['guests'],
            'extra_beds' => $validated['extra_beds'] ?? 0,
            'has_child' => $request->boolean('has_child'),
            'child_age_group' => $validated['child_age_group'],
            'has_pwd' => $request->boolean('has_pwd'),
            'has_senior' => $request->boolean('has_senior'),
        ]);

        return redirect()->route('frontoffice', ['tab' => 'bookings'])
            ->with('status', 'Booking updated successfully.');
    }

    public function storeReceipt(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $file = $validated['receipt'];
        $path = $file->store('receipts', 'local');

        PaymentReceipt::query()->create([
            'booking_id' => (int) $validated['booking_id'],
            'amount' => $validated['amount'],
            'reference_number' => $path,
        ]);

        return redirect()->route('frontoffice', ['tab' => 'receipts'])
            ->with('status', 'Receipt uploaded successfully.');
    }

    public function viewReceipt(string $receipt): StreamedResponse
    {
        $record = PaymentReceipt::query()->findOrFail($receipt);
        $path = $record->reference_number;
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';
        return $disk->download($path, basename($path), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}