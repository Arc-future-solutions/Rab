<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentPillarScore;
use App\Models\AssessmentQuestionBank;
use App\Models\AssessmentQuestionResponse;
use App\Models\Client;
use App\Services\AssessmentAiPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentAiPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_pir_full_payload_includes_consultant_context_and_prompt_tier(): void
    {
        $client = Client::create([
            'company_name' => 'Acme Ltd',
            'primary_contact' => 'Alex Sponsor',
        ]);

        $assessment = Assessment::create([
            'client_id' => $client->id,
            'name' => 'ERP Programme',
            'target_entity' => 'ERP Replacement',
            'type' => 'PIR',
            'report_tier' => 'Tier 2 Full',
            'overall_score' => 3.25,
            'rag_status' => 'Amber',
            'status' => 'draft',
            'delivery_stage' => 'Build',
            'regulatory_context' => 'fca_uk',
            'sponsor_name' => 'Alex Sponsor',
            'interview_count' => 3,
            'documents_reviewed' => ['RAID log', 'PID'],
            'programme_value' => 1250000,
            'reporting_accuracy_risk' => true,
            'reporting_accuracy_evidence' => 'Financial reports conflict with RAID status.',
            'sponsor_position' => 'Sponsor expects green status.',
            'operational_position' => 'Delivery leads report schedule pressure.',
            'divergence_areas' => ['status reporting', 'cutover readiness'],
            'chi' => 2.5,
        ]);

        $framework = AssessmentFramework::create([
            'code' => 'PIR',
            'name' => 'Programme Integrity Review',
        ]);
        $pillar = AssessmentPillar::create([
            'framework_id' => $framework->id,
            'code' => 'P1',
            'name' => 'Governance & Decision-Making',
            'weight' => 1,
        ]);
        AssessmentQuestionBank::create([
            'framework_id' => $framework->id,
            'pillar_id' => $pillar->id,
            'level' => 'full',
            'question_code' => 'P1.F1',
            'question_text' => 'Is decision ownership clear?',
            'is_compliance' => true,
        ]);

        AssessmentPillarScore::create([
            'assessment_id' => $assessment->id,
            'name' => 'P1 — Governance & Decision-Making',
            'score' => 2.5,
            'rag_status' => 'Amber',
        ]);
        AssessmentQuestionResponse::create([
            'assessment_id' => $assessment->id,
            'pillar_name' => 'P1 — Governance & Decision-Making',
            'question' => 'P1.F1: Is decision ownership clear?',
            'score' => 2,
            'evidence_note' => 'Decision forum is informal.',
            'respondent_role' => 'Programme Director',
            'document_source' => 'Governance pack',
            'confidence' => 'low',
        ]);

        $builder = new AssessmentAiPayloadBuilder();

        $payload = $builder->buildFullPayload($assessment);

        $this->assertSame('pir_full_tier2', $builder->promptKey($assessment));
        $this->assertSame('PIR', $payload['framework']);
        $this->assertSame('Briefing', $payload['tier']);
        $this->assertSame('ERP Replacement', $payload['programme_name']);
        $this->assertSame('Build', $payload['delivery_stage']);
        $this->assertSame('fca_uk', $payload['regulatory_context']);
        $this->assertSame(['RAID log', 'PID'], $payload['documents_reviewed']);
        $this->assertSame(1250000.0, $payload['programme_value']);
        $this->assertTrue($payload['reporting_accuracy_risk']);
        $this->assertSame(['P1 below 3.0'], $payload['alert_flags']);
        $this->assertSame(2, $payload['compliance_question_scores']['P1.F1']);
        $this->assertSame('Programme Director', $payload['evidence_notes']['P1.F1']['respondent_role']);
        $this->assertSame('Low', $payload['confidence_level']);
        $this->assertSame(['status reporting', 'cutover readiness'], $payload['stakeholder_notes']['divergence_areas']);
    }
}
