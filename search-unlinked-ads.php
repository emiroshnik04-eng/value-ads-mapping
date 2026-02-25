<?php
/**
 * Search for ads that mention parameter values in title/description
 * but don't have those parameters linked in ad_param table
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
    $paramValueIds = isset($_GET['param_value_ids']) ? $_GET['param_value_ids'] : '';

    if (!$countryId || !$categoryId || !$paramId || empty($paramValueIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'country_id, category_id, param_id and param_value_ids are required']);
        exit;
    }

    // Parse param value IDs
    $paramValueIdsArray = array_filter(array_map('intval', explode(',', $paramValueIds)));

    if (empty($paramValueIdsArray)) {
        http_response_code(400);
        echo json_encode(['error' => 'param_value_ids must contain at least one valid ID']);
        exit;
    }

    // Fetch param values and build search terms with translations
    $placeholders = implode(',', array_fill(0, count($paramValueIdsArray), '?'));

    $stmt = $pdo->prepare("SELECT id, value FROM param_value WHERE id IN ($placeholders)");
    $stmt->execute($paramValueIdsArray);
    $paramValues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build LIKE conditions for each value (English + Translation)
    $searchConditions = [];
    $searchParams = [];

    foreach ($paramValues as $pv) {
        $englishValue = $pv['value'];
        $translatedValue = TranslationHelper::translateValue($englishValue, $countryId);

        // Add English term
        $searchConditions[] = "LOWER(a.title) LIKE ? OR LOWER(a.description) LIKE ?";
        $searchParams[] = '%' . mb_strtolower($englishValue) . '%';
        $searchParams[] = '%' . mb_strtolower($englishValue) . '%';

        // Add translated term if different from English
        if ($translatedValue !== $englishValue) {
            $searchConditions[] = "LOWER(a.title) LIKE ? OR LOWER(a.description) LIKE ?";
            $searchParams[] = '%' . mb_strtolower($translatedValue) . '%';
            $searchParams[] = '%' . mb_strtolower($translatedValue) . '%';
        }
    }

    $searchSql = !empty($searchConditions) ? '(' . implode(') OR (', $searchConditions) . ')' : '1=0';

    // Search for ads that:
    // 1. Are in the specified category and country
    // 2. Don't have this param_id linked in ad_param
    // 3. Mention any of the param values in title or description (English or translated)
    $sql = "
        SELECT
            a.id AS ad_id,
            a.title,
            a.title_slug,
            a.description,
            a.created_time
        FROM ad a
        LEFT JOIN ad_param ap ON ap.ad_id = a.id AND ap.param_id = ?
        WHERE a.country_id = ?
          AND a.category_id = ?
          AND a.is_deleted = false
          AND a.status_id = 2
          AND ap.ad_id IS NULL
          AND ($searchSql)
        ORDER BY a.created_time DESC
        LIMIT 1000
    ";

    $stmt = $pdo->prepare($sql);

    // Bind parameters: param_id, country_id, category_id, then search terms
    $bindParams = array_merge(
        [$paramId, $countryId, $categoryId],
        $searchParams
    );

    $stmt->execute($bindParams);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format results and detect which values matched
    foreach ($results as &$result) {
        $result['ad_id'] = (int)$result['ad_id'];
        $result['created_time'] = (int)$result['created_time'];

        $titleLower = mb_strtolower($result['title']);
        $descLower = mb_strtolower($result['description']);

        // Determine which param values matched
        $matchedValues = [];
        $matchedParamValueIds = [];

        foreach ($paramValues as $pv) {
            $englishValue = $pv['value'];
            $translatedValue = TranslationHelper::translateValue($englishValue, $countryId);

            $englishLower = mb_strtolower($englishValue);
            $translatedLower = mb_strtolower($translatedValue);

            if (str_contains($titleLower, $englishLower) || str_contains($descLower, $englishLower) ||
                str_contains($titleLower, $translatedLower) || str_contains($descLower, $translatedLower)) {
                $matchedValues[] = $translatedValue;
                $matchedParamValueIds[] = (int)$pv['id'];
            }
        }

        $result['matched_values'] = implode(', ', array_unique($matchedValues));
        $result['matched_param_value_ids'] = array_values(array_unique($matchedParamValueIds));
        $result['match_count'] = count($matchedParamValueIds);

        // Generate ad URL (assuming Kyrgyzstan = .kg, others can be added)
        $domain = 'lalafo.kg'; // TODO: map country_id to domain
        $result['url'] = "https://{$domain}/bishkek/ad/{$result['title_slug']}-{$result['ad_id']}";
    }

    // Response with metadata
    $response = [
        'results' => $results,
        'total' => count($results),
        'search_params' => [
            'country_id' => $countryId,
            'category_id' => $categoryId,
            'param_id' => $paramId,
            'param_value_ids' => $paramValueIdsArray,
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
