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
            'sponsor_position' => 'Sponsor sees service reporting as controlled.',
            'operational_position' => 'Service management reports unresolved recurring incidents.',
            'divergence_areas' => ['service reporting', 'incident recurrence'],
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
            'stakeholder_divergence_note' => 'Sponsor sees CMDB as stable; operations reports ownership gaps.',
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
        $this->assertSame('Briefing', $payload['tier']);
        $this->assertSame('Payments Platform', $payload['service_name']);
        $this->assertSame('Recurring incidents are not visible in service reporting.', $payload['primary_concern']);
        $this->assertSame('Transformation', $payload['service_context']);
        $this->assertSame(250000.0, $payload['annual_service_cost']);
        $this->assertSame('Sponsor sees service reporting as controlled.', $payload['stakeholder_notes']['sponsor_position']);
        $this->assertSame('Service management reports unresolved recurring incidents.', $payload['stakeholder_notes']['operational_position']);
        $this->assertSame(['service reporting', 'incident recurrence'], $payload['stakeholder_notes']['divergence_areas']);
        $this->assertSame(
            'Sponsor sees CMDB as stable; operations reports ownership gaps.',
            $payload['evidence_notes']['D11.F1']['stakeholder_divergence_note']
        );
        $this->assertSame('D1 — Service Governance & Ownership', $payload['domain_names']['DD1']);
        $this->assertSame('D11 — Service Tooling, CMDB & Knowledge Management', $payload['domain_names']['DD11']);
        $this->assertSame('D11 — Service Tooling, CMDB & Knowledge Management', $payload['domain_names']['D11']);
        $this->assertArrayNotHasKey('pillar_names', $payload);
        $this->assertArrayNotHasKey('pillar_scores', $payload);
        foreach (['bri', 'vri', 'dmi', 'rii'] as $pirIndex) {
            $this->assertArrayNotHasKey($pirIndex, $payload);
        }
    }

    public function test_sir_tier1_review_payload_includes_required_contract_fields_and_domain_question_responses(): void
    {
        $client = Client::create([
            'company_name' => 'Service Review Co',
            'primary_contact' => 'Sam Sponsor',
        ]);

        $assessment = Assessment::create([
            'client_id' => $client->id,
            'name' => 'Customer Support Service',
            'target_entity' => 'Customer Support Platform',
            'type' => 'SIR',
            'report_tier' => 'Tier 1 Rapid',
            'overall_score' => 2.8,
            'rag_status' => 'Amber',
            'status' => 'draft',
            'client_concerns' => 'Recurring incidents are not visible in service reporting.',
            'service_context' => 'Transformation',
            'regulatory_context' => 'fca_uk',
            'sponsor_name' => 'Sam Sponsor',
            'interview_count' => 4,
            'documents_reviewed' => "SLA pack\nIncident report",
            'annual_service_cost' => 250000,
            'chi' => 2.6,
        ]);

        $framework = AssessmentFramework::create([
            'code' => 'SIR',
            'name' => 'Service Intelligence Review',
        ]);

        foreach ([
            ['D1', 'Service Governance & Ownership', 2.5, 'D1.F1', true],
            ['D2', 'Incident & Major Incident Management', 2.4, 'D2.F1', false],
            ['D4', 'Problem Management', 2.7, 'D4.F1', false],
            ['D5', 'Change & Release Management', 2.3, 'D5.F1', false],
            ['D6', 'Service Performance, SLA & Reporting', 3.1, 'D6.F1', false],
            ['D7', 'Service Transition & BAU Readiness', 2.2, 'D7.F1', false],
            ['D8', 'Service Operations & Support Model', 3.2, 'D8.F1', false],
            ['D10', 'Operational Resilience & Continuity', 2.6, 'D10.F1', true],
            ['D11', 'Service Tooling, CMDB & Knowledge Management', 2.8, 'D11.F1', false],
            ['D12', 'Service Intelligence & Continuous Value', 2.5, 'D12.F1', false],
        ] as [$domainCode, $domainName, $score, $questionCode, $isCompliance]) {
            $pillar = AssessmentPillar::create([
                'framework_id' => $framework->id,
                'code' => $domainCode,
                'name' => $domainName,
                'weight' => 1,
            ]);

            AssessmentQuestionBank::create([
                'framework_id' => $framework->id,
                'pillar_id' => $pillar->id,
                'level' => 'full',
                'question_code' => $questionCode,
                'question_text' => "{$domainName} question",
                'is_compliance' => $isCompliance,
            ]);

            AssessmentPillarScore::create([
                'assessment_id' => $assessment->id,
                'name' => "{$domainCode} — {$domainName}",
                'score' => $score,
                'rag_status' => 'Amber',
            ]);

            AssessmentQuestionResponse::create([
                'assessment_id' => $assessment->id,
                'pillar_name' => "{$domainCode} — {$domainName}",
                'question' => "{$questionCode}: {$domainName} question",
                'score' => (int) floor($score),
                'evidence_note' => "{$domainCode} evidence",
                'respondent_role' => 'Service Owner',
                'document_source' => 'Service pack',
                'confidence' => $domainCode === 'D1' ? 'low' : 'high',
                'confidence_level' => $domainCode === 'D1' ? 'low' : 'high',
            ]);
        }

        $builder = new AssessmentAiPayloadBuilder();
        $payload = $builder->buildFullPayload($assessment);

        $this->assertSame('sir_full_tier1', $builder->promptKey($assessment));
        $this->assertSame('SIR', $payload['framework']);
        $this->assertSame('Review', $payload['tier']);
        $this->assertSame('Customer Support Platform', $payload['service_name']);
        $this->assertSame('Transformation', $payload['service_context']);
        $this->assertArrayHasKey('domain_scores', $payload);
        $this->assertArrayHasKey('domain_names', $payload);
        $this->assertSame(2.8, $payload['overall_score']);
        $this->assertSame('Amber', $payload['rag_status']);
        foreach (['ssi', 'smi', 'simi', 'bau_ri', 'chi', 'smi_simi_delta'] as $indexKey) {
            $this->assertArrayHasKey($indexKey, $payload);
            $this->assertIsNumeric($payload[$indexKey]);
        }
        $this->assertArrayHasKey('evidence_notes', $payload);
        $this->assertSame('Reda Boukhiar', $payload['consultant_name']);
        $this->assertSame('Sam Sponsor', $payload['sponsor_name']);
        $this->assertSame(4, $payload['interview_count']);
        $this->assertSame(['SLA pack', 'Incident report'], $payload['documents_reviewed']);
        $this->assertSame('Low', $payload['confidence_level']);
        $this->assertNull($payload['stakeholder_notes']);
        $this->assertSame(250000.0, $payload['annual_service_cost']);
        $this->assertArrayHasKey('D1.F1', $payload['compliance_question_scores']);
        $this->assertArrayHasKey('D10.F1', $payload['compliance_question_scores']);

        $this->assertNotEmpty($payload['question_responses']);
        foreach ($payload['question_responses'] as $response) {
            $this->assertSame(['id', 'domain_code', 'score', 'is_compliance'], array_keys($response));
            $this->assertArrayNotHasKey('pillar_code', $response);
        }

        $this->assertArrayNotHasKey('pillar_names', $payload);
        $this->assertArrayNotHasKey('pillar_scores', $payload);
    }

    public function test_full_payload_keeps_only_report_relevant_question_evidence(): void
    {
        $client = Client::create([
            'company_name' => 'Payload Co',
            'primary_contact' => 'Pat Sponsor',
        ]);

        $assessment = Assessment::create([
            'client_id' => $client->id,
            'name' => 'Tier 1 Programme',
            'type' => 'PIR',
            'report_tier' => 'Tier 1 Rapid',
            'overall_score' => 3.2,
            'rag_status' => 'Amber',
            'status' => 'draft',
        ]);

        $framework = AssessmentFramework::create([
            'code' => 'PIR',
            'name' => 'Programme Integrity Review',
        ]);

        foreach (['P1', 'P2', 'P3'] as $code) {
            AssessmentPillar::create([
                'framework_id' => $framework->id,
                'code' => $code,
                'name' => $code,
                'weight' => 1,
            ]);
        }

        foreach ([
            ['P1.F1', 'P1', false],
            ['P2.F1', 'P2', false],
            ['P3.F1', 'P3', true],
        ] as [$questionCode, $pillarCode, $isCompliance]) {
            $pillar = AssessmentPillar::where('code', $pillarCode)->first();

            AssessmentQuestionBank::create([
                'framework_id' => $framework->id,
                'pillar_id' => $pillar->id,
                'level' => 'full',
                'question_code' => $questionCode,
                'question_text' => 'Question',
                'is_compliance' => $isCompliance,
            ]);
        }

        foreach ([
            ['P1.F1', 'P1', 2],
            ['P2.F1', 'P2', 4],
            ['P3.F1', 'P3', 5],
        ] as [$questionCode, $pillarCode, $score]) {
            AssessmentQuestionResponse::create([
                'assessment_id' => $assessment->id,
                'pillar_name' => "{$pillarCode} — Test",
                'question' => "{$questionCode}: Question",
                'score' => $score,
                'evidence_note' => "{$questionCode} evidence",
            ]);
        }

        $payload = (new AssessmentAiPayloadBuilder())->buildFullPayload($assessment);

        $this->assertSame(['P1.F1', 'P3.F1'], array_column($payload['question_responses'], 'id'));
        $this->assertArrayHasKey('P1.F1', $payload['evidence_notes']);
        $this->assertArrayHasKey('P3.F1', $payload['evidence_notes']);
        $this->assertArrayNotHasKey('P2.F1', $payload['evidence_notes']);
    }
}
