<?php
/**
 * Check parameter translations for all countries
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/translation-helper.php';

$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== Checking Parameter Translations ===\n\n";

    $countries = [
        11 => 'Serbia (RS) - Serbian',
        12 => 'Kyrgyzstan (KG) - Russian',
        13 => 'Azerbaijan (AZ) - Azerbaijani',
        14 => 'Poland (PL) - Polish',
    ];

    // Get all unique parameter names used across countries
    $stmt = $pdo->prepare('
        SELECT DISTINCT p.name
        FROM param p
        INNER JOIN country_category_param ccp ON ccp.param_id = p.id
        WHERE ccp.country_id IN (11, 12, 13, 14)
        ORDER BY p.name
    ');
    $stmt->execute();
    $allParams = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Total unique parameters used: " . count($allParams) . "\n\n";

    foreach ($countries as $countryId => $countryName) {
        echo "==========================================\n";
        echo "$countryName\n";
        echo "Language: " . TranslationHelper::getLanguageForCountry($countryId) . "\n";
        echo "==========================================\n\n";

        $missingTranslations = [];

        foreach ($allParams as $paramName) {
            $translated = TranslationHelper::translateParam($paramName, $countryId);
            if ($translated === $paramName) {
                $missingTranslations[] = $paramName;
            }
        }

        if (count($missingTranslations) > 0) {
            echo "❌ Missing translations: " . count($missingTranslations) . "\n";
            foreach ($missingTranslations as $param) {
                echo "  - '$param'\n";
            }
        } else {
            echo "✅ All parameters translated!\n";
        }

        echo "\n";
    }

    // Get most common parameter names to prioritize translations
    echo "==========================================\n";
    echo "Most Common Parameters (need translations)\n";
    echo "==========================================\n\n";

    $stmt = $pdo->prepare('
        SELECT p.name, COUNT(DISTINCT ccp.country_id) as country_count
        FROM param p
        INNER JOIN country_category_param ccp ON ccp.param_id = p.id
        WHERE ccp.country_id IN (11, 12, 13, 14)
        GROUP BY p.name
        ORDER BY country_count DESC, p.name ASC
        LIMIT 50
    ');
    $stmt->execute();
    $commonParams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($commonParams as $param) {
        echo "{$param['name']} (used in {$param['country_count']} countries)\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
