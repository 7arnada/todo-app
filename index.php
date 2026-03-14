<?php
// セッション開始
session_start();

// データベース接続を読み込む
require_once __DIR__ . '/db.php';

// スタイル変更処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $_SESSION['theme'] = $_POST['theme'];
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// 現在のテーマを取得（デフォルト: white）
$currentTheme = $_SESSION['theme'] ?? 'white';

// タスクの追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
    $newTask = trim($_POST['task']);
    $description = trim($_POST['description'] ?? '');
    if (!empty($newTask)) {
        $stmt = $pdo->prepare('INSERT INTO tasks (title, description) VALUES (:title, :description)');
        $stmt->execute(['title' => $newTask, 'description' => $description]);
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

// タスクの完了状態トグル処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $toggleId = (int)$_POST['toggle_id'];
    if ($toggleId > 0) {
        $stmt = $pdo->prepare('UPDATE tasks SET completed = 1 - completed WHERE id = :id');
        $stmt->execute(['id' => $toggleId]);
        // 成功したらリダイレクト
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// メモ更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['memo_id'])) {
    $memoId = (int)$_POST['memo_id'];
    $memo = trim($_POST['memo'] ?? '');
    if ($memoId > 0) {
        $stmt = $pdo->prepare('UPDATE tasks SET description = :description WHERE id = :id');
        $stmt->execute(['description' => $memo, 'id' => $memoId]);
        // 成功したらリダイレクト
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// タスク一覧を取得
$stmt = $pdo->query('SELECT id, title, description, completed, created_at FROM tasks ORDER BY created_at DESC');
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ToDoアプリ</title>
    <style>
        body.theme-white { background-color: #fff; color: #000; }
        body.theme-dark { background-color: #333; color: #fff; }
        body.theme-pink { background-color: #ffb6c1; color: #000; }
        body.theme-auto { /* JavaScriptで処理 */ }

        .theme-selector {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        @media (prefers-color-scheme: dark) {
            body.theme-auto { background-color: #333; color: #fff; }
        }
        @media (prefers-color-scheme: light) {
            body.theme-auto { background-color: #fff; color: #000; }
        }
    </style>
</head>
<body class="theme-<?php echo $currentTheme; ?>">
    <div class="theme-selector">
        <form method="POST" action="">
            <label for="theme">テーマ:</label>
            <select name="theme" id="theme" onchange="this.form.submit()">
                <option value="white" <?php echo $currentTheme === 'white' ? 'selected' : ''; ?>>ホワイト</option>
                <option value="dark" <?php echo $currentTheme === 'dark' ? 'selected' : ''; ?>>ダーク</option>
                <option value="pink" <?php echo $currentTheme === 'pink' ? 'selected' : ''; ?>>ピンク</option>
                <option value="auto" <?php echo $currentTheme === 'auto' ? 'selected' : ''; ?>>Windowsの設定に合わせる</option>
            </select>
        </form>
    </div>

    <h1>ToDoリスト</h1>
    
    <!-- タスク追加フォーム -->
    <form method="POST" action="">
        <input type="text" name="task" placeholder="新しいタスクを入力" required>
        <button type="submit">追加</button>
    </form>
    
    <!-- タスクリスト表示 -->
    <ul>
        <?php foreach ($tasks as $task): ?>
            <li style="<?php echo $task['completed'] ? 'text-decoration: line-through;' : ''; ?>">
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="toggle_id" value="<?php echo $task['id']; ?>">
                    <input type="checkbox" onchange="this.form.submit()" <?php echo $task['completed'] ? 'checked' : ''; ?>>
                </form>
                <?php echo htmlspecialchars($task['title']); ?> <small>(<?php echo htmlspecialchars("作成日時: " . $task['created_at']); ?>)</small>
                  <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="delete_id" value="<?php echo $task['id']; ?>">
                    <button type="submit" onclick="return confirm('このタスクを削除しますか？')">削除</button>
              
    
                <form method="POST" action="">
                    <textarea name="memo" rows="1" placeholder="メモを入力"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                    <input type="hidden" name="memo_id" value="<?php echo $task['id']; ?>">
                    <button type="submit">メモ更新</button>
                </form>
              
            </li>
            <br>
        <?php endforeach; ?>
    </ul>
</body>
</html>