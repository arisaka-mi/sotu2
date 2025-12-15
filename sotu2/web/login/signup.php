<?php
    session_start();
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta  charset="UTF-8">
        <meta  name="description"  content="">
        <meta  name="viewport"  content="width=device-width, initial-scale=1">
        <title>新規登録</title>
        <style>
            body {
                display: flex;
                justify-content: center; /* 横方向中央 */
                align-items: center;    /* 縦方向中央 */
                height: 100vh;          /* 画面の高さ全体を使う */
                margin: 0;
                font-family: sans-serif;
                background: url('img/gazo.png') no-repeat center center/cover; /* ← 背景画像を設定 */
            }

           main {
                background-color: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(15px);
                border: 1px solid rgba(255, 255, 255, 0.3);
                width: 350px;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
                text-align: center;
            }


            input {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                font-size: 1em;
                border: 1px solid #ccc;
                border-radius: 5px;
                box-sizing: border-box;
            }

            button {
                display: block;
                margin: 20px auto 10px auto;
                width: 120px;
                background: linear-gradient(135deg, #FFF7D4, #FFDDDD);
                color: #333;
                border: none;
                padding: 12px 0;
                font-size: 1em;
                border-radius: 30px;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            }

            button:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
                filter: brightness(1.05);
            }

            form p {
            text-align: center; /* 中央寄せ */
            margin-top: 10px;   /* ボタンとの間隔 */
            margin-bottom: 0;    /* 下の余白をなくす */
            font-size: 0.9em;   /* リンクの文字サイズを少し小さく */
            }

            form p a {
                color: #007bff;
                text-decoration: none;
            }

            form p a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <header>
            <!--グローバルナビ未完成-->
        </header>
        <main>
            <h1>新規会員登録</h1>
            <form action="register.php" method="post">
                <!--名前-->
                <div class="u_name">
                <label>
                    <input type="text" name="u_name" required placeholder="Name">
                </label>
                </div>
                <!--ユーザーネーム-->
                <div class="u_name_id">
                <label>
                    <input type="text" name="u_name_id" required placeholder="User-Name">
                </label>
                </div>
                <!--Eメール-->
                <div class="email">
                <label>
                    <input type="text" name="email" required placeholder="Email Address">
                </label>
                </div>
                <!--電話番号-->
                <div class="pwd">
                <label>
                    <input type="password" name="pwd" required placeholder="Password">
                </label>
                </div>
                <button type="submit">登録する</button>
            </form>
            <p>すでに登録済みの方は<a href="login_from.php">こちら</a></p>
            
        </main>
    </body>
</html>