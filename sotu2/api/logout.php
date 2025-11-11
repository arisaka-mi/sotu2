<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

// セッションの中身をすべて削除
$_SESSION = [];

// セッションCookieがあれば削除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// セッション破棄
session_destroy();

// JSONでログアウト結果を返す
echo json_encode([
    "status" => "ok",
    "message" => "ログアウトしました。"
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
