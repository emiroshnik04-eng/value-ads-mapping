<?php
/**
 * Diagnose translation issue from screenshots
 * User sees "Brend" in production but we show "Brend odeće"
 * User sees "RS - Višebojna" but we might not show it correctly
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
    echo "║               Diagnosing Translation Mismatches               ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $countryId = 11; // Serbia
    $categoryId = 4287; // Dresses

    echo "ISSUE 1: Production shows 'Brend' but we show 'Brend odeće'\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Find all brand-related params
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            fmc.translation,
            ccp.category_id
        FROM param p
        LEFT JOIN fast_message_country fmc ON fmc.message = p.name AND fmc.country_id = :country_id
        LEFT JOIN country_category_param ccp ON ccp.param_id = p.id
            AND ccp.country_id = :country_id
            AND ccp.category_id = :category_id
        WHERE (p.name ILIKE '%brand%' OR p.name ILIKE '%brend%')
        ORDER BY p.name
    ");
    $stmt->execute([
        ':country_id' => $countryId,
        ':category_id' => $categoryId
    ]);
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($brands) . " brand-related params:\n\n";
    foreach ($brands as $brand) {
        $inCategory = $brand['category_id'] ? 'YES' : 'NO';
        $translation = $brand['translation'] ?: 'NO TRANSLATION IN DB';
        echo "Key: '{$brand['name']}'\n";
        echo "  Translation in DB: $translation\n";
        echo "  In Dresses category: $inCategory\n\n";
    }

    echo "\nCONCLUSION:\n";
    echo "If 'Clothing Brand' shows translation = 'Brend odeće' in DB, that's WRONG.\n";
    echo "It should be 'Brend' according to production screenshot.\n";
    echo "We need to check what production lalafo.rs actually returns.\n\n";

    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "ISSUE 2: Value with 'RS -' prefix\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Find color param
    $stmt = $pdo->prepare("
        SELECT p.id, p.name
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        WHERE ccp.category_id = :category_id
          AND ccp.country_id = :country_id
          AND p.name ILIKE '%color%'
        LIMIT 1
    ");
    $stmt->execute([
        ':category_id' => $categoryId,
        ':country_id' => $countryId
    ]);
    $colorParam = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($colorParam) {
        echo "Color param: {$colorParam['name']} (ID: {$colorParam['id']})\n\n";

        // Get values with RS prefix or višebojna
        $stmt2 = $pdo->prepare("
            SELECT
                pv.id,
                pv.value,
                pv.display_value,
                cpv.alias
            FROM param_param_value ppv
            INNER JOIN param_value pv ON pv.id = ppv.param_value_id
            LEFT JOIN country_param_value cpv ON cpv.param_value_id = pv.id AND cpv.country_id = :country_id
            WHERE ppv.param_id = :param_id
            AND (
                pv.value LIKE 'RS%'
                OR pv.display_value LIKE 'RS%'
                OR cpv.alias LIKE 'RS%'
                OR cpv.alias ILIKE '%višebojna%'
                OR cpv.alias ILIKE '%multicolor%'
                OR pv.value ILIKE '%multicolor%'
            )
            ORDER BY pv.value
            LIMIT 20
        ");
        $stmt2->execute([
            ':param_id' => $colorParam['id'],
            ':country_id' => $countryId
        ]);
        $multiColors = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (count($multiColors) > 0) {
            echo "Found " . count($multiColors) . " matching values:\n\n";
            foreach ($multiColors as $color) {
                echo "Value ID: {$color['id']}\n";
                echo "  value: {$color['value']}\n";
                echo "  display_value: {$color['display_value']}\n";
                echo "  alias (Serbia): {$color['alias']}\n\n";
            }
        } else {
            echo "No values with 'RS -' prefix or višebojna found.\n";
        }
    }

    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║            Testing Current API Responses                      ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    require_once __DIR__ . '/translation-helper.php';

    echo "Testing TranslationHelper (now uses DB first):\n\n";

    $testKeys = [
        'Clothing Brand',
        'Brand',
        'Dresses Type',
        'Color',
    ];

    foreach ($testKeys as $key) {
        $translated = TranslationHelper::translateParam($key, $countryId);
        echo "'$key' → '$translated'\n";
    }

    echo "\n\nNOTE: After recent changes, TranslationHelper now:\n";
    echo "1. First checks fast_message_country table in database\n";
    echo "2. Falls back to translations.php file if not found\n";
    echo "3. Returns original key if still not found\n\n";

    echo "The question is: Does fast_message_country have the correct translations?\n";
    echo "Or do we need to add them?\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
