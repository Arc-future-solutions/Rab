<?php

namespace Database\Seeders;

use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AdditionalAssessmentQuestionsSeeder extends Seeder
{
    private array $pirPillars = [
        'P1' => ['name' => 'Governance & Decision-Making', 'weight' => 1.5, 'critical' => true],
        'P2' => ['name' => 'Planning, Stage Gates & Delivery Control', 'weight' => 1.4, 'critical' => true],
        'P3' => ['name' => 'Business Alignment, Value & Financial Control', 'weight' => 1.3, 'critical' => true],
        'P4' => ['name' => 'Change Management, Training & Adoption', 'weight' => 1.2, 'critical' => false],
        'P5' => ['name' => 'Data Readiness, Migration & GDPR', 'weight' => 1.2, 'critical' => false],
        'P6' => ['name' => 'Solution, Process Fit & UAT', 'weight' => 1.1, 'critical' => false],
        'P7' => ['name' => 'Cutover, Go-Live, Decommissioning & Archiving', 'weight' => 1.2, 'critical' => true],
        'P8' => ['name' => 'Delivery Capability, Security & RACI', 'weight' => 1.1, 'critical' => false],
        'P9' => ['name' => 'Operational, Automation Readiness & Data Archiving', 'weight' => 1.0, 'critical' => false],
        'P10' => ['name' => 'Digital & Transformation Maturity', 'weight' => 1.1, 'critical' => false],
    ];

    private array $sirDomains = [
        'D1' => ['name' => 'Service Governance & Ownership', 'weight' => 1.4, 'critical' => true],
        'D2' => ['name' => 'Incident & Major Incident Management', 'weight' => 1.4, 'critical' => true],
        'D3' => ['name' => 'Service Request Management', 'weight' => 1.0, 'critical' => false],
        'D4' => ['name' => 'Problem Management', 'weight' => 1.2, 'critical' => false],
        'D5' => ['name' => 'Change & Release Management', 'weight' => 1.3, 'critical' => true],
        'D6' => ['name' => 'Service Performance, SLA & Reporting', 'weight' => 1.2, 'critical' => false],
        'D7' => ['name' => 'Service Transition & BAU Readiness', 'weight' => 1.2, 'critical' => true],
        'D8' => ['name' => 'Service Operations & Support Model', 'weight' => 1.2, 'critical' => false],
        'D9' => ['name' => 'Supplier & Vendor Management', 'weight' => 1.0, 'critical' => false],
        'D10' => ['name' => 'Operational Resilience & Continuity', 'weight' => 1.3, 'critical' => true],
        'D11' => ['name' => 'Service Tooling, CMDB & Knowledge Management', 'weight' => 1.2, 'critical' => false],
        'D12' => ['name' => 'Service Intelligence & Continuous Value', 'weight' => 1.1, 'critical' => false],
    ];

    public function run(): void
    {
        $this->seedFile(base_path('docs/pir_additional_questions_final.json'), 'PIR');
        $this->seedFile(base_path('docs/sir_additional_questions.json'), 'SIR');
        $this->markBaseComplianceQuestions();
    }

    private function seedFile(string $path, string $frameworkCode): void
    {
        if (!File::exists($path)) {
            $this->command?->error("Missing file: {$path}");
            return;
        }

        $questions = json_decode(File::get($path), true);
        if (!is_array($questions)) {
            $this->command?->error("Invalid JSON: {$path}");
            return;
        }

        $framework = AssessmentFramework::updateOrCreate(
            ['code' => $frameworkCode],
            [
                'name' => $frameworkCode === 'PIR' ? 'Programme Intelligence Review' : 'Service Intelligence Review',
                'is_active' => true,
            ]
        );

        $count = 0;
        foreach ($questions as $question) {
            $pillarCode = $this->normalisePillarCode($question['pillar_code'] ?? '');
            if (!$pillarCode) {
                continue;
            }

            $pillar = $this->upsertPillar($framework, $frameworkCode, $pillarCode);
            $scoreAnchors = $question['score_anchors'] ?? null;
            if (is_array($scoreAnchors)) {
                $scoreAnchors = json_encode($scoreAnchors);
            }

            DB::table('assessment_questions')->updateOrInsert(
                [
                    'framework_id' => $framework->id,
                    'question_code' => $question['id'],
                ],
                [
                    'pillar_id' => $pillar->id,
                    'level' => strtolower($question['assessment_type'] ?? 'FULL'),
                    'question_text' => $question['question_text'],
                    'hidden_risk' => $question['hidden_risk'] ?? null,
                    'stage_note' => $question['stage_note'] ?? null,
                    'question_type' => $question['question_type'] ?? null,
                    'question_type_label' => $question['display_type'] ?? null,
                    'score_anchors' => $scoreAnchors,
                    'is_compliance' => (bool) ($question['is_compliance'] ?? false),
                    'is_hybrid' => (bool) ($question['is_hybrid'] ?? false),
                    'hybrid_context' => $question['hybrid_context'] ?? null,
                    'version' => (int) ($question['version'] ?? 1),
                    'is_active' => (bool) ($question['published'] ?? true),
                    'display_order' => (int) ($question['order_index'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $count++;
        }

        $this->command?->info("Seeded {$count} {$frameworkCode} additional questions from " . basename($path));
    }

    private function upsertPillar(AssessmentFramework $framework, string $frameworkCode, string $pillarCode): AssessmentPillar
    {
        $definition = $frameworkCode === 'PIR'
            ? ($this->pirPillars[$pillarCode] ?? null)
            : ($this->sirDomains[$pillarCode] ?? null);

        return AssessmentPillar::updateOrCreate(
            ['framework_id' => $framework->id, 'code' => $pillarCode],
            [
                'name' => $definition['name'] ?? $pillarCode,
                'weight' => $definition['weight'] ?? 1.0,
                'is_critical' => $definition['critical'] ?? false,
                'display_order' => (int) preg_replace('/\D/', '', $pillarCode),
            ]
        );
    }

    private function normalisePillarCode(string $code): string
    {
        if (preg_match('/^PP(\d+)/', $code, $matches)) {
            return 'P' . $matches[1];
        }

        if (preg_match('/^DD(\d+)/', $code, $matches)) {
            return 'D' . $matches[1];
        }

        if (preg_match('/^[PD]\d+/', $code, $matches)) {
            return $matches[0];
        }

        return '';
    }

    private function markBaseComplianceQuestions(): void
    {
        $baseCompliance = [
            'PIR' => ['P1.F8', 'P3.F11', 'P5.F8', 'P5.F9', 'P8.F8', 'P8.F9'],
            'SIR' => ['D1.F5', 'D1.F7', 'D3.F5', 'D5.F4', 'D9.F3'],
        ];

        foreach ($baseCompliance as $frameworkCode => $questionCodes) {
            $framework = AssessmentFramework::where('code', $frameworkCode)->first();
            if (!$framework) {
                continue;
            }

            DB::table('assessment_questions')
                ->where('framework_id', $framework->id)
                ->whereIn('question_code', $questionCodes)
                ->update(['is_compliance' => true, 'updated_at' => now()]);
        }

        $this->command?->info('Applied base compliance flags required by the build guide.');
    }
}
