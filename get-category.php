<?php
/**
 * Get category by ID with translations
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

    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    $countryId = isset($_GET['country_id']) ? (int)$_GET['country_id'] : 0;

    if (!$categoryId) {
        http_response_code(400);
        echo json_encode(['error' => 'category_id is required']);
        exit;
    }

    // Get category info and check if it's a leaf category (has ads)
    $sql = "
        SELECT
            c.id,
            c.name,
            COUNT(DISTINCT a.id) as ads_count,
            CASE
                WHEN EXISTS(SELECT 1 FROM category child WHERE child.parent_id = c.id AND child.is_deleted = false)
                THEN false
                ELSE true
            END as is_leaf
        FROM category c
        LEFT JOIN ad a ON a.category_id = c.id AND a.is_deleted = false
        WHERE c.id = :category_id
          AND c.is_deleted = false
        GROUP BY c.id, c.name
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':category_id' => $categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found or deleted']);
        exit;
    }

    // Format response
    $category['id'] = (int)$category['id'];
    $category['ads_count'] = (int)$category['ads_count'];
    $category['is_leaf'] = (bool)$category['is_leaf'];

    // Add translated category name if country_id provided
    if ($countryId) {
        $category['name_translated'] = TranslationHelper::translateCategory($category['name'], $countryId);
    }

    echo json_encode($category, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
