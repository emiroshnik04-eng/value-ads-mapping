<?php
/**
 * Debug Serbia category 4287 issue from screenshot
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/translation-helper.php';

echo "=== Debugging Serbia Category 4287 (Dresses) ===\n\n";

// Test get-params for Serbia + category 4287
echo "1. Testing get-params.php for Serbia (11) + Category 4287...\n";
$url = "http://localhost:8888/get-params.php?country_id=11&category_id=4287";
$response = @file_get_contents($url);

if ($response) {
    $params = json_decode($response, true);
    if (is_array($params)) {
        echo "   ✅ " . count($params) . " params loaded\n\n";

        echo "   Parameters with translations:\n";
        foreach ($params as $param) {
            $original = $param['name'];
            $translated = $param['name_translated'];
            $hasTranslation = ($original !== $translated) ? '✅' : '⚠️';
            echo "     $hasTranslation ID {$param['id']}: '$original' → '$translated'\n";
        }

        // Find param 105 (Color) or param 29 (Condition)
        $colorParam = array_values(array_filter($params, fn($p) => $p['id'] == 105));
        $conditionParam = array_values(array_filter($params, fn($p) => $p['id'] == 29));

        if (!empty($colorParam)) {
            echo "\n   📌 Found Color param (105): {$colorParam[0]['name_translated']}\n";
            $paramId = 105;
        } elseif (!empty($conditionParam)) {
            echo "\n   📌 Found Condition param (29): {$conditionParam[0]['name_translated']}\n";
            $paramId = 29;
        } else {
            echo "\n   ❌ Neither Color (105) nor Condition (29) found\n";
            $paramId = $params[0]['id']; // Use first param
            echo "   📌 Will test with first param: ID $paramId\n";
        }

        echo "\n2. Testing get-param-values.php for Serbia (11) + Category 4287 + Param $paramId...\n";
        $url = "http://localhost:8888/get-param-values.php?country_id=11&category_id=4287&param_id=$paramId";
        $response2 = @file_get_contents($url);

        if ($response2) {
            $values = json_decode($response2, true);
            if (is_array($values)) {
                echo "   ✅ " . count($values) . " values loaded\n\n";

                echo "   Sample values (first 10):\n";
                foreach (array_slice($values, 0, 10) as $value) {
                    $original = $value['display_value'];
                    $translated = $value['display_value_translated'];
                    $hasTranslation = ($original !== $translated) ? '✅' : '⚠️';
                    echo "     $hasTranslation ID {$value['id']}: '$original' → '$translated'\n";
                }

                // Check if any values are in Azerbaijani
                echo "\n   🔍 Checking for Azerbaijani translations (should NOT be here for Serbia):\n";
                $azerbaijaniWords = ['Qara', 'Ağ', 'Boz', 'Mavi', 'Gümüşü', 'Digər'];
                $foundAzerbaijani = false;

                foreach ($values as $value) {
                    $translated = $value['display_value_translated'];
                    if (in_array($translated, $azerbaijaniWords)) {
                        echo "     ❌ FOUND AZERBAIJANI: '{$value['display_value']}' → '$translated'\n";
                        $foundAzerbaijani = true;
                    }
                }

                if (!$foundAzerbaijani) {
                    echo "     ✅ No Azerbaijani translations found (correct!)\n";
                }

            } else {
                echo "   ❌ Invalid response format\n";
            }
        } else {
            echo "   ❌ Failed to fetch param values\n";
        }

    } else {
        echo "   ❌ Invalid response format\n";
    }
} else {
    echo "   ❌ Failed to fetch params\n";
}

echo "\n3. Checking translation system directly...\n";

// Test translation directly for some common values
$testValues = ['black', 'white', 'red', 'blue', 'grey'];

echo "   Testing color translations for Serbia (11):\n";
foreach ($testValues as $value) {
    $translated = TranslationHelper::translateValue($value, 11);
    $hasTranslation = ($value !== $translated) ? '✅' : '⚠️';
    echo "     $hasTranslation '$value' → '$translated'\n";
}
