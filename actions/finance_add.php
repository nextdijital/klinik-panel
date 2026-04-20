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
        $stmt = $db->prepare("INSERT INTO finance (amount, type, description) VALUES (?, ?, ?)");
        $stmt->execute([$amount, $type, $description]);
        json_response('success', 'İşlem başarıyla kaydedildi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
