<?php
// アップロードディレクトリ
$uploadDir = "uploads/";

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 画像ファイル処理
    $imageName = time() . "_" . basename($_FILES["image"]["name"]);
    $targetFile = $uploadDir . $imageName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {

        // DB保存
        $description = $_POST["description"] ?? "";
        $tags = $_POST["tags"] ?? "";

        try {
            $pdo = new PDO("mysql:host=localhost;dbname=sotu2;charset=utf8", "root", "");
            $sql = "INSERT INTO posts (image_path, description, tags) VALUES (:image, :description, :tags)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindValue(":image", $targetFile);
            $stmt->bindValue(":description", $description);
            $stmt->bindValue(":tags", $tags);

            $stmt->execute();

            echo "投稿が完了しました！<br>";
            echo "<a href='index.php'>戻る</a>";

        } catch (PDOException $e) {
            echo "データベースエラー：" . $e->getMessage();
        }

    } else {
        echo "画像アップロードに失敗しました。";
    }
}
?>
