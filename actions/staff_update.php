<?php
// actions/staff_update.php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $first_name = clean($_POST['first_name'] ?? '');
    $last_name = clean($_POST['last_name'] ?? '');
    $phone = clean($_POST['phone'] ?? '');

    if (empty($first_name) || empty($last_name)) {
        json_response('error', 'İsim ve soyisim alanları zorunludur.');
    }

    try {
        $stmt = $db->prepare("UPDATE staff SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $phone, $id]);
        json_response('success', 'Personel bilgileri güncellendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
<!-- Separator for another file -->
<?php
// actions/staff_delete.php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    try {
        $stmt = $db->prepare("UPDATE staff SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);
        json_response('success', 'Personel arşivlendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
<!-- Separator for another file -->
<?php
// actions/staff_status.php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $status = (int)$_POST['status'];

    try {
        $stmt = $db->prepare("UPDATE staff SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        json_response('success', 'Personel durumu güncellendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
