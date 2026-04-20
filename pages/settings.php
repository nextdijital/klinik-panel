<?php
require_once __DIR__ . '/../functions.php';
check_login();

// Fetch settings
$settings_query = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_query->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = __('settings');
include __DIR__ . '/../includes/header.php';
// NOTE: sidebar.php is already included in header.php, so we don't include it here.
?>

<main class="main-content" style="flex: 1; padding: 20px; overflow-y: auto;">
    <div class="settings-container" style="max-width: 1200px; margin: 0 auto;">
        <div class="settings-grid">
            <!-- Sidebar Navigation -->
            <div class="settings-sidebar">
                <nav class="settings-menu">
                    <button class="settings-menu-item active" data-tab="general">
                        <i class="fa-solid fa-building"></i>
                        <span><?= __('company_settings') ?></span>
                    </button>
                    <button class="settings-menu-item" data-tab="sms">
                        <i class="fa-solid fa-comment-sms"></i>
                        <span><?= __('sms_settings') ?></span>
                    </button>
                    <button class="settings-menu-item" data-tab="security">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span><?= __('security_settings') ?></span>
                    </button>
                    <button class="settings-menu-item" data-tab="categories">
                        <i class="fa-solid fa-tags"></i>
                        <span>Randevu Türleri</span>
                    </button>
                </nav>
            </div>

            <!-- Content Area -->
            <div class="settings-main">
                <!-- General Section -->
                <div class="settings-pane active" id="general">
                    <div class="pane-header">
                        <h3><?= __('company_settings') ?></h3>
                        <p>Klinik bilgilerinizi ve marka kimliğinizi buradan yönetebilirsiniz.</p>
                    </div>

                    <form id="general-settings-form" class="premium-form" enctype="multipart/form-data">
                        <input type="hidden" name="form_context" value="general">
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label><?= __('company_name') ?></label>
                                <input type="text" name="clinic_name" value="<?= htmlspecialchars($settings['clinic_name'] ?? '') ?>" class="form-control" placeholder="Örn: KlinikMod Poliklinik">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group half-width">
                                <label><?= __('currency_settings') ?></label>
                                <select name="currency" class="form-control">
                                    <option value="₺" <?= ($settings['currency'] == '₺') ? 'selected' : '' ?>>₺ (Türk Lirası)</option>
                                    <option value="$" <?= ($settings['currency'] == '$') ? 'selected' : '' ?>>$ (Dolar)</option>
                                    <option value="€" <?= ($settings['currency'] == '€') ? 'selected' : '' ?>>€ (Euro)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group half-width">
                                <label>Takvim Başlangıç Saati</label>
                                <select name="calendar_start_time" class="form-control">
                                    <?php for($i=5; $i<=15; $i++): $h = str_pad($i, 2, '0', STR_PAD_LEFT).':00'; ?>
                                        <option value="<?= $h ?>" <?= (($settings['calendar_start_time'] ?? '08:00') == $h) ? 'selected' : '' ?>><?= $h ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group half-width">
                                <label>Takvim Bitiş Saati</label>
                                <select name="calendar_end_time" class="form-control">
                                    <?php for($i=16; $i<=23; $i++): $h = str_pad($i, 2, '0', STR_PAD_LEFT).':00'; ?>
                                        <option value="<?= $h ?>" <?= (($settings['calendar_end_time'] ?? '19:00') == $h) ? 'selected' : '' ?>><?= $h ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- İptal ayarları buradan kaldırıldı ve Randevu Türleri sekmesine taşındı -->


                        <div class="action-footer">
                            <button type="submit" class="btn-premium">
                                <i class="fa-solid fa-check"></i> <?= __('save') ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SMS Section -->
                <div class="settings-pane" id="sms">
                    <div class="pane-header">
                        <h3><?= __('sms_settings') ?></h3>
                        <p>NetGSM API entegrasyonu ve otomatik hatırlatma servisleri.</p>
                    </div>

                    <form id="sms-settings-form" class="premium-form">
                        <input type="hidden" name="form_context" value="sms">
                        <div class="form-row">
                            <div class="form-group">
                                <label><?= __('netgsm_user') ?></label>
                                <input type="text" name="netgsm_user" value="<?= htmlspecialchars($settings['netgsm_user'] ?? '') ?>" class="form-control" placeholder="1234567">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= __('netgsm_pass') ?></label>
                                <input type="password" name="netgsm_pass" value="<?= htmlspecialchars($settings['netgsm_pass'] ?? '') ?>" class="form-control" placeholder="********">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= __('netgsm_header') ?></label>
                                <input type="text" name="netgsm_header" value="<?= htmlspecialchars($settings['netgsm_header'] ?? '') ?>" class="form-control" placeholder="KLINIKMOD">
                            </div>
                        </div>

                        <!-- Randevu Hatırlatma Ayarı -->
                        <div class="toggle-card" style="margin-bottom: 20px;">
                            <div class="toggle-info">
                                <strong>Randevu Hatırlatma SMS'i</strong>
                                <p>Randevudan bir gün önce müşteriye otomatik hatırlatma gider.</p>
                            </div>
                            <label class="switch-pill">
                                <input type="checkbox" name="reminder_sms_enabled" <?= ($settings['reminder_sms_enabled'] == '1') ? 'checked' : '' ?>>
                                <span class="slider-pill">
                                    <span class="status-text text-on"><?= __('active') ?></span>
                                    <span class="status-text text-off"><?= __('passive') ?></span>
                                </span>
                            </label>
                        </div>

                        <!-- Yeni Randevu Bildirimi Ayarı -->
                        <div class="toggle-card">
                            <div class="toggle-info">
                                <strong>Randevu Oluşturulduğunda SMS Gönder</strong>
                                <p>Yeni randevu eklendiği an müşteriye bilgi mesajı gider.</p>
                            </div>
                            <label class="switch-pill">
                                <input type="checkbox" name="sms_new_appointment_enabled" <?= ($settings['sms_new_appointment_enabled'] == '1') ? 'checked' : '' ?>>
                                <span class="slider-pill">
                                    <span class="status-text text-on"><?= __('active') ?></span>
                                    <span class="status-text text-off"><?= __('passive') ?></span>
                                </span>
                            </label>
                        </div>

                        <div class="template-section" style="margin-top: 30px;">
                            <label style="display: block; font-size: 13px; font-weight: 800; margin-bottom: 15px; color: var(--text-muted); text-transform: uppercase;">SMS ŞABLONLARINI DÜZENLE</label>
                            
                            <div class="placeholder-guide" style="background: #f8f9fe; border: 1px solid #e1e3f1; border-radius: 12px; padding: 15px; margin-bottom: 25px;">
                                <div style="font-size: 12px; font-weight: 700; margin-bottom: 10px; color: var(--primary);">Kullanılabilir Etiketler:</div>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                    <span class="tag sms-placeholder" style="cursor: pointer; background: white; border: 1px solid #ddd; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; transition: all 0.2s;" data-tag="{musteri_ad}">{musteri_ad}</span>
                                    <span class="tag sms-placeholder" style="cursor: pointer; background: white; border: 1px solid #ddd; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; transition: all 0.2s;" data-tag="{hizmet}">{hizmet}</span>
                                    <span class="tag sms-placeholder" style="cursor: pointer; background: white; border: 1px solid #ddd; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; transition: all 0.2s;" data-tag="{tarih}">{tarih}</span>
                                    <span class="tag sms-placeholder" style="cursor: pointer; background: white; border: 1px solid #ddd; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; transition: all 0.2s;" data-tag="{saat}">{saat}</span>
                                </div>
                            </div>

                            <div class="form-group full-width" style="margin-bottom: 25px;">
                                <label>Yeni Randevu Bildirim Şablonu</label>
                                <textarea name="sms_new_appointment_template" id="tpl_new" class="form-control sms-textarea" style="height: 100px; resize: none; padding: 15px; border-radius: 12px;"><?= htmlspecialchars($settings['sms_new_appointment_template'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label>Randevu Hatırlatma Şablonu</label>
                                <textarea name="sms_reminder_template" id="tpl_rem" class="form-control sms-textarea" style="height: 100px; resize: none; padding: 15px; border-radius: 12px;"><?= htmlspecialchars($settings['sms_reminder_template'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="action-footer">
                            <button type="submit" class="btn-premium">
                                <i class="fa-solid fa-check"></i> <?= __('save') ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Section -->
                <div class="settings-pane" id="security">
                    <div class="pane-header">
                        <h3><?= __('security_settings') ?></h3>
                        <p>Hesap güvenliğinizi ve sistem giriş anahtarını buradan yönetebilirsiniz.</p>
                    </div>

                    <!-- Giriş Anahtarı Formu -->
                    <form id="security-code-form" class="premium-form" style="margin-bottom: 50px; padding-bottom: 40px; border-bottom: 1px solid rgba(134, 135, 195, 0.08);">
                        <input type="hidden" name="form_context" value="security">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Giriş Anahtarı (Metin Doğrulama)</label>
                                <div class="input-wrapper">
                                    <input type="password" name="security_code" id="sett-sec" value="<?= htmlspecialchars($settings['security_code'] ?? '') ?>" class="form-control" required placeholder="Yeni Giriş Anahtarı Belirleyin">
                                    <i class="fa-solid fa-shield-halved input-icon"></i>
                                </div>
                                <small style="color: var(--text-muted); font-size: 11px; margin-top: 5px;">Giriş ekranında şifreye ek olarak istenecek güvenlik kodudur.</small>
                            </div>
                        </div>
                        <div class="action-footer" style="margin-top: 15px; border: none; padding: 0;">
                            <button type="submit" class="btn-premium" style="height: 45px; padding: 0 25px;">
                                <i class="fa-solid fa-shield-check"></i> Anahtarı Güncelle
                            </button>
                        </div>
                    </form>

                    <!-- Şifre Değiştirme Formu -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 800; margin-bottom: 15px; color: var(--text-muted); text-transform: uppercase;">Şifre Değiştir</label>
                    </div>
                    <form id="password-change-form" class="premium-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label><?= __('current_password') ?></label>
                                <div class="input-wrapper">
                                    <input type="password" name="current_password" id="sett-curr-pass" class="form-control" required>
                                    <i class="fa-solid fa-eye-slash toggle-password" data-target="sett-curr-pass"></i>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= __('new_password') ?></label>
                                <div class="input-wrapper">
                                    <input type="password" name="new_password" id="sett-new-pass" class="form-control" required>
                                    <i class="fa-solid fa-eye-slash toggle-password" data-target="sett-new-pass"></i>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= __('confirm_password') ?></label>
                                <div class="input-wrapper">
                                    <input type="password" name="confirm_password" id="sett-conf-pass" class="form-control" required>
                                    <i class="fa-solid fa-eye-slash toggle-password" data-target="sett-conf-pass"></i>
                                </div>
                            </div>
                        </div>
                        <div class="action-footer">
                            <button type="submit" class="btn-premium">
                                <i class="fa-solid fa-key"></i> <?= __('change_password') ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Appointment Categories Section -->
                <?php $categories_list_data = $db->query("SELECT * FROM appointment_categories WHERE is_deleted = 0 ORDER BY id ASC")->fetchAll(); ?>
                <div class="settings-pane" id="categories">
                    <div class="pane-header">
                        <h3>Randevu Türleri & Renkler</h3>
                        <p>Randevularınızı türlerine göre gruplandırın ve takvimde renkli görünmesini sağlayın.</p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 40px;">
                        <!-- Kategori Listesi -->
                        <div class="category-list-wrapper">
                            <label style="display: block; font-size: 13px; font-weight: 800; margin-bottom: 20px; color: var(--text-muted); text-transform: uppercase;">Mevcut Türler</label>
                            <div class="premium-table-wrapper" style="background: rgba(134, 135, 195, 0.02); border-radius: 16px; border: 1px solid rgba(134, 135, 195, 0.08); overflow: hidden;">
                                <table class="premium-table" style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background: rgba(134, 135, 195, 0.05); text-align: left;">
                                            <th style="padding: 15px 20px; font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Renk</th>
                                            <th style="padding: 15px 20px; font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Tür Adı</th>
                                            <th style="padding: 15px 20px; font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; text-align: right;">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($categories_list_data as $cat): ?>
                                        <tr style="border-bottom: 1px solid rgba(134, 135, 195, 0.05);">
                                            <td style="padding: 15px 20px;">
                                                <div style="width: 24px; height: 24px; border-radius: 50%; background: <?= $cat['color'] ?>; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"></div>
                                            </td>
                                            <td style="padding: 15px 20px; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($cat['name']) ?></td>
                                            <td style="padding: 15px 20px; text-align: right;">
                                                <button onclick="deleteCategory(<?= $cat['id'] ?>)" class="btn-icon" style="color: var(--danger); background: none; border: none; cursor: pointer; font-size: 16px;">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if(empty($categories_list_data)): ?>
                                        <tr>
                                            <td colspan="3" style="padding: 40px; text-align: center; color: var(--text-muted); font-size: 14px;">Henüz bir tür tanımlanmamış.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Yeni Ekleme Formu -->
                        <div class="category-add-wrapper">
                            <label style="display: block; font-size: 13px; font-weight: 800; margin-bottom: 20px; color: var(--text-muted); text-transform: uppercase;">Yeni Tür Ekle</label>
                            <form id="category-add-form" class="premium-form" style="background: white; padding: 25px; border-radius: 20px; border: 1px solid rgba(134, 135, 195, 0.1); box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                                <div class="form-group full-width">
                                    <label>Tür Adı</label>
                                    <input type="text" name="name" class="form-control" placeholder="Örn: Değerlendirme" required>
                                </div>
                                <div class="form-group full-width" style="margin-top: 20px;">
                                    <label>Tür Rengi</label>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <input type="color" name="color" value="#A5A6D8" class="form-control" style="width: 60px; height: 50px; padding: 5px; cursor: pointer;">
                                        <span style="font-size: 13px; color: var(--text-muted);">Takvimde bu renkle görünecektir.</span>
                                    </div>
                                </div>
                                <button type="submit" class="btn-premium" style="width: 100%; margin-top: 30px; height: 50px;">
                                    <i class="fa-solid fa-plus"></i> Ekle
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Takvim Global Görünüm Ayarları -->
                    <div style="margin-top: 60px; padding-top: 40px; border-top: 2px dashed rgba(134, 135, 195, 0.1);">
                        <div class="pane-header" style="margin-bottom: 30px;">
                            <h3 style="font-size: 18px;"><i class="fa-solid fa-palette" style="margin-right: 10px; color: var(--primary);"></i> Takvim Global Görünüm Ayarları</h3>
                            <p style="font-size: 13px;">Takvimin genelinde geçerli olan durum renklerini ve sürelerini buradan yönetin.</p>
                        </div>

                        <form id="global-appearance-form" class="premium-form" style="background: rgba(134, 135, 195, 0.02); padding: 30px; border-radius: 20px; border: 1px solid rgba(134, 135, 195, 0.08);">
                            <input type="hidden" name="form_context" value="global_appearance">
                            
                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                <div class="form-group full-width">
                                    <label>İptal Görünme Süresi (Saat)</label>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <input type="number" name="cancelled_retention_hours" value="<?= htmlspecialchars($settings['cancelled_retention_hours'] ?? '24') ?>" class="form-control" style="width: 100px;" min="0" max="720">
                                        <span style="font-size: 12px; color: var(--text-muted);">Takvimde kalma süresi.</span>
                                    </div>
                                    <p style="font-size: 10px; color: var(--primary); margin-top: 5px; opacity: 0.8;">
                                        <i class="fa-solid fa-circle-info"></i> Örn: 1 hafta = 168 saat.
                                    </p>
                                </div>
                                <div class="form-group full-width">
                                    <label>İptal Randevu Rengi</label>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <input type="color" name="cancelled_color" value="<?= htmlspecialchars($settings['cancelled_color'] ?? '#FF4B5C') ?>" class="form-control" style="width: 55px; height: 45px; padding: 5px; cursor: pointer;">
                                        <span style="font-size: 12px; color: var(--text-muted);">İptal olanların takvim rengi.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row" style="margin-top: 20px;">
                                <div class="form-group full-width">
                                    <label>Çoklu İşlem / Çakışma Rengi</label>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <input type="color" name="multiple_services_color" value="<?= htmlspecialchars($settings['multiple_services_color'] ?? '#111111') ?>" class="form-control" style="width: 55px; height: 45px; padding: 5px; cursor: pointer;">
                                        <div style="display: flex; flex-direction: column; gap: 3px;">
                                            <span style="font-size: 12px; color: var(--text-muted);">Birden fazla hizmet içeren randevuların rengi.</span>
                                            <span style="font-size: 10px; color: #111111; font-weight: 700;">* Varsayılan siyah önerilir.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="action-footer" style="margin-top: 30px; border-top-color: rgba(134, 135, 195, 0.1);">
                                <button type="submit" class="btn-premium">
                                    <i class="fa-solid fa-save"></i> Ayarları Uygula
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Settings Modern Styles */
.settings-grid {
    display: flex;
    gap: 30px;
}

.settings-sidebar {
    width: 280px;
    flex-shrink: 0;
}

.settings-main {
    flex: 1;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.03);
    border: 1px solid rgba(134, 135, 195, 0.08);
    overflow: hidden;
}

.settings-menu {
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: sticky;
    top: 20px;
}

.settings-menu-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 25px;
    background: white;
    border: 1px solid rgba(134, 135, 195, 0.08);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    color: var(--text-muted);
}

.settings-menu-item i {
    font-size: 20px;
}

.settings-menu-item span {
    font-size: 15px;
    font-weight: 700;
}

.settings-menu-item:hover {
    background: rgba(134, 135, 195, 0.03);
    color: var(--primary);
    transform: translateX(5px);
}

.settings-menu-item.active {
    background: var(--primary);
    color: white;
    box-shadow: 0 10px 20px rgba(134, 135, 195, 0.2);
    border-color: var(--primary);
}

.settings-pane {
    display: none;
    padding: 40px;
}

.settings-pane.active {
    display: block;
    animation: paneIn 0.4s ease-out;
}

@keyframes paneIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.pane-header {
    margin-bottom: 40px;
}

.pane-header h3 {
    font-size: 24px;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 8px;
}

.pane-header p {
    color: var(--text-muted);
    font-size: 15px;
}

/* Premium Form Elements */
.premium-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 400px;
}

.form-group.full-width { max-width: 100%; }
.form-group.half-width { max-width: 50%; }

.form-group label {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
}

.logo-section {
    margin: 20px 0;
}

.logo-section label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    margin-bottom: 15px;
}

.logo-upload-box {
    display: flex;
    align-items: center;
    gap: 30px;
    background: rgba(134, 135, 195, 0.03);
    padding: 25px;
    border-radius: 16px;
    border: 1px dashed rgba(134, 135, 195, 0.2);
}

.preview-area {
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.preview-area img {
    max-width: 100%;
    max-height: 100%;
}

.logo-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    color: var(--text-muted);
}

.logo-empty i { font-size: 24px; }
.logo-empty span { font-size: 11px; font-weight: 700; }

.upload-controls {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.file-hint {
    font-size: 12px;
    color: var(--text-muted);
}

.toggle-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px;
    background: rgba(134, 135, 195, 0.03);
    border-radius: 16px;
    margin-top: 10px;
}

.toggle-info strong {
    display: block;
    font-size: 15px;
    margin-bottom: 4px;
}

.toggle-info p {
    font-size: 13px;
    color: var(--text-muted);
}

.action-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 30px;
    border-top: 1px solid rgba(134, 135, 195, 0.08);
}

@media (max-width: 900px) {
    .settings-grid { flex-direction: column; }
    .settings-sidebar { width: 100%; }
    .settings-menu { flex-direction: column !important; gap: 10px; }
    .settings-menu-item { width: 100%; justify-content: center; }
    .action-footer { flex-direction: column !important; gap: 12px; }
    .action-footer .btn-premium { width: 100% !important; justify-content: center !important; padding: 15px !important; }
    .settings-pane { padding: 25px 15px; }
    .toggle-card { flex-direction: column !important; align-items: flex-start !important; gap: 20px !important; }
}
</style>

<script>
$(document).ready(function() {
    // Tab Change
    $('.settings-menu-item').on('click', function() {
        const tabId = $(this).data('tab');
        $('.settings-menu-item').removeClass('active');
        $(this).addClass('active');
        $('.settings-pane').removeClass('active');
        $('#' + tabId).addClass('active');
    });

    // Logo Preview
    $('#clinic_logo_input').on('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#logo-preview-img').attr('src', e.target.result).show();
                $('#logo-empty-state').hide();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Password Show/Hide Toggle (Reversed per user request)
    $('.toggle-password').on('click', function() {
        const targetId = $(this).data('target');
        const $input = $('#' + targetId);
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            $input.attr('type', 'password');
            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });

    // Form Submissions (Gelişmiş Seçici)
    $('#general-settings-form, #sms-settings-form, #security-code-form, #global-appearance-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const formData = new FormData(this);
        
        $.ajax({
            url: '../actions/settings_update.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === 'success') {
                    showToast('Başarılı', res.message, 'success');
                } else {
                    showToast('Hata', res.message, 'error');
                }
            }
        });
    });

    $('#password-change-form').on('submit', function(e) {
        e.preventDefault();
        $.post('../actions/password_change.php', $(this).serialize(), function(res) {
            if (res.status === 'success') {
                showToast('Başarılı', res.message, 'success');
                $('#password-change-form')[0].reset();
            } else {
                showToast('Hata', res.message, 'error');
            }
        });
    });

    // SMS Placeholder Click Logic
    let lastFocusedTextarea = $('#tpl_new')[0];
    $('.sms-textarea').on('focus', function() {
        lastFocusedTextarea = this;
    });

    $('.sms-placeholder').on('click', function() {
        const tag = $(this).data('tag');
        if (!lastFocusedTextarea) return;
        
        const field = lastFocusedTextarea;
        const startPos = field.selectionStart;
        const endPos = field.selectionEnd;
        const text = field.value;
        
        field.value = text.substring(0, startPos) + tag + text.substring(endPos, text.length);
        field.selectionStart = field.selectionEnd = startPos + tag.length;
        field.focus();
    });

    $('.sms-placeholder').hover(
        function() { $(this).css({'border-color': 'var(--primary)', 'color': 'var(--primary)', 'background': 'rgba(134, 135, 195, 0.05)'}); },
        function() { $(this).css({'border-color': '#ddd', 'color': 'inherit', 'background': 'white'}); }
    );
    // Kategori Ekleme
    $('#category-add-form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=add';
        $.post('../actions/category_management.php', formData, function(res) {
            if (res.status === 'success') {
                showToast('Başarılı', res.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Hata', res.message, 'error');
            }
        });
    });
});

function deleteCategory(id) {
    if (!confirm('Bu kategoriyi silmek istediğinize emin misiniz? (Mevcut randevulardan silinmez, sadece yeni seçimlerde görünmez)')) return;
    $.post('../actions/category_management.php', { id: id, action: 'delete' }, function(res) {
        if (res.status === 'success') {
            showToast('Silindi', res.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
