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
            'client_concerns' => 'Sponsor is concerned about governance drift.',
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
            'confidence_level' => 'low',
            'stakeholder_divergence_note' => 'Sponsor and delivery lead disagree on escalation quality.',
        ]);

        $builder = new AssessmentAiPayloadBuilder();

        $snapshotPayload = $builder->buildSnapshotPayload($assessment);
        $payload = $builder->buildFullPayload($assessment);

        $this->assertSame('Sponsor is concerned about governance drift.', $snapshotPayload['primary_concern']);
        $this->assertSame('ERP Replacement', $snapshotPayload['programme_name']);
        $this->assertSame('Build', $snapshotPayload['delivery_stage']);
        $this->assertSame(2, $snapshotPayload['compliance_question_scores']['P1.F1']);
        $this->assertArrayHasKey('pillar_scores', $snapshotPayload);
        $this->assertArrayHasKey('pillar_names', $snapshotPayload);
        $this->assertArrayNotHasKey('tier', $snapshotPayload);
        $this->assertArrayNotHasKey('evidence_notes', $snapshotPayload);
        $this->assertSame('pir_full_tier2', $builder->promptKey($assessment));
        $this->assertSame('PIR', $payload['framework']);
        $this->assertSame('Briefing', $payload['tier']);
        $this->assertSame('ERP Replacement', $payload['programme_name']);
        $this->assertSame('Sponsor is concerned about governance drift.', $payload['primary_concern']);
        $this->assertSame('Build', $payload['delivery_stage']);
        $this->assertSame('fca_uk', $payload['regulatory_context']);
        $this->assertSame(['RAID log', 'PID'], $payload['documents_reviewed']);
        $this->assertSame(1250000.0, $payload['programme_value']);
        $this->assertTrue($payload['reporting_accuracy_risk']);
        $this->assertSame(['P1 below 3.0'], $payload['alert_flags']);
        $this->assertSame(2, $payload['compliance_question_scores']['P1.F1']);
        $this->assertSame('Programme Director', $payload['evidence_notes']['P1.F1']['respondent_role']);
        $this->assertSame('Governance pack', $payload['evidence_notes']['P1.F1']['document_source']);
        $this->assertSame('Low', $payload['evidence_notes']['P1.F1']['confidence']);
        $this->assertSame('Sponsor and delivery lead disagree on escalation quality.', $payload['evidence_notes']['P1.F1']['stakeholder_divergence_note']);
        $this->assertSame('Low', $payload['confidence_level']);
        $this->assertSame(['status reporting', 'cutover readiness'], $payload['stakeholder_notes']['divergence_areas']);
        $this->assertSame('P1 — Governance & Decision-Making', $payload['pillar_names']['PP1']);
        $this->assertSame('P10 — Digital & Transformation Maturity', $payload['pillar_names']['PP10']);
        $this->assertSame('P1 — Governance & Decision-Making', $payload['pillar_names']['P1']);
    }

    public function test_sir_full_payload_includes_domain_name_lookup_aliases(): void
    {
        $client = Client::create([
            'company_name' => 'Service Co',
            'primary_contact' => 'Sam Owner',
        ]);

        $assessment = Assessment::create([
            'client_id' => $client->id,
            'name' => 'Managed Service',
            'target_entity' => 'Payments Platform',
            'type' => 'SIR',
            'report_tier' => 'Tier 2 Full',
            'overall_score' => 3.4,
            'rag_status' => 'Amber',
            'status' => 'draft',
            'client_concerns' => 'Recurring incidents are not visible in service reporting.',
            'service_context' => 'Transformation',
            'annual_service_cost' => 250000,
            'chi' => 3.0,
        ]);

        $framework = AssessmentFramework::create([
            'code' => 'SIR',
            'name' => 'Service Intelligence Review',
        ]);
        $pillar = AssessmentPillar::create([
            'framework_id' => $framework->id,
            'code' => 'D11',
            'name' => 'Service Tooling, CMDB & Knowledge Management',
            'weight' => 1.2,
        ]);
        AssessmentQuestionBank::create([
            'framework_id' => $framework->id,
            'pillar_id' => $pillar->id,
            'level' => 'full',
            'question_code' => 'D11.F1',
            'question_text' => 'Is CMDB governance accurate?',
            'is_compliance' => false,
        ]);

        AssessmentPillarScore::create([
            'assessment_id' => $assessment->id,
            'name' => 'D11 — Service Tooling, CMDB & Knowledge Management',
            'score' => 2.8,
            'rag_status' => 'Amber',
        ]);
        AssessmentQuestionResponse::create([
            'assessment_id' => $assessment->id,
            'pillar_name' => 'D11 — Service Tooling, CMDB & Knowledge Management',
            'question' => 'D11.F1: Is CMDB governance accurate?',
            'score' => 3,
            'confidence' => 'medium',
        ]);

        $builder = new AssessmentAiPayloadBuilder();

        $snapshotPayload = $builder->buildSnapshotPayload($assessment);
        $payload = $builder->buildFullPayload($assessment);

        $this->assertSame('Recurring incidents are not visible in service reporting.', $snapshotPayload['primary_concern']);
        $this->assertSame('Payments Platform', $snapshotPayload['service_name']);
        $this->assertSame('Transformation', $snapshotPayload['service_context']);
        $this->assertArrayHasKey('domain_scores', $snapshotPayload);
        $this->assertArrayHasKey('domain_names', $snapshotPayload);
        $this->assertArrayNotHasKey('tier', $snapshotPayload);
        $this->assertArrayNotHasKey('annual_service_cost', $snapshotPayload);
        $this->assertSame('sir_full_tier2', $builder->promptKey($assessment));
        $this->assertSame('SIR', $payload['framework']);
        $this->assertSame('Payments Platform', $payload['service_name']);
        $this->assertSame('Recurring incidents are not visible in service reporting.', $payload['primary_concern']);
        $this->assertSame('Transformation', $payload['service_context']);
        $this->assertSame(250000.0, $payload['annual_service_cost']);
        $this->assertSame('D1 — Service Governance & Ownership', $payload['domain_names']['DD1']);
        $this->assertSame('D11 — Service Tooling, CMDB & Knowledge Management', $payload['domain_names']['DD11']);
        $this->assertSame('D11 — Service Tooling, CMDB & Knowledge Management', $payload['domain_names']['D11']);
        $this->assertArrayNotHasKey('pillar_names', $payload);
    }
}
