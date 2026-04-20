<?php
require_once '../functions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

// Saati geçen ama durumu hala 'pending' (Bekliyor) olan randevuları bul
$query = "SELECT a.id, CONCAT(c.first_name, ' ', c.last_name) as name, a.appointment_time, s.name as service_name, s.color as service_color 
          FROM appointments a
          JOIN customers c ON a.customer_id = c.id
          JOIN services s ON a.service_id = s.id
          WHERE a.appointment_date = CURDATE()
          AND a.appointment_time < CURTIME()
          AND a.status = 'pending'";

$stmt = $db->query($query);
$overdue = $stmt->fetchAll();

echo json_encode($overdue ?: []);
