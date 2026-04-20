<?php
// ============================================================
// db.sample.php - KlinikMod Veritabanı Şablonu
// ============================================================
// Bu dosyayı db.php olarak kopyalayın ve bilgileri doldurun.
// ============================================================

$db_host = 'localhost';
$db_name = 'veritabani_adi';
$db_user = 'veritabani_kullanici';
$db_pass = 'sifre';

try {
    $db = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Veritabanı bağlantısı kurulamadı.');
}
?>
