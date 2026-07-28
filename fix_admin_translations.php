<?php
$enDir = 'lang/en/admin';
$esDir = 'lang/es/admin';

if (!is_dir($esDir)) {
    mkdir($esDir, 0755, true);
}

$files = scandir($enDir);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $enFile = "$enDir/$file";
        $esFile = "$esDir/$file";
        
        if (!file_exists($esFile)) {
            copy($enFile, $esFile);
        } else {
            $enSettings = require $enFile;
            $esSettings = require $esFile;
            if (is_array($enSettings) && is_array($esSettings)) {
                $mergedSettings = array_replace_recursive($enSettings, $esSettings);
                file_put_contents($esFile, "<?php\n\nreturn " . var_export($mergedSettings, true) . ";\n");
            }
        }
    }
}
echo "Admin translations merged successfully.\n";
