<?php include '../includes/header.php'; ?>

<div class="large-calendar-container">
    <div id="customer-list-view">
        <div class="page-header-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 class="desktop-only" style="font-size: 24px; font-weight: 700; color: var(--text-main);"><?= __('customers') ?></h2>
            <div class="mobile-only-title" style="display: none; width: 100%; text-align: center; margin-bottom: 20px;">
                 <h2 style="font-size: 24px; font-weight: 800; color: var(--text-main);">Müşteri Yönetimi</h2>
                 <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">Müşteri portföyünüzü buradan yönetin</p>
            </div>
            <div class="header-actions-mobile" style="display: flex; gap: 12px; align-items: center;">
                <button class="btn-premium" onclick="addCustomerModal()">
                    <i class="fa-solid fa-user-plus"></i>
                    <span><?= __('add_customer') ?></span>
                </button>
            </div>
        </div>

    <div style="overflow-x: auto;" class="premium-table-scroll">
        <table class="premium-customer-table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(165,166,216,0.1);">
                    <th class="col-customer" style="padding: 15px 10px;">Müşteri</th>
                    <th class="desktop-only col-date" style="padding: 15px 10px;">Kayıt Tarihi</th>
                    <th class="desktop-only col-phone" style="padding: 15px 10px;">Telefon</th>
                    <th class="desktop-only col-action" style="padding: 15px 10px; text-align: right;">Aksiyon</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->query("SELECT * FROM customers WHERE is_deleted = 0 ORDER BY created_at DESC");
                $customers = $stmt->fetchAll();

                $card_colors = [
                    'rgba(165, 166, 216, 0.15)', // Soft Purple
                    'rgba(46, 204, 113, 0.15)',  // Soft Green
                    'rgba(52, 152, 219, 0.15)',  // Soft Blue
                    'rgba(241, 196, 15, 0.15)',  // Soft Yellow
                    'rgba(155, 89, 182, 0.15)'   // Soft Deep Purple
                ];

                if (empty($customers)): ?>
                    <tr><td colspan="4" style="padding: 50px; text-align: center; color: var(--text-muted);">Henüz kayıtlı müşteri bulunmuyor.</td></tr>
                <?php else: 
                    foreach ($customers as $index => $row): 
                        $c_id = $row['id'];
                        $bg_color = $card_colors[$index % count($card_colors)];
                        
                        // Telefon formatlama: 0555 555 55 55
                        $p = preg_replace('/[^0-9]/', '', $row['phone']);
                        if(strlen($p) == 11) {
                            $formatted_phone = substr($p, 0, 4) . ' ' . substr($p, 4, 3) . ' ' . substr($p, 7, 2) . ' ' . substr($p, 9, 2);
                        } else {
                            $formatted_phone = $row['phone'];
                        }
                    ?>
                    <tr class="appointment-row" onclick="if(window.innerWidth < 1024) viewCustomer(<?= $c_id ?>)" style="border-bottom: 1px solid rgba(165, 166, 216, 0.05); transition: all 0.2s;">
                        <td style="padding: 18px 10px;">
                            <div class="patient-block-card" onclick="if(window.innerWidth >= 1024) viewCustomerHistory(<?= $c_id ?>)" style="background: <?= $bg_color ?>; cursor: pointer;">
                                <div style="display: flex; align-items: center; gap: 12px; overflow: hidden;">
                                    <span class="patient-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px; font-weight: 700;"><?= clean($row['first_name'] . ' ' . $row['last_name']) ?></span>
                                </div>
                                <a href="tel:<?= $row['phone'] ?>" class="cta-call-icon" data-tooltip="Arama Yap" onclick="event.stopPropagation()" style="width: 30px; height: 30px; right: 8px;">
                                    <i class="fa-solid fa-phone" style="font-size: 11px;"></i>
                                </a>
                            </div>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px;">
                            <div class="date-badge" style="background: rgba(165, 166, 216, 0.1); color: var(--primary);">
                                <i class="fa-regular fa-calendar-check"></i>
                                <span><?= date('d.m.Y', strtotime($row['created_at'])) ?></span>
                            </div>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px;">
                            <div class="date-badge" style="background: <?= $bg_color ?>; color: var(--text-main); font-weight: 700; border-radius: 10px;">
                                <i class="fa-solid fa-phone-flip" style="opacity: 0.5;"></i>
                                <span><?= clean($formatted_phone) ?></span>
                            </div>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px; text-align: right;">
                            <div class="action-wrapper-premium">
                                <div class="action-btn-pc bg-blue-soft" data-tooltip="<?= __('details') ?>" onclick="event.stopPropagation(); viewCustomer(<?= $c_id ?>)">
                                    <i class="fa-solid fa-eye"></i>
                                </div>
                                <div class="action-btn-pc bg-green-soft" data-tooltip="<?= __('edit') ?>" onclick="event.stopPropagation(); editCustomerModal(<?= $c_id ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                                <div class="action-btn-pc bg-red-soft" data-tooltip="<?= __('delete') ?>" onclick="event.stopPropagation(); deleteCustomer(<?= $c_id ?>)">
                                    <i class="fa-solid fa-trash-can"></i>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    </div> <!-- #customer-list-view end -->

    <div id="customer-history-view" style="display: none;">
        <!-- Bu kısım JS ile dinamik doldurulacak -->
    </div>
</div>

<!-- Yüzen Geri Dön Butonu (Desktop) -->
<div id="back-to-list-btn" class="floating-back-btn" onclick="backToCustomerList()" style="display: none;">
    <i class="fa-solid fa-arrow-left"></i>
    <span>Müşteri Listesine Dön</span>
</div>

<style>
.floating-back-btn {
    position: fixed;
    bottom: 40px;
    right: 40px;
    background: #0f172a;
    color: white;
    padding: 15px 25px;
    border-radius: 50px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    box-shadow: 0 15px 35px rgba(15, 23, 42, 0.3);
    z-index: 1000;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.floating-back-btn:hover {
    transform: translateY(-5px) scale(1.05);
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.4);
    background: #1e293b;
}

.floating-back-btn i { font-size: 16px; }
.floating-back-btn span { font-weight: 700; font-size: 14px; }

/* Tarih Aralığı Filtresi İçin Özel Stil */
#history-date-filter {
    background: white;
    border: 1px solid rgba(134, 135, 195, 0.1);
    border-radius: 12px;
    padding: 10px 15px;
    font-size: 13px;
    color: var(--text-main);
    width: 200px;
    transition: all 0.3s ease;
}

/* Müşteri Tablo Düzeni - Mobil ve PC Ayrımı */
@media (min-width: 1024px) {
    .premium-customer-table {
        table-layout: fixed;
    }
    .col-customer { width: 28%; }
    .col-date { width: 24%; }
    .col-phone { width: 24%; }
    .col-action { width: 24%; }
}

@media (max-width: 1023px) {
    .app-container { padding: 5px !important; }
    .large-calendar-container { padding: 20px 10px !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; }
    .premium-table-scroll { width: 100% !important; }
    .premium-customer-table { display: block !important; width: 100% !important; }
    .premium-customer-table thead { display: none; }
    .premium-customer-table tbody, .premium-customer-table tr { display: block !important; width: 100% !important; }
    .premium-customer-table td.col-customer { 
        display: block !important; 
        width: 100% !important; 
        padding: 0 !important;
        margin: 0 0 10px 0 !important;
    }
    .premium-customer-table td.desktop-only { display: none !important; }
    
    .patient-block-card { 
        width: 100% !important; 
        max-width: none !important;
        padding: 18px 15px !important;
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        box-sizing: border-box !important;
        gap: 15px;
    }
    .patient-block-card > div:first-child {
        flex: 1 !important;
        overflow: hidden !important;
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
    }
    .patient-name { 
        font-size: 17px !important; 
        font-weight: 800 !important; 
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: block !important;
        margin: 0 !important;
    }
    .cta-call-icon { 
        position: relative !important; 
        right: auto !important; 
        top: 0 !important;
        margin: 0 !important;
        transform: none !important;
        width: 44px !important; 
        height: 44px !important; 
        flex-shrink: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
}

.history-filter-panel {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    background: rgba(134, 135, 195, 0.03);
    padding: 12px 20px;
    border-radius: 18px;
    border: 1px solid rgba(134, 135, 195, 0.05);
}

.history-search-input {
    flex: 1;
    max-width: 400px;
    background: white;
    border: 1px solid rgba(134, 135, 195, 0.1);
    border-radius: 12px;
    padding: 10px 15px;
    font-size: 13px;
    color: var(--text-main);
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
}

.btn-filter-apply {
    background: var(--primary);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(134, 135, 195, 0.2);
}

.btn-filter-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(134, 135, 195, 0.3);
    opacity: 0.9;
}

.btn-filter-clear {
    background: white;
    color: var(--text-muted);
    border: 1px solid rgba(134, 135, 195, 0.2);
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-filter-clear:hover {
    background: rgba(134, 135, 195, 0.05);
    color: var(--text-main);
    border-color: rgba(134, 135, 195, 0.4);
}

/* Flatpickr Alt Input Hizalaması */
.history-filter-panel input.flatpickr-input,
.history-filter-panel .flatpickr-input.form-control,
.history-filter-panel input[readonly] {
    padding-left: 50px !important;
    background: white !important;
    border: 1px solid rgba(134, 135, 195, 0.1) !important;
    border-radius: 12px !important;
    font-size: 13px !important;
    height: 42px !important;
    width: 200px !important;
}
</style>

<script>
function addCustomerModal() {
    const body = `
        <form id="customerForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Ad</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Soyad</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>Telefon</label>
                <input type="text" name="phone" class="form-control" placeholder="05XX XXX XX XX" required>
            </div>
        </form>
    `;
    const footer = `<button onclick="saveCustomer()" class="btn-premium" style="width: 100%;">Müşteriyi Kaydet</button>`;
    openGlobalModal('Yeni Müşteri Ekle', body, footer);
}

function saveCustomer() {
    const data = $('#customerForm').serialize();
    $.post('../actions/customer_add.php', data, function(res) {
        if(res.status == 'success') {
            location.reload();
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function editCustomerModal(id) {
    $.get('../actions/get_customer.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const c = res.data;
            const body = `
                <form id="editCustomerForm">
                    <input type="hidden" name="id" value="${c.id}">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ad</label>
                            <input type="text" name="first_name" class="form-control" value="${c.first_name}" required>
                        </div>
                        <div class="form-group">
                            <label>Soyad</label>
                            <input type="text" name="last_name" class="form-control" value="${c.last_name}" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Telefon</label>
                        <input type="text" name="phone" class="form-control" value="${c.phone}" required>
                    </div>
                </form>
            `;
            const footer = `<button onclick="updateCustomer()" class="btn-premium" style="width: 100%;">Güncelle</button>`;
            openGlobalModal('Müşteri Düzenle', body, footer);
        }
    });
}

function updateCustomer() {
    const data = $('#editCustomerForm').serialize();
    $.post('../actions/customer_update.php', data, function(res) {
        if(res.status == 'success') {
            location.reload();
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function deleteCustomer(id) {
    const body = `
        <div style="text-align: center; padding: 20px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: #f43f5e; margin-bottom: 20px;"></i>
            <h3><?= __('archive_confirm_title') ?></h3>
            <p style="color: var(--text-muted); margin-top: 10px;">
                <?= __('archive_confirm_text') ?>
            </p>
        </div>
    `;
    const footer = `
        <button onclick="confirmDeleteCustomer(${id})" class="btn-premium" style="background: #f43f5e; color: white;"><?= __('archive_btn') ?></button>
        <button onclick="closeGlobalModal()" class="btn-secondary"><?= __('cancel') ?></button>
    `;
    openGlobalModal('<?= __('archive_customer_title') ?>', body, footer);
}

function confirmDeleteCustomer(id) {
    $.post('../actions/customer_delete.php', { id: id }, function(res) {
        if(res.status == 'success') {
            location.reload();
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function viewCustomer(id) {
    $.get('../actions/get_customer.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const c = res.data;
            const body = `
                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 15px;">
                        <span class="status-pill-large" style="background: rgba(134, 135, 195, 0.1); color: var(--primary); margin: 0;">
                            <i class="fa-solid fa-user-tag"></i> MÜŞTERİ
                        </span>
                        <a href="tel:${c.phone}" class="call-btn" style="margin: 0; padding: 10px 20px;">
                            <i class="fa-solid fa-phone"></i> ${c.phone || '-'}
                        </a>
                    </div>
                    
                    <div class="modal-apt-actions" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                        <button onclick="viewCustomerHistory(${c.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: var(--primary); border: none; box-shadow: 0 4px 12px rgba(134, 135, 195, 0.2);">
                            <i class="fa-solid fa-clock-rotate-left"></i> MÜŞTERİ GEÇMİŞİ
                        </button>
                        <button onclick="editCustomerModal(${c.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: var(--text-main); border: none; box-shadow: 0 4px 12px rgba(45, 46, 66, 0.2);">
                            <i class="fa-solid fa-pen-to-square"></i> MÜŞTERİYİ DÜZENLE
                        </button>
                        <button onclick="deleteCustomer(${c.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: #f43f5e; border: none; box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2);">
                            <i class="fa-solid fa-trash-can"></i> <?= __('archive_customer_title') ?>
                        </button>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: var(--primary);">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Ad Soyad</span>
                            <span class="detail-value">${c.first_name} ${c.last_name}</span>
                        </div>
                    </div>
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #3498db;">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Telefon No</span>
                            <span class="detail-value">${c.phone}</span>
                        </div>
                    </div>
                    <div class="detail-item-card" style="grid-column: span 2;">
                        <div class="detail-icon-box" style="color: #9b59b6;">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Kayıt Tarihi</span>
                            <span class="detail-value">${new Date(c.created_at).toLocaleDateString('tr-TR')}</span>
                        </div>
                    </div>
                </div>
            `;
            openGlobalModal('Müşteri Detayları', body, '');
        }
    });
}

let currentCustomerHistory = [];
let allHistoryData = [];

function viewCustomerHistory(id) {
    $.get('../actions/get_customer_history.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const data = res.data;
            allHistoryData = data.history;
            const c = data.customer;
            const summary = data.summary;

            if (window.innerWidth >= 1024) {
                // MASAÜSTÜ: Sayfa içi geçiş
                $('#customer-list-view').hide();
                $('#customer-history-view').fadeIn(300).show();
                $('#back-to-list-btn').fadeIn(300).show();

                // Filtreleri her yeni müşteride sıfırla
                $('#history-search').val('');
                $('#history-date-filter').val('');

                const historyHtml = `
                    <div class="history-container-pc">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
                            <div>
                                <h2 style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-bottom: 5px;">${c.first_name} ${c.last_name}</h2>
                                <p style="color: var(--text-muted); font-size: 14px; font-weight: 500;">Müşterinin tüm işlem geçmişi ve finansal özeti</p>
                            </div>
                            <div style="display: flex; gap: 15px;">
                                <div class="detail-item-card" style="background: white; border: 1px solid rgba(134, 135, 195, 0.1); padding: 15px; min-width: 180px;">
                                    <div class="detail-icon-box" style="color: var(--primary); background: rgba(134, 135, 195, 0.1); width: 36px; height: 36px; font-size: 14px;">
                                        <i class="fa-solid fa-hand-holding-heart"></i>
                                    </div>
                                    <div class="detail-info-content">
                                        <span class="detail-label">Toplam Hizmet</span>
                                        <span class="detail-value" style="font-size: 18px;">${summary.total_services} İşlem</span>
                                    </div>
                                </div>
                                <div class="detail-item-card" style="background: white; border: 1px solid rgba(46, 204, 113, 0.1); padding: 15px; min-width: 200px;">
                                    <div class="detail-icon-box" style="color: #27ae60; background: rgba(46, 204, 113, 0.1); width: 36px; height: 36px; font-size: 14px;">
                                        <i class="fa-solid fa-wallet"></i>
                                    </div>
                                    <div class="detail-info-content">
                                        <span class="detail-label">Toplam Ödeme</span>
                                        <span class="detail-value" style="font-size: 18px;">${summary.total_paid.toLocaleString('tr-TR')} <?= $currency ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtreleme Paneli -->
                        <div class="history-filter-panel">
                            <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                                <i class="fa-solid fa-magnifying-glass" style="color: var(--primary); opacity: 0.5;"></i>
                                <input type="text" id="history-search" class="history-search-input" placeholder="İşlem veya personel ara..." onkeyup="filterHistory()">
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="position: relative; display: flex; align-items: center; gap: 10px;">
                                    <i class="fa-solid fa-calendar-alt" style="color: var(--primary); opacity: 0.5; position: absolute; left: 15px; z-index: 10;"></i>
                                    <input type="text" id="history-date-filter" style="padding-left: 45px;" placeholder="Tarih Aralığı Seçin">
                                </div>
                                <button class="btn-filter-apply" onclick="filterHistory()">
                                    <i class="fa-solid fa-filter"></i> Uygula
                                </button>
                                <button class="btn-filter-clear" onclick="clearHistoryFilters()">
                                    <i class="fa-solid fa-eraser"></i> Temizle
                                </button>
                            </div>
                        </div>

                        <div class="premium-table-scroll custom-scroll" style="background: white; border-radius: 20px; border: 1px solid rgba(134, 135, 195, 0.08); box-shadow: 0 10px 30px rgba(0,0,0,0.02); max-height: 520px; overflow-y: auto;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; table-layout: fixed;">
                                <thead>
                                    <tr style="color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; border-bottom: 2px solid rgba(165,166,216,0.05);">
                                        <th style="padding: 20px 25px; width: 22%;">Tarih / Saat</th>
                                        <th style="padding: 20px 25px; width: 25%;">Hizmet</th>
                                        <th style="padding: 20px 25px; width: 20%;">Personel</th>
                                        <th style="padding: 20px 25px; width: 18%;">Tutar</th>
                                        <th style="padding: 20px 25px; width: 15%;">Durum</th>
                                    </tr>
                                </thead>
                                <tbody id="history-table-body">
                                    <!-- JS ile doldurulacak -->
                                </tbody>
                            </table>
                            <div id="no-results-history" style="display: none; padding: 60px; text-align: center; color: var(--text-muted);">
                                <i class="fa-solid fa-filter-circle-xmark" style="font-size: 40px; margin-bottom: 15px; opacity: 0.2;"></i>
                                <p>Aradığınız kriterlere uygun işlem bulunamadı.</p>
                            </div>
                        </div>
                    </div>
                `;
                $('#customer-history-view').html(historyHtml);
                initHistoryFilters();
                renderHistoryResults(allHistoryData);
            } else {
                // MOBİL: Mevcut Modal Yapısı
                let historyHtml = '';
                if (allHistoryData.length === 0) {
                    historyHtml = `<div style="padding: 40px; text-align: center; color: var(--text-muted); font-size: 14px; background: rgba(134, 135, 195, 0.03); border-radius: 20px; border: 1px dashed rgba(134, 135, 195, 0.2);"><?= __('no_history') ?></div>`;
                } else {
                    allHistoryData.forEach(item => {
                        const statusMap = {
                            'pending': { label: '<?= __('status_pending') ?>', color: '#f1c40f', bg: 'rgba(241, 196, 15, 0.1)' },
                            'completed': { label: '<?= __('status_completed') ?>', color: '#2ecc71', bg: 'rgba(46, 204, 113, 0.1)' },
                            'missed': { label: '<?= __('status_missed') ?>', color: '#e74c3c', bg: 'rgba(231, 76, 60, 0.1)' },
                            'cancelled': { label: '<?= __('status_cancelled') ?>', color: '#95a5a6', bg: 'rgba(149, 165, 166, 0.1)' }
                        };
                        const s = statusMap[item.status] || statusMap.pending;
                        const dateFormatted = new Date(item.appointment_date).toLocaleDateString('tr-TR');
                        
                        historyHtml += `
                            <div class="history-card" style="background: white; border: 1px solid rgba(134, 135, 195, 0.1); border-radius: 18px; padding: 18px; margin-bottom: 15px; box-shadow: 0 4px 12px rgba(134, 135, 195, 0.03); display: flex; flex-direction: column; gap: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 13px; font-weight: 800; color: var(--text-main);">${dateFormatted}</span>
                                        <span style="font-size: 13px; font-weight: 500; color: var(--text-muted); opacity: 0.6;">${item.appointment_time.substr(0, 5)}</span>
                                    </div>
                                    <span style="padding: 5px 12px; border-radius: 50px; font-size: 10px; font-weight: 800; background: ${s.bg}; color: ${s.color}; text-transform: uppercase; letter-spacing: 0.5px;">
                                        ${s.label}
                                    </span>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 4px; padding: 12px; background: rgba(134, 135, 195, 0.03); border-radius: 12px;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: ${item.service_color};"></div>
                                        <span style="font-size: 14px; font-weight: 700; color: var(--text-main);">${item.service_name}</span>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-user-doctor" style="font-size: 11px; opacity: 0.5;"></i>
                                        <span>${item.staff_name || 'Atanmamış'}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }

                const body = `
                    <div style="margin-bottom: 10px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 25px;">
                            <div class="detail-item-card" style="background: white; border: 1px solid rgba(134, 135, 195, 0.1); padding: 15px;">
                                <div class="detail-icon-box" style="color: var(--primary); background: rgba(134, 135, 195, 0.1); width: 36px; height: 36px; font-size: 14px;">
                                    <i class="fa-solid fa-hand-holding-heart"></i>
                                </div>
                                <div class="detail-info-content">
                                    <span class="detail-label"><?= __('total_services') ?></span>
                                    <span class="detail-value" style="font-size: 16px;">${summary.total_services} İşlem</span>
                                </div>
                            </div>
                            <div class="detail-item-card" style="background: white; border: 1px solid rgba(46, 204, 113, 0.1); padding: 15px;">
                                <div class="detail-icon-box" style="color: #27ae60; background: rgba(46, 204, 113, 0.1); width: 36px; height: 36px; font-size: 14px;">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <div class="detail-info-content">
                                    <span class="detail-label"><?= __('total_paid') ?></span>
                                    <span class="detail-value" style="font-size: 16px;">${summary.total_paid.toLocaleString('tr-TR')} <?= $currency ?></span>
                                </div>
                            </div>
                        </div>

                        <h4 style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i> <?= __('past_appointments') ?>
                        </h4>
                        
                        <div style="max-height: 420px; overflow-y: auto !important; padding-right: 5px;" class="premium-table-scroll">
                            ${historyHtml}
                        </div>
                    </div>
                `;
                openGlobalModal(`${c.first_name} ${c.last_name} - <?= __('customer_history') ?>`, body, '');
            }
        }
    });
}

function initHistoryFilters() {
    flatpickr("#history-date-filter", {
        mode: "range",
        locale: "tr",
        dateFormat: "Y-m-d",
        altInput: true,
        altInputClass: "form-control flatpickr-input",
        altFormat: "d.m.Y",
        conjunction: " to "
    });
}

function filterHistory() {
    const searchTerm = $('#history-search').val().trim().toLowerCase();
    const dateRange = $('#history-date-filter').val();
    let startDateStr = null;
    let endDateStr = null;

    let dates = dateRange.split(/ to | – | - /);
    if (dates.length === 2) {
        startDateStr = dates[0].trim();
        endDateStr = dates[1].trim();
    } else if (dateRange.trim() !== '') {
        startDateStr = dateRange.trim();
        endDateStr = dateRange.trim();
    }

    const filtered = allHistoryData.filter(item => {
        const sName = (item.service_name || "").toLowerCase();
        const stName = (item.staff_name || "").toLowerCase();
        const matchesSearch = sName.includes(searchTerm) || stName.includes(searchTerm);
        
        let matchesDate = true;
        if (startDateStr && endDateStr) {
            const itemDateStr = item.appointment_date.substr(0, 10); // YYYY-MM-DD
            matchesDate = itemDateStr >= startDateStr && itemDateStr <= endDateStr;
        }

        return matchesSearch && matchesDate;
    });

    renderHistoryResults(filtered);
}

function clearHistoryFilters() {
    $('#history-search').val('');
    $('#history-date-filter').val('');
    const fp = document.querySelector("#history-date-filter")._flatpickr;
    if(fp) fp.clear();
    renderHistoryResults(allHistoryData);
}

function renderHistoryResults(data) {
    const tbody = $('#history-table-body');
    const noResults = $('#no-results-history');
    tbody.empty();

    if (data.length === 0) {
        noResults.show();
    } else {
        noResults.hide();
        data.forEach(item => {
            const statusMap = {
                'pending': { label: 'Bekliyor', color: '#f1c40f', bg: 'rgba(241, 196, 15, 0.1)' },
                'completed': { label: 'Tamamlandı', color: '#2ecc71', bg: 'rgba(46, 204, 113, 0.1)' },
                'missed': { label: 'Gelinmedi', color: '#e74c3c', bg: 'rgba(231, 76, 60, 0.1)' },
                'cancelled': { label: 'İptal Edildi', color: '#95a5a6', bg: 'rgba(149, 165, 166, 0.1)' }
            };
            const s = statusMap[item.status] || statusMap.pending;
            const dateFormatted = new Date(item.appointment_date).toLocaleDateString('tr-TR');
            const isInactive = item.status === 'cancelled' || item.status === 'missed';
            const displayPrice = isInactive ? '0' : parseFloat(item.service_price).toLocaleString('tr-TR');
            const priceColor = isInactive ? '#94a3b8' : 'var(--text-main)';

            tbody.append(`
                <tr style="border-bottom: 1px solid rgba(165,166,216,0.03); transition: background 0.2s;">
                    <td style="padding: 18px 25px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="detail-icon-box" style="width: 34px; height: 34px; background: rgba(134, 135, 195, 0.08); color: var(--primary); flex-shrink: 0; font-size: 13px;">
                                <i class="fa-regular fa-clock"></i>
                            </div>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 800; color: var(--text-main); font-size: 14px;">${dateFormatted}</span>
                                <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">${item.appointment_time.substr(0, 5)}</span>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 18px 25px;">
                        <div class="patient-block-card" style="background: rgba(190, 242, 100, 0.12); color: #74b816; border: none; padding: 10px 18px; border-radius: 12px; display: inline-flex; width: auto; gap: 10px;">
                            <i class="fa-solid fa-stethoscope" style="font-size: 13px;"></i>
                            <span style="font-weight: 800; font-size: 13px; text-transform: uppercase;">${item.service_name}</span>
                        </div>
                    </td>
                    <td style="padding: 18px 25px;">
                        <div style="display: flex; align-items: center; gap: 8px; color: var(--text-main); font-weight: 700; font-size: 13px;">
                            <i class="fa-solid fa-user-doctor" style="color: var(--primary); opacity: 0.6; font-size: 12px;"></i>
                            <span>${item.staff_name || 'Atanmamış'}</span>
                        </div>
                    </td>
                    <td style="padding: 18px 25px;">
                        <span style="font-weight: 900; color: ${priceColor}; font-size: 16px;">${displayPrice} <?= $currency ?></span>
                    </td>
                    <td style="padding: 18px 25px;">
                        <span class="type-pill" style="background: ${s.bg}; color: ${s.color}; border-radius: 10px; padding: 8px 16px; font-weight: 800; font-size: 11px;">
                            ${s.label}
                        </span>
                    </td>
                </tr>
            `);
        });
    }
}

function backToCustomerList() {
    $('#customer-history-view').hide();
    $('#back-to-list-btn').hide();
    $('#customer-list-view').fadeIn(300).show();
}

/* Mobil Kart Stilleri */
const mobileStyles = `
@media (max-width: 1024px) {
    .mobile-only-title { display: block !important; }
    .header-actions-mobile { width: 100%; justify-content: center; }
    
    table, thead, tbody, th, td, tr { display: block; }
    thead tr { display: none; }
    
    .appointment-row {
        margin-bottom: 15px;
        background: white;
        border-radius: 20px !important;
        border: 1px solid rgba(134,135,195,0.08) !important;
        box-shadow: 0 8px 25px rgba(45,46,66,0.04);
        padding: 5px;
        cursor: pointer;
    }
    
    .patient-block-card {
        padding: 20px !important;
        border-radius: 16px !important;
    }

    .patient-name {
        font-size: 16px !important;
        font-weight: 800 !important;
    }
}
`;
$('<style>').text(mobileStyles).appendTo('head');
</script>

<?php include '../includes/footer.php'; ?>
