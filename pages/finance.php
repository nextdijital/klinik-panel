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
    $where[] = "DATE(f.created_at) >= ?";
    $params[] = $filter_start;
}
if ($filter_end) {
    $where[] = "DATE(f.created_at) <= ?";
    $params[] = $filter_end;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Header İstatistikleri Hesaplanıyor (Dinamik Kartlar İçin)
$sum_income_q = $db->prepare("SELECT SUM(amount) FROM finance f " . (empty($where_clause) ? " WHERE type='income'" : $where_clause . " AND type='income'"));
$sum_income_q->execute($params);
$filtered_income = $sum_income_q->fetchColumn() ?? 0;

$sum_expense_q = $db->prepare("SELECT SUM(amount) FROM finance f " . (empty($where_clause) ? " WHERE type='expense'" : $where_clause . " AND type='expense'"));
$sum_expense_q->execute($params);
$filtered_expense = $sum_expense_q->fetchColumn() ?? 0;

$filtered_net = $filtered_income - $filtered_expense;
$is_filtered = !empty($where);

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
.type-pill { padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; }
.type-pill.income { background: #ecfbe6; color: #74b816; }
.type-pill.expense { background: #fee2e2; color: #dc2626; }
.type-pill.cancelled { background: #f1f5f9; color: #64748b; }
.amount-text { font-weight: 800; font-size: 15px; }
.amount-text.income { color: #74b816; }
.amount-text.expense { color: #dc2626; }
.amount-text.cancelled { color: #64748b; }

/* --- PROFESYONEL YAZDIRMA TASARIMI --- */
.print-only { display: none; }

@media print {
    @page { margin: 1cm; size: A4; }
    body { background: white !important; font-family: 'Outfit', sans-serif !important; }
    .no-print, .sidebar, .top-nav, .filter-toolbar, .action-col { display: none !important; }
    .main-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; left: 0 !important; box-shadow: none !important; background: white !important; }
    .large-calendar-container { box-shadow: none !important; padding: 0 !important; visibility: visible !important; position: relative !important; width: 100% !important; }
    
    .print-only { display: block !important; width: 100% !important; }
    
    /* Header Branding - Boşluk ve Hizalama Düzenlendi */
    .print-header { 
        display: flex !important; 
        justify-content: space-between !important; 
        align-items: center !important; 
        margin-bottom: 40px; 
        border-bottom: 2px solid #f8fafc; 
        padding-bottom: 30px; 
        width: 100% !important;
    }
    .print-header-left h1 { font-size: 28px; font-weight: 900; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
    .print-header-right { text-align: right; }
    .print-header-right h2 { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; }
    .print-header-right p { color: #64748b; font-size: 14px; margin-top: 6px; font-weight: 600; }
    
    /* Özet Kartları - Premium & Stabil Düzen */
    .print-stats { 
        display: flex !important; 
        justify-content: center !important; 
        gap: 25px; 
        margin-bottom: 50px; 
        width: 100% !important;
    }
    .print-stat-item { 
        width: 190px;
        padding: 24px 15px; 
        border: 1.5px solid #f1f5f9; 
        border-radius: 20px; 
        background: #ffffff;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .print-stat-label { 
        font-size: 10px; 
        color: #64748b; 
        text-transform: uppercase; 
        margin-bottom: 12px; 
        font-weight: 800; 
        letter-spacing: 1.2px; 
    }
    .print-stat-value { font-size: 26px; font-weight: 900; line-height: 1; }
    .print-stat-value.inc { color: #16a34a; }
    .print-stat-value.exp { color: #dc2626; }
    .print-stat-value.net { color: #2563eb; }

    .print-table-container { 
        border: 1.5px solid #f1f5f9; 
        border-radius: 16px; 
        overflow: hidden; 
        width: 100% !important; 
        margin-top: 20px;
    }
    .print-table { width: 100% !important; border-collapse: collapse !important; border: none !important; }
    .print-table th { padding: 15px 12px; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 800; border-bottom: 2px solid #f1f5f9; text-align: left; background: #fdfdfe; }
    .print-table td { padding: 16px 12px; font-size: 14px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 500; }
    .print-table tr:last-child td { border-bottom: none !important; }
    
    .pt-type-income { color: #16a34a; font-weight: 800; }
    .pt-type-expense { color: #dc2626; font-weight: 800; }
    .pt-amount { font-weight: 800; text-align: right; font-size: 15px; }
}

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

<div class="large-calendar-container">
    <!-- Yazdırma Modu Header ve Kartlar (Sadece Yazdırma Esnasında Görünür) -->
    <div class="print-only no-mobile-report-view">
        <div class="print-header">
            <div class="print-header-left"><h1>KLİNİKMOD</h1></div>
            <div class="print-header-right">
                <h2>FİNANSAL RAPOR</h2>
                <p><?= $is_filtered ? ($filter_start ? date('d.m.Y', strtotime($filter_start)) : '...') . ' - ' . ($filter_end ? date('d.m.Y', strtotime($filter_end)) : date('d.m.Y')) : 'TÜM ZAMANLAR' ?></p>
            </div>
        </div>
        <div class="print-stats">
            <div class="print-stat-item">
                <div class="print-stat-label">TOPLAM GELİR</div>
                <div class="print-stat-value inc"><?= number_format($filtered_income, 0, ',', '.') ?> <?= $currency ?></div>
            </div>
            <div class="print-stat-item">
                <div class="print-stat-label">TOPLAM GİDER</div>
                <div class="print-stat-value exp"><?= number_format($filtered_expense, 0, ',', '.') ?> <?= $currency ?></div>
            </div>
            <div class="print-stat-item">
                <div class="print-stat-label">NET DURUM</div>
                <div class="print-stat-value net"><?= number_format($filtered_net, 0, ',', '.') ?> <?= $currency ?></div>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;" class="no-print header-main-mobile">
        <h2 style="font-size: 26px; font-weight: 800; color: var(--text-main);"><?= __('finance') ?></h2>
        
        <div style="display: flex; gap: 12px;" class="header-actions-mobile-flex">
            <button class="btn-premium" style="background: #0f172a; color: white;" onclick="window.print()"><i class="fa-solid fa-file-pdf"></i> PDF İndir</button>
            <button class="btn-premium" style="background: white; color: var(--text-main); border: 1px solid rgba(165,166,216,0.2);" onclick="window.print()"><i class="fa-solid fa-print"></i> Yazdır</button>
            <div class="header-divider-mobile" style="width: 1px; height: 35px; background: rgba(165,166,216,0.1); margin: 0 5px;"></div>
            <button class="btn-premium" style="background: var(--accent-lime); color: #1e3a1f;" onclick="addFinanceModal('income')"><i class="fa-solid fa-plus-circle"></i> Gelir Ekle</button>
            <button class="btn-premium" style="background: var(--accent-coral); color: white;" onclick="addFinanceModal('expense')"><i class="fa-solid fa-minus-circle"></i> Gider Ekle</button>
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
        <button type="submit" class="btn-premium btn-apply-mobile" style="padding: 12px 30px; font-size: 13px;"><i class="fa-solid fa-circle-check"></i> Uygula</button>
        <?php if($is_filtered): ?>
            <a href="finance.php" class="btn-secondary btn-clear-mobile" style="font-size: 13px; text-decoration: none; padding: 10px 20px; border-radius: 12px; display: flex; align-items: center; gap: 8px; background: rgba(165,166,216,0.05);"><i class="fa-solid fa-filter-circle-xmark"></i> Temizle</a>
        <?php endif; ?>
    </form>

    <div style="overflow-x: auto;" class="premium-table-scroll">
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
                // Sistem entegrasyonlu SQL: Randevu ve Hizmet bilgilerini çek
                $stmt = $db->prepare("
                    SELECT f.*, s.name as service_name 
                    FROM finance f 
                    LEFT JOIN appointments a ON f.appointment_id = a.id 
                    LEFT JOIN services s ON a.service_id = s.id 
                    $where_clause 
                    ORDER BY f.created_at DESC
                ");
                $stmt->execute($params);
                $records = $stmt->fetchAll();

                if (empty($records)): ?>
                    <tr><td colspan="5" style="padding: 60px; text-align: center; color: var(--text-muted);">Henüz kayıt bulunmuyor.</td></tr>
                <?php else: 
                    foreach ($records as $row): 
                        $amount = (float)$row['amount'];
                        $is_expense = $row['type'] == 'expense';
                        $is_cancelled = ($amount == 0 && (strpos($row['description'], 'İptal') !== false || strpos($row['description'], 'Gelmeme') !== false));
                        $status_class = $is_cancelled ? 'cancelled' : ($is_expense ? 'expense' : 'income');
                        $status_label = $is_cancelled ? 'İPTAL' : ($is_expense ? 'GİDER' : 'GELİR');
                    ?>
                    <tr class="finance-row-mobile" onclick="if(window.innerWidth < 1024) viewFinance(<?= $row['id'] ?>)" style="border-bottom: 1px solid rgba(165, 166, 216, 0.04); transition: all 0.2s;">
                        <td class="desktop-only" style="padding: 20px 10px;"><div class="date-badge"><i class="fa-regular fa-calendar"></i> <span><?= date('d.m.Y H:i', strtotime($row['created_at'])) ?></span></div></td>
                        <td class="mobile-full-td" style="padding: 20px 10px;"><div class="description-block-card <?= $status_class ?>" style="width: 100%;"><i class="fa-solid <?= $is_cancelled ? 'fa-ban' : ($is_expense ? 'fa-arrow-trend-down' : 'fa-arrow-trend-up') ?>"></i> <span><?= clean($row['description']) ?></span></div></td>
                        <td class="desktop-only" style="padding: 20px 10px;"><span class="type-pill <?= $status_class ?>"><?= $status_label ?></span></td>
                        <td class="desktop-only" style="padding: 20px 10px; text-align: right;"><span class="amount-text <?= $status_class ?>"><?= $is_cancelled ? '' : ($is_expense ? '- ' : '+ ') ?><?= number_format($row['amount'], 2, '.', ',') ?> <?= $currency ?></span></td>
                        <td class="desktop-only action-col" style="padding: 20px 10px; text-align: right;" class="no-print"><div class="action-wrapper-premium"><div class="action-btn-pc bg-green-soft" onclick="editFinanceModal(<?= $row['id'] ?>)"><i class="fa-solid fa-pen-to-square"></i></div><div class="action-btn-pc bg-red-soft" onclick="deleteFinance(<?= $row['id'] ?>)"><i class="fa-solid fa-trash-can"></i></div></div></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- PROFESYONEL RAPOR TABLOSU (SADECE YAZDIRIRKEN GÖRÜNÜR) -->
        <div class="print-table-container print-only">
            <table class="print-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">TARİH</th>
                        <th style="width: 12%;">TİP</th>
                        <th style="width: 35%;">BAŞLIK / AÇIKLAMA</th>
                        <th style="width: 20%;">HİZMET</th>
                        <th style="width: 18%; text-align: right;">TUTAR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row): 
                        $is_expense = $row['type'] == 'expense';
                        $is_cancelled = ($row['amount'] == 0);
                    ?>
                    <tr>
                        <td><?= date('d.m.Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <?php if($is_cancelled): ?>
                                <span style="color: #64748b;">İptal</span>
                            <?php elseif($is_expense): ?>
                                <span class="pt-type-expense">↓ Gider</span>
                            <?php else: ?>
                                <span class="pt-type-income">↑ Gelir</span>
                            <?php endif; ?>
                        </td>
                        <td><?= clean($row['description']) ?></td>
                        <td><?= $row['service_name'] ?: ($row['appointment_id'] ? 'Hizmet Bilgisi Yok' : '-') ?></td>
                        <td class="pt-amount <?= $is_expense ? 'pt-type-expense' : ($is_cancelled ? '' : 'pt-type-income') ?>">
                            <?= number_format($row['amount'], 0, ',', '.') ?> <?= $currency ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
});

function addFinanceModal(type) {
    const title = type === 'income' ? 'Gelir Ekle' : 'Gider Ekle';
    const body = `<form id="financeForm"><input type="hidden" name="type" value="${type}"><div class="form-group"><label>Tutar (₺)</label><input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required></div><div class="form-group" style="margin-top: 15px;"><label>Açıklama</label><input type="text" name="description" class="form-control" placeholder="İşlem detayı..." required></div></form>`;
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
            const body = `<form id="editFinanceForm"><input type="hidden" name="id" value="${f.id}"><div class="form-group"><label>Tutar (₺)</label><input type="number" step="0.01" name="amount" class="form-control" value="${f.amount}" required></div><div class="form-group" style="margin-top: 15px;"><label>Tür</label><select name="type" class="form-control"><option value="income" ${f.type == 'income' ? 'selected' : ''}>Gelir</option><option value="expense" ${f.type == 'expense' ? 'selected' : ''}>Gider</option></select></div><div class="form-group" style="margin-top: 15px;"><label>Açıklama</label><input type="text" name="description" class="form-control" value="${f.description}" required></div></form>`;
            const footer = `<button onclick="updateFinance()" class="btn-premium" style="width: 100%;">Güncelle</button>`;
            openGlobalModal('Kaydı Düzenle', body, footer);
        }
    });
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
                </div>
            `;
            openGlobalModal('İşlem Detayları', body, '');
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
