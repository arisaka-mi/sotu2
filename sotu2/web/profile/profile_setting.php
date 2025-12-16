<?php
session_start();
require_once('../login/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ユーザー情報取得
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        bt.bt_name,
        pc.pc_name
    FROM user u
    LEFT JOIN body_type bt ON u.bt_id = bt.bt_id
    LEFT JOIN parsonal_color pc ON u.pc_id = pc.pc_id
    WHERE u.user_id = :user_id
");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) exit("ユーザー情報が見つかりません。");

$error = "";

// 画像リサイズ関数
function resizeImageGD($file_tmp, $save_path, $max_width = 300, $max_height = 300) {
    if (!function_exists('imagecreatefromstring')) return false;
    $image_info = getimagesize($file_tmp);
    if (!$image_info) return false;

    $orig_width = $image_info[0];
    $orig_height = $image_info[1];

    $ratio = min($max_width / $orig_width, $max_height / $orig_height, 1);
    $new_width = (int)($orig_width * $ratio);
    $new_height = (int)($orig_height * $ratio);

    $src = imagecreatefromstring(file_get_contents($file_tmp));
    $dst = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($dst, $src, 0,0,0,0, $new_width,$new_height, $orig_width,$orig_height);

    $result = imagejpeg($dst, $save_path, 80);

    imagedestroy($src);
    imagedestroy($dst);
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u_name = $_POST['u_name'] ?? '';
    $u_name_id = $_POST['u_name_id'] ?? '';
    $u_text = $_POST['u_text'] ?? '';
    $height = $_POST['height'] ?? '';
    $pro_img = $user['pro_img']; // 現在の画像

    $image_uploaded = !empty($_FILES['pro_img']['name']);

    if ($image_uploaded) {
        $allowed_types = ['image/jpeg','image/png','image/gif'];
        $image_info = getimagesize($_FILES['pro_img']['tmp_name']);
        if (!$image_info || !in_array($image_info['mime'],$allowed_types)) {
            $error = "画像ファイル（jpg/png/gif）のみアップロード可能です。";
        }

        if ($_FILES['pro_img']['size'] > 2*1024*1024) {
            $error = "画像は2MB以下にしてください。";
        }

        if (!$error) {
            $upload_dir = __DIR__ . '/u_img/';
            $relative_dir = 'u_img/';
            if (!is_dir($upload_dir)) mkdir($upload_dir,0755,true);

            $file_tmp = $_FILES['pro_img']['tmp_name'];
            $file_name = uniqid() . '_' . basename($_FILES['pro_img']['name']);
            $save_path = $upload_dir . $file_name;
            $file_path_for_db = $relative_dir . $file_name;

            if (function_exists('imagecreatefromstring')) {
                if (!resizeImageGD($file_tmp, $save_path)) {
                    $error = "画像のアップロードに失敗しました。";
                } else {
                    $pro_img = $file_path_for_db;
                }
            } else {
                if (!move_uploaded_file($file_tmp, $save_path)) {
                    $error = "画像のアップロードに失敗しました。";
                } else {
                    $pro_img = $file_path_for_db;
                }
            }
        }
    }

    if (!$error) {
        $sql = $image_uploaded
            ? "UPDATE User SET u_name=:u_name,u_name_id=:u_name_id,u_text=:u_text,height=:height,pro_img=:pro_img WHERE user_id=:user_id"
            : "UPDATE User SET u_name=:u_name,u_name_id=:u_name_id,u_text=:u_text,height=:height WHERE user_id=:user_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':u_name', $u_name, PDO::PARAM_STR);
        $stmt->bindValue(':u_name_id', $u_name_id, PDO::PARAM_STR);
        $stmt->bindValue(':u_text', $u_text, PDO::PARAM_STR);
        $stmt->bindValue(':height', $height, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        if ($image_uploaded) $stmt->bindValue(':pro_img',$pro_img,PDO::PARAM_STR);

        if ($stmt->execute()) {
            $_SESSION['u_name']=$u_name;
            $_SESSION['u_name_id']=$u_name_id;
            $_SESSION['u_text']=$u_text;
            $_SESSION['height']=$height;
            if ($image_uploaded) $_SESSION['pro_img']=$pro_img;
            header('Location: profile.php');
            exit();
        } else {
            $error="更新に失敗しました。";
        }
    }
}

$img_path = $user['pro_img'] ?? 'u_img/default.png';
if (!file_exists(__DIR__ . '/' . $img_path) || empty($user['pro_img'])) {
    $img_path = 'u_img/default.png';
}

$u_name = htmlspecialchars($user['u_name'], ENT_QUOTES);
$u_name_id = htmlspecialchars($user['u_name_id'], ENT_QUOTES);
$u_text = htmlspecialchars($user['u_text'] ?? '', ENT_QUOTES);
$height = htmlspecialchars($user['height'] ?? '', ENT_QUOTES);
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

            <img src="<?= htmlspecialchars($img_path, ENT_QUOTES) ?>" class="profile-icon">

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
            <p>骨格: <?= htmlspecialchars($user['bt_name'] ?? '未設定', ENT_QUOTES) ?>（編集不可）</p>
            <p>パーソナルカラー: <?= htmlspecialchars($user['pc_name'] ?? '未設定', ENT_QUOTES) ?>（編集不可）</p>
            <p><a href="../login/logout.php">ログアウト</a></p>

            <button type="submit" class="btn">更新する</button>
            <a href="profile.php" class="btn" style="background:#6c757d;">キャンセル</a>
        </form>
    </div>
</main>
</body>
</html>
