<?php
// Gzip Output Compression (Sayfa yanıt boyutunu %90+ küçülterek anında yüklenmesini sağlar)
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', '1');
}
ob_start();
// functions.php - Core functions for KlinikMod

// ============================================================
// Production Güvenlik Ayarları
// ============================================================
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
// Log'a yaz ama ekrana gösterme (sunucu log klasörüne)
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/logs/error.log');

header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Istanbul');

// Proje klasör yolunu dinamik olarak belirle
$project_path = str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', str_replace('\\', '/', __DIR__));
$base_url = '/' . trim($project_path, '/') . '/';
if ($base_url == '//') $base_url = '/';
define('BASE_URL', $base_url);

// Session Güvenlik Ayarları (Production)
session_name('KLINIKMOD_AUTH');
if (session_status() === PHP_SESSION_NONE) {
    $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL,
        'domain'   => '',
        'secure'   => $is_secure,  // HTTPS'de otomatik aktif
        'httponly' => true,         // JS ile çerez okunamaz
        'samesite' => 'Strict'      // CSRF koruması
    ]);
    session_start();
}
require_once __DIR__ . '/config/db.php';

// Otomatik Veritabanı Şema Senkronizasyonu (Eksik tablo ve sütunları otomatik tamamlar)
if (isset($db)) {
    try {
        // 1. services.total_sessions kontrolü
        $col_check = $db->query("SHOW COLUMNS FROM services LIKE 'total_sessions'")->fetch();
        if (!$col_check) {
            $db->exec("ALTER TABLE services ADD COLUMN total_sessions INT NOT NULL DEFAULT 1");
        }

        // 2. appointments.package_id ve is_package_session kontrolü
        $col_pkg = $db->query("SHOW COLUMNS FROM appointments LIKE 'package_id'")->fetch();
        if (!$col_pkg) {
            $db->exec("ALTER TABLE appointments ADD COLUMN package_id INT DEFAULT NULL");
        }
        $col_pkg_sess = $db->query("SHOW COLUMNS FROM appointments LIKE 'is_package_session'")->fetch();
        if (!$col_pkg_sess) {
            $db->exec("ALTER TABLE appointments ADD COLUMN is_package_session TINYINT(1) DEFAULT 0");
        }

        // 3. customer_packages tablosu kontrolü
        $db->exec("CREATE TABLE IF NOT EXISTS `customer_packages` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `customer_id` int(11) NOT NULL,
          `service_id` int(11) DEFAULT NULL,
          `package_name` varchar(150) NOT NULL,
          `total_sessions` int(11) NOT NULL DEFAULT 1,
          `used_sessions` int(11) NOT NULL DEFAULT 0,
          `price` decimal(10,2) NOT NULL DEFAULT 0.00,
          `payment_status` enum('paid','partial','unpaid') DEFAULT 'paid',
          `start_date` date NOT NULL,
          `end_date` date DEFAULT NULL,
          `status` enum('active','completed','cancelled') DEFAULT 'active',
          `notes` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_customer` (`customer_id`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 4. Performans indeksleri
        $idx_check = $db->query("SHOW INDEX FROM appointments WHERE Key_name = 'idx_appt_customer_service_session'")->fetch();
        if (!$idx_check) {
            $db->exec("CREATE INDEX idx_appt_customer_service_session ON appointments(customer_id, service_id, is_deleted, status, appointment_date, appointment_time, id)");
        }
        $idx_fin1 = $db->query("SHOW INDEX FROM finance WHERE Key_name = 'idx_finance_created_at'")->fetch();
        if (!$idx_fin1) {
            $db->exec("CREATE INDEX idx_finance_created_at ON finance(created_at)");
        }
        $idx_fin2 = $db->query("SHOW INDEX FROM finance WHERE Key_name = 'idx_finance_type'")->fetch();
        if (!$idx_fin2) {
            $db->exec("CREATE INDEX idx_finance_type ON finance(type)");
        }
    } catch (Exception $e) {
        // Hata durumunda akışı durdurma
    }
}

// Authentication Check
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

// Redirect if already logged in
function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'pages/dashboard.php');
        exit();
    }
}

// i18n System
function __($key) {
    global $lang_data;
    return isset($lang_data[$key]) ? $lang_data[$key] : $key;
}

// Language Loader
$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'tr';
$lang_file = __DIR__ . "/assets/lang/{$current_lang}.php";

if (file_exists($lang_file)) {
    $lang_data = include $lang_file;
} else {
    $lang_data = [];
}

// Security: Clean input
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// JSON Response helper
function json_response($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

// Calculate Revenue (Basic helper)
function get_total_revenue() {
    global $db;
    $stmt = $db->query("SELECT SUM(amount) as total FROM finance WHERE type = 'income'");
    $res = $stmt->fetch();
    return $res['total'] ?? 0;
}

// Auto-cleanup: Archive expired cancelled appointments
function archive_expired_appointments() {
    global $db;
    if (!isset($_SESSION['user_id'])) return;

    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'cancelled_retention_hours'");
    $res = $stmt->fetch();
    $retention_hours = intval($res['setting_value'] ?? 24);
    
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_hours hours"));
    
    $stmt_upd = $db->prepare("UPDATE appointments SET is_deleted = 1 
                              WHERE status = 'cancelled' 
                              AND is_deleted = 0 
                              AND updated_at < ?");
    $stmt_upd->execute([$cutoff_date]);
}

// Run cleanup on every page load if logged in
archive_expired_appointments();

// ============================================================
// Kullanıcı Yetki Kontrol Fonksiyonları
// ============================================================

// Oturumdaki kullanıcının admin olup olmadığını döner
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Belirtilen sayfa anahtarı için kullanıcının erişim yetkisini kontrol eder
// Admin her zaman true döner; diğerleri için session'daki permissions dizisine bakılır
function has_page_permission($page_key) {
    if (is_admin()) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (empty($perms) || !is_array($perms)) return false;
    return in_array($page_key, $perms);
}
?>
