<?php
/**
 * Run extraction script and show results
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Run the extraction
ob_start();
include __DIR__ . '/extract-all-translations.php';
$output = ob_get_clean();

echo $output;
