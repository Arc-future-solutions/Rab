<?php

namespace Tests\Feature;

use Database\Seeders\AdditionalAssessmentQuestionsSeeder;
use Database\Seeders\GuideStageNotesSeeder;
use Database\Seeders\JsonAssessmentBankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuideStageNotesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_guide_stage_notes_are_applied_to_all_23_full_questions(): void
    {
        $this->seed(JsonAssessmentBankSeeder::class);
        $this->seed(AdditionalAssessmentQuestionsSeeder::class);
        $this->seed(GuideStageNotesSeeder::class);

        $expectedNotes = [
            'PIR:P5.F1' => 'At Design: score whether data migration strategy exists as a plan. At Build: strategy is being executed. At Test: mock migrations completed. At Cutover: production-readiness confirmed.',
            'PIR:P5.F2' => 'At Design: data owners should be identified. At Build/Test: owners actively engaged in data quality management.',
            'PIR:P5.F3' => 'At Design: data quality assessment should have commenced. At Build: profiling complete. At Test/Cutover: remediation evidenced.',
            'PIR:P5.F4' => 'At Design: cleansing strategy defined. At Build: cleansing underway. At Test: substantially complete.',
            'PIR:P5.F5' => 'Not expected at Design. At Build: first mock planned. At Test: at least one mock completed. At Cutover: multiple rehearsals with documented outcomes expected.',
            'PIR:P5.F6' => 'Not expected at Design. At Build: reconciliation approach defined. At Test: reconciliation tested in mock migrations.',
            'PIR:P5.F7' => 'Not expected before Build. At Test/Cutover: active business validation expected.',
            'PIR:P6.F5' => 'At Design: key decisions in progress. At Build: formally validated. At Test: validate decisions under testing conditions.',
            'PIR:P6.F7' => 'At Design/early Build: workarounds being identified. At UAT/Cutover: formally accepted or eliminated.',
            'PIR:P7.F1' => 'Not expected before Test. At UAT: data migration tested. At Cutover Prep: full end-to-end in production-equivalent environment expected.',
            'PIR:P7.F2' => 'Not expected before Test. Reconciliation framework should be defined by Build.',
            'PIR:P7.F5' => 'At Design: Day 1 process design commenced. At Build: substantially defined. At Cutover: all confirmed and trained.',
            'PIR:P7.F9' => 'Not expected before Cutover Prep. At Build/Test: cutover runbook in development.',
            'PIR:P7.F11' => 'Not expected before Test. At Cutover Prep: at least one full rehearsal completed and findings resolved.',
            'PIR:P7.F14' => 'At Design: hypercare model defined. At Build: resourcing confirmed. At Go-Live: hypercare team deployed and operational.',
            'PIR:P9.F1' => 'At Design: operational reporting requirements identified. At Build: reporting design confirmed. At Test: validated. At Go-Live: live and accepted.',
            'PIR:P9.F3' => 'Not expected before Build. At Test/Go-Live: AI/automation components tested and governed.',
            'PIR:P2.F9' => 'At Mobilisation: Change Control process and authority must be defined before any delivery commences. At Design: process active for design decisions. At Build/Test: no scope, technical, or budget changes without formal approval — this is the highest-risk period for undocumented change. At Cutover: strict change freeze or emergency-only process in force.',
            'SIR:D7.F1' => 'NSI: assess whether transition plan is complete before introduction. Transformation: assess whether service can absorb change alongside current load.',
            'SIR:D3.F1' => 'Under Pressure: assess whether change freeze has been considered. Transformation: assess whether change capacity is explicitly managed.',
            'SIR:D10.F1' => 'Legacy Pre-Retirement: assess whether continuity is formally planned through wind-down.',
            'SIR:D8.F1' => 'NSI: D8 capacity score is the primary BAU readiness signal. Below 3.0 = service cannot safely absorb the new introduction.',
            'SIR:D11.F1' => 'Transformation: D11 governance and CMDB accuracy must be assessed before any structural change proceeds.',
        ];

        $notes = DB::table('assessment_questions')
            ->join('assessment_frameworks', 'assessment_frameworks.id', '=', 'assessment_questions.framework_id')
            ->where('assessment_questions.level', 'full')
            ->whereIn('assessment_questions.question_code', collect(array_keys($expectedNotes))->map(
                fn (string $key) => explode(':', $key)[1]
            ))
            ->select('assessment_frameworks.code as framework', 'assessment_questions.question_code', 'assessment_questions.stage_note')
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->framework}:{$row->question_code}" => $row->stage_note])
            ->all();

        ksort($expectedNotes);
        ksort($notes);

        $this->assertCount(23, $notes);
        $this->assertSame($expectedNotes, $notes);
    }
}
