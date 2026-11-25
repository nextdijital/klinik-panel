<?php
require_once '../functions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

// Saati geçen ama durumu hala 'pending' (Bekliyor) olan randevuları bul
$client_date = $_GET['client_date'] ?? date('Y-m-d');

$query = "SELECT a.id, CONCAT(c.first_name, ' ', c.last_name) as name, a.appointment_time, a.appointment_date, s.name as service_name, s.color as service_color 
          FROM appointments a
          JOIN customers c ON a.customer_id = c.id
          JOIN services s ON a.service_id = s.id
          WHERE a.appointment_date = ?
          AND a.status = 'pending'
          AND a.is_deleted = 0
          AND c.is_deleted = 0
          AND s.is_deleted = 0";

$stmt = $db->prepare($query);
$stmt->execute([$client_date]);
$overdue = $stmt->fetchAll();

echo json_encode($overdue ?: []);
