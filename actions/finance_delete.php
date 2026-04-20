<?php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    try {
        $stmt = $db->prepare("DELETE FROM finance WHERE id = ?");
        $stmt->execute([$id]);
        json_response('success', 'Kayıt başarıyla silindi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
