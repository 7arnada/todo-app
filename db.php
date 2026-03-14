<?php
// MySQL接続設定
// XAMPPのデフォルトではユーザー: root / パスワード: (空)
$host = '127.0.0.1';
$db   = 'todo_app';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    
    exit('データベース接続に失敗しました: ' . $e->getMessage());
}

// テーブルが存在しない場合は作成（completed / description を含む）
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        completed TINYINT(1) NOT NULL DEFAULT 0,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
);

// 既存テーブルにカラムがない場合は追加（MySQL 5.7 だと IF NOT EXISTS が使えないため、存在チェックを行う）
$columns = $pdo->query("SHOW COLUMNS FROM tasks")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('completed', $columns, true)) {
    $pdo->exec("ALTER TABLE tasks ADD COLUMN completed TINYINT(1) NOT NULL DEFAULT 0");
}
if (!in_array('description', $columns, true)) {
    $pdo->exec("ALTER TABLE tasks ADD COLUMN description TEXT NULL");
}
