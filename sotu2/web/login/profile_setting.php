<?php
//sscoachaidhaudh
session_start();

// DB接続設定
require_once('config.php');

// ログイン済みかチェック
if (!isset($_SESSION['user_id'])) {
    die('ログインしてください');
}

$userId = $_SESSION['user_id'];

// 現在のユーザー情報を取得
$stmt = $pdo->prepare("SELECT name, image FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$username = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
$currentImage = !empty($user['image']) ? 'uploads/' . htmlspecialchars($user['image'], ENT_QUOTES, 'UTF-8') : 'uploads/default.png';

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['new_name']);
    $imageFileName = null;

    // 🔹 名前バリデーション
    if ($newName === '') {
        echo "名前は空にできません。";
    } elseif (!preg_match('/^[a-zA-Z]{1,30}$/', $newName)) {
        echo "ユーザー名は英字のみ30文字以内で入力してください。";
    } else {
        // 🔹 画像がアップロードされた場合
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = 'uploads/';
            $imageFileName = uniqid() . '_' . basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $imageFileName;

            // 許可するファイル形式
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['image']['type'], $allowedTypes)) {
                // ファイルを保存
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                    // 古い画像が存在すれば削除（default.pngは削除しない）
                    if (!empty($user['image']) && file_exists('uploads/' . $user['image']) && $user['image'] !== 'default.png') {
                        unlink('uploads/' . $user['image']);
                    }
                } else {
                    die('画像のアップロードに失敗しました。');
                }
            } else {
                die('画像形式は JPG / PNG / GIF のみ対応しています。');
            }
        }

        // 🔹 DB更新
        if ($imageFileName) {
            $stmt = $pdo->prepare("UPDATE users SET name = :name, image = :image WHERE id = :id");
            $stmt->execute([':name' => $newName, ':image' => $imageFileName, ':id' => $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $newName, ':id' => $userId]);
        }

        // セッション更新
        $_SESSION['name'] = $newName;

        // リダイレクトしてフォーム再送信防止
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>プロフィール設定</title>
</head>
<body>
    <h1>プロフィールを編集</h1>

    <!-- 現在のプロフィール画像 -->
    <img src="<?= $currentImage ?>" alt="プロフィール画像" class="profile-icon"><br><br>

    <form method="POST" enctype="multipart/form-data">
        <label for="new_name">ユーザー名を変更:</label><br>
        <input type="text" name="new_name" id="new_name" value="<?= $username ?>" required
               maxlength="30" pattern="[A-Za-z]{1,30}" title="英字のみ30文字以内"><br><br>

        <label for="image">プロフィール画像を変更:</label><br>
        <input type="file" name="image" id="image" accept="image/*"><br><br>

        <button type="submit">変更</button>
    </form>

    <br>
    <a href="profile.php">プロフィールへ戻る</a><br>
    <a href="logout.php">ログアウト</a>
</body>
</html>
