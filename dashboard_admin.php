<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
            alert('Akses Ditolak: Anda bukan admin!');
            window.location.href = 'index.php';
          </script>";
    exit();
}

require_once 'koneksi.php';

// Fix: Proper prepared statement usage with error handling
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt->execute([$_SESSION['user_id']])) {
    die("Error fetching user data: " . implode(", ", $pdo->errorInfo()));
}
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'admin') {
    header('Location: dashboard_sales.php');
    exit;
}

// Ambil statistik admin
$stats_query = $pdo->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_sales,
        COUNT(t.id) as total_toko,
        COUNT(DISTINCT t.sales_id) as sales_aktif,
        SUM(CASE WHEN t.foto_toko IS NOT NULL THEN 1 ELSE 0 END) as total_foto
    FROM users u 
    LEFT JOIN toko t ON u.id = t.sales_id AND u.role = 'sales'
    WHERE u.role = 'sales'
");
$stats = $stats_query->fetch();

// Ambil top sales
$top_sales_query = $pdo->query("
    SELECT 
            u.username, 
            u.email,
            u. area_code,
            COUNT(t.id) as jumlah_toko,
            SUM(CASE WHEN t.foto_toko IS NOT NULL THEN 1 ELSE 0 END) as foto_toko
    FROM users u 
    LEFT JOIN toko t ON u.id = t.sales_id 
    WHERE u.role = 'sales'
    GROUP BY u.id 
    ORDER BY jumlah_toko DESC 
    LIMIT 5
");
$top_sales = $top_sales_query->fetchAll();

// Ambil data toko untuk table dan CSV
$all_toko_data = $pdo->query("
    SELECT t.*, u.username as sales_name, u.id as sales_id
    FROM toko t 
    JOIN users u ON t.sales_id = u.id
    ORDER BY t.created_at DESC -- Diubah agar data terbaru di atas
")->fetchAll(PDO::FETCH_ASSOC);

$all_toko_csv = $all_toko_data; // Sama untuk CSV


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPN - Admin Sales</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <style>
        /* Add your CSS here - copy from dashboard_sales.php */
        body {
            background-color: #f4f1ea; /* Coklat sangat muda/Cream */
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #3d2b1f;
            }        
        .navbar {
            background: #3d2b1f; /* Coklat Tua Elegan */
            padding: 1rem 2rem;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-bottom: 3px solid #d4a017; /* Garis Kuning Tipis */
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: #f4f1ea;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            color: #d4a017; /* Icon Kuning */
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 50px;
            border: 1px solid rgba(212, 160, 23, 0.3);
        }

        .user-avatar span {
            font-weight: 600;
        }
        .logout-btn {
            background: #a62c2b; /* Merah Marun */
            color: white;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: bold;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .logout-btn:hover {
            background: #c53030;
            box-shadow: 0 0 10px rgba(166, 44, 43, 0.4);
        }

        .export-btn {
            background: #d4a017; /* Kuning Emas/Mustard */
            color: #3d2b1f;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .export-btn:hover {
            background: #b8860b;
            color: white;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        /* Base style untuk tombol aksi */
        .btn-action {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 1rem;
        }

        /* Tombol Edit - Menggunakan Kuning sesuai tema */
        .btn-edit {
            background-color: #fcf4db; /* Kuning muda transparan */
            color: #531401; /* Kuning Mustard */
            border: 2px solid #410f00;
        }

        .btn-edit:hover {
            background-color: #a62c2b;
            color: #fff;
            transform: translateY(-2px);
        }

        /* Tombol Hapus - Menggunakan Merah sesuai tema */
        .btn-delete {
            background-color: #fdf2f2; /* Merah muda transparan */
            color: #a62c2b; /* Merah Marun */
            border: 1px solid #a62c2b;
        }

        .btn-delete:hover {
            background-color: #a62c2b;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(166, 44, 43, 0.2);
        }

        .main-content { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .stats-header { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card {
                background: white;
                border-left: 5px solid #d4a017; /* Aksen Kuning */
                padding: 20px;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            }

        .stat-icon {
                color: #a62c2b; /* Icon Merah */
                font-size: 1.8rem;
            }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #2d3748; }
        .stat-label { color: #718096; font-size: 1.1rem; }
        .section-title { display: flex; align-items: center; gap: 0.5rem; color: #2d3748; margin-bottom: 1rem; }
        .table-container {
                background: white;
                border-radius: 15px;
                overflow: hidden;
                border: 1px solid #e2d9c8;
         }

        table { width: 100%; border-collapse: collapse; }
        table th, 
        table td {
            text-align: center;
            vertical-align: middle; /* Menjaga konten tetap di tengah secara vertikal */
            padding: 12px 15px;
        }

        .performance-text {
            font-weight: bold;
            color: #a62c2b; /* Merah sesuai tema */
        }

        table td i {
            margin-right: 5px;
            color: #d4a017; /* Kuning sesuai tema */
        }

        td { padding: 1.5rem 1rem; border-bottom: 1px solid #e2e8f0; }

        thead {
            background: #3d2b1f;
            color: #f4f1ea;
        }

        th {
            padding: 15px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .toko-foto { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer; }
        .no-foto { width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; color: #cbd5e0; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
        .status-aktif {
            background: #e6fffa;
            color: #2c7a7b;
            border: 1px solid #b2f5ea;
        }

        .id-toko-badge {
            background: #3d2b1f;
            color: #d4a017;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
        }
        .status-belum { background: #fed7d7; color: #742a2a; }
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
        select { padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; }

        .area-code-badge {
            background: linear-gradient(135deg, #f6953b, #d8391d);
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

        .id-toko-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }
        /* --- Penyesuaian Responsive Baru --- */

        .table-container { 
            background: white; 
            border-radius: 15px; 
            overflow-x: auto; /* Memungkinkan scroll horizontal jika tabel terlalu lebar */
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            margin-bottom: 2rem;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 1100px; /* Memaksa tabel tetap lebar agar kolom tidak berhimpitan di HP */
        }

        /* Memperkecil padding sel di layar HP */
        @media (max-width: 768px) {
            th, td {
                padding: 1rem 0.75rem;
                font-size: 0.85rem;
            }
            
            .main-content {
                padding: 0 1rem;
            }

            /* Membuat Header Statistik Jadi 2 Kolom di HP */
            .stats-header {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .stat-card { padding: 1.5rem 1rem; }
            .stat-number { font-size: 1.8rem; }
            .stat-icon { font-size: 2rem; }

            /* Indikator agar user tahu tabel bisa digeser */
            .table-container::after {
                content: "← Geser tabel ke samping untuk melihat detail →";
                display: block;
                text-align: center;
                padding: 10px;
                font-size: 0.7rem;
                color: #a0aec0;
                background: #f7fafc;
            }
        }

        /* Sembunyikan Nama User di HP agar Navbar tetap rapi */
        @media (max-width: 480px) {
            .user-info span { display: none; }
            .stats-header { grid-template-columns: 1fr; } /* 1 Kolom saja di HP kecil */
        }

        
    </style>

</head>
<body>
    <!-- Navbar Admin -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-users-cog"></i> ADMIN SALES
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-key"></i>
                    <span><?= htmlspecialchars($user['username']) ?></span>
                </div>
                <a href="logout.php" class="logout-btn">Keluar</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <!-- Stats Admin -->
        <div class="stats-header">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                <div class="stat-number"><?= $stats['total_sales'] ?? 0 ?></div>
                <div class="stat-label">Sales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-store"></i></div>
                <div class="stat-number"><?= $stats['total_toko'] ?? 0 ?></div>
                <div class="stat-label">Total Toko</div>
            </div>
            <!-- <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-camera"></i></div>
                <div class="stat-number"><?= $stats['total_foto'] ?? 0 ?></div>
                <div class="stat-label">Foto Toko</div>
            </div> -->
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
        </div>

        <!-- Top Sales -->
        <div class="maps-section">
            <h2 class="section-title"><i class="fas fa-trophy"></i> Top Sales</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Sales</th>
                            <th>Area</th>
                            <th>Jumlah Toko</th>
                            <!-- <th>Foto Toko</th> -->
                            <!-- <th>Performance</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_sales)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding:2rem; color:#999;">
                                    <i class="fas fa-info-circle"></i> Belum ada data sales
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($top_sales as $sales): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($sales['username']) ?></strong></td>
                                <td>
                                    <span class="area-code-badge">
                                        <?= htmlspecialchars($sales['area_code'] ?? '-') ?>
                                    </span>
                                </td>
                                <td><i class="fas fa-store"></i> <?= $sales['jumlah_toko'] ?></td>
                                <!-- <td><i class="fas fa-camera"></i> <?= $sales['foto_toko'] ?></td> -->
                                 
                                <!-- <td>
                                    //PERHITUNGAN PERFORMANCE DAPAT DARI MANA ?
                                    <span class="performance-text">
                                        <?= $sales['jumlah_toko'] > 0 ? round(($sales['foto_toko'] / $sales['jumlah_toko']) * 100) : 0 ?>%
                                    </span>
                                </td> -->


                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Filter Sales & Semua Toko -->
        <div class="toko-table-section">
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; align-items: center; justify-content: space-between; flex-wrap: wrap;">
        <h2 class="section-title" style="margin-bottom: 0;"><i class="fas fa-list"></i> Semua Toko</h2>
        <div style="display: flex; gap: 0.5rem; flex-grow: 1; justify-content: flex-end; flex-wrap: wrap;">
            <select id="salesFilter" onchange="filterToko()" style="flex-grow: 1; max-width: 200px;">
                <option value="">Semua Sales</option>
            <?php 
            $sales_list_query = $pdo->query("SELECT id, username FROM users WHERE role='sales' ORDER BY username");
            if ($sales_list_query) {
                $sales_list = $sales_list_query->fetchAll();
                foreach($sales_list as $s): 
            ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['username']) ?></option>
            <?php 
                endforeach; 
            }
            ?>
        </select>
        <!-- BUTTON EXPORT CSV BARU -->
             <button onclick="exportToCSV()" class="export-btn">
                <i class="fas fa-download"></i> <span class="btn-text">Export CSV</span>
            </button>
    </div>
    
    <div id="tokoTableContainer">
        <!-- Konten table akan di-generate JS -->
    </div>
</div>
    </main>

    <script>
    // Data untuk table dan CSV
    const allTokoData = <?= json_encode($all_toko_data) ?>;
    const csvData = <?= json_encode($all_toko_csv) ?>;

    function renderTokoTable(tokoList) {
        const container = document.getElementById('tokoTableContainer');
        if (tokoList.length === 0) {
            container.innerHTML = '<div style="padding:2rem;text-align:center;color:#999;">Tidak ada data toko</div>';
            return;
        }
        
        let html = `
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Toko</th>
                            <th>Foto</th>
                            <th>Nama Toko</th>
                            <th>Sales</th>
                            <th>Jenis Toko</th>
                            <th>Area</th>
                            <th>Wilayah</th>
                            <th>Alamat</th>
                            <th>No.HP</th>
                            <th>Status</th>
                            <th>Maps</th>
                            <TH>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        tokoList.forEach(toko => {
            const statusClass = toko.status === 'aktif' ? 'status-aktif' : 'status-belum';

            // LOGIKA AREA: Mengambil dari kolom kode_area per toko
            const areaBadge = toko.kode_area ? 
            `<span class="area-code-badge">${escapeHtml(toko.kode_area)}</span>` : 
            '<span style="color:#ccc; font-size: 0.8rem;">N/A</span>';
            
            // Jenis Toko badge
            let jenisClass = 'jenis-lain';
            let jenisText = toko.jenis_toko || 'Tidak diketahui';
            if (toko.jenis_toko === 'Grosir') jenisClass = 'jenis-grosir';
            else if (toko.jenis_toko === 'Eceran') jenisClass = 'jenis-eceran';
            else if (toko.jenis_toko === 'Agen') jenisClass = 'jenis-agen';
            
            const jenisBadge = `<span class="jenis-toko-badge ${jenisClass}">${escapeHtml(jenisText)}</span>`;
            
             // ID Toko
            const idToko = `<span class="id-toko-badge">${toko.id || 'N/A'}</span>`;
            
            html += `
                <tr>
                    <td>${idToko}</td>
                    <td>${toko.foto_toko ? 
                        `<img src="${toko.foto_toko}" class="toko-foto" onclick="openImageModal('${toko.foto_toko}')">` : 
                        '<div class="no-foto"><i class="fas fa-camera"></i></div>'
                    }</td>
                    <td><strong>${escapeHtml(toko.nama_toko)}</strong></td>
                    <td>${escapeHtml(toko.sales_name)}</td>
                    <td>${jenisBadge}</td>
                    <td>${areaBadge}</td>
                    <td>${escapeHtml(toko.wilayah || '-')}</td>
                    <td style="max-width: 200px;">${escapeHtml(toko.alamat || '-')}</td>
                    <td>${escapeHtml(toko.no_pemilik || '-')}</td>
                    <td><span class="status-badge ${statusClass}">${escapeHtml(toko.status)}</span></td>
                    <td>${toko.lat && toko.lng ? 
                        `<a href="https://maps.google.com/?q=${toko.lat},${toko.lng}" target="_blank" class="maps-link">
                            <i class="fas fa-map-marker-alt"></i>
                        </a>` : '-' 
                    }</td>

                   <td>
                        <div class="action-buttons">
                            <a href="edit_toko.php?id=${toko.id}" class="btn-action btn-edit" title="Edit Data">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <a href="hapus_toko.php?id=${toko.id}" 
                            onclick="return confirm('Yakin ingin menghapus toko ${escapeHtml(toko.nama_toko)}?')" 
                            class="btn-action btn-delete" 
                            title="Hapus Toko">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>

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

    function filterToko() {
        const filterSales = document.getElementById('salesFilter').value;
        const filtered = filterSales ? 
            allTokoData.filter(t => t.sales_id == filterSales) : 
            allTokoData;
        renderTokoTable(filtered);
    }

    // EXPORT CSV - UPDATE DENGAN KOLOM BARU
    function exportToCSV() {
        const filterSales = document.getElementById('salesFilter').value;
        let exportData = csvData;
        
        if (filterSales) {
            exportData = csvData.filter(t => t.sales_id == filterSales);
        }

        if (exportData.length === 0) {
            alert('Tidak ada data untuk di-export!');
            return;
        }

        const headers = [
            'ID Toko', 'Nama Toko', 'Sales', 'Jenis Toko', 'Kode Area', 
            'Alamat', 'No. HP', 'Status', 'Latitude', 'Longitude', 
            'Foto Toko', 'Tanggal Dibuat'
        ];
        
        let csvContent = headers.join(',') + '\n';
        
        exportData.forEach(toko => {
            const row = [
                toko.id || '',
                `"${toko.nama_toko?.replace(/"/g, '""') || ''}"`,
                `"${toko.sales_name?.replace(/"/g, '""') || ''}"`,
                `"${toko.jenis_toko?.replace(/"/g, '""') || ''}"`,
                `"${toko.kode_area?.replace(/"/g, '""') || ''}"`,
                `"${toko.alamat?.replace(/"/g, '""') || ''}"`,
                `"${toko.no_pemilik?.replace(/"/g, '""') || ''}"`,
                `"${toko.status || ''}"`,
                toko.lat || '',
                toko.lng || '',
                `"${toko.foto_toko?.replace(/"/g, '""') || ''}"`,
                toko.created_at || ''
            ];
            csvContent += row.join(',') + '\n';
        });

        // Download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `data_toko_${new Date().toISOString().slice(0,10)}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function openImageModal(src) {
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;z-index:9999;';
        modal.innerHTML = `<img src="${src}" style="max-width:90%;max-height:90%;border-radius:10px;"><button onclick="this.parentElement.remove()" style="position:absolute;top:20px;right:20px;color:white;font-size:2rem;background:none;border:none;cursor:pointer;">×</button>`;
        document.body.appendChild(modal);
    }

    // Initial load
    renderTokoTable(allTokoData);
</script>
</body>
</html>