<?php
/**
 * Find parameters and values that are missing translations
 *
 * This script scans the database for most used params and values,
 * then checks which ones don't have translations yet.
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/translation-helper.php';
$config = require __DIR__ . '/db-config.php';

$countryId = isset($_GET['country_id']) ? (int)$_GET['country_id'] : 12;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $lang = TranslationHelper::getLanguageForCountry($countryId);

    echo "<h1>🔍 Missing Translations Detector</h1>";
    echo "<p><b>Country ID:</b> $countryId | <b>Language:</b> $lang</p>";
    echo "<p><a href='?country_id=12'>Kyrgyzstan (12)</a> | <a href='?country_id=1'>Kazakhstan (1)</a> | <a href='?country_id=17'>Poland (17)</a> | <a href='?country_id=21'>Uzbekistan (21)</a> | <a href='?country_id=18'>Moldova (18)</a></p>";
    echo "<hr>";

    // 1. Find missing PARAMETER translations
    echo "<h2>📋 Missing Parameter Translations</h2>";
    echo "<p>Top $limit most used parameters without translation:</p>";

    $sql = "
        SELECT DISTINCT
            p.id,
            p.name,
            COUNT(DISTINCT ap.ad_id) as usage_count
        FROM param p
        INNER JOIN ad_param ap ON ap.param_id = p.id
        INNER JOIN ad a ON a.id = ap.ad_id
        WHERE a.country_id = :country_id
          AND a.is_deleted = false
        GROUP BY p.id, p.name
        ORDER BY usage_count DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':country_id' => $countryId,
        ':limit' => $limit
    ]);
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $missingParams = [];
    foreach ($params as $param) {
        $translated = TranslationHelper::translateParam($param['name'], $countryId);
        if ($translated === $param['name']) {
            // No translation found
            $missingParams[] = $param;
        }
    }

    if (empty($missingParams)) {
        echo "<p style='color: green;'>✅ All top $limit parameters have translations!</p>";
    } else {
        echo "<p style='color: red;'>❌ Found " . count($missingParams) . " parameters without translation:</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name (English)</th><th>Usage Count</th><th>Add to translations.php</th></tr>";

        foreach ($missingParams as $param) {
            $usageCount = number_format($param['usage_count']);
            $codeSnippet = "'" . addslashes($param['name']) . "' => 'TODO',";
            echo "<tr>";
            echo "<td>{$param['id']}</td>";
            echo "<td><b>{$param['name']}</b></td>";
            echo "<td>{$usageCount} ads</td>";
            echo "<td><code>{$codeSnippet}</code></td>";
            echo "</tr>";
        }

        echo "</table>";
    }

    // 2. Find missing VALUE translations
    echo "<hr>";
    echo "<h2>🏷️ Missing Value Translations</h2>";
    echo "<p>Top $limit most used values without translation:</p>";

    $sql = "
        SELECT
            pv.id,
            pv.value,
            COUNT(DISTINCT ap.ad_id) as usage_count
        FROM param_value pv
        INNER JOIN ad_param ap ON ap.param_value_id = pv.id
        INNER JOIN ad a ON a.id = ap.ad_id
        WHERE a.country_id = :country_id
          AND a.is_deleted = false
        GROUP BY pv.id, pv.value
        ORDER BY usage_count DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':country_id' => $countryId,
        ':limit' => $limit
    ]);
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $missingValues = [];
    foreach ($values as $value) {
        $translated = TranslationHelper::translateValue($value['value'], $countryId);
        if ($translated === $value['value']) {
            // No translation found
            $missingValues[] = $value;
        }
    }

    if (empty($missingValues)) {
        echo "<p style='color: green;'>✅ All top $limit values have translations!</p>";
    } else {
        echo "<p style='color: red;'>❌ Found " . count($missingValues) . " values without translation:</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Value (English)</th><th>Usage Count</th><th>Add to translations.php</th></tr>";

        foreach ($missingValues as $value) {
            $usageCount = number_format($value['usage_count']);
            $codeSnippet = "'" . addslashes($value['value']) . "' => 'TODO',";
            echo "<tr>";
            echo "<td>{$value['id']}</td>";
            echo "<td><b>{$value['value']}</b></td>";
            echo "<td>{$usageCount} ads</td>";
            echo "<td><code>{$codeSnippet}</code></td>";
            echo "</tr>";
        }

        echo "</table>";
    }

    // 3. Statistics
    echo "<hr>";
    echo "<h2>📊 Translation Coverage Statistics</h2>";

    $translations = require __DIR__ . '/translations.php';
    $langTranslations = $translations[$lang] ?? ['params' => [], 'values' => []];

    $totalParams = count($params);
    $translatedParams = $totalParams - count($missingParams);
    $coverageParams = $totalParams > 0 ? round(($translatedParams / $totalParams) * 100, 1) : 0;

    $totalValues = count($values);
    $translatedValues = $totalValues - count($missingValues);
    $coverageValues = $totalValues > 0 ? round(($translatedValues / $totalValues) * 100, 1) : 0;

    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>Type</th><th>Total in translations.php</th><th>Used in top $limit</th><th>Translated</th><th>Coverage</th></tr>";

    echo "<tr>";
    echo "<td><b>Parameters</b></td>";
    echo "<td>" . count($langTranslations['params']) . "</td>";
    echo "<td>$totalParams</td>";
    echo "<td>$translatedParams</td>";
    echo "<td><b style='color: " . ($coverageParams >= 80 ? 'green' : 'red') . ";'>{$coverageParams}%</b></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><b>Values</b></td>";
    echo "<td>" . count($langTranslations['values']) . "</td>";
    echo "<td>$totalValues</td>";
    echo "<td>$translatedValues</td>";
    echo "<td><b style='color: " . ($coverageValues >= 80 ? 'green' : 'red') . ";'>{$coverageValues}%</b></td>";
    echo "</tr>";

    echo "</table>";

    echo "<hr>";
    echo "<p><a href='ads-param-matcher-demo.html'>← Back to Main Interface</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'><b>Database Error:</b> " . $e->getMessage() . "</p>";
}
