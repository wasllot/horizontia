<?php
$log = file_get_contents('/var/www/html/storage/logs/laravel.log');
$lines = explode("\n", $log);
$total = count($lines);
// Find all lines containing "local.ERROR" and print those lines with context
$out = [];
for ($i = 0; $i < $total; $i++) {
    if (str_contains($lines[$i], 'local.ERROR')) {
        $out[] = "=== ERROR at L$i ===";
        // Print this line up to 500 chars
        $out[] = substr($lines[$i], 0, 500);
        $out[] = '';
    }
}
// Print last 10 error entries
echo implode("\n", array_slice($out, -30));
