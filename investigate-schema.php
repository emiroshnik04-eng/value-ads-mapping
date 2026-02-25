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

    echo "=== INVESTIGATING DATABASE SCHEMA ===\n\n";

    // 1. Check ad_param table structure
    echo "1. AD_PARAM TABLE STRUCTURE:\n";
    $result = $pdo->query("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'ad_param' 
        ORDER BY ordinal_position
    ");
    foreach ($result as $row) {
        echo "  - {$row['column_name']}: {$row['data_type']}\n";
    }

    // 2. Sample data from ad_param
    echo "\n2. SAMPLE AD_PARAM DATA (country 12, category 1343):\n";
    $result = $pdo->query("
        SELECT ap.*, a.category_id, a.country_id
        FROM ad_param ap
        INNER JOIN ad a ON a.id = ap.ad_id
        WHERE a.country_id = 12 
          AND a.category_id = 1343
          AND a.is_deleted = false
        LIMIT 5
    ");
    foreach ($result as $row) {
        echo "  ad_id={$row['ad_id']}, param_id={$row['param_id']}, param_value_id={$row['param_value_id']}\n";
    }

    // 3. Check param_value table structure
    echo "\n3. PARAM_VALUE TABLE STRUCTURE:\n";
    $result = $pdo->query("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'param_value' 
        ORDER BY ordinal_position
    ");
    foreach ($result as $row) {
        echo "  - {$row['column_name']}: {$row['data_type']}\n";
    }

    // 4. Sample param_value data
    echo "\n4. SAMPLE PARAM_VALUE DATA (for param 1071 - Brand):\n";
    $result = $pdo->query("
        SELECT * FROM param_value WHERE id IN (21616, 21618, 21619) LIMIT 3
    ");
    foreach ($result as $row) {
        echo "  id={$row['id']}, value={$row['value']}\n";
    }

    // 5. Check if there's a relationship table between param and param_value
    echo "\n5. CHECKING FOR PARAM-PARAMVALUE RELATIONSHIP:\n";
    $tables = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
          AND table_name LIKE '%param%'
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    echo "  Tables with 'param': " . implode(', ', $tables) . "\n";

    // 6. Count param values used in ads for param 1071
    echo "\n6. COUNT PARAM VALUES USED IN ADS (param 1071, category 1343):\n";
    $result = $pdo->query("
        SELECT 
            COUNT(DISTINCT ap.param_value_id) as unique_values,
            COUNT(*) as total_usage
        FROM ad_param ap
        INNER JOIN ad a ON a.id = ap.ad_id
        WHERE ap.param_id = 1071
          AND a.category_id = 1343
          AND a.country_id = 12
          AND a.is_deleted = false
    ")->fetch(PDO::FETCH_ASSOC);
    echo "  Unique values: {$result['unique_values']}, Total usage: {$result['total_usage']}\n";

    // 7. Get top 10 most used param values
    echo "\n7. TOP 10 MOST USED PARAM VALUES (param 1071, category 1343):\n";
    $result = $pdo->query("
        SELECT 
            pv.id,
            pv.value,
            COUNT(DISTINCT ap.ad_id) as usage_count
        FROM ad_param ap
        INNER JOIN ad a ON a.id = ap.ad_id
        INNER JOIN param_value pv ON pv.id = ap.param_value_id
        WHERE ap.param_id = 1071
          AND a.category_id = 1343
          AND a.country_id = 12
          AND a.is_deleted = false
        GROUP BY pv.id, pv.value
        ORDER BY usage_count DESC
        LIMIT 10
    ");
    foreach ($result as $row) {
        echo "  {$row['value']}: {$row['usage_count']} ads\n";
    }

    echo "\n=== INVESTIGATION COMPLETE ===\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
