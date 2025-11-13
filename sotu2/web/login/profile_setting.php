<?php
session_start();
require_once('config.php'); // DB接続

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login_form.php');
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

// 画像リサイズ関数（GDライブラリ）
function resizeImage($file_tmp, $save_path, $max_width = 300, $max_height = 300) {
    list($orig_width, $orig_height) = getimagesize($file_tmp);
    $ratio = min($max_width / $orig_width, $max_height / $orig_height, 1); // 小さい画像はそのまま
    $new_width = (int)($orig_width * $ratio);
    $new_height = (int)($orig_height * $ratio);

    $src = imagecreatefromstring(file_get_contents($file_tmp));
    $dst = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);

    $result = imagejpeg($dst, $save_path, 80); // 画質80%
    imagedestroy($src);
    imagedestroy($dst);
    return $result;
}

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $u_name = $_POST['u_name'] ?? '';
    $u_name_id = $_POST['u_name_id'] ?? '';

    $pro_img = $user['pro_img'];

    // 画像アップロード
    if (!empty($_FILES['pro_img']['name'])) {

        if ($_FILES['pro_img']['size'] > 2*1024*1024) {
            $error = "画像は2MB以下にしてください。";
        } else {
            $upload_dir = 'u_icon/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $file_tmp = $_FILES['pro_img']['tmp_name'];
            $file_name = uniqid() . '_' . basename($_FILES['pro_img']['name']);
            $file_path = $upload_dir . $file_name;

            if (resizeImage($file_tmp, $file_path)) {
                $pro_img = $file_path;
            } else {
                $error = "画像のアップロードに失敗しました。";
            }
        }
    }

    // DB更新
    if (!isset($error)) {
        $update = $pdo->prepare("UPDATE User SET u_name = :u_name, u_name_id = :u_name_id, pro_img = :pro_img WHERE user_id = :user_id");
        $update->bindValue(':u_name', $u_name, PDO::PARAM_STR);
        $update->bindValue(':u_name_id', $u_name_id, PDO::PARAM_STR);
        $update->bindValue(':pro_img', $pro_img, PDO::PARAM_STR);
        $update->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        if ($update->execute()) {
            // セッション更新
            $_SESSION['u_name'] = $u_name;
            $_SESSION['u_name_id'] = $u_name_id;
            $_SESSION['pro_img'] = $pro_img;

            header('Location: profile.php');
            exit();
        } else {
            $error = "更新に失敗しました。";
        }
    }
}

// 表示用
$img_icon = $user['pro_img'] ?? 'dflt_icon.jpg';
$u_name = htmlspecialchars($user['u_name'], ENT_QUOTES, 'UTF-8');
$u_name_id = htmlspecialchars($user['u_name_id'], ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>プロフィール編集</title>
<style>
body { font-family: sans-serif; background: #f9f9f9; margin:0; padding:0;}
.container { max-width:500px; margin:50px auto; padding:20px; background:#fff; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
h1 { text-align:center; margin-bottom:20px; }
.form-group { margin-bottom:15px; }
label { display:block; margin-bottom:5px; font-weight:bold; }
input[type="text"], textarea { width:100%; padding:8px; box-sizing:border-box; }
input[type="file"] { padding:5px; }
.profile-icon { width:100px; height:100px; border-radius:50%; object-fit:cover; margin-bottom:15px; display:block;}
.btn { display:inline-block; padding:10px 20px; background:#007bff; color:#fff; text-decoration:none; border-radius:5px; border:none; cursor:pointer; }
.btn:hover { background:#0056b3; }
.error { color:red; margin-bottom:15px; }
</style>
</head>
<body>
<div class="container">
<h1>プロフィール編集</h1>

<?php if(isset($error)) echo "<p class='error'>{$error}</p>"; ?>

<form action="" method="post" enctype="multipart/form-data">
    <img src="<?= htmlspecialchars($img_icon, ENT_QUOTES) ?>" alt="プロフィール画像" class="profile-icon">
    <div class="form-group">
        <label for="pro_img">プロフィール画像</label>
        <input type="file" name="pro_img" id="pro_img" accept="image/*">
    </div>

    <div class="form-group">
        <label for="u_name">ユーザー名</label>
        <input type="text" name="u_name" id="u_name" value="<?= $u_name ?>" required>
    </div>

    <div class="form-group">
        <label for="u_name_id">ユーザーID</label>
        <input type="text" name="u_name_id" id="u_name_id" value="<?= $u_name_id ?>" required>
    </div>

    <button type="submit" class="btn">更新する</button>
    <a href="profile.php" class="btn" style="background:#6c757d;">キャンセル</a>
</form>
</div>
</body>
</html>
