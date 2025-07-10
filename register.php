<?php
// もしフォームが送信されていれば処理を開始
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $servername = "localhost";
    $username = "root";  // MySQLのユーザー名
    $password = "";  // MySQLのパスワード
    $dbname = "mydb";  // データベース名

    // MySQLに接続
    $conn = new mysqli($servername, $username, $password, $dbname);

    // 接続チェック
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $user_name = $_POST['username'];
    $user_password = $_POST['password'];

    // パスワードのハッシュ化
    $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);

    // SQLクエリを準備（usersテーブルに新規登録）
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user_name, $hashed_password);

    // 登録実行
    if ($stmt->execute()) {
        // 登録が成功した場合、挿入されたユーザーのIDを取得
        $user_id = $stmt->insert_id;

        $message = "登録が完了しました。<br><a href='health.php'>ログインページへ</a>";
    } else {
        $message = "エラー: " . $stmt->error;
    }

    // ステートメントを閉じる
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>新規登録ページ</title>
</head>
<body>
    <div class="register-container">
        <h1>新規登録</h1>

        <?php
        // 登録結果のメッセージを表示
        if (isset($message)) {
            echo "<p>$message</p>";
        }
        ?>

        <!-- ユーザー登録フォーム -->
        <form action="register.php" method="post">
            <label for="username">ユーザー名:</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required>

            <p></p>
            
            <label for="password">パスワード:</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>

            <p></p>
            
            <button type="submit">登録</button>
        </form>
    </div>
</body>
</html>
