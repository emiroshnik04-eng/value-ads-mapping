<?php
header('Content-Type: text/plain; charset=utf-8');

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   Ads Parameter Matcher - Multi-Country Translation Demo      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$countries = [
    11 => ['name' => 'Serbia (RS)', 'flag' => '🇷🇸', 'lang' => 'Serbian'],
    12 => ['name' => 'Kyrgyzstan (KG)', 'flag' => '🇰🇬', 'lang' => 'Russian'],
    13 => ['name' => 'Azerbaijan (AZ)', 'flag' => '🇦🇿', 'lang' => 'Azerbaijani'],
    14 => ['name' => 'Poland (PL)', 'flag' => '🇵🇱', 'lang' => 'Polish'],
];

echo "Testing category 1473 (Toys), param 105 (Color) for all countries:\n\n";

foreach ($countries as $countryId => $info) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "{$info['flag']} {$info['name']} - {$info['lang']}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $url = "http://localhost:8888/get-param-values.php?country_id=$countryId&category_id=1473&param_id=105";
    exec("curl -s \"$url\"", $output, $returnCode);

    if ($returnCode === 0) {
        $json = implode("\n", $output);
        $values = json_decode($json, true);

        if (is_array($values) && count($values) > 0) {
            echo "✅ Total values: " . count($values) . "\n\n";

            // Show first 10 values
            echo "Sample values:\n";
            foreach (array_slice($values, 0, 10) as $value) {
                $original = $value['display_value'];
                $translated = $value['display_value_translated'];
                $hasTranslation = ($original !== $translated) ? '✅' : '⚠️';

                printf("  %s ID %d: %s → %s\n",
                    $hasTranslation,
                    $value['id'],
                    str_pad($original, 20),
                    $translated
                );
            }

            // Check Silver Iphone and Blue-1
            echo "\nSpecial phone-specific values:\n";
            foreach ($values as $value) {
                if ($value['display_value'] === 'Silver Iphone' || $value['display_value'] === 'Blue-1') {
                    $original = $value['display_value'];
                    $translated = $value['display_value_translated'];
                    $hasTranslation = ($original !== $translated) ? '✅' : '❌';

                    printf("  %s ID %d: %s → %s\n",
                        $hasTranslation,
                        $value['id'],
                        str_pad($original, 20),
                        $translated
                    );
                }
            }
        } else {
            echo "❌ No values returned or invalid response\n";
        }
    } else {
        echo "❌ Failed to fetch data\n";
    }

    echo "\n";
    $output = []; // Clear for next iteration
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        Test Complete!                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Open in browser:\n";
foreach ($countries as $countryId => $info) {
    echo "  {$info['flag']} {$info['name']}: http://localhost:8888/ads-param-matcher.html?country_id=$countryId&category_id=1473&param_id=105\n";
}
