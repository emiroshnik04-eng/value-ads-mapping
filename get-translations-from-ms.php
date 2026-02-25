<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== Getting translations from translation-microservice ===\n\n";

// Try to call translation-microservice API
// Based on typical microservice setup, it should be at translation.api.lalafo.internal or similar

$endpoints = [
    'http://translation-microservice/api/translate',
    'http://translation.api.lalafo.internal/translate',
    'http://localhost:8080/translation/api/translate',
];

$valuesToTranslate = ['Silver Iphone', 'Blue-1'];

echo "Attempting to reach translation-microservice API...\n\n";

foreach ($endpoints as $endpoint) {
    echo "Trying: $endpoint\n";

    // Try GET request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$endpoint?key=Silver+Iphone&language=ru");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode == 200 && $response) {
        echo "✅ SUCCESS! Got response:\n";
        echo $response . "\n\n";
        break;
    } else {
        echo "❌ Failed (HTTP $httpCode): $error\n\n";
    }
}

echo "\n=== Alternative: Check if translations exist in database ===\n";
echo "Since we don't have access to translation-microservice API,\n";
echo "let's check if there's a translation database accessible.\n\n";

// The reporting DB might have a translation schema or table
$configFile = __DIR__ . '/db-config.php';
$dbConfig = require $configFile;

try {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=translation',
        $dbConfig['host'], $dbConfig['port']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "✅ Connected to translation database!\n\n";

    // Check for translation tables
    $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' LIMIT 10";
    $tables = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";

} catch (PDOException $e) {
    echo "❌ Cannot connect to translation database: {$e->getMessage()}\n";
}

echo "\n\n=== Recommendation ===\n";
echo "Without access to translation-microservice, we have two options:\n\n";

echo "1. Use generic translations (recommended for now):\n";
echo "   'Silver Iphone' => 'Серебристый'\n";
echo "   'Blue-1' => 'Синий'\n\n";

echo "2. Leave without translation:\n";
echo "   'Silver Iphone' => 'Silver Iphone'\n";
echo "   'Blue-1' => 'Blue-1'\n\n";

echo "Which option would you prefer?\n";
