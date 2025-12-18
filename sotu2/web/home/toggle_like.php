
//session_start();
// require_once '../login/config.php';

//if (!isset($_SESSION['user_id'])) {
  //  exit('ログインしてください');
//} 

// $user_id = $_SESSION['user_id'];
// $post_id = $_POST['post_id'] ?? null;

//if (!$post_id) exit('投稿がありません');

// すでにいいねしているか確認
// $stmt = $pdo->prepare("SELECT * FROM PostLike WHERE user_id = ? AND post_id = ?");
// $stmt->execute([$user_id, $post_id]);
// $liked = $stmt->fetch();

// if ($liked) {
//     // いいね解除
//     $stmt = $pdo->prepare("DELETE FROM PostLike WHERE user_id = ? AND post_id = ?");
//     $stmt->execute([$user_id, $post_id]);
//     $status = "unliked";
// } else {
//     // いいね追加
//     $stmt = $pdo->prepare("INSERT INTO PostLike (user_id, post_id) VALUES (?, ?)");
//     $stmt->execute([$user_id, $post_id]);
//     $status = "liked";

    // ★ 通知作成
//     $stmt = $pdo->prepare("
//         INSERT INTO Notifications (user_id, from_user_id, type, post_id)
//         VALUES ((SELECT user_id FROM Post WHERE post_id = ?), ?, 'like', ?)
//     ");
//     $stmt->execute([$post_id, $user_id, $post_id]);
// }

// いいね数取得
// $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM PostLike WHERE post_id = ?");
// $stmt->execute([$post_id]);
// $like_count = $stmt->fetch()['cnt'];

// リダイレクトして元ページに戻す
// header("Location: " . $_SERVER['HTTP_REFERER']);
// exit;
 




 <?php
session_start();
require_once '../login/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'not login']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;

if (!$post_id) {
<<<<<<< HEAD
    echo json_encode(['status'=>'error','message'=>'no post id']);
    exit;
}

// いいね済み確認
$sql = "SELECT * FROM PostLike WHERE post_id=? AND user_id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id, $user_id]);

if ($stmt->fetch()) {
    // 解除
    $pdo->prepare(
        "DELETE FROM PostLike WHERE post_id=? AND user_id=?"
    )->execute([$post_id, $user_id]);
    $liked = false;
} else {
    // 追加
    $pdo->prepare(
        "INSERT INTO PostLike (post_id,user_id) VALUES (?,?)"
    )->execute([$post_id, $user_id]);
    $liked = true;
=======
    exit('投稿がありません');
}

try {
    $pdo->beginTransaction();

    // 投稿者取得
    $stmt = $pdo->prepare("SELECT user_id FROM Post WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post_owner = $stmt->fetchColumn();

    if (!$post_owner) {
        throw new Exception('投稿が存在しません');
    }

    // すでにいいねしているか
    $stmt = $pdo->prepare(
        "SELECT 1 FROM PostLike WHERE user_id = ? AND post_id = ?"
    );
    $stmt->execute([$user_id, $post_id]);
    $liked = $stmt->fetchColumn();

    if ($liked) {
        // いいね解除
        $stmt = $pdo->prepare(
            "DELETE FROM PostLike WHERE user_id = ? AND post_id = ?"
        );
        $stmt->execute([$user_id, $post_id]);

        $status = 'unliked';

    } else {
        // いいね追加
        $stmt = $pdo->prepare(
            "INSERT INTO PostLike (user_id, post_id) VALUES (?, ?)"
        );
        $stmt->execute([$user_id, $post_id]);

        $status = 'liked';

        // 自分の投稿には通知しない
        if ($post_owner != $user_id) {

            // 既存の未読いいね通知があるか確認
            $stmt = $pdo->prepare("
                SELECT id FROM Notifications
                WHERE user_id = ?
                  AND from_user_id = ?
                  AND type = 'like'
                  AND post_id = ?
                  AND is_read = 0
            ");
            $stmt->execute([$post_owner, $user_id, $post_id]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                // 通知作成
                $stmt = $pdo->prepare("
                    INSERT INTO Notifications (user_id, from_user_id, type, post_id)
                    VALUES (?, ?, 'like', ?)
                ");
                $stmt->execute([$post_owner, $user_id, $post_id]);
            }
        }
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    exit('エラーが発生しました');
>>>>>>> 679cc81d30a5b30121d6f6db2e85c595f66e4289
}

// いいね数取得
$stmt = $pdo->prepare(
<<<<<<< HEAD
    "SELECT COUNT(*) FROM PostLike WHERE post_id=?"
=======
    "SELECT COUNT(*) FROM PostLike WHERE post_id = ?"
>>>>>>> 679cc81d30a5b30121d6f6db2e85c595f66e4289
);
$stmt->execute([$post_id]);
$like_count = $stmt->fetchColumn();

<<<<<<< HEAD
echo json_encode([
    'status' => 'ok',
    'liked' => $liked,
    'like_count' => $like_count
]);
=======
// 元ページに戻る
header("Location: " . $_SERVER['HTTP_REFERER']);
>>>>>>> 679cc81d30a5b30121d6f6db2e85c595f66e4289
exit;
