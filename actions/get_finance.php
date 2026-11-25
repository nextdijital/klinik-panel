<?php
require_once '../functions.php';
check_login();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("
        SELECT f.*, s.name as service_name, s.total_sessions,
               CASE 
                   WHEN a.id IS NOT NULL AND s.total_sessions > 1 THEN
                       (
                           SELECT COUNT(*)
                           FROM appointments a2
                           WHERE a2.customer_id = a.customer_id 
                             AND a2.service_id = a.service_id 
                             AND a2.is_deleted = 0 
                             AND a2.status != 'cancelled'
                             AND (a2.appointment_date < a.appointment_date 
                                  OR (a2.appointment_date = a.appointment_date AND (a2.appointment_time < a.appointment_time OR (a2.appointment_time = a.appointment_time AND a2.id <= a.id))))
                       )
                   ELSE 1 
               END as session_num
        FROM finance f 
        LEFT JOIN appointments a ON f.appointment_id = a.id 
        LEFT JOIN services s ON a.service_id = s.id 
        WHERE f.id = ?
    ");
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if ($record) {
        json_response('success', 'Kayıt bulundu.', $record);
    } else {
        json_response('error', 'Kayıt bulunamadı.');
    }
}
?>
