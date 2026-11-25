<?php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = clean($_POST['first_name']);
    $last_name = clean($_POST['last_name']);
    $phone = clean($_POST['phone']);
    $email = clean($_POST['email'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($phone)) {
        json_response('error', 'Lütfen tüm zorunlu alanları doldurun.');
    }

    // Mükerrer kayıt kontrolü (Ad, Soyad ve Telefon aynıysa ve silinmemişse)
    $check = $db->prepare("SELECT id FROM customers WHERE first_name = ? AND last_name = ? AND phone = ? AND is_deleted = 0");
    $check->execute([$first_name, $last_name, $phone]);
    if ($check->fetch()) {
        json_response('error', 'Bu isim, soyisim ve telefon numarasına sahip bir müşteri zaten kayıtlı.');
    }

    try {
        $stmt = $db->prepare("INSERT INTO customers (first_name, last_name, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $phone, $email]);
        $new_id = $db->lastInsertId();
        
        json_response('success', 'Müşteri başarıyla eklendi.', [
            'id' => $new_id,
            'full_name' => $first_name . ' ' . $last_name
        ]);
    } catch (PDOException $e) {
        json_response('error', 'Veritabanı hatası: ' . $e->getMessage());
    }
} else {
    json_response('error', 'Geçersiz istek.');
}
?>
