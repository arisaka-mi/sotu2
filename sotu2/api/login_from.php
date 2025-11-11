<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

// APIとして返す内容
$response = [
    'status' => 'ok',
    'message' => 'ログインページ設定情報',
    'form' => [
        'action' => 'login.php',
        'method' => 'POST',
        'fields' => [
            [
                'name' => 'mail',
                'type' => 'text',
                'label' => 'メールアドレス',
                'required' => true
            ],
            [
                'name' => 'pass',
                'type' => 'password',
                'label' => 'パスワード',
                'required' => true
            ]
        ],
        'submit' => 'ログイン',
        'link' => [
            'text' => '新規登録',
            'url' => 'signup.php'
        ]
    ]
];

// JSONとして出力
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
