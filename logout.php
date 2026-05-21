<?php
include 'db.php';
// Smazání tokenu z DB
if (isset($_SESSION['user_id'])) {
    $pdo->prepare("UPDATE uzivatele SET remember_token = NULL WHERE id = ?")->execute([$_SESSION['user_id']]);
}
// Smazání cookie
setcookie('remember_me', '', time() - 3600, "/");
session_destroy();
header("Location: index.php");
exit;