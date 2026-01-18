<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>通知</title>

<style>
body {
    margin: 0;
    background: #fff;
    font-family: "Helvetica", "Arial", sans-serif;
}

/* ===== グローバルナビ回避 ===== */
.main-area {
    margin-left: 20vw;
}

/* ===== タイトル固定 ===== */
.header {
    position: sticky;
    top: 0;
    z-index: 100;
    font-size: 22px;
    padding: 20px;
    font-weight: bold;
    background: white;
    border-bottom: 1px solid #ddd;
}

/* ===== 通知エリア ===== */
.notify-wrapper {
    max-width: 600px;
    margin: 0 auto;
    padding: 30px 15px 60px;
    position: relative;
}

/* ===== 串刺し縦線 ===== */
.timeline-line {
    position: absolute;
    left: 40px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #a3a3a3ff;
    z-index: 1;
}

/* ===== 通知1件 ===== */
.notify-block {
    margin: 30px 0;
}

/* ===== 通知カード ===== */
.notify-box {
    margin-left: 10px;
    padding: 14px 16px;
    background: #ffffff ;
    
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    z-index: 2;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

/* ===== アクションアイコン ===== */
.action-icon {
    width: 26px;
    height: 26px;
}

/* ===== ユーザーアイコン ===== */
.user-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

/* ===== 時刻 ===== */
.notify-time {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}
</style>
</head>

<body>

<header>
<?php include '../navigation/nav.php'; ?>
</header>

<div class="main-area">

    <div class="header">通知</div>

    <div class="notify-wrapper">
        <div class="timeline-line"></div>
        <div id="notifyList">読み込み中…</div>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", loadNotifications);

function loadNotifications() {
    fetch("./get_notifications.php")
        .then(res => {
            if (!res.ok) throw new Error("HTTP error");
            return res.json();
        })
        .then(list => {
            const box = document.getElementById("notifyList");
            box.innerHTML = "";

            if (!Array.isArray(list) || list.length === 0) {
                box.innerHTML = "<p style='text-align:center;'>通知はありません。</p>";
                return;
            }

            list.forEach(n => {
                const actionIcon = getActionIcon(n.type); 
                const userIcon = n.profile_img
                    ? `../profile/u_img/${n.profile_img}`
                    : `../profile/u_img/default.png`;



                const div = document.createElement("div");
                div.className = "notify-block";

                div.innerHTML = `
                    <div class="notify-box">
                        <img src="${actionIcon}" class="action-icon">
                        <img src="${userIcon}" class="user-icon">
                        <div>
                            <strong>${n.username}</strong> さんが
                            ${renderMessage(n.type)}
                            <div class="notify-time">${n.created_at}</div>
                        </div>
                    </div>
                `;

                box.appendChild(div);
            });
        })
        .catch(err => {
            document.getElementById("notifyList").innerHTML =
                "<p style='text-align:center;color:red;'>通知の取得に失敗しました</p>";
            console.error(err);
        });
}

/* ===== アクション別アイコン ===== */
function getActionIcon(type) {
    if (type === "like") return "./img/like_2.PNG";
    if (type === "follow") return "./img/default.PNG";
    if (type === "comment") return "./img/comment.PNG";
    return "./img/message.PNG";
}

function renderMessage(type) {
    if (type === "like") return "いいねしました";
    if (type === "follow") return "フォローしました";
    if (type === "comment") return "コメントしました";
    return "アクションしました";
}
</script>

</body>
</html>
