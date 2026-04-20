<?php
require_once '../functions.php';
check_login();

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Ay isimleri
$months_tr = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 
    7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];

// Takvim verileri
$first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
$number_of_days = date('t', $first_day_of_month);
$day_of_week = date('N', $first_day_of_month); // 1 (Pzt) - 7 (Paz)

// Randevu sayılarını çekelim
$start_date = "$year-$month-01";
$end_date = "$year-$month-$number_of_days";

$stmt = $db->prepare("SELECT appointment_date, COUNT(*) as count FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY appointment_date");
$stmt->execute([$start_date, $end_date]);
$apt_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['2024-01-11' => 3, ...]

// Navigasyon için önceki/sonraki ay verileri
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month == 0) { $prev_month = 12; $prev_year--; }

$next_month = $month + 1;
$next_year = $year;
if ($next_month == 13) { $next_month = 1; $next_year++; }

$today = date('Y-m-d');
?>

<div class="widget-title">
    <span><?= $months_tr[$month] . ' ' . $year ?></span>
    <div style="font-size: 14px; color: var(--text-muted);">
        <i class="fa-solid fa-chevron-left" style="margin-right: 15px; cursor: pointer;" onclick="loadMiniCalendar(<?= $prev_month ?>, <?= $prev_year ?>)"></i>
        <i class="fa-solid fa-chevron-right" style="cursor: pointer;" onclick="loadMiniCalendar(<?= $next_month ?>, <?= $next_year ?>)"></i>
    </div>
</div>

<div class="mini-calendar">
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; text-align: center; font-size: 10px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
        <span>Pzt</span><span>Sal</span><span>Çar</span><span>Per</span><span>Cum</span><span>Cmt</span><span>Paz</span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center;">
        <?php 
        // Boş hücreler (Ayın başladığı günden öncesi)
        for($i = 1; $i < $day_of_week; $i++) { echo '<span></span>'; }

        // Günler
        for($day = 1; $day <= $number_of_days; $day++): 
            $current_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
            $has_apt = isset($apt_counts[$current_date]);
            $is_today = ($current_date == $today);
            $count = $has_apt ? $apt_counts[$current_date] : 0;
        ?>
            <div class="mini-day <?= $is_today ? 'is-today' : '' ?>" onclick="selectMiniDay('<?= $current_date ?>')">
                <span class="day-num"><?= $day ?></span>
                <?php if($has_apt): ?>
                    <div class="apt-dots">
                        <?php for($d=0; $d < min($count, 3); $d++): ?>
                            <span class="dot"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>
