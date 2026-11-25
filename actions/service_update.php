<?php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $name = clean($_POST['name'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $total_sessions = max(1, (int)($_POST['total_sessions'] ?? 1));
    $base_price = (float)($_POST['base_price'] ?? 0);
    $discount_type = clean($_POST['discount_type'] ?? 'none');
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    
    // Final fiyatı hesapla
    $price = $base_price;
    if ($discount_type == 'fixed') {
        $price = $base_price - $discount_value;
    } elseif ($discount_type == 'percentage') {
        $price = $base_price - ($base_price * ($discount_value / 100));
    }
    
    $color = clean($_POST['color'] ?? '#A5A6D8');

    if ($price < 0) $price = 0;

    if (empty($name)) {
        json_response('error', 'Hizmet adı zorunludur.');
    }

    try {
        $stmt = $db->prepare("UPDATE services SET name = ?, description = ?, total_sessions = ?, base_price = ?, discount_type = ?, discount_value = ?, price = ?, color = ? WHERE id = ?");
        $stmt->execute([$name, $description, $total_sessions, $base_price, $discount_type, $discount_value, $price, $color, $id]);
        json_response('success', 'Hizmet bilgileri güncellendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
