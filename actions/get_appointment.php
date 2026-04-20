<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id) {
        $stmt = $db->prepare("SELECT a.*, c.first_name, c.last_name, c.phone, s.name as service_name, CONCAT(st.first_name, ' ', st.last_name) as staff_name 
                             FROM appointments a 
                             JOIN customers c ON a.customer_id = c.id 
                             JOIN services s ON a.service_id = s.id 
                             LEFT JOIN staff st ON a.staff_id = st.id
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
