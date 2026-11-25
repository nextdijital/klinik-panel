        </main>
    </div>

    <!-- Global Toast Container -->
    <div id="toast-container"></div>

    <!-- Approval Hub (Onay Merkezi) Overlay -->
    <div id="approvalHub" class="approval-overlay">
        <button class="close-approval-hub" onclick="closeApprovalHub()"><i class="fa-solid fa-xmark"></i></button>
        <div style="text-align: center; margin-bottom: 40px; color: white;">
            <h2 style="font-size: 32px; font-weight: 800; letter-spacing: -1px;">Onay Merkezi</h2>
            <p style="opacity: 0.7;">Lütfen saati gelen randevular için durum bildirimi yapın.</p>
        </div>
        <div id="approvalGrid" class="approval-grid">
            <!-- Dinamik olarak dolacak -->
        </div>
    </div>

    <!-- Reusable Modal Foundation -->
    <div id="globalModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Mesaj</h3>
                <button class="modal-close" onclick="closeGlobalModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="modalBody" class="modal-content"></div>
            <div id="modalFooter" class="modal-footer"></div>
        </div>
    </div>

    <!-- Quick Priority Approval Modal (Proaktif Uyarı) -->
    <style>
        .priority-majestic-card {
            width: 800px !important;
            max-width: 95vw !important;
            background: #ffffff !important;
            border-radius: 60px !important;
            padding: 100px !important;
            text-align: center !important;
            box-shadow: 0 50px 150px rgba(0,0,0,0.4) !important;
            box-sizing: border-box !important;
            margin: auto !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            position: relative !important;
            overflow: hidden !important;
            border: none !important;
        }
        .priority-icon-circle {
            width: 80px !important;
            height: 80px !important;
            background: rgba(244, 63, 94, 0.08) !important;
            color: #f43f5e !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 36px !important;
            margin: 0 auto 30px !important;
        }
        .priority-main-title {
            font-size: 24px !important;
            font-weight: 800 !important;
            color: #1a1a1a !important;
            margin-bottom: 10px !important;
        }
        .priority-patient-name {
            font-size: 18px !important;
            font-weight: 700 !important;
            color: #8384b6 !important;
            margin-bottom: 8px !important;
        }
        .priority-info-line {
            font-size: 13px !important;
            font-weight: 700 !important;
            color: #94a3b8 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            display: flex !important;
            justify-content: center !important;
            gap: 10px !important;
        }
        .priority-btn-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 20px !important;
            width: 100% !important;
            margin-top: 40px !important;
        }
        .priority-big-snooze {
            width: 100% !important;
            margin-top: 15px !important;
            padding: 18px !important;
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 20px !important;
            font-weight: 800 !important;
            color: #64748b !important;
            cursor: pointer !important;
        }

        @media (max-width: 768px) {
            .priority-majestic-card {
                padding: 40px 20px !important;
                border-radius: 30px !important;
            }
            .priority-btn-grid {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
                margin-top: 30px !important;
            }
            .priority-big-snooze {
                margin-top: 12px !important;
                padding: 15px !important;
            }
            .priority-main-title {
                font-size: 20px !important;
            }
            .priority-icon-circle {
                width: 60px !important;
                height: 60px !important;
                font-size: 28px !important;
                margin-bottom: 20px !important;
            }
        }

        /* Ödeme Yöntemi Butonları - Global */
        .payment-methods-grid-global {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        .pay-method-btn-global {
            background: white;
            border: 1px solid rgba(134, 135, 195, 0.15);
            padding: 12px 5px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-muted);
        }
        .pay-method-btn-global i { font-size: 18px; }
        .pay-method-btn-global span { font-size: 12px; font-weight: 700; }

        .pay-method-btn-global:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(134, 135, 195, 0.05);
        }

        .pay-method-btn-global.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(134, 135, 195, 0.2);
        }
    </style>
    <div id="priorityModal" class="modal-overlay" style="z-index: 3000; display:none; align-items:center; justify-content:center;">
        <div class="priority-majestic-card">
            <div class="priority-icon-circle">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            
            <div class="priority-body-centered">
                <h3 class="priority-main-title">Randevu Vakti Geldi!</h3>
                <h4 id="pri-name" class="priority-patient-name">-</h4>
                <div class="priority-info-line">
                    <span id="pri-service">-</span>
                    <span class="sep">•</span>
                    <span id="pri-time">-</span>
                </div>
                <div id="pri-date" class="priority-date-line">-</div>
            </div>
            
            <div class="priority-actions-centered">
                <div class="priority-btn-grid">
                    <button onclick="handlePriorityAction('completed')" class="btn-premium btn-premium-success">GELDİ</button>
                    <button onclick="handlePriorityAction('missed')" class="btn-premium btn-premium-danger">GELMEDİ</button>
                </div>
                <button onclick="handlePriorityAction('hub')" class="btn-secondary priority-big-snooze">ONAY MERKEZİNE AL</button>
            </div>
        </div>
    </div>

    <!-- Scripts (Libraries already checked in header) -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    
    <script>
        let overdueAppointments = [];
        let seenAppointments = (function() {
            try {
                const today = new Date().toDateString();
                const storedDate = localStorage.getItem('aptSeenDate');
                if (storedDate !== today) {
                    localStorage.setItem('aptSeenDate', today);
                    localStorage.setItem('aptSeenList', '[]');
                    return [];
                }
                let list = JSON.parse(localStorage.getItem('aptSeenList') || '[]');
                return Array.isArray(list) ? list.map(id => String(id)) : [];
            } catch(e) { return []; }
        })();
        let currentPriorityApt = null;

        // Toast ve Modal Sistemleri Artık header.php İçinde Merkezi Olarak Yönetiliyor

        // --- Modal System handled in header.php ---

        // --- Approval Hub Logic ---
        function checkOverdue() {
            const d = new Date();
            const clientDate = d.getFullYear() + '-' + (d.getMonth()+1).toString().padStart(2,'0') + '-' + d.getDate().toString().padStart(2,'0');
            const nowTime = d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');

            $.get('../actions/check_overdue.php', { client_date: clientDate }, function(data) {
                // Saniye farklarını elemek için saati 05:00 formatına çekip karşılaştırıyoruz
                overdueAppointments = data.filter(a => a.appointment_time.substr(0,5) <= nowTime);

                updateIndicator();
                
                // Proaktif Uyarılar (Tam vaktinde veya geçmiş olanlar için modal aç)
                handleProactiveAlerts(overdueAppointments);

                if (overdueAppointments.length > 0 && $('#approvalHub').is(':visible')) {
                    renderApprovalCards();
                }
            });
        }

        function handleProactiveAlerts(list) {
            if ($('#priorityModal').is(':visible') || $('#globalModal').is(':visible')) return;
            const newApt = list.find(a => !seenAppointments.includes(String(a.id)));
            if (newApt) {
                currentPriorityApt = newApt;
                $('#pri-name').text(newApt.name);
                $('#pri-service').text(newApt.service_name);
                $('#pri-time').text(newApt.appointment_time.substr(0,5));
                
                // Tarihi daha güvenli ve temiz formatla
                if (newApt.appointment_date) {
                    const parts = newApt.appointment_date.split('-');
                    $('#pri-date').text(parts[2] + '.' + parts[1] + '.' + parts[0]);
                } else {
                    $('#pri-date').text(new Date().toLocaleDateString('tr-TR'));
                }
                
                // Flexbox uyumluluğu için özel görünürlük tetikleyici
                $('#priorityModal').css('z-index', '99999').fadeIn(400).css('display', 'flex');
            }
        }

        function handlePriorityAction(action) {
            if (!currentPriorityApt) return;
            const id = String(currentPriorityApt.id);

            // Görüldü listesine ekle ki Popup bir daha otomatik çıkmasın
            if (!seenAppointments.includes(String(id))) {
                seenAppointments.push(String(id));
                localStorage.setItem('aptSeenList', JSON.stringify(seenAppointments));
            }

            if (action === 'hub') {
                showToast('Merkeze Aktarıldı', 'Randevu onay merkezine aktarıldı.', 'info');
                $('#priorityModal').hide();
                updateIndicator();
            } else {
                updateAptStatusHub(id, action);
                $('#priorityModal').hide();
                updateIndicator();
            }
        }

        function updateIndicator() {
            const btn = $('#approval-hub-btn');
            
            if (overdueAppointments.length > 0) {
                $('#pending-count').text(overdueAppointments.length);
                btn.css('display', 'flex');
            } else {
                btn.hide();
                closeApprovalHub();
            }
        }

        function openApprovalHub() {
            $('#approvalHub').css('display', 'flex');
            renderApprovalCards();
        }

        function closeApprovalHub() {
            $('#approvalHub').hide();
        }

        function renderApprovalCards() {
            const grid = $('#approvalGrid');
            grid.empty();

            overdueAppointments.forEach(a => {
                const card = $(`
                    <div class="approval-mini-card" id="apt-card-${a.id}">
                        <div class="priority-content-layout">
                            <div class="priority-header-row">
                                <div class="status-dot-large" style="background: ${a.service_color}"></div>
                                <h3 class="priority-title-text">${a.name}</h3>
                            </div>
                            
                            <div class="priority-details">
                                <div class="priority-detail-item" style="color: var(--primary);">
                                    <i class="fa-solid fa-briefcase-medical"></i>
                                    <span style="font-weight: 700;">${a.service_name}</span>
                                </div>
                                <div class="priority-detail-item" style="color: #3b82f6;">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>Randevu Saati: <b style="font-size: 16px;">${a.appointment_time.substr(0,5)}</b></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="priority-actions" style="margin-top: 20px;">
                            <div class="approval-btn-group">
                                <button onclick="updateAptStatusHub(${a.id}, 'completed')" class="btn-premium btn-premium-success" style="justify-content: center;">GELDİ</button>
                                <button onclick="updateAptStatusHub(${a.id}, 'missed')" class="btn-premium btn-premium-danger" style="justify-content: center;">GELMEDİ</button>
                            </div>
                        </div>
                    </div>
                `);
                grid.append(card);
            });
        }

        function updateAptStatusHub(id, status) {
            if (status === 'completed') {
                // Tamamlandı durumunda önce veriyi çek
                $.get('../actions/get_appointment.php', { id: id }, function(res) {
                    if(res.status == 'success') {
                        const a = res.data;
                        const currency = typeof globalCurrency !== 'undefined' ? globalCurrency : '₺';
                        const body = `
                            <div class="modal-split-grid">
                                <!-- Left Panel: Summary -->
                                <div class="modal-summary-panel">
                                    <div class="summary-item" style="margin-bottom: 0;">
                                        <div class="summary-icon"><i class="fa-solid fa-user"></i></div>
                                        <div class="summary-detail">
                                            <label>Hasta</label>
                                            <div class="value" style="color: #1a1a1a;">${a.first_name} ${a.last_name}</div>
                                        </div>
                                    </div>
                                    <div class="summary-item" style="margin-bottom: 0;">
                                        <div class="summary-icon"><i class="fa-solid fa-stethoscope"></i></div>
                                        <div class="summary-detail">
                                            <label>Hizmet</label>
                                            <div class="value" style="color: #1a1a1a;">${a.service_name}</div>
                                        </div>
                                    </div>
                                    <div class="summary-item" style="margin-bottom: 0;">
                                        <div class="summary-icon"><i class="fa-solid fa-calendar-day"></i></div>
                                        <div class="summary-detail">
                                            <label>Tarih & Saat</label>
                                            <div class="value" style="color: #1a1a1a;">${a.appointment_date} | ${a.appointment_time}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Panel: Pricing -->
                                <div class="modal-pricing-panel">
                                    <div class="confirm-wrapper" style="margin-bottom: 10px; text-align: left; padding: 0;">
                                        <h3 class="confirm-title" style="font-size: 16px; margin-bottom: 5px; color: #1a1a1a;">Ödeme ve Tahsilat</h3>
                                        <p class="confirm-text" style="font-size: 11px; margin-bottom: 15px; color: #64748b;">Tahsilat tutarını onaylayarak işlemi tamamlayın.</p>
                                    </div>

                                    <div style="padding: 15px; background: rgba(134, 135, 195, 0.03); border-radius: 12px; border: 1px dashed rgba(134, 135, 195, 0.2); text-align: left;">
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <label style="color: #1a1a1a; font-weight: 700; font-size: 10px; margin-bottom: 4px;">Hizmet Tutarı</label>
                                                <input type="number" step="0.01" id="status_base_price_global" class="form-control" style="height: 40px; font-size: 13px;" value="${a.base_price}" oninput="calculateStatusPriceGlobal()">
                                            </div>
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                                <div class="form-group" style="margin-bottom: 0;">
                                                    <label style="color: #1a1a1a; font-weight: 700; font-size: 10px; margin-bottom: 4px;">İndirim Türü</label>
                                                    <select id="status_discount_type_global" class="form-control" style="height: 40px; font-size: 13px;" onchange="calculateStatusPriceGlobal()">
                                                        <option value="none" ${a.discount_type == 'none' ? 'selected' : ''}>Yok</option>
                                                        <option value="fixed" ${a.discount_type == 'fixed' ? 'selected' : ''}>Sabit</option>
                                                        <option value="percentage" ${a.discount_type == 'percentage' ? 'selected' : ''}>Yüzde</option>
                                                    </select>
                                                </div>
                                                <div class="form-group" style="margin-bottom: 0;">
                                                    <label style="color: #1a1a1a; font-weight: 700; font-size: 10px; margin-bottom: 4px;">Değer</label>
                                                    <input type="number" step="0.01" id="status_discount_value_global" class="form-control" style="height: 40px; font-size: 13px;" value="${a.discount_value}" oninput="calculateStatusPriceGlobal()">
                                                </div>
                                            </div>
                                        </div>

                                        <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid rgba(134, 135, 195, 0.1);">
                                            <span style="font-weight: 700; color: #64748b; font-size: 10px;">TOPLAM TAHSİLAT</span>
                                            <div id="status_net_price_display_global" style="font-size: 18px; font-weight: 900; color: var(--primary);">${parseFloat(a.service_price).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${currency}</div>
                                            <input type="hidden" id="status_final_price_global" value="${a.service_price}">
                                        </div>

                                        <div style="margin-top: 20px;">
                                            <label style="font-size: 10px; font-weight: 800; color: #1a1a1a; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Ödeme Yöntemi Seçin</label>
                                            <div class="payment-methods-grid-global">
                                                <div class="pay-method-btn-global" onclick="selectPaymentMethodGlobal('cash', this)">
                                                    <i class="fa-solid fa-money-bill-wave"></i>
                                                    <span>Nakit</span>
                                                </div>
                                                <div class="pay-method-btn-global" onclick="selectPaymentMethodGlobal('transfer', this)">
                                                    <i class="fa-solid fa-building-columns"></i>
                                                    <span>EFT/Havale</span>
                                                </div>
                                                <div class="pay-method-btn-global" onclick="selectPaymentMethodGlobal('card', this)">
                                                    <i class="fa-solid fa-credit-card"></i>
                                                    <span>Kredi Kartı</span>
                                                </div>
                                            </div>
                                            <input type="hidden" id="selected_payment_method_global" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        const footer = `
                            <div class="confirm-footer-mobile" style="margin-top: 20px;">
                                <button id="complete-payment-btn-global" onclick="executeStatusUpdateGlobal(${id}, '${status}')" class="btn-premium" style="opacity: 0.5; cursor: not-allowed;" disabled>Tahsilatı Onayla ve Tamamla</button>
                                <button onclick="closeGlobalModal()" class="btn-secondary">Vazgeç</button>
                            </div>
                        `;

                        openGlobalModal('Ödeme ve Onay', body, footer, 'modal-lg');
                    }
                });
            } else {
                executeStatusUpdateGlobal(id, status);
            }
        }

        function selectPaymentMethodGlobal(method, el) {
            $('.pay-method-btn-global').removeClass('active');
            $(el).addClass('active');
            $('#selected_payment_method_global').val(method);
            
            // Aktif et butonu
            $('#complete-payment-btn-global').prop('disabled', false).css({'opacity': '1', 'cursor': 'pointer'});
        }

        function executeStatusUpdateGlobal(id, status) {
            const data = { id: id, status: status };
            
            if ($('#status_base_price_global').length) {
                data.base_price = $('#status_base_price_global').val();
                data.discount_type = $('#status_discount_type_global').val();
                data.discount_value = $('#status_discount_value_global').val();
                data.service_price = $('#status_final_price_global').val();
                data.payment_method = $('#selected_payment_method_global').val();
            }

            $.post('../actions/appointment_status.php', data, function(res) {
                if(res.status == 'success') {
                    showToast('Başarılı', 'İşlem tamamlandı.', 'success');
                    removeAptFromLocal(id);
                    closeGlobalModal();
                    
                    if (!seenAppointments.includes(String(id))) {
                        seenAppointments.push(String(id));
                        localStorage.setItem('aptSeenList', JSON.stringify(seenAppointments));
                    }

                    if(typeof calendar !== 'undefined') calendar.refetchEvents();
                } else {
                    showToast('Hata', res.message, 'error');
                }
            });
        }

        function removeAptFromLocal(id) {
            overdueAppointments = overdueAppointments.filter(a => a.id != id);
            $(`#apt-card-${id}`).fadeOut(300, function() {
                $(this).remove();
                updateIndicator();
            });
        }

        // Sayfa açılır açılmaz ve her 30 saniyede bir kontrol et
        checkOverdue(); 
        setInterval(checkOverdue, 30000);

        // PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../sw.js').then(reg => {
                    console.log('SW Registered');
                }).catch(err => {
                    console.log('SW Failed', err);
                });
            });
        }
    </script>
</body>
</html>
