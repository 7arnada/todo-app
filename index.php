<?php
// データベース接続を読み込む
require_once __DIR__ . '/db.php';

// タスクの追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
    $newTask = trim($_POST['task']);
    if (!empty($newTask)) {
        $stmt = $pdo->prepare('INSERT INTO tasks (title) VALUES (:title)');
        $stmt->execute(['title' => $newTask]);
        // 成功したらリダイレクトしてPOSTリクエストの再送を防ぐ
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// タスクの削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    if ($deleteId > 0) {
        $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $deleteId]);
        // 成功したらリダイレクト
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// タスク一覧を取得
$stmt = $pdo->query('SELECT id, title, created_at FROM tasks ORDER BY created_at DESC');
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ToDoアプリ</title>
</head>
<body>
    <h1>ToDoリスト</h1>
    
    <!-- タスク追加フォーム -->
    <form method="POST" action="">
        <input type="text" name="task" placeholder="新しいタスクを入力" required>
        <button type="submit">追加</button>
    </form>
    
    <!-- タスクリスト表示 -->
    <ul>
        <?php foreach ($tasks as $task): ?>
            <li>
                <?php echo htmlspecialchars($task['title']); ?> <small>(<?php echo htmlspecialchars($task['created_at']); ?>)</small>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="delete_id" value="<?php echo $task['id']; ?>">
                    <button type="submit" onclick="return confirm('このタスクを削除しますか？')">削除</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>