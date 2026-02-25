<?php
/**
 * Fetch real translations from database for all countries
 * Check fast_message_country table for actual translations
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
    echo "║      Fetching Real Translations from Database                ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $countries = [
        11 => ['name' => 'Serbia', 'code' => 'RS', 'lang' => 'sr'],
        12 => ['name' => 'Kyrgyzstan', 'code' => 'KG', 'lang' => 'ru'],
        13 => ['name' => 'Azerbaijan', 'code' => 'AZ', 'lang' => 'az'],
        14 => ['name' => 'Poland', 'code' => 'PL', 'lang' => 'pl'],
    ];

    // Keys we need translations for (from screenshots and current translations.php)
    $keysToCheck = [
        // Categories
        'Dresses',
        'Women\'s Clothing',
        'Men\'s Clothing',
        'Shoes',
        'Accessories',
        'Toys',
        'Electronics',

        // Parameters - check exact keys from database
        'Clothing Brand',
        'Brand',
        'Dresses Type',
        'Dresses, skirts - Length',
        'Color',
        'Size',
        'Condition',
        'Personal items - Material',
        'Personal items - Pattern',
        'Personal items - Sleeves',

        // Values
        'Evening',
        'Cocktail',
        'Oversize',
        'Casual',
        'Business',
        'Maxi',
        'Mini',
        'Midi',
        'New',
        'Used',
    ];

    foreach ($countries as $countryId => $info) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "{$info['name']} (ID: $countryId, Lang: {$info['lang']})\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        foreach ($keysToCheck as $key) {
            // Check fast_message_country
            $stmt = $pdo->prepare("
                SELECT translation
                FROM fast_message_country
                WHERE country_id = :country_id
                AND message = :message
                LIMIT 1
            ");
            $stmt->execute([
                ':country_id' => $countryId,
                ':message' => $key
            ]);
            $translation = $stmt->fetchColumn();

            if ($translation && $translation !== $key) {
                echo "'{$key}' => '{$translation}',\n";
            }
        }
        echo "\n";
    }

    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║      Checking ALL params for Serbia + Dresses category       ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Get ALL params for Serbia + Dresses to see exact keys
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            p.id,
            p.name,
            fmc.translation
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        LEFT JOIN fast_message_country fmc ON fmc.message = p.name AND fmc.country_id = 11
        WHERE ccp.category_id = 4287
          AND ccp.country_id = 11
        ORDER BY p.name
    ");
    $stmt->execute();
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($params) . " parameters:\n\n";
    foreach ($params as $param) {
        $trans = $param['translation'] ?: 'NO TRANSLATION';
        echo "'{$param['name']}' => '{$trans}',\n";
    }

    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║      Checking param VALUES with 'RS -' or country prefixes    ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Check if there are values with country prefixes
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            pv.id,
            pv.value,
            pv.display_value,
            cpv.alias,
            cpv.country_id
        FROM param_value pv
        LEFT JOIN country_param_value cpv ON cpv.param_value_id = pv.id
        WHERE (
            pv.value LIKE 'RS -%'
            OR pv.value LIKE 'KG -%'
            OR pv.value LIKE 'AZ -%'
            OR pv.value LIKE 'PL -%'
            OR pv.display_value LIKE 'RS -%'
            OR pv.display_value LIKE 'KG -%'
            OR pv.display_value LIKE 'AZ -%'
            OR pv.display_value LIKE 'PL -%'
            OR cpv.alias LIKE 'RS -%'
            OR cpv.alias LIKE 'KG -%'
            OR cpv.alias LIKE 'AZ -%'
            OR cpv.alias LIKE 'PL -%'
        )
        AND cpv.country_id IN (11, 12, 13, 14)
        ORDER BY cpv.country_id, pv.value
        LIMIT 50
    ");
    $stmt->execute();
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($values) > 0) {
        echo "Found " . count($values) . " values with country prefixes:\n\n";
        foreach ($values as $val) {
            $countryInfo = $countries[$val['country_id']] ?? ['name' => 'Unknown'];
            echo "[{$countryInfo['name']}] Value: {$val['value']} | Display: {$val['display_value']} | Alias: {$val['alias']}\n";
        }
    } else {
        echo "No values with country prefixes found\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
