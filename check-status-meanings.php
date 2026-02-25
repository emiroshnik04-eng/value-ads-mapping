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

    echo "=== AD STATUS MEANINGS ===\n\n";
    
    // Check if there's a status table
    $tables = $pdo->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_name LIKE '%status%'
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables with 'status' in name:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    // Try to find ad_status or status table
    $statusTable = null;
    foreach (['ad_status', 'status'] as $name) {
        if (in_array($name, $tables)) {
            $statusTable = $name;
            break;
        }
    }
    
    if ($statusTable) {
        echo "\n=== STATUS VALUES FROM '$statusTable' TABLE ===\n\n";
        $result = $pdo->query("SELECT * FROM $statusTable ORDER BY id");
        foreach ($result as $row) {
            echo "status_id {$row['id']}: {$row['name']}\n";
        }
    } else {
        echo "\n=== STATUS DISTRIBUTION IN ADS ===\n\n";
        $result = $pdo->query("
            SELECT 
                status_id,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
            FROM ad
            WHERE is_deleted = false
            GROUP BY status_id
            ORDER BY count DESC
        ");
        
        foreach ($result as $row) {
            echo "status_id {$row['status_id']}: {$row['count']} ads ({$row['percentage']}%)\n";
        }
        
        echo "\nGuessing based on common patterns:\n";
        echo "  1 = Active/Published\n";
        echo "  2 = Active (maybe Premium/VIP)\n";
        echo "  3 = Active (maybe Moderated)\n";
        echo "  5 = Active (maybe Pending)\n";
        echo "  8 = Deactivated/Archived\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
