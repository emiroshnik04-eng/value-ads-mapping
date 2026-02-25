<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== All 16 color values for category 1473 with translations ===\n\n";

$url = 'http://localhost:8888/get-param-values.php?country_id=12&category_id=1473&param_id=105';
exec("curl -s \"$url\"", $output, $returnCode);

if ($returnCode === 0) {
    $json = implode("\n", $output);
    $values = json_decode($json, true);

    if ($values !== null && is_array($values)) {
        echo "Total: " . count($values) . " values\n\n";

        foreach ($values as $v) {
            $original = $v['display_value'];
            $translated = $v['display_value_translated'];
            $usage = number_format($v['usage_count']);
            
            $hasTranslation = ($original !== $translated) ? '✅' : '❌';
            
            echo sprintf("%s ID %d: %s => %s (usage: %s)\n",
                $hasTranslation,
                $v['id'],
                $original,
                $translated,
                $usage
            );
        }
        
        // Count how many have translations
        $withTranslation = count(array_filter($values, fn($v) => $v['display_value'] !== $v['display_value_translated']));
        $withoutTranslation = count($values) - $withTranslation;
        
        echo "\n=== Summary ===\n";
        echo "With translation: $withTranslation / " . count($values) . "\n";
        echo "Without translation: $withoutTranslation / " . count($values) . "\n";
    }
}
