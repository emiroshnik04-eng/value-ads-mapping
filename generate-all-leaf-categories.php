<?php
/**
 * Generate ALL 1724 leaf categories for demo HTML
 */

$jsonFile = __DIR__ . '/../../../../seo-microservice/api/tests/_data/rest_category_v2_full_tree.json';
$data = json_decode(file_get_contents($jsonFile), true);

$leafCategories = [];

function extractLeafCategories($categories, &$leafCategories) {
    foreach ($categories as $cat) {
        if (isset($cat['children']) && empty($cat['children'])) {
            $leafCategories[] = $cat;
        }
        if (isset($cat['children']) && !empty($cat['children'])) {
            extractLeafCategories($cat['children'], $leafCategories);
        }
    }
}

extractLeafCategories($data['data'], $leafCategories);

echo "Found " . count($leafCategories) . " leaf categories\n\n";
echo "=== ALL leaf categories for JavaScript ===\n\n";
echo "const ALL_LEAF_CATEGORIES = [\n";

foreach ($leafCategories as $cat) {
    $name = addslashes($cat['name']);
    echo "    {id: {$cat['id']}, name: '[{$cat['id']}] $name'},\n";
}

echo "];\n";
