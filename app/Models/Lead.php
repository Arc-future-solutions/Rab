<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'index_scores_json' => 'array',
        'answers_json' => 'array',
        'confidence_json' => 'array',
        'snapshot_report_json' => 'array',
        'alerts_json' => 'array',
        'top_three_insight_areas_json' => 'array',
        'converted_to_client' => 'boolean',
        'consent_given' => 'boolean',
        'consent_timestamp' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
