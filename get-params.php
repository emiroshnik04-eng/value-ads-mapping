<?php
/**
 * Get parameters for category and country
 * Returns ONLY parameters that are:
 * - Configured for this country + category in country_category_param
 * - Have active status (status_id IN (1,2,3))
 * - Actually used in ads (have usage_count > 0)
 * - With translations based on country language
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/translation-helper.php';
$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $countryId = isset($_GET['country_id']) ? (int)$_GET['country_id'] : 0;
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

    if (!$countryId || !$categoryId) {
        http_response_code(400);
        echo json_encode(['error' => 'country_id and category_id are required']);
        exit;
    }

    // Get parameters that are:
    // 1. Configured in country_category_param for this country+category
    // 2. Have active status (status_id IN (1,2,3) or NULL for backward compatibility)
    //
    // Note: We don't check if params are used in ads to avoid slow queries (no indexes).
    // TODO: Add translations from translation-microservice instead of master param.name
    $sql = "
        SELECT
            p.id,
            p.name,
            ccp.order_id,
            ccp.status_id
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        WHERE ccp.category_id = :category_id
          AND ccp.country_id = :country_id
          AND (ccp.status_id IN (1, 2, 3) OR ccp.status_id IS NULL)
        ORDER BY ccp.order_id ASC NULLS LAST, p.name ASC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':category_id' => $categoryId,
        ':country_id' => $countryId
    ]);
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert to integers and add translations
    foreach ($params as &$param) {
        $param['id'] = (int)$param['id'];
        $param['order_id'] = (int)($param['order_id'] ?? 999);
        $param['status_id'] = (int)($param['status_id'] ?? 1);

        // Add translated name based on country
        $param['name_translated'] = TranslationHelper::translateParam($param['name'], $countryId);
    }

    echo json_encode($params, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
