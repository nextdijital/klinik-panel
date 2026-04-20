<?php
require_once '../functions.php';
check_login();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();

    if ($customer) {
        json_response('success', 'Müşteri bulundu.', $customer);
    } else {
        json_response('error', 'Müşteri bulunamadı.');
    }
}
?>
