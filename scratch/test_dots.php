<?php
// Test PHP dot conversion in POST names
$_POST['P1.S1'] = '5';
// PHP doesn't automatically convert manually assigned $_POST keys, 
// it only does it during initial request parsing.
// But we can simulate what happens in a real request.

$raw = ['P1.S1' => '5'];
// In a real request, PHP would have already converted it.
// Let's assume the user is right and it's missing.

print_r($raw);
echo "\n";
echo "isset('P1.S1'): " . (isset($raw['P1.S1']) ? 'yes' : 'no') . "\n";
echo "isset('P1_S1'): " . (isset($raw['P1_S1']) ? 'yes' : 'no') . "\n";
