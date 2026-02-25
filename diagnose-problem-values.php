<?php
header('Content-Type: text/plain; charset=utf-8');

$countryId = 12;
$categoryId = 1473;

// Load database config
$configFile = __DIR__ . '/db-config.php';
if (file_exists($configFile)) {
    $dbConfig = require $configFile;
} else {
    die("db-config.php not found");
}

try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['dbname']
    );

    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $problemValues = [
        23108 => 'Silver Iphone',
        27221 => 'Blue-1',
        44999 => 'RS - Multicolored',
        39849 => 'Navy blue',
        30483 => 'Multicolored color'
    ];

    echo "=== DIAGNOSIS: Problem Values for Category 1473 (Toys) ===\n\n";
    echo "Country: $countryId (Kyrgyzstan)\n";
    echo "Category: $categoryId (Toys)\n\n";

    foreach ($problemValues as $valueId => $valueName) {
        echo "----------------------------------------\n";
        echo "Value ID $valueId: $valueName\n";
        echo "----------------------------------------\n";

        // Check country_category_param_value
        $sql = "SELECT status_id
                FROM country_category_param_value
                WHERE country_id = $countryId
                  AND category_id = $categoryId
                  AND param_value_id = $valueId";
        $stmt = $pdo->query($sql);
        $ccpv = $stmt->fetch();

        if ($ccpv) {
            echo "✓ EXISTS in country_category_param_value:\n";
            echo "  status_id: {$ccpv['status_id']} " . ($ccpv['status_id'] == 1 ? '(ACTIVE - will be shown)' : '(INACTIVE - will be hidden)') . "\n";
        } else {
            echo "✗ NOT FOUND in country_category_param_value\n";
            echo "  (will be excluded from results)\n";
        }

        // Check usage
        $sql = "SELECT COUNT(DISTINCT ad_id) as count
                FROM ad_param
                WHERE param_value_id = $valueId
                  AND param_id = 105";
        $stmt = $pdo->query($sql);
        $usage = $stmt->fetch();
        echo "\n  Used in " . $usage['count'] . " ads total\n";

        $sql = "SELECT COUNT(DISTINCT ap.ad_id) as count
                FROM ad_param ap
                INNER JOIN ad a ON a.id = ap.ad_id
                WHERE ap.param_value_id = $valueId
                  AND ap.param_id = 105
                  AND a.category_id = $categoryId
                  AND a.country_id = $countryId";
        $stmt = $pdo->query($sql);
        $categoryUsage = $stmt->fetch();
        echo "  Used in " . $categoryUsage['count'] . " ads in Toys category (country $countryId)\n";

        echo "\n";
    }

    echo "\n=== CONCLUSION ===\n\n";
    echo "Values with status_id = 1 will be SHOWN\n";
    echo "Values with status_id != 1 will be HIDDEN\n";
    echo "Values not in country_category_param_value will be HIDDEN\n\n";

    echo "If problem values are showing, it means they have status_id = 1 in the database.\n";
    echo "This is a DATA issue - the reporting database has different data than production.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
