<?php
/**
 * Run extraction and save output to file
 */

ob_start();
try {
    include __DIR__ . '/extract-all-translations.php';
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
$output = ob_get_clean();

file_put_contents(__DIR__ . '/extraction-output.txt', $output);

echo "Extraction complete. Output saved to extraction-output.txt\n";
echo "==========\n\n";
echo $output;
