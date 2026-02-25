<?php
/**
 * Quick test of API endpoints
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "Testing API endpoints...\n\n";

// Test categories
echo "1. Testing get-categories.php?country_id=11\n";

$context = stream_context_create([
    'http' => [
        'ignore_errors' => true,
        'timeout' => 5
    ]
]);

$response = @file_get_contents('http://localhost:8888/get-categories.php?country_id=11', false, $context);

if ($response === false) {
    echo "   ❌ Request failed\n";
    $error = error_get_last();
    if ($error) {
        print_r($error);
    }
} else {
    $http_response_header_local = $http_response_header;
    echo "   Response code: " . $http_response_header_local[0] . "\n";
    echo "   Response length: " . strlen($response) . " bytes\n";

    // Check if it's JSON
    $json = json_decode($response, true);
    if ($json === null) {
        echo "   ❌ Not valid JSON\n";
        echo "   First 500 chars: " . substr($response, 0, 500) . "\n";
    } else {
        echo "   ✅ Valid JSON\n";
        if (isset($json['error'])) {
            echo "   ❌ API Error: {$json['error']}\n";
            if (isset($json['message'])) {
                echo "   Message: {$json['message']}\n";
            }
        } else if (isset($json['data'])) {
            echo "   ✅ Got data: " . count($json['data']) . " categories\n";
        }
    }
}

echo "\n";
