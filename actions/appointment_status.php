<?php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = (int)($_POST['id'] ?? 0);
    $action = clean($_POST['action'] ?? 'status');

    if (!$appointment_id) {
        json_response('error', 'Geçersiz randevu ID.');
    }

    try {
        $db->beginTransaction();

        if ($action === 'move') {
            // --- Sürükle-Bırak / Manuel Güncelleme ---
            $new_date = clean($_POST['new_date']);
            $new_time = clean($_POST['new_time']);
            $staff_id = (int)($_POST['staff_id'] ?? 0);
            $service_id = (int)($_POST['service_id'] ?? 0);
            $extra_services = $_POST['extra_services'] ?? [];

            if (!$new_date || !$new_time) {
                throw new Exception('Lütfen tarih ve saat bilgilerini kontrol edin.');
            }

            // 30 Dakika Doğrulaması (Zorunlu Kılma)
            $minutes = date('i', strtotime($new_time));
            if ($minutes != '00' && $minutes != '30') {
                throw new Exception('Randevu saati sadece tam (00) veya yarım (30) saatler olabilir.');
            }

            // Mevcut verileri çekelim (eksik alanlar için yedek)
            $stmt_old = $db->prepare("SELECT staff_id, service_id, base_price, discount_type, discount_value, service_price FROM appointments WHERE id = ?");
            $stmt_old->execute([$appointment_id]);
            $old_data = $stmt_old->fetch();

            if (!$old_data) throw new Exception('Randevu bulunamadı.');

            // Eğer gönderilmediyse eskileri kullan
            if (!$staff_id) $staff_id = (int)$old_data['staff_id'];
            if (!$service_id) $service_id = (int)$old_data['service_id'];
            $category_id = (int)($_POST['category_id'] ?? 0);

            $warning_msg = '';
            // 1. Çakışma Kontrolü (Personel Doluluk) - Artık engellemez, uyarır
            $stmt_check = $db->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE appointment_date = ? 
                AND staff_id = ? 
                AND id != ?
                AND status != 'cancelled' 
                AND appointment_time = ?
            ");
            $stmt_check->execute([$new_date, $staff_id, $appointment_id, $new_time]);
            if ($stmt_check->fetchColumn() > 0) {
                $warning_msg = 'Bu personel aynı saatte başka bir randevuda görünüyor.';
            }

            // 2. Fiyatları ve Hizmet Adlarını Al
            $base_price = isset($_POST['base_price']) ? floatval($_POST['base_price']) : $old_data['base_price'];
            $discount_type = $_POST['discount_type'] ?? $old_data['discount_type'];
            $discount_value = isset($_POST['discount_value']) ? floatval($_POST['discount_value']) : $old_data['discount_value'];
            $service_price = isset($_POST['service_price']) ? floatval($_POST['service_price']) : $old_data['service_price'];

            // Tüm hizmet adlarını finans açıklaması için toplayalım
            $all_service_ids = array_merge([$service_id], $extra_services);
            $service_names = [];
            foreach ($all_service_ids as $sid) {
                $stmt_sn = $db->prepare("SELECT name FROM services WHERE id = ?");
                $stmt_sn->execute([$sid]);
                $sn = $stmt_sn->fetchColumn();
                if ($sn) $service_names[] = $sn;
            }

            // 3. Randevuyu güncelle (İptal durumundaysa otomatik 'pending' yap)
            $stmt_up = $db->prepare("UPDATE appointments SET 
                appointment_date = ?, 
                appointment_time = ?, 
                staff_id = ?, 
                service_id = ?, 
                category_id = ?,
                base_price = ?,
                discount_type = ?,
                discount_value = ?,
                service_price = ?,
                status = 'pending' 
                WHERE id = ?");
            $stmt_up->execute([$new_date, $new_time, $staff_id, $service_id, $category_id, $base_price, $discount_type, $discount_value, $service_price, $appointment_id]);

            // 4. Ek Hizmetleri güncelle
            $stmt_del_extras = $db->prepare("DELETE FROM appointment_extras WHERE appointment_id = ?");
            $stmt_del_extras->execute([$appointment_id]);
            foreach ($extra_services as $esid) {
                if ($esid) {
                    $stmt_ins_extra = $db->prepare("INSERT INTO appointment_extras (appointment_id, service_id) VALUES (?, ?)");
                    $stmt_ins_extra->execute([$appointment_id, $esid]);
                }
            }

            $db->commit();
            json_response('success', 'Randevu başarıyla güncellendi.' . ($warning_msg ? ' (Not: ' . $warning_msg . ')' : ''), ['warning' => $warning_msg]);

        } else {
            // --- Durum Güncelleme ---
            $new_status = clean($_POST['status'] ?? '');
            if (!in_array($new_status, ['completed', 'missed', 'cancelled', 'pending'])) {
                throw new Exception('Geçersiz durum.');
            }

            // Gelişmiş Fiyat Güncelleme (Özellikle Tamamlandı durumunda)
            $base_price = isset($_POST['base_price']) ? floatval($_POST['base_price']) : null;
            $discount_type = $_POST['discount_type'] ?? null;
            $discount_value = isset($_POST['discount_value']) ? floatval($_POST['discount_value']) : null;
            $service_price = isset($_POST['service_price']) ? floatval($_POST['service_price']) : null;

            if ($base_price !== null && $new_status === 'completed') {
                $stmt_up_apt = $db->prepare("UPDATE appointments SET 
                    status = ?, 
                    base_price = ?, 
                    discount_type = ?, 
                    discount_value = ?, 
                    service_price = ? 
                    WHERE id = ?");
                $stmt_up_apt->execute([$new_status, $base_price, $discount_type, $discount_value, $service_price, $appointment_id]);
            } else {
                $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $appointment_id]);
            }

            if (in_array($new_status, ['completed', 'missed', 'cancelled'])) {
                $stmt_data = $db->prepare("SELECT a.service_price, c.first_name, c.last_name 
                                           FROM appointments a 
                                           JOIN customers c ON a.customer_id = c.id 
                                           WHERE a.id = ?");
                $stmt_data->execute([$appointment_id]);
                $apt_data = $stmt_data->fetch();

                if ($apt_data) {
                    $amount = ($new_status === 'completed') ? $apt_data['service_price'] : 0;
                    $status_text = ($new_status === 'completed') ? 'Geliri' : ($new_status === 'missed' ? 'Gelmeme Kaydı' : 'İptali');
                    $description = "Randevu " . $status_text . ": " . $apt_data['first_name'] . " " . $apt_data['last_name'];
                    
                    $stmt_check = $db->prepare("SELECT id FROM finance WHERE appointment_id = ?");
                    $stmt_check->execute([$appointment_id]);
                    
                    if (!$stmt_check->fetch()) {
                        $stmt_fin = $db->prepare("INSERT INTO finance (appointment_id, amount, type, description) VALUES (?, ?, 'income', ?)");
                        $stmt_fin->execute([$appointment_id, $amount, $description]);
                    } else {
                        $stmt_up_fin = $db->prepare("UPDATE finance SET amount = ?, description = ? WHERE appointment_id = ?");
                        $stmt_up_fin->execute([$amount, $description, $appointment_id]);
                    }
                }
            }
            $db->commit();
            json_response('success', 'Durum güncellendi.');
        }
    } catch (Exception $e) {
        $db->rollBack();
        json_response('error', $e->getMessage());
    }
}
?>
