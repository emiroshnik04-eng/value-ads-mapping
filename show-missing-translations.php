<?php
/**
 * Show which translations are missing from fast_message_country table
 * For Serbia + Dresses category
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
    echo "║   Checking Missing Translations for Serbia + Dresses         ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Get all params for this country+category
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            fmc.translation
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        LEFT JOIN fast_message_country fmc ON fmc.message = p.name AND fmc.country_id = :country_id
        WHERE ccp.category_id = :category_id
          AND ccp.country_id = :country_id
        ORDER BY ccp.order_id ASC NULLS LAST
    ");
    $stmt->execute([
        ':category_id' => $categoryId,
        ':country_id' => $countryId
    ]);
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $missingParams = [];
    $foundParams = [];

    foreach ($params as $param) {
        if (empty($param['translation']) || $param['translation'] === $param['name']) {
            $missingParams[] = $param['name'];
        } else {
            $foundParams[$param['name']] = $param['translation'];
        }
    }

    echo "✅ Parameters WITH translations: " . count($foundParams) . "\n";
    foreach ($foundParams as $key => $translation) {
        echo "  '$key' => '$translation'\n";
    }

    echo "\n⚠️  Parameters MISSING translations: " . count($missingParams) . "\n";
    foreach ($missingParams as $key) {
        echo "  '$key'\n";
    }

    echo "\n\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║   Checking All Translations in fast_message_country          ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $stmt = $pdo->prepare("
        SELECT message, translation
        FROM fast_message_country
        WHERE country_id = :country_id
        ORDER BY message
        LIMIT 200
    ");
    $stmt->execute([':country_id' => $countryId]);
    $allTranslations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($allTranslations) . " translations in database for Serbia:\n\n";
    foreach ($allTranslations as $trans) {
        echo "'{$trans['message']}' => '{$trans['translation']}'\n";
    }

    if (count($allTranslations) === 0) {
        echo "\n⚠️  WARNING: No translations found in fast_message_country for Serbia!\n";
        echo "This means all translations are coming from translations.php file.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
