<?php
session_start();
require_once(__DIR__ . '/../login/config.php'); // DB接続

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login_form.php");
    exit();
}

// POST 値チェック
if (!isset($_POST['upper']) || !isset($_POST['lower'])) {
    echo "保存データがありません。";
    exit();
}

$upper = $_POST['upper'];
$lower = $_POST['lower'];

// ====== ここで保存する最終タイプを決める ======
$typeMap = [
    "upper_straight" => "ストレート",
    "upper_wave" => "ウェーブ",
    "upper_natural" => "ナチュラル",
    "lower_straight" => "ストレート",
    "lower_wave" => "ウェーブ",
    "lower_natural" => "ナチュラル",
];

// 上半身 × 下半身の最終タイプを作成
$finalType = $typeMap[$upper] . "×" . $typeMap[$lower];



// ===== Body_type テーブルから bt_id を取得（LIKEで取得）=====
$stmt = $pdo->prepare(
    "SELECT bt_id FROM body_type WHERE bt_name LIKE ?"
);
$stmt->execute(['%' . $typeMap[$upper] . '%']);
$btRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$btRow) {
    echo "骨格のIDが取得できません。";
    exit();
}

$bt_id = $btRow['bt_id'];


// ===== ユーザーに保存 =====
$stmt = $pdo->prepare("UPDATE user SET bt_id = ? WHERE user_id = ?");
$stmt->execute([$bt_id, $_SESSION['user_id']]);

// プロフィール画面へ
header("Location: ../profile/profile_setting.php?saved=1");
exit();
