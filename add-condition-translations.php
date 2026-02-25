<?php
/**
 * Add Russian translations for Condition parameter values
 * This will make search work for Russian-language ads
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

    echo "=== ADDING TRANSLATIONS FOR CONDITION VALUES ===\n\n";
    
    // Update translations
    $updates = [
        ['param_value_id' => 2757, 'country_id' => 12, 'alias' => 'Новый'],  // New
        ['param_value_id' => 2756, 'country_id' => 12, 'alias' => 'Б/у'],    // Used
    ];
    
    foreach ($updates as $update) {
        $sql = "
            UPDATE country_param_value 
            SET alias = :alias 
            WHERE param_value_id = :param_value_id 
              AND country_id = :country_id
        ";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':alias' => $update['alias'],
            ':param_value_id' => $update['param_value_id'],
            ':country_id' => $update['country_id']
        ]);
        
        $rowCount = $stmt->rowCount();
        
        if ($rowCount > 0) {
            echo "✅ Updated param_value {$update['param_value_id']} to '{$update['alias']}' (country {$update['country_id']})\n";
        } else {
            echo "⚠️ No rows updated for param_value {$update['param_value_id']} - record might not exist\n";
        }
    }
    
    echo "\n=== TRANSLATIONS UPDATED ===\n";
    echo "\nNow search should work with Russian terms like 'Новый' and 'Б/у'\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'read only') !== false || strpos($e->getMessage(), 'read-only') !== false) {
        echo "\n❌ Database is READ-ONLY. Cannot update translations.\n";
        echo "This needs to be done by DBA team.\n";
    }
}
