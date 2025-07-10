<?php
session_start();

$client_id = 'xxxxx';
$client_secret = 'xxxxx';
$redirect_uri = 'http://localhost/fitbit_callback.php';

$username = $_SESSION['username'];

// MySQLに接続
$pdo = new PDO('mysql:host=localhost;dbname=mydb;charset=utf8', 'root', '');

// username から id を取得
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<h1>エラー</h1><p>ユーザー情報が見つかりません。</p>";
    exit;
}

$user_id = $user['id'];

if (!isset($_GET['code'])) {
    $auth_url = "https://www.fitbit.com/oauth2/authorize?response_type=code" .
        "&client_id=$client_id" .
        "&redirect_uri=$redirect_uri" .
        "&scope=activity%20heartrate%20profile%20sleep%20weight" .
        "&expires_in=86400";
    header("Location: $auth_url");
    exit;
} else {
    $code = $_GET['code'];

    $headers = [
        "Authorization: Basic " . base64_encode("$client_id:$client_secret"),
        "Content-Type: application/x-www-form-urlencoded"
    ];

    $data = "client_id=$client_id&code=$code&grant_type=authorization_code&redirect_uri=$redirect_uri";

    $ch = curl_init("https://api.fitbit.com/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    curl_close($ch);

    $tokens = json_decode($response, true);

    if (isset($tokens['user_id'])) {
        // トークンを保存
        $stmt = $pdo->prepare("INSERT INTO tokens (user_id, fitbit_id, fitbit_access, fitbit_refresh) 
                                VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE fitbit_access = VALUES(fitbit_access), fitbit_refresh = VALUES(fitbit_refresh)");
        $stmt->execute([$user_id, $tokens['user_id'], $tokens['access_token'], $tokens['refresh_token']]);

        echo "<h1>認証成功</h1><p>Fitbitアカウントがリンクされました。</p>";
    } else {
        echo "<h1>エラー</h1><p>トークン取得に失敗しました。</p>";
    }
}
?>