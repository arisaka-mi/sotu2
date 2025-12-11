<?php
session_start();
// require_once('../login/config.php'); // DB接続

// ログインチェック
// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../login/login_form.php');
//     exit();
// }

// $user_id = $_SESSION['user_id'];

// DBからユーザー情報取得
// $stmt = $pdo->prepare("SELECT * FROM User WHERE user_id = :user_id");
// $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
// $stmt->execute();
// $user = $stmt->fetch(PDO::FETCH_ASSOC);

// if (!$user) {
//     echo "ユーザー情報が見つかりません。";
//     exit();
// }

// エラー初期化
$error = "";
 ?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>診断メニュー（中央配置＋丸角安定版）</title>
<style>
:root{
  --edge: clamp(40px, 10vh, 90px);     /* 上/右/下のオレンジ余白 */
  --white-h: max(13vh, 100px);        /* 下1/4白：25vh or 160px */
}

/* 全体背景 */
html,body{
  height:100%;
  margin:0;
  background:#FFA07A; /* サーモンオレンジ */
  font-family: "Hiragino Kaku Gothic Pro", Meiryo, sans-serif;
}

/* 画面下の白を固定表示 */
.bottom-global-white{
  position:fixed;
  left:0;
  right:0;
  bottom:0;
  height:var(--white-h);
  background:#fff;
  z-index:1;
}

/* ページ全体の内側余白を確保して右側に白四角を置く */
.page-wrap{
  min-height:100vh;
  box-sizing:border-box;
  padding-top:var(--edge);
  padding-right:var(--edge);
  padding-left:var(--edge);

  /* 下部白の高さ＋余白分だけ container が白と重ならないようにする */
  padding-bottom: calc(var(--white-h) + var(--edge));

  display:flex;
  justify-content:flex-end;
  align-items:flex-start;
}

/* 白い四角（診断メニュー） */
.container{
  width:45vw;
  max-width:650px;
  min-width:300px;
  background:#fff;
  border-radius:25px;
  padding:35px;
  box-shadow:0 10px 30px rgba(0,0,0,0.12);
  box-sizing:border-box;
  z-index:2;

  /* ★★ 中央配置のため flex を有効化 ★★ */
  display:flex;
  flex-direction:column;
  align-items:center;   /* 水平中央 */
  justify-content:center; /* 垂直中央 */

  text-align:center;
}

/* テキスト */
h2 { 
  margin:0 0 10px 0; 
  font-size:24px;
}
p.lead { 
  margin:0 0 20px 0; 
  color:#555; 
  font-size:15px;
}

/* ボタン */
.menu-btn{
  display:block;
  width:80%;         /* 白い四角の中で80%幅 */
  padding:14px 16px;
  margin-bottom:12px;
  font-size:17px;
  border-radius:12px;
  color:#fff;
  text-decoration:none;
  text-align:center;
  cursor:pointer;
}

/* 色 */
.menu-btn.bone{ background:#FF9800; }
.menu-btn.bone:hover{ background:#FFB74D; }
.menu-btn.color{ background:#6495ED; }
.menu-btn.color:hover{ background:#87CEFA; }

/* 小さい画面用 */
@media (max-width:1000px){
  .page-wrap{
    padding-left:clamp(12px,3vw,30px);
    padding-right:clamp(12px,3vw,30px);
  }
  .container{
    width:90vw;
    max-width:none;
    border-radius:16px;
    margin-bottom:16px;
  }
}

/* スマホ用（下の白を非表示） */
@media (max-width:480px){
  .bottom-global-white{
    height:0;
    display:none;
  }
}
</style>
</head>
<body>

<!-- 下1/4の白固定 -->
<div class="bottom-global-white"></div>

<!-- 右側に白四角を置くエリア -->
<div class="page-wrap">

  <!-- 白い診断メニュー（中央揃え済み） -->
  <div class="container">
      <h2>診断メニュー</h2>
      <p class="lead">どちらの診断を行いますか？</p>

      <a href="body_type.php" class="menu-btn bone">骨格診断</a>
      <a href="parsonal_color.php" class="menu-btn color">パーソナルカラー診断</a>
      <a href="./profile/profile_setting.php">もどる</a>
  </div>

</div>

</body>
</html>
