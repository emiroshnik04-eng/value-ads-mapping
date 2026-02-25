<?php
/**
 * Diagnostic tool to check param value data quality
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

    $countryId = isset($_GET['country_id']) ? (int)$_GET['country_id'] : 12;
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 1473;

    echo "<h1>🔍 Diagnostic: Param Values Quality Check</h1>";
    echo "<p>Category: $categoryId, Country: $countryId</p>";
    echo "<hr>";

    // Get category name
    $stmt = $pdo->prepare("SELECT name FROM category WHERE id = ?");
    $stmt->execute([$categoryId]);
    $categoryName = $stmt->fetchColumn();
    echo "<h2>Category: $categoryName (ID: $categoryId)</h2>";

    // Get all params for this category
    echo "<h2>Parameters in this category:</h2>";
    $stmt = $pdo->prepare("
        SELECT p.id, p.name
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        WHERE ccp.category_id = ? AND ccp.country_id = ?
        ORDER BY ccp.order_id ASC NULLS LAST
        LIMIT 20
    ");
    $stmt->execute([$categoryId, $countryId]);
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>Param ID</th><th>Param Name</th><th>Check Values</th></tr>";
    foreach ($params as $param) {
        $checkUrl = "?country_id=$countryId&category_id=$categoryId&param_id={$param['id']}";
        echo "<tr>";
        echo "<td>{$param['id']}</td>";
        echo "<td><b>{$param['name']}</b></td>";
        echo "<td><a href='$checkUrl' style='color: #4caf50;'>Check Values</a></td>";
        echo "</tr>";
    }
    echo "</table>";

    // If param_id provided, show detailed analysis
    $paramId = isset($_GET['param_id']) ? (int)$_GET['param_id'] : 0;

    if ($paramId) {
        echo "<hr>";

        // Get param name
        $stmt = $pdo->prepare("SELECT name FROM param WHERE id = ?");
        $stmt->execute([$paramId]);
        $paramName = $stmt->fetchColumn();

        echo "<h2>📊 Detailed Analysis: $paramName (ID: $paramId)</h2>";

        // Get values using CURRENT logic (from get-param-values.php)
        $sql = "
            SELECT
                pv.id,
                pv.value,
                pv.param_id as original_param_id,
                COUNT(DISTINCT ap.ad_id) as usage_count
            FROM ad_param ap
            INNER JOIN ad a ON a.id = ap.ad_id
            INNER JOIN param_value pv ON pv.id = ap.param_value_id
            WHERE ap.param_id = :param_id
              AND a.category_id = :category_id
              AND a.country_id = :country_id
              AND a.is_deleted = false
            GROUP BY pv.id, pv.value, pv.param_id
            ORDER BY usage_count DESC
            LIMIT 100
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':param_id' => $paramId,
            ':category_id' => $categoryId,
            ':country_id' => $countryId
        ]);
        $values = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Values returned by current API:</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr>";
        echo "<th>Value ID</th>";
        echo "<th>Value</th>";
        echo "<th>Original Param ID</th>";
        echo "<th>Usage Count</th>";
        echo "<th>Status</th>";
        echo "</tr>";

        foreach ($values as $val) {
            $isCorrect = ($val['original_param_id'] == $paramId);
            $statusColor = $isCorrect ? 'green' : 'red';
            $statusText = $isCorrect ? '✅ Correct' : '❌ WRONG PARAM!';

            echo "<tr style='background: " . ($isCorrect ? '#f0fff0' : '#fff0f0') . "'>";
            echo "<td>{$val['id']}</td>";
            echo "<td><b>{$val['value']}</b></td>";
            echo "<td>{$val['original_param_id']}</td>";
            echo "<td>{$val['usage_count']}</td>";
            echo "<td style='color: $statusColor;'><b>$statusText</b></td>";
            echo "</tr>";
        }
        echo "</table>";

        // Count wrong values
        $wrongCount = 0;
        foreach ($values as $val) {
            if ($val['original_param_id'] != $paramId) {
                $wrongCount++;
            }
        }

        if ($wrongCount > 0) {
            echo "<div style='background: #fff0f0; padding: 15px; border-left: 4px solid #f44336; margin-top: 20px;'>";
            echo "<h3 style='color: #f44336;'>⚠️ DATA QUALITY ISSUE FOUND!</h3>";
            echo "<p><b>$wrongCount</b> values belong to DIFFERENT parameters!</p>";
            echo "<p><b>Root cause:</b> In ad_param table, there are records where:</p>";
            echo "<ul>";
            echo "<li>param_id = $paramId (<b>$paramName</b>)</li>";
            echo "<li>param_value_id belongs to a DIFFERENT param</li>";
            echo "</ul>";
            echo "<p><b>Solution:</b> Filter out values where pv.param_id != ap.param_id</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f0fff0; padding: 15px; border-left: 4px solid #4caf50; margin-top: 20px;'>";
            echo "<h3 style='color: #4caf50;'>✅ DATA QUALITY OK</h3>";
            echo "<p>All values belong to the correct parameter.</p>";
            echo "</div>";
        }

        // Show recommended fix
        echo "<hr>";
        echo "<h3>🔧 Recommended Fix:</h3>";
        echo "<p>Add condition to filter out mismatched param values:</p>";
        echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 4px;'>";
        echo "WHERE ap.param_id = :param_id\n";
        echo "  AND a.category_id = :category_id\n";
        echo "  AND a.country_id = :country_id\n";
        echo "  AND a.is_deleted = false\n";
        echo "  <b style='color: #4caf50;'>AND pv.param_id = :param_id  -- ADD THIS LINE</b>\n";
        echo "</pre>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
