<?php
header('Content-Type: text/plain; charset=utf-8');

$configFile = __DIR__ . '/db-config.php';
$dbConfig = require $configFile;

try {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
        $dbConfig['host'], $dbConfig['port'], $dbConfig['dbname']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $countryId = 12;
    $categoryId = 1473;
    $paramId = 105;

    echo "=== Finding correct logic for category 1473, param 105 ===\n\n";

    // Option 1: Check if there's a table linking category + param + param_value
    echo "OPTION 1: Check tables that might link category-param-value\n";
    $sql = "
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_name LIKE '%category%param%'
        ORDER BY table_name
    ";
    $tables = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n\n";

    // Option 2: Check category_param table
    echo "OPTION 2: Check category_param for category 1473\n";
    $sql = "
        SELECT column_name
        FROM information_schema.columns
        WHERE table_name = 'category_param'
        ORDER BY ordinal_position
    ";
    $columns = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in category_param: " . implode(', ', $columns) . "\n\n";

    $sql = "
        SELECT *
        FROM category_param
        WHERE category_id = ?
          AND param_id = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoryId, $paramId]);
    $catParam = $stmt->fetch();
    if ($catParam) {
        echo "category_param row found:\n";
        print_r($catParam);
    } else {
        echo "No row in category_param for category 1473, param 105\n";
    }

    // Option 3: Maybe the correct logic is to use country_category_param_value but filter by param_id via join
    echo "\n\nOPTION 3: Get values from country_category_param_value with param filtering\n";
    $sql = "
        SELECT
            ccpv.param_value_id,
            pv.value,
            ccpv.status_id
        FROM country_category_param_value ccpv
        INNER JOIN param_param_value ppv ON ppv.param_value_id = ccpv.param_value_id
        INNER JOIN param_value pv ON pv.id = ccpv.param_value_id
        WHERE ccpv.country_id = ?
          AND ccpv.category_id = ?
          AND ppv.param_id = ?
        ORDER BY ccpv.param_value_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$countryId, $categoryId, $paramId]);
    $ccpvValues = $stmt->fetchAll();

    echo "Total values in country_category_param_value for this category+param: " . count($ccpvValues) . "\n";
    if (count($ccpvValues) > 0) {
        echo "Breakdown by status:\n";
        $statusCount = [];
        foreach ($ccpvValues as $row) {
            if (!isset($statusCount[$row['status_id']])) {
                $statusCount[$row['status_id']] = 0;
            }
            $statusCount[$row['status_id']]++;
        }
        foreach ($statusCount as $status => $count) {
            $label = $status == 1 ? 'ACTIVE' : ($status == 3 ? 'INACTIVE' : 'OTHER');
            echo "  status_id $status ($label): $count values\n";
        }

        echo "\nAll values (first 20):\n";
        foreach (array_slice($ccpvValues, 0, 20) as $row) {
            $statusLabel = $row['status_id'] == 1 ? 'ACTIVE' : 'INACTIVE';
            echo "  ID {$row['param_value_id']}: {$row['value']} - status_id: {$row['status_id']} ($statusLabel)\n";
        }
    }

    // Option 4: Check if there's a specific table for category-specific param values
    echo "\n\nOPTION 4: Check for category_param_value table\n";
    $sql = "
        SELECT EXISTS (
            SELECT 1 FROM information_schema.tables
            WHERE table_name = 'category_param_value'
        )
    ";
    $exists = $pdo->query($sql)->fetchColumn();
    if ($exists) {
        echo "Table category_param_value EXISTS\n";

        $sql = "
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'category_param_value'
            ORDER BY ordinal_position
        ";
        $columns = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        echo "Columns: " . implode(', ', $columns) . "\n";

        $sql = "
            SELECT cpv.*, pv.value
            FROM category_param_value cpv
            INNER JOIN param_value pv ON pv.id = cpv.param_value_id
            WHERE cpv.category_id = ?
              AND cpv.param_id = ?
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoryId, $paramId]);
        $cpvValues = $stmt->fetchAll();

        echo "Total rows: " . count($cpvValues) . "\n";
        if (count($cpvValues) > 0) {
            echo "Values:\n";
            foreach ($cpvValues as $row) {
                echo "  ID {$row['param_value_id']}: {$row['value']}\n";
            }
        }
    } else {
        echo "Table category_param_value DOES NOT exist\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
