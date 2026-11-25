<?php
// ============================================================
// package_update.php — Paket Düzenleme, Seans Değiştirme, İptal
// ============================================================
require_once __DIR__ . '/../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = clean($_POST['action'] ?? 'update');
    $package_id = (int)($_POST['package_id'] ?? 0);

    if (!$package_id) {
        json_response('error', 'Geçersiz paket ID.');
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM customer_packages WHERE id = ?");
        $stmt->execute([$package_id]);
        $pkg = $stmt->fetch();
        if (!$pkg) {
            throw new Exception('Paket bulunamadı.');
        }

        switch ($action) {
            // ----------------------------------------------------
            // 1. Manuel Seans Artırma / Azaltma (+1 / -1)
            // ----------------------------------------------------
            case 'adjust_session':
                $delta = (int)($_POST['delta'] ?? 0);
                if ($delta === 0) {
                    throw new Exception('Geçersiz seans değişimi.');
                }

                $new_used = (int)$pkg['used_sessions'] + $delta;
                if ($new_used < 0) {
                    $new_used = 0;
                }
                if ($new_used > (int)$pkg['total_sessions']) {
                    $new_used = (int)$pkg['total_sessions'];
                }

                $new_status = ($new_used >= (int)$pkg['total_sessions']) ? 'completed' : 'active';

                $up = $db->prepare("UPDATE customer_packages SET used_sessions = ?, status = ? WHERE id = ?");
                $up->execute([$new_used, $new_status, $package_id]);

                $db->commit();
                json_response('success', 'Seans güncellendi.', [
                    'used_sessions'      => $new_used,
                    'total_sessions'     => (int)$pkg['total_sessions'],
                    'remaining_sessions' => (int)$pkg['total_sessions'] - $new_used,
                    'status'             => $new_status
                ]);
                break;

            // ----------------------------------------------------
            // 2. Paket Durumunu Değiştirme (İptal / Tamamlandı / Aktif)
            // ----------------------------------------------------
            case 'change_status':
                $status = clean($_POST['status'] ?? 'active');
                if (!in_array($status, ['active', 'completed', 'cancelled'])) {
                    throw new Exception('Geçersiz durum.');
                }
                $up = $db->prepare("UPDATE customer_packages SET status = ? WHERE id = ?");
                $up->execute([$status, $package_id]);

                $db->commit();
                json_response('success', 'Paket durumu güncellendi.');
                break;

            // ----------------------------------------------------
            // 3. Genel Bilgileri Güncelleme
            // ----------------------------------------------------
            case 'update':
            default:
                $package_name   = clean($_POST['package_name'] ?? $pkg['package_name']);
                $service_id     = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
                $total_sessions = (int)($_POST['total_sessions'] ?? $pkg['total_sessions']);
                $price          = isset($_POST['price']) ? (float)$_POST['price'] : (float)$pkg['price'];
                $payment_status = clean($_POST['payment_status'] ?? $pkg['payment_status']);
                $start_date     = clean($_POST['start_date'] ?? $pkg['start_date']);
                $end_date       = !empty($_POST['end_date']) ? clean($_POST['end_date']) : null;
                $notes          = clean($_POST['notes'] ?? $pkg['notes']);
                $status         = clean($_POST['status'] ?? $pkg['status']);

                if (empty($package_name)) {
                    throw new Exception('Paket adı boş bırakılamaz.');
                }
                if ($total_sessions < (int)$pkg['used_sessions']) {
                    throw new Exception('Toplam seans, kullanılmış seans adedinden (' . $pkg['used_sessions'] . ') az olamaz.');
                }

                if ($pkg['used_sessions'] >= $total_sessions && $status === 'active') {
                    $status = 'completed';
                }

                $up = $db->prepare("
                    UPDATE customer_packages SET 
                        package_name = ?, service_id = ?, total_sessions = ?, price = ?, 
                        payment_status = ?, start_date = ?, end_date = ?, status = ?, notes = ? 
                    WHERE id = ?
                ");
                $up->execute([
                    $package_name, $service_id, $total_sessions, $price,
                    $payment_status, $start_date, $end_date, $status, $notes, $package_id
                ]);

                $db->commit();
                json_response('success', 'Paket başarıyla güncellendi.');
                break;
        }

    } catch (Exception $e) {
        $db->rollBack();
        json_response('error', $e->getMessage());
    }
} else {
    json_response('error', 'Geçersiz istek metodu.');
}
?>