<?php
// ============================================================
// package_add.php — Müşteriye Yeni Paket Tanımlama
// ============================================================
require_once __DIR__ . '/../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id    = (int)($_POST['customer_id'] ?? 0);
    $service_id     = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
    $package_name   = clean($_POST['package_name'] ?? '');
    $total_sessions = (int)($_POST['total_sessions'] ?? 0);
    $price          = (float)($_POST['price'] ?? 0);
    $payment_status = clean($_POST['payment_status'] ?? 'paid');
    $start_date     = clean($_POST['start_date'] ?? date('Y-m-d'));
    $end_date       = !empty($_POST['end_date']) ? clean($_POST['end_date']) : null;
    $notes          = clean($_POST['notes'] ?? '');
    $add_to_finance = !empty($_POST['add_to_finance']) && $_POST['add_to_finance'] == '1';
    $payment_method = clean($_POST['payment_method'] ?? 'cash');

    if (!$customer_id) {
        json_response('error', 'Lütfen geçerli bir müşteri seçin.');
    }
    if (empty($package_name)) {
        json_response('error', 'Paket adı zorunludur.');
    }
    if ($total_sessions <= 0) {
        json_response('error', 'Toplam seans sayısı en az 1 olmalıdır.');
    }
    if (empty($start_date)) {
        $start_date = date('Y-m-d');
    }

    try {
        $db->beginTransaction();

        // 1. Müşteri doğrula
        $c_stmt = $db->prepare("SELECT id, first_name, last_name FROM customers WHERE id = ? AND is_deleted = 0");
        $c_stmt->execute([$customer_id]);
        $customer = $c_stmt->fetch();
        if (!$customer) {
            throw new Exception('Müşteri bulunamadı.');
        }

        // 2. Paketi kaydet
        $stmt = $db->prepare("
            INSERT INTO customer_packages 
            (customer_id, service_id, package_name, total_sessions, used_sessions, price, payment_status, start_date, end_date, status, notes) 
            VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, 'active', ?)
        ");
        $stmt->execute([
            $customer_id,
            $service_id,
            $package_name,
            $total_sessions,
            $price,
            $payment_status,
            $start_date,
            $end_date,
            $notes
        ]);
        $package_id = $db->lastInsertId();

        // 3. İsteğe bağlı finans kaydı
        if ($add_to_finance && $price > 0 && $payment_status === 'paid') {
            $cust_name = trim($customer['first_name'] . ' ' . $customer['last_name']);
            $desc = "Paket Satışı: {$package_name} ({$cust_name})";
            $now = date('Y-m-d H:i:s');
            $f_stmt = $db->prepare("INSERT INTO finance (amount, type, description, payment_method, created_at) VALUES (?, 'income', ?, ?, ?)");
            $f_stmt->execute([$price, $desc, $payment_method, $now]);
        }

        $db->commit();
        json_response('success', 'Paket başarıyla tanımlandı.', ['package_id' => $package_id]);
    } catch (Exception $e) {
        $db->rollBack();
        json_response('error', $e->getMessage());
    }
} else {
    json_response('error', 'Geçersiz istek metodu.');
}
?>