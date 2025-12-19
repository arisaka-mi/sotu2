
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
}

// いいね数取得
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM PostLike WHERE post_id=?"
);
$stmt->execute([$post_id]);
$like_count = $stmt->fetchColumn();

echo json_encode([
    'status' => 'ok',
    'liked' => $liked,
    'like_count' => $like_count
]);
exit;
