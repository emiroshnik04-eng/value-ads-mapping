<?php
echo "Current directory: " . getcwd() . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "__FILE__: " . __FILE__ . "\n\n";

$translationFile = __DIR__ . '/translations.php';
echo "Looking for translations.php: $translationFile\n";
echo "File exists: " . (file_exists($translationFile) ? 'YES' : 'NO') . "\n";

if (file_exists($translationFile)) {
    echo "File is readable: " . (is_readable($translationFile) ? 'YES' : 'NO') . "\n\n";

    echo "Testing require...\n";
    try {
        $translations = require $translationFile;
        echo "SUCCESS! Loaded translations\n";
        print_r(array_keys($translations));
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "\nSearching for translations.php in current directory...\n";
    $files = glob('*.php');
    echo "PHP files found: " . count($files) . "\n";
    foreach ($files as $file) {
        if (strpos($file, 'translation') !== false) {
            echo "  - $file\n";
        }
    }
}
