<?php
session_start();
header('Content-Type: text/html; charset=UTF-8'); // HTMLレスポンスを指定

$servername = "localhost";
$username = "root";  // MySQLのユーザー名
$password = "";  // MySQLのパスワード
$dbname = "mydb";  // データベース名

// MySQLに接続
$conn = new mysqli($servername, $username, $password, $dbname);

// 接続チェック
if ($conn->connect_error) {
    die("データベース接続に失敗しました: " . $conn->connect_error);
}

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = $_POST['username'] ?? '';
    $user_password = $_POST['password'] ?? '';

    // SQLクエリを準備
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $result = $stmt->get_result();

    // ユーザー確認
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // パスワードの照合
        if (password_verify($user_password, $row['password'])) {
            // ユーザー名とその他の情報をセッションに格納
            $_SESSION['username'] = $user_name;

            // 成功した場合、リダイレクト
            header("Location: link.php");  // index.phpにリダイレクト
            exit;
        } else {
            $error_message = "パスワードが違います";
        }
    } else {
        $error_message = "ユーザー名が見つかりません";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>ログインページ</title>
</head>
<body>
    <div class="login-container">
        <h1>ログイン</h1>

        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form id="loginForm" method="post">
            <label for="username">ユーザー名:</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required>

            <p></p>
            
            <label for="password">パスワード:</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            
            <button type="submit">ログイン</button>
        </form>

        <p>まだアカウントを作成していませんか？<br> <a href="register.php">新規登録</a></p>
    </div>
</body>
</html>
