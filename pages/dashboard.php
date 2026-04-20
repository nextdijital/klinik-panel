<?php 
include '../includes/header.php'; 

// Takvim saat ayarlarını çek
$cal_start = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'calendar_start_time'")->fetchColumn() ?: '08:00';
$cal_end = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'calendar_end_time'")->fetchColumn() ?: '19:00';

// Randevu Kategorilerini Çek
$categories = $db->query("SELECT * FROM appointment_categories WHERE is_deleted = 0 ORDER BY id ASC")->fetchAll();

// FullCalendar formatına çevir (HH:mm:ss)
// Bitiş saatini kapsayabilmesi için 30 dakika ekliyoruz (FullCalendar slotMaxTime exclusive'dir)
$slotMinTime = $cal_start . ':00';
$slotMaxTime = date('H:i:s', strtotime($cal_end . ' +30 minutes'));
?>
<style>
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(134, 135, 195, 0.2); border-radius: 10px; }
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}

/* Takvim Etkinlik Stilleri */
.fc-event {
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 1px 0 !important;
    transition: opacity 0.2s ease; /* Sürüklemeyi bozmamak için sadece opaklık geçişi kalsın */
}
.fc-event:hover {
    z-index: 5 !important;
}
.fc-event-dragging, 
.fc-event-dragging .fc-event-inner {
    transition: none !important; 
    animation: none !important;
    opacity: 0.8 !important;
    z-index: 9999 !important;
}
.fc-event-main {
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}
/* Randevu Hizmet Etiketleri (Pill Tags) */
.service-tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}
.service-pill {
    background: #ecfbe6;
    color: #2e5a27;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(46, 90, 39, 0.1);
    animation: tagFadeIn 0.2s ease;
}
.service-pill .remove-pill {
    cursor: pointer;
    background: rgba(46, 90, 39, 0.1);
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    transition: all 0.2s ease;
}
.service-pill .remove-pill:hover {
    background: var(--danger);
    color: white;
}
@keyframes tagFadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Randevu Blokları (Pistachio Pill - Dinamik Dikey Düzen) */
.fc-event {
    margin: 2px 4px !important;
    border: none !important;
    background: transparent !important;
}

.fc-event-inner {
    padding: 6px 10px !important;
    border-radius: 8px !important;
    background: var(--event-bg, #ecfbe6) !important;
    color: var(--event-color, #2e5a27) !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    justify-content: center !important;
    min-height: 38px !important;
    width: 100% !important;
    overflow: hidden;
    transition: background 0.2s ease, transform 0.2s ease;
    border: 1px solid rgba(46, 90, 39, 0.05) !important;
}

.fc-event-title-custom {
    color: white;
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
    line-height: 1.2;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.fc-event-time-custom {
    font-size: 10px;
    font-weight: 800;
    color: rgba(255, 255, 255, 0.9);
    margin-top: 1px;
}

/* Dinamik Uzayan DayGrid Ayarları */
.fc-daygrid-day-frame {
    min-height: 100px !important;
    padding-bottom: 10px !important;
}
.fc-daygrid-event-harness {
    margin: 2px 0 !important;
}
.fc-daygrid-day-number {
    font-weight: 800 !important;
    color: var(--text-muted) !important;
    font-size: 13px !important;
    padding: 10px !important;
}
.fc-timegrid-event {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}



.btn-danger-premium {
    background: #f43f5e;
    color: white;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(244, 63, 94, 0.2);
}

.btn-danger-premium:hover {
    background: #e11d48;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(244, 63, 94, 0.3);
}

.confirm-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px 0;
    text-align: center;
}

.confirm-icon {
    width: 70px;
    height: 70px;
    background: rgba(244, 63, 94, 0.1);
    color: #f43f5e;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin-bottom: 20px;
}

.confirm-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 10px;
}

.confirm-text {
    font-size: 14px;
    color: var(--text-muted);
    max-width: 250px;
    line-height: 1.6;
}
</style>
<?php
// ModalDropdownları için verileri çekelim
$customers_list = $db->query("SELECT id, first_name, last_name FROM customers WHERE is_deleted = 0 ORDER BY first_name ASC")->fetchAll();
$services_list = $db->query("SELECT id, name, price FROM services WHERE is_deleted = 0 AND status = 1 ORDER BY name ASC")->fetchAll();
$staff_list = $db->query("SELECT id, first_name, last_name FROM staff WHERE status = 1 AND is_deleted = 0 ORDER BY first_name ASC")->fetchAll();

// Yaklaşan Randevular (Hala gelmemiş ve önümüzdeki 6 saat içindekiler)
$upcoming_appts = $db->query("
    SELECT a.id, a.appointment_time, c.first_name, c.last_name 
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
?>

<div class="dashboard-grid">
    <!-- Sol Sütun: Widgetlar -->
    <div class="left-widgets">
        <!-- Yaklaşan Randevular Widget -->
        <div class="widget-card" id="upcoming-widget-container" style="height: 300px; display: flex; flex-direction: column;">
            <div class="widget-title" style="margin-bottom: 15px;">
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
        </div>


        <!-- Hızlı İşlemler -->
        <div class="widget-card quick-action-card">
            <div class="widget-title">
                <span>Hızlı Müşteri</span>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="premium-badge badge-blue">Yeni</span>
                    <i class="fa-solid fa-plus-circle quick-add-btn" onclick="openAddModal('customer')"></i>
                </div>
            </div>
            <p class="quick-action-text">Yeni hastaları hızlıca sisteme dahil edin.</p>
        </div>

        <div class="widget-card quick-action-card">
            <div class="widget-title">
                <span>Hızlı Hizmet</span>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="premium-badge badge-lime">Aktif</span>
                    <i class="fa-solid fa-plus-circle quick-add-btn" onclick="openAddModal('service')"></i>
                </div>
            </div>
            <p class="quick-action-text">Yeni tedavi veya hizmet tanımlayın.</p>
        </div>

        <div class="widget-card quick-action-card">
            <div class="widget-title">
                <span>Hızlı Personel</span>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="premium-badge badge-coral">Ekip</span>
                    <i class="fa-solid fa-plus-circle quick-add-btn" onclick="openAddModal('staff')"></i>
                </div>
            </div>
            <p class="quick-action-text">Ekibinize yeni bir üye ekleyin.</p>
        </div>
    </div>

    <!-- Sağ Sütun: Ana Takvim -->
    <div class="large-calendar-container">
        <div class="calendar-header-wrapper">
            <div class="calendar-title-group">
                <h3 id="calendar-title" style="font-size: 24px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">Randevu Çizelgesi</h3>
                <div style="width: 40px; height: 4px; background: var(--primary); border-radius: 10px; margin-top: 8px; opacity: 0.3;"></div>
            </div>

            <div class="calendar-nav-pill">
                <div class="nav-controls">
                    <button class="nav-arrow-btn" onclick="calendar.prev()"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="nav-label-btn" id="calendar-nav-label" onclick="calendar.today()">BUGÜN</button>
                    <button class="nav-arrow-btn" onclick="calendar.next()"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="nav-divider"></div>
                <div class="view-toggles">
                    <button class="toggle-btn" onclick="changeView('timeGridDay', event)">Günlük</button>
                    <button class="toggle-btn active" onclick="changeView('timeGridWeek', event)">Haftalık</button>
                    <button class="toggle-btn" onclick="changeView('dayGridMonth', event)">Aylık</button>
                </div>
            </div>

            <button class="btn-premium" onclick="openAddModal()">
                <i class="fa-solid fa-plus"></i>
                <span>Randevu Ekle</span>
            </button>
        </div>
        <div id="calendar"></div>
    </div>
</div>

<!-- Randevu Ekle Modalı -->
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
                            <button type="button" class="btn-quick-add" onclick="openAddModal('customer')">
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

<!-- Diğer Hızlı Modallar (Hizmet/Personel) Benzer Yapıda -->
<div id="serviceModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Yeni Hizmet Tanımla</h3>
            <button class="modal-close" onclick="$('#serviceModal').hide()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="serviceForm">
            <div class="form-group">
                <label>Hizmet Adı</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Fiyat (<?= $currency ?>)</label>
                <input type="number" name="base_price" class="form-control" required>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>Hizmet Rengi</label>
                <input type="hidden" name="color" id="quick_service_color" value="#A5A6D8">
                <div class="category-selector" style="display: flex; gap: 15px; margin-top: 5px;">
                    <?php foreach($categories as $cat): ?>
                        <div class="category-dot" 
                             data-color="<?= $cat['color'] === '#BEF264' ? '#10B981' : $cat['color'] ?>" 
                             title="<?= $cat['name'] ?>"
                             style="width: 24px; height: 24px; border-radius: 50%; background: <?= $cat['color'] === '#BEF264' ? '#10B981' : $cat['color'] ?>; cursor: pointer; border: 3px solid transparent; transition: all 0.2s ease;">
                        </div>
                    <?php endforeach; ?>
                </div>
                <small class="selected-color-name" style="display: block; margin-top: 8px; font-size: 11px; font-weight: 700; color: var(--primary); text-transform: uppercase; min-height: 15px;"></small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="$('#serviceModal').hide()">Vazgeç</button>
                <button type="submit" class="btn-premium">Hizmeti Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div id="staffModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Yeni Personel Ekle</h3>
            <button class="modal-close" onclick="$('#staffModal').hide()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="staffForm">
            <div class="form-group">
                <label>Ad</label>
                <input type="text" name="first_name" class="form-control" placeholder="Personel Adı" required>
            </div>
            <div class="form-group">
                <label>Soyad</label>
                <input type="text" name="last_name" class="form-control" placeholder="Personel Soyadı" required>
            </div>
            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="phone" class="form-control" placeholder="05xx...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="$('#staffModal').hide()">Vazgeç</button>
                <button type="submit" class="btn-premium">Personeli Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- Randevu Detay Modalı -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-container" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title">Randevu Detayları</h3>
            <button class="modal-close" onclick="$('#detailModal').hide()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div class="detail-card">
            <div class="detail-row">
                <div class="detail-icon"><i class="fa-solid fa-user"></i></div>
                <div class="detail-info">
                    <label>Müşteri</label>
                    <span id="det-customer">-</span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon"><i class="fa-solid fa-phone"></i></div>
                <div class="detail-info">
                    <label>Telefon</label>
                    <span id="det-phone">-</span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon"><i class="fa-solid fa-stethoscope"></i></div>
                <div class="detail-info">
                    <label>Hizmet</label>
                    <span id="det-service">-</span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon"><i class="fa-solid fa-clock"></i></div>
                <div class="detail-info">
                    <label>Zaman</label>
                    <span id="det-time">-</span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon" style="color: #10b981;"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="detail-info">
                    <label><?= __('net_price') ?></label>
                    <span id="det-price" style="font-weight: 800; color: #10b981;">-</span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="detail-info">
                    <label>Personel</label>
                    <span id="det-staff">-</span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon"><i class="fa-solid fa-tag"></i></div>
                <div class="detail-info">
                    <label>Randevu Türü</label>
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                        <div id="det-category-color" style="width: 12px; height: 12px; border-radius: 50%;"></div>
                        <span id="det-category-name" style="font-weight: 700; color: var(--primary); font-size: 13px;">-</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group" style="padding: 0 10px;">
            <label>Notlar</label>
            <p id="det-notes" style="font-size: 14px; color: var(--text-main); background: white; padding: 15px; border-radius: 12px; border: 1px solid rgba(134,135,195,0.1); min-height: 60px;">-</p>
        </div>

        <div class="modal-footer" style="margin-top: 25px; display: flex; flex-direction: column; gap: 10px;">
            <button type="button" class="btn-premium btn-move-trigger" style="width: 100%;" onclick="openEditModal(window.currentEventId)">
                <i class="fa-solid fa-pen-to-square"></i> Randevuyu Düzenle
            </button>
            <button type="button" class="btn-danger-premium" style="width: 100%;" onclick="updateStatus(window.currentEventId, 'cancelled')">
                <i class="fa-solid fa-ban"></i> Randevuyu İptal Et
            </button>
            <button type="button" class="btn-secondary" style="width: 100%;" onclick="$('#detailModal').hide()">Kapat</button>
        </div>
    </div>
</div>

<!-- Mobil Taşıma İptal FAB -->
<button class="move-cancel-fab" id="moveCancelBtn" onclick="exitMoveMode()">
    <i class="fa-solid fa-xmark"></i> Taşıma İşlemini İptal Et
</button>

<!-- Sürükle-Bırak Onay Modalı -->
<div id="moveConfirmModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Randevuyu Kaydır</h3>
            <button class="modal-close" onclick="cancelMove()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="moveConfirmForm">
            <input type="hidden" name="id" id="move-id">
            <input type="hidden" name="action" value="move">
            
            <div style="background: var(--bg-main); padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px dashed var(--primary);">
                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Müşteri</div>
                <div id="move-customer-name" style="font-weight: 700; color: var(--text-main);"></div>
            </div>

            <div class="form-group">
                <label>Hizmet Seçin (Birden fazla seçebilirsiniz)</label>
                <select id="move-service-selector" class="form-control" onchange="addServiceTag(this, 'move')">
                    <option value="">Hizmet eklemek için seçin...</option>
                    <?php foreach($services_list as $sr): ?>
                        <option value="<?= $sr['id'] ?>" data-price="<?= $sr['price'] ?>"><?= $sr['name'] ?> (<?= number_format($sr['price'],0) ?> <?= $currency ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="service_id" id="move-main-service-id" required>
                <div id="move-selected-services-tags" class="service-tags-container"></div>
            </div>

            <div class="form-group">
                <label>Personel</label>
                <select name="staff_id" id="move-staff" class="form-control" required>
                    <option value=""><?= __('select_staff') ?></option>
                    <?php foreach($staff_list as $st): ?>
                        <option value="<?= $st['id'] ?>"><?= $st['first_name'] . ' ' . $st['last_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Yeni Tarih</label>
                    <input type="text" id="move_date" name="new_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Yeni Saat</label>
                    <input type="text" id="move_time" name="new_time" class="form-control" required>
                </div>
            </div>

            <!-- Fiyat Alanları (Gizli ama gönderilmesi gerekiyor) -->
            <input type="hidden" name="base_price" id="move_base_price" value="0">
            <input type="hidden" name="discount_type" id="move_discount_type" value="none">
            <input type="hidden" name="discount_value" id="move_discount_value" value="0">
            <input type="hidden" name="service_price" id="move_final_price" value="0">
            <input type="hidden" name="category_id" id="move-category-id" value="">

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cancelMove()">İptal</button>
                <button type="submit" class="btn-premium">Onayla ve Güncelle</button>
            </div>
        </form>
    </div>
</div>

<script>
let calendar;
let isMoveMode = false;
let pickedEventId = null;

function enterMoveMode() {
    if (window.innerWidth > 1024) return; // PC'de devre dışı 
    isMoveMode = true;
    pickedEventId = window.currentEventId;
    $('#detailModal').hide();
    $('body').addClass('moving-active');
    $('#moveCancelBtn').css('display', 'flex');
    
    // Takvimdeki ilgili eventi bul ve class ekle
    const event = calendar.getEventById(pickedEventId);
    if (event) {
        event.setProp('classNames', ['picked-event']);
    }
    
    showToast('Taşıma Modu', 'Takvimde yeni bir saat dilimine dokunun.', 'info');
}

function exitMoveMode() {
    if (pickedEventId) {
        const event = calendar.getEventById(pickedEventId);
        if (event) event.setProp('classNames', []);
    }
    isMoveMode = false;
    pickedEventId = null;
    $('body').removeClass('moving-active');
    $('#moveCancelBtn').hide();
}

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: window.innerWidth <= 1024 ? 'listWeek' : 'timeGridWeek',
        headerToolbar: false,
        height: window.innerWidth <= 1024 ? 'auto' : '100%',
        expandRows: true,
        stickyHeaderDates: true,
        aspectRatio: window.innerWidth <= 1024 ? 0.8 : 2.2,
        locale: '<?= $_SESSION['lang'] ?? 'tr' ?>',
        slotMinTime: '<?= $slotMinTime ?>',
        slotMaxTime: '<?= $slotMaxTime ?>',
        allDaySlot: false,
        slotDuration: '00:30:00',
        slotLabelInterval: '00:30:00',
        slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        hiddenDays: [0],
        handleWindowResize: true,
        editable: true,
        selectable: true,
        dragRevertDuration: 0, // Hata durumunda veya geri bırakıldığında anında (0ms) yerine otursun
        dragScroll: true,
        longPressDelay: 100,
        events: '../actions/get_appointments.php?v=2.0.0',
        displayEventEnd: false, 
        slotEventOverlap: true,
        eventOrder: 'start,title',
        dayCellDidMount: function(info) {
            // SADECE GERÇEK TELEFON BOYUTUNDA ÇALIŞSIN (PC GÜVENLİĞİ)
            if (window.innerWidth > 768) return;
            
            const dayNames = ['PAZAR', 'PAZARTESİ', 'SALI', 'ÇARŞAMBA', 'PERŞEMBE', 'CUMA', 'CUMARTESİ'];
            const d = info.date;
            const dayLabel = dayNames[d.getDay()];
            const dateLabel = d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth() + 1).toString().padStart(2, '0');
            
            const header = document.createElement('div');
            header.className = 'mobile-day-header';
            header.innerHTML = `<span>${dateLabel}</span> <strong>${dayLabel}</strong>`;
            
            const top = info.el.querySelector('.fc-daygrid-day-top');
            if (top) top.prepend(header);
            else info.el.prepend(header);
        },
        datesSet: function(info) {
            // Takvim her tazelendiğinde hizalama motorunu çalıştır
            setTimeout(alignAppointments, 100);
        },
        eventContent: function(arg) {
            let time = arg.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            let title = arg.event.title;
            const bgColor = arg.event.backgroundColor || '#8687C3';
            
            // Zaman eşleşmesi için arka plan kimliğini basıyoruz
            let h = arg.event.start.getHours();
            let m = arg.event.start.getMinutes();
            let slotM = m < 30 ? 0 : 30; // 30 dk aralığa yuvarla
            let offsetM = m - slotM; // dk fazlası
            let slotStr = String(h).padStart(2, '0') + ':' + String(slotM).padStart(2, '0') + ':00';
            
            return {
                html: `
                    <div data-slot="${slotStr}" data-offset="${offsetM}" style="background: ${bgColor}; border-radius: 10px; height: 100%; width: 100%; display: flex; flex-direction: column; justify-content: center; padding: 6px 10px; overflow: hidden; box-sizing: border-box;">
                        <div style="color: white; font-size: 13px; font-weight: 800; text-shadow: 0 1px 3px rgba(0,0,0,0.5); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height:1.2;">${title}</div>
                        <div style="color: white; font-size: 11px; font-weight: 700; margin-top:2px;">${time}</div>
                    </div>
                `
            };
        },

        dayCellDidMount: function(info) {
            if (window.innerWidth > 1024) return; // PC'de başlık ekleme
            
            const dayNames = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
            const d = info.date;
            const dayLabel = dayNames[d.getDay()];
            const dateLabel = d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth() + 1).toString().padStart(2, '0');
            
            const header = document.createElement('div');
            header.className = 'mobile-day-header';
            header.innerHTML = `<span>${dateLabel}</span> <strong>${dayLabel}</strong>`;
            
            const top = info.el.querySelector('.fc-daygrid-day-top');
            if (top) top.prepend(header);
            else info.el.prepend(header);
        },

        eventClick: function(info) {
            const props = info.event.extendedProps;
            const allServices = props.service_name + (props.extraServices ? ', ' + props.extraServices : '');
            
            $('#det-customer').text(info.event.title);
            $('#det-phone').text(props.customer_phone || '-');
            $('#det-service').text(allServices || '-');
            $('#det-time').text(info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}));
            $('#det-staff').text(props.staff_name || 'Atanmamış');
            $('#det-notes').text(props.notes || 'Not eklenmemiş.');

            // Ücret Bilgisini Bas
            const netPrice = parseFloat(props.service_price || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2});
            const basePrice = parseFloat(props.base_price || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2});
            let priceHtml = `${netPrice} <?= $currency ?>`;
            if (props.discount_type && props.discount_type !== 'none') {
                priceHtml += ` <span style="font-size: 11px; color: var(--text-muted); text-decoration: line-through; font-weight: 400; margin-left: 5px;">(${basePrice} <?= $currency ?>)</span>`;
            }
            $('#det-price').html(priceHtml);

            // Hizmet rengini backgroundColor'dan al
            const mainColor = info.event.backgroundColor || '#A5A6D8';
            $('#det-category-name').text(props.service_name);
            $('#det-category-color').css('background-color', mainColor);
            
            window.currentEventId = info.event.id;
            $('#detailModal').fadeIn(200).css('display', 'flex');
        },
        eventDrop: function(info) {
            handleEventDrop(info);
        },
        dateClick: function(info) {
            if (isMoveMode && pickedEventId) {
                const event = calendar.getEventById(pickedEventId);
                if (!event) return;

                // Sahte bir info objesi oluşturup handleEventDrop'u tetikliyoruz
                const mockInfo = {
                    event: event,
                    oldEvent: event,
                    revert: () => exitMoveMode()
                };

                // Yeni tarih ve saat bilgisini event'e set et (görsel olarak değil, modal için)
                // handleEventDrop normalde sürükleme sonrası veriyi event objesinden okur
                // Ancak tıklandığında biz manuel güncellemeliyiz
                
                const start = info.date;
                const end = new Date(start.getTime() + (event.end ? (event.end.getTime() - event.start.getTime()) : 1800000));
                
                // Modal için global değişkeni set et
                window.currentDroppedEvent = mockInfo;
                
                // Modal verilerini doldur
                $('#move-id').val(pickedEventId);
                $('#move-customer-name').text(event.title);
                
                const dateStr = start.getFullYear() + '-' + String(start.getMonth() + 1).padStart(2, '0') + '-' + String(start.getDate()).padStart(2, '0');
                const timeStr = start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: false});
                
                $('#moveConfirmModal').fadeIn(200).css('display', 'flex');
                
                setTimeout(() => {
                    if (document.querySelector("#move_date")._flatpickr) {
                        document.querySelector("#move_date")._flatpickr.setDate(dateStr, true);
                    }
                    if (document.querySelector("#move_time")._flatpickr) {
                        document.querySelector("#move_time")._flatpickr.setDate(timeStr, true);
                    }
                }, 100);

                // Diğer verileri yükle
                $('#move-staff').val(event.extendedProps.staff_id || '').trigger('change');
                $('#move-category-id').val(event.extendedProps.category_id || '');
                $('#move_base_price').val(event.extendedProps.base_price || 0);
                
                // Modu kapat
                exitMoveMode();
            }
        }
    });

    calendar.render();

    // --- Kesin Çözüm: Hizalama Motoru (Hardware-Synced Semantic Edition) ---
    function alignAppointments() {
        // Hem Haftalık hem Günlük görünümü destekle
        if (calendar.view.type !== 'timeGridWeek' && calendar.view.type !== 'timeGridDay') return; 

        const slotsTrs = Array.from(document.querySelectorAll('.fc-timegrid-slots tr'));
        if (slotsTrs.length === 0) return;
        
        // 1. Önceki tüm esnetmeleri SIFIRLA
        slotsTrs.forEach(tr => tr.style.removeProperty('height'));
        const colsTable = document.querySelector('.fc-timegrid-cols table');
        if (colsTable) colsTable.style.removeProperty('height');

        let defaultSlotHeight = parseFloat(slotsTrs[0].getBoundingClientRect().height) || 40;

        // 2. Her satırın zaman objelerini haritalandır
        let slotData = [];
        slotsTrs.forEach((tr) => {
            let slotTd = tr.querySelector('.fc-timegrid-slot[data-time]');
            if (slotTd) {
                slotData.push({ 
                    time: slotTd.getAttribute('data-time'), 
                    tr: tr, 
                    neededHeight: defaultSlotHeight 
                });
            }
        });

        // 3. Orijinal Kutuların İhtiyaç Duyduğu Boyutları Kaydet 
        document.querySelectorAll('.fc-timegrid-col').forEach(col => {
            const harnesses = Array.from(col.querySelectorAll('.fc-timegrid-event-harness:not(.fc-event-dragging)'));
            
            // Boyut Kaydı (Süre Koruma)
            harnesses.forEach(h => {
                if (!h.hasAttribute('data-orig-height')) {
                    const childRect = h.getBoundingClientRect();
                    if (childRect.height === 0 && !h.classList.contains('fc-event-mirror')) return;
                    h.setAttribute('data-orig-height', childRect.height || 48);
                }
            });
            
            // Mirror (Sürüklenen Hayalet) Tespiti - Daha kararlı koordinat okuma
            const mirror = col.querySelector('.fc-event-mirror');
            let mirrorSlotTime = null;
            if (mirror) {
                const mirrorRect = mirror.getBoundingClientRect();
                const mirrorCenterY = mirrorRect.top + (mirrorRect.height / 2); // Merkeze göre hesapla
                
                const targetTr = slotData.find(s => {
                    const trRect = s.tr.getBoundingClientRect();
                    return mirrorCenterY >= trRect.top && mirrorCenterY < trRect.bottom;
                });
                if (targetTr) mirrorSlotTime = targetTr.time;
            }

            // Satır İhtiyaç Alanını Bulma
            if (harnesses.length === 0) return;
            let colGroups = {};
            harnesses.forEach(h => {
                let sTime = null;
                let innerDiv = h.querySelector('[data-slot]');
                if (innerDiv) {
                    sTime = innerDiv.getAttribute('data-slot');
                } else if (h.classList.contains('fc-event-mirror') && mirrorSlotTime) {
                    sTime = mirrorSlotTime;
                }

                if (!sTime) return;
                if (!colGroups[sTime]) colGroups[sTime] = [];
                colGroups[sTime].push(h);
            });

            for (let sTime in colGroups) {
                let sMap = slotData.find(s => s.time === sTime);
                if (!sMap) continue;

                let totalHeight = 0;
                colGroups[sTime].forEach(h => {
                    let origH = parseFloat(h.getAttribute('data-orig-height')) || 44;
                    let targetH = Math.max(origH, 48); 
                    totalHeight += targetH + 4; 
                });
                
                if (totalHeight > sMap.neededHeight) {
                    sMap.neededHeight = totalHeight;
                }
            }
        });

        // 4. ÇİZGİLERİ ESNET
        slotData.forEach((s) => {
            s.tr.style.setProperty('height', s.neededHeight + 'px', 'important');
        });

        const slotsTable = document.querySelector('.fc-timegrid-slots table');
        if (colsTable && slotsTable) {
            colsTable.style.setProperty('height', slotsTable.offsetHeight + 'px', 'important');
        }

        // 5. EVENTLERİ MÜKEMMEL DOM KOORDİNATLARINA GÖRE YAPIŞTIR
        document.querySelectorAll('.fc-timegrid-col').forEach(col => {
            const colEventsContainer = col.querySelector('.fc-timegrid-col-events');
            if (!colEventsContainer) return;
            
            const colRect = colEventsContainer.getBoundingClientRect();
            const harnesses = Array.from(col.querySelectorAll('.fc-timegrid-event-harness:not(.fc-event-dragging)'));
            
            let colGroups = {};
            harnesses.forEach(h => {
                let sTime = null;
                let innerDiv = h.querySelector('[data-slot]');
                if (innerDiv) {
                    sTime = innerDiv.getAttribute('data-slot');
                } else if (h.classList.contains('fc-event-mirror')) {
                    const mirrorRect = h.getBoundingClientRect();
                    const mirrorCenterY = mirrorRect.top + (mirrorRect.height / 2);
                    const targetTr = slotData.find(s => {
                        const trRect = s.tr.getBoundingClientRect();
                        return mirrorCenterY >= trRect.top && mirrorCenterY < trRect.bottom;
                    });
                    if (targetTr) sTime = targetTr.time;
                }

                if (!sTime) return;
                if (!colGroups[sTime]) colGroups[sTime] = [];
                colGroups[sTime].push({h: h, inner: innerDiv});
            });

            for (let sTime in colGroups) {
                let sMap = slotData.find(s => s.time === sTime);
                if (!sMap) continue;
                
                let group = colGroups[sTime];
                group.sort((a,b) => {
                    if (a.h.classList.contains('fc-event-mirror')) return 1;
                    if (b.h.classList.contains('fc-event-mirror')) return -1;
                    return parseInt(a.inner?.getAttribute('data-offset') || 0) - parseInt(b.inner?.getAttribute('data-offset') || 0);
                });
                
                let trRect = sMap.tr.getBoundingClientRect();
                let currentY = trRect.top - colRect.top; 
                
                group.forEach(item => {
                    const h = item.h;
                    let origH = parseFloat(h.getAttribute('data-orig-height')) || 44;
                    let targetH = Math.max(origH, 48); 
                    
                    h.style.setProperty('position', 'absolute', 'important');
                    h.style.setProperty('inset', 'auto', 'important'); 
                    h.style.setProperty('bottom', 'auto', 'important'); 
                    h.style.setProperty('top', currentY + 'px', 'important');
                    h.style.setProperty('height', targetH + 'px', 'important');
                    h.style.setProperty('width', '96%', 'important');   
                    h.style.setProperty('left', '2%', 'important'); 
                    h.style.setProperty('right', 'auto', 'important');
                    h.style.setProperty('margin', '0', 'important'); 
                    h.style.setProperty('transform', 'none', 'important'); 
                    h.style.setProperty('z-index', h.classList.contains('fc-event-mirror') ? '100' : '10', 'important');
                    
                    if (item.inner) {
                        item.inner.style.setProperty('margin', '0', 'important');
                        item.inner.style.setProperty('width', '100%', 'important');
                    }
                    
                    currentY += targetH + 4; 
                });
            }
        });
    }

    const observer = new MutationObserver(debounce(alignAppointments, 16));
    observer.observe(calendarEl, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });

    function debounce(func, wait) {
        let timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(func, wait);
        };
    }

    // --- Countdown Logic ---
    function updateCountdowns() {
        const now = new Date();
        document.querySelectorAll('.upcoming-item-card').forEach(card => {
            const target = new Date(card.getAttribute('data-time'));
            const diff = target - now;

            if (diff <= 0) {
                // Zaman doldu - kaldır ve takvimi yenile
                card.style.opacity = '0';
                card.style.transform = 'translateX(20px)';
                setTimeout(() => {
                    card.remove();
                    if (calendar) calendar.refetchEvents();
                }, 300);
            } else {
                const minutes = Math.floor(diff / 1000 / 60);
                const seconds = Math.floor((diff / 1000) % 60);
                card.querySelector('.countdown-timer').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')} kaldı`;
            }
        });
    }
    
    setInterval(updateCountdowns, 1000);
    updateCountdowns();

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

    flatpickr("#move_date", {
        locale: "tr",
        altInput: true,
        altFormat: "d.m.Y",
        dateFormat: "Y-m-d",
        disableMobile: true
    });

    flatpickr("#move_time", {
        locale: "tr",
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        minuteIncrement: 30,
        disableMobile: true
    });


    // Form Submit Handlers
    $('#aptForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '../actions/appointment_add.php', '#aptModal');
    });

    $('#customerForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '../actions/customer_add.php', '#customerModal', true);
    });

    $('#serviceForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '../actions/service_add.php', '#serviceModal', true);
    });

    $('#staffForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '../actions/staff_add.php', '#staffModal');
    });

    $('#moveConfirmForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '../actions/appointment_status.php', '#moveConfirmModal');
    });

    // Category Selector Handler
    $(document).on('click', '.category-dot', function() {
        $('.category-dot').css({'border-color': 'transparent', 'transform': 'scale(1)'});
        $(this).css({'border-color': 'var(--primary)', 'transform': 'scale(1.1)'});
        $('#selected-category-id').val($(this).data('id'));
        $('#selected-category-name').text($(this).attr('title'));
    });
});


function submitForm(form, url, modalId, reloadOnSuccess = false) {
    $.post(url, form.serialize(), function(res) {
        if(res.status == 'success') {
            // Eğer hızlı müşteri kaydı ise ve randevu modalı açıksa
            if (url.includes('customer_add.php') && $('#aptModal').is(':visible')) {
                const newOption = new Option(res.data.full_name, res.data.id, true, true);
                $('#apt_customer_id').append(newOption).trigger('change');
                $(modalId).hide();
                form[0].reset();
                showToast('Başarılı', 'Yeni müşteri seçildi.', 'success');
                return;
            }

            $(modalId).hide();
            const type = res.data && res.data.warning ? 'warning' : 'success';
            showToast(res.data && res.data.warning ? 'Dikkat' : 'Başarılı', res.message, type);
            form[0].reset();
            if(reloadOnSuccess) {
                refreshDashboardUI(); // Artık premium bir deneyim için reload yerine UI tazeleyelim
            } else {
                refreshDashboardUI();
            }
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function openAddModal(type = 'appointment') {
    if(type == 'appointment') {
        selectedServices = []; // Reset
        $('#selected-services-tags').html('');
        $('#main-service-id').val('');
        $('#apt_base_price').val('0.00');
        $('#apt_discount_type').val('none');
        $('#apt_discount_value').val('0');
        calculateAppointmentPrice();
        $('#aptForm')[0].reset();
        $('#aptModal').css('display', 'flex');
    }
    else if(type == 'customer') $('#customerModal').css('display', 'flex');
    else if(type == 'service') $('#serviceModal').css('display', 'flex');
    else if(type == 'staff') $('#staffModal').css('display', 'flex');
}

function calculateAppointmentPrice(context = 'apt') {
    const prefix = context === 'move' ? '#move_' : '#apt_';
    const base = parseFloat($(prefix + 'base_price').val()) || 0;
    const type = $(prefix + 'discount_type').val();
    const val = parseFloat($(prefix + 'discount_value').val()) || 0;
    
    let net = base;
    if (type === 'fixed') {
        net = base - val;
    } else if (type === 'percentage') {
        net = base - (base * (val / 100));
    }
    
    if (net < 0) net = 0;
    
    if (context === 'apt') {
        $('#apt_net_price_display').text(net.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + '<?= $currency ?>');
    }
    $(prefix + 'final_price').val(net.toFixed(2));
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
    
    $('#status_net_price_display').text(net.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + '<?= $currency ?>');
    $('#status_final_price').val(net.toFixed(2));
}

let selectedServices = [];

function updateStatus(id, status) {
    if (status === 'completed') {
        const body = `
            <div class="confirm-wrapper">
                <div class="confirm-icon" style="background: rgba(134, 135, 195, 0.1); color: var(--primary);">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h3 class="confirm-title">Randevuyu Tamamla</h3>
                <p class="confirm-text">Bu randevuyu tamamland&#305; olarak i&#351;aretlemek ve ödeme detaylar&#305;n&#305; girmek istiyor musunuz?</p>
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
        const title = status == 'cancelled' ? 'Randevuyu İptal Et' : 'Durumu Güncelle';
        const text = status == 'cancelled' ? 'Bu randevuyu iptal etmek istediğinize emin misiniz?' : 'Randevu durumunu güncellemek istiyor musunuz?';
        const btnText = status == 'cancelled' ? 'Evet, İptal Et' : 'Evet, Güncelle';
        const icon = status == 'cancelled' ? 'fa-ban' : 'fa-clock';

        const body = `
            <div class="confirm-wrapper">
                <div class="confirm-icon" style="${status == 'cancelled' ? 'background: rgba(244, 63, 94, 0.1); color: #f43f5e;' : 'background: rgba(134, 135, 195, 0.1); color: var(--primary);'}">
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
    $.get('../actions/get_appointment.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const a = res.data;
            const currency = typeof globalCurrency !== 'undefined' ? globalCurrency : '₺';
            const body = `
                <div class="modal-split-grid">
                    <!-- Left Panel: Summary -->
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

                    <!-- Right Panel: Pricing -->
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
                                <div id="status_net_price_display" style="font-size: 20px; font-weight: 900; color: var(--primary);">${parseFloat(a.service_price).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${currency}</div>
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
    
    if ($('#status_base_price').length) {
        data.base_price = $('#status_base_price').val();
        data.discount_type = $('#status_discount_type').val();
        data.discount_value = $('#status_discount_value').val();
        data.service_price = $('#status_final_price').val();
    }

    $.post('../actions/appointment_status.php', data, function(res) {
        closeGlobalModal();
        if(res.status == 'success') {
            const statusType = res.data && res.data.warning ? 'warning' : 'success';
            showToast(res.data && res.data.warning ? 'Dikkat' : 'Başarılı', res.message, statusType);
            refreshDashboardUI();
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

function addServiceTag(select, context = 'apt') {
    const option = select.options[select.selectedIndex];
    const serviceId = option.value;
    const serviceName = option.text;
    const servicePrice = parseFloat(option.getAttribute('data-price')) || 0;
    
    const targetSelectedServices = context === 'move' ? moveSelectedServices : selectedServices;
    const containerId = context === 'move' ? 'move-selected-services-tags' : 'selected-services-tags';
    const mainInputId = context === 'move' ? 'move-main-service-id' : 'main-service-id';
    const basePriceId = context === 'move' ? '#move_base_price' : '#apt_base_price';

    if (!serviceId || targetSelectedServices.includes(serviceId)) {
        select.value = "";
        return;
    }

    targetSelectedServices.push(serviceId);
    
    const container = document.getElementById(containerId);
    const pill = document.createElement('div');
    pill.className = 'service-pill';
    pill.id = (context === 'move' ? 'move-pill-' : 'pill-') + serviceId;
    pill.setAttribute('data-price', servicePrice);
    pill.innerHTML = `
        <span>${serviceName}</span>
        <input type="hidden" name="${targetSelectedServices.length === 1 ? 'service_id' : 'extra_services[]'}" value="${serviceId}">
        <span class="remove-pill" onclick="removeServiceTag('${serviceId}', '${context}')"><i class="fa-solid fa-xmark"></i></span>
    `;
    
    if (targetSelectedServices.length === 1) {
        document.getElementById(mainInputId).value = serviceId;
    }
    
    container.appendChild(pill);
    
    let currentBase = parseFloat($(basePriceId).val()) || 0;
    $(basePriceId).val((currentBase + servicePrice).toFixed(2));
    calculateAppointmentPrice(context);

    select.value = "";
}

function removeServiceTag(serviceId, context = 'apt') {
    const pillId = (context === 'move' ? 'move-pill-' : 'pill-') + serviceId;
    const pill = document.getElementById(pillId);
    if (!pill) return;

    const targetSelectedServices = context === 'move' ? moveSelectedServices : selectedServices;
    const basePriceId = context === 'move' ? '#move_base_price' : '#apt_base_price';
    const mainInputId = context === 'move' ? 'move-main-service-id' : 'main-service-id';
    const containerId = context === 'move' ? 'move-selected-services-tags' : 'selected-services-tags';

    const sPrice = parseFloat(pill.getAttribute('data-price')) || 0;
    
    // UI'dan sil
    pill.remove();
    
    // Diziden çıkar
    const idx = targetSelectedServices.findIndex(id => id.toString() === serviceId.toString());
    if (idx > -1) targetSelectedServices.splice(idx, 1);
    
    // Verileri tazele
    if (targetSelectedServices.length > 0) {
        document.getElementById(mainInputId).value = targetSelectedServices[0];
        // Input isimlerini düzenle (ilk eleman service_id, diğerleri extra_services[])
        const container = document.getElementById(containerId);
        const pills = container.querySelectorAll('.service-pill');
        pills.forEach((p, index) => {
            const input = p.querySelector('input');
            if (input) input.name = (index === 0) ? 'service_id' : 'extra_services[]';
        });
    } else {
        document.getElementById(mainInputId).value = "";
    }
    
    let currentBase = parseFloat($(basePriceId).val()) || 0;
    $(basePriceId).val(Math.max(0, currentBase - sPrice).toFixed(2));
    calculateAppointmentPrice(context);
}

let moveSelectedServices = [];

// --- Seamless UI Refresh (No Reload) ---
function refreshDashboardUI() {
    // 1. Takvimi Yenile
    if (typeof calendar !== 'undefined') {
        calendar.refetchEvents();
    }
    
    // 2. Yan Paneli (Yaklaşanlar) Yenile
    $.get('../actions/get_dashboard_sidebar.php', function(res) {
        if(res.status == 'success') {
            $('#upcoming-widget-container').html(res.html);
            // Geri sayım başlatıcıyı tekrar tetikle
            updateCountdowns();
        }
    }, 'json');
}

function changeView(viewName, e) {
    let targetView = viewName;
    let labelText = "BUGÜN";
    
    // Mobilde Haftalık görünümü Liste yapısına (listWeek) çevir
    if (window.innerWidth <= 1024 && viewName === 'timeGridWeek') {
        targetView = 'listWeek';
    }
    
    // Mobilde Günlük görünümü Liste yapısına (listDay) çevir
    if (window.innerWidth <= 1024 && viewName === 'timeGridDay') {
        targetView = 'listDay';
    }

    // Mobilde Aylık görünümü Liste yapısına (listMonth) çevir (İstek üzerine: blok tasarımı)
    if (window.innerWidth <= 1024 && viewName === 'dayGridMonth') {
        targetView = 'listMonth';
    }

    // Navigasyon etiketi belirleme
    if (viewName === 'timeGridDay') labelText = "GÜN";
    else if (viewName === 'timeGridWeek') labelText = "HAFTA";
    else if (viewName === 'dayGridMonth') labelText = "AY";
    
    $('#calendar-nav-label').text(labelText);
    
    calendar.changeView(targetView);
    document.querySelectorAll('.view-toggles .toggle-btn').forEach(btn => btn.classList.remove('active'));
    
    // Aktif butonu seç
    if(e) {
        e.target.classList.add('active');
    } else {
        // Eğer e yoksa (manuel çağrı), viewName'e göre bulup ekle
        const btnMap = { 'timeGridDay': 0, 'timeGridWeek': 1, 'dayGridMonth': 2 };
        const btns = document.querySelectorAll('.view-toggles .toggle-btn');
        if (btns[btnMap[viewName]]) btns[btnMap[viewName]].classList.add('active');
    }
}

function handleEventDrop(info) {
    // Eğer konum/zaman hiç değişmediyse görseli sıfırla ve işlemi bitir (Donmayı engeller)
    if (info.oldEvent.start.getTime() === info.event.start.getTime() && 
        info.oldEvent.extendedProps.staff_id === info.event.extendedProps.staff_id) {
        info.revert();
        return;
    }

    window.currentDroppedEvent = info;
    const event = info.event;
    
    $('#move-id').val(event.id);
    $('#move-customer-name').text(event.title);
    
    // Yeni tarih ve saat bilgisini ayır ve Flatpickr'a kesin olarak işlet!
    const start = event.start;
    const dateStr = start.getFullYear() + '-' + String(start.getMonth() + 1).padStart(2, '0') + '-' + String(start.getDate()).padStart(2, '0');
    const timeStr = start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: false});
    
    // Flatpickr instance'larını güncelle ve değişikliği tetikle
    setTimeout(() => {
        if (document.querySelector("#move_date")._flatpickr) {
            document.querySelector("#move_date")._flatpickr.setDate(dateStr, true);
        } else {
            $('#move_date').val(dateStr);
        }

        if (document.querySelector("#move_time")._flatpickr) {
            document.querySelector("#move_time")._flatpickr.setDate(timeStr, true);
        } else {
            $('#move_time').val(timeStr);
        }
        
        $('#move_date').trigger('change');
        $('#move_time').trigger('change');
    }, 100);
    
    // Mevcut personel, hizmet ve kategoriyi gizli/seçili tut
    $('#move-staff').val(event.extendedProps.staff_id || '').trigger('change');
    $('#move-category-id').val(event.extendedProps.category_id || '');
    
    // Fiyatları yükle
    $('#move_base_price').val(event.extendedProps.base_price || 0);
    $('#move_discount_type').val(event.extendedProps.discount_type || 'none');
    $('#move_discount_value').val(event.extendedProps.discount_value || 0);
    $('#move_final_price').val(event.extendedProps.service_price || 0);

    // Hizmetleri yükle (Pill tags)
    moveSelectedServices = [];
    $('#move-selected-services-tags').html('');
    $('#move-main-service-id').val('');

    // Ana hizmeti ekle
    const mainServiceId = event.extendedProps.service_id;
    const selector = document.getElementById('move-service-selector');
    if (mainServiceId) {
        selector.value = mainServiceId;
        addServiceTag(selector, 'move');
    }

    // Ek hizmetleri ekle
    const extraIds = event.extendedProps.extraServiceIds ? event.extendedProps.extraServiceIds.split(',') : [];
    extraIds.forEach(eid => {
        if (eid.trim()) {
            selector.value = eid.trim();
            addServiceTag(selector, 'move');
        }
    });
    
    $('#moveConfirmModal').fadeIn(200).css('display', 'flex');
}

function openEditModal(id) {
    if(!id) return;
    
    // Detay modalını kapat
    $('#detailModal').hide();
    
    $.get('../actions/get_appointment.php', { id: id }, function(res) {
        if(res.status == 'success') {
            const a = res.data;
            
            // Modal başlığını güncelle
            $('#moveConfirmModal .modal-title').text('Randevuyu Düzenle');
            
            // Form verilerini doldur
            $('#move-id').val(a.id);
            $('#move-customer-name').text(a.first_name + ' ' + a.last_name);
            
            // Tarih ve Saati işlet
            if (document.querySelector("#move_date")._flatpickr) {
                document.querySelector("#move_date")._flatpickr.setDate(a.appointment_date, true);
            }
            if (document.querySelector("#move_time")._flatpickr) {
                document.querySelector("#move_time")._flatpickr.setDate(a.appointment_time, true);
            }
            
            // Personel ve Kategori
            $('#move-staff').val(a.staff_id || '').trigger('change');
            $('#move-category-id').val(a.category_id || '');
            
            // Ücretlendirme
            $('#move_base_price').val(a.base_price || 0);
            $('#move_discount_type').val(a.discount_type || 'none');
            $('#move_discount_value').val(a.discount_value || 0);
            $('#move_final_price').val(a.service_price || 0);

            // Hizmetleri yükle
            moveSelectedServices = [];
            $('#move-selected-services-tags').html('');
            $('#move-main-service-id').val('');

            // Ana hizmet
            const selector = document.getElementById('move-service-selector');
            if (a.service_id) {
                selector.value = a.service_id;
                addServiceTag(selector, 'move');
            }

            // Ek hizmetler
            if (a.extraServiceIds) {
                const extraIds = a.extraServiceIds.split(',');
                extraIds.forEach(eid => {
                    if (eid.trim() && eid.trim() != a.service_id) {
                        selector.value = eid.trim();
                        addServiceTag(selector, 'move');
                    }
                });
            }
            
            $('#moveConfirmModal').fadeIn(200).css('display', 'flex');
        } else {
            showToast('Hata', 'Randevu verileri alınamadı.', 'error');
        }
    });
}

function cancelMove() {
    if (window.currentDroppedEvent) {
        window.currentDroppedEvent.revert();
    }
    $('#moveConfirmModal').hide();
}

function updateAppointmentTime(event) {
    // Bu fonksiyon artık kullanılmıyor, handleEventDrop ve moveConfirmForm kullanılıyor
}
function deleteAppointment() {
    if(!confirm('Bu randevuyu silmek istediğinize emin misiniz?')) return;
    
    $.post('../actions/appointment_status.php', { 
        id: window.currentEventId, 
        action: 'cancel'
    }, function(res) {
        if(res.status == 'success') {
            $('#detailModal').hide();
            showToast('Silindi', 'Randevu başarıyla iptal edildi.', 'success');
            calendar.refetchEvents();
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}
    // Renk Butonları Seçim Mantığı (Quick-Add Modalları için)
    $(document).on('click', '.category-dot', function() {
        const name = $(this).attr('title');
        const parent = $(this).closest('.form-group');
        parent.find('.category-dot').css('border-color', 'transparent');
        $(this).css('border-color', 'var(--primary)');
        parent.find('.selected-color-name').text(name);
        $('#quick_service_color').val($(this).data('color'));
    });
</script>

<?php include '../includes/footer.php'; ?>
