<?php
header('Content-Type: text/plain; charset=utf-8');
$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== CHECKING TRANSLATIONS FOR CONDITION VALUES ===\n\n";
    
    // Check param_value original values
    echo "Original param_value table:\n";
    $result = $pdo->query("
        SELECT id, value 
        FROM param_value 
        WHERE id IN (2757, 2756)
    ");
    foreach ($result as $row) {
        echo "  id={$row['id']}: {$row['value']}\n";
    }
    
    echo "\nCountry-specific translations (country_param_value):\n";
    $result = $pdo->query("
        SELECT cpv.param_value_id, cpv.country_id, cpv.alias, pv.value as original
        FROM country_param_value cpv
        INNER JOIN param_value pv ON pv.id = cpv.param_value_id
        WHERE cpv.param_value_id IN (2757, 2756)
          AND cpv.country_id = 12
    ");
    
    $hasTranslations = false;
    foreach ($result as $row) {
        $hasTranslations = true;
        echo "  id={$row['param_value_id']} (country 12): '{$row['alias']}' (original: '{$row['original']}')\n";
    }
    
    if (!$hasTranslations) {
        echo "  ❌ NO translations found for country 12\n";
        echo "  This means search will only work with English values\n";
    }
    
    echo "\nTesting what values exist in ads:\n";
    $result = $pdo->query("
        SELECT DISTINCT 
            CASE 
                WHEN LOWER(title) LIKE '%новый%' OR LOWER(description) LIKE '%новый%' THEN 'Contains: новый'
                WHEN LOWER(title) LIKE '%new%' OR LOWER(description) LIKE '%new%' THEN 'Contains: new'
                WHEN LOWER(title) LIKE '%б/у%' OR LOWER(description) LIKE '%б/у%' THEN 'Contains: б/у'
                WHEN LOWER(title) LIKE '%used%' OR LOWER(description) LIKE '%used%' THEN 'Contains: used'
                ELSE 'No condition mentioned'
            END as found_text
        FROM ad
        WHERE category_id = 7859
          AND country_id = 12
          AND is_deleted = false
          AND id IN (
              SELECT a.id FROM ad a
              LEFT JOIN ad_param ap ON ap.ad_id = a.id AND ap.param_id = 29
              WHERE a.category_id = 7859
                AND a.country_id = 12
                AND a.is_deleted = false
                AND ap.ad_id IS NULL
              LIMIT 10
          )
    ");
    foreach ($result as $row) {
        echo "  {$row['found_text']}\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
