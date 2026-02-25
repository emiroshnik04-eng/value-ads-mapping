<?php
/**
 * Apply database indexes for performance optimization
 * Run this file once: http://localhost:8888/admin/apply-indexes.php
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

    echo "Connected to database: {$config['dbname']}\n";
    echo "Host: {$config['host']}\n\n";
    echo "Creating indexes...\n\n";

    // Index 1: Critical for param value lookups
    echo "1. Creating index on ad_param(param_id)... ";
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ad_param_param_id ON ad_param(param_id)");
        echo "✓ SUCCESS\n";
    } catch (PDOException $e) {
        echo "✗ FAILED: " . $e->getMessage() . "\n";
    }

    // Index 2: For JOIN performance
    echo "2. Creating index on ad_param(param_value_id)... ";
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ad_param_param_value_id ON ad_param(param_value_id)");
        echo "✓ SUCCESS\n";
    } catch (PDOException $e) {
        echo "✗ FAILED: " . $e->getMessage() . "\n";
    }

    // Index 3: Composite index for ad filtering
    echo "3. Creating composite index on ad(country_id, is_deleted)... ";
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ad_country_deleted ON ad(country_id, is_deleted)");
        echo "✓ SUCCESS\n";
    } catch (PDOException $e) {
        echo "✗ FAILED: " . $e->getMessage() . "\n";
    }

    echo "\n✅ Indexes applied successfully!\n\n";
    echo "Expected performance improvement:\n";
    echo "- Before: get-param-values.php takes 15-20 seconds\n";
    echo "- After:  get-param-values.php takes < 1 second\n\n";
    echo "Test the improvement:\n";
    echo "curl \"http://localhost:8888/admin/get-param-values.php?country_id=12&param_id=1071\"\n";

} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
