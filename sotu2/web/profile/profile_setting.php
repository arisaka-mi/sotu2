<?php
session_start();
require_once('../login/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_from.php');
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   ユーザー情報取得
========================= */
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

if (!$user) {
    exit('ユーザー情報が見つかりません。');
}

$error = "";

/* =========================
   画像リサイズ関数
========================= */
function resizeImageGD($file_tmp, $save_path, $max_width = 300, $max_height = 300) {
    if (!function_exists('imagecreatefromstring')) return false;

    $image_info = getimagesize($file_tmp);
    if (!$image_info) return false;

    $orig_width  = $image_info[0];
    $orig_height = $image_info[1];

    $ratio = min($max_width / $orig_width, $max_height / $orig_height, 1);
    $new_width  = (int)($orig_width * $ratio);
    $new_height = (int)($orig_height * $ratio);

    $src = imagecreatefromstring(file_get_contents($file_tmp));
    $dst = imagecreatetruecolor($new_width, $new_height);

    imagecopyresampled(
        $dst, $src,
        0, 0, 0, 0,
        $new_width, $new_height,
        $orig_width, $orig_height
    );

    $result = imagejpeg($dst, $save_path, 80);

    imagedestroy($src);
    imagedestroy($dst);

    return $result;
}

/* =========================
   POST処理
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $u_name     = $_POST['u_name'] ?? '';
    $u_name_id  = $_POST['u_name_id'] ?? '';
    $u_text     = $_POST['u_text'] ?? '';
    $height     = $_POST['height'] ?? '';

    // 既存画像（ファイル名のみ）
    $pro_img = $user['pro_img'];

    $image_uploaded = !empty($_FILES['pro_img']['name']);

    if ($image_uploaded) {

        $allowed_types = ['image/jpeg','image/png','image/gif'];
        $image_info = getimagesize($_FILES['pro_img']['tmp_name']);

        if (!$image_info || !in_array($image_info['mime'], $allowed_types)) {
            $error = "画像ファイル（jpg/png/gif）のみアップロード可能です。";
        }

        if ($_FILES['pro_img']['size'] > 2 * 1024 * 1024) {
            $error = "画像は2MB以下にしてください。";
        }

        if (!$error) {

            // 保存先（統一）
            $upload_dir = __DIR__ . '/u_img/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_tmp  = $_FILES['pro_img']['tmp_name'];
            $file_name = uniqid('user_', true) . '.jpg';
            $save_path = $upload_dir . $file_name;

            if (function_exists('imagecreatefromstring')) {
                if (!resizeImageGD($file_tmp, $save_path)) {
                    $error = "画像のアップロードに失敗しました。";
                } else {
                    // DBにはファイル名のみ
                    $pro_img = $file_name;
                }
            } else {
                if (!move_uploaded_file($file_tmp, $save_path)) {
                    $error = "画像のアップロードに失敗しました。";
                } else {
                    $pro_img = $file_name;
                }
            }
        }
    }

    if (!$error) {

        $sql = $image_uploaded
            ? "UPDATE User
               SET u_name=:u_name,
                   u_name_id=:u_name_id,
                   u_text=:u_text,
                   height=:height,
                   pro_img=:pro_img
               WHERE user_id=:user_id"
            : "UPDATE User
               SET u_name=:u_name,
                   u_name_id=:u_name_id,
                   u_text=:u_text,
                   height=:height
               WHERE user_id=:user_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':u_name', $u_name, PDO::PARAM_STR);
        $stmt->bindValue(':u_name_id', $u_name_id, PDO::PARAM_STR);
        $stmt->bindValue(':u_text', $u_text, PDO::PARAM_STR);
        $stmt->bindValue(':height', $height, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        if ($image_uploaded) {
            $stmt->bindValue(':pro_img', $pro_img, PDO::PARAM_STR);
        }

        if ($stmt->execute()) {
            $_SESSION['u_name']     = $u_name;
            $_SESSION['u_name_id']  = $u_name_id;
            $_SESSION['u_text']     = $u_text;
            $_SESSION['height']     = $height;
            if ($image_uploaded) {
                $_SESSION['pro_img'] = $pro_img;
            }
            header('Location: profile.php');
            exit();
        } else {
            $error = "更新に失敗しました。";
        }
    }
}

/* =========================
   表示用画像パス
========================= */
if (!empty($user['pro_img']) && file_exists(__DIR__ . '/u_img/' . $user['pro_img'])) {
    $img_path = 'u_img/' . $user['pro_img'];
} else {
    $img_path = 'u_img/default.png';
}


$u_name     = htmlspecialchars($user['u_name'], ENT_QUOTES);
$u_name_id  = htmlspecialchars($user['u_name_id'], ENT_QUOTES);
$u_text     = htmlspecialchars($user['u_text'] ?? '', ENT_QUOTES);
$height     = htmlspecialchars($user['height'] ?? '', ENT_QUOTES);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロフィール編集</title>
<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, sans-serif;
    margin: 0;
    background: #fff;
}

.container {
    max-width: 520px;
    margin: 40px auto;
    padding: 0 16px;
}

/* プロフィール画像 */
.profile-icon {
    display: block;
    margin: 0 auto 30px;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #eee;
}

/* セクション見出し */
.section-title {
    margin: 40px 0 20px;
    font-size: 16px;
    font-weight: 600;
}

/* 横並び・下線 */
.row-line {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 26px;
}

.row-line label {
    width: 120px;
    font-size: 14px;
    color: #555;
}

.row-line input,
.row-line textarea {
    flex: 1;
    border: none;
    border-bottom: 1px solid #ccc;
    padding: 6px 4px;
    font-size: 15px;
    background: transparent;
}

.row-line textarea {
    resize: none;
    min-height: 36px;
}

.row-line input:focus,
.row-line textarea:focus {
    outline: none;
    border-bottom-color: #ff6b6b;
}

/* 固定情報行 */
.static-row {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
    font-size: 14px;
}

.static-row span:first-child {
    width: 120px;
    color: #555;
}

.static-row a {
    font-size: 12px;
    color: #ff6b6b;
    text-decoration: none;
}

/* ボタン */
.btn-area {
    margin-top: 40px;
    display: flex;
    gap: 12px;
}

.btn {
    flex: 1;
    padding: 12px;
    border-radius: 24px;
    text-align: center;
    font-size: 14px;
    border: none;
    cursor: pointer;
    text-decoration: none;
}

.btn.primary {
    background: #ff6b6b;
    color: #fff;
}

.btn.cancel {
    background: #eee;
    color: #333;
}

/* エラー */
.error {
    color: #ff4d4d;
    margin-bottom: 20px;
    font-size: 14px;
}
/* ===== アバター＋ボタン ===== */
.avatar-wrapper {
    position: relative;
    width: 120px;
    margin: 0 auto 40px;
}

.avatar-wrapper .profile-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #eee;
}

/* ＋マーク */
.avatar-plus {
    position: absolute;
    right: 4px;
    bottom: 4px;
    width: 32px;
    height: 32px;
    background: #ff6b6b;
    color: #fff;
    font-size: 22px;
    font-weight: 600;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,.15);
}

.avatar-plus input {
    display: none;
}

.avatar-plus:hover {
    background: #ff4d4d;
}

.logout {
    color: #F67979;
    text-decoration: none;   /* デフォルト下線なし */
    font-size: 14px;
    font-weight: 500;
    transition: none;
}

/* hover時だけ下線 */
.logout:hover {
    text-decoration: underline;
}

/* クリック・訪問後も色を変えない（保険） */
.logout:link,
.logout:visited,
.logout:hover,
.logout:active {
    color: #F67979;
}

  
</style>
</head>
<body>
<main>
<div class="container">

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error, ENT_QUOTES) ?></p>
<?php endif; ?>

<form action="" method="post" enctype="multipart/form-data">

    <div class="avatar-wrapper">
        <img src="<?= htmlspecialchars($img_path, ENT_QUOTES) ?>" class="profile-icon">

        <label class="avatar-plus">
            +
            <input type="file" name="pro_img" accept="image/*">
        </label>
    </div>


    <h2 class="section-title">ユーザー情報</h2>

    <div class="row-line">
        <label>ユーザー名</label>
        <input type="text" name="u_name" value="<?= $u_name ?>" required>
    </div>

    <div class="row-line">
        <label>ユーザーID</label>
        <input type="text" name="u_name_id" value="<?= $u_name_id ?>" required>
    </div>

    <div class="row-line">
        <label>自己紹介</label>
        <textarea name="u_text"><?= $u_text ?></textarea>
    </div>

    <h2 class="section-title">あなたのタイプ</h2>

    <div class="row-line">
        <label>身長</label>
        <input type="text" name="height" value="<?= $height ?>">
    </div>

    <div class="static-row">
        <span>骨格</span>
        <span><?= htmlspecialchars($user['bt_name'] ?? '未設定', ENT_QUOTES) ?></span>
        <a href="../diagnosis/body_detail.php">変更</a>
    </div>

    <div class="static-row">
        <span>パーソナルカラー</span>
        <span><?= htmlspecialchars($user['pc_name'] ?? '未設定', ENT_QUOTES) ?></span>
        <a href="../diagnosis/pc_detail.php">変更</a>
    </div>

    <div class="logout">
        <p><a href="../login/logout.php">ログアウト</a></p>
    </div>

    <div class="btn-area">
        <button type="submit" class="btn primary">更新</button>
        <a href="profile.php" class="btn cancel">キャンセル</a>
    </div>

</form>

</div>
</main>
</body>
</html>
