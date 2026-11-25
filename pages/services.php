<?php include '../includes/header.php'; 
$categories = $db->query("SELECT * FROM appointment_categories WHERE is_deleted = 0 ORDER BY id ASC")->fetchAll();
?>

<div class="large-calendar-container">
    <div class="page-header-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 class="desktop-only" style="font-size: 24px; font-weight: 700; color: var(--text-main);"><?= __('services') ?></h2>
        <div class="mobile-only-title" style="display: none; width: 100%; text-align: center; margin-bottom: 20px;">
             <h2 style="font-size: 24px; font-weight: 800; color: var(--text-main);">Hizmet Yönetimi</h2>
             <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">Hizmet yelpazenizi buradan yönetin</p>
        </div>
        <div class="header-actions-mobile" style="display: flex; gap: 12px; align-items: center;">
            <?php $show_passive = isset($_GET['show_passive']) && $_GET['show_passive'] == '1'; ?>
            <a href="?show_passive=<?= $show_passive ? '0' : '1' ?>" class="btn-premium" style="background: <?= $show_passive ? 'var(--primary)' : 'var(--bg-page)' ?>; color: <?= $show_passive ? 'white' : 'var(--text-muted)' ?>; box-shadow: <?= $show_passive ? '0 8px 16px rgba(134,135,195,0.2)' : 'none' ?>; border: 1px solid rgba(134,135,195,0.2); text-decoration: none;">
                <i class="fa-solid <?= $show_passive ? 'fa-eye-slash' : 'fa-eye' ?>"></i> 
                <span><?= $show_passive ? 'Gizle' : 'Pasifler' ?></span>
            </a>
            <button class="btn-premium" onclick="addServiceModal()">
                <i class="fa-solid fa-plus-circle"></i> <span><?= __('add_service') ?></span>
            </button>
        </div>
    </div>

    <div style="overflow-x: auto; overflow-y: auto; flex: 1; min-height: 0;" class="premium-table-scroll custom-scroll">
        <table style="width: 100%; border-collapse: collapse; text-align: left; table-layout: fixed;">
            <thead>
                <tr style="color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(165,166,216,0.1);">
                    <th class="mobile-full-width" style="padding: 15px 10px; width: 40%;">Hizmet</th>
                    <th class="desktop-only" style="padding: 15px 10px; width: 20%;">Fiyat</th>
                    <th class="desktop-only" style="padding: 15px 10px; width: 25%;">Durum</th>
                    <th class="desktop-only" style="padding: 15px 10px; width: 15%; text-align: right;">Aksiyon</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $status_val = $show_passive ? 0 : 1;
                $stmt = $db->query("SELECT * FROM services WHERE is_deleted = 0 AND status = $status_val ORDER BY name ASC");
                $services = $stmt->fetchAll();

                if (empty($services)): ?>
                    <tr>
                        <td colspan="5" style="padding: 100px 50px; text-align: center;">
                            <div style="opacity: 0.5;">
                                <i class="fa-solid fa-folder-open" style="font-size: 48px; margin-bottom: 15px; color: var(--primary);"></i>
                                <h3 style="font-size: 18px; color: var(--text-main);"><?= $show_passive ? 'Pasif Kayıt Bulunmuyor' : 'Aktif Hizmet Kaydı Bulunmuyor' ?></h3>
                                <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">
                                    <?= $show_passive ? 'Şu anda sistemde devre dışı bırakılmış herhangi bir hizmet yer almamaktadır.' : 'Henüz sisteme tanımlanmış aktif bir hizmetiniz bulunmuyor.' ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: 
                    foreach ($services as $index => $row): 
                        $svc_id = $row['id'];
                        $is_active = $row['status'] == 1;
                    ?>
                    <tr class="appointment-row <?= !$is_active ? 'row-passive' : '' ?>" onclick="if(window.innerWidth < 1024) viewService(<?= $svc_id ?>)" style="border-bottom: 1px solid rgba(165, 166, 216, 0.05); transition: all 0.2s;">
                        <td style="padding: 18px 10px;">
                            <div class="patient-block-card" style="background: rgba(134, 135, 195, 0.1);">
                                <div style="display: flex; align-items: center; gap: 10px; overflow: hidden; flex-wrap: wrap;">
                                    <span class="patient-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px; font-weight: 700; max-width: 100%; color: <?= $row['color'] ?: 'var(--primary)' ?>;"><?= clean($row['name']) ?></span>
                                    <?php if($row['total_sessions'] > 1): ?>
                                        <span style="background: rgba(39, 174, 96, 0.15); color: #219653; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-box-open" style="font-size: 10px;"></i> <?= $row['total_sessions'] ?> Seanslık Paket
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <td class="desktop-only" style="padding: 18px 10px;">
                            <span style="font-weight: 800; font-size: 15px; color: var(--primary);">
                                <?= number_format($row['price'], 2) ?> <?= $currency ?>
                            </span>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px;">
                            <label class="switch-pill" onclick="event.stopPropagation()">
                                <input type="checkbox" <?= $is_active ? 'checked' : '' ?> onchange="toggleServiceStatus(<?= $svc_id ?>, this.checked ? 1 : 0)">
                                <span class="slider-pill">
                                    <span class="status-text text-on">AKTİF</span>
                                    <span class="status-text text-off">PASİF</span>
                                </span>
                            </label>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px; text-align: right;">
                            <div class="action-wrapper-premium">
                                <div class="action-btn-pc bg-blue-soft" data-tooltip="<?= __('details') ?>" onclick="event.stopPropagation(); viewService(<?= $svc_id ?>)">
                                    <i class="fa-solid fa-eye"></i>
                                </div>
                                <div class="action-btn-pc bg-green-soft" data-tooltip="<?= __('edit') ?>" onclick="event.stopPropagation(); editServiceModal(<?= $svc_id ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                                <div class="action-btn-pc bg-red-soft" data-tooltip="<?= __('delete') ?>" onclick="event.stopPropagation(); deleteService(<?= $svc_id ?>)">
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
function addServiceModal() {
    const body = `
        <form id="serviceForm">
            <div class="form-group">
                <label><?= __('service_name') ?></label>
                <input type="text" name="name" class="form-control" placeholder="Örn: Cilt Bakımı" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div class="form-group">
                    <label><?= __('base_price') ?> (₺)</label>
                    <input type="number" step="0.01" name="base_price" id="add_base_price" class="form-control" oninput="calculateNetPrice('add')" required>
                </div>
                <div class="form-group">
                    <label><?= __('discount_type') ?></label>
                    <select name="discount_type" id="add_discount_type" class="form-control" onchange="calculateNetPrice('add')">
                        <option value="none"><?= __('discount_none') ?></option>
                        <option value="fixed"><?= __('discount_fixed') ?></option>
                        <option value="percentage"><?= __('discount_percentage') ?></option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div class="form-group">
                    <label><?= __('discount_value') ?></label>
                    <input type="number" step="0.01" name="discount_value" id="add_discount_value" class="form-control" oninput="calculateNetPrice('add')" value="0">
                </div>
                <div class="form-group">
                    <label><?= __('net_price') ?></label>
                    <div id="add_net_price_display" style="height: 45px; display: flex; align-items: center; padding: 0 15px; background: rgba(134, 135, 195, 0.05); border-radius: 12px; font-weight: 800; color: var(--primary); border: 1px solid rgba(134, 135, 195, 0.1);">0.00 <?= $currency ?></div>
                    <input type="hidden" name="price" id="add_price">
                </div>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>Paket Seans Sayısı</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number" name="total_sessions" id="add_total_sessions" class="form-control" value="1" min="1" placeholder="1 = Tek Seans, 10 = 10 Seanslık Paket" required>
                    <span style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">(1 = Tek Seans, >1 = Paket)</span>
                </div>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>Hizmet Rengi</label>
                <input type="hidden" name="color" id="add_service_color" value="#A5A6D8">
                <div class="category-selector" style="display: flex; gap: 15px; margin-top: 5px;">
                    <?php foreach($categories as $cat): ?>
                                <div class="category-dot" 
                                     data-color="<?= $cat['color'] === '#BEF264' ? '#10B981' : $cat['color'] ?>" 
                                     title="<?= $cat['name'] ?>"
                                     style="width: 32px; height: 32px; border-radius: 50%; background: <?= $cat['color'] === '#BEF264' ? '#10B981' : $cat['color'] ?>; cursor: pointer; border: 3px solid transparent; transition: all 0.2s ease;">
                                </div>
                    <?php endforeach; ?>
                </div>
                <small class="selected-color-name" style="display: block; margin-top: 8px; font-size: 11px; font-weight: 700; color: var(--primary); text-transform: uppercase; min-height: 15px;"></small>
            </div>
        </form>
    `;
    const footer = `<button onclick="saveService()" class="btn-premium" style="width: 100%;">Hizmeti Ekle</button>`;
    openGlobalModal('<?= __('add_service') ?>', body, footer);
    
    // Color dot selection logic
    $(document).off('click', '.category-dot').on('click', '.category-dot', function() {
        const name = $(this).attr('title');
        $('.category-dot').css('border-color', 'transparent');
        $(this).css('border-color', 'var(--primary)');
        $(this).closest('.form-group').find('.selected-color-name').text(name);
        $('#add_service_color, #edit_service_color').val($(this).data('color'));
    });
    // Default selection
    $('.category-dot:first').trigger('click');
}

function calculateNetPrice(prefix) {
    const base = parseFloat($(`#${prefix}_base_price`).val()) || 0;
    const type = $(`#${prefix}_discount_type`).val();
    const val = parseFloat($(`#${prefix}_discount_value`).val()) || 0;
    
    let net = base;
    if (type === 'fixed') {
        net = base - val;
    } else if (type === 'percentage') {
        net = base - (base * (val / 100));
    }
    
    if (net < 0) net = 0;
    
    $(`#${prefix}_net_price_display`).text(net.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + globalCurrency);
    $(`#${prefix}_price`).val(net.toFixed(2));
}

function saveService() {
    const data = $('#serviceForm').serialize();
    $.post('../actions/service_add.php', data, function(res) {
        if(res.status == 'success') {
            location.reload();
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function editServiceModal(id) {
    $.get('../actions/get_service.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const s = res.data;
            const body = `
                <form id="editServiceForm">
                    <input type="hidden" name="id" value="${s.id}">
                    <div class="form-group">
                        <label><?= __('service_name') ?></label>
                        <input type="text" name="name" class="form-control" value="${s.name}" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                        <div class="form-group">
                            <label><?= __('base_price') ?> (₺)</label>
                            <input type="number" step="0.01" name="base_price" id="edit_base_price" class="form-control" value="${s.base_price}" oninput="calculateNetPrice('edit')" required>
                        </div>
                        <div class="form-group">
                            <label><?= __('discount_type') ?></label>
                            <select name="discount_type" id="edit_discount_type" class="form-control" onchange="calculateNetPrice('edit')">
                                <option value="none" ${s.discount_type === 'none' ? 'selected' : ''}><?= __('discount_none') ?></option>
                                <option value="fixed" ${s.discount_type === 'fixed' ? 'selected' : ''}><?= __('discount_fixed') ?></option>
                                <option value="percentage" ${s.discount_type === 'percentage' ? 'selected' : ''}><?= __('discount_percentage') ?></option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                        <div class="form-group">
                            <label><?= __('discount_value') ?></label>
                            <input type="number" step="0.01" name="discount_value" id="edit_discount_value" class="form-control" value="${s.discount_value}" oninput="calculateNetPrice('edit')">
                        </div>
                        <div class="form-group">
                            <label><?= __('net_price') ?></label>
                            <div id="edit_net_price_display" style="height: 45px; display: flex; align-items: center; padding: 0 15px; background: rgba(134, 135, 195, 0.05); border-radius: 12px; font-weight: 800; color: var(--primary); border: 1px solid rgba(134, 135, 195, 0.1);">0.00 <?= $currency ?></div>
                            <input type="hidden" name="price" id="edit_price" value="${s.price}">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Paket Seans Sayısı</label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="number" name="total_sessions" id="edit_total_sessions" class="form-control" value="${s.total_sessions || 1}" min="1" required>
                            <span style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">(1 = Tek Seans, >1 = Paket)</span>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Hizmet Rengi</label>
                        <input type="hidden" name="color" id="edit_service_color" value="${s.color}">
                        <div class="category-selector" style="display: flex; gap: 15px; margin-top: 5px;">
                            <?php foreach($categories as $cat): ?>
                                <div class="category-dot" 
                                     data-color="<?= $cat['color'] ?>" 
                                     title="<?= $cat['name'] ?>"
                                     style="width: 32px; height: 32px; border-radius: 50%; background: <?= $cat['color'] ?>; cursor: pointer; border: 3px solid transparent; transition: all 0.2s ease;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="selected-color-name" style="display: block; margin-top: 8px; font-size: 11px; font-weight: 700; color: var(--primary); text-transform: uppercase; min-height: 15px;"></small>
                    </div>
                </form>
            `;
            const footer = `<button onclick="updateService()" class="btn-premium" style="width: 100%;">Güncelle</button>`;
            openGlobalModal('Hizmet Düzenle', body, footer);
            calculateNetPrice('edit'); // Initial calculation
            
            // Set current color active
            $(`.category-dot[data-color="${s.color}"]`).css('border-color', 'var(--primary)');
        }
    });
}

function updateService() {
    const data = $('#editServiceForm').serialize();
    $.post('../actions/service_update.php', data, function(res) {
        if(res.status == 'success') {
            location.reload();
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function toggleServiceStatus(id, status) {
    $.post('../actions/service_status.php', { id: id, status: status }, function(res) {
        if(res.status == 'success') {
            location.reload();
        }
    });
}

function deleteService(id) {
    const body = `
        <div style="text-align: center; padding: 20px;">
            <i class="fa-solid fa-tag-slash" style="font-size: 48px; color: #f43f5e; margin-bottom: 20px;"></i>
            <h3>Hizmeti Arşivle?</h3>
            <p style="color: var(--text-muted); margin-top: 10px;">Bu hizmet tanımı kalıcı olarak listeden kaldırılacaktır.</p>
        </div>
    `;
    const footer = `
        <button onclick="confirmDeleteService(${id})" class="btn-premium" style="background: #f43f5e;">Evet, Arşivle</button>
        <button onclick="closeGlobalModal()" class="btn-secondary">İptal</button>
    `;
    openGlobalModal('Hizmet Sil', body, footer);
}

function confirmDeleteService(id) {
    $.post('../actions/service_delete.php', { id: id }, function(res) {
        if(res.status == 'success') {
            location.reload();
        }
    });
}

function viewService(id) {
    $.get('../actions/get_service.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const s = res.data;
            const body = `
                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 15px;">
                        <span class="status-pill-large" style="background: ${s.status == 1 ? 'var(--accent-lime-soft)' : 'rgba(244, 63, 94, 0.1)'}; color: ${s.status == 1 ? 'var(--accent-lime-dark)' : '#f43f5e'}; margin: 0;">
                            <i class="fa-solid ${s.status == 1 ? 'fa-check-circle' : 'fa-ban'}"></i> ${s.status == 1 ? 'AKTİF HİZMET' : 'PASİF HİZMET'}
                        </span>
                        <div class="call-btn" style="margin: 0; padding: 10px 20px; background: rgba(134, 135, 195, 0.1); color: var(--primary);">
                            <i class="fa-solid fa-tag"></i> ${parseFloat(s.price).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${globalCurrency}
                        </div>
                    </div>
                    
                    <div class="modal-apt-actions" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                        <button onclick="editServiceModal(${s.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: var(--text-main); border: none; box-shadow: 0 4px 12px rgba(45, 46, 66, 0.2);">
                            <i class="fa-solid fa-pen-to-square"></i> HİZMETİ DÜZENLE
                        </button>
                        <button onclick="deleteService(${s.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: #f43f5e; border: none; box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2);">
                            <i class="fa-solid fa-trash-can"></i> HİZMETİ ARŞİVLE
                        </button>
                        <button onclick="toggleServiceStatus(${s.id}, ${s.status == 1 ? 0 : 1})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: ${s.status == 1 ? '#f59e0b' : '#10b981'}; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            <i class="fa-solid ${s.status == 1 ? 'fa-toggle-off' : 'fa-toggle-on'}"></i> ${s.status == 1 ? 'HİZMETİ PASİFE AL' : 'HİZMETİ AKTİFLEŞTİR'}
                        </button>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: var(--primary);">
                            <i class="fa-solid fa-tags"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label"><?= __('service_name') ?></span>
                            <span class="detail-value">${s.name}</span>
                        </div>
                    </div>
                    
                    ${s.discount_type !== 'none' ? `
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #94a3b8;">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label"><?= __('base_price') ?></span>
                            <span class="detail-value" style="text-decoration: line-through; opacity: 0.6;">${parseFloat(s.base_price).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${globalCurrency}</span>
                        </div>
                    </div>
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #f59e0b;">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label"><?= __('discount_type') ?></span>
                            <span class="detail-value">
                                ${s.discount_type === 'fixed' ? '<?= __('discount_fixed') ?>' : '<?= __('discount_percentage') ?>'} 
                                (${parseFloat(s.discount_value).toLocaleString('tr-TR')} ${s.discount_type === 'fixed' ? '₺' : '%'})
                            </span>
                        </div>
                    </div>
                    ` : ''}

                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #10b981;">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">${s.discount_type !== 'none' ? '<?= __('net_price') ?>' : '<?= __('price') ?>'}</span>
                            <span class="detail-value" style="font-weight: 800; color: #10b981;">${parseFloat(s.price).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${globalCurrency}</span>
                        </div>
                    </div>

                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #27ae60;">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Seans Türü</span>
                            <span class="detail-value" style="font-weight: 700; color: #27ae60;">${s.total_sessions > 1 ? s.total_sessions + ' Seanslık Paket' : 'Tek Seans'}</span>
                        </div>
                    </div>

                </div>
            `;
            openGlobalModal('Hizmet Detayları', body, '');
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
