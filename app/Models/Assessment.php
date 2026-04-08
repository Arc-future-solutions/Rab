<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'ai_draft_json' => 'array',
        'critical_flag' => 'boolean'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function pillarScores()
    {
        return $this->hasMany(AssessmentPillarScore::class);
    }

    public function questionResponses()
    {
        return $this->hasMany(AssessmentQuestionResponse::class);
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }
}
