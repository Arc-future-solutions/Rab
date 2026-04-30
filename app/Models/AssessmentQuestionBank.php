<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentQuestionBank extends Model
{
    use HasFactory;

    protected $table = 'assessment_questions';

    protected $fillable = [
        'framework_id', 
        'pillar_id', 
        'level', 
        'question_code', 
        'question_text', 
        'hidden_risk', 
        'stage_note', 
        'question_type', 
        'question_type_label', 
        'score_anchors', 
        'is_compliance',
        'is_hybrid',
        'version',
        'is_active', 
        'display_order'
    ];

    protected $casts = [
        'score_anchors' => 'array',
    ];

    public function framework()
    {
        return $this->belongsTo(AssessmentFramework::class, 'framework_id');
    }

    public function pillar()
    {
        return $this->belongsTo(AssessmentPillar::class, 'pillar_id');
    }
}
