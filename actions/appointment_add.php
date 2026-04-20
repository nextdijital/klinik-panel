<?php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = clean($_POST['customer_id']);
    $service_id = clean($_POST['service_id']);
    $appointment_date = clean($_POST['appointment_date']);
    $appointment_time = clean($_POST['appointment_time']);
    $staff_id = clean($_POST['staff_id'] ?? '');
    $category_id = clean($_POST['category_id'] ?? null);
    $notes = clean($_POST['notes'] ?? '');
    
    $user_id = $_SESSION['user_id']; 

    if (empty($customer_id) || empty($service_id) || empty($appointment_date) || empty($appointment_time) || empty($staff_id)) {
        json_response('error', 'Lütfen tüm alanları (personel dahil) doldurun.');
    }

    // 30 Dakika Doğrulaması (Zorunlu Kılma)
    $minutes = date('i', strtotime($appointment_time));
    if ($minutes != '00' && $minutes != '30') {
        json_response('error', 'Randevu saati sadece tam (00) veya yarım (30) saatler olabilir.');
    }

    try {
        $db->beginTransaction();

        $warning_msg = '';

        // 1. Mükerrer Randevu Kontrolü (Aynı Müşteri - Aynı Saat) - Sadece uyarı için
        $stmt_dup = $db->prepare("SELECT COUNT(*) FROM appointments WHERE customer_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $stmt_dup->execute([$customer_id, $appointment_date, $appointment_time]);
        if ($stmt_dup->fetchColumn() > 0) {
            $warning_msg = 'Aynı müşterinin bu saatte başka bir randevusu var.';
        }

        // 2. Çakışma Kontrolü (Personel Doluluk) - Sadece uyarı için
        $stmt_check = $db->prepare("
            SELECT COUNT(*) 
            FROM appointments 
            WHERE appointment_date = ? 
            AND staff_id = ? 
            AND status != 'cancelled' 
            AND appointment_time = ?
        ");
        $stmt_check->execute([$appointment_date, $staff_id, $appointment_time]);
        if ($stmt_check->fetchColumn() > 0) {
            $warning_msg = 'Bu personel aynı saatte başka bir radevuda dolu.';
        }

        // 3. Fiyat Bilgilerini Al (Frontend'den gelen manuel değerler)
        $base_price = (float)($_POST['base_price'] ?? 0);
        $discount_type = clean($_POST['discount_type'] ?? 'none');
        $discount_value = (float)($_POST['discount_value'] ?? 0);
        $total_price = (float)($_POST['service_price'] ?? 0); // Bu bizim Net Tutarımız

        $extra_services = $_POST['extra_services'] ?? [];
        $all_service_ids = array_merge([$service_id], $extra_services);
        $service_names = [];

        foreach ($all_service_ids as $sid) {
            if (empty($sid)) continue;
            $stmt_s = $db->prepare("SELECT name FROM services WHERE id = ?");
            $stmt_s->execute([$sid]);
            $s_data = $stmt_s->fetch();
            if ($s_data) {
                $service_names[] = $s_data['name'];
            }
        }

        if (empty($service_names)) {
            $db->rollBack();
            json_response('error', 'En az bir geçerli hizmet seçilmelidir.');
        }

        // 4. Randevuyu kaydedelim
        $stmt = $db->prepare("INSERT INTO appointments (customer_id, user_id, staff_id, service_id, category_id, appointment_date, appointment_time, base_price, discount_type, discount_value, service_price, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$customer_id, $user_id, $staff_id, $service_id, $category_id, $appointment_date, $appointment_time, $base_price, $discount_type, $discount_value, $total_price, $notes]);
        $appointment_id = $db->lastInsertId();

        // 5. Ek Hizmetleri (varsa) tabloya kaydedelim
        if (!empty($extra_services)) {
            foreach ($extra_services as $esid) {
                if (empty($esid)) continue;
                $stmt_es = $db->prepare("INSERT INTO appointment_extras (appointment_id, service_id) VALUES (?, ?)");
                $stmt_es->execute([$appointment_id, $esid]);
            }
        }

        $db->commit();
        json_response('success', 'Randevu başarıyla oluşturuldu.' . ($warning_msg ? ' (Not: ' . $warning_msg . ')' : ''), ['warning' => $warning_msg]);

    } catch (PDOException $e) {
        $db->rollBack();
        json_response('error', 'Veritabanı hatası: ' . $e->getMessage());
    }
} else {
    json_response('error', 'Geçersiz istek.');
}
?>
