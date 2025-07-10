# 健康管理アプリ

このアプリケーションは、ユーザーが日々の健康データを記録し、FitbitやTANITAのデバイスと連携することで、健康状態を可視化するものです。使用している技術は以下の通りです：

- **XAMPP**（Apache, MySQL）
- **PHP**
- **HTML, CSS, JavaScript**
- **Python**
- **YAML**

## 機能

### 1. ログイン画面 (`health.php`)
ユーザーはこのページでログインします。ログインには、ユーザー名とパスワードが必要です。

### 2. 新規登録画面 (`register.php`)
まだアカウントがない場合は、新規登録が可能です。ユーザー名とパスワードを入力し、アカウントを作成します。

### 3. アクセス許可画面 (`link.php`)
ログイン後、FitbitとTANITAのデバイスとの連携を行います。ユーザーはここで、それぞれのデバイスの認証を行い、アプリとデバイス間でデータのやり取りを可能にします。

### 4. 健康データの表示 (`index3.php`)
ユーザーはここで、自分の健康データをグラフ形式で確認できます。一週間分のデータを可視化し、日々の健康状態を一目で把握できます。

### 5. データ取得 (`getdata.py`)
Pythonスクリプト `getdata.py` は、FitbitやTANITAのAPIを使用して、ユーザーの健康データを取得します。このデータはMySQLデータベースに保存され、ユーザーの健康情報として表示されます。

### 6. 設定ファイル (`_secret.yaml`)
`_secret.yaml` ファイルには、FitbitやTANITAのAPIにアクセスするためのクライアントIDやアクセストークンなどの重要な情報が保存されています。

## データベース

MySQLデータベースは以下の3つのテーブルで構成されています：

1. **users**
    - ユーザーのアカウント情報を保存します。
    ```sql
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100),
        password VARCHAR(100)
    );
    ```

2. **tokens**
    - ユーザーごとのFitbitおよびTANITAの認証トークンを管理します。
    ```sql
    CREATE TABLE tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE, 
        fitbit_id VARCHAR(100),
        fitbit_access TEXT,
        fitbit_refresh TEXT,
        tanita_access TEXT,
        tanita_refresh TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE (user_id, fitbit_id)
    );
    ```

3. **health_data**
    - ユーザーの日々の健康データ（歩数、体重、体脂肪率、身長など）を保存します。
    ```sql
    CREATE TABLE health_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        date DATE,
        steps INT,
        weight DECIMAL(5,2),
        fat DECIMAL(5,2),
        height DECIMAL(5,2),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE (user_id, date)
    );
    ```

## 使用方法

### 必要なソフトウェア

- **XAMPP**（Apache, MySQL）
- **PHP** (7.x 以上推奨)
- **Python** (3.x)
