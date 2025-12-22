<?php
session_start();
require_once(__DIR__ . '/../login/config.php');

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login_form.php");
    exit();
}

// POSTチェック
if (!isset($_POST['first']) || !isset($_POST['second'])) {
    echo "保存データがありません。";
    exit();
}

$first = $_POST['first'];   // spring
$second = $_POST['second']; // summer 等

$pcMap = [
    "spring" => "イエベ春",
    "summer" => "ブルべ夏",
    "autumn" => "イエベ秋",
    "winter" => "ブルべ冬"
];

if (!isset($pcMap[$first]) || !isset($pcMap[$second])) {
    echo "不正なパーソナルカラーです。";
    exit();
}

$firstName = $pcMap[$first];
$secondName = $pcMap[$second];

/* ===== 1st pc_id ===== */
$stmt = $pdo->prepare(
    "SELECT pc_id FROM parsonal_color WHERE pc_name = ?"
);
$stmt->execute([$firstName]);
$firstRow = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===== 2nd pc_id ===== */
$stmt->execute([$secondName]);
$secondRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$firstRow || !$secondRow) {
    echo "パーソナルカラーID取得失敗";
    exit();
}

$stmt = $pdo->prepare("
    UPDATE user
    SET pc_id = ?, pc_second_id = ?
    WHERE user_id = ?
");
$stmt->execute([
    $firstRow['pc_id'],
    $secondRow['pc_id'],
    $_SESSION['user_id']
]);

header("Location: ../profile/profile_setting.php?saved=1");
exit();
