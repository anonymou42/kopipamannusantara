<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Sistem Autentikasi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
        }

        .register-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .back-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255,255,255,0.8);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            color: #666;
        }

        .back-btn:hover {
            background: #fff;
            color: #f5576c;
            transform: translateX(-2px);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #f5576c;
            background: white;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 1.1rem;
        }

        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            border-radius: 2px;
            background: #e1e5e9;
            overflow: hidden;
            display: none;
        }

        .strength-weak { background: #ff4757; width: 25%; }
        .strength-fair { background: #ffa502; width: 50%; }
        .strength-good { background: #2ed573; width: 75%; }
        .strength-strong { background: #00d2d3; width: 100%; }

        .register-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 87, 108, 0.4);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .register-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-link a {
            color: #f5576c;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #e43f5d;
        }

        .message {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
            font-weight: 500;
        }

        .error-message {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 2rem 1.5rem;
                margin: 10px;
            }
            
            .register-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <button class="back-btn" onclick="window.location.href='index.php'">
            <i class="fas fa-arrow-left"></i>
        </button>
        
        <div class="register-header">
            <h1><i class="fas fa-user-plus"></i> Daftar Akun</h1>
            <p>Buat akun baru untuk melanjutkan</p>
        </div>

        <form id="registerForm" method="POST" action="proses_register.php">
            <div id="messageContainer"></div>
            
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Username" maxlength="50" required>
                <div class="password-strength" id="usernameStrength"></div>
            </div>
            
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Email (opsional)" maxlength="100">
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Password" maxlength="255" required>
                <div class="password-strength" id="passwordStrength"></div>
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock-open"></i>
                <input type="password" id="confirmPassword" name="confirm_password" placeholder="Konfirmasi Password" maxlength="255" required>
            </div>
            
            <button type="submit" class="register-btn" id="registerBtn">
                <span id="btnText">Daftar Akun</span>
            </button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="index.php">Masuk sekarang</a>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');

        // Password strength checker
        passwordInput.addEventListener('input', checkPasswordStrength);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        usernameInput.addEventListener('input', checkUsernameStrength);

        function checkPasswordStrength() {
            const password = passwordInput.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            strengthBar.style.display = password ? 'block' : 'none';
            
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;

            strengthBar.className = 'password-strength';
            switch(strength) {
                case 1: strengthBar.classList.add('strength-weak'); break;
                case 2: case 3: strengthBar.classList.add('strength-fair'); break;
                case 4: strengthBar.classList.add('strength-good'); break;
                case 5: strengthBar.classList.add('strength-strong'); break;
            }
        }

        function checkUsernameStrength() {
            const username = usernameInput.value;
            const strengthBar = document.getElementById('usernameStrength');
            
            strengthBar.style.display = username ? 'block' : 'none';
            
            let strength = username.length >= 3 ? 1 : 0;
            strengthBar.className = 'password-strength';
            if (strength === 1) {
                strengthBar.classList.add('strength-strong');
            } else {
                strengthBar.classList.add('strength-weak');
            }
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmPasswordInput.value;
            
            if (confirm && password !== confirm) {
                confirmPasswordInput.style.borderColor = '#ff4757';
            } else {
                confirmPasswordInput.style.borderColor = '#e1e5e9';
            }
        }

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const username = usernameInput.value.trim();
            const email = emailInput.value.trim();
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const btn = document.getElementById('registerBtn');
            const btnText = document.getElementById('btnText');
            const messageContainer = document.getElementById('messageContainer');

            messageContainer.innerHTML = '';

            // Validasi
            if (username.length < 3 || username.length > 50) {
                showMessage('Username harus 3-50 karakter!', 'error');
                e.preventDefault();
                return;
            }

            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                showMessage('Username hanya boleh huruf, angka, dan underscore!', 'error');
                e.preventDefault();
                return;
            }

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showMessage('Format email tidak valid!', 'error');
                e.preventDefault();
                return;
            }

            if (password.length < 8) {
                showMessage('Password minimal 8 karakter!', 'error');
                e.preventDefault();
                return;
            }

            if (password !== confirmPassword) {
                showMessage('Konfirmasi password tidak cocok!', 'error');
                e.preventDefault();
                return;
            }

            // Disable button
            btn.disabled = true;
            btnText.innerHTML = '<span class="loading"></span> Membuat akun...';
        });

        function showMessage(message, type) {
            const messageContainer = document.getElementById('messageContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;
            messageDiv.textContent = message;
            messageContainer.appendChild(messageDiv);
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }

        // Show server messages
        <?php if (isset($_GET['error'])): ?>
            showMessage('<?= htmlspecialchars($_GET['error']) ?>', 'error');
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            showMessage('<?= htmlspecialchars($_GET['success']) ?>', 'success');
        <?php endif; ?>
    </script>
</body>
</html>