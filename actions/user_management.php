<?php
// ============================================================
// user_management.php — Kullanıcı Yönetimi AJAX Action Dosyası
// ============================================================
require_once __DIR__ . '/../functions.php';
check_login();

// Sadece admin erişebilir
if (!is_admin()) {
    json_response('error', 'Yetkisiz erişim.');
}

$action          = $_POST['action']   ?? '';
$LOCKED_USERNAME = 'yarenipek';        // Bu kullanıcı silinemez / role'ü değiştirilemez

// İzin verilebilecek sayfalar
$valid_pages = ['dashboard','appointments','customers','services','staff','finance','settings'];

switch ($action) {

    // --------------------------------------------------------
    // KULLANICI EKLE
    // --------------------------------------------------------
    case 'add_user':
        $full_name = trim($_POST['full_name'] ?? '');
        $username  = trim($_POST['username']  ?? '');
        $password  = $_POST['password']       ?? '';
        $role      = in_array($_POST['role'] ?? '', ['admin','staff']) ? $_POST['role'] : 'staff';
        $perms_raw = $_POST['permissions']    ?? [];

        if (!$full_name || !$username || !$password) {
            json_response('error', 'Ad soyad, kullanıcı adı ve şifre zorunludur.');
        }
        if (strlen($password) < 6) {
            json_response('error', 'Şifre en az 6 karakter olmalıdır.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            json_response('error', 'Kullanıcı adı yalnızca harf, rakam ve alt çizgi içerebilir.');
        }

        // Aynı kullanıcı adı var mı?
        $chk = $db->prepare("SELECT id FROM users WHERE username = ?");
        $chk->execute([$username]);
        if ($chk->fetch()) {
            json_response('error', 'Bu kullanıcı adı zaten kullanılıyor.');
        }

        // İzinleri doğrula ve JSON'a çevir
        $perms = array_values(array_intersect((array)$perms_raw, $valid_pages));
        $perms_json = $role === 'admin' ? null : json_encode($perms, JSON_UNESCAPED_UNICODE);

        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role, permissions, lang)
                               VALUES (?, ?, ?, ?, ?, 'tr')");
        $stmt->execute([$username, $hashed, $full_name, $role, $perms_json]);

        json_response('success', 'Kullanıcı başarıyla oluşturuldu.', ['id' => $db->lastInsertId()]);

    // --------------------------------------------------------
    // KULLANICI GÜNCELLE
    // --------------------------------------------------------
    case 'update_user':
        $user_id   = (int)($_POST['user_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $username  = trim($_POST['username']  ?? '');
        $password  = $_POST['password']       ?? '';
        $role      = in_array($_POST['role'] ?? '', ['admin','staff']) ? $_POST['role'] : 'staff';
        $perms_raw = $_POST['permissions']    ?? [];

        if (!$user_id || !$full_name || !$username) {
            json_response('error', 'Gerekli alanlar eksik.');
        }
        if ($password && strlen($password) < 6) {
            json_response('error', 'Şifre en az 6 karakter olmalıdır.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            json_response('error', 'Kullanıcı adı yalnızca harf, rakam ve alt çizgi içerebilir.');
        }

        // Düzenlenecek kullanıcıyı getir
        $user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $target_user = $user_stmt->fetch();
        if (!$target_user) {
            json_response('error', 'Kullanıcı bulunamadı.');
        }

        // Kilitli kullanıcı korumaları
        if ($target_user['username'] === $LOCKED_USERNAME) {
            $role     = 'admin';  // Role değiştirilemez
            $username = $LOCKED_USERNAME; // Kullanıcı adı değiştirilemez
        }

        // Aynı kullanıcı adı başka biri tarafından alınmış mı?
        $chk = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $chk->execute([$username, $user_id]);
        if ($chk->fetch()) {
            json_response('error', 'Bu kullanıcı adı zaten kullanılıyor.');
        }

        // İzinleri doğrula
        $perms = array_values(array_intersect((array)$perms_raw, $valid_pages));
        $perms_json = $role === 'admin' ? null : json_encode($perms, JSON_UNESCAPED_UNICODE);

        if ($password) {
            // Şifre güncelleniyor
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("UPDATE users SET full_name=?, username=?, password=?, role=?, permissions=? WHERE id=?");
            $stmt->execute([$full_name, $username, $hashed, $role, $perms_json, $user_id]);
        } else {
            // Şifre güncellenmeden kaydet
            $stmt = $db->prepare("UPDATE users SET full_name=?, username=?, role=?, permissions=? WHERE id=?");
            $stmt->execute([$full_name, $username, $role, $perms_json, $user_id]);
        }

        json_response('success', 'Kullanıcı başarıyla güncellendi.');

    // --------------------------------------------------------
    // KULLANICI SİL
    // --------------------------------------------------------
    case 'delete_user':
        $user_id = (int)($_POST['user_id'] ?? 0);
        if (!$user_id) {
            json_response('error', 'Geçersiz kullanıcı.');
        }

        $user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $target_user = $user_stmt->fetch();
        if (!$target_user) {
            json_response('error', 'Kullanıcı bulunamadı.');
        }

        // Kilitli kullanıcı silinemez
        if ($target_user['username'] === $LOCKED_USERNAME) {
            json_response('error', 'Bu kullanıcı sistem yöneticisi olduğu için silinemez.');
        }

        // Kendini silemez
        if ($target_user['id'] == $_SESSION['user_id']) {
            json_response('error', 'Kendi hesabınızı silemezsiniz.');
        }

        $del = $db->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$user_id]);

        json_response('success', 'Kullanıcı başarıyla silindi.');

    // --------------------------------------------------------
    // KULLANICI VERİSİ GETİR (düzenleme modalı için)
    // --------------------------------------------------------
    case 'get_user':
        $user_id = (int)($_POST['user_id'] ?? 0);
        $stmt = $db->prepare("SELECT id, username, full_name, role, permissions FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();
        if (!$u) {
            json_response('error', 'Kullanıcı bulunamadı.');
        }
        $u['permissions_arr'] = $u['permissions'] ? json_decode($u['permissions'], true) : [];
        json_response('success', 'OK', $u);

    default:
        json_response('error', 'Geçersiz işlem.');
}
?>
