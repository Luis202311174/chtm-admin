<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivedBooking extends Model
{
    protected $table = 'public.archived_bookings';

    public $timestamps = false;

    protected $fillable = [
        'original_booking_id',
        'user_id',
        'room_id',
        'room_number',
        'room_type_name',
        'room_type_id',
        'room_capacity',
        'room_base_price',
        'room_floor',
        'start_at',
        'end_at',
        'checked_in_at',
        'checked_out_at',
        'guests',
        'status',
        'message',
        'payment_method',
        'total_amount',
        'extra_beds',
        'has_child',
        'has_pwd',
        'has_senior',
        'child_age_group',
        'guest_fname',
        'guest_lname',
        'guest_email_hash',
        'approved_by',
        'rejected_by',
        'checked_in_by',
        'checked_out_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function checkedInByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'room_base_price' => 'decimal:2',
            'has_child' => 'boolean',
            'has_pwd' => 'boolean',
            'has_senior' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
