<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sales') {
    header('Location: index.php?error=Silahkan login terlebih dahulu');
    exit;
}

require_once 'koneksi.php';

// Cek apakah user adalah sales
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt->execute([$_SESSION['user_id']])) {
    die("Error fetching user data: " . implode(", ", $pdo->errorInfo()));
}
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'sales') {
    header('Location: dashboard_admin.php'); // Redirect ke admin dashboard atau halaman lain
    exit;
}

// Ambil data wilayah sales (misal dari field wilayah di table users)
$sales_wilayah = $user['area_code'] ?? 'BELUM DISISIPKAN';

// Ambil statistik sales - hanya data milik sales ini
$stats_query = $pdo->prepare("
    SELECT 
        COUNT(t.id) as total_toko,
        SUM(CASE WHEN t.foto_toko IS NOT NULL THEN 1 ELSE 0 END) as total_foto,
        SUM(CASE WHEN t.status = 'aktif' THEN 1 ELSE 0 END) as toko_aktif,
        AVG(CASE WHEN t.lat IS NOT NULL AND t.lng IS NOT NULL THEN 1 ELSE 0 END) * 100 as coverage_persen
    FROM toko t 
    WHERE t.sales_id = ?
");

$toko_stmt = $pdo->prepare("
    SELECT 
        t.*, 
        u.username as sales_name, 
        u.area_code,
        -- Menggabungkan Area Code dan ID Toko (Contoh: JMB1-001)
        CONCAT(u.area_code, '-', LPAD(t.id, 3, '0')) as area_otomatis
    FROM toko t 
    JOIN users u ON t.sales_id = u.id
    WHERE t.sales_id = ?
    ORDER BY t.nama_toko
");
$toko_stmt->execute([$_SESSION['user_id']]);
$toko_list = $toko_stmt->fetchAll();

if (!$stats_query->execute([$_SESSION['user_id']])) {
    die("Error fetching stats: " . implode(", ", $pdo->errorInfo()));
}
$stats = $stats_query->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - <?= htmlspecialchars($user['username']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet"> -->
    <style>
        body { 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            margin: 0; 
            background: #f4f1ea; /* Background Cream/Coklat sangat muda */
            color: #000000;
            text-align: center;
        }        
        .navbar { background: linear-gradient(135deg, #00778b 0%, #00778b 100%); color: white; padding: 1rem 0; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .user-info { display: flex; gap: 1rem; align-items: center; }
        .user-avatar { 
            background: rgba(255,255,255,0.2); 
            padding: 0.5rem 1rem; 
            border-radius: 20px;
            display: flex; 
            align-items: center; 
            gap: 0.5rem;
        }
        .wilayah-badge {
            background: rgba(255,255,255,0.3);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        .logout-btn, .admin-link { 
            color: white; 
            text-decoration: none; 
            padding: 0.5rem 1rem; 
            border-radius: 5px; 
            transition: background 0.3s; 
        }
        .logout-btn:hover, .admin-link:hover { background: rgba(255,255,255,0.2); }
        .main-content { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .stats-header { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { 
            background: white; 
            padding: 2rem; 
            border-radius: 15px; 
            text-align: center; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00778b, #f31c00);
        }
        .stat-icon { font-size: 3rem; color: #00778b; margin-bottom: 1rem; }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #2d3748; }
        .stat-label { color: #718096; font-size: 1.1rem; }
        .progress-bar {
            width: 100%; 
            height: 8px; 
            background: #e2e8f0; 
            border-radius: 4px; 
            overflow: hidden; 
            margin-top: 0.5rem;
        }
        .progress-fill {
            height: 100%; 
            background: linear-gradient(90deg, rgb(0, 155, 175), #ff0000); 
            transition: width 0.3s ease;
        }
        .section-title { 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            color: #2d3748; 
            margin-bottom: 1rem; 
        }
        .table-container { 
            background: white; 
            border-radius: 15px; 
            overflow: hidden; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
        }
        table { width: 100%; border-collapse: collapse; }
        th { 
            background: #00768b70; 
            /* padding: 1.5rem 1rem;  */
            padding: 15px;
            letter-spacing: 1px;
            text-align: center; 
            text-transform: uppercase;
            font-weight: 800 !important; 
            color: #000000; 
            /* text-shadow: 1px 1px 2px rgba(0,0,0,0.2); */
        }
        td { padding: 1.5rem 1rem; border-bottom: 1px solid #e2e8f0; }
        .toko-foto { 
            width: 60px; 
            height: 60px; 
            object-fit: cover; 
            border-radius: 8px; 
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s;
        }
        .toko-foto:hover { border-color: #48bb78; transform: scale(1.05); }
        .no-foto { 
            width: 60px; 
            height: 60px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #cbd5e0; 
            background: #f7fafc;
            border-radius: 8px;
        }
        .status-badge { 
            padding: 0.25rem 0.75rem; 
            border-radius: 20px; 
            font-size: 0.8rem; 
            font-weight: 500; 
        }
        .status-aktif { background: #c6f6d5; color: #22543d; }
        .status-belum { background: #fed7d7; color: #742a2a; }
        .maps-link { 
            color: #48bb78; 
            text-decoration: none; 
            font-weight: 500;
        }
        .maps-link i {
            font-size: 2rem !important; /* Ukuran lebih besar */
            color: #a62c2b; /* Warna Merah Marun sesuai tema */
            cursor: pointer;
        }

        /* Efek hover agar lebih interaktif */
        .maps-link:hover {
            transform: scale(1.3); /* Membesar sedikit saat disentuh mouse */
        }

        .maps-link:hover i {
            color: #17d4ab; /* Berubah jadi Kuning saat hover */
        }

        .add-toko-btn {
            background: linear-gradient(135deg, #00778b, #38a169);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }
        .add-toko-btn:hover { transform: translateY(-2px); }
        /* .coverage-map {
            height: 500px;
            border-radius: 15px;
            margin-top: 1rem;
            background: #f8f9fa;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        } */

        .map-legend {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            font-size: 0.9rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
        }

        .stats-overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        #mapZoomInfo {
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .id-toko-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .area-code-badge {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            min-width: 50px;
            text-align: center;
        }

        .jenis-toko-badge {
            padding: 0.25rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }

        .jenis-grosir { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .jenis-eceran { background: linear-gradient(135deg, #10b981, #059669); }
        .jenis-agen { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .jenis-lain { background: #6b7280; }

        /* --- Penyesuaian Responsive --- */

        /* Memastikan container tabel bisa di-scroll secara horizontal di layar kecil */
        .table-container { 
            background: white; 
            border-radius: 15px; 
            overflow-x: auto; /* Kunci utama: scroll horizontal */
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            margin-bottom: 2rem;
        }

        /* Mencegah isi tabel hancur saat layar menyempit */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 900px; /* Memaksa tabel tetap lebar agar tidak berhimpitan */
        }

        /* Responsivitas untuk Navbar dan Konten Utama */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 1rem;
            }
            .logo { font-size: 1.2rem; }
            
            .user-info span { display: none; } /* Sembunyikan nama user di HP, sisakan icon & wilayah */
            .user-info .wilayah-badge { display: inline-block; }

            .main-content {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .stats-header {
                grid-template-columns: 1fr 1fr; /* Tampilan 2 kolom di HP */
                gap: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-number { font-size: 1.8rem; }
            .stat-icon { font-size: 2rem; }
            
            /* Indikator Geser untuk Tabel */
            .table-container::after {
                content: "← Geser tabel ke samping untuk melihat detail →";
                display: block;
                text-align: center;
                padding: 10px;
                font-size: 0.75rem;
                color: #a0aec0;
                background: #f7fafc;
            }
        }

        @media (max-width: 480px) {
            .stats-header {
                grid-template-columns: 1fr; /* Tampilan 1 kolom penuh di HP kecil */
            }
        }
    </style>
</head>
<body>
    <!-- Navbar Sales -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-user-tie"></i> Sales Dashboard
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($user['username']) ?></span>
                    <span class="wilayah-badge"><?= htmlspecialchars($sales_wilayah) ?></span>
                </div>
               <?php 
                // Contoh: cek apakah role user adalah 'admin'
                $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'); 
                ?>

                <!-- <?php if ($isAdmin): ?>
                    <a href="dashboard_admin.php" class="admin-link" title="Admin View">
                        <i class="fas fa-users-cog"></i>
                    </a>
                <?php else: ?>
                    <a href="javascript:void(0);" class="admin-link" title="Admin View" onclick="alert('Akses Ditolak: Anda bukan admin!')">
                        <i class="fas fa-users-cog"></i>
                    </a>
                <?php endif; ?> -->
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <!-- Stats Sales -->
        <div class="stats-header">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-store"></i></div>
                <div class="stat-number"><?= $stats['total_toko'] ?? 0 ?></div>
                <div class="stat-label">Total Toko</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-camera"></i></div>
                <div class="stat-number"><?= $stats['total_foto'] ?? 0 ?></div>
                <div class="stat-label">Foto Toko</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?= $stats['toko_aktif'] ?? 0 ?></div>
                <div class="stat-label">Toko Aktif</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= ($stats['total_toko'] > 0 ? round(($stats['toko_aktif'] / $stats['total_toko']) * 100) : 0) ?>%"></div>
                </div>
            </div>

            <!-- Tambah card ini di stats-header -->
            <!-- <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-tags"></i></div>
                <div class="stat-number">
                    <?php 
                    $jenis_stats = $pdo->prepare("
                        SELECT 
                            SUM(CASE WHEN jenis_toko = 'Grosir' THEN 1 ELSE 0 END) as grosir,
                            SUM(CASE WHEN jenis_toko = 'Eceran' THEN 1 ELSE 0 END) as eceran,
                            SUM(CASE WHEN jenis_toko = 'Agen' THEN 1 ELSE 0 END) as agen
                        FROM toko WHERE sales_id = ?
                    ");
                    $jenis_stats->execute([$_SESSION['user_id']]);
                    $jenis = $jenis_stats->fetch();
                    echo ($jenis['grosir'] ?? 0) + ($jenis['eceran'] ?? 0) + ($jenis['agen'] ?? 0);
                    ?>
                </div>
                <div class="stat-label">Jenis Toko</div>
            </div> -->

            <!-- <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="stat-number"><?= round($stats['coverage_persen'] ?? 0) ?>%</div>
                <div class="stat-label">Coverage Maps</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= round($stats['coverage_persen'] ?? 0) ?>%"></div>
                </div>
            </div> -->
        </div>

        <!-- MAPS COVERAGE WILAYAH SALES -->
        <!-- <div style="margin-bottom: 2rem;">
            <h2 class="section-title">
                <i class="fas fa-map"></i> 
                Coverage Wilayah <?= htmlspecialchars($sales_wilayah) ?>
            </h2>
            <div id="coverageMap" class="coverage-map"></div>
        </div> -->

        <!-- Tombol Tambah Toko -->
        <div style="margin-bottom: 2rem; text-align: left;">
            <button class="add-toko-btn" onclick="window.location.href='tambah_toko.php'">
                <i class="fas fa-plus"></i> Tambah Toko Baru
            </button>
        </div>

        <!-- Daftar Toko Sales -->
        <div>
            <h2 class="section-title">
                <i class="fas fa-list"></i> 
                TOKO WILAYAH <?= htmlspecialchars($sales_wilayah) ?>
            </h2>
            <div id="tokoTableContainer">
                <!-- Konten table akan di-generate JS -->
            </div>
        </div>
    </main>

    <script>
    // Load data toko milik sales ini saja
    <?php
    $toko_stmt = $pdo->prepare("
    SELECT 
                t.*, 
                u.username as sales_name, 
                u.area_code,
                -- Menggabungkan Area Code dan ID Toko (Contoh: JMB1-001)
                CONCAT(u.area_code, '-', LPAD(t.id, 3, '0')) as area_otomatis
            FROM toko t 
            JOIN users u ON t.sales_id = u.id
            WHERE t.sales_id = ?
            ORDER BY t.nama_toko
        ");
        $toko_stmt->execute([$_SESSION['user_id']]);
        $toko_list = $toko_stmt->fetchAll();
    ?>

    const allTokoData = <?= json_encode($toko_list) ?>;

    function renderTokoTable(tokoList) {
    const container = document.getElementById('tokoTableContainer');
    if (tokoList.length === 0) {
        container.innerHTML = `
            <div style="padding:2rem;text-align:center;color:#999;">
                <i class="fas fa-store-slash" style="font-size:4rem;color:#cbd5e0;margin-bottom:1rem;"></i>
                <p>Belum ada data toko di wilayah Anda</p>
                <button class="add-toko-btn" onclick="window.location.href='tambah_toko.php'" style="margin-top:1rem;">
                    <i class="fas fa-plus"></i> Tambah Toko Pertama
                </button>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-container">
            <table>
                <thead><tr>
                    <th>ID</th><th>Foto</th><th>Toko</th><th>Jenis</th><th>Area</th><th>Wilayah</th>
                    <th>Alamat</th><th>No.HP</th><th>Status</th><th>Maps</th>
                </tr></thead>
                <tbody>
    `;
    
    tokoList.forEach(toko => {
        const statusClass = toko.status === 'aktif' ? 'status-aktif' : 'status-belum';
        
        // ID Toko Badge
        const idToko = `<span class="id-toko-badge">${toko.id || 'N/A'}</span>`;
        
        // Jenis Toko Badge
        let jenisClass = 'jenis-lain';
        let jenisText = toko.jenis_toko || 'Tidak diketahui';
        if (toko.jenis_toko === 'Grosir') jenisClass = 'jenis-grosir';
        else if (toko.jenis_toko === 'Eceran') jenisClass = 'jenis-eceran';
        else if (toko.jenis_toko === 'Agen') jenisClass = 'jenis-agen';
        const jenisBadge = `<span class="jenis-toko-badge ${jenisClass}">${escapeHtml(jenisText)}</span>`;
        
        // --- BAGIAN YANG DIUBAH ---
        // Area Code Badge Otomatis (Mengambil hasil CONCAT dari SQL)
        const areaCode = toko.area_otomatis ? 
            `<span class="area-code-badge">${escapeHtml(toko.area_otomatis)}</span>` : 
            `<span class="area-code-badge">${escapeHtml(toko.area_code)}-${toko.id}</span>`;
        // ---------------------------
        
        html += `
            <tr>
                <td>${idToko}</td>
                <td>${toko.foto_toko ? 
                    `<img src="${toko.foto_toko}" class="toko-foto" onclick="openImageModal('${toko.foto_toko}')">` : 
                    '<div class="no-foto"><i class="fas fa-camera"></i></div>'
                }</td>
                <td><strong>${escapeHtml(toko.nama_toko)}</strong></td>
                <td>${jenisBadge}</td>
                <td>${areaCode}</td>
                <td>${escapeHtml(toko.wilayah || '-')}</td>
                <td style="max-width: 200px;">${escapeHtml(toko.alamat || '-')}</td>
                <td>${escapeHtml(toko.no_pemilik || '-')}</td>
                <td><span class="status-badge ${statusClass}">${escapeHtml(toko.status)}</span></td>
                <td>${toko.lat && toko.lng ? 
                    `<a href="https://www.google.com/maps?q=${toko.lat},${toko.lng}" target="_blank" class="maps-link">
                        <i class="fas fa-map-marker-alt"></i>
                    </a>` : '-' 
                }</td>
                
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function openImageModal(src) {
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;z-index:9999;';
        modal.innerHTML = `
            <img src="${src}" style="max-width:90%;max-height:90%;border-radius:10px;">
            <button onclick="this.parentElement.remove()" style="position:absolute;top:20px;right:20px;color:white;font-size:2rem;background:none;border:none;cursor:pointer;">×</button>
        `;
        document.body.appendChild(modal);
    }

    // Initial load - tampilkan semua toko sales ini
    renderTokoTable(allTokoData);
    </script>
    
</body>
</html>