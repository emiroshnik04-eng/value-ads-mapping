<?php
/**
 * Simple CORS proxy for API requests
 * This allows the standalone HTML tool to bypass CORS restrictions
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, country-id, device, language');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$url = $_GET['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL parameter is required']);
    exit;
}

// Security: only allow requests to lalafo.kg domain
if (!preg_match('/^https?:\/\/(api|staging-api)\.lalafo\.kg/', $url)) {
    http_response_code(403);
    echo json_encode(['error' => 'Only lalafo.kg domain is allowed']);
    exit;
}

// Add additional query parameters (e.g., country_id) to the URL
$additionalParams = $_GET;
unset($additionalParams['url']); // Remove the 'url' parameter

if (!empty($additionalParams)) {
    $separator = strpos($url, '?') !== false ? '&' : '?';
    $url .= $separator . http_build_query($additionalParams);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Forward headers from the request
$headers = [];
foreach (getallheaders() as $name => $value) {
    $lowerName = strtolower($name);
    if (in_array($lowerName, ['country-id', 'device', 'language'])) {
        $headers[] = "$name: $value";
    }
}

if (!empty($headers)) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['error' => 'Curl error: ' . $error]);
    exit;
}

curl_close($ch);

http_response_code($httpCode);
echo $response;
