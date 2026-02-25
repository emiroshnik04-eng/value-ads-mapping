<?php
/**
 * Find real translation tables in database
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║         Finding Translation Tables                            ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // 1. Check fast_message table
    echo "1. Checking fast_message table...\n";
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'fast_message'
        )
    ");
    
    if ($stmt->fetchColumn()) {
        echo "   ✅ fast_message table exists\n\n";
        
        // Get structure
        $stmt = $pdo->query("
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = 'fast_message'
            ORDER BY ordinal_position
        ");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Columns:\n";
        foreach ($columns as $col) {
            echo "     - {$col['column_name']}: {$col['data_type']}\n";
        }
        
        echo "\n   Sample data:\n";
        $stmt = $pdo->query("SELECT * FROM fast_message LIMIT 5");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($samples as $i => $row) {
            echo "\n   Row " . ($i + 1) . ":\n";
            foreach ($row as $key => $value) {
                if (is_string($value) && strlen($value) > 100) {
                    $value = substr($value, 0, 100) . '...';
                }
                echo "     $key: $value\n";
            }
        }
        
        // Count
        $stmt = $pdo->query("SELECT COUNT(*) FROM fast_message");
        $total = $stmt->fetchColumn();
        echo "\n   Total rows: $total\n";
        
    } else {
        echo "   ❌ fast_message table does NOT exist\n";
    }

    echo "\n\n2. Looking for JOIN between fast_message and fast_message_country...\n";
    
    if ($stmt->fetchColumn()) {
        echo "   Attempting to get translations for Serbia:\n\n";
        
        $stmt = $pdo->query("
            SELECT 
                fm.*,
                fmc.country_id
            FROM fast_message fm
            JOIN fast_message_country fmc ON fm.id = fmc.fast_message_id
            WHERE fmc.country_id = 11
            LIMIT 10
        ");
        $joined = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($joined) > 0) {
            echo "   Found " . count($joined) . " translations:\n\n";
            foreach ($joined as $i => $row) {
                echo "   Translation " . ($i + 1) . ":\n";
                foreach ($row as $key => $value) {
                    if (is_string($value) && strlen($value) > 100) {
                        $value = substr($value, 0, 100) . '...';
                    }
                    echo "     $key: $value\n";
                }
                echo "\n";
            }
        }
    }

    echo "\n\n3. Checking country_param_value table (for param value aliases)...\n";
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'country_param_value'
        )
    ");
    
    if ($stmt->fetchColumn()) {
        echo "   ✅ country_param_value table exists\n\n";
        
        echo "   Sample aliases for Serbia:\n";
        $stmt = $pdo->query("
            SELECT 
                cpv.param_value_id,
                pv.value,
                pv.display_value,
                cpv.alias,
                cpv.country_id
            FROM country_param_value cpv
            JOIN param_value pv ON pv.id = cpv.param_value_id
            WHERE cpv.country_id = 11
            AND cpv.alias IS NOT NULL
            LIMIT 10
        ");
        $aliases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($aliases as $alias) {
            echo "   '{$alias['value']}' → '{$alias['alias']}'\n";
        }
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM country_param_value WHERE country_id = 11");
        $total = $stmt->fetchColumn();
        echo "\n   Total aliases for Serbia: $total\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
