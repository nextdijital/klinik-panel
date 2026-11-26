<?php
require_once '../functions.php';
header('Content-Type: application/json');

// Sadece giriş yapmış kullanıcılar görebilir
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

if (!$start || !$end) {
    echo json_encode([]);
    exit();
}

// Ayarları çek
$settings_all = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$retention_hours = intval($settings_all['cancelled_retention_hours'] ?? 24);
$cancelled_color = $settings_all['cancelled_color'] ?? '#FF4B5C';
$multiple_services_color = $settings_all['multiple_services_color'] ?? '#111111';

// İptal süresi için sınır zamanı PHP ile hesapla
$cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_hours hours"));

$query = "SELECT a.id, 
                 a.service_id,
                 a.staff_id,
                 a.category_id,
                 a.package_id,
                 0 as is_package_session,
                 cp.package_name,
                 CONCAT(c.first_name, ' ', c.last_name) as title, 
                 c.phone as customer_phone,
                 s.name as service_name,
                 s.total_sessions,
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
                 ac.name as category_name,
                 CONCAT(st.first_name, ' ', st.last_name) as staff_name,
                 a.notes,
                 a.status,
                 a.base_price,
                 a.discount_type,
                 a.discount_value,
                 a.service_price,
                 CONCAT(a.appointment_date, 'T', a.appointment_time) as start,
                 DATE_ADD(CONCAT(a.appointment_date, ' ', a.appointment_time), INTERVAL s.duration MINUTE) as end,
                 CASE 
                    WHEN a.status = 'cancelled' THEN '$cancelled_color' 
                    WHEN a.status = 'missed' THEN '#5D4037'
                    WHEN (SELECT COUNT(*) FROM appointment_extras WHERE appointment_id = a.id) > 0 THEN '$multiple_services_color'
                    ELSE COALESCE(ac.color, s.color, '#8687C3')
                 END as backgroundColor,
                 'white' as textColor,
                 (SELECT GROUP_CONCAT(s2.name SEPARATOR ', ') 
                  FROM appointment_extras ae 
                  JOIN services s2 ON ae.service_id = s2.id 
                  WHERE ae.appointment_id = a.id) as extraServices,
                 (SELECT GROUP_CONCAT(ae2.service_id) 
                  FROM appointment_extras ae2 
                  WHERE ae2.appointment_id = a.id) as extraServiceIds,
                 CASE WHEN a.status IN ('completed', 'missed') THEN 0 ELSE 1 END as editable
          FROM appointments a
          JOIN customers c ON a.customer_id = c.id
          JOIN services s ON a.service_id = s.id
          LEFT JOIN staff st ON a.staff_id = st.id
          LEFT JOIN appointment_categories ac ON a.category_id = ac.id
          LEFT JOIN customer_packages cp ON a.package_id = cp.id
          WHERE a.is_deleted = 0 
          AND c.is_deleted = 0 
          AND s.is_deleted = 0
          AND (a.staff_id IS NULL OR st.is_deleted = 0)
          AND a.appointment_date BETWEEN ? AND ?
          AND (
            a.status = 'pending' 
            OR (a.status = 'cancelled' AND a.updated_at > ?) 
            OR (a.status = 'completed' AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))
            OR (a.status = 'missed' AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))
          )
          AND MINUTE(a.appointment_time) IN (0, 30)
          ORDER BY a.appointment_time ASC";

$stmt = $db->prepare($query);
$stmt->execute([
    substr($start, 0, 10), 
    substr($end, 0, 10), 
    $cutoff_date
]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FullCalendar için editable değerini gerçek boolean yapalım
foreach ($events as &$event) {
    $event['editable'] = (bool)$event['editable'];
}

echo json_encode($events);
?>
