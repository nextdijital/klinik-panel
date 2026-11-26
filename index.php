<?php
require_once 'functions.php';

// Önbelleği devre dışı bırak
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Zaten giriş yapmışsa Dashboard'a yönlendir
redirect_if_logged_in();
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'tr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login_title') ?> - KlinikMod</title>
    <meta name="description" content="KlinikMod - Profesyonel Randevu ve Danışan Yönetim Paneli">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-body">
    <main class="login-wrapper">
        <!-- Sol Panel: Giriş Formu -->
        <div class="login-side-form">
            <div class="login-card">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fa-solid fa-om" aria-hidden="true"></i>
                    </div>
                    <span>KlinikMod</span>
                </div>
                
                <h1><?= __('welcome') ?></h1>
                <p class="login-subtitle"><span><?= __('login_subtitle') ?? 'Lütfen hesabınıza giriş yapın.' ?></span></p>

                <!-- Dinamik Hata Alanı -->
                <div id="login-alert" class="login-error" style="display: none;">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span class="error-text"></span>
                </div>

                <form id="login-form">
                    <div class="form-group">
                        <label class="form-label" for="login-username"><?= __('username') ?></label>
                        <div class="input-wrapper">
                            <input type="text" name="username" id="login-username" class="form-control" required placeholder="Kullanıcı Adı" autocomplete="username">
                            <i class="fa-solid fa-user input-icon" aria-hidden="true"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="login-pass"><?= __('password') ?></label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="login-pass" class="form-control" required placeholder="••••••••" autocomplete="current-password">
                            <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash toggle-password" data-target="login-pass" role="button" aria-label="Şifreyi Göster veya Gizle" tabindex="0"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="login-sec">Giriş Anahtarı</label>
                        <div class="input-wrapper">
                            <input type="password" name="security_code" id="login-sec" class="form-control" required placeholder="Güvenlik Kodu" autocomplete="off">
                            <i class="fa-solid fa-shield-halved input-icon" aria-hidden="true"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="login-btn" aria-label="<?= __('login_btn') ?>">
                        <span><?= __('login_btn') ?></span>
                        <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                    </button>
                </form>

                <div class="login-footer">
                    Klinikmod &copy; <?= date('Y') ?> NextDijital Tüm Hakları Saklıdır.
                </div>
            </div>
        </div>

        <!-- Sağ Paneller: Bilgilendirme ve Karşılama -->
        <div class="login-side-info">
            <div class="info-card-large">
                <!-- İçerik Alanı (Normalde Görünür) -->
                <div id="side-content-default" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                    <div class="date-display">
                        <?php 
                            $gunler = array("Pazar", "Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi");
                            $aylar = array("", "Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık");
                            $gun = $gunler[date('w')];
                            $ay = $aylar[date('n')];
                        ?>
                        <div class="day-info"><?= $gun ?></div>
                        <h3><?= date('j') ?></h3>
                        <div class="day-info"><?= $ay ?></div>
                    </div>
                    
                    <div class="location-info">
                        <p><i class="fa-solid fa-circle-info"></i> KlinikMod Yönetim Sistemi'ne hoş geldiniz. Profesyonel randevu ve finans takibi ile işinizi kolaylaştırın.</p>
                    </div>
                </div>

                <!-- Dinamik Hata Alanı (Sadece Hata Anında) -->
                <div id="side-content-error" class="side-error-box" style="display: none;">
                    <div class="error-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="error-msg"></div>
                    <p class="error-hint">Lütfen bilgilerinizi kontrol edip tekrar deneyin.</p>
                </div>
            </div>

            <div class="info-card-small">
                <div class="text-content">
                    <h4>Hızlı Erişim</h4>
                    <p>Operasyon Paneli</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Arka Plan Parçacıkları -->
    <div class="bg-particles" aria-hidden="true">
        <div class="particle p1"></div>
        <div class="particle p2"></div>
        <div class="particle p3"></div>
        <div class="particle p4"></div>
    </div>

    <!-- Scriptler (Oluşturma engellemesini önlemek için sayfa sonuna taşındı) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
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

            // AJAX Login Logic
            $('#login-form').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $('#login-btn');
                const $defaultSide = $('#side-content-default');
                const $errorSide = $('#side-content-error');
                
                $btn.prop('disabled', true).find('span').text('İşleniyor...');
                
                // Hata varsa önceki halini temizle
                $errorSide.fadeOut(200);

                $.ajax({
                    url: 'actions/login_process.php',
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(res) {
                        if (res.status === 'success') {
                            // Başarı durumunda ufak bir yeşil bildirim veya direkt yönlendirme
                            $btn.find('span').text('Giriş Yapıldı!');
                            window.location.href = 'pages/dashboard.php';
                        } else {
                            // Hata durumunda sağ paneli güncelle
                            $defaultSide.fadeOut(300, function() {
                                $errorSide.find('.error-msg').text(res.message);
                                $errorSide.fadeIn(300);
                            });
                            
                            $btn.prop('disabled', false).find('span').text('<?= __('login_btn') ?>');
                            
                            // 5 saniye sonra veya her denemede geri getir
                            setTimeout(() => {
                                if($errorSide.is(':visible')) {
                                    $errorSide.fadeOut(300, function() {
                                        $defaultSide.fadeIn(300);
                                    });
                                }
                            }, 5000);
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Sistem hatası oluştu.';
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            if (parsed && parsed.message) msg = parsed.message;
                        } catch(err) {}
                        $defaultSide.fadeOut(300, function() {
                            $errorSide.find('.error-msg').text(msg);
                            $errorSide.fadeIn(300);
                        });
                        $btn.prop('disabled', false).find('span').text('<?= __('login_btn') ?>');
                    }
                });
            });
        });
    </script>
</body>
</html>
