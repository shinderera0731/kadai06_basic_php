<?php
// CSVファイルから見積もりデータを読み込む関数
function readEstimateData() {
    $csv_file = 'data/estimates.csv';
    $data = [];
    
    if (!file_exists($csv_file)) {
        return $data;
    }
    
    $fp = fopen($csv_file, 'r');
    if ($fp === false) {
        return $data;
    }
    
    // ヘッダー行をスキップ
    $headers = fgetcsv($fp);
    
    // データ行を読み込み
    while (($row = fgetcsv($fp)) !== false) {
        if (count($row) >= 20) {
            $data[] = [
                'estimate_id' => $row[0] ?? '',
                'customer_name' => $row[1] ?? '',
                'phone' => $row[2] ?? '',
                'email' => $row[3] ?? '',
                'address' => $row[4] ?? '',
                'tree_details' => $row[5] ?? '',
                'pine_details' => $row[6] ?? '',
                'maki_details' => $row[7] ?? '',
                'waste_amount' => intval($row[8] ?? 0),
                'grass_cutting' => $row[9] ?? '',
                'hedge_trimming' => $row[10] ?? '',
                'fertilizing' => $row[11] ?? '',
                'cleanup' => $row[12] ?? '',
                'garden_size' => $row[13] ?? '',
                'special_requests' => $row[14] ?? '',
                'preferred_date' => $row[15] ?? '',
                'preferred_time' => $row[16] ?? '',
                'total_amount' => intval($row[17] ?? 0),
                'status' => $row[18] ?? '',
                'created_at' => $row[19] ?? ''
            ];
        }
    }
    
    fclose($fp);
    return array_reverse($data); // 新しいものから表示
}

// 統計情報を計算する関数
function calculateStatistics($data) {
    if (empty($data)) {
        return [
            'total_estimates' => 0,
            'total_revenue' => 0,
            'average_amount' => 0,
            'this_month_count' => 0,
            'this_month_revenue' => 0,
            'monthly' => [],
            'services' => ['grass_cutting' => 0, 'hedge_trimming' => 0, 'fertilizing' => 0, 'cleanup' => 0],
            'status_breakdown' => []
        ];
    }
    
    $stats = [];
    $stats['total_estimates'] = count($data);
    $stats['total_revenue'] = array_sum(array_column($data, 'total_amount'));
    $stats['average_amount'] = $stats['total_revenue'] / $stats['total_estimates'];
    
    // 今月の統計
    $current_month = date('Y-m');
    $this_month_data = array_filter($data, function($estimate) use ($current_month) {
        return date('Y-m', strtotime($estimate['created_at'])) === $current_month;
    });
    $stats['this_month_count'] = count($this_month_data);
    $stats['this_month_revenue'] = array_sum(array_column($this_month_data, 'total_amount'));
    
    // 月別統計
    $monthly = [];
    foreach ($data as $estimate) {
        $month = date('Y-m', strtotime($estimate['created_at']));
        if (!isset($monthly[$month])) {
            $monthly[$month] = ['count' => 0, 'amount' => 0];
        }
        $monthly[$month]['count']++;
        $monthly[$month]['amount'] += $estimate['total_amount'];
    }
    $stats['monthly'] = $monthly;
    
    // 作業種別統計
    $services = ['grass_cutting' => 0, 'hedge_trimming' => 0, 'fertilizing' => 0, 'cleanup' => 0];
    foreach ($data as $estimate) {
        foreach ($services as $service => $count) {
            if ($estimate[$service] === 'あり') {
                $services[$service]++;
            }
        }
    }
    $stats['services'] = $services;
    
    // ステータス別統計
    $status_stats = [];
    foreach ($data as $estimate) {
        $status = $estimate['status'];
        if (!isset($status_stats[$status])) {
            $status_stats[$status] = 0;
        }
        $status_stats[$status]++;
    }
    $stats['status_breakdown'] = $status_stats;
    
    return $stats;
}

// 顧客別の集計データを作成
function getCustomerSummary($data) {
    $customers = [];
    
    foreach ($data as $estimate) {
        $phone = $estimate['phone'];
        if (!isset($customers[$phone])) {
            $customers[$phone] = [
                'name' => $estimate['customer_name'],
                'phone' => $estimate['phone'],
                'email' => $estimate['email'],
                'address' => $estimate['address'],
                'total_estimates' => 0,
                'total_amount' => 0,
                'last_estimate_date' => '',
                'estimates' => []
            ];
        }
        
        $customers[$phone]['total_estimates']++;
        $customers[$phone]['total_amount'] += $estimate['total_amount'];
        $customers[$phone]['estimates'][] = $estimate;
        
        if ($estimate['created_at'] > $customers[$phone]['last_estimate_date']) {
            $customers[$phone]['last_estimate_date'] = $estimate['created_at'];
        }
    }
    
    // 最新の見積もり日順でソート
    uasort($customers, function($a, $b) {
        return strtotime($b['last_estimate_date']) - strtotime($a['last_estimate_date']);
    });
    
    return $customers;
}

// データの削除機能
function deleteEstimate($estimate_id) {
    $csv_file = 'data/estimates.csv';
    if (!file_exists($csv_file)) return false;
    
    $lines = file($csv_file);
    if (empty($lines)) return false;
    
    $fp = fopen($csv_file, 'w');
    if ($fp === false) return false;
    
    fwrite($fp, $lines[0]); // ヘッダー行
    
    $deleted = false;
    for ($i = 1; $i < count($lines); $i++) {
        $data = str_getcsv($lines[$i]);
        if (isset($data[0]) && $data[0] === $estimate_id) {
            $deleted = true;
            // この行をスキップ（削除）
            continue;
        }
        fputcsv($fp, $data);
    }
    fclose($fp);
    
    // 対応する見積書ファイルも削除
    $estimate_file = "data/estimate_{$estimate_id}.html";
    if (file_exists($estimate_file)) {
        unlink($estimate_file);
    }
    
    return $deleted;
}

// データを読み込み
$estimate_data = readEstimateData();
$statistics = calculateStatistics($estimate_data);
$customer_summary = getCustomerSummary($estimate_data);
$total_estimates = count($estimate_data);

// 削除処理
if (isset($_POST['delete_estimate']) && isset($_POST['estimate_id'])) {
    if (deleteEstimate($_POST['estimate_id'])) {
        header('Location: read.php?message=deleted');
        exit;
    }
}

// 検索・フィルタ機能
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$month_filter = $_GET['month'] ?? '';

$filtered_data = $estimate_data;

if (!empty($search_term)) {
    $filtered_data = array_filter($filtered_data, function($estimate) use ($search_term) {
        return stripos($estimate['customer_name'], $search_term) !== false ||
               stripos($estimate['phone'], $search_term) !== false ||
               stripos($estimate['address'], $search_term) !== false ||
               stripos($estimate['estimate_id'], $search_term) !== false;
    });
}

if (!empty($status_filter)) {
    $filtered_data = array_filter($filtered_data, function($estimate) use ($status_filter) {
        return $estimate['status'] === $status_filter;
    });
}

if (!empty($month_filter)) {
    $filtered_data = array_filter($filtered_data, function($estimate) use ($month_filter) {
        return date('Y-m', strtotime($estimate['created_at'])) === $month_filter;
    });
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 顧客管理・見積もり履歴 - お庭見積もりナビ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', 'Yu Gothic', sans-serif;
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(45deg, #66BB6A, #43A047);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .nav {
            display: flex;
            background: #f8f9fa;
            justify-content: center;
            padding: 10px 0;
        }

        .nav a {
            padding: 15px 30px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            margin: 0 10px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav a:hover {
            background: #4CAF50;
            color: white;
            transform: translateY(-2px);
        }

        .nav a.active {
            background: #4CAF50;
            color: white;
        }

        .content {
            padding: 40px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .search-filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin: 30px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
        }

        .tabs {
            display: flex;
            margin: 30px 0 0 0;
            border-bottom: 2px solid #e9ecef;
        }

        .tab {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab.active {
            color: #4CAF50;
            border-bottom-color: #4CAF50;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .estimate-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .estimate-table th {
            background: #4CAF50;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
        }

        .estimate-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }

        .estimate-table tr:hover {
            background: #f8f9fa;
        }

        .customer-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #4CAF50;
        }

        .customer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .customer-name {
            font-size: 1.3em;
            font-weight: bold;
            color: #2E7D32;
        }

        .customer-stats {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 0.9em;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-estimate {
            background: #FFF3E0;
            color: #F57C00;
        }

        .status-confirmed {
            background: #E8F5E8;
            color: #2E7D32;
        }

        .status-completed {
            background: #E3F2FD;
            color: #1976D2;
        }

        .status-cancelled {
            background: #FFEBEE;
            color: #D32F2F;
        }

        .no-data {
            text-align: center;
            padding: 60px;
            color: #666;
        }

        .btn {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin: 5px;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            transition: transform 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-small {
            padding: 5px 12px;
            font-size: 0.8em;
        }

        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #495057);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }

        .btn-info {
            background: linear-gradient(45deg, #17a2b8, #138496);
        }

        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .chart-container {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
        }

        .chart-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        .bar-chart {
            margin: 15px 0;
        }

        .bar-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .bar-background {
            height: 25px;
            background: #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 5px;
        }

        .bar-fill {
            height: 100%;
            transition: width 0.5s ease;
        }

        @media (max-width: 768px) {
            .estimate-table {
                font-size: 0.8em;
            }
            
            .estimate-table th,
            .estimate-table td {
                padding: 8px 5px;
            }

            .customer-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .customer-stats {
                flex-direction: column;
                gap: 5px;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 顧客管理・見積もり履歴システム</h1>
            <p>お庭の見積もり依頼と顧客情報の管理</p>
        </div>

        <div class="nav">
            <a href="index.php">🧭 見積もりナビ</a>
            <a href="read.php" class="active">📊 顧客管理・履歴</a>
        </div>

        <div class="content">
            <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
                <div class="success-message">
                    <h3>✅ 削除完了</h3>
                    <p>見積もりデータを削除しました。</p>
                </div>
            <?php endif; ?>

            <?php if ($total_estimates === 0): ?>
                <div class="no-data">
                    <h2>📋 見積もり履歴がありません</h2>
                    <p>お客様からの見積もり依頼をお待ちしております</p>
                    <a href="index.php" class="btn">🌿 見積もりナビで依頼を受付</a>
                </div>
            <?php else: ?>
                <!-- 統計概要 -->
                <div class="stats-overview">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $statistics['total_estimates']; ?></div>
                        <div class="stat-label">総見積もり件数</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number">¥<?php echo number_format($statistics['total_revenue']); ?></div>
                        <div class="stat-label">総見積金額</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number">¥<?php echo number_format($statistics['average_amount']); ?></div>
                        <div class="stat-label">平均見積金額</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $statistics['this_month_count']; ?></div>
                        <div class="stat-label">今月の依頼件数</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($customer_summary); ?></div>
                        <div class="stat-label">登録顧客数</div>
                    </div>
                </div>

                <!-- 検索・フィルタ -->
                <form method="GET" class="search-filters">
                    <div class="filter-group">
                        <label for="search">🔍 顧客名・電話番号・住所で検索</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="検索キーワード">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">📋 ステータス</label>
                        <select id="status" name="status">
                            <option value="">すべて</option>
                            <option value="見積もり依頼" <?php echo $status_filter === '見積もり依頼' ? 'selected' : ''; ?>>見積もり依頼</option>
                            <option value="作業確定" <?php echo $status_filter === '作業確定' ? 'selected' : ''; ?>>作業確定</option>
                            <option value="作業完了" <?php echo $status_filter === '作業完了' ? 'selected' : ''; ?>>作業完了</option>
                            <option value="キャンセル" <?php echo $status_filter === 'キャンセル' ? 'selected' : ''; ?>>キャンセル</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="month">📅 月別</label>
                        <select id="month" name="month">
                            <option value="">すべて</option>
                            <?php
                            $months = array_keys($statistics['monthly'] ?? []);
                            rsort($months);
                            foreach ($months as $month) {
                                $selected = $month_filter === $month ? 'selected' : '';
                                echo "<option value=\"$month\" $selected>" . date('Y年n月', strtotime($month . '-01')) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn">🔍 検索</button>
                        <a href="read.php" class="btn btn-secondary">リセット</a>
                    </div>
                </form>

                <!-- タブ切り替え -->
                <div class="tabs">
                    <button class="tab active" onclick="showTab('estimates')">📋 見積もり履歴</button>
                    <button class="tab" onclick="showTab('customers')">👥 顧客一覧</button>
                    <button class="tab" onclick="showTab('analytics')">📈 分析・統計</button>
                </div>

                <!-- 見積もり履歴タブ -->
                <div id="estimates" class="tab-content active">
                    <div class="chart-container">
                        <h3 class="chart-title">📋 見積もり履歴一覧</h3>
                        
                        <?php if (empty($filtered_data)): ?>
                            <div class="no-data">
                                <p>条件に合致する見積もりが見つかりません。</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="estimate-table">
                                    <thead>
                                        <tr>
                                            <th>見積ID</th>
                                            <th>顧客名</th>
                                            <th>電話番号</th>
                                            <th>見積金額</th>
                                            <th>希望作業日</th>
                                            <th>ステータス</th>
                                            <th>依頼日時</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filtered_data as $estimate): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($estimate['estimate_id']); ?></td>
                                                <td><?php echo htmlspecialchars($estimate['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($estimate['phone']); ?></td>
                                                <td>¥<?php echo number_format($estimate['total_amount']); ?></td>
                                                <td><?php echo htmlspecialchars($estimate['preferred_date']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = 'status-estimate';
                                                    switch($estimate['status']) {
                                                        case '作業確定': $status_class = 'status-confirmed'; break;
                                                        case '作業完了': $status_class = 'status-completed'; break;
                                                        case 'キャンセル': $status_class = 'status-cancelled'; break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($estimate['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('m/d H:i', strtotime($estimate['created_at'])); ?></td>
                                                <td>
                                                    <div class="actions">
                                                        <a href="?view=<?php echo $estimate['estimate_id']; ?>" class="btn btn-small btn-info">詳細</a>
                                                        <?php if (file_exists("data/estimate_{$estimate['estimate_id']}.html")): ?>
                                                            <a href="data/estimate_<?php echo $estimate['estimate_id']; ?>.html" target="_blank" class="btn btn-small btn-secondary">見積書</a>
                                                        <?php endif; ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('本当に削除しますか？')">
                                                            <input type="hidden" name="estimate_id" value="<?php echo $estimate['estimate_id']; ?>">
                                                            <button type="submit" name="delete_estimate" class="btn btn-small btn-danger">削除</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 顧客一覧タブ -->
                <div id="customers" class="tab-content">
                    <div class="chart-container">
                        <h3 class="chart-title">👥 顧客一覧</h3>
                        
                        <?php if (empty($customer_summary)): ?>
                            <div class="no-data">
                                <p>登録顧客がありません。</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($customer_summary as $customer): ?>
                                <div class="customer-card">
                                    <div class="customer-header">
                                        <div>
                                            <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                                            <div style="color: #666; margin-top: 5px;">
                                                📞 <?php echo htmlspecialchars($customer['phone']); ?>
                                                <?php if (!empty($customer['email'])): ?>
                                                    | 📧 <?php echo htmlspecialchars($customer['email']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div style="color: #666; margin-top: 5px;">
                                                🏠 <?php echo htmlspecialchars($customer['address']); ?>
                                            </div>
                                        </div>
                                        <div class="customer-stats">
                                            <div><strong><?php echo $customer['total_estimates']; ?></strong>件の依頼</div>
                                            <div><strong>¥<?php echo number_format($customer['total_amount']); ?></strong>の総額</div>
                                            <div>最終: <?php echo date('Y/m/d', strtotime($customer['last_estimate_date'])); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 15px;">
                                        <h4 style="margin-bottom: 10px; color: #2E7D32;">見積もり履歴</h4>
                                        <?php foreach ($customer['estimates'] as $estimate): ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($estimate['estimate_id']); ?></strong>
                                                    - ¥<?php echo number_format($estimate['total_amount']); ?>
                                                    (<?php echo date('Y/m/d', strtotime($estimate['created_at'])); ?>)
                                                </div>
                                                <div>
                                                    <?php
                                                    $status_class = 'status-estimate';
                                                    switch($estimate['status']) {
                                                        case '作業確定': $status_class = 'status-confirmed'; break;
                                                        case '作業完了': $status_class = 'status-completed'; break;
                                                        case 'キャンセル': $status_class = 'status-cancelled'; break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($estimate['status']); ?></span>
                                                    <a href="?view=<?php echo $estimate['estimate_id']; ?>" class="btn btn-small btn-info">詳細</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 分析・統計タブ -->
                <div id="analytics" class="tab-content">
                    <!-- ステータス別統計 -->
                    <div class="chart-container">
                        <h3 class="chart-title">📊 ステータス別統計</h3>
                        <?php if (!empty($statistics['status_breakdown'])): ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                <?php 
                                $status_colors = [
                                    '見積もり依頼' => '#FF9800',
                                    '作業確定' => '#4CAF50',
                                    '作業完了' => '#2196F3',
                                    'キャンセル' => '#f44336'
                                ];
                                foreach ($statistics['status_breakdown'] as $status => $count): 
                                    $color = $status_colors[$status] ?? '#6c757d';
                                    $percentage = round(($count / $statistics['total_estimates']) * 100, 1);
                                ?>
                                    <div style="text-align: center; padding: 20px; background: <?php echo $color; ?>; color: white; border-radius: 10px;">
                                        <div style="font-size: 2em; font-weight: bold;"><?php echo $count; ?></div>
                                        <div><?php echo htmlspecialchars($status); ?></div>
                                        <div style="font-size: 0.9em; opacity: 0.9;">(<?php echo $percentage; ?>%)</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 人気サービス統計 -->
                    <div class="chart-container">
                        <h3 class="chart-title">🌿 人気サービス</h3>
                        <?php 
                        $service_names = [
                            'grass_cutting' => '草刈り・除草',
                            'hedge_trimming' => '生け垣剪定',
                            'fertilizing' => '施肥作業',
                            'cleanup' => '清掃・片付け'
                        ];
                        $max_service_count = max($statistics['services']);
                        if ($max_service_count > 0):
                            $colors = ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0'];
                            $color_index = 0;
                            foreach ($statistics['services'] as $service => $count):
                                if ($count > 0):
                                    $percentage = round(($count / $statistics['total_estimates']) * 100, 1);
                        ?>
                                    <div class="bar-chart">
                                        <div class="bar-item">
                                            <span><?php echo $service_names[$service]; ?></span>
                                            <span><?php echo $count; ?>件 (<?php echo $percentage; ?>%)</span>
                                        </div>
                                        <div class="bar-background">
                                            <div class="bar-fill" style="width: <?php echo ($count / $max_service_count * 100); ?>%; background: <?php echo $colors[$color_index % count($colors)]; ?>;"></div>
                                        </div>
                                    </div>
                        <?php 
                                    $color_index++;
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>

                    <!-- 月別売上推移 -->
                    <?php if (!empty($statistics['monthly'])): ?>
                        <div class="chart-container">
                            <h3 class="chart-title">📈 月別見積もり推移</h3>
                            <div style="overflow-x: auto;">
                                <table class="estimate-table">
                                    <thead>
                                        <tr>
                                            <th>月</th>
                                            <th>件数</th>
                                            <th>見積総額</th>
                                            <th>平均単価</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $monthly_data = $statistics['monthly'];
                                        krsort($monthly_data); // 新しい月から表示
                                        foreach ($monthly_data as $month => $data): 
                                            $average = $data['count'] > 0 ? $data['amount'] / $data['count'] : 0;
                                        ?>
                                            <tr>
                                                <td><?php echo date('Y年n月', strtotime($month . '-01')); ?></td>
                                                <td><?php echo $data['count']; ?>件</td>
                                                <td>¥<?php echo number_format($data['amount']); ?></td>
                                                <td>¥<?php echo number_format($average); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 見積金額分布 -->
                    <div class="chart-container">
                        <h3 class="chart-title">💰 見積金額分布</h3>
                        <?php
                        $price_ranges = [
                            '〜1万円' => 0,
                            '1〜3万円' => 0,
                            '3〜5万円' => 0,
                            '5〜10万円' => 0,
                            '10万円〜' => 0
                        ];
                        
                        foreach ($estimate_data as $estimate) {
                            $amount = $estimate['total_amount'];
                            if ($amount < 10000) {
                                $price_ranges['〜1万円']++;
                            } elseif ($amount < 30000) {
                                $price_ranges['1〜3万円']++;
                            } elseif ($amount < 50000) {
                                $price_ranges['3〜5万円']++;
                            } elseif ($amount < 100000) {
                                $price_ranges['5〜10万円']++;
                            } else {
                                $price_ranges['10万円〜']++;
                            }
                        }
                        
                        $max_range_count = max($price_ranges);
                        if ($max_range_count > 0):
                            $colors = ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#F44336'];
                            $color_index = 0;
                            foreach ($price_ranges as $range => $count):
                                if ($count > 0):
                                    $percentage = round(($count / $statistics['total_estimates']) * 100, 1);
                        ?>
                                    <div class="bar-chart">
                                        <div class="bar-item">
                                            <span><?php echo $range; ?></span>
                                            <span><?php echo $count; ?>件 (<?php echo $percentage; ?>%)</span>
                                        </div>
                                        <div class="bar-background">
                                            <div class="bar-fill" style="width: <?php echo ($count / $max_range_count * 100); ?>%; background: <?php echo $colors[$color_index % count($colors)]; ?>;"></div>
                                        </div>
                                    </div>
                        <?php 
                                    $color_index++;
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>

                <!-- 詳細表示モーダル -->
                <?php if (isset($_GET['view'])): ?>
                    <?php 
                    $view_id = $_GET['view'];
                    $selected_estimate = null;
                    foreach ($estimate_data as $estimate) {
                        if ($estimate['estimate_id'] === $view_id) {
                            $selected_estimate = $estimate;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($selected_estimate): ?>
                        <div class="chart-container">
                            <h3 class="chart-title">📋 見積もり詳細 - <?php echo htmlspecialchars($selected_estimate['estimate_id']); ?></h3>
                            
                            <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #4CAF50;">
                                <h4>👤 顧客情報</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0;">
                                    <div><strong>お名前:</strong> <?php echo htmlspecialchars($selected_estimate['customer_name']); ?></div>
                                    <div><strong>電話番号:</strong> <?php echo htmlspecialchars($selected_estimate['phone']); ?></div>
                                    <div><strong>メール:</strong> <?php echo htmlspecialchars($selected_estimate['email'] ?: '未登録'); ?></div>
                                    <div style="grid-column: 1 / -1;"><strong>住所:</strong> <?php echo htmlspecialchars($selected_estimate['address']); ?></div>
                                </div>
                            </div>

                            <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #4CAF50;">
                                <h4>🌳 作業内容</h4>
                                <div style="margin: 15px 0;">
                                    <?php if (!empty($selected_estimate['tree_details'])): ?>
                                        <div style="margin: 10px 0;"><strong>立木剪定:</strong> <?php echo htmlspecialchars($selected_estimate['tree_details']); ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($selected_estimate['pine_details'])): ?>
                                        <div style="margin: 10px 0;"><strong>マツ剪定:</strong> <?php echo htmlspecialchars($selected_estimate['pine_details']); ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($selected_estimate['maki_details'])): ?>
                                        <div style="margin: 10px 0;"><strong>マキ剪定:</strong> <?php echo htmlspecialchars($selected_estimate['maki_details']); ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if ($selected_estimate['waste_amount'] > 0): ?>
                                        <div style="margin: 10px 0; background: #fff3e0; padding: 10px; border-radius: 5px;">
                                            <strong style="color: #f57c00;">🚛 剪定ゴミ処理費:</strong> 
                                            <span style="color: #f57c00; font-weight: bold;"><?php echo number_format($selected_estimate['waste_amount']); ?>円</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin: 15px 0;">
                                        <div><strong>草刈り:</strong> <?php echo $selected_estimate['grass_cutting']; ?></div>
                                        <div><strong>生け垣剪定:</strong> <?php echo $selected_estimate['hedge_trimming']; ?></div>
                                        <div><strong>施肥:</strong> <?php echo $selected_estimate['fertilizing']; ?></div>
                                        <div><strong>清掃:</strong> <?php echo $selected_estimate['cleanup']; ?></div>
                                    </div>
                                    
                                    <?php if (!empty($selected_estimate['garden_size'])): ?>
                                        <div style="margin: 10px 0;"><strong>庭の広さ:</strong> <?php echo htmlspecialchars($selected_estimate['garden_size']); ?>㎡</div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($selected_estimate['special_requests'])): ?>
                                        <div style="margin: 15px 0;">
                                            <strong>特別要望:</strong>
                                            <p style="margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                                <?php echo nl2br(htmlspecialchars($selected_estimate['special_requests'])); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #4CAF50;">
                                <h4>📅 作業予定・料金</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0;">
                                    <div><strong>希望作業日:</strong> <?php echo htmlspecialchars($selected_estimate['preferred_date']); ?></div>
                                    <div><strong>希望時間:</strong> <?php echo htmlspecialchars($selected_estimate['preferred_time']); ?></div>
                                    <div><strong>見積金額:</strong> <span style="font-size: 1.2em; font-weight: bold; color: #2E7D32;">¥<?php echo number_format($selected_estimate['total_amount']); ?></span></div>
                                    <div><strong>ステータス:</strong> 
                                        <?php
                                        $status_class = 'status-estimate';
                                        switch($selected_estimate['status']) {
                                            case '作業確定': $status_class = 'status-confirmed'; break;
                                            case '作業完了': $status_class = 'status-completed'; break;
                                            case 'キャンセル': $status_class = 'status-cancelled'; break;
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($selected_estimate['status']); ?></span>
                                    </div>
                                    <div><strong>依頼日時:</strong> <?php echo date('Y年m月d日 H:i', strtotime($selected_estimate['created_at'])); ?></div>
                                </div>
                            </div>

                            <div style="text-align: center; margin-top: 20px;">
                                <a href="read.php" class="btn">📊 一覧に戻る</a>
                                <?php if (file_exists("data/estimate_{$selected_estimate['estimate_id']}.html")): ?>
                                    <a href="data/estimate_<?php echo $selected_estimate['estimate_id']; ?>.html" target="_blank" class="btn btn-secondary">📄 見積書を表示</a>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('本当に削除しますか？')">
                                    <input type="hidden" name="estimate_id" value="<?php echo $selected_estimate['estimate_id']; ?>">
                                    <button type="submit" name="delete_estimate" class="btn btn-danger">🗑️ 削除</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- アクションボタン -->
                <div style="text-align: center; margin-top: 40px;">
                    <a href="index.php" class="btn">🧭 新しい見積もり依頼</a>
                    <button onclick="downloadCSV()" class="btn btn-secondary">📥 データをCSVでダウンロード</button>
                    <button onclick="printReport()" class="btn btn-secondary">🖨️ レポートを印刷</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // タブ切り替え機能
        function showTab(tabName) {
            // すべてのタブコンテンツを非表示
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // すべてのタブボタンを非アクティブ
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // 指定されたタブを表示
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // CSVダウンロード機能
        function downloadCSV() {
            const link = document.createElement('a');
            link.href = 'data/estimates.csv';
            link.download = '顧客管理_見積もり履歴_' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // レポート印刷機能
        function printReport() {
            window.print();
        }

        // ページ読み込み時のアニメーション
        window.addEventListener('load', function() {
            const barFills = document.querySelectorAll('.bar-fill');
            barFills.forEach((bar, index) => {
                setTimeout(() => {
                    const originalWidth = bar.style.width;
                    bar.style.width = '0%';
                    
                    setTimeout(() => {
                        bar.style.width = originalWidth;
                    }, 100);
                }, index * 100);
            });
        });

        // 自動保存・リフレッシュ機能
        let lastUpdateTime = <?php echo time(); ?>;
        
        function checkForUpdates() {
            // 実際のシステムでは、AJAXで新しいデータをチェック
            const currentTime = Math.floor(Date.now() / 1000);
            if (currentTime - lastUpdateTime > 300) { // 5分経過
                console.log('データの更新をチェック中...');
            }
        }

        // 5分ごとに更新チェック
        setInterval(checkForUpdates, 300000);
    </script>
</body>
</html>