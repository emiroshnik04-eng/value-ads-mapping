<?php
/**
 * Generate all-categories.js from FULL category tree
 * Source: complex-purchase-microservice (contains ALL countries)
 */

$jsonFile = __DIR__ . '/../../../../complex-purchase-microservice/tests/Support/Data/catalog/category_tree_12.json';

if (!file_exists($jsonFile)) {
    die("JSON file not found: $jsonFile\n");
}

$json = file_get_contents($jsonFile);
$categories = json_decode($json, true);

if (!is_array($categories)) {
    die("Invalid JSON structure\n");
}

// Find all leaf categories (those that are not parents of any other category)
$allIds = array_column($categories, 'id');
$parentIds = array_unique(array_column($categories, 'parent'));
$leafIds = array_diff($allIds, $parentIds);

$leafCategories = array_filter($categories, function($cat) use ($leafIds) {
    return in_array($cat['id'], $leafIds) && $cat['id'] > 1; // Skip root
});

// Sort by ID
usort($leafCategories, function($a, $b) {
    return $a['id'] - $b['id'];
});

echo "Found " . count($leafCategories) . " leaf categories\n\n";
echo "=== Generating all-categories.js ===\n\n";

// Generate JavaScript array
echo "const ALL_LEAF_CATEGORIES = [\n";
foreach ($leafCategories as $cat) {
    $id = $cat['id'];
    $name = addslashes($cat['name']);
    echo "    {id: $id, name: '[$id] $name'},\n";
}
echo "];\n";

echo "\n=== Stats ===\n";
echo "Total leaf categories: " . count($leafCategories) . "\n";
echo "First ID: " . $leafCategories[0]['id'] . "\n";
echo "Last ID: " . end($leafCategories)['id'] . "\n";

// Check if 7859 exists
$found7859 = array_filter($leafCategories, fn($c) => $c['id'] == 7859);
echo "Category 7859 found: " . (empty($found7859) ? "NO" : "YES - " . current($found7859)['name']) . "\n";
