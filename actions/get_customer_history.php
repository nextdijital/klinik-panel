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

    // 4. Tanımlı Paketler
    $stmt_pkg = $db->prepare("
        SELECT cp.*, s.name as service_name, s.color as service_color,
               (cp.total_sessions - cp.used_sessions) as remaining_sessions
        FROM customer_packages cp
        LEFT JOIN services s ON cp.service_id = s.id
        WHERE cp.customer_id = ?
        ORDER BY cp.created_at DESC
    ");
    $stmt_pkg->execute([$id]);
    $packages = $stmt_pkg->fetchAll();

    // 5. Hizmet Bazlı Paket İlerlemesi (services.total_sessions > 1)
    $stmt_sp = $db->prepare("
        SELECT s.id as service_id, s.name as service_name, s.color as service_color, s.total_sessions,
               COUNT(a.id) as total_completed
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        WHERE a.customer_id = ? AND a.status = 'completed' AND a.is_deleted = 0 AND s.total_sessions > 1
        GROUP BY s.id
    ");
    $stmt_sp->execute([$id]);
    $service_packages_raw = $stmt_sp->fetchAll();
    $service_packages = [];
    foreach ($service_packages_raw as $sp) {
        $tot = (int)$sp['total_sessions'];
        $comp = (int)$sp['total_completed'];
        if ($tot > 0) {
            if ($comp === 0) {
                $used_in_cycle = 0;
                $remaining = $tot;
                $cycle = 1;
                $is_cycle_completed = false;
            } else {
                $rem_mod = $comp % $tot;
                if ($rem_mod === 0) {
                    $used_in_cycle = $tot;
                    $remaining = 0;
                    $cycle = floor($comp / $tot);
                    $is_cycle_completed = true;
                } else {
                    $used_in_cycle = $rem_mod;
                    $remaining = $tot - $rem_mod;
                    $cycle = floor($comp / $tot) + 1;
                    $is_cycle_completed = false;
                }
            }
        } else {
            $used_in_cycle = 0;
            $remaining = 0;
            $cycle = 1;
            $is_cycle_completed = false;
        }
        $completed_cycles = ($tot > 0) ? floor($comp / $tot) : 0;
        $service_packages[] = [
            'service_id'        => $sp['service_id'],
            'service_name'      => $sp['service_name'],
            'service_color'     => $sp['service_color'],
            'total_sessions'    => $tot,
            'total_completed'   => $comp,
            'used_in_cycle'     => $used_in_cycle,
            'remaining_sessions'=> $remaining,
            'cycle'             => $cycle,
            'is_cycle_completed'=> $is_cycle_completed,
            'completed_cycles'  => $completed_cycles
        ];
    }

    $data = [
        'customer' => $customer,
        'history' => $history,
        'packages' => $packages,
        'service_packages' => $service_packages,
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
