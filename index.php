<?php
// セッション開始
session_start();

// 成功メッセージがあるかチェック
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// エラーメッセージがあるかチェック
$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌿 お庭見積もりナビ 🌿</title>
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
            max-width: 900px;
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

        .form-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
            border-left: 5px solid #4CAF50;
        }

        .form-section h3 {
            color: #2E7D32;
            margin-bottom: 20px;
            font-size: 1.3em;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
            font-size: 1.1em;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
        }

        .btn {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
            margin: 10px 5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-calc {
            background: linear-gradient(45deg, #FF9800, #F57C00);
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }

        .price-calculator {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px solid #4CAF50;
        }

        .price-display {
            font-size: 1.5em;
            font-weight: bold;
            color: #2E7D32;
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }

        .required {
            color: #f44336;
        }

        .emoji {
            font-size: 1.3em;
            margin-right: 8px;
        }

        .tree-count-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .tree-height-input, .pine-height-input, .maki-height-input {
            width: 80px;
        }

        .tree-count-input, .pine-count-input, .maki-count-input {
            width: 60px;
        }

        .add-tree-btn {
            background: #2196F3;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .remove-tree-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8em;
        }

        .tree-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .tree-count-group {
                flex-wrap: wrap;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧭 お庭見積もりナビ 🧭</h1>
            <p>最適な庭師さんへご案内します</p>
        </div>

        <div class="nav">
            <a href="index.php" class="active">🧭 見積もりナビ</a>
            <a href="read.php">📊 顧客管理・履歴</a>
        </div>

        <div class="content">
            <?php if ($success_message): ?>
                <div class="success-message">
                    <h3>🧭 ナビゲート完了！</h3>
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <h3>❌ エラー</h3>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <form action="create.php" method="POST" id="estimateForm">
                <!-- お客様情報 -->
                <div class="form-section">
                    <h3>👤 お客様情報</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="customer_name">
                                <span class="emoji">📝</span>お名前 <span class="required">*</span>
                            </label>
                            <input type="text" id="customer_name" name="customer_name" required placeholder="山田太郎">
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <span class="emoji">📞</span>電話番号 <span class="required">*</span>
                            </label>
                            <input type="tel" id="phone" name="phone" required placeholder="090-1234-5678">
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <span class="emoji">📧</span>メールアドレス
                            </label>
                            <input type="email" id="email" name="email" placeholder="example@example.com">
                        </div>

                        <div class="form-group">
                            <label for="address">
                                <span class="emoji">🏠</span>住所 <span class="required">*</span>
                            </label>
                            <input type="text" id="address" name="address" required placeholder="東京都〇〇区〇〇町1-2-3">
                        </div>
                    </div>
                </div>

                <!-- 作業内容 -->
                <div class="form-section">
                    <h3>🌳 樹木剪定作業</h3>
                    
                    <!-- 立木剪定 -->
                    <div class="price-calculator">
                        <h4>🌲 立木剪定（一般樹木）</h4>
                        <p>• 1メートル: 2,000円</p>
                        <p>• 2メートル: 4,000円</p>
                        <p>• 3メートル以上: 高さ × 2,000円</p>
                        
                        <div id="tree-container">
                            <div class="tree-count-group">
                                <label>高さ(m):</label>
                                <input type="number" class="tree-height-input" min="0.5" max="20" step="0.5" value="1.0" onchange="calculatePrice()">
                                <label>本数:</label>
                                <input type="number" class="tree-count-input" min="0" max="50" value="0" onchange="calculatePrice()">
                                <span>本</span>
                                <button type="button" class="remove-tree-btn" onclick="removeTree(this)" style="display: none;">削除</button>
                            </div>
                        </div>
                        
                        <button type="button" class="add-tree-btn" onclick="addTree()">+ 立木を追加</button>
                        
                        <div class="price-display" id="tree-total">
                            立木剪定料金: 0円
                        </div>
                    </div>

                    <!-- マツ剪定 -->
                    <div class="price-calculator">
                        <h4>🌲 マツ剪定（特別料金）</h4>
                        <p>• 1メートル: 8,000円</p>
                        <p>• 2メートル: 16,000円</p>
                        <p>• 3メートル以上: 高さ × 8,000円</p>
                        
                        <div id="pine-container">
                            <div class="tree-count-group">
                                <label>高さ(m):</label>
                                <input type="number" class="pine-height-input" min="0.5" max="20" step="0.5" value="1.0" onchange="calculatePrice()">
                                <label>本数:</label>
                                <input type="number" class="pine-count-input" min="0" max="50" value="0" onchange="calculatePrice()">
                                <span>本</span>
                                <button type="button" class="remove-tree-btn" onclick="removePine(this)" style="display: none;">削除</button>
                            </div>
                        </div>
                        
                        <button type="button" class="add-tree-btn" onclick="addPine()">+ マツを追加</button>
                        
                        <div class="price-display" id="pine-total">
                            マツ剪定料金: 0円
                        </div>
                    </div>

                    <!-- マキ剪定 -->
                    <div class="price-calculator">
                        <h4>🌲 マキ剪定（特別料金）</h4>
                        <p>• 1メートル: 4,000円</p>
                        <p>• 2メートル: 8,000円</p>
                        <p>• 3メートル以上: 高さ × 4,000円</p>
                        
                        <div id="maki-container">
                            <div class="tree-count-group">
                                <label>高さ(m):</label>
                                <input type="number" class="maki-height-input" min="0.5" max="20" step="0.5" value="1.0" onchange="calculatePrice()">
                                <label>本数:</label>
                                <input type="number" class="maki-count-input" min="0" max="50" value="0" onchange="calculatePrice()">
                                <span>本</span>
                                <button type="button" class="remove-tree-btn" onclick="removeMaki(this)" style="display: none;">削除</button>
                            </div>
                        </div>
                        
                        <button type="button" class="add-tree-btn" onclick="addMaki()">+ マキを追加</button>
                        
                        <div class="price-display" id="maki-total">
                            マキ剪定料金: 0円
                        </div>
                    </div>

                    <!-- 剪定ゴミ運搬処理費 -->
                    <div class="price-calculator" style="background: #fff3e0; border: 2px solid #ff9800;">
                        <h4>🚛 剪定ゴミ運搬処理費</h4>
                        <p>• 剪定で発生したゴミの運搬・処理費用</p>
                        <p>• 環境に配慮した適切な処理を行います</p>
                        
                        <div class="price-display" id="waste-total" style="background: #fff3e0; color: #f57c00;">
                            剪定ゴミ処理費: 0円
                        </div>
                    </div>
                </div>

                <!-- その他の作業 -->
                <div class="form-section">
                    <h3>🌸 その他の作業</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="grass_cutting" value="5000" onchange="calculateTotalPrice()"> 
                                <span class="emoji">🌱</span>草刈り・除草 (5,000円)
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="hedge_trimming" value="3000" onchange="calculateTotalPrice()"> 
                                <span class="emoji">🌿</span>生け垣剪定 (3,000円)
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="fertilizing" value="2000" onchange="calculateTotalPrice()"> 
                                <span class="emoji">🌰</span>施肥作業 (2,000円)
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="cleanup" value="1500" onchange="calculateTotalPrice()"> 
                                <span class="emoji">🧹</span>清掃・片付け (1,500円)
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="garden_size">
                            <span class="emoji">📏</span>お庭の広さ（平方メートル）
                        </label>
                        <input type="number" id="garden_size" name="garden_size" min="1" placeholder="例: 50" onchange="calculateTotalPrice()">
                    </div>

                    <div class="form-group">
                        <label for="special_requests">
                            <span class="emoji">💭</span>特別な要望・注意事項
                        </label>
                        <textarea id="special_requests" name="special_requests" rows="4" placeholder="例: 高所作業あり、駐車場なし、ペットがいます など"></textarea>
                    </div>
                </div>

                <!-- 見積もり日時 -->
                <div class="form-section">
                    <h3>📅 希望作業日時</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="preferred_date">
                                <span class="emoji">📆</span>希望作業日 <span class="required">*</span>
                            </label>
                            <input type="date" id="preferred_date" name="preferred_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>

                        <div class="form-group">
                            <label for="preferred_time">
                                <span class="emoji">⏰</span>希望時間帯
                            </label>
                            <select id="preferred_time" name="preferred_time">
                                <option value="午前中">午前中 (9:00-12:00)</option>
                                <option value="午後">午後 (13:00-17:00)</option>
                                <option value="相談">要相談</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 概算料金表示 -->
                <div class="price-calculator">
                    <h3>💰 見積もりナビ結果</h3>
                    <p style="font-size: 0.9em; color: #666; margin-bottom: 15px;">
                        ※ 以下は概算の目安です。正確な料金は現地調査後にお見積もりいたします
                    </p>
                    <div class="price-display" id="total-price">
                        概算料金: 0円（税込）
                    </div>
                    <input type="hidden" id="total_amount" name="total_amount" value="0">
                    <input type="hidden" id="tree_details" name="tree_details" value="">
                    <input type="hidden" id="pine_details" name="pine_details" value="">
                    <input type="hidden" id="maki_details" name="maki_details" value="">
                    <input type="hidden" id="waste_amount" name="waste_amount" value="0">
                    
                    <!-- 重要な注意書き -->
                    <div style="background: #e8f4fd; border: 2px solid #2196F3; border-radius: 10px; padding: 20px; margin: 20px 0;">
                        <h4 style="color: #1565C0; margin-bottom: 15px;">🧭 ナビゲートについて</h4>
                        <ul style="color: #1565C0; font-size: 0.95em; line-height: 1.6;">
                            <li><strong>無料現地調査</strong>で最適な庭師さんをご案内いたします</li>
                            <li>上記の金額は概算の目安であり、確定金額ではありません</li>
                            <li>現地の状況、アクセス、作業環境により料金が変動いたします</li>
                            <li>お見積もり後のキャンセルも可能です（調査費無料）</li>
                            <li>複数の庭師さんからお選びいただけます</li>
                        </ul>
                        <p style="color: #1565C0; font-weight: bold; margin-top: 15px; text-align: center; font-size: 1.1em;">
                            🧭 最適な庭師さんへナビゲートします！
                        </p>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="button" class="btn btn-calc" onclick="calculateTotalPrice()">💰 料金を再計算</button>
                    <button type="submit" class="btn">🧭 庭師さんへナビゲート！</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let treeCount = 1;
        let pineCount = 1;
        let makiCount = 1;

        // 立木の追加・削除
        function addTree() {
            const container = document.getElementById('tree-container');
            const newTree = document.createElement('div');
            newTree.className = 'tree-count-group';
            newTree.innerHTML = `
                <label>高さ(m):</label>
                <input type="number" class="tree-height-input" min="0.5" max="20" step="0.5" value="1.0" onchange="calculatePrice()">
                <label>本数:</label>
                <input type="number" class="tree-count-input" min="0" max="50" value="0" onchange="calculatePrice()">
                <span>本</span>
                <button type="button" class="remove-tree-btn" onclick="removeTree(this)">削除</button>
            `;
            container.appendChild(newTree);
            treeCount++;
            calculatePrice();
        }

        function removeTree(button) {
            if (treeCount > 1) {
                button.parentElement.remove();
                treeCount--;
                calculatePrice();
            }
        }

        // マツの追加・削除
        function addPine() {
            const container = document.getElementById('pine-container');
            const newPine = document.createElement('div');
            newPine.className = 'tree-count-group';
            newPine.innerHTML = `
                <label>高さ(m):</label>
                <input type="number" class="pine-height-input" min="0.5" max="20" step="0.5" value="1.0" onchange="calculatePrice()">
                <label>本数:</label>
                <input type="number" class="pine-count-input" min="0" max="50" value="0" onchange="calculatePrice()">
                <span>本</span>
                <button type="button" class="remove-tree-btn" onclick="removePine(this)">削除</button>
            `;
            container.appendChild(newPine);
            pineCount++;
            calculatePrice();
        }

        function removePine(button) {
            if (pineCount > 1) {
                button.parentElement.remove();
                pineCount--;
                calculatePrice();
            }
        }

        // マキの追加・削除
        function addMaki() {
            const container = document.getElementById('maki-container');
            const newMaki = document.createElement('div');
            newMaki.className = 'tree-count-group';
            newMaki.innerHTML = `
                <label>高さ(m):</label>
                <input type="number" class="maki-height-input" min="0.5" max="20" step="0.5" value="1.0" onchange="calculatePrice()">
                <label>本数:</label>
                <input type="number" class="maki-count-input" min="0" max="50" value="0" onchange="calculatePrice()">
                <span>本</span>
                <button type="button" class="remove-tree-btn" onclick="removeMaki(this)">削除</button>
            `;
            container.appendChild(newMaki);
            makiCount++;
            calculatePrice();
        }

        function removeMaki(button) {
            if (makiCount > 1) {
                button.parentElement.remove();
                makiCount--;
                calculatePrice();
            }
        }

        // 立木料金計算
        function calculateTreePrice() {
            const heights = document.querySelectorAll('.tree-height-input');
            const counts = document.querySelectorAll('.tree-count-input');
            let total = 0;
            let details = [];

            for (let i = 0; i < heights.length; i++) {
                const height = parseFloat(heights[i].value) || 0;
                const count = parseInt(counts[i].value) || 0;
                if (height > 0 && count > 0) {
                    const pricePerTree = Math.ceil(height) * 2000; // 1メートル2000円
                    const totalPrice = pricePerTree * count;
                    total += totalPrice;
                    details.push(`${height}m×${count}本: ${totalPrice.toLocaleString()}円`);
                }
            }

            document.getElementById('tree-total').textContent = 
                `立木剪定料金: ${total.toLocaleString()}円`;
            document.getElementById('tree_details').value = details.join(', ');
            return total;
        }

        // マツ料金計算
        function calculatePinePrice() {
            const heights = document.querySelectorAll('.pine-height-input');
            const counts = document.querySelectorAll('.pine-count-input');
            let total = 0;
            let details = [];

            for (let i = 0; i < heights.length; i++) {
                const height = parseFloat(heights[i].value) || 0;
                const count = parseInt(counts[i].value) || 0;
                if (height > 0 && count > 0) {
                    const pricePerTree = Math.ceil(height) * 8000; // 1メートル8000円
                    const totalPrice = pricePerTree * count;
                    total += totalPrice;
                    details.push(`${height}m×${count}本: ${totalPrice.toLocaleString()}円`);
                }
            }

            document.getElementById('pine-total').textContent = 
                `マツ剪定料金: ${total.toLocaleString()}円`;
            document.getElementById('pine_details').value = details.join(', ');
            return total;
        }

        // マキ料金計算
        function calculateMakiPrice() {
            const heights = document.querySelectorAll('.maki-height-input');
            const counts = document.querySelectorAll('.maki-count-input');
            let total = 0;
            let details = [];

            for (let i = 0; i < heights.length; i++) {
                const height = parseFloat(heights[i].value) || 0;
                const count = parseInt(counts[i].value) || 0;
                if (height > 0 && count > 0) {
                    const pricePerTree = Math.ceil(height) * 4000; // 1メートル4000円
                    const totalPrice = pricePerTree * count;
                    total += totalPrice;
                    details.push(`${height}m×${count}本: ${totalPrice.toLocaleString()}円`);
                }
            }

            document.getElementById('maki-total').textContent = 
                `マキ剪定料金: ${total.toLocaleString()}円`;
            document.getElementById('maki_details').value = details.join(', ');
            return total;
        }

        // 全体料金計算
        function calculatePrice() {
            const treeTotal = calculateTreePrice();
            const pineTotal = calculatePinePrice();
            const makiTotal = calculateMakiPrice();
            
            // 剪定ゴミ処理費を計算（剪定料金の1/3）
            const pruningTotal = treeTotal + pineTotal + makiTotal;
            const wasteTotal = Math.round(pruningTotal / 3);
            
            document.getElementById('waste-total').textContent = 
                `剪定ゴミ処理費: ${wasteTotal.toLocaleString()}円`;
            document.getElementById('waste_amount').value = wasteTotal;
            
            calculateTotalPrice();
        }

        function calculateTotalPrice() {
            // 各樹木料金
            const treeTotal = parseInt(document.getElementById('tree-total').textContent.match(/\d+/g)?.join('') || '0');
            const pineTotal = parseInt(document.getElementById('pine-total').textContent.match(/\d+/g)?.join('') || '0');
            const makiTotal = parseInt(document.getElementById('maki-total').textContent.match(/\d+/g)?.join('') || '0');
            
            // 剪定ゴミ処理費
            const wasteTotal = parseInt(document.getElementById('waste_amount').value) || 0;
            
            // その他作業料金
            let otherTotal = 0;
            document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
                otherTotal += parseInt(checkbox.value);
            });

            // 庭の広さによる追加料金
            const gardenSize = parseInt(document.getElementById('garden_size').value) || 0;
            let sizeMultiplier = 1;
            if (gardenSize >= 100) {
                sizeMultiplier = 1.2; // 20%増し
            } else if (gardenSize >= 50) {
                sizeMultiplier = 1.1; // 10%増し
            }

            const subtotal = (treeTotal + pineTotal + makiTotal + wasteTotal + otherTotal) * sizeMultiplier;
            const total = Math.round(subtotal);

            document.getElementById('total-price').textContent = 
                `概算料金: ${total.toLocaleString()}円（税込）`;
            document.getElementById('total_amount').value = total;
        }

        // ページ読み込み時に計算
        window.onload = function() {
            calculatePrice();
            
            // 明日以降の日付のみ選択可能にする
            const today = new Date();
            today.setDate(today.getDate() + 1);
            document.getElementById('preferred_date').min = today.toISOString().split('T')[0];
        };

        // フォーム送信時の確認
        document.getElementById('estimateForm').addEventListener('submit', function(e) {
            const total = document.getElementById('total_amount').value;
            if (!confirm(`概算料金 ${parseInt(total).toLocaleString()}円で庭師さんへナビゲートしますか？\n※最適な庭師さんをご案内いたします`)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>