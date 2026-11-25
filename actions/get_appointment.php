<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id) {
        $stmt = $db->prepare("SELECT a.*, c.first_name, c.last_name, c.phone, s.name as service_name, s.total_sessions,
                                     (
                                         SELECT COUNT(*)
                                         FROM appointments a2
                                         WHERE a2.customer_id = a.customer_id 
                                           AND a2.service_id = a.service_id 
                                           AND a2.is_deleted = 0 
                                           AND a2.status != 'cancelled'
                                           AND (a2.appointment_date < a.appointment_date 
                                                OR (a2.appointment_date = a.appointment_date AND (a2.appointment_time < a.appointment_time OR (a2.appointment_time = a.appointment_time AND a2.id <= a.id))))
                                     ) as session_num,
                                     CONCAT(st.first_name, ' ', st.last_name) as staff_name,
                                     cp.package_name, cp.total_sessions as pkg_total_sessions, cp.used_sessions as pkg_used_sessions,
                                     (cp.total_sessions - cp.used_sessions) as pkg_remaining_sessions
                             FROM appointments a 
                             JOIN customers c ON a.customer_id = c.id 
                             JOIN services s ON a.service_id = s.id 
                             LEFT JOIN staff st ON a.staff_id = st.id
                             LEFT JOIN customer_packages cp ON a.package_id = cp.id
                             WHERE a.id = ?");
        $stmt->execute([$id]);
        $appt = $stmt->fetch();

        if ($appt) {
            // Ek hizmetleri çek
            $stmt_extras = $db->prepare("SELECT ae.service_id, s.name, s.price 
                                        FROM appointment_extras ae 
                                        JOIN services s ON ae.service_id = s.id 
                                        WHERE ae.appointment_id = ?");
            $stmt_extras->execute([$id]);
            $appt['extras'] = $stmt_extras->fetchAll();

            json_response('success', 'Veri çekildi.', $appt);
        } else {
            json_response('error', 'Randevu bulunamadı.');
        }
    } else {
        json_response('error', 'Geçersiz ID.');
    }
}
?>
