<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ログイン画面</title>
<style>
    body {
        margin: 0;
        height: 100vh;
        display: flex;
        font-family: sans-serif;
        background-image: url("img/gazo.png");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .left, .right {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .left img {
        max-width: 80%;
        height: auto;
        border-radius: 10px; 
        margin-left: 100px; 
    }

    .right main {
        background-color: rgba(255, 255, 255, 0.3); /* 半透明の白背景 */
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 10px;
        padding: 40px 30px;
        backdrop-filter: blur(10px); /* ガラス風のぼかし効果 */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .right main form {
        width: 350px;
        margin: 0 auto;
    }

    input {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        font-size: 1em;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
        background: rgba(255,255,255,0.9);
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
        text-align: center;
        margin-top: 10px;
        margin-bottom: 0;
        font-size: 0.9em;
    }

    form p a {
        color: #007bff;
        text-decoration: none;
    }

    form p a:hover {
        text-decoration: underline;
    }

    /* スマホ・タブレット対応 */
    @media (max-width: 768px) {
        .left {
            display: none;
        }
        body {
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .right {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .right main form {
            width: 90%;
        }
    }

    /* ===== 雲ベース ===== */
.clouds {
    position: fixed;
    bottom: -10vh;
    left: 0;
    width: 100%;
    height: 40vh;
    background: #ffffff;
    border-top-left-radius: 50% 120px;
    border-top-right-radius: 50% 120px;
    z-index: -1;
}

/* ===== 左〜中央 ===== */
.clouds::before {
    content: "";
    position: absolute;
    top: -20vh;
    left: -10vw;
    width: clamp(220px, 22vw, 320px);
    height: clamp(220px, 22vw, 320px);
    background: #ffffff;
    border-radius: 50%;
    box-shadow:
        15vw 4vh 0 2vw #ffffff,
        30vw 2vh 0 1.8vw #ffffff,
        45vw 6vh 0 2.6vw #ffffff,
        60vw 3vh 0 2vw #ffffff,
        75vw 7vh 0 2.8vw #ffffff,
        90vw 4vh 0 2.2vw #ffffff,
        105vw 6vh 0 3vw #ffffff;
}

/* ===== 右〜中央 ===== */
.clouds::after {
    content: "";
    position: absolute;
    top: -22vh;
    right: -12vw;
    width: clamp(240px, 24vw, 340px);
    height: clamp(240px, 24vw, 340px);
    background: #ffffff;
    border-radius: 50%;
    box-shadow:
        -18vw 5vh 0 2vw #ffffff,
        -34vw 3vh 0 1.6vw #ffffff,
        -50vw 7vh 0 2.8vw #ffffff,
        -66vw 4vh 0 2vw #ffffff,
        -82vw 8vh 0 3vw #ffffff,
        -98vw 5vh 0 2.4vw #ffffff,
        -114vw 7vh 0 3.2vw #ffffff;
}




</style>
</head>
<body>
    <div class="left">
        <!--<img class="logo" src="img/gazo.png" alt="BeautyConnect">-->
    </div>
    <div class="right">
        <main>
            <form action="login.php" method="post">
                <div>
                    <label>
                        <input type="text" name="email" required placeholder="Email Address">
                    </label>
                </div>
                <div>
                    <label>
                        <input type="password" name="pwd" required placeholder="Password">
                    </label>
                </div>
                <button type="submit">Login</button>
                <p>アカウントを持っていない場合 <a href="signup.php">登録する</a></p>
            </form>
        </main>
    </div>
    <div class="clouds"></div>
</body>
</html>
