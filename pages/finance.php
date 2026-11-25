<?php 
require_once '../functions.php';
check_login();

// Filtreleme Parametreleri
$filter_type = $_GET['type'] ?? 'all';
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';

$where = [];
$params = [];

if ($filter_type !== 'all' && in_array($filter_type, ['income', 'expense'])) {
    $where[] = "f.type = ?";
    $params[] = $filter_type;
}
if ($filter_start) {
    $where[] = "f.created_at >= ?";
    $params[] = $filter_start . ' 00:00:00';
}
if ($filter_end) {
    $where[] = "f.created_at <= ?";
    $params[] = $filter_end . ' 23:59:59';
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Header İstatistikleri Hesaplanıyor (Dinamik Kartlar İçin)
$sum_income_q = $db->prepare("SELECT SUM(amount) FROM finance f " . (empty($where_clause) ? " WHERE f.type='income'" : $where_clause . " AND f.type='income'"));
$sum_income_q->execute($params);
$filtered_income = $sum_income_q->fetchColumn() ?? 0;

$sum_expense_q = $db->prepare("SELECT SUM(amount) FROM finance f " . (empty($where_clause) ? " WHERE f.type='expense'" : $where_clause . " AND f.type='expense'"));
$sum_expense_q->execute($params);
$filtered_expense = $sum_expense_q->fetchColumn() ?? 0;

$filtered_net = $filtered_income - $filtered_expense;
$is_filtered = !empty($where);

// VERİLERİ BURADA ÇEKELİM (HAYAT MEMAT MESELESİ: YAZDIRMA ALANINDAN ÖNCE OLMALI)
$stmt = $db->prepare("
    SELECT f.*, s.name as service_name, s.total_sessions,
           CASE 
               WHEN a.id IS NOT NULL AND s.total_sessions > 1 THEN
                   (
                       SELECT COUNT(*)
                       FROM appointments a2
                       WHERE a2.customer_id = a.customer_id 
                         AND a2.service_id = a.service_id 
                         AND a2.is_deleted = 0 
                         AND a2.status != 'cancelled'
                         AND (a2.appointment_date < a.appointment_date 
                              OR (a2.appointment_date = a.appointment_date AND (a2.appointment_time < a.appointment_time OR (a2.appointment_time = a.appointment_time AND a2.id <= a.id))))
                   )
               ELSE 1 
           END as session_num
    FROM finance f 
    LEFT JOIN appointments a ON f.appointment_id = a.id 
    LEFT JOIN services s ON a.service_id = s.id 
    $where_clause 
    ORDER BY f.created_at DESC
");
$stmt->execute($params);
$records = $stmt->fetchAll();

include '../includes/header.php'; 
?>

<style>
/* --- Filtreleme Paneli Stilleri --- */
.filter-toolbar {
    display: flex;
    align-items: center;
    gap: 25px;
    background: white;
    padding: 15px 25px;
    border-radius: 20px;
    border: 1px solid rgba(165,166,216,0.15);
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(165,166,216,0.05);
}
.filter-item { display: flex; align-items: center; gap: 12px; }
.filter-item label { font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.5px; display: flex; align-items: center; gap: 6px; }
.filter-item label i { font-size: 14px; color: var(--primary); opacity: 0.7; }
.filter-input { background: #f8fafc; border: 1px solid rgba(165,166,216,0.1); border-radius: 12px; padding: 10px 15px; font-size: 13px; color: var(--text-main); width: 140px; transition: all 0.3s ease; }
.filter-input:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(165,166,216,0.1); outline: none; }

/* --- EKran Tasarımı (Pistachio UI) --- */
.date-badge { background: rgba(134, 135, 195, 0.1); color: var(--primary); padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 10px; }
.description-block-card { padding: 8px 16px; border-radius: 10px; display: inline-flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 700; }
.description-block-card i { font-size: 14px; }
.description-block-card.income { background: rgba(190, 242, 100, 0.12); color: #74b816; border-color: rgba(163, 217, 71, 0.2); }
.description-block-card.expense { background: #fef2f2; color: #dc2626; }
.description-block-card.cancelled { background: #f1f5f9; color: #64748b; }
.description-block-card.missed { background: #EFEBE9; color: #5D4037; border-color: rgba(93, 64, 55, 0.1); }
.type-pill { padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; }
.type-pill.income { background: #ecfbe6; color: #74b816; }
.type-pill.expense { background: #fee2e2; color: #dc2626; }
.type-pill.cancelled { background: #f1f5f9; color: #64748b; }
.type-pill.missed { background: #EFEBE9; color: #5D4037; }
.amount-text { font-weight: 800; font-size: 15px; }
.amount-text.income { color: #74b816; }
.amount-text.expense { color: #dc2626; }
.amount-text.cancelled { color: #64748b; }
.amount-text.missed { color: #5D4037; }



/* --- TEMİZ YAZDIRMA TASARIMI (KULLANICI TALEBİ) --- */
@media print {
    @page { margin: 1cm; size: A4; }
    
    /* Renkleri ve çizgileri koru */
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

    /* Web arayüzü elemanlarını gizle */
    .sidebar, .top-nav, .filter-toolbar, .header-main-mobile, .premium-table-scroll, .no-print, .action-col, .btn-premium { 
        display: none !important; 
    }

    /* Arka Planı Beyaz Yap (Yeşil tonu tamamen kaldır) */
    html, body, .app-container, .main-wrapper, .large-calendar-container { 
        background: white !important; 
        background-color: white !important;
        margin: 0 !important; 
        padding: 0 !important; 
        box-shadow: none !important;
    }

    body { font-family: 'Outfit', sans-serif !important; }

    /* Yazdırma Alanını Göster */
    .print-only-section { 
        display: block !important; 
        visibility: visible !important;
        width: 100% !important;
        padding: 20px !important; /* Kenar Boşlukları Eklendi */
    }

    /* Kartlar (Tepedeki Özet) */
    .print-stats-grid {
        display: flex !important;
        justify-content: space-between !important;
        gap: 15px !important;
        margin-bottom: 30px !important;
    }
    .print-stat-card {
        flex: 1;
        padding: 15px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        text-align: center;
        background: #f8fafc !important;
    }
    .print-stat-card .label { display: block; font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
    .print-stat-card .value { font-size: 18px; font-weight: 800; color: #0f172a; }

    /* Başlık ve Tarih */
    .print-report-title { margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
    .print-report-title h1 { font-size: 22px; margin: 0; font-weight: 900; }
    .print-report-title p { font-size: 13px; color: #64748b; margin: 5px 0 0; font-weight: 600; }

    /* SÜTUNLARIN YAN YANA DURMASI İÇİN EN SERT KURALLAR */
    body .print-only-section .print-clean-table { 
        display: table !important; 
        border-collapse: collapse !important; 
        table-layout: fixed !important; 
        width: 100% !important;
        margin: 20px 0 !important;
        border: 1px solid #e2e8f0 !important;
    }
    body .print-only-section .print-clean-table thead { display: table-header-group !important; }
    body .print-only-section .print-clean-table tbody { display: table-row-group !important; }
    body .print-only-section .print-clean-table tr { display: table-row !important; background: transparent !important; }
    
    body .print-only-section .print-clean-table th,
    body .print-only-section .print-clean-table td { 
        display: table-cell !important; 
        vertical-align: middle !important;
        padding: 12px 10px !important; 
        border-bottom: 1px solid #e2e8f0 !important;
        background: transparent !important;
        font-size: 11px !important;
        height: auto !important;
        word-wrap: break-word !important;
    }

    body .print-only-section .print-clean-table th { 
        font-weight: 800 !important;
        background: #f8fafc !important; 
        color: #64748b !important;
        text-transform: uppercase !important;
    }

    /* Sütun Genişlikleri */
    .col-date { width: 95px !important; }
    .col-amount { width: 100px !important; text-align: right !important; }
    .col-type { width: 85px !important; white-space: nowrap !important; }

    .print-section-header {
        font-size: 20px !important;
        font-weight: 900 !important;
        text-align: center !important;
        margin: 25px 0 15px !important;
        text-transform: uppercase !important;
        color: #0f172a !important;
        display: block !important;
    }

    .text-success { color: #16a34a !important; font-weight: 700 !important; }
    .text-danger { color: #dc2626 !important; font-weight: 700 !important; }
    .text-missed { color: #5D4037 !important; font-weight: 700 !important; }
}

.no-pc { display: none !important; }
.no-mobile { display: inline-flex !important; }

/* Ekran görünümünde yazdırma alanını gizle */
.print-only-section { display: none; }

/* --- Mobil Kısıtlamalar --- */
@media (max-width: 1024px) {
    .no-mobile-report-view { display: none !important; visibility: hidden !important; height: 0 !important; overflow: hidden !important; }
    .header-main-mobile { flex-direction: column !important; align-items: center !important; gap: 20px !important; }
    .header-actions-mobile-flex { flex-direction: column !important; width: 100% !important; gap: 12px !important; }
    .header-actions-mobile-flex .btn-premium { width: 100% !important; justify-content: center !important; padding: 15px !important; }
    .header-divider-mobile { display: none !important; }
    
    .finance-filters-mobile { flex-direction: column !important; align-items: stretch !important; gap: 20px !important; padding: 20px !important; }
    .filter-item-mobile { width: 100% !important; justify-content: space-between !important; flex-direction: column !important; align-items: flex-start !important; gap: 10px !important; }
    .filter-inputs-mobile { flex-direction: column !important; width: 100% !important; gap: 10px !important; }
    .filter-inputs-mobile .filter-input { width: 100% !important; }
    .filter-inputs-mobile span { display: none !important; }
    .filter-spacer { display: none !important; }
    .btn-apply-mobile, .btn-clear-mobile { width: 100% !important; justify-content: center !important; }
    
    /* Yeni Buton Grubu (Sil & Uygula için) */
    .filter-actions-group-mobile {
        display: flex !important;
        gap: 12px !important;
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    .filter-actions-group-mobile .btn-premium { flex: 1 !important; margin: 0 !important; }

    .no-pc { display: flex !important; }
    .no-mobile { display: none !important; }
    
    table.no-print thead { display: none !important; }
    .desktop-only { display: none !important; } /* Local override to ensure hiding */
    .finance-row-mobile {
        display: block !important;
        margin-bottom: 20px !important;
        background: transparent !important;
        padding: 0 !important;
        cursor: pointer;
        border: none !important;
    }
    .finance-row-mobile td.desktop-only {
        display: none !important;
    }
    .mobile-full-td { 
        display: block !important; 
        width: 100% !important; 
        border: none !important; 
        padding: 0 !important; 
    }
    .description-block-card { 
        padding: 25px 20px !important; 
        border-radius: 20px !important;
        width: 100% !important;
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 8px 25px rgba(45,46,66,0.06);
    }
}
</style>

<!-- SADECE YAZDIRIRKEN GÖRÜNECEK ALAN (KARTLAR + ARALIK + TABLO) -->
<div class="print-only-section">
    <div class="print-report-title">
        <h1>KLİNİKMOD FİNANSAL RAPOR</h1>
        <p><?= $is_filtered ? ($filter_start ? date('d.m.Y', strtotime($filter_start)) : '...') . ' - ' . ($filter_end ? date('d.m.Y', strtotime($filter_end)) : date('d.m.Y')) : 'TÜM ZAMANLAR' ?></p>
    </div>

    <?php if (is_admin()): ?>
    <div class="print-stats-grid">
        <div class="print-stat-card">
            <span class="label">TOPLAM GELİR</span>
            <span class="value"><?= number_format($filtered_income, 0, ',', '.') ?> <?= $currency ?></span>
        </div>
        <div class="print-stat-card">
            <span class="label">TOPLAM GİDER</span>
            <span class="value"><?= number_format($filtered_expense, 0, ',', '.') ?> <?= $currency ?></span>
        </div>
        <div class="print-stat-card">
            <span class="label">NET DURUM</span>
            <span class="value"><?= number_format($filtered_net, 0, ',', '.') ?> <?= $currency ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="print-section-header">Finans</div>

    <table class="print-clean-table">
        <thead>
            <tr>
                <th class="col-date">Tarih</th>
                <th>Açıklama</th>
                <th class="col-type">Yöntem</th>
                <th class="col-type">Tür</th>
                <th class="col-amount">Tutar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $row): 
                $amount = (float)$row['amount'];
                $is_expense = $row['type'] == 'expense';
                $is_missed = (strpos($row['description'], 'Randevuya Gelmedi:') !== false);
                $is_cancelled = !$is_missed && ($amount == 0 && (strpos($row['description'], 'İptal') !== false || strpos($row['description'], 'Randevu İptali') !== false || strpos($row['description'], '[İPTALİ]') !== false));
                
                $is_inc = $row['type'] == 'income' && !$is_missed && !$is_cancelled;
                
                $pm = $row['payment_method'] ?? 'cash';
                $pm_label = $pm == 'cash' ? 'Nakit' : ($pm == 'transfer' ? 'EFT' : 'Kart');
                
                $status_label_print = $is_missed ? 'GELMEDİ' : ($is_cancelled ? 'İPTAL' : ($is_expense ? 'GİDER' : 'GELİR'));
                $color_class = $is_missed ? 'text-missed' : ($is_cancelled ? 'text-muted' : ($is_expense ? 'text-danger' : 'text-success'));
            ?>
            <tr>
                <td><?= date('d.m.Y H:i', strtotime($row['created_at'])) ?></td>
                <td>
                    <?= clean($row['description']) ?>
                    <?php if(!empty($row['total_sessions']) && (int)$row['total_sessions'] > 1): 
                        $tot = (int)$row['total_sessions'];
                        $s_num = (int)($row['session_num'] ?? 1);
                        $used = (($s_num - 1) % $tot) + 1;
                        $rem = $tot - $used;
                        $cyc = floor(($s_num - 1) / $tot) + 1;
                    ?>
                        <small style="color: #219653; font-weight: 800; display: block; margin-top: 2px;">
                            [<?= clean($row['service_name']) ?>: <?= $used ?>/<?= $tot ?>. Seans (<?= $rem > 0 ? $rem . ' Kaldı' : 'Paket Bitti' ?>)<?= $cyc > 1 ? " - {$cyc}. Paket" : "" ?>]
                        </small>
                    <?php endif; ?>
                </td>
                <td><?= (!empty($pm_label)) ? $pm_label : '-' ?></td>
                <td><?= $status_label_print ?></td>
                <td style="text-align: right;" class="<?= $color_class ?>">
                    <?= $is_expense ? '- ' : ($is_inc ? '+ ' : '') ?><?= number_format($row['amount'], 2, ',', '.') ?> <?= $currency ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="large-calendar-container no-print">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;" class="no-print header-main-mobile">
        <h2 style="font-size: 26px; font-weight: 800; color: var(--text-main);" class="no-print"><?= __('finance') ?></h2>
        
        <div style="display: flex; gap: 12px;" class="header-actions-mobile-flex no-print">
            <button class="btn-premium no-print" style="background: #0f172a; color: white;" onclick="window.print()"><i class="fa-solid fa-file-pdf"></i> PDF İndir</button>
            <button class="btn-premium no-print" style="background: white; color: var(--text-main); border: 1px solid rgba(165,166,216,0.2);" onclick="window.print()"><i class="fa-solid fa-print"></i> Yazdır</button>
            <div class="header-divider-mobile no-print" style="width: 1px; height: 35px; background: rgba(165,166,216,0.1); margin: 0 5px;"></div>
            <button class="btn-premium no-print" style="background: var(--accent-lime); color: #1e3a1f;" onclick="addFinanceModal('income')"><i class="fa-solid fa-plus-circle"></i> Gelir Ekle</button>
            <button class="btn-premium no-print" style="background: var(--accent-coral); color: white;" onclick="addFinanceModal('expense')"><i class="fa-solid fa-minus-circle"></i> Gider Ekle</button>
        </div>
    </div>

    <!-- Hızlı Arama Kutusu -->
    <div class="finance-search-wrapper no-print" style="margin-bottom: 25px; flex-shrink: 0;">
        <div style="position: relative; display: flex; align-items: center;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 18px; color: var(--primary); opacity: 0.6; font-size: 14px;"></i>
            <input type="text" id="main-finance-search" placeholder="Müşteri adı, hizmet adı, açıklama veya ödeme yöntemi ile hızlıca ara..." 
                   style="width: 100%; padding: 14px 20px 14px 50px; border-radius: 16px; border: 1px solid rgba(134, 135, 195, 0.15); background: #FFFFFF; font-family: 'Outfit', sans-serif; font-size: 14px; color: var(--text-main); outline: none; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
        </div>
    </div>

    <!-- Filtreleme Paneli -->
    <form method="GET" class="filter-toolbar no-print finance-filters-mobile">
        <div class="filter-item filter-item-mobile">
            <label><i class="fa-solid fa-tags"></i> TÜR:</label>
            <select name="type" class="form-control" style="width: 140px;">
                <option value="all" <?= $filter_type == 'all' ? 'selected' : '' ?>>Tümü</option>
                <option value="income" <?= $filter_type == 'income' ? 'selected' : '' ?>>Gelir</option>
                <option value="expense" <?= $filter_type == 'expense' ? 'selected' : '' ?>>Gider</option>
            </select>
        </div>
        <div class="header-divider-mobile" style="width: 1px; height: 35px; background: rgba(165,166,216,0.1); margin: 0 10px;"></div>
        <div class="filter-item filter-item-mobile">
            <label><i class="fa-solid fa-calendar-days"></i> ARALIK:</label>
            <div style="display: flex; align-items: center; gap: 10px;" class="filter-inputs-mobile">
                <input type="text" name="start_date" id="start_date" class="filter-input" placeholder="Başlangıç" value="<?= $filter_start ?>">
                <span style="color: var(--text-muted); font-weight: 800; opacity: 0.3;">-</span>
                <input type="text" name="end_date" id="end_date" class="filter-input" placeholder="Bitiş" value="<?= $filter_end ?>">
            </div>
        </div>
        <div class="filter-spacer" style="flex: 1;"></div>
        
        <!-- Mobil Buton Grubu -->
        <div class="filter-actions-group-mobile no-pc" style="display: none;">
             <button type="button" class="btn-premium" style="background: var(--accent-coral); color: white;" onclick="bulkDeleteFinance()"><i class="fa-solid fa-trash-sweep"></i> Sil</button>
             <button type="submit" class="btn-premium" style="background: #0f172a; color: white;"><i class="fa-solid fa-circle-check"></i> Uygula</button>
        </div>

        <button type="button" class="btn-premium no-mobile" style="background: var(--accent-coral); color: white; margin-right: 10px;" onclick="bulkDeleteFinance()"><i class="fa-solid fa-trash-sweep"></i> Tümünü Sil</button>
        <button type="submit" class="btn-premium btn-apply-mobile no-mobile" style="padding: 12px 30px; font-size: 13px;"><i class="fa-solid fa-circle-check"></i> Uygula</button>
        
        <?php if($is_filtered): ?>
            <a href="finance.php" class="btn-secondary btn-clear-mobile" style="font-size: 13px; text-decoration: none; padding: 10px 20px; border-radius: 12px; display: flex; align-items: center; gap: 8px; background: rgba(165,166,216,0.05);"><i class="fa-solid fa-filter-circle-xmark"></i> Temizle</a>
        <?php endif; ?>
    </form>

    <div style="overflow-x: auto; overflow-y: auto; flex: 1; min-height: 0;" class="premium-table-scroll custom-scroll">
        <!-- EKRAN TABLOSU - TASARIM KORUNUYOR -->
        <table style="width: 100%; border-collapse: collapse; text-align: left; table-layout: fixed;" class="no-print">
            <thead>
                <tr style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; border-bottom: 2px solid rgba(165,166,216,0.05);">
                    <th style="padding: 15px 10px; width: 22%;">İşlem Tarihi</th>
                    <th style="padding: 15px 10px; width: 38%;">Açıklama</th>
                    <th style="padding: 15px 10px; width: 15%;">Tür</th>
                    <th style="padding: 15px 10px; width: 15%; text-align: right;">Tutar</th>
                    <th style="padding: 15px 10px; width: 10%; text-align: right;" class="no-print">Aksiyon</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Sistem entegrasyonlu SQL: Randevu ve Hizmet bilgilerini yukarı taşıdık
                
                if (empty($records)): ?>
                    <tr><td colspan="5" style="padding: 60px; text-align: center; color: var(--text-muted);">Henüz kayıt bulunmuyor.</td></tr>
                <?php else: 
                    foreach ($records as $row): 
                        $amount = (float)$row['amount'];
                        $is_expense = $row['type'] == 'expense';
                        $is_missed = (strpos($row['description'], 'Randevuya Gelmedi:') !== false);
                        $is_cancelled = !$is_missed && ($amount == 0 && (strpos($row['description'], 'İptal') !== false || strpos($row['description'], 'Randevu İptali') !== false || strpos($row['description'], '[İPTALİ]') !== false));
                        
                        $status_class = $is_missed ? 'missed' : ($is_cancelled ? 'cancelled' : ($is_expense ? 'expense' : 'income'));
                        $status_label = $is_missed ? 'GELMEDİ' : ($is_cancelled ? 'İPTAL' : ($is_expense ? 'GİDER' : 'GELİR'));
                    ?>
                    <tr class="finance-row-mobile" onclick="if(window.innerWidth < 1024) viewFinance(<?= $row['id'] ?>)" style="border-bottom: 1px solid rgba(165, 166, 216, 0.04); transition: all 0.2s;">
                        <td class="desktop-only" style="padding: 20px 10px;"><div class="date-badge"><i class="fa-regular fa-calendar"></i> <span><?= date('d.m.Y H:i', strtotime($row['created_at'])) ?></span></div></td>
                        <td class="mobile-full-td" style="padding: 20px 10px;">
                            <div class="description-block-card <?= $status_class ?>" style="width: 100%; display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fa-solid <?= $is_missed ? 'fa-circle-xmark' : ($is_cancelled ? 'fa-ban' : ($is_expense ? 'fa-arrow-trend-down' : 'fa-arrow-trend-up')) ?>"></i> 
                                    <span><?= clean($row['description']) ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: 24px;">
                                    <?php if(!empty($row['total_sessions']) && (int)$row['total_sessions'] > 1): 
                                        $tot = (int)$row['total_sessions'];
                                        $s_num = (int)($row['session_num'] ?? 1);
                                        $used = (($s_num - 1) % $tot) + 1;
                                        $rem = $tot - $used;
                                        $cyc = floor(($s_num - 1) / $tot) + 1;
                                    ?>
                                        <div style="display: inline-flex; align-items: center; gap: 4px; background: rgba(39, 174, 96, 0.15); color: #219653; border-radius: 6px; padding: 2px 8px; font-size: 11px; font-weight: 800;">
                                            <i class="fa-solid fa-box-open" style="font-size: 10px;"></i>
                                            <span><?= clean($row['service_name']) ?>: <?= $used ?>/<?= $tot ?>. Seans (<?= $rem > 0 ? $rem . ' Kaldı' : 'Paket Bitti' ?>)<?= $cyc > 1 ? " [{$cyc}. Paket]" : "" ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(!empty($row['payment_method'])): 
                                        $pm = $row['payment_method'];
                                        $pm_text = $pm == 'cash' ? 'Nakit' : ($pm == 'transfer' ? 'EFT/Havale' : 'Kredi Kartı');
                                        $pm_icon = $pm == 'cash' ? 'fa-money-bill-wave' : ($pm == 'transfer' ? 'fa-building-columns' : 'fa-credit-card');
                                    ?>
                                        <div style="font-size: 10px; opacity: 0.7; font-weight: 800; display: flex; align-items: center; gap: 4px; text-transform: uppercase;">
                                            <i class="fa-solid <?= $pm_icon ?>"></i> <?= $pm_text ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="desktop-only" style="padding: 20px 10px;"><span class="type-pill <?= $status_class ?>"><?= $status_label ?></span></td>
                        <td class="desktop-only" style="padding: 20px 10px; text-align: right;"><span class="amount-text <?= $status_class ?>"><?= $is_cancelled ? '' : ($is_expense ? '- ' : '+ ') ?><?= number_format($row['amount'], 2, '.', ',') ?> <?= $currency ?></span></td>
                        <td class="desktop-only action-col" style="padding: 20px 10px; text-align: right;" class="no-print"><div class="action-wrapper-premium"><div class="action-btn-pc bg-green-soft" onclick="editFinanceModal(<?= $row['id'] ?>)"><i class="fa-solid fa-pen-to-square"></i></div><div class="action-btn-pc bg-red-soft" onclick="deleteFinance(<?= $row['id'] ?>)"><i class="fa-solid fa-trash-can"></i></div></div></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>


    </div>
</div>

<script>
$(document).ready(function() {
    flatpickr("#start_date", { 
        locale: "tr", 
        altInput: true,
        altFormat: "d.m.Y",
        dateFormat: "Y-m-d" 
    });
    flatpickr("#end_date", { 
        locale: "tr", 
        altInput: true,
        altFormat: "d.m.Y",
        dateFormat: "Y-m-d" 
    });

    // Finans Tablosu Anlık Arama Mantığı
    $('#main-finance-search').on('input', function() {
        const value = $(this).val().toLocaleLowerCase('tr-TR').trim();
        $('.finance-row-mobile').each(function() {
            const text = $(this).text().toLocaleLowerCase('tr-TR');
            if (text.indexOf(value) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        const visibleRows = $('.finance-row-mobile:visible').length;
        $('#no-finance-search-found').remove();
        if (visibleRows === 0 && $('.finance-row-mobile').length > 0) {
            $('table.no-print tbody').append('<tr id="no-finance-search-found"><td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);"><i class="fa-solid fa-magnifying-glass" style="font-size: 24px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>Aradığınız kriterlere uygun finans kaydı bulunamadı.</td></tr>');
        }
    });
});



function addFinanceModal(type) {
    const title = type === 'income' ? 'Gelir Ekle' : 'Gider Ekle';
    const body = `
        <form id="financeForm">
            <input type="hidden" name="type" value="${type}">
            <div class="form-group">
                <label>Tutar (₺)</label>
                <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>Ödeme Yöntemi</label>
                <select name="payment_method" class="form-control">
                    <option value="cash">Nakit</option>
                    <option value="transfer">EFT/Havale</option>
                    <option value="card">Kredi Kartı</option>
                </select>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>Açıklama</label>
                <input type="text" name="description" class="form-control" placeholder="İşlem detayı..." required>
            </div>
        </form>`;
    const footer = `<button onclick="saveFinance()" class="btn-premium" style="width: 100%;">${title}</button>`;
    openGlobalModal(title, body, footer);
}

function saveFinance() {
    const data = $('#financeForm').serialize();
    $.post('../actions/finance_add.php', data, function(res) {
        if(res.status == 'success') location.reload();
        else showToast('Hata', res.message, 'error');
    });
}

function editFinanceModal(id) {
    $.get('../actions/get_finance.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const f = res.data;
            const body = `
                <form id="editFinanceForm">
                    <input type="hidden" name="id" value="${f.id}">
                    <div class="form-group">
                        <label>Tutar (₺)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="${f.amount}" required>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Tür</label>
                        <select name="type" class="form-control" onchange="togglePaymentMethod(this.value)">
                            <option value="income" ${f.type == 'income' ? 'selected' : ''}>Gelir</option>
                            <option value="expense" ${f.type == 'expense' ? 'selected' : ''}>Gider</option>
                        </select>
                    </div>
                    <div class="form-group pm-group" style="margin-top: 15px;">
                        <label>Ödeme Yöntemi</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash" ${f.payment_method == 'cash' ? 'selected' : ''}>Nakit</option>
                            <option value="transfer" ${f.payment_method == 'transfer' ? 'selected' : ''}>EFT/Havale</option>
                            <option value="card" ${f.payment_method == 'card' ? 'selected' : ''}>Kredi Kartı</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Açıklama</label>
                        <input type="text" name="description" class="form-control" value="${f.description}" required>
                    </div>
                </form>`;
            const footer = `<button onclick="updateFinance()" class="btn-premium" style="width: 100%;">Güncelle</button>`;
            openGlobalModal('Kaydı Düzenle', body, footer);
        }
    });
}

function togglePaymentMethod(type) {
    // Artık her ikisinde de göründüğü için gizleme yapmıyoruz.
    // İleride özel bir mantık gerekirse burası kullanılabilir.
}


function updateFinance() {
    const data = $('#editFinanceForm').serialize();
    $.post('../actions/finance_update.php', data, function(res) {
        if(res.status == 'success') location.reload();
        else showToast('Hata', res.message, 'error');
    });
}

function deleteFinance(id) {
    const body = `<div style="text-align: center; padding: 20px;"><i class="fa-solid fa-trash-can" style="font-size: 48px; color: #f43f5e; margin-bottom: 20px;"></i><h3>Kaydı Sil?</h3></div>`;
    const footer = `<button onclick="confirmDeleteFinance(${id})" class="btn-premium" style="background: #f43f5e;">Evet, Sil</button><button onclick="closeGlobalModal()" class="btn-secondary">İptal</button>`;
    openGlobalModal('Kaydı Sil', body, footer);
}

function confirmDeleteFinance(id) {
    $.post('../actions/finance_delete.php', { id: id }, function(res) {
        if(res.status == 'success') location.reload();
        else showToast('Hata', res.message, 'error');
    });
}

function bulkDeleteFinance() {
    const isFiltered = <?= $is_filtered ? 'true' : 'false' ?>;
    const recordsCount = <?= count($records) ?>;
    
    if (recordsCount === 0) {
        showToast('Bilgi', 'Silinecek herhangi bir kayıt bulunamadı.', 'info');
        return;
    }

    const title = isFiltered ? 'Filtreli Kayıtları Sil' : 'Tüm Kayıtları Temizle';
    const warning = isFiltered 
        ? `Şu an listelenen <b>${recordsCount} adet</b> filtreli kayıt silinecektir.` 
        : `Sistemdeki <b>tüm finansal veriler (${recordsCount} kayıt)</b> kalıcı olarak silinecektir.`;

    const body = `
        <div style="text-align: center; padding: 20px;">
            <div style="width: 80px; height: 80px; background: #fff1f2; color: #f43f5e; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 32px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-weight: 800; color: var(--text-main); margin-bottom: 10px;">Emin misiniz?</h3>
            <p style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">${warning}<br><br><b>Bu işlem geri alınamaz!</b></p>
        </div>
    `;
    
    const footer = `
        <button onclick="confirmBulkDelete()" class="btn-premium" style="background: #f43f5e; width: 100%; margin-bottom: 10px;">Evet, Her Şeyi Sil</button>
        <button onclick="closeGlobalModal()" class="btn-secondary" style="width: 100%; border: none;">Vazgeç</button>
    `;

    openGlobalModal(title, body, footer);
}

function confirmBulkDelete() {
    const data = {
        type: '<?= $filter_type ?>',
        start_date: '<?= $filter_start ?>',
        end_date: '<?= $filter_end ?>'
    };

    $.post('../actions/finance_bulk_delete.php', data, function(res) {
        if(res.status == 'success') {
            showToast('Başarılı', res.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function viewFinance(id) {
    $.get('../actions/get_finance.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const f = res.data;
            const is_inc = f.type === 'income';
            const body = `
                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 15px;">
                        <span class="status-pill-large" style="background: ${is_inc ? 'rgba(190, 242, 100, 0.12)' : '#fef2f2'}; color: ${is_inc ? '#74b816' : '#dc2626'}; margin: 0;">
                            <i class="fa-solid ${is_inc ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'}"></i> ${is_inc ? 'GELİR KAYDI' : 'GİDER KAYDI'}
                        </span>
                        <div class="call-btn" style="margin: 0; padding: 10px 20px; background: rgba(134, 135, 195, 0.1); color: var(--primary); font-weight: 800;">
                            ${parseFloat(f.amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${globalCurrency}
                        </div>
                    </div>
                    
                    <div class="modal-apt-actions" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                        <button onclick="editFinanceModal(${f.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: var(--text-main); border: none; box-shadow: 0 4px 12px rgba(45, 46, 66, 0.2);">
                            <i class="fa-solid fa-pen-to-square"></i> KAYDI DÜZENLE
                        </button>
                        <button onclick="deleteFinance(${f.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: #f43f5e; border: none; box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2);">
                            <i class="fa-solid fa-trash-can"></i> KAYDI SİL
                        </button>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: var(--primary);">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">İşlem Tarihi</span>
                            <span class="detail-value">${new Date(f.created_at).toLocaleString('tr-TR')}</span>
                        </div>
                    </div>
                    <div class="detail-item-card" style="grid-column: span 2;">
                        <div class="detail-icon-box" style="color: #9b59b6;">
                            <i class="fa-solid fa-align-left"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Açıklama</span>
                            <span class="detail-value" style="font-size: 13px; line-height: 1.5;">${f.description}</span>
                        </div>
                    </div>
                    ${(f.total_sessions && parseInt(f.total_sessions) > 1) ? `
                    <div class="detail-item-card" style="grid-column: span 2;">
                        <div class="detail-icon-box" style="color: #27ae60;">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Paket & Seans Bilgisi</span>
                            <span class="detail-value" style="font-weight: 800; color: #219653;">
                                ${f.service_name}: ${((parseInt(f.session_num) - 1) % parseInt(f.total_sessions)) + 1}/${f.total_sessions}. Seans (${(parseInt(f.total_sessions) - (((parseInt(f.session_num) - 1) % parseInt(f.total_sessions)) + 1)) > 0 ? (parseInt(f.total_sessions) - (((parseInt(f.session_num) - 1) % parseInt(f.total_sessions)) + 1)) + ' Seans Kaldı' : 'Paket Tamamlandı'})
                            </span>
                        </div>
                    </div>
                    ` : ''}
                    ${f.payment_method ? `
                    <div class="detail-item-card" style="grid-column: span 2;">
                        <div class="detail-icon-box" style="color: #f1c40f;">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Ödeme Yöntemi</span>
                            <span class="detail-value" style="font-weight: 800; text-transform: uppercase;">
                                ${f.payment_method === 'cash' ? 'Nakit' : (f.payment_method === 'transfer' ? 'EFT/Havale' : 'Kredi Kartı')}
                            </span>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
            openGlobalModal('İşlem Detayları', body, '');
        }
    });
}
</script>
</div> <!-- no-print large-calendar-container bitti -->

<div class="no-print">
    <?php include '../includes/footer.php'; ?>
</div>
