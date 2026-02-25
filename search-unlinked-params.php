<?php
/**
 * Local admin tool: Search for ads with unlinked parameters
 * Uses real-time data from catalog-microservice database
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request parameters
$countryId = (int) ($_GET['country_id'] ?? 0);
$categoryId = (int) ($_GET['category_id'] ?? 0);
$paramId = (int) ($_GET['param_id'] ?? 0);
$paramValueIds = array_filter(array_map('intval', explode(',', $_GET['param_value_ids'] ?? '')));

// Validate required parameters
if (!$countryId || !$categoryId || !$paramId || empty($paramValueIds)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required parameters',
        'required' => ['country_id', 'category_id', 'param_id', 'param_value_ids'],
        'received' => [
            'country_id' => $countryId,
            'category_id' => $categoryId,
            'param_id' => $paramId,
            'param_value_ids' => $paramValueIds
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Database configuration
// Priority: db-config.php > environment variables > defaults
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
    // Connect to database
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

    // Build SQL query to find ads with unlinked parameters
    $sql = "
        SELECT
            a.id AS ad_id,
            a.title,
            a.description,
            a.created_time,
            STRING_AGG(DISTINCT cpv.alias, ', ') AS matched_values,
            COUNT(DISTINCT cpv.param_value_id) AS match_count,
            ARRAY_AGG(DISTINCT cpv.param_value_id) AS matched_param_value_ids
        FROM ad a
        CROSS JOIN country_param_value cpv
        LEFT JOIN ad_param ap
            ON ap.ad_id = a.id AND ap.param_id = :param_id
        WHERE
            a.country_id = :country_id
            AND a.category_id = :category_id
            AND a.status_id IN (1, 2, 3, 5)
            AND a.is_deleted = false
            AND ap.ad_id IS NULL
            AND cpv.country_id = :country_id
            AND cpv.param_value_id = ANY(:param_value_ids)
            AND (
                LOWER(a.title) LIKE LOWER('%' || cpv.alias || '%')
                OR LOWER(a.description) LIKE LOWER('%' || cpv.alias || '%')
            )
        GROUP BY a.id, a.title, a.description, a.created_time
        ORDER BY match_count DESC, a.created_time DESC
        LIMIT 1000
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':country_id' => $countryId,
        ':category_id' => $categoryId,
        ':param_id' => $paramId,
        ':param_value_ids' => '{' . implode(',', $paramValueIds) . '}', // PostgreSQL array format
    ]);

    $results = $stmt->fetchAll();

    // Format matched_param_value_ids from PostgreSQL array to PHP array
    foreach ($results as &$row) {
        if (isset($row['matched_param_value_ids'])) {
            // Convert PostgreSQL array format {1,2,3} to PHP array
            $row['matched_param_value_ids'] = json_decode(
                '[' . trim($row['matched_param_value_ids'], '{}') . ']'
            );
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($results),
        'data' => $results,
        'filters' => [
            'country_id' => $countryId,
            'category_id' => $categoryId,
            'param_id' => $paramId,
            'param_value_ids' => $paramValueIds,
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'hint' => 'Create db-config.php from db-config.example.php, or set environment variables: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
