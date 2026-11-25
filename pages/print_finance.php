<?php
require_once '../functions.php';
check_login();

// Filtreleme Parametreleri (finance.php'den gelenlerle aynı)
$filter_type = $_GET['type'] ?? 'all';
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';

$where = [];
$params = [];

if ($filter_type !== 'all' && in_array($filter_type, ['income', 'expense'])) {
    $where[] = "f.type = ?";
    $params[] = $filter_type;
}
if ($filter_start) {
    $where[] = "DATE(f.created_at) >= ?";
    $params[] = $filter_start;
}
if ($filter_end) {
    $where[] = "DATE(f.created_at) <= ?";
    $params[] = $filter_end;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Verileri Çek
$stmt = $db->prepare("
    SELECT f.*, s.name as service_name 
    FROM finance f 
    LEFT JOIN appointments a ON f.appointment_id = a.id 
    LEFT JOIN services s ON a.service_id = s.id 
    $where_clause 
    ORDER BY f.created_at DESC
");
$stmt->execute($params);
$records = $stmt->fetchAll();

// İstatistikleri Hesapla
$total_income = 0;
$total_expense = 0;
foreach($records as $r) {
    if($r['type'] == 'income') $total_income += $r['amount'];
    else $total_expense += $r['amount'];
}
$net_profit = $total_income - $total_expense;

$currency = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'currency'")->fetchColumn() ?: '₺';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Finansal Rapor - KlinikMod</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #A5A6D8;
            --text-main: #2d2e42;
            --text-muted: #64748b;
            --accent-lime: #BEF264;
            --accent-coral: #FB7185;
        }
        body {
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 40px;
            background: white;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .brand h1 { font-size: 28px; font-weight: 900; margin: 0; letter-spacing: 1px; color: #0f172a; }
        .brand p { margin: 5px 0 0; color: var(--text-muted); font-size: 14px; font-weight: 600; }
        
        .header-stats {
            display: flex;
            gap: 15px;
        }
        .stat-item {
            border: 1px solid rgba(165,166,216,0.15);
            background: #f8fafc;
            border-radius: 16px;
            padding: 12px 20px;
            min-width: 130px;
            text-align: center;
        }
        .stat-item .label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .stat-item .value {
            font-size: 18px;
            font-weight: 800;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            margin-top: 20px;
        }
        th {
            text-align: left;
            padding: 10px;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 1px;
            border-bottom: 2px solid #f1f5f9;
        }
        td {
            padding: 15px 10px;
            vertical-align: middle;
            background: transparent;
        }

        /* Bloklu Tasarım */
        .date-badge {
            background: rgba(134, 135, 195, 0.1);
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(165,166,216,0.1);
        }
        .description-block-card {
            padding: 10px 16px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 700;
            width: 100%;
            border: 1px solid rgba(0,0,0,0.03);
        }
        .description-block-card.income { background: rgba(190, 242, 100, 0.12); color: #74b816; }
        .description-block-card.expense { background: #fef2f2; color: #dc2626; }
        .description-block-card.cancelled { background: #f1f5f9; color: #64748b; }

        .type-pill {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .type-pill.income { background: #ecfbe6; color: #74b816; }
        .type-pill.expense { background: #fee2e2; color: #dc2626; }

        .amount-text { font-weight: 800; font-size: 15px; }
        .amount-text.income { color: #74b816; }
        .amount-text.expense { color: #dc2626; }

        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="brand">
            <h1>KLİNİKMOD</h1>
            <p>FİNANSAL RAPOR | <?= $is_filtered ? ($filter_start ? date('d.m.Y', strtotime($filter_start)) : '...') . ' - ' . ($filter_end ? date('d.m.Y', strtotime($filter_end)) : date('d.m.Y')) : 'TÜM ZAMANLAR' ?></p>
        </div>
        <?php if(is_admin()): ?>
        <div class="header-stats">
            <div class="stat-item">
                <span class="label">TOPLAM GELİR</span>
                <span class="value" style="color: #74b816;"><?= number_format($total_income, 0, ',', '.') ?> <?= $currency ?></span>
            </div>
            <div class="stat-item">
                <span class="label">TOPLAM GİDER</span>
                <span class="value" style="color: #dc2626;"><?= number_format($total_expense, 0, ',', '.') ?> <?= $currency ?></span>
            </div>
            <div class="stat-item">
                <span class="label">NET DURUM</span>
                <span class="value" style="color: #2563eb;"><?= number_format($net_profit, 0, ',', '.') ?> <?= $currency ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%;">İŞLEM TARİHİ</th>
                <th style="width: 45%;">AÇIKLAMA</th>
                <th style="width: 15%;">TÜR</th>
                <th style="width: 20%; text-align: right;">TUTAR</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $row): 
                $amount = (float)$row['amount'];
                $is_expense = $row['type'] == 'expense';
                $is_cancelled = ($amount == 0 && (strpos($row['description'], 'İptal') !== false || strpos($row['description'], 'Gelmeme') !== false));
                $status_class = $is_cancelled ? 'cancelled' : ($is_expense ? 'expense' : 'income');
                $status_label = $is_cancelled ? 'İPTAL' : ($is_expense ? 'GİDER' : 'GELİR');
            ?>
            <tr>
                <td><div class="date-badge"><i class="fa-regular fa-calendar"></i> <?= date('d.m.Y H:i', strtotime($row['created_at'])) ?></div></td>
                <td><div class="description-block-card <?= $status_class ?>"><i class="fa-solid <?= $is_cancelled ? 'fa-ban' : ($is_expense ? 'fa-arrow-trend-down' : 'fa-arrow-trend-up') ?>"></i> <?= clean($row['description']) ?></div></td>
                <td><span class="type-pill <?= $status_class ?>"><?= $status_label ?></span></td>
                <td style="text-align: right;"><span class="amount-text <?= $status_class ?>"><?= $is_cancelled ? '' : ($is_expense ? '- ' : '+ ') ?><?= number_format($row['amount'], 2, '.', ',') ?> <?= $currency ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        window.onload = function() {
            window.print();
            // window.onafterprint = function() { window.close(); }; // İsteğe bağlı
        }
    </script>
</body>
</html>
