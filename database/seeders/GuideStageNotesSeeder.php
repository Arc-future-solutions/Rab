<?php

namespace Database\Seeders;

use App\Models\AssessmentFramework;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GuideStageNotesSeeder extends Seeder
{
    public function run(): void
    {
        $notes = [
            'PIR' => [
                'P5.F1' => 'At Design: score whether data migration strategy exists as a plan. At Build: strategy is being executed. At Test: mock migrations completed. At Cutover: production-readiness confirmed.',
                'P5.F2' => 'At Design: data owners should be identified. At Build/Test: owners actively engaged in data quality management.',
                'P5.F3' => 'At Design: data quality assessment should have commenced. At Build: profiling complete. At Test/Cutover: remediation evidenced.',
                'P5.F4' => 'At Design: cleansing strategy defined. At Build: cleansing underway. At Test: substantially complete.',
                'P5.F5' => 'Not expected at Design. At Build: first mock planned. At Test: at least one mock completed. At Cutover: multiple rehearsals with documented outcomes expected.',
                'P5.F6' => 'Not expected at Design. At Build: reconciliation approach defined. At Test: reconciliation tested in mock migrations.',
                'P5.F7' => 'Not expected before Build. At Test/Cutover: active business validation expected.',
                'P6.F5' => 'At Design: key decisions in progress. At Build: formally validated. At Test: validate decisions under testing conditions.',
                'P6.F7' => 'At Design/early Build: workarounds being identified. At UAT/Cutover: formally accepted or eliminated.',
                'P7.F1' => 'Not expected before Test. At UAT: data migration tested. At Cutover Prep: full end-to-end in production-equivalent environment expected.',
                'P7.F2' => 'Not expected before Test. Reconciliation framework should be defined by Build.',
                'P7.F5' => 'At Design: Day 1 process design commenced. At Build: substantially defined. At Cutover: all confirmed and trained.',
                'P7.F9' => 'Not expected before Cutover Prep. At Build/Test: cutover runbook in development.',
                'P7.F11' => 'Not expected before Test. At Cutover Prep: at least one full rehearsal completed and findings resolved.',
                'P7.F14' => 'At Design: hypercare model defined. At Build: resourcing confirmed. At Go-Live: hypercare team deployed and operational.',
                'P9.F1' => 'At Design: operational reporting requirements identified. At Build: reporting design confirmed. At Test: validated. At Go-Live: live and accepted.',
                'P9.F3' => 'Not expected before Build. At Test/Go-Live: AI/automation components tested and governed.',
                'P2.F9' => 'At Mobilisation: Change Control process and authority must be defined before any delivery commences. At Design: process active for design decisions. At Build/Test: no scope, technical, or budget changes without formal approval — this is the highest-risk period for undocumented change. At Cutover: strict change freeze or emergency-only process in force.',
            ],
            'SIR' => [
                'D7.F1' => 'NSI: assess whether transition plan is complete before introduction. Transformation: assess whether service can absorb change alongside current load.',
                'D3.F1' => 'Under Pressure: assess whether change freeze has been considered. Transformation: assess whether change capacity is explicitly managed.',
                'D10.F1' => 'Legacy Pre-Retirement: assess whether continuity is formally planned through wind-down.',
                'D8.F1' => 'NSI: D8 capacity score is the primary BAU readiness signal. Below 3.0 = service cannot safely absorb the new introduction.',
                'D11.F1' => 'Transformation: D11 governance and CMDB accuracy must be assessed before any structural change proceeds.',
            ],
        ];

        $updated = 0;
        foreach ($notes as $frameworkCode => $frameworkNotes) {
            $framework = AssessmentFramework::where('code', $frameworkCode)->first();
            if (!$framework) {
                continue;
            }

            foreach ($frameworkNotes as $questionCode => $stageNote) {
                $updated += DB::table('assessment_questions')
                    ->where('framework_id', $framework->id)
                    ->where('question_code', $questionCode)
                    ->where('level', 'full')
                    ->update(['stage_note' => $stageNote, 'updated_at' => now()]);
            }
        }

        $this->command?->info("Applied {$updated} stage/context notes from the build guide.");
    }
}
