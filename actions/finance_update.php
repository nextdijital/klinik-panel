<?php
// finance_update.php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $amount = (float)($_POST['amount'] ?? 0);
    $type = clean($_POST['type'] ?? '');
    $description = clean($_POST['description'] ?? '');

    if ($id <= 0 || $amount <= 0 || !in_array($type, ['income', 'expense']) || empty($description)) {
        json_response('error', 'Lütfen geçerli veriler girin.');
    }

    try {
        $stmt = $db->prepare("UPDATE finance SET amount = ?, type = ?, description = ? WHERE id = ?");
        $stmt->execute([$amount, $type, $description, $id]);
        json_response('success', 'Kayıt başarıyla güncellendi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
<!-- Separator for another file -->
<?php
// finance_delete.php content
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    try {
        $stmt = $db->prepare("DELETE FROM finance WHERE id = ?");
        $stmt->execute([$id]);
        json_response('success', 'Kayıt başarıyla silindi.');
    } catch (PDOException $e) {
        json_response('error', 'Hata: ' . $e->getMessage());
    }
}
?>
