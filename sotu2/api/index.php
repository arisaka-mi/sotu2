<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

// セッションからユーザー名を取得
$username = isset($_SESSION['name']) ? $_SESSION['name'] : '';

// ログイン状態をチェック
if (isset($_SESSION['id'])) {
    $response = [
        'status' => 'ok',
        'message' => 'ログイン成功',
        'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
        'redirect' => 'profile.php'
    ];
} else {
    $response = [
        'status' => 'error',
        'message' => 'ログインしていません',
        'redirect' => 'login_form.php'
    ];
}

// JSONとして返す
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
