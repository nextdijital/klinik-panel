<?php 
require_once '../functions.php';
check_login();

// Yaklaşan Randevuları çek (Tam olarak dashboard.php'deki mantıkla)
$upcoming_appts = $db->query("
    SELECT a.id, a.appointment_time, c.first_name, c.last_name, s.name as service_name 
    FROM appointments a 
    JOIN customers c ON a.customer_id = c.id 
    JOIN services s ON a.service_id = s.id 
    LEFT JOIN staff st ON a.staff_id = st.id
    WHERE a.appointment_date = CURDATE() 
    AND a.appointment_time >= CURTIME()
    AND a.appointment_time <= ADDTIME(CURTIME(), '06:00:00')
    AND a.status = 'pending'
    AND a.is_deleted = 0
    AND c.is_deleted = 0
    AND s.is_deleted = 0
    AND (a.staff_id IS NULL OR st.is_deleted = 0)
    AND MINUTE(a.appointment_time) IN (0, 30)
    ORDER BY a.appointment_time ASC
")->fetchAll();

ob_start();
?>
<div class="widget-title" id="sidebar-count-wrapper" style="margin-bottom: 15px;">
    <span>Yaklaşan Randevular</span>
    <span class="premium-badge badge-coral" style="font-size: 9px; animation: pulse 2s infinite;"><?= count($upcoming_appts) ?></span>
</div>

<div style="flex: 1; overflow-y: auto; padding-right: 5px;" class="custom-scroll">
    <?php if(empty($upcoming_appts)): ?>
        <div style="text-align:center; padding: 40px 10px; color: var(--text-muted); font-size: 13px;">
            <i class="fa-solid fa-calendar-check" style="font-size: 24px; opacity: 0.2; display: block; margin-bottom: 10px;"></i>
            Önümüzdeki 1 saat içinde randevu bulunmuyor.
        </div>
    <?php else: ?>
        <div id="upcoming-list" style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach($upcoming_appts as $appt): ?>
                <div class="upcoming-item-card" id="upcoming-<?= $appt['id'] ?>" data-time="<?= date('Y-m-d') ?>T<?= $appt['appointment_time'] ?>" style="display: flex; align-items: center; gap: 12px; padding: 10px; background: rgba(134, 135, 195, 0.03); border-radius: 12px; border: 1px solid rgba(134, 135, 195, 0.05); transition: all 0.3s ease;">
                    <div style="width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
                        <?= date('H:i', strtotime($appt['appointment_time'])) ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 13px; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= $appt['first_name'] . ' ' . $appt['last_name'] ?></div>
                        <div class="countdown-timer" style="font-size: 11px; color: var(--accent-coral); font-weight: 800;">--:--</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
$html = ob_get_clean();
echo json_encode(['status' => 'success', 'html' => $html]);
?>
