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
            font-family: sans-serif;
            background: #f5f5f5;
            margin: 0;
        }

        .header {
            background: white;
            padding: 15px 20px;
            font-size: 20px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }

        .notify-container {
            max-width: 600px;
            margin: 30px auto;
        }

        .notify-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            display: flex;
            gap: 12px;
        }

        .notify-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        .notify-text {
            flex: 1;
        }

        .notify-time {
            color: #777;
            font-size: 12px;
            margin-top: 4px;
        }

        .post-preview {
            margin-top: 8px;
            padding: 8px;
            background: #fafafa;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
    </style>
</head>
<body>
<?php
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
        padding: 0;
        background: #f5f5f5;
        font-family: "Helvetica", "Arial", sans-serif;
    }

    .header {
        font-size: 22px;
        padding: 20px;
        font-weight: bold;
        background: white;
        border-bottom: 1px solid #ddd;
    }

    /* メインコンテナ */
    .notify-wrapper {
        max-width: 480px;
        margin: 0 auto;
        padding: 20px 15px;
        position: relative;
    }

    /* 縦の線 */
    .timeline-line {
        position: absolute;
        left: 30px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #ccc;
        z-index: 0;
    }

    /* 通知ブロック */
    .notify-block {
        position: relative;
        margin: 25px 0;
        padding-left: 60px;
        display: flex;
        align-items: center;
    }

    /* ブロックの背景部分（吹き出し） */
    .notify-box {
        background: #e5e5e5;
        padding: 12px 16px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 2;
    }

    /* アイコン（●の部分） */
    .icon-circle {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: black;
    }

    /* 各通知アイコン */
    .notify-icon {
        font-size: 22px;
        margin-right: 5px;
    }

    .notify-time {
        font-size: 12px;
        color: #666;
        margin-top: 4px;
    }

</style>
</head>
<body>

<div class="header">通知</div>

<div class="notify-wrapper">
    <div class="timeline-line"></div>

    <div id="notifyList">読み込み中…</div>
</div>

<script>
function loadNotifications() {
    fetch("get_notifications.php")
        .then(res => res.json())
        .then(list => {
            const box = document.getElementById("notifyList");
            box.innerHTML = "";

            if (list.length === 0) {
                box.innerHTML = "<p style='text-align:center;'>通知はありません。</p>";
                return;
            }

            list.forEach(n => {
                let icon = "";
                if (n.type === "like")     icon = "♡";
                if (n.type === "follow")   icon = "👤";
                if (n.type === "comment")  icon = "💬";

                const div = document.createElement("div");
                div.className = "notify-block";

                div.innerHTML = `
                    <div class="notify-box">
                        <span class="notify-icon">${icon}</span>
                        <div class="icon-circle"></div>
                        <div>
                            ${renderMessage(n)}
                            <div class="notify-time">${n.created_at}</div>
                        </div>
                    </div>
                `;

                box.appendChild(div);
            });
        });
}

function renderMessage(n) {
    if (n.type === "like") {
        return `<strong>${n.username}</strong> さんがいいねしました`;
    }
    if (n.type === "follow") {
        return `<strong>${n.username}</strong> さんがフォローしました`;
    }
    if (n.type === "comment") {
        return `<strong>${n.username}</strong> さんがコメントしました`;
    }
    return "不明な通知";
}

loadNotifications();
</script>

</body>
</html>
