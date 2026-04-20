<?php
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
