<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$framework = App\Models\AssessmentFramework::where('code', 'PIR')->first();
$pillar = App\Models\AssessmentPillar::where('framework_id', $framework->id)->with(['questions' => function($q) {
    $q->where('level', 'snapshot')->where('is_active', true);
}])->first();

$q = $pillar->questions->first();
var_dump($q->score_anchors);
var_dump(is_array($q->score_anchors));
