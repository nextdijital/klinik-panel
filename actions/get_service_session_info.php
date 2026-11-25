<?php
require_once '../functions.php';
check_login();

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

if (!$service_id) {
    json_response('error', 'Hizmet ID eksik.');
}

// Hizmeti çek
$stmt_svc = $db->prepare("SELECT id, name, price, total_sessions, color FROM services WHERE id = ? AND is_deleted = 0");
$stmt_svc->execute([$service_id]);
$service = $stmt_svc->fetch();

if (!$service) {
    json_response('error', 'Hizmet bulunamadı.');
}

$total_sessions = (int)($service['total_sessions'] ?? 1);

if ($total_sessions <= 1) {
    json_response('success', 'Tek seanslık hizmet.', [
        'is_package' => false,
        'total_sessions' => 1
    ]);
}

$completed_count = 0;
if ($customer_id > 0) {
    $stmt_cnt = $db->prepare("
        SELECT COUNT(*) as cnt 
        FROM appointments 
        WHERE customer_id = ? AND service_id = ? AND is_deleted = 0 AND status != 'cancelled'
    ");
    $stmt_cnt->execute([$customer_id, $service_id]);
    $completed_count = (int)$stmt_cnt->fetchColumn();
}

$next_num = $completed_count + 1;
$used_in_cycle = (($next_num - 1) % $total_sessions) + 1;
$remaining_after = $total_sessions - $used_in_cycle;
$cycle = floor(($next_num - 1) / $total_sessions) + 1;

$msg = "{$service['name']} ({$total_sessions} Seanslık Paket): Bu randevu {$used_in_cycle}. Seans olacak (" . ($remaining_after > 0 ? "Kalan: {$remaining_after} Seans" : "Paketin Son Seansı!") . ")";
if ($cycle > 1) {
    $msg .= " • [{$cycle}. Paket Döngüsü]";
}

json_response('success', 'Paket bilgisi alındı.', [
    'is_package' => true,
    'service_name' => $service['name'],
    'total_sessions' => $total_sessions,
    'current_count' => $completed_count,
    'next_session' => $used_in_cycle,
    'remaining_after' => $remaining_after,
    'cycle' => $cycle,
    'message' => $msg
]);
