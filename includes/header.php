<?php
require_once '../functions.php';
check_login();

// Header verilerini burada çekelim
$stmt_appts = $db->query("SELECT COUNT(*) FROM appointments a 
                            JOIN customers c ON a.customer_id = c.id 
                            JOIN services s ON a.service_id = s.id 
                            WHERE a.appointment_date = CURDATE() AND a.is_deleted = 0 AND c.is_deleted = 0 AND s.is_deleted = 0");
$today_appts_count = $stmt_appts->fetchColumn();

// Bugünün Geliri (Ciro)
$today_income = $db->query("SELECT SUM(amount) FROM finance WHERE type = 'income' AND DATE(created_at) = CURDATE()")->fetchColumn() ?? 0;

// Son 12 Saatlik Gelir
$last_12h_income = $db->query("SELECT SUM(amount) FROM finance WHERE type = 'income' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 HOUR)")->fetchColumn() ?? 0;

// Tüm Zamanlar Brüt Gelir (Ciro)
$total_income = $db->query("SELECT SUM(amount) FROM finance WHERE type = 'income'")->fetchColumn() ?? 0;

// Tüm Zamanlar Brüt Gider
$total_expense = $db->query("SELECT SUM(amount) FROM finance WHERE type = 'expense'")->fetchColumn() ?? 0;

// Net Kar
$net_profit = $total_income - $total_expense;

$is_finance_page = basename($_SERVER['PHP_SELF']) === 'finance.php';
$is_dashboard = basename($_SERVER['PHP_SELF']) === 'index.php';

// Global Ayarlar (Para Birimi vb.)
$currency = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'currency'")->fetchColumn() ?: '₺';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'tr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KlinikMod - <?= __('dashboard') ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/favicon.png">
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/premium_select.css?v=1.0.1">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/tr.js"></script>
    
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#A5A6D8">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const globalCurrency = '<?= $currency ?>';
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../sw.js').then(reg => {
                    console.log('SW Registered');
                }).catch(err => {
                    console.log('SW Failed', err);
                });
            });
        }

        function calculateStatusPriceGlobal() {
            const base = parseFloat($('#status_base_price_global').val()) || 0;
            const type = $('#status_discount_type_global').val();
            const val = parseFloat($('#status_discount_value_global').val()) || 0;
            
            let net = base;
            if (type === 'fixed') {
                net = base - val;
            } else if (type === 'percentage') {
                net = base - (base * (val / 100));
            }
            
            if (net < 0) net = 0;
            
            const currency = typeof globalCurrency !== 'undefined' ? globalCurrency : '₺';
            $('#status_net_price_display_global').text(net.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + currency);
            $('#status_final_price_global').val(net.toFixed(2));
        }

        $(document).ready(function() {
            // Global Expandable Description Handler
            $(document).on('click', '.expandable-description', function(e) {
                e.stopPropagation();
                const $this = $(this);
                const isExpanded = $this.hasClass('expanded');
                
                // Close all others
                $('.expandable-description').removeClass('expanded');
                
                if (!isExpanded) {
                    $this.addClass('expanded');
                }
            });

            // Close when clicking outside
            $(document).on('click', function() {
                $('.expandable-description').removeClass('expanded');
                $('.premium-select-wrapper').removeClass('active');
            });

            initPremiumSelects();
        });

        function initPremiumSelects(container = 'body') {
            $(container).find('select.form-control').each(function() {
                const $select = $(this);
                if ($select.hasClass('premium-select-native') || $select.closest('.premium-select-wrapper').length) return;

                const $wrapper = $('<div class="premium-select-wrapper"></div>');
                const $display = $('<div class="premium-select-display"></div>');
                const $list = $('<div class="premium-select-list custom-scroll"></div>');
                
                $select.addClass('premium-select-native').before($wrapper);
                $wrapper.append($select).append($display).append($list);

                // Arama Kutusu Ekle
                const $searchWrapper = $('<div class="premium-select-search-wrapper"></div>');
                const $searchContainer = $('<div class="premium-select-search-container"></div>');
                $searchContainer.append('<i class="fa-solid fa-magnifying-glass"></i>');
                const $searchInput = $('<input type="text" class="premium-select-search-input" placeholder="Ara...">');
                $searchContainer.append($searchInput);
                $searchWrapper.append($searchContainer);
                $list.append($searchWrapper);

                const $optionsContainer = $('<div class="premium-options-container"></div>');
                $list.append($optionsContainer);

                // Initial display
                const $selected = $select.find('option:selected');
                const selectedText = $selected.length && $selected.val() !== "" ? $selected.text() : ($select.find('option:first').text() || 'Seçiniz...');
                $display.text(selectedText);

                // Options
                function renderOptions() {
                    $optionsContainer.empty();
                    $select.find('option').each(function() {
                        const $opt = $(this);
                        const isSelected = $opt.is(':selected');
                        const val = $opt.val();
                        const text = $opt.text().trim();
                        
                        if (val === "" || text === "" || text.toLowerCase().includes('seçiniz')) return;
                        
                        let icon = 'fa-chevron-right'; 
                        const selectName = $select.attr('name') || '';
                        const optText = text.toLowerCase();

                        if (selectName.toLowerCase().indexOf('customer') !== -1) icon = 'fa-user';
                        else if (selectName.toLowerCase().indexOf('service') !== -1) icon = 'fa-stethoscope';
                        else if (selectName.toLowerCase().indexOf('staff') !== -1) icon = 'fa-user-doctor';
                        else if (optText.includes('gelir')) icon = 'fa-arrow-trend-up';
                        else if (optText.includes('gider')) icon = 'fa-arrow-trend-down';
                        else if (selectName.toLowerCase().indexOf('type') !== -1) icon = 'fa-tags';
                        
                        const $customOpt = $('<div class="premium-select-option"></div>')
                            .html(`<i class="fa-solid ${icon}"></i> <span>${text}</span>`)
                            .attr('data-value', val);
                        
                        if (isSelected) $customOpt.addClass('selected');
                        $optionsContainer.append($customOpt);
                    });
                }
                renderOptions();

                // Search Logic
                $searchInput.on('click', function(e) {
                    e.stopPropagation(); // Tıklayınca kapanmasını engelle
                });

                $searchInput.on('input', function(e) {
                    const rawVal = $(this).val();
                    const term = rawVal.toLocaleLowerCase('tr-TR').trim();
                    const termNoSpace = term.replace(/\s/g, '');
                    const tokens = term.split(/\s+/).filter(t => t.length > 0);
                    
                    let found = 0;
                    
                    $optionsContainer.find('.premium-select-option').each(function() {
                        const originalTxt = $(this).text();
                        const txt = originalTxt.toLocaleLowerCase('tr-TR');
                        const txtNoSpace = txt.replace(/\s/g, '');
                        
                        let isMatch = false;
                        
                        if (term === "") {
                            isMatch = true;
                        } else {
                            // Esnek Arama Mantığı:
                            // 1. Kelime bazlı arama (Tüm kelimeler geçiyor mu?)
                            let tokenMatch = tokens.length > 0 && tokens.every(token => txt.indexOf(token) > -1);
                            
                            // 2. Boşluksuz arama (ahmetcan -> Ahmet Can eşleşsin)
                            let noSpaceMatch = txtNoSpace.indexOf(termNoSpace) > -1;
                            
                            isMatch = tokenMatch || noSpaceMatch;
                        }

                        if (isMatch) {
                            $(this).removeClass('hidden');
                            found++;
                        } else {
                            $(this).addClass('hidden');
                        }
                    });

                    // No results message
                    $list.find('.no-results-msg').remove();
                    if (found === 0 && term !== "") {
                        $optionsContainer.append('<div class="no-results-msg">Sonuç bulunamadı</div>');
                    }
                });

                // Toggle
                $display.on('click', function(e) {
                    e.stopPropagation();
                    const isActive = $wrapper.hasClass('active');
                    $('.premium-select-wrapper').not($wrapper).removeClass('active');
                    $wrapper.toggleClass('active');
                    
                    if ($wrapper.hasClass('active')) {
                        setTimeout(() => $searchInput.focus(), 50);
                    } else {
                        $searchInput.val('').trigger('input');
                    }
                });

                // Choice
                $list.on('click', '.premium-select-option', function(e) {
                    e.stopPropagation();
                    const val = $(this).attr('data-value');
                    const text = $(this).text();
                    
                    $select.val(val).trigger('change');
                    $display.text(text);
                    
                    $list.find('.premium-select-option').removeClass('selected');
                    $(this).addClass('selected');
                    $wrapper.removeClass('active');
                    $searchInput.val('').trigger('input');
                });

                // Sync if native select changes externally
                $select.on('change', function() {
                    const val = $(this).val();
                    const text = $select.find('option:selected').text();
                    $display.text(text);
                    $list.find('.premium-select-option').removeClass('selected')
                        .filter(`[data-value="${val}"]`).addClass('selected');
                });
            });
        }

        // Standard Premium Toast System
        function showToast(title, message, type = 'info') {
            const container = $('#toast-container');
            if (container.length === 0) return; // container yoksa çık

            const icons = { 
                success: 'fa-circle-check', 
                error: 'fa-circle-xmark', 
                warning: 'fa-triangle-exclamation', 
                info: 'fa-circle-info' 
            };
            
            const toast = $(`
                <div class="toast toast-${type}">
                    <div class="toast-icon"><i class="fa-solid ${icons[type] || icons.info}"></i></div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close"><i class="fa-solid fa-xmark"></i></button>
                </div>
            `);
            
            container.append(toast);
            
            const timeout = setTimeout(() => removeToast(toast), 5000);
            
            toast.find('.toast-close').on('click', function() {
                clearTimeout(timeout);
                removeToast(toast);
            });
        }

        function removeToast(toast) {
            toast.addClass('hiding');
            setTimeout(() => { toast.remove(); }, 400);
        }

        // Universal Modal System
        function openGlobalModal(title, body, footer = '', sizeClass = '') {
            const $container = $('#globalModal .modal-container');
            $container.removeClass('modal-lg').addClass(sizeClass);
            
            $('#modalTitle').text(title);
            $('#modalBody').html(body);
            $('#modalFooter').html(footer);
            $('#globalModal').fadeIn(300).css('display', 'flex');
            
            // Initialize premium selects in the newly added HTML
            setTimeout(() => initPremiumSelects('#modalBody'), 50);
        }

        function closeGlobalModal() {
            $('#globalModal').fadeOut(300);
        }
    </script>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-wrapper">
            <!-- Top Navigation -->
            <nav class="top-nav">
                <div class="nav-brand desktop-only">
                    <?php
                    $page_titles = [
                        'dashboard.php' => 'KlinikMod Dashboard',
                        'appointments.php' => 'Randevu Yönetimi',
                        'customers.php' => 'Müşteri Yönetimi',
                        'finance.php' => 'Finans Yönetimi',
                        'staff.php' => 'Personel Yönetimi',
                        'services.php' => 'Hizmet Yönetimi',
                        'settings.php' => 'Ayarlar',
                        'users.php' => 'Kullanıcı Yönetimi'
                    ];
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $display_title = $page_titles[$current_page] ?? 'KlinikMod';

                    if ($current_page === 'dashboard.php'): 
                        // Yaklaşan Randevu Sayısı (Header Butonu İçin)
                        $header_upcoming_count = $db->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND appointment_time >= CURTIME() AND status = 'pending' AND is_deleted = 0")->fetchColumn();
                    ?>
                        <div class="header-quick-actions">
                            <button class="quick-action-btn upcoming" onclick="openUpcomingModal()">
                                <i class="fa-solid fa-calendar-day"></i>
                                <span>Yaklaşanlar</span>
                                <?php if($header_upcoming_count > 0): ?>
                                    <span class="count-badge"><?= $header_upcoming_count ?></span>
                                <?php endif; ?>
                            </button>
                            <div class="action-divider"></div>
                            <button class="quick-action-btn" onclick="openAddModal('customer')">
                                <i class="fa-solid fa-user-plus"></i>
                                <span>Müşteri</span>
                            </button>
                            <button class="quick-action-btn" onclick="openAddModal('service')">
                                <i class="fa-solid fa-briefcase-medical"></i>
                                <span>Hizmet</span>
                            </button>
                            <button class="quick-action-btn" onclick="openAddModal('staff')">
                                <i class="fa-solid fa-user-doctor"></i>
                                <span>Personel</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <h2><?= $display_title ?></h2>
                        <p style="color: var(--text-muted); font-size: 14px;">Bilgileriniz en doğru içgörüler için tasarlandı</p>
                    <?php endif; ?>
                </div>


                <div class="top-actions">
                    <button id="approval-hub-btn" class="approval-indicator" onclick="openApprovalHub()">
                        <i class="fa-solid fa-bell-concierge"></i>
                        <span>Onay Bekleyen <b id="pending-count">0</b> Randevu</span>
                    </button>
                    <div class="header-stats">
                        <?php if ($is_finance_page && is_admin()): ?>
                            <?php 
                            // Filtreleme yapılmışsa bu değerleri kullan, yoksa varsayılan header değerlerini
                            $display_income = isset($filtered_income) ? $filtered_income : $total_income;
                            $display_expense = isset($filtered_expense) ? $filtered_expense : $total_expense;
                            $display_net = isset($filtered_net) ? $filtered_net : $net_profit;
                            $is_filtering = isset($is_filtered) && $is_filtered;
                            ?>
                            <div class="stat-item stat-12h">
                                <span class="label"><?= $is_filtering ? 'Seçili Gelir' : '12S Gelir' ?></span>
                                <span class="value" style="color: var(--accent-lime-dark); font-size: 15px;"><?= number_format($is_filtering ? $display_income : $last_12h_income, 0) ?> <?= $currency ?></span>
                            </div>
                            <div class="stat-item stat-all">
                                <span class="label"><?= $is_filtering ? 'Seçili Gider' : 'Tüm Gelir' ?></span>
                                <span class="value" style="color: <?= $is_filtering ? 'var(--accent-coral)' : 'var(--primary)' ?>; font-size: 15px;"><?= number_format($is_filtering ? $display_expense : $total_income, 0) ?> <?= $currency ?></span>
                            </div>
                            <div class="stat-item stat-profit">
                                <span class="label"><?= $is_filtering ? 'Dönem Karı' : 'Net Kar' ?></span>
                                <span class="value" style="color: #3b5a0a; font-size: 15px;"><?= number_format($display_net, 0) ?> <?= $currency ?></span>
                            </div>
<?php endif; ?>
                        <?php if (!$is_finance_page): ?>
                            <?php if(is_admin()): ?>
                            <div class="stat-item stat-income">
                                <span class="label">Bugünkü Gelir</span>
                                <span class="value" style="color: var(--primary);"><?= number_format($today_income, 0) ?> <?= $currency ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="stat-item stat-appt">
                                <span class="label">Randevu</span>
                                <span class="value" style="color: #3b5a0a;"><?= $today_appts_count ?></span>
                            </div>
                        <?php endif; ?>

                    </div>
                    
                    <div class="user-block" style="display: flex; align-items: center; gap: 15px;">

                        <div class="user-avatar-wrapper desktop-only">
                            <div class="user-profile-icon">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="online-status"></div>
                        </div>
                        <button class="mobile-menu-btn" onclick="toggleSidebar()" title="Menü">
                            <i class="fa-solid fa-bars-staggered"></i>
                        </button>
                    </div>
                </div>
            </nav>

            <?php
            // Sayfa erişim izni kontrolü - izin yoksa blur overlay göster
            $permission_pages = ['dashboard','appointments','customers','services','staff','finance','settings'];
            $_current_page_key = str_replace('.php', '', basename($_SERVER['PHP_SELF']));
            if (in_array($_current_page_key, $permission_pages) && !has_page_permission($_current_page_key)):
            ?>
            <div id="permission-denied-overlay" style="position:fixed;inset:0;z-index:9900;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);background:rgba(248,250,252,0.70);">
                <div style="background:#ffffff;border-radius:28px;padding:52px 44px;text-align:center;max-width:440px;width:90%;box-shadow:0 24px 80px rgba(165,166,216,0.28),0 8px 24px rgba(0,0,0,0.08);border:1px solid rgba(165,166,216,0.18);animation:fadeInScale .35s cubic-bezier(.4,0,.2,1);">
                    <div style="width:88px;height:88px;background:linear-gradient(135deg,rgba(165,166,216,0.15),rgba(165,166,216,0.05));border:2px solid rgba(165,166,216,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 28px;">
                        <i class="fa-solid fa-lock" style="font-size:38px;color:var(--primary);"></i>
                    </div>
                    <h2 style="margin:0 0 14px;font-size:24px;font-weight:800;color:#1e1e2e;letter-spacing:-0.5px;">Erişim Kısıtlandı</h2>
                    <p style="margin:0 0 32px;color:#64748b;font-size:15px;line-height:1.7;">Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.<br>Sistem yöneticinizle iletişime geçiniz.</p>
                    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,var(--primary),#8384c0);color:white;padding:15px 32px;border-radius:14px;text-decoration:none;font-weight:700;font-size:14px;box-shadow:0 4px 16px rgba(165,166,216,0.35);transition:all .3s;">
                        <i class="fa-solid fa-house"></i> Dashboard'a Dön
                    </a>
                </div>
            </div>
            <style>@keyframes fadeInScale{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}</style>
            <?php endif; ?>

            <script>
            function toggleSidebar() {
                console.log('Sidebar toggling...');
                const sidebar = document.querySelector('.sidebar');
                if(!sidebar) return;
                
                sidebar.classList.toggle('mobile-active');
                
                if(sidebar.classList.contains('mobile-active')) {
                    if(!document.querySelector('.sidebar-overlay')) {
                        const overlay = document.createElement('div');
                        overlay.className = 'sidebar-overlay';
                        overlay.onclick = toggleSidebar;
                        document.body.appendChild(overlay);
                        document.body.style.overflow = 'hidden';
                    }
                } else {
                    const overlay = document.querySelector('.sidebar-overlay');
                    if(overlay) overlay.remove();
                    document.body.style.overflow = '';
                }
            }
            </script>

