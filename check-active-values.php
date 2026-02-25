<?php
header('Content-Type: text/plain; charset=utf-8');

$configFile = __DIR__ . '/db-config.php';
$dbConfig = require $configFile;

try {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
        $dbConfig['host'], $dbConfig['port'], $dbConfig['dbname']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $countryId = 12;

    echo "=== Checking country_category_param_value for country 12 (Kyrgyzstan) ===\n\n";

    // Check total rows for this country
    $sql = "SELECT COUNT(*) FROM country_category_param_value WHERE country_id = $countryId";
    $count = $pdo->query($sql)->fetchColumn();
    echo "Total rows for country 12: $count\n\n";

    // Check by status_id
    $sql = "SELECT status_id, COUNT(*) as cnt
            FROM country_category_param_value
            WHERE country_id = $countryId
            GROUP BY status_id
            ORDER BY status_id";
    $statuses = $pdo->query($sql)->fetchAll();
    echo "Breakdown by status_id:\n";
    foreach ($statuses as $row) {
        $label = $row['status_id'] == 1 ? 'ACTIVE' : ($row['status_id'] == 3 ? 'INACTIVE' : 'OTHER');
        echo "  status_id {$row['status_id']} ($label): {$row['cnt']} rows\n";
    }

    // Check for category 1473 specifically
    echo "\n=== Category 1473 (Toys) specifically ===\n";
    $sql = "SELECT status_id, COUNT(*) as cnt
            FROM country_category_param_value
            WHERE country_id = $countryId AND category_id = 1473
            GROUP BY status_id
            ORDER BY status_id";
    $catStatuses = $pdo->query($sql)->fetchAll();
    if (empty($catStatuses)) {
        echo "NO ROWS FOUND for category 1473!\n";
        echo "This means category 1473 is not configured in country_category_param_value.\n";
    } else {
        echo "Found rows:\n";
        foreach ($catStatuses as $row) {
            $label = $row['status_id'] == 1 ? 'ACTIVE' : ($row['status_id'] == 3 ? 'INACTIVE' : 'OTHER');
            echo "  status_id {$row['status_id']} ($label): {$row['cnt']} rows\n";
        }
    }

    // Check if there are ANY categories with active values
    echo "\n=== Categories with active values (status_id = 1) ===\n";
    $sql = "SELECT category_id, COUNT(*) as cnt
            FROM country_category_param_value
            WHERE country_id = $countryId AND status_id = 1
            GROUP BY category_id
            ORDER BY cnt DESC
            LIMIT 10";
    $activeCategories = $pdo->query($sql)->fetchAll();
    if (empty($activeCategories)) {
        echo "NO CATEGORIES with active values!\n";
        echo "This means country 12 has no active param values configured.\n";
    } else {
        echo "Top 10 categories with active values:\n";
        foreach ($activeCategories as $row) {
            echo "  Category {$row['category_id']}: {$row['cnt']} active values\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
