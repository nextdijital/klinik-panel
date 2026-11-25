<?php
require_once '../functions.php';
check_login();

// Sadece admin erişebilir - blur overlay değil, hard redirect
if (!is_admin()) {
    header('Location: dashboard.php');
    exit();
}

define('LOCKED_USERNAME', 'yarenipek');

// Tüm kullanıcıları çek
$users = $db->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

// İzin verilebilecek sayfalar
$pages_list = [
    'dashboard'    => ['label' => 'Dashboard',  'icon' => 'fa-house'],
    'appointments' => ['label' => 'Randevular',  'icon' => 'fa-calendar-alt'],
    'customers'    => ['label' => 'Müşteriler',  'icon' => 'fa-users'],
    'services'     => ['label' => 'Hizmetler',   'icon' => 'fa-hand-holding-medical'],
    'staff'        => ['label' => 'Personel',    'icon' => 'fa-user-tie'],
    'finance'      => ['label' => 'Finans',      'icon' => 'fa-wallet'],
    'settings'     => ['label' => 'Ayarlar',     'icon' => 'fa-cog'],
];

include '../includes/header.php';
?>

<style>
/* =============================================
   Kullanıcı Yönetimi — Sayfa Stilleri
   ============================================= */
.users-page-wrapper {
    padding: 28px 32px;
}
.users-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
    gap: 16px;
}
.users-page-header h1 {
    font-size: 22px;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
    letter-spacing: -0.5px;
}
.users-page-header p {
    margin: 4px 0 0;
    color: var(--text-muted);
    font-size: 13px;
}
.btn-add-user {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: linear-gradient(135deg, var(--primary), #8384c0);
    color: white;
    border: none;
    border-radius: 14px;
    padding: 13px 24px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(165,166,216,0.35);
    transition: all 0.3s cubic-bezier(.4,0,.2,1);
    white-space: nowrap;
}
.btn-add-user:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(165,166,216,0.45);
}

/* Kullanıcı Kartları Grid */
.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
}
.user-card {
    background: #ffffff;
    border-radius: 22px;
    border: 1px solid rgba(165,166,216,0.12);
    padding: 28px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    transition: all 0.3s cubic-bezier(.4,0,.2,1);
    position: relative;
}
.user-card:hover {
    box-shadow: 0 8px 32px rgba(165,166,216,0.18);
    transform: translateY(-2px);
}
.user-card-top {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 20px;
}
.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(165,166,216,0.18), rgba(165,166,216,0.06));
    border: 2px solid rgba(165,166,216,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 800;
    color: var(--primary);
    flex-shrink: 0;
}
.user-info { flex: 1; min-width: 0; }
.user-info h3 {
    margin: 0 0 4px;
    font-size: 16px;
    font-weight: 800;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-info span.username {
    font-size: 13px;
    color: var(--text-muted);
    font-weight: 500;
}
.role-badge {
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.role-badge.admin {
    background: rgba(165,166,216,0.12);
    color: #6869aa;
    border: 1px solid rgba(165,166,216,0.25);
}
.role-badge.staff {
    background: rgba(190,242,100,0.12);
    color: #74b816;
    border: 1px solid rgba(190,242,100,0.25);
}
/* Kilit rozeti — artık inline (kart-top'un sağ sütununda) */
.lock-badge-inline {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(251,113,133,0.1);
    border: 1px solid rgba(251,113,133,0.18);
    border-radius: 8px;
    padding: 4px 10px;
    color: #f43f5e;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
.lock-badge-inline i { font-size: 11px; }
/* Sağ sütun: rol + kilit rozeti */
.user-card-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    flex-shrink: 0;
}

/* Sayfa İzinleri Kısmı */
.user-perms-section {
    border-top: 1px solid rgba(165,166,216,0.1);
    padding-top: 18px;
    margin-bottom: 20px;
}
.user-perms-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-muted);
    margin-bottom: 10px;
    display: block;
}
.perms-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.perm-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(165,166,216,0.08);
    border: 1px solid rgba(165,166,216,0.15);
    color: #6869aa;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 700;
}
.perm-pill i { font-size: 10px; }
.perm-pill.all-access {
    background: rgba(190,242,100,0.1);
    border-color: rgba(190,242,100,0.25);
    color: #74b816;
}
.perm-pill.no-access {
    background: rgba(251,113,133,0.08);
    border-color: rgba(251,113,133,0.2);
    color: #f43f5e;
}

/* Kart Alt Butonları */
.user-card-actions {
    display: flex;
    gap: 10px;
}
.btn-card-edit, .btn-card-delete {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border-radius: 12px;
    padding: 11px 16px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: all 0.25s;
}
.btn-card-edit {
    background: rgba(165,166,216,0.1);
    color: #6869aa;
    border: 1px solid rgba(165,166,216,0.2);
}
.btn-card-edit:hover { background: var(--primary); color: white; }
.btn-card-delete {
    background: rgba(251,113,133,0.08);
    color: #f43f5e;
    border: 1px solid rgba(251,113,133,0.15);
}
.btn-card-delete:hover { background: #f43f5e; color: white; }
.btn-card-delete:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.btn-card-delete:disabled:hover { background: rgba(251,113,133,0.08); color: #f43f5e; }

/* Modal İçi İzin Seçici Grid */
.perm-selector-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-top: 8px;
}
.perm-selector-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    border: 2px solid transparent;
    border-radius: 14px;
    padding: 13px 16px;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.perm-selector-item:hover {
    border-color: rgba(165,166,216,0.3);
    background: rgba(165,166,216,0.06);
}
.perm-selector-item.selected {
    border-color: var(--primary);
    background: rgba(165,166,216,0.1);
}
.perm-selector-item .perm-icon {
    width: 36px;
    height: 36px;
    background: rgba(165,166,216,0.12);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 15px;
    flex-shrink: 0;
    transition: all 0.2s;
}
.perm-selector-item.selected .perm-icon {
    background: var(--primary);
    color: white;
}
.perm-selector-item .perm-text {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-main);
}
.perm-check-icon {
    margin-left: auto;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}
.perm-selector-item.selected .perm-check-icon {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}
.perm-check-icon i { font-size: 11px; }

/* Select All / None butonları */
.perm-bulk-btns {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}
.perm-bulk-btn {
    font-size: 12px;
    font-weight: 700;
    color: var(--primary);
    background: rgba(165,166,216,0.1);
    border: 1px solid rgba(165,166,216,0.2);
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
    transition: all 0.2s;
}
.perm-bulk-btn:hover { background: var(--primary); color: white; }

.role-warning {
    display: none;
    background: rgba(165,166,216,0.08);
    border: 1px solid rgba(165,166,216,0.2);
    border-radius: 12px;
    padding: 14px 18px;
    font-size: 13px;
    color: #6869aa;
    margin-top: 10px;
    align-items: center;
    gap: 10px;
}
.modal-section-label {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-muted);
    margin: 20px 0 8px;
    display: block;
}
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 6px;
}
.form-group .form-input {
    width: 100%;
    border: 1.5px solid rgba(165,166,216,0.2);
    border-radius: 12px;
    padding: 13px 16px;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    color: var(--text-main);
    background: #f8fafc;
    box-sizing: border-box;
    transition: border-color 0.2s;
    outline: none;
}
.form-group .form-input:focus { border-color: var(--primary); background: white; }

/* Select — native dropdown sisteme uygun stillendirildi */
select.form-input {
    appearance: none;
    -webkit-appearance: none;
    background-color: #f8fafc;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%238384c0' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 16px;
    padding-right: 42px;
    cursor: pointer;
    border: 1.5px solid rgba(165,166,216,0.2);
    border-radius: 12px;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    color: var(--text-main);
}
select.form-input:focus {
    border-color: var(--primary);
    background-color: white;
}

/* Modal Footer Butonları - Sistemle Birebir Uyumlu */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
    padding: 20px 28px;
    border-top: 1px solid rgba(165,166,216,0.12);
    margin-top: 10px;
}
.modal-footer .btn-secondary {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: 12px 24px;
    border-radius: 14px;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
}
.modal-footer .btn-secondary:hover {
    background: #e2e8f0;
    color: #334155;
}
.modal-footer .btn-premium {
    background: linear-gradient(135deg, var(--primary), #8384c0);
    color: white;
    border: none;
    padding: 12px 26px;
    border-radius: 14px;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 16px rgba(165,166,216,0.35);
    transition: all 0.25s cubic-bezier(.4,0,.2,1);
}
.modal-footer .btn-premium:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(165,166,216,0.45);
}

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

/* Responsive */
@media(max-width: 640px) {
    .users-page-wrapper { padding: 16px; }
    .users-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .perm-selector-grid { grid-template-columns: 1fr; }
}
</style>

<main class="main-content">
<div class="users-page-wrapper">

    <!-- Sayfa Başlığı -->
    <div class="users-page-header">
        <div>
            <h1><i class="fa-solid fa-users-gear" style="color:var(--primary);margin-right:10px;"></i>Kullanıcı Yönetimi</h1>
            <p>Sistem kullanıcılarını ve sayfa erişim izinlerini buradan yönetebilirsiniz.</p>
        </div>
        <button class="btn-add-user" onclick="openAddUserModal()">
            <i class="fa-solid fa-user-plus"></i> Yeni Kullanıcı
        </button>
    </div>

    <!-- Kullanıcı Kartları -->
    <div class="users-grid" id="usersGrid">
        <?php foreach ($users as $u):
            $is_locked = ($u['username'] === LOCKED_USERNAME);
            $is_admin_user = ($u['role'] === 'admin');
            $perms_arr = ($u['permissions'] && !$is_admin_user) ? json_decode($u['permissions'], true) : [];
            $initial = strtoupper(mb_substr($u['full_name'], 0, 1));
        ?>
        <div class="user-card" id="userCard<?= $u['id'] ?>">

            <div class="user-card-top">
                <div class="user-avatar"><?= $initial ?></div>
                <div class="user-info">
                    <h3><?= htmlspecialchars($u['full_name']) ?></h3>
                    <span class="username">@<?= htmlspecialchars($u['username']) ?></span>
                </div>
                <div class="user-card-right">
                    <span class="role-badge <?= $u['role'] ?>"><?= $u['role'] === 'admin' ? 'Yönetici' : 'Personel' ?></span>
                    <?php if ($is_locked): ?>
                    <span class="lock-badge-inline" title="Bu kullanıcı silinemez">
                        <i class="fa-solid fa-lock"></i> Kilitli
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="user-perms-section">
                <span class="user-perms-label"><i class="fa-solid fa-shield-halved" style="margin-right:5px;"></i>Sayfa Erişimleri</span>
                <div class="perms-pills">
                    <?php if ($is_admin_user): ?>
                        <span class="perm-pill all-access"><i class="fa-solid fa-circle-check"></i> Tüm Sayfalara Erişim</span>
                    <?php elseif (empty($perms_arr)): ?>
                        <span class="perm-pill no-access"><i class="fa-solid fa-ban"></i> Erişim Yok</span>
                    <?php else: ?>
                        <?php foreach ($perms_arr as $perm_key):
                            if (isset($pages_list[$perm_key])): ?>
                        <span class="perm-pill">
                            <i class="fa-solid <?= $pages_list[$perm_key]['icon'] ?>"></i>
                            <?= $pages_list[$perm_key]['label'] ?>
                        </span>
                        <?php endif; endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="user-card-actions">
                <button class="btn-card-edit" onclick="openEditUserModal(<?= $u['id'] ?>)">
                    <i class="fa-solid fa-pen"></i> Düzenle
                </button>
                <button class="btn-card-delete"
                    onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>')"
                    <?= $is_locked ? 'disabled title="Sistem yöneticisi silinemez"' : '' ?>>
                    <i class="fa-solid fa-trash"></i> Sil
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
</main>

<!-- =============================================
     KULLANICI EKLE MODALİ
     ============================================= -->
<div id="addUserModal" class="modal-overlay" style="display:none;">
    <div class="modal-container modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-user-plus" style="margin-right:8px;color:var(--primary);"></i>Yeni Kullanıcı Ekle</h3>
            <button class="modal-close" onclick="closeAddUserModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-content" style="max-height:75vh; overflow-y:auto;">
            <div class="form-row">
                <div class="form-group">
                    <label>Ad Soyad</label>
                    <input type="text" id="add_full_name" class="form-input" placeholder="Ahmet Yılmaz">
                </div>
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <input type="text" id="add_username" class="form-input" placeholder="ahmetyilmaz">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Şifre</label>
                    <input type="password" id="add_password" class="form-input" placeholder="En az 6 karakter">
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select id="add_role" class="form-control form-input" onchange="togglePermSection('add')">
                        <option value="staff">Personel</option>
                        <option value="admin">Yönetici</option>
                    </select>
                </div>
            </div>

            <div id="add_perm_section">
                <span class="modal-section-label"><i class="fa-solid fa-shield-halved" style="margin-right:6px;"></i>Sayfa Erişim İzinleri</span>
                <div class="perm-bulk-btns">
                    <button type="button" class="perm-bulk-btn" onclick="selectAllPerms('add')"><i class="fa-solid fa-check-double"></i> Tümünü Seç</button>
                    <button type="button" class="perm-bulk-btn" onclick="clearAllPerms('add')"><i class="fa-solid fa-xmark"></i> Temizle</button>
                </div>
                <div class="perm-selector-grid" id="add_perm_grid">
                    <?php foreach ($pages_list as $key => $pg): ?>
                    <div class="perm-selector-item" data-key="<?= $key ?>" onclick="togglePerm(this)">
                        <div class="perm-icon"><i class="fa-solid <?= $pg['icon'] ?>"></i></div>
                        <span class="perm-text"><?= $pg['label'] ?></span>
                        <div class="perm-check-icon"><i class="fa-solid fa-check"></i></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="role-warning" id="add_role_warning">
                <i class="fa-solid fa-circle-info" style="font-size:18px;color:var(--primary);"></i>
                Yönetici rolündeki kullanıcılar tüm sayfalara otomatik olarak tam erişime sahip olur.
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeAddUserModal()">İptal</button>
            <button type="button" class="btn-premium" onclick="submitAddUser()">
                <i class="fa-solid fa-user-plus"></i> Kullanıcı Oluştur
            </button>
        </div>
    </div>
</div>

<!-- =============================================
     KULLANICI DÜZENLE MODALİ
     ============================================= -->
<div id="editUserModal" class="modal-overlay" style="display:none;">
    <div class="modal-container modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-pen" style="margin-right:8px;color:var(--primary);"></i>Kullanıcı Düzenle</h3>
            <button class="modal-close" onclick="closeEditUserModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-content" style="max-height:75vh; overflow-y:auto;">
            <input type="hidden" id="edit_user_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Ad Soyad</label>
                    <input type="text" id="edit_full_name" class="form-input" placeholder="Ad Soyad">
                </div>
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <input type="text" id="edit_username" class="form-input" placeholder="kullanici_adi">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Yeni Şifre <small style="font-weight:400;color:var(--text-muted);">(boş bırakırsan değişmez)</small></label>
                    <input type="password" id="edit_password" class="form-input" placeholder="Değiştirmek için gir">
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select id="edit_role" class="form-control form-input" onchange="togglePermSection('edit')">
                        <option value="staff">Personel</option>
                        <option value="admin">Yönetici</option>
                    </select>
                </div>
            </div>

            <div id="edit_perm_section">
                <span class="modal-section-label"><i class="fa-solid fa-shield-halved" style="margin-right:6px;"></i>Sayfa Erişim İzinleri</span>
                <div class="perm-bulk-btns">
                    <button type="button" class="perm-bulk-btn" onclick="selectAllPerms('edit')"><i class="fa-solid fa-check-double"></i> Tümünü Seç</button>
                    <button type="button" class="perm-bulk-btn" onclick="clearAllPerms('edit')"><i class="fa-solid fa-xmark"></i> Temizle</button>
                </div>
                <div class="perm-selector-grid" id="edit_perm_grid">
                    <?php foreach ($pages_list as $key => $pg): ?>
                    <div class="perm-selector-item" data-key="<?= $key ?>" onclick="togglePerm(this)">
                        <div class="perm-icon"><i class="fa-solid <?= $pg['icon'] ?>"></i></div>
                        <span class="perm-text"><?= $pg['label'] ?></span>
                        <div class="perm-check-icon"><i class="fa-solid fa-check"></i></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="role-warning" id="edit_role_warning">
                <i class="fa-solid fa-circle-info" style="font-size:18px;color:var(--primary);"></i>
                Yönetici rolündeki kullanıcılar tüm sayfalara otomatik olarak tam erişime sahip olur.
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeEditUserModal()">İptal</button>
            <button type="button" class="btn-premium" onclick="submitEditUser()">
                <i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet
            </button>
        </div>
    </div>
</div>

<!-- =============================================
     JAVASCRIPT
     ============================================= -->
<script>
const LOCKED_USERNAME = '<?= LOCKED_USERNAME ?>';

/* ---------- İzin Selector Helpers ---------- */
function togglePerm(el) {
    el.classList.toggle('selected');
}
function selectAllPerms(prefix) {
    document.querySelectorAll(`#${prefix}_perm_grid .perm-selector-item`).forEach(el => el.classList.add('selected'));
}
function clearAllPerms(prefix) {
    document.querySelectorAll(`#${prefix}_perm_grid .perm-selector-item`).forEach(el => el.classList.remove('selected'));
}
function getSelectedPerms(prefix) {
    const selected = [];
    document.querySelectorAll(`#${prefix}_perm_grid .perm-selector-item.selected`).forEach(el => {
        selected.push(el.dataset.key);
    });
    return selected;
}
function setSelectedPerms(prefix, permsArr) {
    document.querySelectorAll(`#${prefix}_perm_grid .perm-selector-item`).forEach(el => {
        el.classList.toggle('selected', permsArr.includes(el.dataset.key));
    });
}
function togglePermSection(prefix) {
    const role = document.getElementById(`${prefix}_role`).value;
    const isAdmin = role === 'admin';
    const section = document.getElementById(`${prefix}_perm_section`);
    const warning = document.getElementById(`${prefix}_role_warning`);

    if (section) {
        section.style.opacity = isAdmin ? '0.4' : '1';
        section.style.pointerEvents = isAdmin ? 'none' : 'auto';
    }
    if (warning) warning.style.display = isAdmin ? 'flex' : 'none';
}

/* ---------- EKLE MODAL ---------- */
function openAddUserModal() {
    document.getElementById('add_full_name').value = '';
    document.getElementById('add_username').value = '';
    document.getElementById('add_password').value = '';
    document.getElementById('add_role').value = 'staff';
    clearAllPerms('add');
    togglePermSection('add');
    document.getElementById('addUserModal').style.display = 'flex';
    setTimeout(() => initPremiumSelects('#addUserModal'), 50);
}
function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
}
function submitAddUser() {
    const full_name   = document.getElementById('add_full_name').value.trim();
    const username    = document.getElementById('add_username').value.trim();
    const password    = document.getElementById('add_password').value;
    const role        = document.getElementById('add_role').value;
    const permissions = getSelectedPerms('add');

    if (!full_name || !username || !password) {
        showToast('Uyarı', 'Ad soyad, kullanıcı adı ve şifre zorunludur.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_user');
    formData.append('full_name', full_name);
    formData.append('username', username);
    formData.append('password', password);
    formData.append('role', role);
    permissions.forEach(p => formData.append('permissions[]', p));

    $.ajax({
        url: '../actions/user_management.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.status === 'success') {
                showToast('Başarılı', res.message, 'success');
                closeAddUserModal();
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Hata', res.message, 'error');
            }
        },
        error: function() { showToast('Hata', 'Sunucu hatası.', 'error'); }
    });
}

/* ---------- DÜZENLE MODAL ---------- */
function openEditUserModal(userId) {
    $.ajax({
        url: '../actions/user_management.php',
        type: 'POST',
        data: { action: 'get_user', user_id: userId },
        success: function(res) {
            if (res.status !== 'success') { showToast('Hata', res.message, 'error'); return; }
            const u = res.data;
            document.getElementById('edit_user_id').value   = u.id;
            document.getElementById('edit_full_name').value = u.full_name;
            document.getElementById('edit_password').value  = '';

            // Kilitli kullanıcı için username ve role alanlarını disable et
            const isLocked = (u.username === LOCKED_USERNAME);
            const usernameInput = document.getElementById('edit_username');
            const roleSelect    = document.getElementById('edit_role');
            usernameInput.value    = u.username;
            usernameInput.readOnly = isLocked;
            usernameInput.style.opacity = isLocked ? '0.5' : '1';
            roleSelect.value    = u.role;
            roleSelect.disabled = isLocked;

            setSelectedPerms('edit', u.permissions_arr || []);
            togglePermSection('edit');
            document.getElementById('editUserModal').style.display = 'flex';
            setTimeout(() => initPremiumSelects('#editUserModal'), 50);
        },
        error: function() { showToast('Hata', 'Kullanıcı verisi alınamadı.', 'error'); }
    });
}
function closeEditUserModal() {
    document.getElementById('editUserModal').style.display = 'none';
    document.getElementById('edit_role').disabled = false;
}
function submitEditUser() {
    const user_id     = document.getElementById('edit_user_id').value;
    const full_name   = document.getElementById('edit_full_name').value.trim();
    const username    = document.getElementById('edit_username').value.trim();
    const password    = document.getElementById('edit_password').value;
    const role        = document.getElementById('edit_role').value;
    const permissions = getSelectedPerms('edit');

    if (!full_name || !username) {
        showToast('Uyarı', 'Ad soyad ve kullanıcı adı zorunludur.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_user');
    formData.append('user_id', user_id);
    formData.append('full_name', full_name);
    formData.append('username', username);
    formData.append('password', password);
    formData.append('role', role);
    permissions.forEach(p => formData.append('permissions[]', p));

    $.ajax({
        url: '../actions/user_management.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.status === 'success') {
                showToast('Başarılı', res.message, 'success');
                closeEditUserModal();
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Hata', res.message, 'error');
            }
        },
        error: function() { showToast('Hata', 'Sunucu hatası.', 'error'); }
    });
}

/* ---------- SİL ---------- */
function confirmDeleteUser(userId, userName) {
    const body = `<div style="text-align:center;padding:20px 10px;">
        <div style="width:70px;height:70px;background:rgba(251,113,133,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <i class="fa-solid fa-trash" style="font-size:28px;color:#f43f5e;"></i>
        </div>
        <p style="margin:0;font-size:15px;color:#64748b;line-height:1.6;">
            <strong style="color:#1e1e2e;">${userName}</strong> adlı kullanıcıyı silmek istediğinize emin misiniz?<br>
            Bu işlem geri alınamaz.
        </p>
    </div>`;
    const footer = `
        <button class="btn-secondary" onclick="closeGlobalModal()">İptal</button>
        <button class="btn-danger" onclick="doDeleteUser(${userId})">
            <i class="fa-solid fa-trash"></i> Evet, Sil
        </button>`;
    openGlobalModal('Kullanıcıyı Sil', body, footer);
}
function doDeleteUser(userId) {
    $.post('../actions/user_management.php', { action: 'delete_user', user_id: userId }, function(res) {
        if (res.status === 'success') {
            showToast('Başarılı', res.message, 'success');
            closeGlobalModal();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Hata', res.message, 'error');
        }
    });
}

/* Modal overlay dışına tıklayınca kapat */
document.getElementById('addUserModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddUserModal();
});
document.getElementById('editUserModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditUserModal();
});
</script>

<?php include '../includes/footer.php'; ?>
