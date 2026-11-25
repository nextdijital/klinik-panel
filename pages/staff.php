<?php include '../includes/header.php'; ?>

<div class="large-calendar-container">
    <div class="page-header-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 class="desktop-only" style="font-size: 24px; font-weight: 700; color: var(--text-main);"><?= __('staff') ?></h2>
        <div class="mobile-only-title" style="display: none; width: 100%; text-align: center; margin-bottom: 20px;">
             <h2 style="font-size: 24px; font-weight: 800; color: var(--text-main);">Personel Yönetimi</h2>
             <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">Ekibinizi buradan yönetin</p>
        </div>
        <div class="header-actions-mobile" style="display: flex; gap: 12px; align-items: center;">
            <?php $show_passive = isset($_GET['show_passive']) && $_GET['show_passive'] == '1'; ?>
            <a href="?show_passive=<?= $show_passive ? '0' : '1' ?>" class="btn-premium" style="background: <?= $show_passive ? 'var(--primary)' : 'var(--bg-page)' ?>; color: <?= $show_passive ? 'white' : 'var(--text-muted)' ?>; box-shadow: <?= $show_passive ? '0 8px 16px rgba(134,135,195,0.2)' : 'none' ?>; border: 1px solid rgba(134,135,195,0.2); text-decoration: none;">
                <i class="fa-solid <?= $show_passive ? 'fa-eye-slash' : 'fa-eye' ?>"></i> 
                <span><?= $show_passive ? 'Gizle' : 'Pasifler' ?></span>
            </a>
            <button class="btn-premium" onclick="addStaffModal()">
                <i class="fa-solid fa-user-plus"></i> <span><?= __('add_staff') ?></span>
            </button>
        </div>
    </div>

    <div style="overflow-x: auto; overflow-y: auto; flex: 1; min-height: 0;" class="premium-table-scroll custom-scroll">
        <table style="width: 100%; border-collapse: collapse; text-align: left; table-layout: fixed;">
            <thead>
                <tr style="color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(165,166,216,0.1);">
                    <th style="padding: 15px 10px; width: 35%;">Personel</th>
                    <th class="desktop-only" style="padding: 15px 10px; width: 25%;">Telefon</th>
                    <th class="desktop-only" style="padding: 15px 10px; width: 25%;">Durum</th>
                    <th class="desktop-only" style="padding: 15px 10px; width: 15%; text-align: right;">Aksiyon</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $status_val = $show_passive ? 0 : 1;
                $stmt = $db->query("SELECT * FROM staff WHERE is_deleted = 0 AND status = $status_val ORDER BY first_name ASC");
                $staff_list = $stmt->fetchAll();

                $card_colors = [
                    'rgba(134, 135, 195, 0.15)', // Purple
                    'rgba(190, 242, 100, 0.15)', // Lime
                    'rgba(153, 193, 255, 0.15)', // Blue
                    'rgba(255, 179, 179, 0.15)'  // Coral
                ];

                if (empty($staff_list)): ?>
                    <tr>
                        <td colspan="4" style="padding: 100px 50px; text-align: center;">
                            <div style="opacity: 0.5;">
                                <i class="fa-solid fa-users-slash" style="font-size: 48px; margin-bottom: 15px; color: var(--primary);"></i>
                                <h3 style="font-size: 18px; color: var(--text-main);"><?= $show_passive ? 'Pasif Personel Bulunmuyor' : 'Aktif Personel Bulunmuyor' ?></h3>
                                <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">
                                    <?= $show_passive ? 'Şu anda sistemde pasif durumda olan bir personel kaydı yer almamaktadır.' : 'Henüz sisteme kayıtlı aktif bir personeliniz bulunmuyor.' ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: 
                    foreach ($staff_list as $index => $row): 
                        $s_id = $row['id'];
                        $is_active = $row['status'] == 1;
                        $bg_color = $card_colors[$index % count($card_colors)];
                        
                        // Telefon formatlama
                        $p = preg_replace('/[^0-9]/', '', $row['phone']);
                        $formatted_phone = (strlen($p) == 11) 
                            ? substr($p, 0, 4) . ' ' . substr($p, 4, 3) . ' ' . substr($p, 7, 2) . ' ' . substr($p, 9, 2)
                            : ($row['phone'] ?: '-');
                    ?>
                    <tr class="appointment-row <?= !$is_active ? 'row-passive' : '' ?>" onclick="if(window.innerWidth < 1024) viewStaff(<?= $s_id ?>)" style="border-bottom: 1px solid rgba(165, 166, 216, 0.05); transition: all 0.2s;">
                        <td style="padding: 18px 10px;">
                            <div class="patient-block-card" style="background: <?= $bg_color ?>;">
                                <div style="display: flex; align-items: center; gap: 12px; overflow: hidden;">
                                    <span class="patient-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px; font-weight: 700;"><?= clean($row['first_name'] . ' ' . $row['last_name']) ?></span>
                                </div>
                                <a href="tel:<?= $row['phone'] ?>" class="cta-call-icon" onclick="event.stopPropagation()" data-tooltip="Arama Yap">
                                    <i class="fa-solid fa-phone" style="font-size: 11px;"></i>
                                </a>
                            </div>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px;">
                            <div class="date-badge" style="background: rgba(165, 166, 216, 0.1); color: var(--text-main); font-weight: 700;">
                                <i class="fa-solid fa-phone-flip" style="opacity: 0.5;"></i>
                                <span><?= clean($formatted_phone) ?></span>
                            </div>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px;">
                            <label class="switch-pill" onclick="event.stopPropagation()">
                                <input type="checkbox" <?= $is_active ? 'checked' : '' ?> onchange="toggleStaffStatus(<?= $s_id ?>, this.checked ? 1 : 0)">
                                <span class="slider-pill">
                                    <span class="status-text text-on">AKTİF</span>
                                    <span class="status-text text-off">PASİF</span>
                                </span>
                            </label>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px; text-align: right;">
                            <div class="action-wrapper-premium">
                                <div class="action-btn-pc bg-blue-soft" data-tooltip="<?= __('details') ?>" onclick="event.stopPropagation(); viewStaff(<?= $s_id ?>)">
                                    <i class="fa-solid fa-eye"></i>
                                </div>
                                <div class="action-btn-pc bg-green-soft" data-tooltip="<?= __('edit') ?>" onclick="event.stopPropagation(); editStaffModal(<?= $s_id ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                                <div class="action-btn-pc bg-red-soft" data-tooltip="<?= __('delete') ?>" onclick="event.stopPropagation(); deleteStaff(<?= $s_id ?>)">
                                    <i class="fa-solid fa-trash-can"></i>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function addStaffModal() {
    const body = `
        <form id="staffForm">
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
                <input type="text" name="phone" class="form-control" placeholder="05XX XXX XX XX">
            </div>
        </form>
    `;
    const footer = `<button onclick="saveStaff()" class="btn-premium" style="width: 100%;">Personeli Kaydet</button>`;
    openGlobalModal('Sisteme Personel Ekle', body, footer);
}

function saveStaff() {
    const data = $('#staffForm').serialize();
    $.post('../actions/staff_add.php', data, function(res) {
        if(res.status == 'success') {
            location.reload();
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function editStaffModal(id) {
    $.get('../actions/get_staff.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const s = res.data;
            const body = `
                <form id="editStaffForm">
                    <input type="hidden" name="id" value="${s.id}">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ad</label>
                            <input type="text" name="first_name" class="form-control" value="${s.first_name}" required>
                        </div>
                        <div class="form-group">
                            <label>Soyad</label>
                            <input type="text" name="last_name" class="form-control" value="${s.last_name}" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Telefon</label>
                        <input type="text" name="phone" class="form-control" value="${s.phone}">
                    </div>
                </form>
            `;
            const footer = `<button onclick="updateStaff()" class="btn-premium" style="width: 100%;">Güncelle</button>`;
            openGlobalModal('Personel Bilgilerini Düzenle', body, footer);
        }
    });
}

function updateStaff() {
    const data = $('#editStaffForm').serialize();
    $.post('../actions/staff_update.php', data, function(res) {
        if(res.status == 'success') {
            location.reload();
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function toggleStaffStatus(id, status) {
    $.post('../actions/staff_status.php', { id: id, status: status }, function(res) {
        if(res.status == 'success') {
            location.reload();
        }
    });
}

function deleteStaff(id) {
    const body = `
        <div style="text-align: center; padding: 20px;">
            <i class="fa-solid fa-user-slash" style="font-size: 48px; color: #f43f5e; margin-bottom: 20px;"></i>
            <h3>Personeli Arşivle?</h3>
            <p style="color: var(--text-muted); margin-top: 10px;">Personel kaydı sistemden kaldırılacaktır (arşivlenecektir).</p>
        </div>
    `;
    const footer = `
        <button onclick="confirmDeleteStaff(${id})" class="btn-premium" style="background: #f43f5e;">Evet, Arşivle</button>
        <button onclick="closeGlobalModal()" class="btn-secondary">İptal</button>
    `;
    openGlobalModal('Personel Sil', body, footer);
}

function confirmDeleteStaff(id) {
    $.post('../actions/staff_delete.php', { id: id }, function(res) {
        if(res.status == 'success') {
            location.reload();
        }
    });
}
function viewStaff(id) {
    $.get('../actions/get_staff.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const s = res.data;
            const body = `
                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 15px;">
                        <span class="status-pill-large" style="background: ${s.status == 1 ? 'var(--accent-lime-soft)' : 'rgba(244, 63, 94, 0.1)'}; color: ${s.status == 1 ? 'var(--accent-lime-dark)' : '#f43f5e'}; margin: 0;">
                            <i class="fa-solid ${s.status == 1 ? 'fa-check-circle' : 'fa-ban'}"></i> ${s.status == 1 ? 'AKTİF PERSONEL' : 'PASİF PERSONEL'}
                        </span>
                        ${s.phone ? `<a href="tel:${s.phone}" class="call-btn" style="margin: 0; padding: 10px 20px;">
                            <i class="fa-solid fa-phone"></i> ${s.phone}
                        </a>` : ''}
                    </div>
                    
                    <div class="modal-apt-actions" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                        <button onclick="editStaffModal(${s.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: var(--text-main); border: none; box-shadow: 0 4px 12px rgba(45, 46, 66, 0.2);">
                            <i class="fa-solid fa-pen-to-square"></i> PERSONELİ DÜZENLE
                        </button>
                        <button onclick="deleteStaff(${s.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: #f43f5e; border: none; box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2);">
                            <i class="fa-solid fa-trash-can"></i> PERSONELİ ARŞİVLE
                        </button>
                        <button onclick="toggleStaffStatus(${s.id}, ${s.status == 1 ? 0 : 1})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: ${s.status == 1 ? '#f59e0b' : '#10b981'}; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            <i class="fa-solid ${s.status == 1 ? 'fa-toggle-off' : 'fa-toggle-on'}"></i> ${s.status == 1 ? 'PERSONELİ PASİFE AL' : 'PERSONELİ AKTİFLEŞTİR'}
                        </button>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: var(--primary);">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Ad Soyad</span>
                            <span class="detail-value">${s.first_name} ${s.last_name}</span>
                        </div>
                    </div>
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #3498db;">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Telefon</span>
                            <span class="detail-value">${s.phone || '-'}</span>
                        </div>
                    </div>
                </div>
            `;
            openGlobalModal('Personel Detayları', body, '');
        }
    });
}

/* Mobil Kart Stilleri */
const mobileStyles = `
@media (max-width: 1024px) {
    .mobile-only-title { display: block !important; }
    .header-actions-mobile { width: 100%; justify-content: center; }
    
    table, thead, tbody, th, td, tr { display: block; }
    thead tr { display: none; }
    
    .appointment-row {
        margin-bottom: 20px;
        background: white;
        border-radius: 20px !important;
        border: 1px solid rgba(134,135,195,0.08) !important;
        box-shadow: 0 8px 25px rgba(45,46,66,0.04);
        padding: 5px;
        cursor: pointer;
    }
    
    .patient-block-card {
        padding: 22px !important;
        border-radius: 18px !important;
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
