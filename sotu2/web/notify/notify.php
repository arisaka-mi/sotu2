<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

$pdo = new PDO('mysql:host=localhost;dbname=noti_test;charset=utf8', 'notified_user_id', 'actor_user_id','notify_type','tweet_id','is_read');




>