<?php
session_start();
require_once(__DIR__ . '/../login/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login_form.php");
    exit();
}

if (
    !isset($_POST['upper_bt']) ||
    !isset($_POST['lower_bt']) ||
    !isset($_POST['final_bt'])
) {
    echo "保存データがありません。";
    exit();
}

$upper = $_POST['upper_bt'];
$lower = $_POST['lower_bt'];
$final = $_POST['final_bt'];

$stmt = $pdo->prepare("SELECT bt_id FROM body_type WHERE bt_name = ?");
$stmt->execute([$final]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "骨格タイプが見つかりません。";
    exit();
}

$stmt = $pdo->prepare("
    UPDATE user
    SET bt_id = ?, upper_bt = ?, lower_bt = ?
    WHERE user_id = ?
");
$stmt->execute([
    $row['bt_id'],
    $upper,
    $lower,
    $_SESSION['user_id']
]);

header("Location: ../profile/profile_setting.php");
exit();
