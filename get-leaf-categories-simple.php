<?php
/**
 * Get REAL leaf categories from database
 * This script bootstraps Yii2 to access DB
 */

// Bootstrap Yii2
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../../../vendor/autoload.php';
require __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../../common/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../../common/config/main.php',
    require __DIR__ . '/../../../api/config/main.php'
);

try {
    $app = new yii\console\Application($config);

    // Query to get leaf categories (categories with no children)
    $sql = "
        SELECT
            c.id,
            c.name,
            c.country_id,
            co.code as country_code
        FROM category c
        LEFT JOIN category child ON child.parent_id = c.id AND child.is_deleted = false
        LEFT JOIN country co ON co.id = c.country_id
        WHERE child.id IS NULL
          AND c.status_id = 3
          AND c.is_deleted = false
          AND c.country_id IN (1, 2, 3, 4)
        ORDER BY c.country_id, c.id
        LIMIT 100
    ";

    $categories = Yii::$app->db->createCommand($sql)->queryAll();

    echo "Found " . count($categories) . " leaf categories:\n\n";

    // Group by country
    $byCountry = [];
    foreach ($categories as $cat) {
        $countryId = $cat['country_id'];
        if (!isset($byCountry[$countryId])) {
            $byCountry[$countryId] = [];
        }
        $byCountry[$countryId][] = $cat;
    }

    // Output for demo HTML
    echo "// Copy this into ads-param-matcher-demo.html:\n\n";
    echo "'1': [ // Kyrgyzstan\n";
    foreach (array_slice($byCountry[1] ?? [], 0, 30) as $cat) {
        echo "    {id: {$cat['id']}, name: '[{$cat['id']}] {$cat['name']}'},\n";
    }
    echo "],\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "To fix:\n";
    echo "1. Install composer dependencies: cd projects/catalog-microservice && composer install\n";
    echo "2. Or provide database credentials in common/config/main-local.php\n";
}
