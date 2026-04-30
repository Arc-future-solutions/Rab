<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = app(App\Http\Controllers\RapidConsultingController::class);
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('getFrameworkQuestions');
$method->setAccessible(true);

foreach (['PIR', 'SIR'] as $type) {
    $questions = $method->invokeArgs($controller, [$type]);
    foreach ($questions as $pCode => $p) {
        foreach ($p['questions'] as $qCode => $qData) {
            if (!array_key_exists('anchors', $qData)) {
                echo "Missing anchors in $type - $pCode - $qCode\n";
            }
        }
    }
}
echo "Done\n";
