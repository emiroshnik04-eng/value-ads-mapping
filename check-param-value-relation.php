<?php
/**
 * Check if there's a table linking params to their valid values
 */

header('Content-Type: text/html; charset=utf-8');

$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h1>🔍 Check Param-Value Relationship Tables</h1>";
    echo "<hr>";

    // Check if param_value_param table exists
    echo "<h2>Test 1: Check for param_value_param table</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM param_value_param LIMIT 1");
        $count = $stmt->fetchColumn();
        echo "<p style='color: green;'>✅ Table <b>param_value_param</b> exists! ($count rows)</p>";

        // Get sample data
        $stmt = $pdo->query("
            SELECT pvp.param_id, p.name as param_name, pvp.param_value_id, pv.value
            FROM param_value_param pvp
            INNER JOIN param p ON p.id = pvp.param_id
            INNER JOIN param_value pv ON pv.id = pvp.param_value_id
            LIMIT 10
        ");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Sample data:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Param ID</th><th>Param Name</th><th>Value ID</th><th>Value</th></tr>";
        foreach ($samples as $row) {
            echo "<tr>";
            echo "<td>{$row['param_id']}</td>";
            echo "<td>{$row['param_name']}</td>";
            echo "<td>{$row['param_value_id']}</td>";
            echo "<td>{$row['value']}</td>";
            echo "</tr>";
        }
        echo "</table>";

    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Table param_value_param does NOT exist</p>";
    }

    // Check for country_param_value which we know exists
    echo "<hr>";
    echo "<h2>Test 2: Check country_param_value table</h2>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               COUNT(CASE WHEN alias IS NOT NULL AND alias != '' THEN 1 END) as with_alias
        FROM country_param_value
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Total records: {$stats['total']}</p>";
    echo "<p>✅ With alias: {$stats['with_alias']}</p>";

    // Check specific example: param_id=105 (Color)
    echo "<hr>";
    echo "<h2>Test 3: Valid values for Color (param_id=105) via country_param_value</h2>";

    $stmt = $pdo->query("
        SELECT DISTINCT cpv.param_value_id, pv.value
        FROM country_param_value cpv
        INNER JOIN param_value pv ON pv.id = cpv.param_value_id
        WHERE cpv.country_id = 12
          AND cpv.param_id = 105
        ORDER BY pv.value
        LIMIT 20
    ");
    $colorValues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($colorValues) > 0) {
        echo "<p style='color: green;'>✅ Found " . count($colorValues) . " valid Color values in country_param_value</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Value ID</th><th>Value</th></tr>";
        foreach ($colorValues as $val) {
            echo "<tr><td>{$val['param_value_id']}</td><td>{$val['value']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No Color values found in country_param_value</p>";
    }

    // Check if "New" (2757) is in Color values
    echo "<hr>";
    echo "<h2>Test 4: Is 'New' (2757) a valid Color value?</h2>";
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM country_param_value
        WHERE country_id = 12
          AND param_id = 105
          AND param_value_id = 2757
    ");
    $stmt->execute();
    $exists = $stmt->fetchColumn();

    if ($exists > 0) {
        echo "<p style='color: red;'>❌ YES - 'New' (2757) is incorrectly configured as a Color value!</p>";
    } else {
        echo "<p style='color: green;'>✅ NO - 'New' (2757) is NOT a valid Color value (correct!)</p>";
    }

    // Conclusion
    echo "<hr>";
    echo "<h2>💡 Solution</h2>";

    if (count($colorValues) > 0) {
        echo "<p><b>Use country_param_value as the source of truth!</b></p>";
        echo "<p>Instead of querying ad_param to find what values exist, use country_param_value to get the VALID values for a param in a country.</p>";
        echo "<pre style='background: #f5f5f5; padding: 15px;'>";
        echo "SELECT pv.id, pv.value, COUNT(DISTINCT ap.ad_id) as usage_count\n";
        echo "FROM country_param_value cpv\n";
        echo "INNER JOIN param_value pv ON pv.id = cpv.param_value_id\n";
        echo "LEFT JOIN ad_param ap ON ap.param_value_id = pv.id \n";
        echo "                      AND ap.param_id = cpv.param_id\n";
        echo "LEFT JOIN ad a ON a.id = ap.ad_id \n";
        echo "              AND a.category_id = :category_id\n";
        echo "              AND a.country_id = :country_id\n";
        echo "              AND a.is_deleted = false\n";
        echo "WHERE cpv.country_id = :country_id\n";
        echo "  AND cpv.param_id = :param_id\n";
        echo "GROUP BY pv.id, pv.value\n";
        echo "ORDER BY usage_count DESC\n";
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ country_param_value doesn't have Color values. Need to investigate alternative solution.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
