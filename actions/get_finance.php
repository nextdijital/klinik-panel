<?php
require_once '../functions.php';
check_login();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM finance WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if ($record) {
        json_response('success', 'Kayıt bulundu.', $record);
    } else {
        json_response('error', 'Kayıt bulunamadı.');
    }
}
?>
