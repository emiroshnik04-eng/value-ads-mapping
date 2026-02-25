<?php
/**
 * Extract ALL translations from database and create correct translations.php
 */

$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Extracting translations from database...\n\n";

    $countries = [
        11 => 'sr', // Serbia
        12 => 'ru', // Kyrgyzstan
        13 => 'az', // Azerbaijan
        14 => 'pl', // Poland
    ];

    $translations = [];

    // Check category table for name_sr, name_az, etc columns
    echo "1. Checking category table for language columns...\n";
    $stmt = $pdo->query("
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'category'
        AND column_name LIKE 'name_%'
    ");
    $nameColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($nameColumns) > 0) {
        echo "   Found columns: " . implode(', ', $nameColumns) . "\n\n";

        foreach ($countries as $countryId => $lang) {
            $columnName = 'name_' . $lang;
            if (in_array($columnName, $nameColumns)) {
                echo "   Extracting categories for $lang ($columnName)...\n";
                $stmt = $pdo->query("
                    SELECT name, $columnName as translation
                    FROM category
                    WHERE $columnName IS NOT NULL
                    AND $columnName != ''
                    AND name != $columnName
                    LIMIT 1000
                ");
                $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!isset($translations[$lang])) {
                    $translations[$lang] = ['categories' => [], 'params' => [], 'values' => []];
                }

                foreach ($cats as $cat) {
                    $translations[$lang]['categories'][$cat['name']] = $cat['translation'];
                }

                echo "     Found " . count($cats) . " translations\n";
            }
        }
    } else {
        echo "   No language columns found in category table\n";
    }

    // Check param table
    echo "\n2. Checking param table for language columns...\n";
    $stmt = $pdo->query("
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'param'
        AND column_name LIKE 'name_%'
    ");
    $paramColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($paramColumns) > 0) {
        echo "   Found columns: " . implode(', ', $paramColumns) . "\n\n";

        foreach ($countries as $countryId => $lang) {
            $columnName = 'name_' . $lang;
            if (in_array($columnName, $paramColumns)) {
                echo "   Extracting params for $lang ($columnName)...\n";
                $stmt = $pdo->query("
                    SELECT name, $columnName as translation
                    FROM param
                    WHERE $columnName IS NOT NULL
                    AND $columnName != ''
                    AND name != $columnName
                    LIMIT 1000
                ");
                $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!isset($translations[$lang])) {
                    $translations[$lang] = ['categories' => [], 'params' => [], 'values' => []];
                }

                foreach ($params as $param) {
                    $translations[$lang]['params'][$param['name']] = $param['translation'];
                }

                echo "     Found " . count($params) . " translations\n";
            }
        }
    } else {
        echo "   No language columns found in param table\n";
    }

    // Get param value aliases
    echo "\n3. Extracting param value aliases from country_param_value...\n";

    foreach ($countries as $countryId => $lang) {
        echo "   Country $countryId ($lang)...\n";

        $stmt = $pdo->prepare("
            SELECT
                pv.value,
                pv.display_value,
                cpv.alias
            FROM country_param_value cpv
            JOIN param_value pv ON pv.id = cpv.param_value_id
            WHERE cpv.country_id = :country_id
            AND cpv.alias IS NOT NULL
            AND cpv.alias != ''
            LIMIT 1000
        ");
        $stmt->execute([':country_id' => $countryId]);
        $aliases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!isset($translations[$lang])) {
            $translations[$lang] = ['categories' => [], 'params' => [], 'values' => []];
        }

        foreach ($aliases as $alias) {
            $key = $alias['display_value'] ?: $alias['value'];
            if ($key && $alias['alias'] && $key !== $alias['alias']) {
                $translations[$lang]['values'][$key] = $alias['alias'];
            }
        }

        echo "     Found " . count($aliases) . " aliases\n";
    }

    // Summary
    echo "\n=== Summary ===\n\n";

    foreach ($translations as $lang => $sections) {
        echo "Language: $lang\n";
        echo "  Categories: " . count($sections['categories']) . "\n";
        echo "  Parameters: " . count($sections['params']) . "\n";
        echo "  Values: " . count($sections['values']) . "\n";

        // Show Brand translation specifically
        if (isset($sections['params']['Brand'])) {
            echo "  >>> Brand: " . $sections['params']['Brand'] . "\n";
        }
        if (isset($sections['params']['Clothing Brand'])) {
            echo "  >>> Clothing Brand: " . $sections['params']['Clothing Brand'] . "\n";
        }
        echo "\n";
    }

    // Save to file
    if (count($translations) > 0) {
        $output = "<?php\n";
        $output .= "/**\n";
        $output .= " * Translations extracted from database\n";
        $output .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
        $output .= " */\n\n";
        $output .= "return " . var_export($translations, true) . ";\n";

        file_put_contents(__DIR__ . '/translations-from-db.php', $output);
        echo "Saved to: translations-from-db.php\n";
    } else {
        echo "No translations found\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
