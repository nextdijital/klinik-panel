<?php
require_once '../functions.php';
check_login();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // 1. Müşteri Bilgileri
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        json_response('error', 'Müşteri bulunamadı.');
    }

    // 2. Randevu Geçmişi
    $stmt_history = $db->prepare("
        SELECT a.*, s.name as service_name, s.color as service_color, 
               CONCAT(st.first_name, ' ', st.last_name) as staff_name 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        LEFT JOIN staff st ON a.staff_id = st.id 
        WHERE a.customer_id = ? AND a.is_deleted = 0 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt_history->execute([$id]);
    $history = $stmt_history->fetchAll();

    // 3. Finansal Özet (Sadece bu müşterinin randevuları ile ilişkili ödemeler)
    // Not: finance tablosunda appointment_id bazlı tutarlar toplanır.
    $stmt_finance = $db->prepare("
        SELECT SUM(f.amount) as total_paid
        FROM finance f
        JOIN appointments a ON f.appointment_id = a.id
        WHERE a.customer_id = ? AND f.type = 'income' AND a.is_deleted = 0
    ");
    $stmt_finance->execute([$id]);
    $finance_summary = $stmt_finance->fetch();

    $data = [
        'customer' => $customer,
        'history' => $history,
        'summary' => [
            'total_services' => count($history),
            'total_paid' => (float)($finance_summary['total_paid'] ?? 0)
        ]
    ];

    json_response('success', 'Geçmiş verileri çekildi.', $data);
} else {
    json_response('error', 'ID eksik.');
}
?>
