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

// タスクの追加処理 if文の中身→POST送信されたか？かつtaskが存在するか？
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
    $newTask = trim($_POST['task']);// trimは前後の空白を削除する関数
    $description = trim($_POST['description'] ?? '');
    if (!empty($newTask)) {
        $stmt = $pdo->prepare('INSERT INTO tasks (title, description) VALUES (:title, :description)');// $pdo->prepareはそのオブジェクトの機能を呼ぶ
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

// タスクの一括削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete']) ){
    
    $bulkDeleteIds = $_POST['bulk_delete']; 
    if (!empty($bulkDeleteIds)) {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE completed = 1");
        $stmt->execute();
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

// タスクの完了状態トグル処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids[]'])) {
    $deleteIds = (int)$_POST['delete_ids[]'];
    if ($deleteIds > 0) {
        $stmt = $pdo->prepare('UPDATE tasks SET completed = 1 - completed WHERE id = :id');
        $stmt->execute(['id' => $deleteIds]);
        // 成功したらリダイレクト
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// メモ更新処理。preareはSQLインジェクション対策用の安全にSQLを実行するメソッド。 
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

// タスク一覧を取得。fetchAllは結果を全部取り出す
$stmt = $pdo->query('SELECT id, title, completed, description, created_at FROM tasks ORDER BY created_at DESC');
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ToDoアプリ</title>
    <link rel="stylesheet" href="style.css">
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
        <input type="text" name="description" placeholder="メモ (任意)">
        <button type="submit">追加</button>
    </form>
    <form method="POST" action="">
        <input type="hidden" name="bulk_delete" value="<?php echo $task['id']; ?>">
        <button type="submit" name="bulk_delete" value="1" onclick="return confirm('選択したタスクを削除しますか？')">一括削除</button>
    </form>


    <!-- タスクリスト表示 -->
    <table class="task-table">
        <thead>
            <tr>
                <th>チェック</th>
                <th>タスク項目</th>
                <th>作成日時</th>
                <th>メモ</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tasks as $task): ?>
            <tr class="<?php echo $task['completed'] ? 'completed' : ''; ?>"><!--タスクが完了している場合は行全体に 'completed' クラスを追加-->
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="toggle_id" value="<?php echo $task['id']; ?>">
                        <input type="checkbox" onchange="this.form.submit()" <?php echo $task['completed'] ? 'checked' : ''; ?>>
                    </form>
                </td>
                <td><?php echo htmlspecialchars($task['title']); ?></td>
                <td><?php echo htmlspecialchars($task['created_at']); ?></td>
                <td>
                    <form method="POST" action="" style="display:flex; align-items:center; gap:8px;"; >
                        <textarea name="memo" rows="1" style="flex:1;" class="memo-textarea" placeholder="メモを入力"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                        <input type="hidden" name="memo_id" value="<?php echo $task['id']; ?>">
                        <button type="submit" class="memo-button">更新</button>
                    </form>
                </td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="delete_id" value="<?php echo $task['id']; ?>">
                        <button type="submit" class="delete-button" onclick="return confirm('このタスクを削除しますか？')">削除</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
