<?php
session_start();
require_once('../login/config.php'); // DB接続

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// DBからユーザー情報取得
$stmt = $pdo->prepare("SELECT * FROM User WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "ユーザー情報が見つかりません。";
    exit();
}

// エラー初期化
$error = "";

// 画像リサイズ関数（GD）
function resizeImage($file_tmp, $save_path, $max_width = 300, $max_height = 300) {
    list($orig_width, $orig_height) = getimagesize($file_tmp);
    $ratio = min($max_width / $orig_width, $max_height / $orig_height, 1);
    $new_width = (int)($orig_width * $ratio);
    $new_height = (int)($orig_height * $ratio);

    $src = imagecreatefromstring(file_get_contents($file_tmp));
    $dst = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);

    $result = imagejpeg($dst, $save_path, 80);
    imagedestroy($src);
    imagedestroy($dst);
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $u_name = $_POST['u_name'] ?? '';
    $u_name_id = $_POST['u_name_id'] ?? '';
    $u_text = $_POST['u_text'] ?? '';
    $height = $_POST['hight'] ?? '';
    $pro_img = $user['pro_img']; // 現在の画像パス

    $image_uploaded = !empty($_FILES['pro_img']['name']);

    // --- 画像アップロード処理 ---
    if ($image_uploaded) {

        // MIME チェック（セキュリティ）
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['pro_img']['type'], $allowed_types)) {
            $error = "画像ファイル（jpg / png / gif）のみアップロードできます。";
        }

        // サイズチェック（2MB）
        if ($_FILES['pro_img']['size'] > 2 * 1024 * 1024) {
            $error = "画像は2MB以下にしてください。";
        }

        if (!$error) {
            // 絶対パス（保存用）
            $upload_dir = __DIR__ . '/u_icon/';

            // 相対パス（DB保存用）
            $relative_dir = 'u_icon/';

            // フォルダ作成
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $file_tmp = $_FILES['pro_img']['tmp_name'];
            $file_name = uniqid() . '_' . basename($_FILES['pro_img']['name']);

            // 保存先（絶対パス）
            $save_path = $upload_dir . $file_name;

            // DBに保存するパス（相対）
            $file_path_for_db = $relative_dir . $file_name;

            if (resizeImage($file_tmp, $save_path)) {
                $pro_img = $file_path_for_db;
            } else {
                $error = "画像のアップロードに失敗しました。";
            }
        }
    }

    // --- DB更新 ---
    if (!$error) {
        if ($image_uploaded) {
            $stmt = $pdo->prepare("
                UPDATE User 
                SET u_name = :u_name, u_name_id = :u_name_id, u_text = :u_text,
                    hight = :hight, pro_img = :pro_img
                WHERE user_id = :user_id
            ");
            $stmt->bindValue(':pro_img', $pro_img, PDO::PARAM_STR);

        } else {
            $stmt = $pdo->prepare("
                UPDATE User 
                SET u_name = :u_name, u_name_id = :u_name_id,
                    u_text = :u_text, hight = :hight
                WHERE user_id = :user_id
            ");
        }

        $stmt->bindValue(':u_name', $u_name, PDO::PARAM_STR);
        $stmt->bindValue(':u_name_id', $u_name_id, PDO::PARAM_STR);
        $stmt->bindValue(':u_text', $u_text, PDO::PARAM_STR);
        $stmt->bindValue(':height', $height, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            // セッションも更新
            $_SESSION['u_name'] = $u_name;
            $_SESSION['u_name_id'] = $u_name_id;
            $_SESSION['u_text'] = $u_text;
            $_SESSION['hight'] = $height;
            if ($image_uploaded) $_SESSION['pro_img'] = $pro_img;

            header('Location: profile.php');
            exit();
        } else {
            $error = "更新に失敗しました。";
        }
    }
}

// 表示用
$img_icon = $user['pro_img'] ?? 'u_icon/dflt_icon.png';
$u_name = htmlspecialchars($user['u_name'], ENT_QUOTES, 'UTF-8');
$u_name_id = htmlspecialchars($user['u_name_id'], ENT_QUOTES, 'UTF-8');
$u_text = htmlspecialchars($user['u_text'] ?? '', ENT_QUOTES, 'UTF-8');
$height = htmlspecialchars($user['height'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>プロフィール編集</title>
<style>
    body {
        font-family: sans-serif;
        margin:0;
        padding:0;
    }
    .container {
        max-width:500px;
        margin:50px auto;
        padding:20px;
        border-radius:10px;
    }
    .form-group {
        margin-bottom:15px;
    }
    label {
        display:block;
        margin-bottom:5px;
        font-weight:bold;
    }
    input[type="text"], textarea {
        width:100%;
        padding:8px;
        box-sizing:border-box;
    }
    input[type="file"] {
        padding:5px;
    }
    .profile-icon {
        display:block;
        margin:0 auto 15px;
        width:100px;
        height:100px;
        border-radius:50%;
        object-fit:cover;
        border:1px solid #ebebeb;
    }
    .btn {
        display:inline-block;
        padding:10px 20px;
        background:#007bff;
        color:#fff;
        border-radius:5px;
        text-decoration:none;
        border:none;
        cursor:pointer;
    }
    .btn:hover {
        background:#0056b3;
    }
    .error {
        color:red;
        margin-bottom:15px;
    }
</style>
</head>

<body>
<main>
<div class="container">

    <?php if($error) echo "<p class='error'>{$error}</p>"; ?>

    <form action="" method="post" enctype="multipart/form-data">

        <img src="<?= htmlspecialchars($img_icon, ENT_QUOTES) ?>" class="profile-icon">

        <div class="form-group">
            <label for="pro_img">プロフィール画像</label>
            <input type="file" name="pro_img" id="pro_img" accept="image/*">
        </div>

        <h1>ユーザー編集</h1>

        <div class="form-group">
            <label for="u_name">ユーザー名</label>
            <input type="text" name="u_name" id="u_name" value="<?= $u_name ?>" required>
        </div>

        <div class="form-group">
            <label for="u_name_id">ユーザーID</label>
            <input type="text" name="u_name_id" id="u_name_id" value="<?= $u_name_id ?>" required>
        </div>

        <div class="form-group">
            <label for="u_text">自己紹介</label>
            <textarea name="u_text" id="u_text" rows="5"><?= $u_text ?></textarea>
        </div>

        <h1>あなたのタイプ</h1>

        <div class="form-group">
            <label for="height">身長</label>
            <input type="text" name="height" id="height" value="<?= $height ?>" required>
        </div>

        <p>骨格: <?= $user['bt_id'] ?>（編集不可）</p>
        <p>パーソナルカラー: <?= $user['pc_id'] ?>（編集不可）</p>

        <button type="submit" class="btn">更新する</button>
        <a href="profile.php" class="btn" style="background:#6c757d;">キャンセル</a>
    </form>

    <p><a href="../login/logout.php">ログアウト</a></p>

</div>
</main>
</body>
</html>
