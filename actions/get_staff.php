<?php
require_once '../functions.php';
check_login();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM staff WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$id]);
    $staff = $stmt->fetch();

    if ($staff) {
        json_response('success', 'Personel bulundu.', $staff);
    } else {
        json_response('error', 'Personel bulunamadı.');
    }
}
?>
