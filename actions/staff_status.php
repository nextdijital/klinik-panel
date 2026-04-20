<?php
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
