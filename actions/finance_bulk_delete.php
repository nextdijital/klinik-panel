<?php
require_once '../functions.php';
header('Content-Type: application/json');
check_login();

// Filtreleme Parametreleri
$filter_type = $_POST['type'] ?? 'all';
$filter_start = $_POST['start_date'] ?? '';
$filter_end = $_POST['end_date'] ?? '';

$where = [];
$params = [];

if ($filter_type !== 'all' && in_array($filter_type, ['income', 'expense'])) {
    $where[] = "type = ?";
    $params[] = $filter_type;
}
if ($filter_start) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $filter_start;
}
if ($filter_end) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $filter_end;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

try {
    $sql = "DELETE FROM finance $where_clause";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $deleted_count = $stmt->rowCount();

    echo json_encode(['status' => 'success', 'message' => $deleted_count . ' kayıt başarıyla silindi.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Silme işlemi sırasında bir hata oluştu: ' . $e->getMessage()]);
}
