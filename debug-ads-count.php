<?php
/**
 * Debug: Check ads count in category
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $categoryId = 4288;
    $countryId = 1;

    // Count ads in category
    $sql = "SELECT COUNT(*) FROM ad WHERE category_id = :cat AND is_deleted = false";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cat' => $categoryId]);
    $totalAds = $stmt->fetchColumn();

    echo "Category: $categoryId\n";
    echo "Total ads (all countries): $totalAds\n\n";

    // Count ads by country
    $sql = "SELECT country_id, COUNT(*) as cnt FROM ad WHERE category_id = :cat AND is_deleted = false GROUP BY country_id ORDER BY cnt DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cat' => $categoryId]);
    $byCountry = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "By country:\n";
    foreach ($byCountry as $row) {
        echo "  Country {$row['country_id']}: {$row['cnt']} ads\n";
    }

    echo "\n";

    // Count ads with params for country 1
    $sql = "
        SELECT COUNT(DISTINCT a.id)
        FROM ad a
        INNER JOIN ad_param ap ON ap.ad_id = a.id
        WHERE a.category_id = :cat
          AND a.country_id = :country
          AND a.is_deleted = false
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cat' => $categoryId, ':country' => $countryId]);
    $adsWithParams = $stmt->fetchColumn();

    echo "Ads with parameters (country $countryId): $adsWithParams\n\n";

    // Find params with usage >= 1
    $sql = "
        SELECT
            p.id,
            p.name,
            COUNT(DISTINCT ap.ad_id) as usage_count
        FROM ad a
        INNER JOIN ad_param ap ON ap.ad_id = a.id
        INNER JOIN param p ON p.id = ap.param_id
        WHERE a.category_id = :cat
          AND a.country_id = :country
          AND a.is_deleted = false
        GROUP BY p.id, p.name
        ORDER BY usage_count DESC
        LIMIT 20
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cat' => $categoryId, ':country' => $countryId]);
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Top parameters (usage >= 1):\n";
    foreach ($params as $p) {
        echo "  [{$p['id']}] {$p['name']} - used in {$p['usage_count']} ads\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
