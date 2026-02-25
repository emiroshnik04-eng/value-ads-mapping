<?php
/**
 * Extract REAL leaf categories from full tree JSON
 */

$jsonFile = __DIR__ . '/../../../../seo-microservice/api/tests/_data/rest_category_v2_full_tree.json';

if (!file_exists($jsonFile)) {
    die("JSON file not found: $jsonFile\n");
}

$data = json_decode(file_get_contents($jsonFile), true);

if (!isset($data['data'])) {
    die("Invalid JSON structure\n");
}

// Extract all leaf categories (those with empty children array)
$leafCategories = [];

function extractLeafCategoriesRecursive($categories, &$leafCategories) {
    foreach ($categories as $cat) {
        // Check if this is a leaf (no children)
        if (isset($cat['children']) && empty($cat['children'])) {
            $leafCategories[] = [
                'id' => $cat['id'],
                'name' => $cat['name'],
                'parent_id' => $cat['parent_id'] ?? null,
                'url' => $cat['url'] ?? '',
                'depth' => $cat['depth'] ?? 0
            ];
        }

        // Recursively process children
        if (isset($cat['children']) && !empty($cat['children'])) {
            extractLeafCategoriesRecursive($cat['children'], $leafCategories);
        }
    }
}

extractLeafCategoriesRecursive($data['data'], $leafCategories);

echo "Found " . count($leafCategories) . " leaf categories\n\n";

// Group by depth and show samples
$byDepth = [];
foreach ($leafCategories as $cat) {
    $depth = $cat['depth'];
    if (!isset($byDepth[$depth])) {
        $byDepth[$depth] = [];
    }
    $byDepth[$depth][] = $cat;
}

echo "By depth:\n";
foreach ($byDepth as $depth => $cats) {
    echo "Depth $depth: " . count($cats) . " categories\n";
}

echo "\n\n=== First 50 leaf categories for demo ===\n\n";
echo "'1': [ // Kyrgyzstan - REAL leaf categories from production\n";
foreach (array_slice($leafCategories, 0, 50) as $cat) {
    $name = addslashes($cat['name']);
    echo "    {id: {$cat['id']}, name: '[{$cat['id']}] $name'},\n";
}
echo "],\n";
