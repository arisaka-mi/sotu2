<?php
session_start();
$_SESSION = array();
session_destroy();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta http-equiv="refresh" content="3;url=login_from.php">
<title>ログアウト</title>
<style>
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin: 0;
            font-family: sans-serif;
        }
        h1 {
            font-size: 2em;
            margin-bottom: 1em;
        }
</style>
</head>
<body>
  <h1>ログアウトしました。 。 。 </h1>
</body>
</html>
