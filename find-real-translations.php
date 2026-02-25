<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== Searching for real translations pattern ===\n\n";

// Check existing translations.php to understand the pattern
$translations = require __DIR__ . '/translations.php';

echo "Looking for color translations that might help:\n\n";

$colorValues = [
    'silver' => null,
    'Silver' => null,
    'blue' => null,
    'Blue' => null,
    'silver iphone' => null,
    'Silver Iphone' => null,
    'Blue-1' => null,
    'blue-1' => null,
];

// Check what's in translations
if (isset($translations['ru']['values'])) {
    foreach ($colorValues as $key => $val) {
        $lowerKey = strtolower($key);

        // Try exact match
        if (isset($translations['ru']['values'][$key])) {
            $colorValues[$key] = $translations['ru']['values'][$key];
        } elseif (isset($translations['ru']['values'][$lowerKey])) {
            $colorValues[$key] = $translations['ru']['values'][$lowerKey];
        }
    }
}

foreach ($colorValues as $en => $ru) {
    if ($ru) {
        echo "✅ '$en' => '$ru'\n";
    } else {
        echo "❌ '$en' => (NO TRANSLATION)\n";
    }
}

echo "\n=== Recommendation ===\n";
echo "For 'Silver Iphone' and 'Blue-1', we need to decide:\n\n";

echo "Option 1: Use simple color name (generic)\n";
echo "  'Silver Iphone' => 'Серебристый'\n";
echo "  'Blue-1' => 'Синий'\n\n";

echo "Option 2: Keep phone context (specific)\n";
echo "  'Silver Iphone' => 'Серебристый iPhone'\n";
echo "  'Blue-1' => 'Синий'\n\n";

echo "Option 3: Leave as is (no translation)\n";
echo "  'Silver Iphone' => 'Silver Iphone'\n";
echo "  'Blue-1' => 'Blue-1'\n\n";

echo "Since these are phone-specific colors that leak into other categories,\n";
echo "the most accurate would be Option 1 - generic color names.\n";
