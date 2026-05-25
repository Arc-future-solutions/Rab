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
        'critical_flag' => 'boolean',
        'documents_reviewed' => 'array',
        'divergence_areas' => 'array',
        'reporting_accuracy_risk' => 'boolean',
        'ai_generation_started_at' => 'datetime',
        'ai_generation_completed_at' => 'datetime',
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

    /**
     * Centralized report configuration based on Tier
     */
    public function reportConfig()
    {
        $tier = $this->report_tier ?? 'Snapshot';
        
        $config = [
            'tier' => $tier,
            'tier_label' => $tier === 'Tier 2 Full' ? 'Tier 2 Full' : ($tier === 'Tier 1 Rapid' ? 'Tier 1 Rapid' : 'Intelligence Snapshot'),
            'show_overall_score' => true,
            'show_rag' => true,
            'show_action_indicator' => true,
            'show_pillar_scores_bar' => true,
            'show_confidence_bands' => in_array($tier, ['Tier 1 Rapid', 'Tier 2 Full']),
            'show_spider_chart' => true,
            'show_score_gauge' => true,
            'show_heat_map' => true,
            'show_risk_matrix' => in_array($tier, ['Tier 1 Rapid', 'Tier 2 Full']),
            'risk_matrix_top_n' => 5,
            'indices_style' => $tier === 'Snapshot' ? 'interpreted' : 'standard',
            'show_full_indices' => true,
            'calculate_chi' => $tier !== 'Snapshot',
            'executive_summary_type' => $tier === 'Snapshot' ? 'compact' : 'full',
            'show_insight_cards' => $tier === 'Snapshot',
            'show_intelligence_profile' => in_array($tier, ['Tier 1 Rapid', 'Tier 2 Full']),
            'intelligence_profile_threshold' => ($tier === 'Tier 1 Rapid') ? 3.0 : 4.0,
            'show_action_register' => in_array($tier, ['Tier 1 Rapid', 'Tier 2 Full']),
            'action_register_top_n' => 5,
            'show_final_position' => in_array($tier, ['Tier 1 Rapid', 'Tier 2 Full']),
            'show_appendix_full_data' => $tier === 'Tier 2 Full',
            'never_show_hidden_risk' => true,
            'remove_30_60_90_plan' => true,
        ];

        return $config;
    }
}
