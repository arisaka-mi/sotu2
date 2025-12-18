<?php
session_start();
require_once(__DIR__ . '/../login/config.php'); // DB接続

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login_from.php");
    exit();
}

// POSTチェック
if (!isset($_POST['first']) || !isset($_POST['second'])) {
    echo "保存データがありません。";
    exit();
}

$first = $_POST['first'];   // spring / summer / autumn / winter
$second = $_POST['second'];

// ===== 表示名マップ（pc_ans.php と一致させる）=====
$pcMap = [
    "spring" => "イエベ春",
    "summer" => "ブルべ夏",
    "autumn" => "イエベ秋",
    "winter" => "ブルべ冬"
];

// 念のため存在チェック
if (!isset($pcMap[$first])) {
    echo "不正なパーソナルカラーです。";
    exit();
}

$pcName = $pcMap[$first]; // 1stタイプを保存する

// ===== personal_color テーブルから pc_id を取得 =====
$stmt = $pdo->prepare(
    "SELECT pc_id FROM parsonal_color WHERE pc_name = ?"
);
$stmt->execute([$pcName]);
$pcRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pcRow) {
    echo "パーソナルカラーIDが取得できません。";
    exit();
}

$pc_id = $pcRow['pc_id'];

// ===== ユーザーに保存 =====
$stmt = $pdo->prepare(
    "UPDATE user SET pc_id = ? WHERE user_id = ?"
);
$stmt->execute([$pc_id, $_SESSION['user_id']]);

// プロフィール画面へ
header("Location: ../profile/profile_setting.php?saved=1");
exit();
