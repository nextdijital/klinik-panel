<?php
require_once __DIR__ . '/../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $name = clean($_POST['name'] ?? '');
        $color = clean($_POST['color'] ?? '#A5A6D8');

        if (empty($name)) {
            json_response('error', 'Lütfen kategori adını girin.');
        }

        try {
            $stmt = $db->prepare("INSERT INTO appointment_categories (name, color) VALUES (?, ?)");
            $stmt->execute([$name, $color]);
            json_response('success', 'Kategori başarıyla eklendi.');
        } catch (Exception $e) {
            json_response('error', 'Hata: ' . $e->getMessage());
        }
    } 
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            json_response('error', 'Geçersiz kategori.');
        }

        try {
            // Soft delete
            $stmt = $db->prepare("UPDATE appointment_categories SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$id]);
            json_response('success', 'Kategori başarıyla silindi.');
        } catch (Exception $e) {
            json_response('error', 'Hata: ' . $e->getMessage());
        }
    }
} else {
    json_response('error', 'Geçersiz istek metodu.');
}
?>
