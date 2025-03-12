<?php
session_start();

$servername = "localhost";
$username = "test_user";  // MySQLのユーザー名
$password = "password";  // MySQLのパスワード
$dbname = "health";  // データベース名

// MySQLに接続
$conn = new mysqli($servername, $username, $password, $dbname);

// 接続チェック
if ($conn->connect_error) {
    die("データベース接続に失敗しました: " . $conn->connect_error);
}

// ログインしているユーザー名をセッションから取得
$logged_in_username = $_SESSION['name'];

// ユーザー名からユーザーIDを取得
$sql_user_id = "SELECT id FROM users WHERE name = ?";
$stmt_user_id = $conn->prepare($sql_user_id);
$stmt_user_id->bind_param("s", $logged_in_username);
$stmt_user_id->execute();
$result_user_id = $stmt_user_id->get_result();

if ($result_user_id->num_rows > 0) {
    $user_id_row = $result_user_id->fetch_assoc();
    $user_id = $user_id_row['id'];  // ユーザーIDを取得
} else {
    die("ユーザーIDが見つかりません");
}

// ユーザーIDに基づいて最新のデータをhealth_dataテーブルから取得
$sql_health_data = "SELECT * FROM health_data WHERE user_id = ? ORDER BY date DESC LIMIT 1";
$stmt_health_data = $conn->prepare($sql_health_data);
$stmt_health_data->bind_param("i", $user_id);  // "i" は整数型
$stmt_health_data->execute();
$result_health_data = $stmt_health_data->get_result();

$health_data = [];
if ($result_health_data->num_rows > 0) {
    $health_data = $result_health_data->fetch_assoc();  // 今日のデータを取得
}

// 一時的に画像を保存する処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $upload_dir = sys_get_temp_dir();  // システムの一時ディレクトリ
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif']; // 許可する拡張子
    $meal_images = ['breakfast', 'lunch', 'dinner'];  // 食事の画像

    foreach ($meal_images as $meal) {
        if (isset($_FILES[$meal])) {
            $file = $_FILES[$meal];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_error = $file['error'];

            // エラーチェック
            if ($file_error === 0) {
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                // 拡張子が許可されているかチェック
                if (in_array($file_ext, $allowed_extensions)) {
                    $new_file_name = uniqid($meal . '_', true) . '.' . $file_ext;  // 新しいファイル名
                    $file_path = $upload_dir . DIRECTORY_SEPARATOR . $new_file_name;
                    
                    // 画像を一時ディレクトリに移動
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        // 画像パスをセッションに保存
                        $_SESSION[$meal . '_image'] = $file_path;
                    }
                } else {
                    echo "無効なファイルタイプです。";
                }
            } else {
                echo "ファイルアップロードにエラーが発生しました。";
            }
        }
    }
}

// 接続を閉じる
$stmt_user_id->close();
$stmt_health_data->close();
$conn->close();
?>



<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Data Input Page</title>
</head>
<body>
    <div class="input-container">
        <h1>データ入力</h1>
        <form action="index3.php" method="post" enctype="multipart/form-data">
            <label>日付:</label>
            <p id="date"><?php echo date('Y-m-d'); ?></p>

            <label>身長(cm):</label>
            <p id="height">取得中...</p>

            <label>体重(kg):</label>
            <p id="weight">取得中...</p>

            <label>体脂肪率(%):</label>
            <p id="body-fat">取得中...</p>

            <label>歩数:</label>
            <p id="steps">取得中...</p>

            <label>朝食の画像:</label>
            <input type="file" name="breakfast" accept="image/*" capture="camera" onchange="previewImage(this, 'breakfast-preview')">
            <img id="breakfast-preview" src="#" alt="朝食の画像" style="max-width: 200px; margin-top: 10px;">
            
            <label>昼食の画像:</label>
            <input type="file" name="lunch" accept="image/*" capture="camera" onchange="previewImage(this, 'lunch-preview')">
            <img id="lunch-preview" src="#" alt="昼食の画像" style="max-width: 200px; margin-top: 10px;">
            
            <label>夕食の画像:</label>
            <input type="file" name="dinner" accept="image/*" capture="camera" onchange="previewImage(this, 'dinner-preview')">
            <img id="dinner-preview" src="#" alt="夕食の画像" style="max-width: 200px; margin-top: 10px;">


            <button type="submit">保存する</button>
        </form>
    </div>

    <script>
    // 日付を表示
    document.getElementById('date').innerText = new Date().toLocaleDateString();

    // PHPで取得したデータをJavaScript側で表示する
    const healthData = <?php echo json_encode($health_data); ?>;
    
    // データが存在する場合、表示する
    if (Object.keys(healthData).length > 0) {
        document.getElementById('height').innerText = healthData.height ? healthData.height + " cm" : "記録なし";
        document.getElementById('weight').innerText = healthData.weight ? healthData.weight + " kg" : "記録なし";
        document.getElementById('body-fat').innerText = healthData.fat ? healthData.fat + " %" : "記録なし";
        document.getElementById('steps').innerText = healthData.steps ? healthData.steps + " 歩" : "記録なし";
    } else {
        document.getElementById('height').innerText = "本日の記録はありません";
        document.getElementById('weight').innerText = "本日の記録はありません";
        document.getElementById('body-fat').innerText = "本日の記録はありません";
        document.getElementById('steps').innerText = "本日の記録はありません";
    }

    function previewImage(input, id) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(id).src = e.target.result;  // プレビュー表示
            };
            reader.readAsDataURL(file);
        }
    }
</script>

</body>
</html>

