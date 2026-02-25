<?php
/**
 * Get leaf categories for a country
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$countryId = (int) ($_GET['country_id'] ?? 0);

if (!$countryId) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required parameter: country_id'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Load database config
$configFile = __DIR__ . '/db-config.php';
if (file_exists($configFile)) {
    $dbConfig = require $configFile;
} else {
    $dbConfig = [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '5432',
        'dbname' => getenv('DB_NAME') ?: 'catalog',
        'user' => getenv('DB_USER') ?: 'postgres',
        'password' => getenv('DB_PASSWORD') ?: '',
    ];
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

    // Query to get all active categories (both parent and leaf categories)
    $sql = "
        SELECT DISTINCT
            c.id,
            c.name
        FROM category c
        WHERE c.is_deleted = false
        ORDER BY c.name ASC
        LIMIT 200
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $categories = $stmt->fetchAll();

    // Load translations
    require_once __DIR__ . '/translation-helper.php';

    // Add translated names
    foreach ($categories as &$category) {
        $category['name_translated'] = TranslationHelper::translateCategory($category['name'], $countryId);
    }

    echo json_encode([
        'success' => true,
        'count' => count($categories),
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'hint' => 'Create db-config.php from db-config.example.php'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
