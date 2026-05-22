<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentPillarScore;
use App\Models\AssessmentQuestionBank;
use App\Models\AssessmentQuestionResponse;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class PirFullTier1PayloadFixtureSeeder extends Seeder
{
    private const ASSESSMENT_NAME = 'PIR Full Tier 1 Payload Fixture';

    private const PILLAR_NAMES = [
        'P1' => 'Governance & Decision-Making',
        'P2' => 'Planning, Stage Gates & Delivery Control',
        'P3' => 'Business Alignment, Value & Financial Control',
        'P4' => 'Change Management, Training & Adoption',
        'P5' => 'Data Readiness, Migration & GDPR',
        'P6' => 'Solution, Process Fit & UAT',
        'P7' => 'Cutover, Go-Live, Decommissioning & Archiving',
        'P8' => 'Delivery Capability, Security & RACI',
        'P9' => 'Operational, Automation Readiness & Data Archiving',
        'P10' => 'Digital & Transformation Maturity',
    ];

    public function run(): void
    {
        $framework = AssessmentFramework::updateOrCreate(
            ['code' => 'PIR'],
            ['name' => 'Programme Implementation Review', 'is_active' => true]
        );

        $pillars = $this->ensurePillars($framework);
        $questions = $this->ensureQuestions($framework, $pillars);

        $client = Client::updateOrCreate(
            ['company_name' => 'RAB Fixture Client Ltd'],
            [
                'primary_contact' => 'Alex Sponsor',
                'email' => 'fixture-client@example.com',
                'industry' => 'Technology',
            ]
        );

        $consultant = User::updateOrCreate(
            ['email' => 'fixture-consultant@example.com'],
            [
                'name' => 'Reda Boukhiar',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        Assessment::where('name', self::ASSESSMENT_NAME)->delete();

        $assessment = Assessment::create([
            'client_id' => $client->id,
            'assessor_id' => $consultant->id,
            'name' => self::ASSESSMENT_NAME,
            'target_entity' => 'ERP Replacement Programme',
            'type' => 'PIR',
            'report_tier' => 'Tier 1 Rapid',
            'overall_score' => 2.84,
            'rag_status' => 'Amber',
            'status' => 'in_progress',
            'bri' => 2.70,
            'vri' => 2.90,
            'dmi' => 2.40,
            'rii' => 2.60,
            'chi' => 2.30,
            'delivery_stage' => 'Build',
            'client_concerns' => 'Sponsor is concerned that RAID reporting and cutover readiness do not match delivery reality.',
            'regulatory_context' => 'fca_uk',
            'sponsor_name' => 'Alex Sponsor',
            'interview_count' => 4,
            'documents_reviewed' => ['RAID log', 'Integrated plan', 'Steering pack', 'Cutover draft', 'Data migration tracker'],
            'programme_value' => 1850000,
            'reporting_accuracy_risk' => true,
            'reporting_accuracy_evidence' => 'Steering pack reports amber-green while RAID and cutover evidence show unresolved critical dependencies.',
        ]);

        foreach ($this->pillarScores() as $code => $score) {
            AssessmentPillarScore::create([
                'assessment_id' => $assessment->id,
                'name' => "{$code} — " . self::PILLAR_NAMES[$code],
                'score' => $score,
                'rag_status' => $score < 2.5 ? 'Red' : ($score < 3.8 ? 'Amber' : 'Green'),
                'critical_flag' => in_array($code, ['P5', 'P7'], true),
            ]);
        }

        foreach ($this->responses() as $response) {
            $question = $questions[$response['code']];

            AssessmentQuestionResponse::create([
                'assessment_id' => $assessment->id,
                'pillar_name' => $response['pillar'] . ' — ' . self::PILLAR_NAMES[$response['pillar']],
                'question' => $response['code'] . ': ' . $question->question_text,
                'score' => $response['score'],
                'evidence_note' => $response['note'],
                'source_type' => $response['source_type'],
                'respondent_role' => $response['respondent'],
                'document_source' => $response['source'],
                'confidence' => strtolower($response['confidence']),
                'confidence_level' => strtolower($response['confidence']),
            ]);
        }

        $this->command?->info("Created PIR Full Tier 1 payload fixture assessment {$assessment->id}.");
    }

    private function ensurePillars(AssessmentFramework $framework): array
    {
        $pillars = [];

        foreach (self::PILLAR_NAMES as $code => $name) {
            $pillars[$code] = AssessmentPillar::updateOrCreate(
                ['framework_id' => $framework->id, 'code' => $code],
                ['name' => $name, 'weight' => 1.0, 'is_critical' => in_array($code, ['P1', 'P2', 'P5', 'P7'], true)]
            );
        }

        return $pillars;
    }

    private function ensureQuestions(AssessmentFramework $framework, array $pillars): array
    {
        $questions = [];

        foreach ($this->questionDefinitions() as $definition) {
            $questions[$definition['code']] = AssessmentQuestionBank::updateOrCreate(
                ['framework_id' => $framework->id, 'question_code' => $definition['code']],
                [
                    'pillar_id' => $pillars[$definition['pillar']]->id,
                    'level' => 'full',
                    'question_text' => $definition['text'],
                    'is_compliance' => $definition['is_compliance'],
                    'is_active' => true,
                ]
            );
        }

        return $questions;
    }

    private function pillarScores(): array
    {
        return [
            'P1' => 2.60,
            'P2' => 2.80,
            'P3' => 3.10,
            'P4' => 3.00,
            'P5' => 2.20,
            'P6' => 3.20,
            'P7' => 2.30,
            'P8' => 3.40,
            'P9' => 2.90,
            'P10' => 3.10,
        ];
    }

    private function questionDefinitions(): array
    {
        return [
            ['code' => 'P1.F1', 'pillar' => 'P1', 'text' => 'Sponsor authority is clear and active.', 'is_compliance' => false],
            ['code' => 'P1.F8', 'pillar' => 'P1', 'text' => 'Decision logs and action ownership are maintained.', 'is_compliance' => true],
            ['code' => 'P2.F2', 'pillar' => 'P2', 'text' => 'Critical path and dependencies are visible and controlled.', 'is_compliance' => false],
            ['code' => 'P3.F4', 'pillar' => 'P3', 'text' => 'Budget tracking is accurate and current.', 'is_compliance' => false],
            ['code' => 'P3.F11', 'pillar' => 'P3', 'text' => 'Financial controls support accurate reporting.', 'is_compliance' => true],
            ['code' => 'P4.F3', 'pillar' => 'P4', 'text' => 'Training plans are role-based and practical.', 'is_compliance' => false],
            ['code' => 'P5.F3', 'pillar' => 'P5', 'text' => 'Data quality issues are visible and managed.', 'is_compliance' => false],
            ['code' => 'P5.F9', 'pillar' => 'P5', 'text' => 'GDPR and data retention requirements are understood.', 'is_compliance' => true],
            ['code' => 'P6.F4', 'pillar' => 'P6', 'text' => 'Integration complexity is understood.', 'is_compliance' => false],
            ['code' => 'P7.F4', 'pillar' => 'P7', 'text' => 'Go / no-go criteria are defined.', 'is_compliance' => false],
            ['code' => 'P8.F6', 'pillar' => 'P8', 'text' => 'Commercial accountabilities are clear.', 'is_compliance' => false],
            ['code' => 'P9.F5', 'pillar' => 'P9', 'text' => 'Operational reporting requirements are clear.', 'is_compliance' => false],
            ['code' => 'P10.F2', 'pillar' => 'P10', 'text' => 'Transformation maturity is understood and actively improved.', 'is_compliance' => false],
        ];
    }

    private function responses(): array
    {
        return [
            ['code' => 'P1.F1', 'pillar' => 'P1', 'score' => 2, 'confidence' => 'High', 'source_type' => 'interview', 'respondent' => 'Programme Sponsor', 'source' => 'Sponsor interview', 'note' => 'Sponsor confirmed decisions are still escalated informally outside the steering forum.'],
            ['code' => 'P1.F8', 'pillar' => 'P1', 'score' => 2, 'confidence' => 'High', 'source_type' => 'document', 'respondent' => 'PMO Lead', 'source' => 'Decision log extract', 'note' => 'Decision log has four open decisions older than 30 days with no named accountable owner.'],
            ['code' => 'P2.F2', 'pillar' => 'P2', 'score' => 3, 'confidence' => 'Medium', 'source_type' => 'document', 'respondent' => 'Programme Planner', 'source' => 'Integrated plan', 'note' => 'Critical path exists but dependency dates for data cleansing and UAT entry are not baselined.'],
            ['code' => 'P3.F4', 'pillar' => 'P3', 'score' => 3, 'confidence' => 'Medium', 'source_type' => 'document', 'respondent' => 'Finance Business Partner', 'source' => 'Finance tracker', 'note' => 'Budget tracker is current, but forecast-to-complete excludes two approved change requests.'],
            ['code' => 'P3.F11', 'pillar' => 'P3', 'score' => 2, 'confidence' => 'High', 'source_type' => 'document', 'respondent' => 'Finance Business Partner', 'source' => 'Steering pack and RAID log', 'note' => 'Financial status is reported amber-green while RAID evidence shows unresolved cost exposure.'],
            ['code' => 'P4.F3', 'pillar' => 'P4', 'score' => 3, 'confidence' => 'Medium', 'source_type' => 'workshop', 'respondent' => 'Change Lead', 'source' => 'Training readiness workshop', 'note' => 'Training plan is role-based, but super-user availability is not confirmed for two business areas.'],
            ['code' => 'P5.F3', 'pillar' => 'P5', 'score' => 2, 'confidence' => 'High', 'source_type' => 'document', 'respondent' => 'Data Migration Lead', 'source' => 'Data defect tracker', 'note' => 'Data defect tracker shows 38 high-priority defects and no agreed daily burn-down target.'],
            ['code' => 'P5.F9', 'pillar' => 'P5', 'score' => 2, 'confidence' => 'Medium', 'source_type' => 'document', 'respondent' => 'Data Protection Officer', 'source' => 'GDPR checklist', 'note' => 'Retention decisions for archived customer records remain marked as pending legal confirmation.'],
            ['code' => 'P6.F4', 'pillar' => 'P6', 'score' => 3, 'confidence' => 'Medium', 'source_type' => 'interview', 'respondent' => 'Solution Architect', 'source' => 'Architecture review', 'note' => 'Integration risk is understood, but the payment interface test window is compressed to one cycle.'],
            ['code' => 'P7.F4', 'pillar' => 'P7', 'score' => 2, 'confidence' => 'High', 'source_type' => 'document', 'respondent' => 'Cutover Manager', 'source' => 'Cutover draft', 'note' => 'Go/no-go criteria are drafted but lack objective thresholds for data reconciliation and support readiness.'],
            ['code' => 'P8.F6', 'pillar' => 'P8', 'score' => 3, 'confidence' => 'Medium', 'source_type' => 'document', 'respondent' => 'Commercial Manager', 'source' => 'SI statement of work', 'note' => 'Commercial responsibilities are defined, but defect triage ownership between SI and client teams is disputed.'],
            ['code' => 'P9.F5', 'pillar' => 'P9', 'score' => 3, 'confidence' => 'Low', 'source_type' => 'interview', 'respondent' => 'Service Transition Lead', 'source' => 'Service transition interview', 'note' => 'Operational reporting requirements are described verbally but are not yet captured in the acceptance checklist.'],
            ['code' => 'P10.F2', 'pillar' => 'P10', 'score' => 3, 'confidence' => 'Medium', 'source_type' => 'workshop', 'respondent' => 'Transformation Lead', 'source' => 'Transformation maturity workshop', 'note' => 'Transformation maturity goals are agreed, but no metric owner is assigned for adoption improvement after go-live.'],
        ];
    }
}
