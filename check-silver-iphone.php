<?php
header('Content-Type: text/html; charset=utf-8');

$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h1>Check: Is 'Silver Iphone' active for Toys category?</h1>";
    echo "<hr>";

    // Check if "Silver Iphone" (23108) is in country_category_param_value for category 1473, country 12
    $stmt = $pdo->prepare("
        SELECT ccpv.*, pv.value
        FROM country_category_param_value ccpv
        INNER JOIN param_value pv ON pv.id = ccpv.param_value_id
        WHERE ccpv.country_id = 12
          AND ccpv.category_id = 1473
          AND ccpv.param_value_id = 23108
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "<p style='color: orange;'>⚠️ YES - 'Silver Iphone' IS configured for Toys category!</p>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        foreach ($result as $key => $val) {
            echo "<tr><td><b>$key</b></td><td>$val</td></tr>";
        }
        echo "</table>";

        echo "<p><b>This is why it appears in the list!</b></p>";
        echo "<p>Solution options:</p>";
        echo "<ul>";
        echo "<li>1. Ask DBA to remove this record from country_category_param_value</li>";
        echo "<li>2. Add additional logic to filter out phone-specific values from non-phone categories</li>";
        echo "<li>3. Keep it as-is (it's technically configured this way in production)</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✅ NO - 'Silver Iphone' is NOT in country_category_param_value for Toys</p>";
        echo "<p>This means my filtering logic is working correctly, but the value still appears due to usage_count.</p>";
    }

    echo "<hr>";
    echo "<h2>Check: How many ads in Toys use 'Silver Iphone'?</h2>";

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ap.ad_id) as usage_count
        FROM ad_param ap
        INNER JOIN ad a ON a.id = ap.ad_id
        WHERE a.category_id = 1473
          AND a.country_id = 12
          AND a.is_deleted = false
          AND ap.param_value_id = 23108
    ");
    $stmt->execute();
    $usageCount = $stmt->fetchColumn();

    echo "<p>Usage count in Toys: <b>" . number_format($usageCount) . " ads</b></p>";

    if ($usageCount > 0) {
        echo "<p style='color: orange;'>⚠️ Value is actually used in Toys ads!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
