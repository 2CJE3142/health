<?php
session_start();

// エラーメッセージを表示に設定
ini_set('display_errors', E_ALL);
error_reporting(1);

// MySQLに接続
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'mydb';

$conn = new mysqli($host, $user, $password, $database);

// 接続エラーの場合
if ($conn->connect_error) {
    echo json_encode(['error' => 'データベース接続エラー: ' . $conn->connect_error]);
    exit;
}

$username = $_SESSION['username'];  // セッションから username を取得

// username から user_id を取得
$sql = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username); 
$stmt->execute();
$result = $stmt->get_result();

// ユーザーが見つからない場合
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'ユーザー情報が見つかりません']);
    exit;
}

$user = $result->fetch_assoc();
$user_id = $user['id'];  // user_id を取得

// SQLクエリを実行（ログインしているユーザーのデータだけを取得）
$sql = "SELECT * FROM health_data WHERE user_id = ? ORDER BY date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);  // プレースホルダーに user_id をバインド
$stmt->execute();
$result = $stmt->get_result();

// SQLクエリの実行に失敗した場合
if (!$result) {
    echo json_encode(['error' => 'SQLクエリエラー: ' . $conn->error]);
    exit;
}

// データ取得とJSON化
$health_data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // フィールド名をフロントエンドのコードと一致させる
        $health_data[] = [
            'date' => $row['date'],
            'steps' => $row['steps'],
            'weight' => $row['weight'],
            'fat' => $row['fat']
        ];
    }
} else {
    // データがない場合
    $health_data = ['error' => 'データがありません'];
}

// 接続終了
$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>健康データ表示</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h1>健康データ一覧</h1>
        <div class="error-message" id="error-message"></div>

        <div class="chart-container">
            <canvas id="stepsChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="weightChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="fatChart"></canvas>
        </div>
    </div>

    <script>
        // PHPから取得したデータをJavaScriptに渡す
        const healthData = <?php echo json_encode($health_data); ?>;

        if (healthData.error) {
            document.getElementById('error-message').innerText = healthData.error;
            document.getElementById('data-container').innerHTML = '';
        } else {
            let labels = [];
            let stepsData = [];
            let weightData = [];
            let fatData = [];
            let lastWeight = null;
            let lastFat = null;
            let lastSteps = null;

            // 日付を取得してグラフに反映
            for (let i = 6; i >= 0; i--) {
                let date = new Date();
                date.setDate(date.getDate() - i);
                let dateStr = formatDate(date);
                labels.push(dateStr);

                let entry = healthData.find(row => row.date === dateStr);
                if (entry) {
                    stepsData.push(entry.steps);
                    weightData.push(entry.weight);
                    fatData.push(entry.fat);
                    lastWeight = entry.weight;
                    lastFat = entry.fat;
                    lastSteps = entry.steps;
                } else {
                    stepsData.push(lastSteps);
                    weightData.push(lastWeight);
                    fatData.push(lastFat);
                }
            }

            function formatDate(date) {
                const yyyy = date.getFullYear();
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }


            // グラフを作成
            createChart('stepsChart', '歩数', labels, stepsData, 'rgba(54, 162, 235, 0.6)');
            createChart('weightChart', '体重 (kg)', labels, weightData, 'rgba(255, 99, 132, 0.6)');
            createChart('fatChart', '体脂肪率 (%)', labels, fatData, 'rgba(75, 192, 192, 0.6)');
        }

        function createChart(canvasId, label, labels, data, color) {
            let ctx = document.getElementById(canvasId).getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        borderColor: color,
                        backgroundColor: color.replace('0.6', '0.2'),
                        fill: true,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 1
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
