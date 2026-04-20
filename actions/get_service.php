<?php
// actions/get_service.php
require_once '../functions.php';
check_login();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$id]);
    $service = $stmt->fetch();

    if ($service) {
        json_response('success', 'Hizmet bulundu.', $service);
    } else {
        json_response('error', 'Hizmet bulunamadı.');
    }
}
?>
<!-- Separator for another file -->
<?php
// actions/service_update.php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $name = clean($_POST['name'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    if (empty($name)) {
        json_response('error', 'Hizmet adı zorunludur.');
    }

    try {
        $stmt = $db->prepare("UPDATE services SET name = ?, description = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $id]);
        json_response('success', 'Hizmet bilgileri güncellendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
<!-- Separator for another file -->
<?php
// actions/service_delete.php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    try {
        $stmt = $db->prepare("UPDATE services SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);
        json_response('success', 'Hizmet arşivlendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
<!-- Separator for another file -->
<?php
// actions/service_status.php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $status = (int)$_POST['status'];

    try {
        $stmt = $db->prepare("UPDATE services SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        json_response('success', 'Hizmet durumu güncellendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
