<?php
require_once '../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    try {
        // Soft delete: is_deleted = 1
        $stmt = $db->prepare("UPDATE customers SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);
        json_response('success', __('archive_success'));
    } catch (PDOException $e) {
        json_response('error', __('archive_error') . ': ' . $e->getMessage());
    }
}
?>
