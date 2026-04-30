<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AssessmentQuestionBank;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use Illuminate\Support\Facades\DB;

$files = [
    'pir full questions.json' => ['framework' => 'PIR', 'level' => 'FULL'],
    'pir snapshot questions.json' => ['framework' => 'PIR', 'level' => 'DIAGNOSTIC'],
    'sir full questions.json' => ['framework' => 'SIR', 'level' => 'FULL'],
    'sir snapshot questions.json' => ['framework' => 'SIR', 'level' => 'DIAGNOSTIC'],
];

$frameworkMap = [
    'PIR' => 3,
    'SIR' => 4,
];

// Pre-load pillars for efficient lookup
$pillars = AssessmentPillar::all();

foreach ($files as $filename => $meta) {
    $path = base_path($filename);
    if (!file_exists($path)) {
        echo "File not found: $path\n";
        continue;
    }

    $data = json_decode(file_get_contents($path), true);
    if (!$data) {
        echo "Failed to decode JSON from $filename\n";
        continue;
    }

    echo "Importing $filename...\n";

    $frameworkId = $frameworkMap[$meta['framework']];
    $level = strtolower($meta['level']);

    // Clear existing questions for this framework and level
    AssessmentQuestionBank::where('framework_id', $frameworkId)
        ->where('level', $level)
        ->delete();

    foreach ($data as $item) {
        // Extract pillar code (e.g. "PP1 — ..." -> "P1", "PP1" -> "P1")
        $rawPillarCode = $item['pillar_code'];
        $cleanPillarCode = explode(' ', $rawPillarCode)[0];
        // Convert PP1 to P1, DD1 to D1
        if (str_starts_with($cleanPillarCode, 'PP')) {
            $cleanPillarCode = 'P' . substr($cleanPillarCode, 2);
        } elseif (str_starts_with($cleanPillarCode, 'DD')) {
            $cleanPillarCode = 'D' . substr($cleanPillarCode, 2);
        }

        $pillar = $pillars->where('framework_id', $frameworkId)
                          ->where('code', $cleanPillarCode)
                          ->first();

        if (!$pillar) {
            echo "  Warning: Pillar not found for code '$cleanPillarCode' in framework {$meta['framework']}\n";
            continue;
        }

        // Process anchors
        $anchors = [];
        $rawAnchors = null;
        if (!empty($item['score_anchors'])) {
            $rawAnchors = json_decode($item['score_anchors'], true);
        } elseif (!empty($item['select_options'])) {
            $rawAnchors = json_decode($item['select_options'], true);
        }

        if (is_array($rawAnchors)) {
            foreach ($rawAnchors as $index => $text) {
                $anchors[$index + 1] = $text;
            }
        }

        AssessmentQuestionBank::create([
            'framework_id' => $frameworkId,
            'pillar_id' => $pillar->id,
            'level' => $level,
            'question_code' => $item['id'],
            'question_text' => $item['question_text'],
            'hidden_risk' => $item['hidden_risk'] ?? null,
            'stage_note' => $item['stage_note'] ?? null,
            'question_type' => $item['question_type'],
            'question_type_label' => $item['question_type_label'] ?? ($item['display_type'] ?? 'N/A'),
            'score_anchors' => $anchors,
            'is_compliance' => $item['is_compliance'] ?? false,
            'is_hybrid' => $item['is_hybrid'] ?? false,
            'version' => $item['version'] ?? 1,
            'is_active' => $item['published'] ?? true,
            'display_order' => $item['order_index'] ?? 0,
        ]);
    }
}

echo "Import completed!\n";
