<?php
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $security_code_input = clean($_POST['security_code'] ?? '');

    // Veritabanındaki güvenlik kodunu al
    $stmt_sec = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'security_code'");
    $stmt_sec->execute();
    $security_code_db = $stmt_sec->fetchColumn();

    if (!$username || !$password || !$security_code_input) {
        json_response('error', 'Lütfen tüm alanları doldurun.');
    }

    // Önce güvenlik kodunu kontrol et
    if ($security_code_input !== $security_code_db) {
        json_response('error', 'Hatalı Giriş Anahtarı!');
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Başarılı Giriş
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['lang'] = $user['lang'];
        
        json_response('success', 'Giriş başarılı, yönlendiriliyorsunuz...');
    } else {
        json_response('error', 'Geçersiz kullanıcı adı veya şifre.');
    }
} else {
    json_response('error', 'Geçersiz istek metodu.');
}
?>
