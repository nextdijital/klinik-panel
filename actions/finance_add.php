<?php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $type = clean($_POST['type'] ?? '');
    $description = clean($_POST['description'] ?? '');

    if ($amount <= 0 || !in_array($type, ['income', 'expense']) || empty($description)) {
        json_response('error', 'Lütfen geçerli bir tutar ve açıklama girin.');
    }

    try {
        $payment_method = clean($_POST['payment_method'] ?? 'cash');
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO finance (amount, type, description, payment_method, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$amount, $type, $description, $payment_method, $now]);
        json_response('success', 'İşlem başarıyla kaydedildi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
