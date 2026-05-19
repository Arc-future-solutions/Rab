<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'lead_id',
        'calendly_event_uuid',
        'calendly_invitee_uuid',
        'client_name',
        'client_email',
        'starts_at',
        'ends_at',
        'join_url',
        'event_type_name',
        'status',
        'cancellation_reason',
        'raw_payload',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'raw_payload' => 'array',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'active')->where('starts_at', '>=', now());
    }
}
