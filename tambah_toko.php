<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

// Proses Tambah Toko
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_toko = $_POST['nama_toko'];
    $jenis_toko = $_POST['jenis_toko'];
    // Karena kode_area di-generate otomatis, kita set null dulu atau abaikan dari POST
    $kode_area = null; 
    $wilayah = $_POST['wilayah'] ?? null;
    $alamat = $_POST['alamat'];
    $no_pemilik = $_POST['no_pemilik'];
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $sales_id = $_SESSION['user_id']; 
    
    // Default foto kosong
    $foto_toko = null;

    // Cek jika ada upload foto
    if (!empty($_FILES['foto_toko']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_extension = pathinfo($_FILES['foto_toko']['name'], PATHINFO_EXTENSION);
        $new_filename = $target_dir . "toko_" . time() . "_" . uniqid() . "." . $file_extension;

        if (move_uploaded_file($_FILES['foto_toko']['tmp_name'], $new_filename)) {
            $foto_toko = $new_filename;
        }
    }

    try {
        $pdo->beginTransaction(); // Gunakan transaksi agar data konsisten

        // 1. Insert toko (kode_area dikosongkan dulu)
        $stmt = $pdo->prepare("INSERT INTO toko (nama_toko, jenis_toko, kode_area, wilayah, alamat, no_pemilik, status, keterangan, foto_toko, lat, lng, sales_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $nama_toko, $jenis_toko, $kode_area, $wilayah, 
            $alamat, $no_pemilik, $status, $keterangan, 
            $foto_toko, $lat, $lng, $sales_id
        ]);

        // 2. Ambil ID toko yang baru dibuat
        $new_toko_id = $pdo->lastInsertId();

        // 3. Ambil area_code milik sales dari tabel users
        $user_stmt = $pdo->prepare("SELECT area_code FROM users WHERE id = ?");
        $user_stmt->execute([$sales_id]);
        $area_prefix = $user_stmt->fetchColumn();

        // Jika sales tidak punya area_code, gunakan default 'GEN' (General)
        if (!$area_prefix) $area_prefix = "GEN";

        // 4. Update kode_area otomatis (Contoh: JAM-001)
        $kode_otomatis = strtoupper($area_prefix) . '-' . str_pad($new_toko_id, 3, '0', STR_PAD_LEFT);
        
        $update_stmt = $pdo->prepare("UPDATE toko SET kode_area = ? WHERE id = ?");
        $update_stmt->execute([$kode_otomatis, $new_toko_id]);

        $pdo->commit(); // Simpan permanen

        // Redirect sukses
        $redirect = ($_SESSION['role'] === 'admin') ? 'dashboard_admin.php' : 'dashboard_sales.php';
        header("Location: $redirect?success=Data toko $kode_otomatis berhasil ditambahkan");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack(); // Batalkan jika error
        $_SESSION['error'] = 'Gagal: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Toko Baru</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding: 40px; }
        .form-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h2 { color: #2d3748; margin-top: 0; border-bottom: 2px solid #48bb78; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #4a5568; }
        input, textarea, select { width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 8px; box-sizing: border-box; font-size: 1rem; }
        textarea { resize: vertical; }
        .btn-container { display: flex; gap: 10px; margin-top: 20px; }
        .btn-submit { background: #48bb78; color: white; border: none; flex: 2; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .btn-cancel { background: #edf2f7; color: #4a5568; text-decoration: none; flex: 1; padding: 12px; border-radius: 8px; text-align: center; font-size: 0.9rem; }
        .btn-submit:hover { background: #38a169; }

        .id-display {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
            color: white !important;
            font-family: 'Courier New', monospace !important;
            font-weight: 600 !important;
            text-align: center;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="form-card">
        <h2><i class="fas fa-plus"></i> Tambah Toko Baru</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label><i class="fas fa-store"></i> Nama Toko <span style="color:#e53e3e;">*</span></label>
                <input type="text" name="nama_toko" required placeholder="Masukkan nama toko">
            </div>

            <div class="form-group">
                <label><i class="fas fa-tags"></i> Jenis Toko <span style="color:#e53e3e;">*</span></label>
                <select name="jenis_toko" required>
                    <option value="">Pilih Jenis Toko</option>
                    <option value="Grosir">🛒 Grosir</option>
                    <option value="Eceran">🏪 Eceran</option>
                    <option value="Agen">📦 Agen/Distributor</option>
                    <option value="Lainnya">📋 Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-phone"></i> No. HP Pemilik <span style="color:#e53e3e;">*</span></label>
                <input type="tel" name="no_pemilik" placeholder="08xxxxxxxxx" required>
            </div>

            <!-- <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Kode Area <span style="color:#e53e3e;">*</span></label>
                <input type="text" name="kode_area" 
                    placeholder="Contoh: DKI-001, BDG-045, SBY-123" required>
                <small style="color:#666;">Format: KOTA-NNN (3 digit angka)</small>
            </div> -->

             <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Wilayah <span style="color:#e53e3e;">*</span></label>
                <input type="text" name="wilayah" placeholder="Isi dengan wilayah anda ketahui" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Alamat <span style="color:#e53e3e;">*</span></label>
                <textarea id="alamat" name="alamat" rows="3" placeholder="Ambil alamat dari GPS..." readonly required style="background: #f7fafc; color: #666; cursor: not-allowed;"></textarea>
                <small style="color: #666; font-style: italic;">
                    Alamat akan terisi otomatis setelah mengambil lokasi GPS
                </small>
            </div>

             <!-- Koordinat Maps (Opsional) -->
            <div class="form-group coords-group">
                <label><i class="fas fa-map-marker-alt"></i> Koordinat Maps </label>
                <div class="coords-display">
                    <input type="text" id="lat" name="lat" placeholder="Latitude" readonly>
                    <input type="text" id="lng" name="lng" placeholder="Longitude" readonly>
                    <button type="button" class="btn-maps" onclick="getCurrentLocation()">
                        <i class="fas fa-location-arrow"></i> Ambil Lokasi Saya
                    </button>
                    <!-- <button type="button" class="btn-maps" onclick="openMaps()" style="background: #10b981;">
                        <i class="fas fa-external-link-alt"></i> Buka Google Maps
                    </button> -->
                </div>
                <small style="color: #666; font-style: italic;">Klik "Ambil Lokasi Saya" untuk mengisi koordinat GPS otomatis</small>
            </div>
            
            <!-- UPLOAD FOTO -->
            <div class="form-group photo-section">
                <label><i class="fas fa-camera"></i> Foto Toko</label>
                <div class="no-photo">
                    <i class="fas fa-image" style="font-size: 4rem; color: #cbd5e0;"></i>
                    <p>Tambahkan foto toko</p>
                </div>
                <input type="file" id="foto_toko" name="foto_toko" accept="image/*" capture="environment" style="display: none;" required>
                <div class="file-info">
                    <small>Format: Kamera Langsung (Max: 5MB) - Opsional</small>
                    <label for="foto_toko" class="btn-upload">
                        <i class="fas fa-camera"></i> Ambil Foto Toko Sekarang
                    </label>
                </div>
                
                <!-- Preview foto -->
                <div id="photoPreview" class="photo-preview" style="display: none;">
                    <img id="previewImg" src="" alt="Preview">
                    <button type="button" class="btn-remove-preview" onclick="clearPreview()">×</button>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-toggle-on"></i> Status <span style="color:#e53e3e;">*</span></label>
                <select name="status" class="select-status">
                    <option value="aktif">✅ Aktif</option>
                    <option value="tidak_aktif">❌ Tidak Aktif</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-sticky-note"></i> Keterangan</label>
                <textarea name="keterangan" rows="3" placeholder="Catatan tambahan..."></textarea>
            </div>

            <div class="btn-container">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Tambah Toko Baru
                </button>
                <a href="dashboard_sales.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
        </form>
    </div>

    <style>
        .coords-display {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: end;
        }

        .coords-display input {
            flex: 1;
            min-width: 120px;
            background: linear-gradient(135deg, #f0fff4, #f0f9ff);
            font-family: monospace;
            font-weight: 600;
            color: #059669;
        }

        .btn-maps {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .btn-maps:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }

        .coords-group small {
            display: block;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: rgba(72, 187, 120, 0.1);
            border-radius: 8px;
            border-left: 4px solid #48bb78;
        }

        .form-card {
            max-width: 700px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .form-card h2 {
            color: #2d3748;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .photo-section {
            background: rgba(72,187,120,0.05);
            padding: 1.5rem;
            border-radius: 16px;
            border: 2px dashed #48bb78;
        }

        .no-photo {
            text-align: center;
            padding: 2rem;
            border: 3px dashed #e2e8f0;
            border-radius: 16px;
            background: rgba(248,250,252,0.5);
            margin-bottom: 1rem;
        }

        .photo-preview {
            text-align: center;
            padding: 2rem;
            border: 3px dashed #48bb78;
            border-radius: 16px;
            background: rgba(72,187,120,0.05);
            margin-bottom: 1rem;
            position: relative;
        }

        .photo-preview img {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .btn-upload {
            display: inline-block;
            margin: 0.5rem 0.25rem 0 0;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(72,187,120,0.4);
        }

        .btn-remove-preview {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .btn-container {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 2px solid #e2e8f0;
        }

        .btn-submit, .btn-cancel {
            flex: 1;
            padding: 1.25rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            box-shadow: 0 8px 25px rgba(72,187,120,0.4);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(72,187,120,0.6);
        }

        .btn-cancel {
            background: #f7fafc;
            color: #4a5568;
            border: 2px solid #e2e8f0;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: #edf2f7;
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        @media (max-width: 768px) {
            .btn-container {
                flex-direction: column;
            }
            .photo-preview img {
                width: 100%;
                max-width: 250px;
            }
        }

        textarea[readonly] {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important;
            color: #64748b !important;
            cursor: not-allowed !important;
            border-color: #cbd5e0 !important;
            opacity: 0.8;
        }

        textarea[readonly]:focus {
            border-color: #94a3b8 !important;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.1) !important;
            transform: none !important;
        }
    </style>
    <script>
    // Preview foto saat pilih file
    document.getElementById('foto_toko').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validasi ukuran file (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran file maksimal 5MB!');
                this.value = '';
                return;
            }
            
            // Preview gambar
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('photoPreview');
                const previewImg = document.getElementById('previewImg');
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    function clearPreview() {
        document.getElementById('foto_toko').value = '';
        document.getElementById('photoPreview').style.display = 'none';
    }

    function hapusFoto() {
        if (confirm('Yakin ingin menghapus foto toko ini?')) {
            document.querySelector('input[name="hapus_foto"]').value = '1';
            document.querySelector('.current-photo').innerHTML = `
                <div class="no-photo">
                    <i class="fas fa-image" style="font-size: 4rem; color: #cbd5e0;"></i>
                    <p>Foto dihapus</p>
                </div>
            `;
        }
    }

    // FUNGSI BARU: Ambil lokasi GPS saat ini dan isi ke input
    function getCurrentLocation() {
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    
    if (navigator.geolocation) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari Lokasi...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;

                // 1. Isi input Latitude dan Longitude
                document.getElementById('lat').value = latitude;
                document.getElementById('lng').value = longitude;

                // 2. Ambil Nama Alamat berdasarkan Koordinat (Reverse Geocoding)
                fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.display_name) {
                            // Isi textarea alamat otomatis
                            document.getElementById('alamat').value = data.display_name;
                        }
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        alert("Lokasi dan alamat berhasil diperbarui!");
                    })
                    .catch(error => {
                        console.error('Error Geocoding:', error);
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        alert("Gagal mengambil nama alamat, silakan isi manual.");
                    });
            },
            (error) => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                alert("Gagal mendapatkan lokasi: " + error.message);
            },
            { enableHighAccuracy: true }
        );
    } else {
        alert("Geolocation tidak didukung oleh browser ini.");
    }
}

function openMaps() {
    const lat = document.getElementById('lat').value;
    const lng = document.getElementById('lng').value;
    if (lat && lng) {
        window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
    } else {
        alert("Koordinat belum tersedia.");
    }
}

    // Notifikasi custom
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            ${message}
        `;
        document.querySelector('.form-card').insertBefore(notification, document.querySelector('form'));
        
        // Auto hide setelah 4 detik
        setTimeout(() => {
            notification.style.transition = 'opacity 0.5s';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 500);
        }, 4000);
    }

    // Auto-hide alerts setelah 5 detik
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>
</body>
</html>