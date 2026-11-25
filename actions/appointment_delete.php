<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id) {
        try {
            // Randevu paket seansı olarak tamamlandıysa pakete seansı iade et
            $stmt_check = $db->prepare("SELECT status, package_id FROM appointments WHERE id = ?");
            $stmt_check->execute([$id]);
            $apt = $stmt_check->fetch();
            if ($apt && $apt['status'] === 'completed' && !empty($apt['package_id'])) {
                $db->prepare("UPDATE customer_packages SET used_sessions = GREATEST(0, used_sessions - 1), status = IF(used_sessions - 1 < total_sessions, 'active', status) WHERE id = ?")->execute([(int)$apt['package_id']]);
            }

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
