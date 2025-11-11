<?php
// JSONを返す設定
header('Content-Type: application/json; charset=UTF-8');

// ナビゲーションの項目を配列で定義
$navItems = [
    ['title' => 'タイムライン', 'icon' => '🏠', 'link' => 'index.php'],
    ['title' => '投稿する', 'icon' => '✍', 'link' => 'post.php'],
    ['title' => 'プロフィール', 'icon' => '👤', 'link' => 'profile.php'],
    ['title' => 'ログアウト', 'icon' => '🚪', 'link' => 'logout.php'],
];

// JSONとして返す
echo json_encode([
    'status' => 'ok',
    'nav' => $navItems
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
