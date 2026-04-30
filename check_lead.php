<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$l = \App\Models\Lead::find(38);
if ($l) {
    echo "Type: " . $l->type . "\n";
    echo "Converted: " . ($l->converted_to_client ? 'Yes' : 'No') . "\n";
    echo "Assessment ID: " . ($l->assessment_id ?? 'None') . "\n";
    echo "Index Scores: " . json_encode($l->index_scores_json) . "\n";
} else {
    echo "Lead 38 not found";
}
