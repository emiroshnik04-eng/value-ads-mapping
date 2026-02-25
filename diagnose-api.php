<?php
/**
 * Diagnose what's broken with the API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                   API Diagnostics                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Check translations.php can be loaded
echo "1. Testing translations.php file...\n";
try {
    $translations = require __DIR__ . '/translations.php';
    echo "   ✅ translations.php loaded successfully\n";
    echo "   Languages: " . implode(', ', array_keys($translations)) . "\n";
    if (isset($translations['sr'])) {
        echo "   Serbian categories: " . count($translations['sr']['categories']) . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Failed to load translations.php: " . $e->getMessage() . "\n";
}

echo "\n2. Testing TranslationHelper class...\n";
try {
    require_once __DIR__ . '/translation-helper.php';
    echo "   ✅ TranslationHelper loaded\n";

    $testTranslation = TranslationHelper::translateCategory('Dresses', 11);
    echo "   Test translation: 'Dresses' → '$testTranslation'\n";

} catch (Exception $e) {
    echo "   ❌ Failed to load TranslationHelper: " . $e->getMessage() . "\n";
}

echo "\n3. Testing database connection...\n";
try {
    $config = require __DIR__ . '/db-config.php';
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "   ✅ Database connection successful\n";

    // Try a simple query
    $stmt = $pdo->query("SELECT COUNT(*) FROM category LIMIT 1");
    $count = $stmt->fetchColumn();
    echo "   Categories in DB: $count\n";

} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n4. Simulating get-categories.php...\n";
try {
    $countryId = 11; // Serbia

    // Reload DB connection
    $config = require __DIR__ . '/db-config.php';
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = "
        SELECT DISTINCT
            c.id,
            c.name
        FROM category c
        WHERE c.is_deleted = false
        ORDER BY c.name ASC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "   ✅ Query executed successfully\n";
    echo "   Found " . count($categories) . " categories\n";

    // Add translations
    foreach ($categories as &$category) {
        $category['name_translated'] = TranslationHelper::translateCategory($category['name'], $countryId);
    }

    echo "   ✅ Translations applied\n";
    echo "\n   Sample categories:\n";
    foreach (array_slice($categories, 0, 5) as $cat) {
        echo "     - {$cat['id']}: {$cat['name']} → {$cat['name_translated']}\n";
    }

} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n5. Testing actual API endpoint...\n";

$url = 'http://localhost:8888/get-categories.php?country_id=11';
$context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5]]);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "   ❌ Failed to fetch API\n";
} else {
    echo "   ✅ API responded\n";
    $json = json_decode($response, true);
    if ($json === null) {
        echo "   ❌ Invalid JSON response\n";
        echo "   Response: " . substr($response, 0, 200) . "\n";
    } else {
        if (isset($json['error'])) {
            echo "   ❌ API Error: {$json['error']}\n";
            if (isset($json['message'])) {
                echo "   Message: {$json['message']}\n";
            }
        } else if (isset($json['data'])) {
            echo "   ✅ Success! Got " . count($json['data']) . " categories\n";
        } else {
            echo "   ⚠️  Unexpected response structure\n";
            print_r($json);
        }
    }
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                   Diagnosis Complete                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
