<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $service_id = (int)($_POST['service_id'] ?? 0);
    $staff_id = (int)($_POST['staff_id'] ?? 0);
    $appointment_date = clean($_POST['appointment_date'] ?? '');
    $appointment_time = clean($_POST['appointment_time'] ?? '');

    if ($id && $service_id && $staff_id && $appointment_date && $appointment_time) {
        // 30 Dakika Doğrulaması (Zorunlu Kılma)
        $minutes = date('i', strtotime($appointment_time));
        if ($minutes != '00' && $minutes != '30') {
            json_response('error', 'Randevu saati sadece tam (00) veya yarım (30) saatler olabilir.');
        }
        try {
            $db->beginTransaction();

            $warning_msg = '';

            // 1. Çakışma Kontrolü (Personel Doluluk)
            $stmt_check = $db->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE appointment_date = ? 
                AND staff_id = ? 
                AND status != 'cancelled' 
                AND id != ?
                AND appointment_time = ?
            ");
            $stmt_check->execute([$appointment_date, $staff_id, $id, $appointment_time]);
            if ($stmt_check->fetchColumn() > 0) {
                $warning_msg = 'Bu personel aynı saatte başka bir randevuda görünüyor.';
            }

            // 2. Fiyat Bilgilerini al (Varsa)
            $base_price = isset($_POST['base_price']) ? (float)$_POST['base_price'] : null;
            $discount_type = $_POST['discount_type'] ?? null;
            $discount_value = isset($_POST['discount_value']) ? (float)$_POST['discount_value'] : null;
            $service_price = isset($_POST['service_price']) ? (float)$_POST['service_price'] : null;
            $category_id = $_POST['category_id'] ?? null;
            $notes = $_POST['notes'] ?? '';

            if ($base_price === null) {
                $stmt_service = $db->prepare("SELECT price FROM services WHERE id = ?");
                $stmt_service->execute([$service_id]);
                $service = $stmt_service->fetch();
                if ($service) {
                    $base_price = $service['price'];
                    $service_price = $service['price'];
                    $discount_type = 'none';
                    $discount_value = 0;
                }
            }

            // 3. Randevuyu Güncelle (Güncellenen randevuyu otomatik 'Bekliyor' durumuna çek)
            $stmt_old_apt = $db->prepare("SELECT status, package_id FROM appointments WHERE id = ?");
            $stmt_old_apt->execute([$id]);
            $old_apt = $stmt_old_apt->fetch();
            $old_status = $old_apt['status'] ?? 'pending';
            $old_pkg_id = (int)($old_apt['package_id'] ?? 0);

            $package_id = isset($_POST['package_id']) ? (!empty($_POST['package_id']) ? (int)$_POST['package_id'] : null) : ($old_apt['package_id'] ?? null);
            $is_package_session = ($package_id > 0) ? 1 : 0;

            // Eğer randevu önceden 'completed' idi ve şimdi 'pending' oluyorsa seansı pakete iade et
            if ($old_status === 'completed' && $old_pkg_id > 0) {
                $db->prepare("UPDATE customer_packages SET used_sessions = GREATEST(0, used_sessions - 1), status = IF(used_sessions - 1 < total_sessions, 'active', status) WHERE id = ?")->execute([$old_pkg_id]);
            }

            $stmt = $db->prepare("UPDATE appointments SET 
                                    service_id = ?, 
                                    staff_id = ?,
                                    category_id = ?,
                                    package_id = ?,
                                    is_package_session = ?,
                                    appointment_date = ?, 
                                    appointment_time = ?, 
                                    base_price = ?,
                                    discount_type = ?,
                                    discount_value = ?,
                                    service_price = ?,
                                    notes = ?,
                                    status = 'pending'
                                  WHERE id = ?");
            $stmt->execute([
                $service_id, 
                $staff_id,
                $category_id,
                $package_id,
                $is_package_session,
                $appointment_date, 
                $appointment_time, 
                $base_price,
                $discount_type,
                $discount_value,
                $service_price,
                $notes,
                $id
            ]);

            // 4. Ek Hizmetleri Güncelle (Sil ve Tekrar Ekle)
            $stmt_del = $db->prepare("DELETE FROM appointment_extras WHERE appointment_id = ?");
            $stmt_del->execute([$id]);

            $extra_services = $_POST['extra_services'] ?? [];
            if (!empty($extra_services)) {
                foreach ($extra_services as $esid) {
                    if (empty($esid)) continue;
                    $stmt_es = $db->prepare("INSERT INTO appointment_extras (appointment_id, service_id) VALUES (?, ?)");
                    $stmt_es->execute([$id, $esid]);
                }
            }

            // 5. Finans Kaydını Temizle (Randevu tekrar 'Bekliyor' olduğu için eski kayıt silinmeli)
            $stmt_fin = $db->prepare("DELETE FROM finance WHERE appointment_id = ?");
            $stmt_fin->execute([$id]);

            $db->commit();
            json_response('success', 'Randevu başarıyla güncellendi.' . ($warning_msg ? ' (Not: ' . $warning_msg . ')' : ''), ['warning' => $warning_msg]);
        } catch (Exception $e) {
            $db->rollBack();
            json_response('error', 'Güncelleme hatası: ' . $e->getMessage());
        }
    } else {
        json_response('error', 'Eksik veriler var.');
    }
}
?>
