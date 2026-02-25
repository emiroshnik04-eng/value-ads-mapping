<?php
$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $adIds = [64662665, 70034541, 72137139, 74891870, 75443513, 79928502, 90302295, 92125934, 95071458, 107584091, 108055873, 109617453, 109902561];
    
    echo "=== РАССЛЕДОВАНИЕ ИЗМЕНЕНИЯ СТАТУСОВ ===\n\n";
    
    $placeholders = implode(',', $adIds);
    
    // Текущее состояние
    $result = $pdo->query("
        SELECT 
            id,
            status_id,
            category_id,
            is_ppv,
            ppv_price,
            updated_time,
            TO_TIMESTAMP(updated_time) as updated_date
        FROM ad
        WHERE id IN ($placeholders)
        ORDER BY status_id, id
    ");
    
    $byStatus = [];
    foreach ($result as $row) {
        $status = $row['status_id'];
        if (!isset($byStatus[$status])) {
            $byStatus[$status] = [];
        }
        $byStatus[$status][] = $row;
    }
    
    foreach ($byStatus as $status => $ads) {
        echo "Status $status: " . count($ads) . " ads\n";
        foreach ($ads as $ad) {
            echo "  {$ad['id']}: cat={$ad['category_id']}, ppv=" . ($ad['is_ppv'] ? 'Y' : 'N') . ", price={$ad['ppv_price']}, updated={$ad['updated_date']}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
