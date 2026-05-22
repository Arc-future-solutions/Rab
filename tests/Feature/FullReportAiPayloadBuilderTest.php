<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentPillarScore;
use App\Models\AssessmentQuestionBank;
use App\Models\AssessmentQuestionResponse;
use App\Models\Client;
use App\Services\FullReportAiPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullReportAiPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_pir_review_payload_uses_stored_values_and_minimal_evidence_shape(): void
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
            'overall_score' => 3.25,
            'rag_status' => 'Amber',
            'status' => 'draft',
            'bri' => 2.75,
            'vri' => 3.50,
            'dmi' => 2.25,
            'rii' => 3.00,
            'chi' => 2.50,
            'delivery_stage' => 'Build',
            'client_concerns' => 'Sponsor is concerned about governance drift.',
            'regulatory_context' => 'fca_uk',
            'sponsor_name' => 'Alex Sponsor',
            'interview_count' => 3,
            'documents_reviewed' => ['RAID log', 'PID'],
            'programme_value' => 1250000,
            'reporting_accuracy_risk' => true,
            'reporting_accuracy_evidence' => 'Financial reports conflict with RAID status.',
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
            'source_type' => 'document',
            'respondent_role' => 'Programme Director',
            'document_source' => 'Governance pack',
            'confidence' => 'low',
            'confidence_level' => 'low',
        ]);

        AssessmentQuestionResponse::create([
            'assessment_id' => $assessment->id,
            'pillar_name' => 'P1 — Governance & Decision-Making',
            'question' => 'P1.F2: Are decisions timely?',
            'score' => 4,
            'evidence_note' => '',
            'confidence' => 'high',
        ]);

        $payload = (new FullReportAiPayloadBuilder())->buildFromAssessment($assessment);

        $this->assertSame('PIR', $payload['framework']);
        $this->assertSame('Review', $payload['tier']);
        $this->assertSame(3.25, $payload['overall_score']);
        $this->assertSame(2.75, $payload['bri']);
        $this->assertSame(3.50, $payload['vri']);
        $this->assertSame(2.25, $payload['dmi']);
        $this->assertSame(3.00, $payload['rii']);
        $this->assertSame(2.50, $payload['chi']);
        $this->assertSame(2.5, $payload['pillar_scores']['P1']);
        $this->assertSame('P10 — Digital & Transformation Maturity', $payload['pillar_names']['P10']);
        $this->assertSame(['id', 'pillar_code', 'score', 'is_compliance'], array_keys($payload['question_responses'][0]));
        $this->assertSame(2, $payload['compliance_question_scores']['P1.F1']);
        $this->assertSame(['RAID log', 'PID'], $payload['documents_reviewed']);
        $this->assertTrue($payload['reporting_accuracy_risk']);
        $this->assertNull($payload['stakeholder_notes']);
        $this->assertSame('Decision forum is informal.', $payload['evidence_notes']['P1.F1']['note']);
        $this->assertSame('Governance pack', $payload['evidence_notes']['P1.F1']['source']);
        $this->assertSame('Low', $payload['evidence_notes']['P1.F1']['confidence']);
        $this->assertSame('Programme Director', $payload['evidence_notes']['P1.F1']['respondent']);
        $this->assertArrayNotHasKey('P1.F2', $payload['evidence_notes']);
    }
}
