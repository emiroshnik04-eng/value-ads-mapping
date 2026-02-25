<?php
/**
 * Trigger extraction - this file can be accessed via browser
 */
header('Content-Type: text/plain; charset=utf-8');

echo "Triggering extraction...\n\n";

// Include and run the extraction script
include __DIR__ . '/auto-extract-and-apply.php';
