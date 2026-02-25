<?php
header('Content-Type: text/plain; charset=utf-8');
$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $adIds = [64662665, 70034541, 72137139, 74891870, 75443513, 79928502, 90302295, 92125934, 95071458, 107584091, 108055873, 109617453, 109902561];
    
    echo "=== РАССЛЕДОВАНИЕ ИЗМЕНЕНИЯ СТАТУСОВ ОБЪЯВЛЕНИЙ ===\n";
    echo "Дата переноса: 28.01.2025\n";
    echo "Количество объявлений: " . count($adIds) . "\n\n";
    
    $placeholders = implode(',', $adIds);
    
    // 1. Текущее состояние объявлений
    echo "=== 1. ТЕКУЩЕЕ СОСТОЯНИЕ ОБЪЯВЛЕНИЙ ===\n\n";
    
    $result = $pdo->query("
        SELECT 
            id,
            status_id,
            category_id,
            is_ppv,
            ppv_price,
            updated_time,
            TO_TIMESTAMP(updated_time) as updated_date,
            is_deleted,
            title
        FROM ad
        WHERE id IN ($placeholders)
        ORDER BY status_id, id
    ");
    
    $byStatus = [];
    foreach ($result as $row) {
        $status = $row['status_id'];
        if (!isset($byStatus[$status])) {
            $byStatus[$status] = [];
        }
        $byStatus[$status][] = $row;
    }
    
    foreach ($byStatus as $status => $ads) {
        echo "Status $status (" . count($ads) . " объявлений):\n";
        foreach ($ads as $ad) {
            echo "  ad_{$ad['id']}:\n";
            echo "    category: {$ad['category_id']}\n";
            echo "    is_ppv: " . ($ad['is_ppv'] ? 'true' : 'false') . "\n";
            echo "    ppv_price: {$ad['ppv_price']}\n";
            echo "    updated: {$ad['updated_date']}\n";
            echo "    title: " . substr($ad['title'], 0, 50) . "\n";
        }
        echo "\n";
    }
    
    // 2. Проверка истории изменений (если есть таблица истории)
    echo "=== 2. ПОИСК ТАБЛИЦ С ИСТОРИЕЙ ===\n\n";
    
    $historyTables = $pdo->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND (
              table_name LIKE '%history%' 
              OR table_name LIKE '%log%'
              OR table_name LIKE '%audit%'
              OR table_name LIKE '%archive%'
          )
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($historyTables)) {
        echo "Найдены таблицы истории:\n";
        foreach ($historyTables as $table) {
            echo "  - $table\n";
        }
    } else {
        echo "❌ Таблицы истории не найдены\n";
    }
    
    echo "\n=== 3. АНАЛИЗ КАТЕГОРИЙ ===\n\n";
    
    $categories = $pdo->query("
        SELECT DISTINCT 
            a.category_id,
            c.name as category_name,
            COUNT(*) as ads_count
        FROM ad a
        LEFT JOIN category c ON c.id = a.category_id
        WHERE a.id IN ($placeholders)
        GROUP BY a.category_id, c.name
        ORDER BY ads_count DESC
    ");
    
    echo "Объявления распределены по категориям:\n";
    foreach ($categories as $cat) {
        echo "  category_{$cat['category_id']}: {$cat['category_name']} - {$cat['ads_count']} объявлений\n";
    }
    
    echo "\n=== 4. ПРОВЕРКА PPV ПРАВИЛ ===\n\n";
    
    // Проверим есть ли таблица с правилами PPV
    $ppvTables = $pdo->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_name LIKE '%ppv%'
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Таблицы связанные с PPV:\n";
    foreach ($ppvTables as $table) {
        echo "  - $table\n";
    }
    
    echo "\n=== 5. СТАТИСТИКА ПО СТАТУСАМ ===\n\n";
    
    $statusStats = $pdo->query("
        SELECT 
            status_id,
            COUNT(*) as count,
            COUNT(CASE WHEN is_ppv = true THEN 1 END) as ppv_count,
            AVG(ppv_price) as avg_ppv_price
        FROM ad
        WHERE id IN ($placeholders)
        GROUP BY status_id
        ORDER BY status_id
    ");
    
    echo "Статистика по статусам:\n";
    foreach ($statusStats as $stat) {
        echo "  status_{$stat['status_id']}:\n";
        echo "    всего: {$stat['count']}\n";
        echo "    с PPV: {$stat['ppv_count']}\n";
        echo "    средняя цена PPV: " . round($stat['avg_ppv_price']) . "\n\n";
    }
    
    echo "=== 6. ВРЕМЯ ОБНОВЛЕНИЯ ===\n\n";
    
    $timeAnalysis = $pdo->query("
        SELECT 
            TO_TIMESTAMP(updated_time) as updated_date,
            COUNT(*) as count
        FROM ad
        WHERE id IN ($placeholders)
        GROUP BY TO_TIMESTAMP(updated_time)
        ORDER BY updated_date DESC
    ");
    
    echo "Группировка по времени обновления:\n";
    foreach ($timeAnalysis as $time) {
        echo "  {$time['updated_date']}: {$time['count']} объявлений\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
