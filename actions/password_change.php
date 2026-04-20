<?php
require_once __DIR__ . '/../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($current_password) || empty($new_password) || $new_password !== $confirm_password) {
        json_response('error', __('password_error'));
    }

    try {
        // Fetch current hash
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            // Hash new password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$new_hash, $user_id]);
            
            json_response('success', __('password_success'));
        } else {
            json_response('error', __('password_error'));
        }
    } catch (Exception $e) {
        json_response('error', $e->getMessage());
    }
} else {
    json_response('error', 'Invalid request method.');
}
?>
