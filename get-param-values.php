<?php
/**
 * Get parameter values for country
 * Returns values with country-specific translations
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
    $paramId = isset($_GET['param_id']) ? (int)$_GET['param_id'] : 0;

    if (!$countryId || !$categoryId || !$paramId) {
        http_response_code(400);
        echo json_encode(['error' => 'country_id, category_id and param_id are required']);
        exit;
    }

    // STEP 1: Get param values configured for this country+category+param combination
    // Using country_category_param_value to get category-specific values for the country
    // NOTE: We ignore status_id because reporting DB is not synchronized with production
    // (all values show status_id=3 but are actually active in production)
    $sqlParamValues = "
        SELECT DISTINCT pv.id, pv.value
        FROM country_category_param_value ccpv
        INNER JOIN param_param_value ppv ON ppv.param_value_id = ccpv.param_value_id
        INNER JOIN param_value pv ON pv.id = ccpv.param_value_id
        WHERE ccpv.country_id = ?
          AND ccpv.category_id = ?
          AND ppv.param_id = ?
        ORDER BY pv.id
        LIMIT 500
    ";

    $stmt = $pdo->prepare($sqlParamValues);
    $stmt->execute([$countryId, $categoryId, $paramId]);
    $validValues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($validValues)) {
        echo json_encode([]);
        exit;
    }

    // Get IDs for IN clause
    $validValueIds = array_column($validValues, 'id');
    $placeholders = implode(',', array_fill(0, count($validValueIds), '?'));

    // STEP 2: Count usage for these valid values in the specific category/country
    // Get country-specific alias from country_param_value table
    $sqlUsage = "
        SELECT
            pv.id,
            pv.value,
            COALESCE(pv.display_value, pv.value) AS display_value,
            cpv.alias AS country_alias,
            COUNT(DISTINCT ap.ad_id) as usage_count
        FROM param_value pv
        LEFT JOIN country_param_value cpv ON cpv.param_value_id = pv.id AND cpv.country_id = ?
        LEFT JOIN ad_param ap ON ap.param_value_id = pv.id AND ap.param_id = ?
        LEFT JOIN ad a ON a.id = ap.ad_id
                      AND a.category_id = ?
                      AND a.country_id = ?
                      AND a.is_deleted = false
        WHERE pv.id IN ($placeholders)
        GROUP BY pv.id, pv.value, pv.display_value, cpv.alias
        HAVING COUNT(DISTINCT ap.ad_id) > 0
        ORDER BY usage_count DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sqlUsage);
    // All positional parameters: country_id (for cpv), param_id, category_id, country_id (for ad), then value IDs
    $params = array_merge(
        [$countryId, $paramId, $categoryId, $countryId],
        $validValueIds
    );
    $stmt->execute($params);
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert to integers and add translations
    foreach ($values as &$value) {
        $value['id'] = (int)$value['id'];
        $value['usage_count'] = (int)$value['usage_count'];

        // Use country_alias if available, otherwise try to translate
        if (!empty($value['country_alias'])) {
            // Country-specific alias takes priority (e.g., "RS - Višebojna")
            $value['display_value_translated'] = $value['country_alias'];
        } else {
            // Fallback to translation helper (checks database then translations.php)
            $value['display_value_translated'] = TranslationHelper::translateValue(
                $value['display_value'],
                $countryId
            );
        }

        // Remove country_alias from response to keep it clean
        unset($value['country_alias']);
    }

    echo json_encode($values, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
