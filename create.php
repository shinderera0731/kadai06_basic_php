<?php
// セッション開始
session_start();

// POSTデータが送信されているかチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 必須項目のチェック
$required_fields = ['customer_name', 'phone', 'address', 'preferred_date', 'total_amount'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    $_SESSION['error_message'] = '必須項目が入力されていません。';
    header('Location: index.php');
    exit;
}

// データの取得と整形
$data = [
    'estimate_id' => 'EST' . date('Ymd') . sprintf('%04d', rand(1, 9999)),
    'customer_name' => trim($_POST['customer_name']),
    'phone' => trim($_POST['phone']),
    'email' => trim($_POST['email'] ?? ''),
    'address' => trim($_POST['address']),
    'tree_details' => trim($_POST['tree_details'] ?? ''),
    'pine_details' => trim($_POST['pine_details'] ?? ''),
    'maki_details' => trim($_POST['maki_details'] ?? ''),
    'waste_amount' => intval($_POST['waste_amount'] ?? 0),
    'grass_cutting' => isset($_POST['grass_cutting']) ? 'あり' : 'なし',
    'hedge_trimming' => isset($_POST['hedge_trimming']) ? 'あり' : 'なし',
    'fertilizing' => isset($_POST['fertilizing']) ? 'あり' : 'なし',
    'cleanup' => isset($_POST['cleanup']) ? 'あり' : 'なし',
    'garden_size' => trim($_POST['garden_size'] ?? ''),
    'special_requests' => trim($_POST['special_requests'] ?? ''),
    'preferred_date' => trim($_POST['preferred_date']),
    'preferred_time' => trim($_POST['preferred_time'] ?? ''),
    'total_amount' => intval($_POST['total_amount']),
    'status' => '見積もり依頼',
    'created_at' => date('Y-m-d H:i:s')
];

// データの検証（XSS対策）
foreach ($data as $key => $value) {
    if (!in_array($key, ['total_amount', 'waste_amount'])) {
        $data[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

// CSVファイルのパス
$csv_file = 'data/estimates.csv';
$data_dir = 'data';

// dataディレクトリが存在しない場合は作成
if (!is_dir($data_dir)) {
    if (!mkdir($data_dir, 0755, true)) {
        $_SESSION['error_message'] = 'データ保存用のディレクトリを作成できませんでした。';
        header('Location: index.php');
        exit;
    }
}

// CSVファイルが存在しない場合はヘッダー行を作成
if (!file_exists($csv_file)) {
    $headers = [
        '見積もりID', 'お客様名', '電話番号', 'メールアドレス', '住所',
        '立木詳細', 'マツ詳細', 'マキ詳細', '剪定ゴミ処理費', '草刈り', '生け垣剪定', '施肥', '清掃',
        '庭の広さ(㎡)', '特別要望', '希望作業日', '希望時間',
        '見積もり金額', 'ステータス', '作成日時'
    ];
    
    $fp = fopen($csv_file, 'w');
    if ($fp === false) {
        $_SESSION['error_message'] = 'CSVファイルを作成できませんでした。';
        header('Location: index.php');
        exit;
    }
    
    // UTF-8 BOMを追加
    fwrite($fp, "\xEF\xBB\xBF");
    fputcsv($fp, $headers);
    fclose($fp);
}

// CSVファイルにデータを追記
$fp = fopen($csv_file, 'a');
if ($fp === false) {
    $_SESSION['error_message'] = 'CSVファイルを開けませんでした。';
    header('Location: index.php');
    exit;
}

// データを配列に変換
$csv_data = [
    $data['estimate_id'],
    $data['customer_name'],
    $data['phone'],
    $data['email'],
    $data['address'],
    $data['tree_details'],
    $data['pine_details'],
    $data['maki_details'],
    $data['waste_amount'],
    $data['grass_cutting'],
    $data['hedge_trimming'],
    $data['fertilizing'],
    $data['cleanup'],
    $data['garden_size'],
    $data['special_requests'],
    $data['preferred_date'],
    $data['preferred_time'],
    $data['total_amount'],
    $data['status'],
    $data['created_at']
];

if (fputcsv($fp, $csv_data) === false) {
    $_SESSION['error_message'] = 'データの保存に失敗しました。';
    fclose($fp);
    header('Location: index.php');
    exit;
}

fclose($fp);

// 見積書HTMLを生成
$estimate_html = generateEstimateHTML($data);
$estimate_file = "data/estimate_{$data['estimate_id']}.html";
if (file_put_contents($estimate_file, $estimate_html) === false) {
    // 見積書の生成に失敗してもエラーにはしない（CSVデータは保存済み）
    error_log("見積書HTMLの生成に失敗: {$estimate_file}");
}

// 成功メッセージをセッションに保存
$_SESSION['success_message'] = "庭師さんへのナビゲートが完了しました！見積もりID: {$data['estimate_id']} / 概算金額: " . number_format($data['total_amount']) . "円（※最適な庭師さんをご案内いたします）";

// ログファイルに記録
$log_file = 'data/estimates.log';
$log_entry = date('Y-m-d H:i:s') . " - 見積もり依頼: {$data['estimate_id']} - {$data['customer_name']} - " . number_format($data['total_amount']) . "円\n";
file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

// index.phpにリダイレクト
header('Location: index.php');
exit;

// 見積書HTML生成関数
function generateEstimateHTML($data) {
    $html = '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>見積書 - ' . $data['estimate_id'] . '</title>
    <style>
        body { 
            font-family: "MS Pゴシック", "Yu Gothic", sans-serif; 
            margin: 40px; 
            line-height: 1.6;
            color: #333;
        }
        .header { 
            text-align: center; 
            border-bottom: 3px solid #4CAF50; 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
        }
        .header h1 {
            color: #2E7D32;
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        .company-info { 
            text-align: right; 
            margin-bottom: 30px; 
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        .customer-info { 
            margin-bottom: 30px; 
            background: white;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
        }
        .estimate-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .estimate-table th, .estimate-table td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        .estimate-table th { 
            background-color: #4CAF50; 
            color: white;
            font-weight: bold;
        }
        .estimate-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .total-row { 
            background-color: #e8f5e8 !important; 
            font-weight: bold; 
            font-size: 1.2em; 
        }
        .notes { 
            margin-top: 30px; 
            background: #fff3e0;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #FF9800;
        }
        .important-notice {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #2196F3;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            color: #666;
        }
        @media print { 
            body { margin: 20px; } 
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧭 お庭見積もりナビ 見積書 🧭</h1>
        <p style="font-size: 1.1em; color: #666;">見積もりID: ' . $data['estimate_id'] . '</p>
        <p style="color: #666;">発行日: ' . date('Y年n月j日') . '</p>
    </div>
    
    <div class="company-info">
        <h3 style="color: #2E7D32; margin-bottom: 15px;">🌿 みどりの庭師</h3>
        <p>〒000-0000 東京都〇〇区〇〇町1-2-3</p>
        <p>TEL: 03-1234-5678 / FAX: 03-1234-5679</p>
        <p>Email: info@midori-gardener.jp</p>
        <p>営業時間: 9:00-18:00（日祝除く）</p>
    </div>
    
    <div class="customer-info">
        <h3 style="color: #2E7D32; margin-bottom: 15px;">👤 お客様情報</h3>
        <p><strong>お名前:</strong> ' . $data['customer_name'] . '</p>
        <p><strong>住所:</strong> ' . $data['address'] . '</p>
        <p><strong>電話番号:</strong> ' . $data['phone'] . '</p>
        ' . (!empty($data['email']) ? '<p><strong>メール:</strong> ' . $data['email'] . '</p>' : '') . '
    </div>
    
    <table class="estimate-table">
        <thead>
            <tr>
                <th style="width: 40%;">作業内容</th>
                <th style="width: 40%;">詳細</th>
                <th style="width: 20%;">金額</th>
            </tr>
        </thead>
        <tbody>';
    
    // 立木剪定の詳細
    if (!empty($data['tree_details'])) {
        $html .= '<tr><td>🌲 立木剪定（一般樹木）</td><td>' . $data['tree_details'] . '</td><td style="text-align: right;">詳細は現地調査後</td></tr>';
    }
    
    // マツ剪定の詳細
    if (!empty($data['pine_details'])) {
        $html .= '<tr><td>🌲 マツ剪定（特別料金）</td><td>' . $data['pine_details'] . '</td><td style="text-align: right;">詳細は現地調査後</td></tr>';
    }
    
    // マキ剪定の詳細
    if (!empty($data['maki_details'])) {
        $html .= '<tr><td>🌲 マキ剪定（特別料金）</td><td>' . $data['maki_details'] . '</td><td style="text-align: right;">詳細は現地調査後</td></tr>';
    }
    
    // 剪定ゴミ処理費
    if ($data['waste_amount'] > 0) {
        $html .= '<tr><td>🚛 剪定ゴミ運搬処理費</td><td>運搬・環境配慮処理</td><td style="text-align: right;">' . number_format($data['waste_amount']) . '円</td></tr>';
    }
    
    // その他作業
    $services = [
        'grass_cutting' => ['🌱 草刈り・除草', 5000],
        'hedge_trimming' => ['🌿 生け垣剪定', 3000],
        'fertilizing' => ['🌰 施肥作業', 2000],
        'cleanup' => ['🧹 清掃・片付け', 1500]
    ];
    
    foreach ($services as $key => $service) {
        if ($data[$key] === 'あり') {
            $html .= '<tr><td>' . $service[0] . '</td><td>一式</td><td style="text-align: right;">' . number_format($service[1]) . '円</td></tr>';
        }
    }
    
    $html .= '<tr class="total-row">
                <td colspan="2"><strong>🧭 概算見積金額（税込）</strong></td>
                <td style="text-align: right;"><strong>' . number_format($data['total_amount']) . '円</strong></td>
            </tr>
        </tbody>
    </table>
    
    <div class="notes">
        <h3 style="color: #F57C00; margin-bottom: 15px;">📅 作業予定</h3>
        <p><strong>希望作業日:</strong> ' . $data['preferred_date'] . '</p>
        <p><strong>希望時間:</strong> ' . $data['preferred_time'] . '</p>
        ' . (!empty($data['garden_size']) ? '<p><strong>庭の広さ:</strong> ' . $data['garden_size'] . '平方メートル</p>' : '') . '
        ' . (!empty($data['special_requests']) ? '<p><strong>特別要望:</strong><br>' . nl2br($data['special_requests']) . '</p>' : '') . '
    </div>
    
    <div class="important-notice">
        <h3 style="color: #1976D2; margin-bottom: 15px;">🧭 見積もりナビについて</h3>
        <ul style="margin: 0; padding-left: 20px;">
            <li><strong>この金額は概算の目安です</strong></li>
            <li>最適な庭師さんをご案内いたします</li>
            <li>無料現地調査で正確なお見積もりをいたします</li>
            <li>現場の状況により料金が変動する場合があります</li>
            <li>複数の庭師さんからお選びいただけます</li>
            <li>お見積もり後のキャンセルも可能です（調査費無料）</li>
            <li>天候により作業日程が変更になる場合があります</li>
            <li>この見積もりは30日間有効です</li>
        </ul>
    </div>
    
    <div class="footer">
        <p style="font-size: 1.1em; color: #2E7D32; margin-bottom: 10px;">🌿 お庭のことなら、みどりの庭師にお任せください 🌿</p>
        <p>見積もり作成日: ' . date('Y年n月j日') . '</p>
        <p>みどりの庭師 代表: 田中一郎</p>
        <p style="margin-top: 20px; font-size: 0.9em; color: #999;">
            ※このページは印刷してご利用いただけます
        </p>
    </div>
</body>
</html>';
    
    return $html;
}
?>