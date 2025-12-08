<?php
// ★ config.php を読み込む
require_once __DIR__ . '/../login/config.php';

$keyword = $_GET['keyword'] ?? '';
$kw_like = '%' . $keyword . '%';

$sql = "
SELECT DISTINCT p.*
FROM Post p
LEFT JOIN PostTag pt ON p.post_id = pt.post_id
LEFT JOIN Tag t ON pt.tag_id = t.tag_id
WHERE
    p.content_text LIKE :kw_like
    OR t.tag_name LIKE :kw_like
    OR SOUNDEX(t.tag_name) = SOUNDEX(:keyword)
";


$stmt = $pdo->prepare($sql);
$stmt->bindValue(':keyword', $keyword);
$stmt->bindValue(':kw_like', $kw_like);
$stmt->execute();

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// SESSION で渡す
session_start();
$_SESSION['posts'] = $posts;
$_SESSION['keyword'] = $keyword;

// 結果表示へ
header("Location: search_hit.php");
exit;
