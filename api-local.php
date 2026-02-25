<?php
/**
 * Local API endpoint for standalone ads parameter matcher
 * This file uses the Yii2 application to access the database
 *
 * Usage: php -S localhost:8888 -t api/web/admin
 * Then open: http://localhost:8888/ads-param-matcher-standalone.html
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Bootstrap Yii2 application
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../../../vendor/autoload.php';
require __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../../common/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../../common/config/main.php',
    require __DIR__ . '/../../../api/config/main.php',
    file_exists(__DIR__ . '/../../../common/config/main-local.php')
        ? require __DIR__ . '/../../../common/config/main-local.php'
        : [],
    file_exists(__DIR__ . '/../../../api/config/main-local.php')
        ? require __DIR__ . '/../../../api/config/main-local.php'
        : []
);

try {
    $app = new yii\web\Application($config);

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'search':
            $countryId = (int) ($_GET['country_id'] ?? 0);
            $categoryId = (int) ($_GET['category_id'] ?? 0);
            $paramId = (int) ($_GET['param_id'] ?? 0);
            $paramValueIds = array_filter(
                array_map('intval', explode(',', $_GET['param_value_ids'] ?? ''))
            );

            if (!$countryId || !$categoryId || !$paramId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                exit;
            }

            $results = Yii::$app->adParamMatcher->searchUnlinkedAds(
                $countryId,
                $categoryId,
                $paramId,
                $paramValueIds
            );

            echo json_encode($results);
            break;

        case 'countries':
            // Get countries from location component
            $countries = Yii::$app->location->getCountries();
            $result = [];
            foreach ($countries as $country) {
                $result[] = [
                    'id' => $country->id,
                    'name' => $country->name,
                    'code' => $country->code,
                ];
            }
            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => YII_DEBUG ? $e->getTraceAsString() : null
    ]);
}
