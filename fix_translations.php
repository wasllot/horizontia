<?php
$enSettings = require 'lang/en/settings.php';
$esSettings = require 'lang/es/settings.php';
$mergedSettings = array_replace_recursive($enSettings, $esSettings);
file_put_contents('lang/es/settings.php', "<?php\n\nreturn " . var_export($mergedSettings, true) . ";\n");

// We can also copy optionbuilder if it exists in packages
if (file_exists('packages/amentotech/optionbuilder/src/lang/en') && !file_exists('packages/amentotech/optionbuilder/src/lang/es')) {
    shell_exec('cp -r packages/amentotech/optionbuilder/src/lang/en packages/amentotech/optionbuilder/src/lang/es');
}

echo "Done.\n";
