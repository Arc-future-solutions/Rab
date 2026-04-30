<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentQuestionBank;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class JsonAssessmentBankSeeder extends Seeder
{
    public function run(): void
    {
        // Delete old frameworks PHI and ITSM
        $oldFrameworks = AssessmentFramework::whereIn('code', ['PHI', 'ITSM'])->get();
        foreach ($oldFrameworks as $fw) {
            // Pillars and questions will be deleted via cascade if set up, 
            // but let's be explicit to be safe.
            foreach ($fw->pillars as $pillar) {
                AssessmentQuestionBank::where('pillar_id', $pillar->id)->delete();
                $pillar->delete();
            }
            $fw->delete();
        }
        $this->command->info("Old frameworks (PHI, ITSM) and their data deleted.");

        $jsonPath = base_path('questions.json');
        if (!File::exists($jsonPath)) {
            $this->command->error("questions.json not found at {$jsonPath}");
            return;
        }

        $data = json_decode(File::get($jsonPath), true);
        if (!$data) {
            $this->command->error("Failed to decode questions.json");
            return;
        }

        // Define new weights
        $pirWeights = json_decode('{"P1":1.5,"P2":1.4,"P3":1.3,"P4":1.2,"P5":1.2,"P6":1.1,"P7":1.2,"P8":1.1,"P9":1.0,"P10":1.1}', true);
        $sirWeights = json_decode('{"D1":1.4,"D2":1.4,"D3":1.0,"D4":1.2,"D5":1.3,"D6":1.2,"D7":1.2,"D8":1.2,"D9":1.0,"D10":1.3,"D11":0.9,"D12":1.1}', true);

        foreach ($data as $key => $questions) {
            $parts = explode('_', $key);
            $frameworkCode = $parts[0]; // PIR, SIR
            $level = strtolower($parts[1]); // diagnostic, full
            
            if ($level === 'diagnostic') {
                $level = 'snapshot';
            }

            $frameworkName = $frameworkCode === 'PIR' ? 'Programme Implementation Review' : 'Service Implementation Review';
            
            $framework = AssessmentFramework::updateOrCreate(
                ['code' => $frameworkCode],
                ['name' => $frameworkName, 'is_active' => true]
            );

            foreach ($questions as $qData) {
                $pillarCode = $qData['pillar_code'];
                $weight = 1.0;
                if ($frameworkCode === 'PIR') {
                    $weight = $pirWeights[$pillarCode] ?? 1.0;
                } elseif ($frameworkCode === 'SIR') {
                    $weight = $sirWeights[$pillarCode] ?? 1.0;
                }

                $pillar = AssessmentPillar::updateOrCreate(
                    ['framework_id' => $framework->id, 'code' => $pillarCode],
                    ['name' => $qData['pillar_name'], 'weight' => $weight]
                );

                // Use DB table directly to avoid Eloquent cast issues during seeding
                $existing = DB::table('assessment_questions')
                    ->where('framework_id', $framework->id)
                    ->where('question_code', $qData['id'])
                    ->first();

                $questionData = [
                    'pillar_id' => $pillar->id,
                    'level' => $level,
                    'question_text' => $qData['question_text'],
                    'hidden_risk' => $qData['hidden_risk'] ?? null,
                    'stage_note' => $qData['stage_note'] ?? null,
                    'question_type' => $qData['question_type'] ?? null,
                    'question_type_label' => $qData['question_type_label'] ?? null,
                    'score_anchors' => isset($qData['score_anchors']) ? json_encode($qData['score_anchors']) : null,
                    'is_active' => true,
                    'updated_at' => now(),
                ];

                if ($existing) {
                    DB::table('assessment_questions')
                        ->where('id', $existing->id)
                        ->update($questionData);
                } else {
                    $questionData['framework_id'] = $framework->id;
                    $questionData['question_code'] = $qData['id'];
                    $questionData['created_at'] = now();
                    DB::table('assessment_questions')->insert($questionData);
                }
            }
        }

        $this->command->info("Assessment bank updated from questions.json successfully with new weights.");
    }
}
