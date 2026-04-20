<?php include '../includes/header.php'; ?>

<div class="large-calendar-container">
    <div class="page-header-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 class="desktop-only" style="font-size: 24px; font-weight: 700; color: var(--text-main);"><?= __('appointments') ?></h2>
        <div class="mobile-only-title" style="display: none; width: 100%; text-align: center; margin-bottom: 20px;">
             <h2 style="font-size: 24px; font-weight: 800; color: var(--text-main);">Randevular</h2>
             <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">Günlük akışınızı buradan yönetin</p>
        </div>
        <div class="header-actions-mobile" style="display: flex; gap: 15px;">
            <button class="btn-premium" onclick="openAddModal()">
                <i class="fa-solid fa-plus"></i>
                <span>Randevu Ekle</span>
            </button>
            <button class="btn-premium calendar-view-btn" onclick="window.location.href='dashboard.php'" style="background: var(--bg-page); color: var(--text-main); border: 1px solid rgba(134,135,195,0.2); box-shadow: none;">
                <i class="fa-solid fa-calendar-alt"></i>
                <span>Takvim Görünümü</span>
            </button>
        </div>
    </div>

    <!-- Hizmetler ve Müşteriler listesini modalda kullanmak için çekelim -->
    <?php 
    $services_list = $db->query("SELECT id, name, price FROM services WHERE is_deleted = 0 AND status = 1 ORDER BY name ASC")->fetchAll(); 
    $customers_list = $db->query("SELECT id, first_name, last_name FROM customers WHERE is_deleted = 0 ORDER BY first_name ASC")->fetchAll();
    $staff_list = $db->query("SELECT id, first_name, last_name FROM staff WHERE status = 1 AND is_deleted = 0 ORDER BY first_name ASC")->fetchAll();
    $categories = $db->query("SELECT id, name, color FROM appointment_categories ORDER BY id ASC")->fetchAll();
    ?>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(165,166,216,0.1);">
                    <th style="padding: 15px 10px;"><?= __('patient') ?></th>
                    <th class="desktop-only" style="padding: 15px 10px;"><?= __('date_time') ?></th>
                    <th class="desktop-only" style="padding: 15px 10px;"><?= __('service') ?></th>
                    <th class="desktop-only" style="padding: 15px 10px;"><?= __('staff') ?></th>
                    <th class="desktop-only" style="padding: 15px 10px;"><?= __('status') ?></th>
                    <th class="desktop-only" style="padding: 15px 10px; text-align: right;"><?= __('action') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $settings_all = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
                $retention_hours = intval($settings_all['cancelled_retention_hours'] ?? 24);
                $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_hours hours"));

                $stmt = $db->prepare("SELECT a.*, c.first_name, c.last_name, c.phone as customer_phone, s.name as service_name, s.color as service_color, CONCAT(st.first_name, ' ', st.last_name) as staff_name 
                                   FROM appointments a 
                                   JOIN customers c ON a.customer_id = c.id 
                                   JOIN services s ON a.service_id = s.id 
                                   LEFT JOIN staff st ON a.staff_id = st.id
                                   WHERE a.is_deleted = 0 
                                   AND c.is_deleted = 0 
                                   AND s.is_deleted = 0
                                   AND (a.staff_id IS NULL OR st.is_deleted = 0)
                                   AND (a.status = 'pending' OR (a.status = 'cancelled' AND a.updated_at > ?))
                                   AND MINUTE(a.appointment_time) IN (0, 30)
                                   ORDER BY a.appointment_date ASC, a.appointment_time ASC");
                $stmt->execute([$cutoff_date]);
                $appointments = $stmt->fetchAll();

                if (empty($appointments)): ?>
                    <tr><td colspan="5" style="padding: 50px; text-align: center; color: var(--text-muted);"><?= __('no_data') ?></td></tr>
                <?php else: 
                    foreach ($appointments as $row): 
                        // Status map for colors
                        $status_map = [
                            'pending' => ['label' => __('status_pending'), 'color' => '#f1c40f', 'bg' => 'rgba(241, 196, 15, 0.1)'],
                            'completed' => ['label' => __('status_completed'), 'color' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.1)'],
                            'missed' => ['label' => __('status_missed'), 'color' => '#f43f5e', 'bg' => 'rgba(244, 63, 94, 0.1)'],
                            'cancelled' => ['label' => __('status_cancelled'), 'color' => '#f43f5e', 'bg' => 'rgba(244, 63, 94, 0.1)']
                        ];
                        $st = $status_map[$row['status']] ?? $status_map['pending'];
                        
                        $appt_id = $row['id'];
                    ?>
                    <tr class="appointment-row" onclick="if(window.innerWidth < 1024) viewApt(<?= $appt_id ?>)" style="border-bottom: 1px solid rgba(165, 166, 216, 0.05); transition: all 0.2s;">
                        <td style="padding: 18px 10px;">
                            <div class="patient-block-card" style="background: <?= $row['service_color'] ?>15;">
                                <span class="patient-name"><?= clean($row['first_name']) ?> <span style="text-transform: uppercase;"><?= clean($row['last_name']) ?></span></span>
                                <a href="tel:<?= $row['customer_phone'] ?>" class="cta-call-icon" data-tooltip="Arama Yap" onclick="event.stopPropagation()">
                                    <i class="fa-solid fa-phone"></i>
                                </a>
                            </div>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px;">
                            <span class="date-badge" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>;">
                                <i class="fa-regular fa-calendar"></i>
                                <?= date('d.m.Y', strtotime($row['appointment_date'])) ?>
                                <span style="opacity: 0.4; margin: 0 5px;">|</span>
                                <i class="fa-regular fa-clock"></i>
                                <?= substr($row['appointment_time'], 0, 5) ?>
                            </span>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px;">
                            <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 800; color: <?= $row['service_color'] ?: 'var(--primary)' ?>; text-transform: uppercase;">
                                <?= clean($row['service_name']) ?>
                            </span>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px;">
                            <span style="font-size: 13px; font-weight: 500; color: var(--text-muted);">
                                <i class="fa-solid fa-user-doctor" style="margin-right: 5px; opacity: 0.5;"></i>
                                <?= clean($row['staff_name'] ?: 'Atanmamış') ?>
                            </span>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px;">
                            <span style="padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>; text-transform: uppercase; letter-spacing: 0.5px;">
                                <?= $st['label'] ?>
                            </span>
                        </td>
                        <td class="desktop-only" style="padding: 18px 10px; text-align: right;">
                            <div class="action-wrapper-premium">
                                <!-- PC View: Quick Actions -->
                                <div class="action-btn-pc bg-green-soft" data-tooltip="<?= __('mark_completed') ?>" onclick="updateStatus(<?= $appt_id ?>, 'completed')">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <div class="action-btn-pc bg-red-soft" data-tooltip="<?= __('cancel_appointment') ?>" onclick="updateStatus(<?= $appt_id ?>, 'cancelled')">
                                    <i class="fa-solid fa-ban"></i>
                                </div>

                                <!-- Common: Dropdown for more/mobile -->
                                <div class="action-wrapper" style="position: relative;">
                                    <button class="action-btn-trigger" onclick="event.stopPropagation(); toggleActionMenu(<?= $appt_id ?>, event)">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div id="menu-<?= $appt_id ?>" class="action-menu">
                                        <div class="action-menu-item" onclick="viewApt(<?= $appt_id ?>)">
                                            <i class="fa-solid fa-eye icon-gray"></i>
                                            <?= __('details') ?>
                                        </div>
                                        <div class="action-menu-item" onclick="editApt(<?= $appt_id ?>)">
                                            <i class="fa-solid fa-pen-to-square icon-blue"></i>
                                            <?= __('edit') ?>
                                        </div>
                                        
                                        <!-- Mobile Only Items -->
                                        <div class="action-menu-item mobile-only-menu-item" onclick="updateStatus(<?= $appt_id ?>, 'completed')">
                                            <i class="fa-solid fa-circle-check icon-green"></i>
                                            <?= __('mark_completed') ?>
                                        </div>
                                        <div class="action-menu-item mobile-only-menu-item" onclick="updateStatus(<?= $appt_id ?>, 'cancelled')">
                                            <i class="fa-solid fa-ban icon-red"></i>
                                            <?= __('cancel_appointment') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Randevu Ekle Modalı (Dashboard ile Aynı) -->
<div id="aptModal" class="modal-overlay">
    <div class="modal-container modal-wide">
        <div class="modal-header">
            <h3 class="modal-title">Yeni Randevu Oluştur</h3>
            <button class="modal-close" onclick="$('#aptModal').hide()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="aptForm">
            <div class="modal-form-grid">
                <!-- Sol Sütun: Randevu Bilgileri -->
                <div class="modal-col-left">
                    <div class="form-group">
                        <label>Hasta Seçin</label>
                        <div class="input-group-split">
                            <select name="customer_id" id="apt_customer_id" class="form-control" required>
                                <option value="">Seçiniz...</option>
                                <?php foreach($customers_list as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['first_name'] . ' ' . $c['last_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn-quick-add" onclick="openQuickCustomerModal()">
                                <i class="fa-solid fa-user-plus"></i> Hızlı Müşteri
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Hizmet Seçin (Birden fazla seçebilirsiniz)</label>
                        <select id="service-selector" class="form-control" onchange="addServiceTag(this)">
                            <option value="">Hizmet eklemek için seçin...</option>
                            <?php foreach($services_list as $s): ?>
                                <option value="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>"><?= $s['name'] ?> (<?= number_format($s['price'],0) ?> <?= $currency ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="service_id" id="main-service-id" required>
                        <div id="selected-services-tags" class="service-tags-container"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tarih</label>
                            <input type="text" id="appointment_date" name="appointment_date" class="form-control" placeholder="Tarih seçin" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Saat</label>
                            <input type="text" id="appointment_time" name="appointment_time" class="form-control" placeholder="Saat seçin" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= __('staff') ?></label>
                        <select name="staff_id" class="form-control" required>
                            <option value=""><?= __('select_staff') ?></option>
                            <?php foreach($staff_list as $st_item): ?>
                                <option value="<?= $st_item['id'] ?>"><?= $st_item['first_name'] . ' ' . $st_item['last_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Notlar (Opsiyonel)</label>
                        <textarea name="notes" class="form-control" rows="4" style="min-height: 120px; font-size: 13px;"></textarea>
                    </div>
                </div>

                <!-- Sağ Sütun: Notlar ve Ücretlendirme -->
                <div class="modal-col-right">
                    <!-- Fiyatlandırma Paneli -->
                    <div style="margin-top: 25px; padding: 20px; background: rgba(134, 135, 195, 0.03); border-radius: 15px; border: 1px dashed rgba(134, 135, 195, 0.2);">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">Ücretlendirme ve İndirim</h4>
                        
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div class="form-group">
                                <label><?= __('base_price') ?></label>
                                <input type="number" step="0.01" name="base_price" id="apt_base_price" class="form-control" oninput="calculateAppointmentPrice()" required>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label><?= __('discount_type') ?></label>
                                    <select name="discount_type" id="apt_discount_type" class="form-control" onchange="calculateAppointmentPrice()">
                                        <option value="none"><?= __('discount_none') ?></option>
                                        <option value="fixed"><?= __('discount_fixed') ?></option>
                                        <option value="percentage"><?= __('discount_percentage') ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><?= __('discount_value') ?></label>
                                    <input type="number" step="0.01" name="discount_value" id="apt_discount_value" class="form-control" value="0" oninput="calculateAppointmentPrice()">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 25px; display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid rgba(134, 135, 195, 0.1);">
                            <span style="font-weight: 700; color: var(--text-muted); font-size: 13px;"><?= __('net_price') ?></span>
                            <div id="apt_net_price_display" style="font-size: 24px; font-weight: 900; color: var(--primary);">0.00 <?= $currency ?></div>
                            <input type="hidden" name="service_price" id="apt_final_price">
                         </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="$('#aptModal').hide()">Vazgeç</button>
                <button type="submit" class="btn-premium">Randevuyu Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- Hızlı Müşteri Modalı -->
<div id="customerModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Hızlı Müşteri Kaydı</h3>
            <button class="modal-close" onclick="$('#customerModal').hide()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="customerForm">
            <div class="form-group">
                <label>Ad</label>
                <input type="text" name="first_name" class="form-control" placeholder="Ad" required>
            </div>
            <div class="form-group">
                <label>Soyad</label>
                <input type="text" name="last_name" class="form-control" placeholder="Soyad" required>
            </div>
            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="phone" class="form-control" placeholder="05xx..." required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="$('#customerModal').hide()">Vazgeç</button>
                <button type="submit" class="btn-premium">Hastayı Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Flatpickr Initialization
    flatpickr("#appointment_date", {
        locale: "tr",
        altInput: true,
        altFormat: "d.m.Y",
        dateFormat: "Y-m-d",
        minDate: "today",
        defaultDate: "today",
        disableMobile: true
    });

    flatpickr("#appointment_time", {
        locale: "tr",
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        minuteIncrement: 30,
        hourIncrement: 1,
        disableMobile: true
    });

    // Form Submit Handler
    $('#aptForm').on('submit', function(e) {
        e.preventDefault();
        $.post('../actions/appointment_add.php', $(this).serialize(), function(res) {
            if(res.status == 'success') {
                closeAddModal();
                const msg = res.message;
                const type = res.data && res.data.warning ? 'warning' : 'success';
                showToast(res.data && res.data.warning ? 'Dikkat' : 'Başarılı', msg, type);
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast('Hata', res.message, 'error');
            }
        });
    });

    // Customer Form Handler
    $('#customerForm').on('submit', function(e) {
        e.preventDefault();
        $.post('../actions/customer_add.php', $(this).serialize(), function(res) {
            if(res.status == 'success') {
                const newOption = new Option(res.data.full_name, res.data.id, true, true);
                $('#apt_customer_id').append(newOption).trigger('change');
                $('#customerModal').hide();
                $('#customerForm')[0].reset();
                showToast('Başarılı', 'Yeni müşteri seçildi.', 'success');
            } else {
                showToast('Hata', res.message, 'error');
            }
        });
    });

    // Category Selector Handler
    $(document).on('click', '.category-dot', function() {
        $('.category-dot').css({'border-color': 'transparent', 'transform': 'scale(1)'});
        $(this).css({'border-color': 'var(--primary)', 'transform': 'scale(1.1)'});
        $('#selected-category-id').val($(this).data('id'));
        $('#selected-category-name').text($(this).attr('title'));
    });
});

function calculateAppointmentPrice() {
    const base = parseFloat($('#apt_base_price').val()) || 0;
    const type = $('#apt_discount_type').val();
    const val = parseFloat($('#apt_discount_value').val()) || 0;
    
    let net = base;
    if (type === 'fixed') {
        net = base - val;
    } else if (type === 'percentage') {
        net = base - (base * (val / 100));
    }
    
    if (net < 0) net = 0;
    
    $('#apt_net_price_display').text(net.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + globalCurrency);
    $('#apt_final_price').val(net.toFixed(2));
}

function calculateEditPrice() {
    const base = parseFloat($('#apt_base_price_edit').val()) || 0;
    const type = $('#apt_discount_type_edit').val();
    const val = parseFloat($('#apt_discount_value_edit').val()) || 0;
    
    let net = base;
    if (type === 'fixed') {
        net = base - val;
    } else if (type === 'percentage') {
        net = base - (base * (val / 100));
    }
    
    if (net < 0) net = 0;
    
    $('#apt_net_price_display_edit').text(net.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + globalCurrency);
    $('#apt_final_price_edit').val(net.toFixed(2));
}

function calculateStatusPrice() {
    const base = parseFloat($('#status_base_price').val()) || 0;
    const type = $('#status_discount_type').val();
    const val = parseFloat($('#status_discount_value').val()) || 0;
    
    let net = base;
    if (type === 'fixed') {
        net = base - val;
    } else if (type === 'percentage') {
        net = base - (base * (val / 100));
    }
    
    if (net < 0) net = 0;
    
    $('#status_net_price_display').text(net.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + globalCurrency);
    $('#status_final_price').val(net.toFixed(2));
}

let selectedServicesEdit = [];

function addServiceTagEdit(select) {
    const serviceId = select.value;
    const option = select.options[select.selectedIndex];
    const serviceName = option.text;
    const servicePrice = parseFloat(option.getAttribute('data-price')) || 0;
    
    if (!serviceId || selectedServicesEdit.includes(serviceId)) {
        select.value = "";
        return;
    }

    selectedServicesEdit.push(serviceId);
    
    const container = document.getElementById('selected-services-tags-edit');
    const pill = document.createElement('div');
    pill.className = 'service-pill';
    pill.id = 'pill-edit-' + serviceId;
    pill.setAttribute('data-price', servicePrice);
    pill.innerHTML = `
        <span>${serviceName}</span>
        <input type="hidden" name="${selectedServicesEdit.length === 1 ? 'service_id' : 'extra_services[]'}" value="${serviceId}">
        <span class="remove-pill" onclick="removeServiceTagEdit('${serviceId}')"><i class="fa-solid fa-xmark"></i></span>
    `;
    
    if (selectedServicesEdit.length === 1) {
        document.getElementById('main-service-id-edit').value = serviceId;
    }
    
    container.appendChild(pill);
    
    let currentBase = parseFloat($('#apt_base_price_edit').val()) || 0;
    $('#apt_base_price_edit').val((currentBase + servicePrice).toFixed(2));
    calculateEditPrice();

    select.value = "";
}

function removeServiceTagEdit(serviceId) {
    const pill = document.getElementById('pill-edit-' + serviceId);
    if (!pill) return;

    const sPrice = parseFloat(pill.getAttribute('data-price')) || 0;
    let currentBase = parseFloat($('#apt_base_price_edit').val()) || 0;
    $('#apt_base_price_edit').val(Math.max(0, currentBase - sPrice).toFixed(2));

    pill.remove();
    
    selectedServicesEdit = selectedServicesEdit.filter(id => id !== serviceId);
    
    if (selectedServicesEdit.length > 0) {
        const firstPill = document.querySelector('#selected-services-tags-edit .service-pill');
        if (firstPill) {
            const firstInput = firstPill.querySelector('input');
            firstInput.name = 'service_id';
            document.getElementById('main-service-id-edit').value = firstInput.value;
        }
    } else {
        document.getElementById('main-service-id-edit').value = "";
    }
    calculateEditPrice();
}

// Modaldaki select optionlarına data-price eklemek için PHP kısmını da güncelleyelim

function openAddModal() {
    selectedServices = []; // Reset
    $('#selected-services-tags').html('');
    $('#main-service-id').val('');
    $('#apt_base_price').val('0.00');
    $('#apt_discount_type').val('none');
    $('#apt_discount_value').val('0');
    calculateAppointmentPrice();
    $('#aptForm')[0].reset();
    
    // Flatpickr değerini reset sonrası tekrar bugün yap
    if (document.querySelector("#appointment_date")._flatpickr) {
        document.querySelector("#appointment_date")._flatpickr.setDate("today");
    }
    
    $('#aptModal').css('display', 'flex');
}

function closeAddModal() {
    $('#aptModal').hide();
    selectedServices = [];
    $('#selected-services-tags').html('');
}

function openQuickCustomerModal() {
    $('#customerForm')[0].reset();
    $('#customerModal').css('display', 'flex');
}


function viewApt(id) {
    $.get('../actions/get_appointment.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const a = res.data;
            const statusMap = {
                'pending': { label: '<?= __('status_pending') ?>', color: '#f1c40f', bg: 'rgba(241, 196, 15, 0.1)', icon: 'fa-clock' },
                'completed': { label: '<?= __('status_completed') ?>', color: '#2ecc71', bg: 'rgba(46, 204, 113, 0.1)', icon: 'fa-circle-check' },
                'missed': { label: '<?= __('status_missed') ?>', color: '#e74c3c', bg: 'rgba(231, 76, 60, 0.1)', icon: 'fa-circle-xmark' },
                'cancelled': { label: '<?= __('status_cancelled') ?>', color: '#95a5a6', bg: 'rgba(149, 165, 166, 0.1)', icon: 'fa-ban' }
            };
            const s = statusMap[a.status] || statusMap.pending;

            const body = `
                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 15px;">
                        <div class="status-pill-large" style="background: ${s.bg}; color: ${s.color}; margin: 0;">
                            <i class="fa-solid ${s.icon}"></i>
                            ${s.label}
                        </div>
                        <a href="tel:${a.phone}" class="call-btn" style="margin: 0; padding: 10px 20px;">
                            <i class="fa-solid fa-phone"></i> ${a.phone || '-'}
                        </a>
                    </div>
                    
                    <div class="modal-apt-actions" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                        <button onclick="updateStatus(${a.id}, 'completed')" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: var(--text-main); border: none; box-shadow: 0 4px 12px rgba(45, 46, 66, 0.2);">
                            <i class="fa-solid fa-check-circle"></i> TAMAMLANDI
                        </button>
                        <button onclick="updateStatus(${a.id}, 'cancelled')" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: #f43f5e; border: none; box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2);">
                            <i class="fa-solid fa-ban"></i> İPTAL ET
                        </button>
                        <button onclick="editApt(${a.id})" class="btn-premium" style="width: 100%; padding: 14px; font-size: 13px; background: #3b82f6; border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);">
                            <i class="fa-solid fa-pen-to-square"></i> RANDEVUYU DÜZENLE
                        </button>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: var(--primary);">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label"><?= __('patient') ?></span>
                            <span class="detail-value">${a.first_name} ${a.last_name}</span>
                        </div>
                    </div>
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #9b59b6;">
                            <i class="fa-solid fa-briefcase-medical"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label"><?= __('service') ?></span>
                            <span class="detail-value">${a.service_name}</span>
                        </div>
                    </div>
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #3498db;">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label"><?= __('date') ?></span>
                            <span class="detail-value">${formatDate(a.appointment_date)}</span>
                        </div>
                    </div>
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #e67e22;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label"><?= __('time') ?></span>
                            <span class="detail-value">${a.appointment_time.substr(0,5)}</span>
                        </div>
                    </div>
                    <div class="detail-item-card">
                        <div class="detail-icon-box" style="color: #1abc9c;">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label"><?= __('staff') ?></span>
                            <span class="detail-value">${a.staff_name || 'Atanmamış'}</span>
                        </div>
                    </div>
                    <div class="detail-item-card" style="grid-column: span 2;">
                        <div class="detail-icon-box" style="color: #10b981;">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label"><?= __('net_price') ?></span>
                            <span class="detail-value" style="font-weight: 800; color: #10b981;">
                                ${parseFloat(a.service_price).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${globalCurrency}
                                ${a.discount_type !== 'none' ? `<span style="font-size: 11px; color: var(--text-muted); text-decoration: line-through; margin-left: 10px; font-weight: 400;">(${parseFloat(a.base_price).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${globalCurrency})</span>` : ''}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item-card" style="grid-column: span 2;">
                        <div class="detail-icon-box" style="color: #7f8c8d;">
                            <i class="fa-solid fa-notes-medical"></i>
                        </div>
                        <div class="detail-info-content">
                            <span class="detail-label">Notlar / Açıklama</span>
                            <span class="detail-value">${a.notes || 'Herhangi bir not eklenmemiş.'}</span>
                        </div>
                    </div>
                </div>
            `;
            openGlobalModal('<?= __('details') ?>', body, ''); // Footer boş, Kaydet yok
        }
    });
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('tr-TR');
}

function editApt(id) {
    $.get('../actions/get_appointment.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const a = res.data;
            selectedServicesEdit = []; 
            
            const body = `
                <form id="editForm">
                    <div class="modal-form-grid">
                        <div class="modal-col-left">
                            <input type="hidden" name="id" value="${id}">
                            
                            <!-- Hasta Bilgisi (Sabit) -->
                            <div class="form-group" style="background: rgba(134, 135, 195, 0.03); padding: 15px; border-radius: 12px; border: 1px solid rgba(134, 135, 195, 0.1); margin-bottom: 20px;">
                                <label style="text-transform: uppercase; font-size: 11px; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 5px; display: block;">Hasta Bilgisi</label>
                                <div style="font-size: 16px; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                                    <i class="fa-solid fa-user-circle" style="color: var(--primary); font-size: 20px;"></i>
                                    ${a.first_name} ${a.last_name}
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Hizmet Seçin (Birden fazla seçebilirsiniz)</label>
                                <select id="edit-service-selector" class="form-control" onchange="addServiceTagEdit(this)">
                                    <option value="">Hizmet eklemek için seçin...</option>
                                    <?php foreach($services_list as $s): ?>
                                        <option value="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>"><?= $s['name'] ?> (<?= number_format($s['price'],0) ?> <?= $currency ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="service_id" id="main-service-id-edit" value="${a.service_id}" required>
                                <div id="selected-services-tags-edit" class="service-tags-container"></div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label><?= __('date') ?></label>
                                    <input type="text" name="appointment_date" id="edit_date" class="form-control" value="${a.appointment_date}">
                                </div>
                                <div class="form-group">
                                    <label><?= __('time') ?></label>
                                    <input type="text" name="appointment_time" id="edit_time" class="form-control" value="${a.appointment_time}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label><?= __('staff') ?></label>
                                <select name="staff_id" class="form-control">
                                    <?php foreach($staff_list as $st_item): ?>
                                        <option value="<?= $st_item['id'] ?>" ${a.staff_id == <?= $st_item['id'] ?> ? 'selected' : ''}><?= $st_item['first_name'] . ' ' . $st_item['last_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label>Notlar (Opsiyonel)</label>
                                <textarea name="notes" class="form-control" rows="4" style="min-height: 120px; font-size: 13px;">${a.notes || ''}</textarea>
                            </div>
                        </div>

                        <div class="modal-col-right">
                            <!-- Fiyatlandırma Paneli (Edit) -->
                            <div style="margin-top: 25px; padding: 20px; background: rgba(134, 135, 195, 0.03); border-radius: 15px; border: 1px dashed rgba(134, 135, 195, 0.2);">
                                <h4 style="font-size: 13px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">Ücretlendirme ve İndirim</h4>
                                
                                <div style="display: flex; flex-direction: column; gap: 15px;">
                                    <div class="form-group">
                                        <label><?= __('base_price') ?></label>
                                        <input type="number" step="0.01" name="base_price" id="apt_base_price_edit" class="form-control" value="${a.base_price}" oninput="calculateEditPrice()" required>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label><?= __('discount_type') ?></label>
                                            <select name="discount_type" id="apt_discount_type_edit" class="form-control" onchange="calculateEditPrice()">
                                                <option value="none" ${a.discount_type == 'none' ? 'selected' : ''}><?= __('discount_none') ?></option>
                                                <option value="fixed" ${a.discount_type == 'fixed' ? 'selected' : ''}><?= __('discount_fixed') ?></option>
                                                <option value="percentage" ${a.discount_type == 'percentage' ? 'selected' : ''}><?= __('discount_percentage') ?></option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label><?= __('discount_value') ?></label>
                                            <input type="number" step="0.01" name="discount_value" id="apt_discount_value_edit" class="form-control" value="${a.discount_value}" oninput="calculateEditPrice()">
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-top: 25px; display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid rgba(134, 135, 195, 0.1);">
                                    <span style="font-weight: 700; color: var(--text-muted); font-size: 13px;"><?= __('net_price') ?></span>
                                    <div id="apt_net_price_display_edit" style="font-size: 24px; font-weight: 900; color: var(--primary);">${parseFloat(a.service_price).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${globalCurrency}</div>
                                    <input type="hidden" name="service_price" id="apt_final_price_edit" value="${a.service_price}">
                                </div>
                            </div>
                            <div style="margin-top: 15px;">
                                <button type="button" onclick="saveEdit()" class="btn-premium" style="padding: 14px; width: 100%; border-radius: 12px; margin-top: 5px;">De&#287;i&#351;iklikleri Kaydet</button>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            const footer = ``;
            openGlobalModal('Randevuyu Düzenle', body, footer, 'modal-wide');
            
            // Mevcut hizmetleri (ana + ek) tag olarak yükle
            const mainServiceSelect = document.getElementById('edit-service-selector');
            const mainOption = Array.from(mainServiceSelect.options).find(o => o.value == a.service_id);
            if (mainOption) {
                mainServiceSelect.value = a.service_id;
                addServiceTagEdit(mainServiceSelect);
            }

            if (a.extras && a.extras.length > 0) {
                a.extras.forEach(extra => {
                    const extraOption = Array.from(mainServiceSelect.options).find(o => o.value == extra.service_id);
                    if (extraOption) {
                        mainServiceSelect.value = extra.service_id;
                        addServiceTagEdit(mainServiceSelect);
                    }
                });
            }

            flatpickr("#edit_date", { locale: "tr", altInput: true, altFormat: "d.m.Y", dateFormat: "Y-m-d", minDate: "today", disableMobile: true });
            flatpickr("#edit_time", { locale: "tr", enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, minuteIncrement: 30, disableMobile: true });
        }
    });
}

function saveEdit() {
    $.post('../actions/appointment_update.php', $('#editForm').serialize(), function(res) {
        if(res.status == 'success') {
            closeGlobalModal();
            const type = res.data && res.data.warning ? 'warning' : 'success';
            showToast(res.data && res.data.warning ? 'Dikkat' : 'Başarılı', res.message, type);
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function updateStatus(id, status) {
    if (status === 'completed') {
        // 1. AŞAMA: Onay Modalı (Görsel olarak Dashboard ile uyumlu)
        const body = `
            <div class="confirm-wrapper">
                <div class="confirm-icon" style="background: rgba(134, 135, 195, 0.1); color: var(--primary);">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h3 class="confirm-title">Randevuyu Tamamla</h3>
                <p class="confirm-text">Bu randevuyu tamamlamak ve ödeme detaylarını girmek istiyor musunuz?</p>
            </div>
        `;
        
        const footer = `
            <div class="confirm-footer-mobile">
                <button onclick="openPaymentModal(${id})" class="btn-premium">Evet, Devam Et</button>
                <button onclick="closeGlobalModal()" class="btn-secondary">Vazgeç</button>
            </div>
        `;
        
        openGlobalModal('Onay Gerekli', body, footer);
    } else {
        // İptal veya Diğer Durumlar
        const title = status == 'cancelled' ? 'Randevuyu İptal Et' : 'Durumu Güncelle';
        const text = status == 'cancelled' ? 'Bu randevuyu iptal etmek istediğinize emin misiniz?' : 'Randevu durumunu güncellemek istiyor musunuz?';
        const btnText = status == 'cancelled' ? 'Evet, İptal Et' : 'Evet, Güncelle';
        const icon = status == 'cancelled' ? 'fa-ban' : 'fa-clock';
        const iconColor = status == 'cancelled' ? '#f43f5e' : 'var(--primary)';
        const iconBg = status == 'cancelled' ? 'rgba(244, 63, 94, 0.1)' : 'rgba(134, 135, 195, 0.1)';

        const body = `
            <div class="confirm-wrapper">
                <div class="confirm-icon" style="background: ${iconBg}; color: ${iconColor};">
                    <i class="fa-solid ${icon}"></i>
                </div>
                <h3 class="confirm-title">${title}</h3>
                <p class="confirm-text">${text}</p>
            </div>
        `;
        
        const footer = `
            <div class="confirm-footer-mobile">
                <button onclick="executeStatusUpdate(${id}, '${status}')" class="${status == 'cancelled' ? 'btn-danger-premium' : 'btn-premium'}">${btnText}</button>
                <button onclick="closeGlobalModal()" class="btn-secondary"><?= __('cancel') ?></button>
            </div>
        `;
        
        openGlobalModal('Onay Gerekli', body, footer);
    }
}

function openPaymentModal(id) {
    // 2. AŞAMA: Tahsilat Modalı (Veri çekilip gösterilir)
    $.get('../actions/get_appointment.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const a = res.data;
            const body = `
                <div class="modal-split-grid">
                    <!-- Sol Panel: Özet -->
                    <div class="modal-summary-panel">
                        <div class="summary-item">
                            <div class="summary-icon"><i class="fa-solid fa-user"></i></div>
                            <div class="summary-detail">
                                <label>Hasta</label>
                                <div class="value">${a.first_name} ${a.last_name}</div>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-icon"><i class="fa-solid fa-stethoscope"></i></div>
                            <div class="summary-detail">
                                <label>Hizmet</label>
                                <div class="value">${a.service_name}</div>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-icon"><i class="fa-solid fa-calendar-day"></i></div>
                            <div class="summary-detail">
                                <label>Tarih & Saat</label>
                                <div class="value">${a.appointment_date} | ${a.appointment_time.substr(0,5)}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sağ Panel: Fiyatlandırma -->
                    <div class="modal-pricing-panel">
                        <div class="confirm-wrapper" style="margin-bottom: 10px; text-align: left; padding: 0;">
                            <h3 class="confirm-title" style="font-size: 16px; margin-bottom: 5px;">Ödeme ve Tahsilat</h3>
                            <p class="confirm-text" style="font-size: 12px; margin-bottom: 15px;">Tahsilat tutarını onaylayarak işlemi tamamlayın.</p>
                        </div>

                        <div style="padding: 15px; background: rgba(134, 135, 195, 0.03); border-radius: 12px; border: 1px dashed rgba(134, 135, 195, 0.2);">
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 11px; margin-bottom: 4px;"><?= __('base_price') ?></label>
                                    <input type="number" step="0.01" id="status_base_price" class="form-control" style="height: 40px; font-size: 13px;" value="${a.base_price}" oninput="calculateStatusPrice()">
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label style="font-size: 11px; margin-bottom: 4px;"><?= __('discount_type') ?></label>
                                        <select id="status_discount_type" class="form-control" style="height: 40px; font-size: 13px;" onchange="calculateStatusPrice()">
                                            <option value="none" ${a.discount_type == 'none' ? 'selected' : ''}><?= __('discount_none') ?></option>
                                            <option value="fixed" ${a.discount_type == 'fixed' ? 'selected' : ''}><?= __('discount_fixed') ?></option>
                                            <option value="percentage" ${a.discount_type == 'percentage' ? 'selected' : ''}><?= __('discount_percentage') ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label style="font-size: 11px; margin-bottom: 4px;"><?= __('discount_value') ?></label>
                                        <input type="number" step="0.01" id="status_discount_value" class="form-control" style="height: 40px; font-size: 13px;" value="${a.discount_value}" oninput="calculateStatusPrice()">
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid rgba(134, 135, 195, 0.1);">
                                <span style="font-weight: 700; color: var(--text-muted); font-size: 11px;"><?= __('net_price') ?></span>
                                <div id="status_net_price_display" style="font-size: 20px; font-weight: 900; color: var(--primary);">${parseFloat(a.service_price).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${globalCurrency}</div>
                                <input type="hidden" id="status_final_price" value="${a.service_price}">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const footer = `
                <div class="confirm-footer-mobile" style="margin-top: 20px;">
                    <button onclick="executeStatusUpdate(${id}, 'completed')" class="btn-premium">Tahsilatı Onayla ve Tamamla</button>
                    <button onclick="closeGlobalModal()" class="btn-secondary">Vazgeç</button>
                </div>
            `;
            
            openGlobalModal('Ödeme ve Onay', body, footer, 'modal-lg');
        }
    });
}


function executeStatusUpdate(id, status) {
    const data = { id: id, status: status };
    
    // Eğer modalda fiyat alanları varsa onları da ekle
    if ($('#status_base_price').length) {
        data.base_price = $('#status_base_price').val();
        data.discount_type = $('#status_discount_type').val();
        data.discount_value = $('#status_discount_value').val();
        data.service_price = $('#status_final_price').val();
    }

    $.post('../actions/appointment_status.php', data, function(res) {
        closeGlobalModal();
        if(res.status == 'success') {
            const type = res.data && res.data.warning ? 'warning' : 'success';
            showToast(res.data && res.data.warning ? 'Dikkat' : 'Başarılı', res.message, type);
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function deleteApt(id) {
    const body = `
        <div class="confirm-wrapper">
            <div class="confirm-icon">
                <i class="fa-solid fa-trash-can"></i>
            </div>
            <h3 class="confirm-title">Randevuyu Sil</h3>
            <p class="confirm-text">Bu randevuyu sistemden kalıcı olarak silmek istediğinize emin misiniz? Bu işlem geri alınamaz.</p>
        </div>
    `;
    
    const footer = `
        <div class="confirm-footer-mobile">
            <button onclick="executeDelete(${id})" class="btn-danger-premium">Evet, Kalıcı Olarak Sil</button>
            <button onclick="closeGlobalModal()" class="btn-secondary"><?= __('cancel') ?></button>
        </div>
    `;
    
    openGlobalModal('Dikkat', body, footer);
}

function executeDelete(id) {
    $.post('../actions/appointment_delete.php', { id: id }, function(res) {
        closeGlobalModal();
        if(res.status == 'success') {
            showToast('Başarılı', res.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function toggleActionMenu(id, event) {
    event.stopPropagation();
    // Close all other menus first
    document.querySelectorAll('.action-menu').forEach(menu => {
        if (menu.id !== 'menu-' + id) menu.classList.remove('active');
    });
    
    const menu = document.getElementById('menu-' + id);
    menu.classList.toggle('active');
}

// Close menus on click outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.action-wrapper')) {
        document.querySelectorAll('.action-menu').forEach(menu => {
            menu.classList.remove('active');
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
