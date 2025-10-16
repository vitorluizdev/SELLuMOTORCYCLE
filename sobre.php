<?php 
require_once 'config/db.php';

// Buscar estatísticas atualizadas
$totalAnuncios = $pdo->query("SELECT COUNT(DISTINCT id) FROM anuncios WHERE ativo = 1")->fetchColumn();
$totalVendedores = $pdo->query("SELECT COUNT(DISTINCT id) FROM utilizadores WHERE tipo = 'cliente'")->fetchColumn();
$totalVisitas = "150K+";
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós - <?= SITE_NOME ?> | Marketplace de Motos em Portugal</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏍️</text></svg>">
    
    <style>
        /* ===============================================
           VARIÁVEIS CSS (MANTIDAS DO INDEX)
           =============================================== */
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
            --border-radius: 10px;
            --shadow: 0 3px 15px rgba(0, 0, 0, 0.12);
            --shadow-hover: 0 6px 25px rgba(0, 0, 0, 0.2);
            --shadow-yellow: 0 3px 15px rgba(255, 215, 0, 0.3);
            --transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        body {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            line-height: 1.6;
            color: var(--primary-black);
            background: linear-gradient(135deg, var(--primary-black) 0%, var(--secondary-black) 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ===============================================
           HEADER (MESMO DO INDEX)
           =============================================== */
        .header {
            background: var(--primary-black);
            backdrop-filter: blur(15px);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 1rem 0;
            border-bottom: 2px solid var(--primary-yellow);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            min-height: 80px;
            display: flex;
            align-items: center;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            min-height: 60px;
            gap: 2rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex: 1;
        }

        .custom-logo-placeholder {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #FFD700, #FFC107);
            border: 3px solid var(--primary-black);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            flex-shrink: 0;
            cursor: pointer;
            transition: var(--transition);
        }

        .custom-logo-placeholder:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5);
            border-color: var(--secondary-black);
        }

        .custom-logo-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 9px;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--pure-white);
            position: relative;
            padding: 0.5rem 0;
            cursor: pointer;
        }

        .logo-container {
            display: flex;
            align-items: center;
            position: relative;
            transition: var(--transition);
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .logo-main {
            font-size: 1.8rem;
            font-weight: 900;
            letter-spacing: -1px;
            color: #FFD700 !important;
            background: none !important;
            -webkit-text-fill-color: #FFD700 !important;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5), 
                         0 0 10px rgba(255, 215, 0, 0.3),
                         0 0 15px rgba(255, 193, 7, 0.2);
            position: relative;
            filter: brightness(1.1) saturate(1.1);
        }

        .logo-highlight {
            font-size: 2.2rem;
            text-shadow: 0 0 15px rgba(255, 215, 0, 0.8),
                         0 0 25px rgba(255, 193, 7, 0.6),
                         0 0 35px rgba(255, 165, 0, 0.4);
            animation: balancedGlow 2s ease-in-out infinite alternate;
            filter: brightness(1.3) contrast(1.2);
            display: inline-block;
            position: relative;
        }

        .logo-sub {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--light-gray);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: -2px;
            opacity: 0.9;
        }

        @keyframes balancedGlow {
            from { 
                text-shadow: 0 0 15px rgba(255, 215, 0, 0.8),
                             0 0 25px rgba(255, 193, 7, 0.6),
                             0 0 35px rgba(255, 165, 0, 0.4);
                filter: brightness(1.3) contrast(1.2);
            }
            to { 
                text-shadow: 0 0 20px rgba(255, 215, 0, 1), 
                             0 0 30px rgba(255, 193, 7, 0.8),
                             0 0 40px rgba(255, 165, 0, 0.6),
                             0 0 50px rgba(218, 165, 32, 0.4);
                filter: brightness(1.4) contrast(1.3);
            }
        }

        .nav-container {
            position: relative;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 1rem;
            align-items: center;
            flex-wrap: nowrap;
        }

        .nav-menu a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            white-space: nowrap;
            border: 2px solid transparent;
            background: transparent;
            color: var(--pure-white);
        }

        .nav-menu a:hover {
            background: var(--primary-yellow);
            color: var(--primary-black);
            border-color: var(--primary-yellow);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        .nav-menu a.active {
            background: transparent;
            color: var(--pure-white);
            border-color: transparent;
            text-decoration: underline;
            text-decoration-color: var(--primary-yellow);
            text-underline-offset: 4px;
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-toggle {
            background: var(--primary-yellow) !important;
            color: var(--primary-black) !important;
            border: 2px solid var(--primary-black) !important;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.95rem;
            font-family: inherit;
        }

        .user-toggle:hover {
            background: var(--secondary-yellow) !important;
            transform: translateY(-2px);
            box-shadow: var(--shadow-yellow);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background: var(--pure-white);
            border: 2px solid var(--primary-yellow);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-hover);
            min-width: 200px;
            z-index: 1001;
        }

        .dropdown-content.show {
            display: block;
            animation: dropdownFadeIn 0.3s ease-out;
        }

        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-content a {
            display: flex !important;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem !important;
            color: var(--primary-black) !important;
            text-decoration: none;
            font-weight: 600;
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
            border-radius: 0 !important;
            background: transparent !important;
            min-height: auto !important;
            white-space: nowrap;
            transform: none !important;
            box-shadow: none !important;
            font-size: 0.95rem;
        }

        .dropdown-content a:last-child {
            border-bottom: none;
        }

        .dropdown-content a:hover {
            background: var(--primary-yellow) !important;
            color: var(--primary-black) !important;
            transform: none !important;
        }

        .dropdown-content a.logout-link:hover {
            background: var(--danger-red) !important;
            color: var(--pure-white) !important;
        }

        /* ===============================================
           MAIN CONTENT STYLES
           =============================================== */
        .main-content {
            margin-top: 80px;
            min-height: calc(100vh - 80px);
        }

        .hero-about {
            background: linear-gradient(135deg, var(--primary-black) 0%, var(--secondary-black) 100%);
            padding: 5rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-about::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 70%, rgba(255, 215, 0, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(255, 193, 7, 0.1) 0%, transparent 50%);
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        .hero-about-content {
            position: relative;
            z-index: 2;
        }

        .hero-about-title {
            font-size: 3.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #FFD700, #FFC107);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-about-subtitle {
            font-size: 1.4rem;
            color: var(--light-gray);
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            text-transform: none;
            letter-spacing: normal;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: var(--primary-black);
            border: 2px solid var(--primary-black);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.5);
            background: linear-gradient(135deg, #FFDD44, #FFB347);
        }

        /* ===============================================
           SECTIONS
           =============================================== */
        .section {
            padding: 5rem 0;
            position: relative;
        }

        .section-white {
            background: linear-gradient(to bottom, rgba(255, 215, 0, 0.05), var(--pure-white));
        }

        .section-dark {
            background: linear-gradient(135deg, var(--secondary-black), #2a2a2a);
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 2.8rem;
            font-weight: 900;
            background: linear-gradient(45deg, #FFD700, #FFC107);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .section-title-white {
            color: var(--pure-white) !important;
            background: none !important;
            -webkit-text-fill-color: var(--pure-white) !important;
            -webkit-background-clip: initial !important;
            background-clip: initial !important;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--dark-gray);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .section-subtitle-white {
            color: var(--light-gray) !important;
        }

        /* ===============================================
           GRID LAYOUTS
           =============================================== */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: var(--pure-white);
            padding: 2.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-yellow);
        }

        .feature-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, #FFD700, #FFC107);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--dark-gray);
            line-height: 1.6;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            background: rgba(255, 215, 0, 0.1);
            padding: 2rem;
            border-radius: 15px;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            color: var(--primary-yellow);
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .stat-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--light-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .team-card {
            background: var(--pure-white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            text-align: center;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }

        .team-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700, #FFC107);
            margin: 2rem auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-black);
            border: 4px solid var(--pure-white);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .team-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 0.5rem;
        }

        .team-role {
            font-size: 1rem;
            color: var(--primary-yellow);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .team-description {
            color: var(--dark-gray);
            line-height: 1.6;
            padding: 0 1.5rem 2rem;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .value-card {
            background: rgba(255, 215, 0, 0.1);
            padding: 2rem;
            border-radius: 15px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            text-align: center;
            transition: var(--transition);
        }

        .value-card:hover {
            background: rgba(255, 215, 0, 0.2);
            border-color: var(--primary-yellow);
            transform: translateY(-5px);
        }

        .value-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-yellow);
        }

        .value-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--pure-white);
            margin-bottom: 1rem;
        }

        .value-description {
            color: var(--light-gray);
            line-height: 1.6;
        }

        /* ===============================================
           FOOTER (MESMO DO INDEX)
           =============================================== */
        .footer {
            background: var(--primary-black);
            color: var(--pure-white);
            text-align: center;
            padding: 3rem 0;
            border-top: 3px solid var(--primary-yellow);
        }

        .footer .logo-container {
            justify-content: center;
            margin-bottom: 2rem;
        }

        .footer .logo-main {
            color: #FFD700 !important;
            background: none !important;
            -webkit-text-fill-color: #FFD700 !important;
            -webkit-background-clip: initial !important;
            background-clip: initial !important;
            filter: brightness(1.1) saturate(1.1);
        }

        .footer .logo-sub {
            color: var(--light-gray);
        }

        /* ===============================================
           ANIMATIONS
           =============================================== */
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* ===============================================
           RESPONSIVIDADE
           =============================================== */
        @media (max-width: 768px) {
            .hero-about-title {
                font-size: 2.5rem;
            }

            .hero-about-subtitle {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 2.2rem;
            }

            .features-grid,
            .team-grid,
            .values-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .feature-card,
            .team-card,
            .value-card {
                padding: 2rem;
            }

            .header-content {
                flex-wrap: wrap;
                gap: 1rem;
                padding: 0 15px;
            }

            .nav-menu {
                flex-wrap: wrap;
                gap: 0.75rem;
                justify-content: center;
                width: 100%;
            }

            .nav-menu a {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }

            .logo-main {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .hero-about {
                padding: 3rem 0;
            }

            .section {
                padding: 3rem 0;
            }

            .feature-card,
            .team-card,
            .value-card {
                padding: 1.5rem;
            }
        }

        /* ===============================================
           NOTIFICAÇÕES
           =============================================== */
        .custom-alert {
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(135deg, var(--success-green), #66BB6A);
            color: var(--pure-white);
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-hover);
            z-index: 2000;
            display: none;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            max-width: 400px;
            animation: slideInRight 0.5s ease-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }

        .custom-alert.show {
            display: flex;
        }

        .custom-alert .close-btn {
            background: none;
            border: none;
            color: var(--pure-white);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            margin-left: auto;
        }
    </style>
</head>
<body>
    <!-- Alerta personalizado -->
    <div id="customAlert" class="custom-alert">
        <span id="alertMessage"></span>
        <button class="close-btn" onclick="hideAlert()">&times;</button>
    </div>

    <!-- Header (mesmo do index) -->
    <header class="header">
        <div class="header-content">
            <!-- Seção da Logo -->
            <div class="logo-section">
                <!-- Placeholder para logo personalizada -->
                <div class="custom-logo-placeholder" id="customLogoPlaceholder">
                    <img src="assets/logo.png" alt="Logo Personalizada">
                </div>
                
                <!-- Logo texto com estilo do index -->
                <a href="index.php" class="logo">
                    <div class="logo-container">
                        <div class="logo-text">
                            <div class="logo-main">
                                SELL<span class="logo-highlight">u</span>MOTORCYCLE
                            </div>
                            <div class="logo-sub">Marketplace</div>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Navegação -->
            <nav class="nav-container">
                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="index.php#anuncios">Anúncios</a></li>
                    <li><a href="sobre.php" class="active">Sobre</a></li>
                    <li><a href="#contato">Contato</a></li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li class="user-dropdown">
                            <button class="user-toggle" onclick="toggleUserMenu()">
                                <span>👤 <?= htmlspecialchars(getUserName()) ?></span>
                                <span id="dropdownArrow">▼</span>
                            </button>
                            <div class="dropdown-content" id="userDropdown">
                                <a href="perfil.php">👤 Meu Perfil</a>
                                <a href="anuncio.php">➕ Criar Anúncio</a>
                                <?php if (isAdmin()): ?>
                                    <a href="admin/">👨‍💼 Painel Admin</a>
                                <?php endif; ?>
                                <a href="#" onclick="confirmLogout(event)" class="logout-link">🚪 Sair</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php">Entrar</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section Otimizada -->
        <section class="hero-about">
            <div class="container">
                <div class="hero-about-content">
                    <!-- Logo centralizada no hero -->
                    <div class="fade-in-up" style="margin-bottom: 2rem;">
                        <div class="custom-logo-placeholder" style="width: 100px; height: 100px; margin: 0 auto; border-radius: 25px; box-shadow: 0 10px 30px rgba(255, 215, 0, 0.5);">
                            <img src="assets/logo.png" alt="SellUMotorcycle Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 22px;">
                        </div>
                    </div>
                    
                    <h1 class="hero-about-title fade-in-up" style="animation-delay: 0.1s;">
                        Sobre o <span style="color: #FFD700; background: none; -webkit-text-fill-color: #FFD700; -webkit-background-clip: initial; background-clip: initial; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5), 0 0 10px rgba(255, 215, 0, 0.3), 0 0 15px rgba(255, 193, 7, 0.2); filter: brightness(1.1) saturate(1.1);">SELL<span style="font-size: 1.1em; text-shadow: 0 0 15px rgba(255, 215, 0, 0.8), 0 0 25px rgba(255, 193, 7, 0.6), 0 0 35px rgba(255, 165, 0, 0.4); animation: balancedGlow 2s ease-in-out infinite alternate; filter: brightness(1.3) contrast(1.2); display: inline-block;">u</span>MOTORCYCLE</span>
                    </h1>
                    <p class="hero-about-subtitle fade-in-up" style="animation-delay: 0.2s;">
                        O marketplace líder em Portugal para compra e venda de motocicletas. 
                        Conectamos apaixonados por motos em todo o país com segurança e transparência.
                    </p>
                    <div class="fade-in-up" style="animation-delay: 0.3s;">
                        <a href="anuncio.php" class="btn btn-primary pulse-animation">
                            🏍️ Comece a Vender Agora
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Estatísticas -->
        <section style="background: linear-gradient(135deg, #FFD700, #FFC107, #FFDD44); padding: 3rem 0; border-top: 3px solid var(--primary-black); border-bottom: 3px solid var(--primary-black);">
            <div class="container">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; text-align: center;">
                    <div>
                        <div style="font-size: 3rem; font-weight: 900; color: var(--primary-black);"><?= $totalAnuncios ?></div>
                        <div style="font-weight: 700; color: var(--secondary-black); text-transform: uppercase; letter-spacing: 1px;">Motos Disponíveis</div>
                    </div>
                    <div>
                        <div style="font-size: 3rem; font-weight: 900; color: var(--primary-black);"><?= $totalVendedores ?></div>
                        <div style="font-weight: 700; color: var(--secondary-black); text-transform: uppercase; letter-spacing: 1px;">Vendedores Ativos</div>
                    </div>
                    <div>
                        <div style="font-size: 3rem; font-weight: 900; color: var(--primary-black);"><?= $totalVisitas ?></div>
                        <div style="font-weight: 700; color: var(--secondary-black); text-transform: uppercase; letter-spacing: 1px;">Visitas Mensais</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nossa História -->
        <section class="section section-white">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title fade-in-up">📖 Nossa História</h2>
                    <p class="section-subtitle fade-in-up" style="animation-delay: 0.1s;">
                        Como nasceu o marketplace de motos mais confiável de Portugal
                    </p>
                </div>
                
                <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                    <div class="feature-card fade-in-up" style="animation-delay: 0.2s;">
                        <!-- Logo da empresa no lugar do emoji -->
                        <div style="margin-bottom: 2rem;">
                            <div class="custom-logo-placeholder" style="width: 120px; height: 120px; margin: 0 auto; border-radius: 20px; box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);">
                                <img src="assets/logo.png" alt="SellUMotorcycle Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 17px;">
                            </div>
                        </div>
                        <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--primary-black);">
                            Paixão por Motocicletas
                        </h3>
                        <p style="font-size: 1.1rem; line-height: 1.8; color: var(--dark-gray);">
                            SellUMotorcycle nasceu em 2024 como um projeto escolar, desenvolvido por Vítor Luís, um aluno apaixonado por tecnologia e motociclos. 
                            Percebendo a falta de uma plataforma moderna, segura e dedicada exclusivamente ao mundo das duas rodas em Portugal, 
                            Vítor transformou a sua ideia académica numa solução prática e acessível. A nossa missão é simples: 
                           <strong>conectar pessoas que adoram motas</strong> através de uma experiência de compra e venda segura, transparente e eficaz.

                        </p>
                        <br>
                        <p style="font-size: 1.1rem; line-height: 1.8; color: var(--dark-gray);">
                            Desde o início, focamo-nos na <strong>transparência, segurança e facilidade de utilização</strong>. 
                            Cada anúncio é verificado pela nossa equipa, garantindo que apenas motocicletas de qualidade 
                            sejam oferecidas na nossa plataforma. Acreditamos que comprar ou vender uma moto deve ser 
                            uma experiência agradável e sem complicações.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Funcionalidades com Logo -->
        <section class="section section-dark">
            <div class="container">
                <div class="section-header">
                    <!-- Logo na secção funcionalidades -->
                    <div class="fade-in-up" style="margin-bottom: 1.5rem;">
                        <div class="custom-logo-placeholder" style="width: 80px; height: 80px; margin: 0 auto; border-radius: 20px; box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);">
                            <img src="assets/logo.png" alt="SellUMotorcycle Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 17px;">
                        </div>
                    </div>
                    
                    <h2 class="section-title section-title-white fade-in-up" style="animation-delay: 0.1s;">⚡ Por Que Escolher-nos?</h2>
                    <p class="section-subtitle section-subtitle-white fade-in-up" style="animation-delay: 0.2s;">
                        Descubra as vantagens que nos tornam únicos no mercado português
                    </p>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card fade-in-up" style="animation-delay: 0.2s;">
                        <div class="feature-icon">🔒</div>
                        <h3 class="feature-title">Segurança Garantida</h3>
                        <p class="feature-description">
                            Todos os anúncios são verificados manualmente pela nossa equipa. 
                            Sistema de pagamento seguro e protecção contra fraudes.
                        </p>
                    </div>
                    
                    <div class="feature-card fade-in-up" style="animation-delay: 0.4s;">
                        <div class="feature-icon">📱</div>
                        <h3 class="feature-title">Fácil de Utilizar</h3>
                        <p class="feature-description">
                            Interface moderna e intuitiva. Publique o seu anúncio em minutos 
                            e faça a gestão de tudo pelo seu smartphone ou computador.
                        </p>
                    </div>
                    
                    <div class="feature-card fade-in-up" style="animation-delay: 0.6s;">
                        <div class="feature-icon">📞</div>
                        <h3 class="feature-title">Contacto Directo</h3>
                        <p class="feature-description">
                            Integração com WhatsApp para comunicação instantânea. 
                            Conecte-se directamente com compradores interessados.
                        </p>
                    </div>
                    
                    <div class="feature-card fade-in-up" style="animation-delay: 0.7s;">
                        <div class="feature-icon">🏆</div>
                        <h3 class="feature-title">Suporte Premium</h3>
                        <p class="feature-description">
                            Equipa de suporte dedicada para ajudar em todas as etapas. 
                            Resposta rápida e atendimento personalizado.
                        </p>
                    </div>
                </div>
            </div>
        </section>



        <!-- Nossos Valores com Logo -->
        <section class="section section-dark">
            <div class="container">
                <div class="section-header">
                    <!-- Logo na secção valores -->
                    <div class="fade-in-up" style="margin-bottom: 1.5rem;">
                        <div class="custom-logo-placeholder" style="width: 80px; height: 80px; margin: 0 auto; border-radius: 20px; box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);">
                            <img src="assets/logo.png" alt="SellUMotorcycle Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 17px;">
                        </div>
                    </div>
                    
                    <h2 class="section-title section-title-white fade-in-up" style="animation-delay: 0.1s;">💎 Os Nossos Valores</h2>
                    <p class="section-subtitle section-subtitle-white fade-in-up" style="animation-delay: 0.2s;">
                        Os princípios que guiam cada decisão na nossa empresa
                    </p>
                </div>
                
                <div class="values-grid">
                    <div class="value-card fade-in-up" style="animation-delay: 0.2s;">
                        <div class="value-icon">🤝</div>
                        <h3 class="value-title">Confiança</h3>
                        <p class="value-description">
                            Construímos relacionamentos baseados na transparência e honestidade. 
                            Cada interacção deve gerar confiança mútua.
                        </p>
                    </div>
                    
                    <div class="value-card fade-in-up" style="animation-delay: 0.3s;">
                        <div class="value-icon">🎯</div>
                        <h3 class="value-title">Excelência</h3>
                        <p class="value-description">
                            Procuramos constantemente melhorar a nossa plataforma e serviços. 
                            A excelência é o nosso padrão mínimo aceitável.
                        </p>
                    </div>
                    
                    <div class="value-card fade-in-up" style="animation-delay: 0.4s;">
                        <div class="value-icon">🚀</div>
                        <h3 class="value-title">Inovação</h3>
                        <p class="value-description">
                            Abraçamos novas tecnologias e metodologias para revolucionar 
                            a experiência de compra e venda de motocicletas.
                        </p>
                    </div>
                    
                    <div class="value-card fade-in-up" style="animation-delay: 0.5s;">
                        <div class="value-icon">❤️</div>
                        <h3 class="value-title">Paixão</h3>
                        <p class="value-description">
                            Adoramos o que fazemos e isso reflecte-se em cada detalhe da nossa 
                            plataforma. Paixão por motos e por servir a nossa comunidade.
                        </p>
                    </div>
                </div>
            </div>
        </section>



        <!-- Call to Action Final com Logo -->
        <section class="section section-white">
            <div class="container">
                <div style="text-align: center; max-width: 800px; margin: 0 auto;">
                    <div class="feature-card fade-in-up" style="background: linear-gradient(135deg, #FFD700, #FFC107); border: 3px solid var(--primary-black);">
                        <!-- Logo no call-to-action -->
                        <div style="margin-bottom: 2rem;">
                            <div class="custom-logo-placeholder" style="width: 100px; height: 100px; margin: 0 auto; border-radius: 25px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3); background: var(--primary-black);">
                                <img src="assets/logo.png" alt="SellUMotorcycle Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 22px;">
                            </div>
                        </div>
                        
                        <h2 style="font-size: 2.5rem; font-weight: 900; color: var(--primary-black); margin-bottom: 1.5rem;">
                            Pronto para Começar?
                        </h2>
                        <p style="font-size: 1.2rem; color: var(--secondary-black); margin-bottom: 2.5rem; line-height: 1.7;">
                            Junte-se a milhares de motociclistas que já confiam no SellUMotorcycle. 
                            Publique o seu primeiro anúncio agora e descubra como é fácil vender a sua moto!
                        </p>
                        <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
                            <a href="anuncio.php" class="btn btn-primary" style="background: var(--primary-black); color: var(--primary-yellow); border-color: var(--primary-black); font-size: 1.1rem; padding: 1.2rem 2.5rem;">
                                🚀 Criar o Meu Anúncio
                            </a>
                            <a href="index.php#anuncios" class="btn" style="background: transparent; color: var(--primary-black); border: 2px solid var(--primary-black); font-size: 1.1rem; padding: 1.2rem 2.5rem;">
                                👀 Ver Anúncios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer" id="contato">
        <div class="container">
            <div class="logo-container" style="justify-content: center; margin-bottom: 2rem;">
                <div class="logo-text">
                    <div class="logo-main" style="font-size: 1.4rem;">
                        SELL<span class="logo-highlight">u</span>MOTORCYCLE
                    </div>
                    <div class="logo-sub">Marketplace</div>
                </div>
            </div>
            <p style="font-size: 1.1rem; margin-bottom: 1rem;">O marketplace de motos mais confiável de Portugal</p>
            <p style="margin-top: 1rem; color: #FFD700; font-weight: 600;">
                📧 sellumotorcycle@gmail.com | 📞 +351 912 345 678
            </p>
            <p style="margin-top: 1rem; opacity: 0.7;">
                © 2025 SellUMotorcycle - Projeto Académico - Todos os direitos reservados
            </p>
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255, 215, 0, 0.3);">
                <p style="font-size: 0.9rem; opacity: 0.8;">
                    🏍️ Conectando motociclistas em todo Portugal desde 2024 | 
                    Plataforma segura, rápida e confiável
                </p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Funções utilitárias (mesmas do index)
        function showAlert(message, type = 'success') {
            const alert = document.getElementById('customAlert');
            const messageEl = document.getElementById('alertMessage');
            
            messageEl.textContent = message;
            alert.classList.add('show');
            
            setTimeout(() => {
                hideAlert();
            }, 5000);
        }

        function hideAlert() {
            const alert = document.getElementById('customAlert');
            alert.classList.remove('show');
        }

        function confirmLogout(event) {
            event.preventDefault();
            
            if (confirm('🚪 Tem certeza que deseja sair?\n\nVocê será redirecionado para a página inicial.')) {
                showAlert('⏳ Realizando logout...', 'info');
                setTimeout(() => {
                    window.location.href = 'index.php?logout=1';
                }, 1000);
            }
        }

        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            const arrow = document.getElementById('dropdownArrow');
            
            dropdown.classList.toggle('show');
            arrow.style.transform = dropdown.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(event) {
            const userDropdown = document.querySelector('.user-dropdown');
            if (userDropdown && !userDropdown.contains(event.target)) {
                document.getElementById('userDropdown').classList.remove('show');
                document.getElementById('dropdownArrow').style.transform = 'rotate(0deg)';
            }
        });

        // Scroll suave
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animações ao scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Animar elementos quando carregar
            document.querySelectorAll('.fade-in-up').forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(50px)';
                element.style.transition = 'all 0.8s ease-out';
                observer.observe(element);
            });

            console.log('🏍️ Página Sobre SellUMotorcycle carregada!');
        });

        // Mensagens de logout
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('logout_success') === '1') {
            showAlert('✅ Logout realizado com sucesso! Obrigado por usar o SellUMotorcycle.', 'success');
        }
    </script>
</body>
</html>