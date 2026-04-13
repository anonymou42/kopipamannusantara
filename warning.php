<?php
session_start();
// Opsional: Hapus session agar user benar-benar logout
// session_destroy(); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Dibatasi - Perlu Otorisasi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Bitcount Prop Double' rel='stylesheet'>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7fafc;
            /* Latar belakang gradasi halus */
            background-image: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #2d3748;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            /* Bayangan lebih dalam dan halus */
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 480px;
            text-align: center;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        /* Garis dekoratif di bagian atas */
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #ed8936 0%, #e53e3e 100%);
        }

        .icon-wrapper {
            background-color: #fff5f5;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 30px;
            border: 4px solid white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .icon-wrapper svg {
            width: 50px;
            height: 50px;
            color: #e53e3e; /* Warna ikon merah */
        }

        h1 {
            color: #c53030;
            font-family: 'Bitcount Prop Double';
            font-weight: 700;
            font-size: 35px;
            margin-bottom: 15px;
            margin-top: 0;
        }

        p.main-text {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        p.sub-text {
            color: #718096;
            font-size: 14px;
            margin-bottom: 40px;
            padding: 15px;
            background-color: #edf2f7;
            border-radius: 8px;
            border-left: 4px solid #cbd5e0;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            background: linear-gradient(90deg, #3182ce 0%, #2b6cb0 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(49, 130, 206, 0.5), 0 2px 4px -1px rgba(49, 130, 206, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px -3px rgba(49, 130, 206, 0.5), 0 4px 6px -2px rgba(49, 130, 206, 0.2);
            background: linear-gradient(90deg, #2b6cb0 0%, #2c5282 100%);
        }

        /* Responsive adjustment */
        @media (max-width: 480px) {
            .card {
                padding: 30px 20px;
            }
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h1 >Akses Tidak Diizinkan</h1>
        
        <p class="main-text">
            Oops! Kami tidak dapat menemukan akun Anda. 
            Sepertinya Anda belum terdaftar sebagai pengguna resmi. 
            Untuk keamanan dan privasi data, hanya pengguna yang telah diverifikasi yang dapat mengakses sistem ini.
        </p>
        
        <p class="sub-text">
            <strong>Hubungi Administrator Atau Tim IT Anda untuk verifikasi atau pengaturan ulang peran akun Anda.</strong>
        </p>
        
        <a href="index.php" class="btn">Kembali ke Halaman Login</a>
    </div>
</div>

</body>
</html>