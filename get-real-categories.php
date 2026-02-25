<?php
/**
 * Helper script to get REAL leaf category IDs from database
 * Run: php get-real-categories.php
 */

// Database connection settings - UPDATE THESE!
$host = 'localhost'; // or your DB host
$dbname = 'catalog'; // your catalog database name
$user = 'postgres'; // your DB user
$password = ''; // your DB password

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get leaf categories for each country
    // A leaf category is one that has no children (parent_id points to it from no other categories)
    $sql = "
        WITH leaf_categories AS (
            SELECT c.id, c.name, c.country_id, c.parent_id, c.status_id
            FROM category c
            LEFT JOIN category child ON child.parent_id = c.id
            WHERE child.id IS NULL
              AND c.status_id = 3
            GROUP BY c.id, c.name, c.country_id, c.parent_id, c.status_id
        )
        SELECT
            lc.id,
            lc.name,
            lc.country_id,
            co.name as country_name,
            co.code as country_code
        FROM leaf_categories lc
        JOIN country co ON co.id = lc.country_id
        WHERE lc.country_id IN (1, 2, 3, 4) -- KG, RS, PL, AZ
        ORDER BY lc.country_id, lc.id
        LIMIT 100
    ";

    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($results) . " leaf categories:\n\n";

    // Group by country
    $byCountry = [];
    foreach ($results as $row) {
        $countryId = $row['country_id'];
        if (!isset($byCountry[$countryId])) {
            $byCountry[$countryId] = [
                'name' => $row['country_name'],
                'code' => $row['country_code'],
                'categories' => []
            ];
        }
        $byCountry[$countryId]['categories'][] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }

    // Print as JavaScript array format for easy copy-paste into demo HTML
    echo "// Copy this into ads-param-matcher-demo.html:\n\n";
    echo "const mockLeafCategories = {\n";
    foreach ($byCountry as $countryId => $data) {
        echo "    '$countryId': [ // {$data['name']} ({$data['code']})\n";
        foreach (array_slice($data['categories'], 0, 15) as $cat) {
            echo "        {id: {$cat['id']}, name: '[{$cat['id']}] {$cat['name']}'},\n";
        }
        echo "    ],\n";
    }
    echo "};\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    echo "\nPlease update database credentials in this file:\n";
    echo "- \$host = '$host'\n";
    echo "- \$dbname = '$dbname'\n";
    echo "- \$user = '$user'\n";
    echo "- \$password = '...'\n";
}
