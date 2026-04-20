<?php
// actions/staff_add.php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = clean($_POST['first_name'] ?? '');
    $last_name = clean($_POST['last_name'] ?? '');
    $phone = clean($_POST['phone'] ?? '');

    if (empty($first_name) || empty($last_name)) {
        json_response('error', 'İsim ve soyisim alanları zorunludur.');
    }

    try {
        $stmt = $db->prepare("INSERT INTO staff (first_name, last_name, phone) VALUES (?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $phone]);
        json_response('success', 'Personel başarıyla eklendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
