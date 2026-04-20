<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id) {
        try {
            // Randevuyu sil (Finans tablosunda ON DELETE SET NULL olduğu için sorun olmayacaktır)
            $stmt = $db->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                json_response('success', 'Randevu başarıyla silindi.');
            } else {
                json_response('error', 'Randevu bulunamadı veya zaten silinmiş.');
            }
        } catch (Exception $e) {
            json_response('error', 'Silme işlemi başarısız: ' . $e->getMessage());
        }
    } else {
        json_response('error', 'Geçersiz ID.');
    }
}
?>
