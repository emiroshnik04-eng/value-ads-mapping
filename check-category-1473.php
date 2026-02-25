<?php
header('Content-Type: text/plain; charset=utf-8');

$countryId = (int) ($_GET['country_id'] ?? 12);

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

    // Check if category 1473 exists
    $sql = "SELECT id, name FROM category WHERE id = 1473";
    $stmt = $pdo->query($sql);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Category 1473:\n";
    print_r($category);
    echo "\n\n";

    // Check if it's linked to country
    $sql = "SELECT * FROM country_category WHERE country_id = $countryId AND category_id = 1473";
    $stmt = $pdo->query($sql);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Country-Category link for country $countryId:\n";
    print_r($link);
    echo "\n\n";

    // Find categories with 'toy' in name
    $sql = "SELECT c.id, c.name
            FROM category c
            INNER JOIN country_category cc ON cc.category_id = c.id
            WHERE cc.country_id = $countryId
            AND LOWER(c.name) LIKE '%toy%'
            LIMIT 10";
    $stmt = $pdo->query($sql);
    $toys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Categories with 'toy' for country $countryId:\n";
    print_r($toys);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
