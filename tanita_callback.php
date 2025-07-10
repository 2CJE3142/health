<?php
session_start();

// MySQL接続の設定
$MYSQL_HOST = 'localhost';
$MYSQL_USER = 'root';
$MYSQL_PASSWORD = '';
$MYSQL_DATABASE = 'mydb';

// メッセージ格納用
$message = "";

// アラート表示用の関数（あとでJSで出力）
function show_alert($msg) {
    global $message;
    $message = $msg;
}

// ログインしているユーザー名を取得
if (isset($_SESSION['username'])) {
    $user_name = $_SESSION['username'];
} else {
    show_alert("エラー: ユーザーがログインしていません。");
    exit;
}

// トークン保存関数
function save_tokens_to_db($user_name, $access_token, $refresh_token) {
    global $MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DATABASE;

    try {
        $conn = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DATABASE);

        if ($conn->connect_error) {
            show_alert("接続失敗: " . $conn->connect_error);
            return;
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $user_id = $row['id'];

            $sql = "INSERT INTO tokens (user_id, tanita_access, tanita_refresh)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        tanita_access = VALUES(tanita_access), 
                        tanita_refresh = VALUES(tanita_refresh)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $user_id, $access_token, $refresh_token);
            $stmt->execute();

            show_alert("トークンがデータベースに保存されました。");
        } else {
            show_alert("エラー: ユーザーが見つかりません。");
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        show_alert("エラー: " . $e->getMessage());
    }
}

// トークン取得処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];

    $client_id = "xxxxx";
    $client_secret = "xxxxx";
    $redirect_url = "https://www.healthplanet.jp/success.html";
    $api_url = "https://www.healthplanet.jp/oauth/token";

    $data = array(
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_url,
        'client_id' => $client_id,
        'client_secret' => $client_secret
    );

    $options = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $response = file_get_contents($api_url, false, $context);

    if ($response !== FALSE) {
        $response_data = json_decode($response, true);

        if (isset($response_data['access_token'], $response_data['refresh_token'])) {
            $access_token = $response_data['access_token'];
            $refresh_token = $response_data['refresh_token'];

            save_tokens_to_db($user_name, $access_token, $refresh_token);

            $json_data = array(
                'access_token' => $access_token,
                'refresh_token' => $refresh_token
            );
            file_put_contents('tokens.json', json_encode($json_data, JSON_PRETTY_PRINT));

            show_alert("トークン情報がJSONファイルに保存されました。");
        } else {
            show_alert("トークンの取得に失敗しました。API応答: " . json_encode($response_data, JSON_PRETTY_PRINT));
        }
    } else {
        $error = error_get_last();
        show_alert("APIリクエストが失敗しました: " . $error['message']);
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanita トークン取得</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <form method="POST">
        <label for="code">コードを入力してください</label><br>
        <input type="text" id="code" name="code" required><br><br>
        <button type="submit">トークンを取得</button>
    </form>

    <!-- アラートをHTMLの末尾で出す -->
    <?php if (!empty($message)) : ?>
        <script>
            alert('<?php echo addslashes($message); ?>');
        </script>
    <?php endif; ?>
</body>
</html>
