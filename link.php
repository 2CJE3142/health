<?php 
session_start();

// ログインしているユーザー名を取得
$user_name = $_SESSION['name'];

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>アプリの連携</title>
</head>
<body>
    <div class="button-container">
        <h1>ようこそ、<?php echo htmlspecialchars($user_name); ?>さん!</h1>
        <h2>連携</h2>
        
        <label for="Fitbit">Fitbit</label>
        <button class="button" onclick="window.location.href='https://mproc.jt.u-tokai.ac.jp/fitbit_callback.php'">連携する</button>
        <p></p>
        
        <label for="Tanita">Tanita</label>
        <button class="button" onclick="window.location.href='https://mproc.jt.u-tokai.ac.jp/tanita_callback.php'">連携する</button>
        <p></p>
        <a href="https://www.healthplanet.jp/oauth/auth?client_id=28690.iQ8uG5kCUb.apps.healthplanet.jp&redirect_uri=https://www.healthplanet.jp/success.html&scope=innerscan&response_type=code" target="_blank">
            ここからTanitaのコードを取得する
        </a>
        <p></p>
        
        <label for="Calomama">カロママ</label>
        <button class="button" onclick="window.location.href='https://www.calomama.com/'">連携する</button>
        <p></p>

        <button class="button" onclick="window.location.href='https://mproc.jt.u-tokai.ac.jp/index2_2.php'">次のページへ</button>
    </div>
</body>
</html>

