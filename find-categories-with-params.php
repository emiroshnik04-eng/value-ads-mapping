<?php
/**
 * Find categories that have parameters
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

    // Find categories with parameters
    $sql = "
        SELECT
            c.id,
            c.name,
            COUNT(cp.param_id) as param_count
        FROM category c
        INNER JOIN category_param cp ON cp.category_id = c.id
        WHERE c.is_deleted = false
        GROUP BY c.id, c.name
        HAVING COUNT(cp.param_id) > 0
        ORDER BY param_count DESC
        LIMIT 20
    ";

    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== TOP 20 Categories with most parameters ===\n\n";

    foreach ($categories as $cat) {
        echo sprintf(
            "ID: %d | Name: %s | Parameters: %d\n",
            $cat['id'],
            $cat['name'],
            $cat['param_count']
        );
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
