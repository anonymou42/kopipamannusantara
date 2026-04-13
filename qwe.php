<?php
session_start();

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php?error=Harap login terlebih dahulu');
    exit;
}

// Tambahkan di atas (setelah session check):
if ($user['role'] !== 'sales') {
    header('Location: dashboard.php?error=Hanya sales yang bisa akses');
    exit;
}

require_once 'koneksi.php';

// Ambil data user
try {
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Ambil data toko sales ini (dengan wilayah)
    $stmt = $pdo->prepare("
        SELECT t.*, u.username as sales_name 
        FROM toko t 
        LEFT JOIN users u ON t.sales_id = u.id 
        WHERE t.sales_id = ? 
        ORDER BY t.nama_toko ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $toko_list = $stmt->fetchAll();
    
    // Hitung statistik
    $total_toko = count($toko_list);
    $toko_with_foto = count(array_filter($toko_list, fn($t) => !empty($t['foto_toko'])));
    
} catch (PDOException $e) {
    error_log("Dashboard sales error: " . $e->getMessage());
    session_destroy();
    header('Location: index.php?error=Sesi tidak valid');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - Wilayah Coverage</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: #333;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .sales-wilayah {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .logout-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }

        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            min-height: calc(100vh - 100px);
        }

        .stats-header {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
        }

        .maps-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #map {
            height: 400px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .toko-table-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 800px;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tr:hover {
            background: #f8f9ff;
        }

        .toko-foto {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .toko-foto:hover {
            transform: scale(1.1);
        }

        .no-foto {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.8rem;
        }

        .maps-link {
            color: #1a73e8;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .maps-link:hover {
            color: #1557b0;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-aktif { background: #d4edda; color: #155724; }
        .status-tidak { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .stats-header { grid-template-columns: 1fr; }
            .main-content { padding: 1rem; }
            #map { height: 300px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-map-marker-alt"></i>
                Sales Dashboard
            </div>
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 2)) ?></div>
                <div class="sales-wilayah">
                    <i class="fas fa-map-pin"></i>
                    <?= $total_toko ?> Toko
                </div>
                <a href="logout.php" class="logout-btn" onclick="return confirm('Yakin logout?')">
                    <i class="fas fa-sign-out-alt"></i>
                    Keluar
                </a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <!-- Stats Header -->
        <div class="stats-header">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-store"></i></div>
                <div class="stat-number"><?= $total_toko ?></div>
                <div class="stat-label">Total Toko</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-camera"></i></div>
                <div class="stat-number"><?= $toko_with_foto ?></div>
                <div class="stat-label">Foto Tersedia</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number">100%</div>
                <div class="stat-label">Coverage</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-number"><?= date('d M Y') ?></div>
                <div class="stat-label">Diperbarui</div>
            </div>
        </div>

        <!-- Maps Section -->
        <div class="maps-section">
            <h2 class="section-title">
                <i class="fas fa-map-marked-alt"></i>
                Wilayah Coverage
            </h2>
            <div id="map"></div>
        </div>

        <!-- Toko Table -->
        <div class="toko-table-section">
            <h2 class="section-title">
                <i class="fas fa-list"></i>
                Daftar Toko (<?= $total_toko ?>)
            </h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nama Toko</th>
                            <th>Alamat</th>
                            <th>No. Pemilik</th>
                            <th>Status</th>
                            <th>Google Maps</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($toko_list)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: #999;">
                                    <i class="fas fa-store-slash" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                    <div>Belum ada toko yang tercover</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($toko_list as $toko): ?>
                            <tr>
                                <td>
                                    <?php if ($toko['foto_toko']): ?>
                                        <img src="<?= htmlspecialchars($toko['foto_toko']) ?>" 
                                             alt="Foto <?= htmlspecialchars($toko['nama_toko']) ?>" 
                                             class="toko-foto" 
                                             onclick="openImageModal('<?= htmlspecialchars($toko['foto_toko']) ?>')">
                                    <?php else: ?>
                                        <div class="no-foto">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($toko['nama_toko']) ?></strong>
                                    <br><small><?= htmlspecialchars($user['username']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($toko['alamat']) ?></td>
                                <td><strong><?= htmlspecialchars($toko['no_pemilik']) ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?= $toko['status'] === 'aktif' ? 'aktif' : 'tidak' ?>">
                                        <?= ucfirst($toko['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($toko['lat'] && $toko['lng']): ?>
                                        <a href="https://maps.google.com/?q=<?= $toko['lat'] ?>,<?= $toko['lng'] ?>" 
                                           target="_blank" class="maps-link">
                                            <i class="fas fa-map-marker-alt"></i>
                                            Buka Maps
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Image Modal -->
    <div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; justify-content: center; align-items: center;">
        <img id="modalImage" style="max-width: 90%; max-height: 90%; border-radius: 10px;">
        <button onclick="closeImageModal()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 2rem; cursor: pointer;">×</button>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize Map
        const map = L.map('map').setView([-7.250475, 112.788462], 11); // Default Surabaya
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add markers from PHP data
        const tokoData = <?= json_encode($toko_list) ?>;
        
        tokoData.forEach(toko => {
            if (toko.lat && toko.lng) {
                const marker = L.marker([toko.lat, toko.lng])
                    .addTo(map)
                    .bindPopup(`
                        <div style="min-width: 200px;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #333;">${toko.nama_toko}</h4>
                            <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem;">📍 ${toko.alamat}</p>
                            <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem;">📱 ${toko.no_pemilik}</p>
                            <a href="https://maps.google.com/?q=${toko.lat},${toko.lng}" 
                               target="_blank" 
                               style="background: #1a73e8; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 5px; font-size: 0.85rem;">
                                Buka Google Maps
                            </a>
                        </div>
                    `);
            }
        });

        // Fit map to all markers
        if (tokoData.length > 0) {
            const group = new L.featureGroup(tokoData.map(t => 
                t.lat && t.lng ? L.marker([t.lat, t.lng]) : null
            ).filter(Boolean));
            map.fitBounds(group.getBounds().pad(0.1));
        }

        // Image modal functions
        function openImageModal(src) {
            document.getElementById('modalImage').src
                                    }