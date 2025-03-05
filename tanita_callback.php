<?php
session_start();

// MySQL接続の設定
$MYSQL_HOST = 'localhost';  // MySQLサーバーのホスト名
$MYSQL_USER = 'root';  // MySQLのユーザー名
$MYSQL_PASSWORD = '';  // MySQLのパスワード
$MYSQL_DATABASE = 'health';  // 使用するデータベース名

// トークン保存の関数
function save_tokens_to_db($code, $access_token, $refresh_token) {
    global $MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DATABASE;

    try {
        // MySQLに接続
        $conn = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DATABASE);

        // 接続チェック
        if ($conn->connect_error) {
            die("接続失敗: " . $conn->connect_error);
        }

        // SQL文を作成
        $sql = "INSERT INTO tanita_tokens (code, tanita_access, tanita_refresh)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $code, $access_token, $refresh_token);
        $stmt->execute();

        echo "トークンがデータベースに保存されました。";
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo "エラー: " . $e->getMessage();
    }
}

// トークン取得処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];
    
    $client_id = "28690.iQ8uG5kCUb.apps.healthplanet.jp";  // クライアントID
    $client_secret = "1738067548265-6UsL6exQJfYCTepdokXYO8z3ATUmTdx2yECbEs9z";  // クライアントシークレット
    $redirect_url = "https://www.healthplanet.jp/success.html";  // リダイレクトURL
    $api_url = "https://www.healthplanet.jp/oauth/token";  // トークン取得のAPI URL

    // トークンを取得するためのPOSTリクエストを準備
    $data = array(
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_url,
        'client_id' => $client_id,
        'client_secret' => $client_secret
    );

    // リクエストデータをエンコード
    $options = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $response = file_get_contents($api_url, false, $context);

    // レスポンスをJSONとしてパース
    if ($response !== FALSE) {
        $response_data = json_decode($response, true);

        $access_token = $response_data['access_token'];
        $refresh_token = $response_data['refresh_token'];

        // トークンをデータベースに保存
        save_tokens_to_db($code, $access_token, $refresh_token);

        // トークン情報をJSONとしてファイルに保存
        $json_data = array(
            'access_token' => $access_token,
            'refresh_token' => $refresh_token
        );
        file_put_contents('tokens.json', json_encode($json_data, JSON_PRETTY_PRINT));

        echo "トークン情報がJSONファイルに保存されました。";
    } else {
        echo "トークンの取得に失敗しました。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Tanita トークン取得</title>
</head>
<body>
    <form method="POST">
        <label for="code">コードを入力してください</label><br>
        <input type="text" id="code" name="code" required><br><br>
        <button type="submit">トークンを取得</button>
    </form>

</body>
</html>
