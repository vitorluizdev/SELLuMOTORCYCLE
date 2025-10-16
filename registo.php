<?php 
require_once 'config/db.php';

$erro = '';
$sucesso = '';

if ($_POST) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $telefone = trim($_POST['telefone']);
    
    // Validações
    if (empty($nome) || empty($email) || empty($password)) {
        $erro = 'Todos os campos obrigatórios devem ser preenchidos.';
    } elseif (strlen($password) < 6) {
        $erro = 'A password deve ter pelo menos 6 caracteres.';
    } else {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, email, password, telefone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $passwordHash, $telefone]);
            $sucesso = 'Conta criada com sucesso! Pode fazer login agora.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'Este email já está registado.';
            } else {
                $erro = 'Erro ao criar conta. Tente novamente.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar - <?= SITE_NOME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏍️</text></svg>">
    
    <style>
        /* CSS Base - CORES IDÊNTICAS AO INDEX */
        :root {
            --primary-black: #000000;
            --secondary-black: #1a1a1a;
            --primary-yellow: #FFD700;
            --secondary-yellow: #FFC107;
            --pure-white: #FFFFFF;
            --off-white: #F8F9FA;
            --light-gray: #E0E0E0;
            --medium-gray: #9E9E9E;
            --dark-gray: #424242;
            --success-green: #4CAF50;
            --danger-red: #F44336;
            --warning-orange: #FF9800;
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            --shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.25);
            --shadow-yellow: 0 4px 20px rgba(255, 215, 0, 0.3);
            --transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        /* Reset e configurações base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: auto;
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        body.auth-page {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, var(--primary-black) 0%, var(--secondary-black) 100%);
            padding: 20px 0;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Background Effects - IGUAL AO INDEX */
        body.auth-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 215, 0, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 193, 7, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 235, 59, 0.15) 0%, transparent 50%);
            animation: float 8s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(2deg); }
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 40px);
            padding-top: 20px;
            padding-bottom: 40px;
            position: relative;
            z-index: 2;
        }
        
        .form-container {
            background: var(--pure-white) !important;
            border-radius: 20px;
            box-shadow: 
                var(--shadow-hover),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 40px;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            position: relative;
            overflow: visible;
            border: 3px solid var(--primary-yellow);
            animation: slideInUp 0.6s ease-out;
        }

        /* Garantir fundo branco puro */
        .form-container::before {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            background: var(--pure-white);
            border-radius: 23px;
            z-index: -1;
            border: 3px solid var(--primary-yellow);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 32px;
            position: relative;
            z-index: 2;
        }
        
        /* ===== LOGO CORRIGIDA - SEM BRANCO! ===== */
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 24px;
            position: relative;
            transition: var(--transition);
            animation: logoEntrance 1s ease-out;
            padding: 10px;
        }

        .logo-container:hover {
            transform: scale(1.05);
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1;
            text-align: center;
        }

        .logo-main {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -1px;
            /* ❌ REMOVIDO O GRADIENTE COM BRANCO */
            /* background: linear-gradient(135deg, var(--primary-yellow), var(--pure-white), var(--secondary-yellow)); */
            
            /* ✅ SOLUÇÃO: COR DOURADA SÓLIDA */
            color: var(--primary-yellow) !important;
            text-shadow: 
                2px 2px 4px rgba(255, 215, 0, 0.3),
                0 0 20px rgba(255, 215, 0, 0.4),
                0 0 30px rgba(255, 215, 0, 0.2);
            position: relative;
            
            /* Garantir que não há gradiente */
            background: none !important;
            -webkit-background-clip: unset !important;
            -webkit-text-fill-color: var(--primary-yellow) !important;
            background-clip: unset !important;
        }

        .logo-highlight {
            color: var(--primary-yellow) !important;
            font-size: 2.2rem;
            text-shadow: 
                0 0 20px rgba(255, 215, 0, 0.8),
                0 0 30px rgba(255, 215, 0, 0.6),
                2px 2px 4px rgba(255, 215, 0, 0.4);
            animation: glow 2s ease-in-out infinite alternate;
            
            /* Forçar cor dourada */
            -webkit-text-fill-color: var(--primary-yellow) !important;
        }

        .logo-sub {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary-yellow) !important;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 4px;
            opacity: 0.8;
            text-shadow: 1px 1px 2px rgba(255, 215, 0, 0.3);
        }

        @keyframes logoEntrance {
            0% { opacity: 0; transform: translateY(-20px) scale(0.8); }
            50% { opacity: 0.8; transform: translateY(5px) scale(1.1); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes glow {
            from { text-shadow: 0 0 20px rgba(255, 215, 0, 0.8); }
            to { text-shadow: 0 0 30px rgba(255, 215, 0, 1), 0 0 40px rgba(255, 215, 0, 0.8); }
        }
        
        .form-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 8px;
        }
        
        .form-subtitle {
            color: var(--dark-gray);
            font-size: 1rem;
            line-height: 1.5;
        }
        
        .alert {
            padding: 16px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            flex-direction: column;
            border: 2px solid;
        }
        
        .alert-error {
            background: #fee;
            color: var(--danger-red);
            border-color: var(--danger-red);
        }
        
        .alert-success {
            background: #f0fff4;
            color: var(--success-green);
            border-color: var(--success-green);
            animation: slideInDown 0.4s ease-out, pulse 1s ease-in-out;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
            z-index: 2;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: var(--primary-black);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--pure-white) !important;
            color: var(--primary-black);
        }

        .form-input::placeholder {
            color: var(--medium-gray);
            opacity: 0.7;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-yellow);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
            background: var(--pure-white) !important;
        }
        
        .form-input.error {
            border-color: var(--danger-red);
            background: #fff5f5;
        }
        
        .form-input.success {
            border-color: var(--success-green);
            background: #f0fff4;
        }
        
        .input-hint {
            color: var(--medium-gray);
            font-size: 0.85rem;
            margin-top: 6px;
            display: block;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        
        .input-hint.error {
            color: var(--danger-red);
            font-weight: 600;
        }
        
        .input-hint.success {
            color: var(--success-green);
            font-weight: 600;
        }
        
        .btn {
            font-family: inherit;
            font-weight: 700;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-transform: none;
            letter-spacing: normal;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-yellow), var(--secondary-yellow));
            color: var(--primary-black);
            border: 2px solid var(--primary-black);
            padding: 16px 24px;
            font-size: 1rem;
            box-shadow: var(--shadow-yellow);
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.5);
            background: linear-gradient(45deg, var(--secondary-yellow), var(--primary-yellow));
        }
        
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-large {
            width: 100%;
            padding: 18px 24px;
            font-size: 1.1rem;
            position: relative;
            z-index: 2;
        }
        
        .loading {
            display: none;
            align-items: center;
            gap: 8px;
        }
        
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(0, 0, 0, 0.3);
            border-top: 2px solid var(--primary-black);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .text-center {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .mt-2 {
            margin-top: 16px;
        }
        
        .mt-3 {
            margin-top: 24px;
        }
        
        a {
            color: var(--primary-yellow);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        a:hover {
            color: var(--secondary-yellow);
            text-decoration: underline;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            body.auth-page {
                padding: 10px 0;
                align-items: flex-start;
            }
            
            .container {
                padding: 0 15px;
                padding-top: 10px;
                padding-bottom: 30px;
                min-height: auto;
            }
            
            .form-container {
                padding: 30px 25px;
                border-radius: 16px;
                max-width: 100%;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .form-subtitle {
                font-size: 0.9rem;
            }
            
            .logo-container {
                gap: 0.4rem;
            }
            
            .logo-main {
                font-size: 1.6rem;
            }
            
            .logo-highlight {
                font-size: 1.8rem;
            }
            
            .logo-sub {
                font-size: 0.7rem;
                letter-spacing: 1.5px;
            }
        }
        
        @media (max-width: 480px) {
            .form-container {
                padding: 25px 20px;
            }
            
            .form-title {
                font-size: 1.4rem;
            }
            
            .logo-container {
                gap: 0.3rem;
            }
            
            .logo-main {
                font-size: 1.4rem;
            }
            
            .logo-highlight {
                font-size: 1.6rem;
            }
            
            .logo-sub {
                font-size: 0.6rem;
                letter-spacing: 1px;
            }
        }
        
        /* Animações */
        @keyframes slideInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInDown {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        /* Ensure body can scroll properly */
        body {
            position: relative;
        }
        
        @media (max-height: 800px) {
            body.auth-page {
                align-items: flex-start;
                padding-top: 20px;
            }
            
            .container {
                align-items: flex-start;
                padding-top: 20px;
            }
        }
    </style>
</head>
<body class="auth-page">
    
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <a href="index.php" style="text-decoration: none;">
                    <div class="logo-container">
                        <div class="logo-text">
                            <div class="logo-main">
                                SELL<span class="logo-highlight">u</span>MOTORCYCLE
                            </div>
                            <div class="logo-sub">Marketplace</div>
                        </div>
                    </div>
                </a>
                <h2 class="form-title">Criar Nova Conta</h2>
                <p class="form-subtitle">Junte-se ao marketplace de motos mais confiável de Portugal</p>
            </div>
            
            <?php if ($erro): ?>
                <div class="alert alert-error">
                    <span>⚠️ <?= htmlspecialchars($erro) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="alert alert-success">
                    <span>✅ <?= htmlspecialchars($sucesso) ?></span>
                    <div class="mt-2">
                        <a href="login.php" class="btn btn-primary">Fazer Login Agora</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registoForm" autocomplete="on">
                <div class="form-group">
                    <label class="form-label">👤 Nome Completo*</label>
                    <input type="text" 
                           name="nome" 
                           class="form-input" 
                           placeholder="João Silva" 
                           required 
                           autocomplete="name"
                           value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">📧 Email*</label>
                    <input type="email" 
                           name="email" 
                           class="form-input" 
                           placeholder="joao@email.com" 
                           required 
                           autocomplete="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">🔒 Password*</label>
                    <input type="password" 
                           name="password" 
                           class="form-input" 
                           placeholder="Mínimo 6 caracteres" 
                           required 
                           minlength="6"
                           autocomplete="new-password">
                    <small class="input-hint" id="passwordHint">
                        Mínimo 6 caracteres
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📞 Telefone</label>
                    <input type="tel" 
                           name="telefone" 
                           class="form-input" 
                           placeholder="912345678" 
                           pattern="[0-9]{9}" 
                           autocomplete="tel"
                           value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                    <small class="input-hint">
                        Opcional - 9 dígitos (sem prefixo)
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large" id="submitBtn">
                    <span class="btn-text">🚀 Criar Conta</span>
                    <span class="loading">
                        <span class="spinner"></span> Criando conta...
                    </span>
                </button>
            </form>
            
            <div class="text-center mt-3">
                <p style="color: var(--dark-gray); margin-bottom: 1rem; font-weight: 500;">
                    Já tem conta? 
                    <a href="login.php">Fazer login</a>
                </p>
                
                <p class="mt-2">
                    <a href="index.php" style="color: var(--primary-yellow); font-weight: 600;">← Voltar ao início</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Efeito de loading no botão
        document.getElementById('registoForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');
            
            // Mostrar loading
            btnText.style.display = 'none';
            loading.style.display = 'flex';
            btn.disabled = true;
        });

        // Validação frontend
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.required && this.value.trim() === '') {
                    this.classList.add('error');
                    this.classList.remove('success');
                } else if (this.value.trim() !== '') {
                    this.classList.remove('error');
                    this.classList.add('success');
                }
            });
            
            input.addEventListener('input', function() {
                this.classList.remove('error');
                if (this.value.trim() !== '') {
                    this.classList.add('success');
                } else {
                    this.classList.remove('success');
                }
            });
        });

        // Formatação do telefone (apenas números)
        const telefoneInput = document.querySelector('input[name="telefone"]');
        if (telefoneInput) {
            telefoneInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').slice(0, 9);
            });
        }

        // Auto-hide success alerts
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.3s ease-out';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });

        // Focus effects
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.01)';
                this.parentElement.style.transition = 'transform 0.2s ease-out';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Validação de password em tempo real
        const passwordInput = document.querySelector('input[name="password"]');
        const passwordHint = document.getElementById('passwordHint');
        
        if (passwordInput && passwordHint) {
            passwordInput.addEventListener('input', function() {
                const length = this.value.length;
                
                if (length === 0) {
                    this.classList.remove('error', 'success');
                    passwordHint.classList.remove('error', 'success');
                    passwordHint.textContent = 'Mínimo 6 caracteres';
                } else if (length < 6) {
                    this.classList.add('error');
                    this.classList.remove('success');
                    passwordHint.classList.add('error');
                    passwordHint.classList.remove('success');
                    passwordHint.textContent = `${length}/6 caracteres (mínimo 6)`;
                } else {
                    this.classList.remove('error');
                    this.classList.add('success');
                    passwordHint.classList.remove('error');
                    passwordHint.classList.add('success');
                    passwordHint.textContent = '✓ Password válida';
                }
            });
        }
    </script>
</body>
</html>