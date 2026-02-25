<?php
/**
 * Get popular leaf categories from different sections
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

// Group categories by parent sections
$bySection = [
    'phones' => [],
    'computers' => [],
    'autos' => [],
    'real_estate' => [],
    'other' => []
];

foreach ($leafCategories as $cat) {
    $id = $cat['id'];
    $url = $cat['url'] ?? '';

    // Classify by URL
    if (str_contains($url, 'mobilnye-telefony') || str_contains($url, 'telefon')) {
        $bySection['phones'][] = $cat;
    } elseif (str_contains($url, 'kompyutery') || str_contains($url, 'noutbuk') || str_contains($url, 'computer')) {
        $bySection['computers'][] = $cat;
    } elseif (str_contains($url, 'avtomobil') || str_contains($url, 'cars')) {
        $bySection['autos'][] = $cat;
    } elseif (str_contains($url, 'kvartir') || str_contains($url, 'doma') || str_contains($url, 'apartment')) {
        $bySection['real_estate'][] = $cat;
    } else {
        $bySection['other'][] = $cat;
    }
}

echo "=== Top 30 popular leaf categories for demo ===\n\n";
echo "'1': [ // Kyrgyzstan - Real leaf categories\n";

// Phones (5)
foreach (array_slice($bySection['phones'], 0, 5) as $cat) {
    $name = addslashes($cat['name']);
    echo "    {id: {$cat['id']}, name: '[{$cat['id']}] $name'},\n";
}

// Computers (5)
foreach (array_slice($bySection['computers'], 0, 5) as $cat) {
    $name = addslashes($cat['name']);
    echo "    {id: {$cat['id']}, name: '[{$cat['id']}] $name'},\n";
}

// Autos (10)
foreach (array_slice($bySection['autos'], 0, 10) as $cat) {
    $name = addslashes($cat['name']);
    echo "    {id: {$cat['id']}, name: '[{$cat['id']}] $name'},\n";
}

// Real estate (5)
foreach (array_slice($bySection['real_estate'], 0, 5) as $cat) {
    $name = addslashes($cat['name']);
    echo "    {id: {$cat['id']}, name: '[{$cat['id']}] $name'},\n";
}

// Other (5)
foreach (array_slice($bySection['other'], 0, 5) as $cat) {
    $name = addslashes($cat['name']);
    echo "    {id: {$cat['id']}, name: '[{$cat['id']}] $name'},\n";
}

echo "],\n";
