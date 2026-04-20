<?php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $first_name = clean($_POST['first_name'] ?? '');
    $last_name = clean($_POST['last_name'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $email = clean($_POST['email'] ?? '');

    if (!$id || empty($first_name) || empty($last_name) || empty($phone)) {
        json_response('error', 'Lütfen tüm alanları doldurun.');
    }

    try {
        $stmt = $db->prepare("UPDATE customers SET first_name = ?, last_name = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $phone, $email, $id]);
        json_response('success', 'Müşteri bilgileri güncellendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
