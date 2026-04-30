<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentPillar extends Model
{
    use HasFactory;

    protected $fillable = ['framework_id', 'code', 'name', 'weight', 'is_critical', 'display_order'];

    public function framework()
    {
        return $this->belongsTo(AssessmentFramework::class, 'framework_id');
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestionBank::class, 'pillar_id');
    }
}
