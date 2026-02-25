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

    require_once __DIR__ . '/translation-helper.php';

    $countries = [
        11 => 'Serbia (RS) - Serbian',
        12 => 'Kyrgyzstan (KG) - Russian',
        13 => 'Azerbaijan (AZ) - Azerbaijani',
        14 => 'Poland (PL) - Polish',
    ];

    foreach ($countries as $countryId => $countryName) {
        echo "==========================================\n";
        echo "$countryName\n";
        echo "==========================================\n\n";

        // Get actual param values from API
        $url = "http://localhost:8888/get-param-values.php?country_id=$countryId&category_id=1473&param_id=105";
        exec("curl -s \"$url\"", $output, $returnCode);

        if ($returnCode === 0) {
            $json = implode("\n", $output);
            $values = json_decode($json, true);

            if (is_array($values)) {
                $total = count($values);
                $translated = 0;
                $missing = [];

                foreach ($values as $value) {
                    if ($value['display_value'] !== $value['display_value_translated']) {
                        $translated++;
                    } else {
                        $missing[] = $value['display_value'];
                    }
                }

                echo "Total values: $total\n";
                echo "Translated: $translated\n";
                echo "Missing translations: " . count($missing) . "\n\n";

                if (!empty($missing)) {
                    echo "Values without translation:\n";
                    foreach ($missing as $val) {
                        echo "  - '$val'\n";
                    }
                    echo "\n";
                }
            }
        }

        $output = [];
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
