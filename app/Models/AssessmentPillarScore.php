<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentPillarScore extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'critical_flag' => 'boolean'
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}
