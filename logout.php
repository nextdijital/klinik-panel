<?php
require_once 'functions.php';

// Önbelleği devre dışı bırak (Redirect cache sorununu önler)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Tüm session verilerini temizle
$_SESSION = array();

// Session çerezini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    // Hem BASE_URL hem de kök dizin için çerezi silmeyi dene
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    setcookie(session_name(), '', time() - 42000, '/', $params["domain"]);
}

// Oturumu sonlandır
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Giriş sayfasına yönlendir (Mutlak URL garantisi)
header('Location: ' . BASE_URL . 'index.php');
exit();
?>
