<?php
// ============================================================
// get_customer_packages.php — Müşteri Paketlerini Getir
// ============================================================
require_once __DIR__ . '/../functions.php';
check_login();

$customer_id = (int)($_GET['customer_id'] ?? 0);
$status      = clean($_GET['status'] ?? 'all'); // 'active', 'all', 'available'

if (!$customer_id) {
    json_response('error', 'Müşteri ID gereklidir.');
}

try {
    $sql = "
        SELECT cp.*, 
               s.name AS service_name, 
               s.color AS service_color,
               (cp.total_sessions - cp.used_sessions) AS remaining_sessions
        FROM customer_packages cp
        LEFT JOIN services s ON cp.service_id = s.id
        WHERE cp.customer_id = ?
    ";
    $params = [$customer_id];

    if ($status === 'active') {
        $sql .= " AND cp.status = 'active'";
    } elseif ($status === 'available') {
        // Aktif ve hala kullanılabilir seansı olanlar
        $sql .= " AND cp.status = 'active' AND (cp.total_sessions - cp.used_sessions) > 0";
    }

    $sql .= " ORDER BY cp.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $packages = $stmt->fetchAll();

    json_response('success', 'Paketler listelendi.', $packages);

} catch (PDOException $e) {
    json_response('error', 'Veritabanı hatası: ' . $e->getMessage());
}
?>