<?php
session_start();

$client_id = '23PYXK';
$client_secret = 'a80eab89b85e2f2819c49441b5cf7c9c';
$redirect_uri = 'http://localhost/fitbit_callback.php';

if (!isset($_GET['code'])) {
    // 認可ページへリダイレクト
    $auth_url = "https://www.fitbit.com/oauth2/authorize?response_type=code" .
        "&client_id=$client_id" .
        "&redirect_uri=$redirect_uri" .
        "&scope=activity%20heartrate%20profile%20sleep%20weight" .
        "&expires_in=86400";
    header("Location: $auth_url");
    exit;
} else {
    // アクセストークン取得
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
        // MySQLに保存
        $pdo = new PDO('mysql:host=localhost;dbname=health;charset=utf8', 'root', '');
        $stmt = $pdo->prepare("INSERT INTO fitbit_tokens (user_id, access_token, refresh_token) 
                                VALUES (?, ?, ?) 
                                ON DUPLICATE KEY UPDATE access_token = VALUES(access_token), refresh_token = VALUES(refresh_token)");
        $stmt->execute([$tokens['user_id'], $tokens['access_token'], $tokens['refresh_token']]);        
    
        echo "<h1>認証成功</h1><p>Fitbitアカウントがリンクされました。</p>";
    } else {
        echo "<h1>エラー</h1><p>トークン取得に失敗しました。</p>";
    }
}
