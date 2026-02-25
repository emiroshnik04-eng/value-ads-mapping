<?php
/**
 * Find real Brand translation from database
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
    echo "║      Finding Real Translations from Database                 ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $countries = [
        11 => 'Serbia',
        12 => 'Kyrgyzstan',
        13 => 'Azerbaijan',
        14 => 'Poland',
    ];

    // First, check if fast_message_country table exists
    echo "1. Checking if fast_message_country table exists...\n";
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'fast_message_country'
        )
    ");
    $tableExists = $stmt->fetchColumn();

    if (!$tableExists) {
        echo "   ❌ fast_message_country table does NOT exist\n";
        echo "   Need to find alternative translation source\n\n";

        // Check for other translation tables
        echo "2. Looking for other translation tables...\n";
        $stmt = $pdo->query("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = 'public'
            AND (
                table_name LIKE '%translation%'
                OR table_name LIKE '%message%'
                OR table_name LIKE '%i18n%'
            )
            ORDER BY table_name
        ");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($tables) > 0) {
            echo "   Found tables:\n";
            foreach ($tables as $table) {
                echo "     - $table\n";
            }
        } else {
            echo "   No translation tables found\n";
        }

        exit;
    }

    echo "   ✅ fast_message_country table exists\n\n";

    // Check what Brand-related keys exist
    echo "2. Searching for 'Brand' related translations...\n\n";

    $brandKeys = [
        'Brand',
        'Clothing Brand',
        'KG - Clothing Brand',
        'AZ - Dresses - Brand',
        'PL - Clothing Brand',
    ];

    foreach ($countries as $countryId => $countryName) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "$countryName (ID: $countryId)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        foreach ($brandKeys as $key) {
            $stmt = $pdo->prepare("
                SELECT message, translation
                FROM fast_message_country
                WHERE country_id = :country_id
                AND message = :message
            ");
            $stmt->execute([
                ':country_id' => $countryId,
                ':message' => $key
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                echo "✅ '$key' → '{$result['translation']}'\n";
            } else {
                echo "⚠️  '$key' → NOT FOUND\n";
            }
        }
        echo "\n";
    }

    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║      Now checking actual params in Dresses category          ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Get actual param names for Serbia + Dresses
    $categoryId = 4287;
    $countryId = 11;

    $stmt = $pdo->prepare("
        SELECT DISTINCT p.name
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
    $actualParams = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Brand-related params in Serbia + Dresses:\n";
    if (count($actualParams) > 0) {
        foreach ($actualParams as $paramName) {
            echo "  - $paramName\n";

            // Check translation
            $stmt = $pdo->prepare("
                SELECT translation
                FROM fast_message_country
                WHERE country_id = 11
                AND message = :message
            ");
            $stmt->execute([':message' => $paramName]);
            $translation = $stmt->fetchColumn();

            if ($translation) {
                echo "    Translation in DB: $translation\n";
            } else {
                echo "    Translation in DB: NOT FOUND\n";
            }
        }
    } else {
        echo "  None found\n";
    }

    echo "\n\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║      Checking ALL params for Serbia + Dresses                ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $stmt = $pdo->prepare("
        SELECT
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
    $allParams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "All params with translations:\n\n";
    foreach ($allParams as $param) {
        $hasTranslation = !empty($param['translation']) && $param['translation'] !== $param['name'];
        $icon = $hasTranslation ? '✅' : '⚠️';
        $translation = $param['translation'] ?: 'NO TRANSLATION';
        echo "$icon '{$param['name']}' → '$translation'\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
