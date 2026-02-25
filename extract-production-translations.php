<?php
/**
 * Extract real translations from production for Serbia
 * Based on screenshots showing mismatches
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

    $countryId = 11; // Serbia
    $categoryId = 4287; // Dresses

    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║         Extracting Real Translations from Database           ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    echo "Country: Serbia (ID: $countryId)\n";
    echo "Category: Dresses (ID: $categoryId)\n\n";

    // Get all params for Serbia + Dresses
    echo "=== Getting parameters for Serbia + Dresses ===\n\n";

    $sql = "
        SELECT
            p.id,
            p.name AS param_name
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        WHERE ccp.category_id = :category_id
          AND ccp.country_id = :country_id
          AND (ccp.status_id IN (1, 2, 3) OR ccp.status_id IS NULL)
        ORDER BY ccp.order_id ASC NULLS LAST
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':category_id' => $categoryId,
        ':country_id' => $countryId
    ]);
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($params) . " parameters:\n\n";

    foreach ($params as $param) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Param ID: {$param['id']}\n";
        echo "Param Name (English): {$param['param_name']}\n";

        // Check if there are translations in fast_message_country
        $stmt2 = $pdo->prepare("
            SELECT translation
            FROM fast_message_country
            WHERE country_id = :country_id
            AND message = :message
            LIMIT 1
        ");
        $stmt2->execute([
            ':country_id' => $countryId,
            ':message' => $param['param_name']
        ]);
        $translation = $stmt2->fetchColumn();

        if ($translation) {
            echo "Translation (from fast_message_country): $translation\n";
        } else {
            echo "Translation: NOT FOUND in fast_message_country\n";
        }

        // Get some values for this param
        $stmt3 = $pdo->prepare("
            SELECT
                pv.id,
                pv.value AS english_value,
                cpv.alias AS country_alias
            FROM param_param_value ppv
            INNER JOIN param_value pv ON pv.id = ppv.param_value_id
            LEFT JOIN country_param_value cpv ON cpv.param_value_id = pv.id AND cpv.country_id = :country_id
            WHERE ppv.param_id = :param_id
            LIMIT 10
        ");
        $stmt3->execute([
            ':param_id' => $param['id'],
            ':country_id' => $countryId
        ]);
        $values = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        if (count($values) > 0) {
            echo "\nValues (sample):\n";
            foreach ($values as $value) {
                $alias = $value['country_alias'] ?: $value['english_value'];
                echo "  - {$value['english_value']} → $alias\n";
            }
        }

        echo "\n";
    }

    // Now let's specifically check the "Clothing Brand" param from screenshot
    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║              Checking Specific Params from Screenshots        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Find param with "Brand" or "Clothing Brand"
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        WHERE ccp.category_id = :category_id
          AND ccp.country_id = :country_id
          AND (p.name ILIKE '%brand%' OR p.name ILIKE '%brend%')
    ");
    $stmt->execute([
        ':category_id' => $categoryId,
        ':country_id' => $countryId
    ]);
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Brand-related params:\n";
    foreach ($brands as $brand) {
        echo "  ID: {$brand['id']} | Name: {$brand['name']}\n";
    }

    // Check color param values with "RS -" prefix
    echo "\n=== Checking Color param values (looking for 'RS -' prefix) ===\n\n";

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        WHERE ccp.category_id = :category_id
          AND ccp.country_id = :country_id
          AND p.name ILIKE '%color%'
    ");
    $stmt->execute([
        ':category_id' => $categoryId,
        ':country_id' => $countryId
    ]);
    $colorParams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($colorParams as $colorParam) {
        echo "Color Param: {$colorParam['name']} (ID: {$colorParam['id']})\n\n";

        // Get values
        $stmt2 = $pdo->prepare("
            SELECT
                pv.id,
                pv.value AS english_value,
                pv.display_value,
                cpv.alias
            FROM param_param_value ppv
            INNER JOIN param_value pv ON pv.id = ppv.param_value_id
            LEFT JOIN country_param_value cpv ON cpv.param_value_id = pv.id AND cpv.country_id = :country_id
            WHERE ppv.param_id = :param_id
            AND (
                pv.value LIKE 'RS -%'
                OR pv.display_value LIKE 'RS -%'
                OR cpv.alias LIKE 'RS -%'
            )
            LIMIT 20
        ");
        $stmt2->execute([
            ':param_id' => $colorParam['id'],
            ':country_id' => $countryId
        ]);
        $rsValues = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (count($rsValues) > 0) {
            echo "Found values with 'RS -' prefix:\n";
            foreach ($rsValues as $val) {
                echo "  Value: {$val['english_value']} | Display: {$val['display_value']} | Alias: {$val['alias']}\n";
            }
        } else {
            echo "No values with 'RS -' prefix found\n";
        }
        echo "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
