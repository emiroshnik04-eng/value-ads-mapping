<?php
/**
 * Check exact params and values for Dresses in Serbia
 * To match with screenshots
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
    echo "║         Serbia (11) + Dresses (4287) - Exact Params          ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Get params with translations
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name AS param_english,
            fmc.translation AS param_serbian
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        LEFT JOIN fast_message_country fmc
            ON fmc.message = p.name
            AND fmc.country_id = :country_id
        WHERE ccp.category_id = :category_id
          AND ccp.country_id = :country_id
        ORDER BY ccp.order_id ASC NULLS LAST
    ");
    $stmt->execute([
        ':category_id' => $categoryId,
        ':country_id' => $countryId
    ]);
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($params) . " parameters:\n\n";

    foreach ($params as $param) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Param ID: {$param['id']}\n";
        echo "English Key: {$param['param_english']}\n";
        echo "Serbian Translation: " . ($param['param_serbian'] ?: 'NOT FOUND') . "\n";

        // Get values for this param
        $stmt2 = $pdo->prepare("
            SELECT
                pv.id,
                pv.value AS english_value,
                pv.display_value,
                cpv.alias AS serbian_alias
            FROM param_param_value ppv
            INNER JOIN param_value pv ON pv.id = ppv.param_value_id
            LEFT JOIN country_param_value cpv
                ON cpv.param_value_id = pv.id
                AND cpv.country_id = :country_id
            WHERE ppv.param_id = :param_id
            ORDER BY pv.value
            LIMIT 20
        ");
        $stmt2->execute([
            ':param_id' => $param['id'],
            ':country_id' => $countryId
        ]);
        $values = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (count($values) > 0) {
            echo "\nSample values (" . count($values) . " shown):\n";
            foreach ($values as $value) {
                $display = $value['serbian_alias'] ?: $value['display_value'] ?: $value['english_value'];
                echo "  '{$value['english_value']}' → '{$display}'\n";
            }
        } else {
            echo "\nNo values found\n";
        }

        echo "\n";
    }

    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║         Looking for specific params from screenshot          ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Look for "Brand" related param (screenshot shows "Brend")
    echo "=== Searching for 'Brand' param ===\n\n";

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            fmc.translation,
            ccp.category_id
        FROM param p
        LEFT JOIN fast_message_country fmc ON fmc.message = p.name AND fmc.country_id = 11
        LEFT JOIN country_category_param ccp ON ccp.param_id = p.id AND ccp.country_id = 11 AND ccp.category_id = 4287
        WHERE p.name ILIKE '%brand%'
        OR p.name ILIKE '%brend%'
        ORDER BY p.name
        LIMIT 20
    ");
    $stmt->execute();
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($brands as $brand) {
        $inCategory = $brand['category_id'] ? 'YES' : 'NO';
        echo "'{$brand['name']}' → '{$brand['translation']}' (In Dresses: $inCategory)\n";
    }

    echo "\n=== Searching for Color param with multi-color value ===\n\n";

    // Find color param
    $stmt = $pdo->prepare("
        SELECT p.id
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        WHERE ccp.category_id = 4287
          AND ccp.country_id = 11
          AND p.name ILIKE '%color%'
        LIMIT 1
    ");
    $stmt->execute();
    $colorParamId = $stmt->fetchColumn();

    if ($colorParamId) {
        echo "Found Color param ID: $colorParamId\n\n";

        // Get multicolor or višebojna value
        $stmt2 = $pdo->prepare("
            SELECT
                pv.id,
                pv.value,
                pv.display_value,
                cpv.alias
            FROM param_param_value ppv
            INNER JOIN param_value pv ON pv.id = ppv.param_value_id
            LEFT JOIN country_param_value cpv ON cpv.param_value_id = pv.id AND cpv.country_id = 11
            WHERE ppv.param_id = :param_id
            AND (
                pv.value ILIKE '%multi%'
                OR pv.display_value ILIKE '%multi%'
                OR cpv.alias ILIKE '%višebojna%'
                OR cpv.alias ILIKE '%RS%'
                OR pv.value LIKE 'RS%'
            )
            LIMIT 10
        ");
        $stmt2->execute([':param_id' => $colorParamId]);
        $multiColors = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (count($multiColors) > 0) {
            echo "Multi-color values:\n";
            foreach ($multiColors as $color) {
                echo "  Value: '{$color['value']}' | Display: '{$color['display_value']}' | Alias: '{$color['alias']}'\n";
            }
        } else {
            echo "No multi-color values found\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
