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

    echo "=== SEARCHING FOR URL FIELDS IN DATABASE ===\n\n";
    
    // Check all tables with 'url' in column name
    $result = $pdo->query("
        SELECT 
            table_name,
            column_name,
            data_type
        FROM information_schema.columns
        WHERE column_name LIKE '%url%'
          AND table_schema = 'public'
        ORDER BY table_name, column_name
    ");
    
    echo "Tables with 'url' columns:\n";
    foreach ($result as $row) {
        echo "  {$row['table_name']}.{$row['column_name']} ({$row['data_type']})\n";
    }
    
    echo "\n=== CHECKING AD TABLE FOR URL-RELATED FIELDS ===\n\n";
    
    $result = $pdo->query("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'ad'
          AND (column_name LIKE '%url%' OR column_name LIKE '%slug%' OR column_name LIKE '%link%')
        ORDER BY column_name
    ");
    
    echo "URL-related fields in 'ad' table:\n";
    foreach ($result as $row) {
        echo "  {$row['column_name']}: {$row['data_type']}\n";
    }
    
    echo "\n=== SAMPLE DATA FROM AD TABLE ===\n\n";
    
    $result = $pdo->query("
        SELECT 
            id,
            title,
            title_slug,
            status_id
        FROM ad
        WHERE category_id = 7859
          AND country_id = 12
          AND is_deleted = false
          AND status_id IN (1, 2, 3, 5)
        LIMIT 3
    ");
    
    echo "Active ads with title_slug:\n";
    foreach ($result as $row) {
        echo "\nad_id: {$row['id']}\n";
        echo "  title: " . substr($row['title'], 0, 50) . "\n";
        echo "  title_slug: {$row['title_slug']}\n";
        echo "  status: {$row['status_id']}\n";
        echo "  URL: https://lalafo.kg/{$row['title_slug']}-{$row['id']}\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
