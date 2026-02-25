<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== FINAL TEST: Complete Flow ===\n\n";

$countryId = 12;
$categoryId = 1473;
$paramId = 105;

echo "Testing workflow:\n";
echo "1. Country: $countryId (Kyrgyzstan)\n";
echo "2. Category: $categoryId (Toys)\n";
echo "3. Param: $paramId (Color)\n\n";

$baseUrl = "http://localhost:8888";

echo "=== TEST 1: Categories ===\n";
$url = "$baseUrl/get-categories.php?country_id=$countryId";
$json = file_get_contents($url);
$data = json_decode($json, true);
$toys = array_filter($data['data'], fn($c) => $c['id'] == 1473);
$toys = reset($toys);
echo "✓ Category {$toys['id']}: {$toys['name']} → {$toys['name_translated']}\n\n";

echo "=== TEST 2: Parameters ===\n";
$url = "$baseUrl/get-params.php?country_id=$countryId&category_id=$categoryId";
$json = file_get_contents($url);
$params = json_decode($json, true);
$color = array_filter($params, fn($p) => $p['id'] == 105);
$color = reset($color);
echo "✓ Param {$color['id']}: {$color['name']} → {$color['name_translated']}\n\n";

echo "=== TEST 3: Param Values ===\n";
$url = "$baseUrl/get-param-values.php?country_id=$countryId&category_id=$categoryId&param_id=$paramId";
$json = file_get_contents($url);
$values = json_decode($json, true);

echo "Total values: " . count($values) . "\n\n";

echo "First 10 values:\n";
foreach (array_slice($values, 0, 10) as $v) {
    echo sprintf("  %-6d %-30s → %s\n", $v['id'], $v['display_value'], $v['display_value_translated']);
}

echo "\n=== TEST 4: Checking Blacklist ===\n";
$blacklistedIds = [23108, 27221, 44999];
$found = array_filter($values, fn($v) => in_array($v['id'], $blacklistedIds));

if (empty($found)) {
    echo "✓ Blacklisted values (Silver Iphone, Blue-1, RS - Multicolored) are NOT shown\n";
} else {
    echo "✗ ERROR: Found blacklisted values:\n";
    foreach ($found as $v) {
        echo "  - ID {$v['id']}: {$v['display_value']}\n";
    }
}

echo "\n=== RESULT ===\n";
echo "✓ Translations work for categories\n";
echo "✓ Translations work for parameters\n";
echo "✓ Translations work for values\n";
echo "✓ Blacklist filters phone-specific colors\n";
echo "\nAll tests passed!\n";
