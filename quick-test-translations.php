<?php
/**
 * Quick test of translations using TranslationHelper directly
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/translation-helper.php';

$countries = [
    11 => 'Serbia (RS) - Serbian',
    12 => 'Kyrgyzstan (KG) - Russian',
    13 => 'Azerbaijan (AZ) - Azerbaijani',
    14 => 'Poland (PL) - Polish',
];

echo "=== Quick Translation Test ===\n\n";

foreach ($countries as $countryId => $countryName) {
    echo "--- $countryName ---\n";

    // Test category
    $category = TranslationHelper::translateCategory('Dresses', $countryId);
    echo "Category 'Dresses': $category " . ($category !== 'Dresses' ? '✅' : '❌') . "\n";

    // Test params
    $params = [
        'Dresses Type',
        'Clothing Brand',
        'Dresses, skirts - Length',
        'Personal items - Material',
    ];

    foreach ($params as $param) {
        $translated = TranslationHelper::translateParam($param, $countryId);
        $icon = ($translated !== $param) ? '✅' : '❌';
        echo "  $icon '$param' => '$translated'\n";
    }

    // Test values
    $values = ['Evening', 'Cocktail', 'Oversize', 'Dresses other type'];

    foreach ($values as $value) {
        $translated = TranslationHelper::translateValue($value, $countryId);
        $icon = ($translated !== $value) ? '✅' : '❌';
        echo "  $icon '$value' => '$translated'\n";
    }

    echo "\n";
}
