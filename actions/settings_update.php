<?php
require_once __DIR__ . '/../functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_name = clean($_POST['clinic_name'] ?? '');
    $netgsm_user = clean($_POST['netgsm_user'] ?? '');
    $netgsm_pass = clean($_POST['netgsm_pass'] ?? '');
    $netgsm_header = clean($_POST['netgsm_header'] ?? '');
    $reminder_sms_enabled = isset($_POST['reminder_sms_enabled']) ? '1' : '0';
    $sms_new_appointment_enabled = isset($_POST['sms_new_appointment_enabled']) ? '1' : '0';
    $sms_new_appointment_template = clean($_POST['sms_new_appointment_template'] ?? '');
    $sms_reminder_template = clean($_POST['sms_reminder_template'] ?? '');
    $currency = clean($_POST['currency'] ?? '₺');
    $security_code = clean($_POST['security_code'] ?? '');

    try {
        $db->beginTransaction();

        $allowed_settings = [
            'clinic_name', 'netgsm_user', 'netgsm_pass', 'netgsm_header',
            'reminder_sms_enabled', 'sms_new_appointment_enabled',
            'sms_new_appointment_template', 'sms_reminder_template',
            'currency', 'security_code', 'calendar_start_time', 'calendar_end_time',
            'cancelled_retention_hours', 'cancelled_color', 'multiple_services_color'
        ];

        $form_context = $_POST['form_context'] ?? '';

        foreach ($allowed_settings as $key) {
            $value = null;

            // Checkboxları sadece kendi formunda değilse atla, kendi formundaysa ve yoksa '0' yap
            if ($key === 'reminder_sms_enabled' || $key === 'sms_new_appointment_enabled') {
                if ($form_context === 'sms') {
                    $value = isset($_POST[$key]) ? '1' : '0';
                }
            } 
            // Diğer tüm alanlar için sadece POST'ta varsa güncelle
            else if (isset($_POST[$key])) {
                $value = clean($_POST[$key]);
            }

            // Değer belirlenmişse güncelle
            if ($value !== null) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            }
        }

        $db->commit();
        json_response('success', __('update_success'));
    } catch (Exception $e) {
        $db->rollBack();
        json_response('error', $e->getMessage());
    }
} else {
    json_response('error', 'Invalid request method.');
}
?>
