<?php
/**
 * Admin panel index - auto-runs extraction if needed
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Catalog Admin - Translation Extraction</title>
    <style>
        body {
            font-family: monospace;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        .button {
            background: #0e639c;
            color: white;
            padding: 15px 30px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
            border-radius: 4px;
        }
        .button:hover {
            background: #1177bb;
        }
        pre {
            background: #252526;
            padding: 20px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #ce9178; }
    </style>
</head>
<body>
    <h1>🔧 Catalog Microservice - Translation Extraction</h1>

    <div>
        <a href="trigger-extraction.php" class="button">🚀 Run Extraction Now</a>
        <a href="ads-param-matcher.html" class="button">📊 Open Interface</a>
        <a href="extraction-log.txt" class="button">📄 View Log</a>
    </div>

    <h2>Status</h2>
    <pre><?php
    $logFile = __DIR__ . '/extraction-log.txt';
    $translationsFile = __DIR__ . '/translations.php';

    if (file_exists($logFile)) {
        echo "Last extraction log:\n\n";
        echo file_get_contents($logFile);
    } else {
        echo "No extraction has been run yet.\n";
        echo "Click 'Run Extraction Now' button above.\n";
    }

    echo "\n\n=== Files ===\n";
    echo "translations.php exists: " . (file_exists($translationsFile) ? "✅ YES" : "❌ NO") . "\n";

    if (file_exists($translationsFile)) {
        echo "translations.php size: " . filesize($translationsFile) . " bytes\n";
        echo "translations.php modified: " . date('Y-m-d H:i:s', filemtime($translationsFile)) . "\n";
    }
    ?></pre>

    <h2>Quick Links</h2>
    <ul>
        <li><a href="TRANSLATION_RULES.md">Translation Rules</a></li>
        <li><a href="README_TRANSLATIONS.md">README Translations</a></li>
        <li><a href="MANUAL_SQL_EXTRACTION.sql">Manual SQL Extraction</a></li>
    </ul>
</body>
</html>
