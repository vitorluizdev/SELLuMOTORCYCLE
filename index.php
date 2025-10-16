<?php 
require_once 'config/db.php';


// ===============================================
// PROCESSAMENTO DE LOGOUT PRIMEIRO
// ===============================================
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    logout();
}

// ===============================================
// SCRIPT DE DIAGNÓSTICO PARA PROBLEMA DOS 2 PRIMEIROS CARDS
// ===============================================
if (isset($_GET['diagnostico'])) {
    echo "<div style='background: #f8f9fa; border: 3px solid #007bff; padding: 2rem; margin: 2rem; border-radius: 10px; font-family: monospace;'>";
    echo "<h2>🔍 DIAGNÓSTICO - Problema dos 2 Primeiros Cards</h2>";
    
    // 1. Verificar consulta SQL bruta
    echo "<h3>📊 1. Dados Brutos da Consulta SQL:</h3>";
    $stmt_debug = $pdo->query("
        SELECT a.id, a.titulo, a.utilizador_id, a.data_criacao, u.nome as vendedor
        FROM anuncios a 
        JOIN utilizadores u ON a.utilizador_id = u.id 
        WHERE a.ativo = 1 AND a.pago = 1 
        ORDER BY a.data_criacao DESC LIMIT 12
    ");
    $anuncios_debug = $stmt_debug->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 1rem 0;'>";
    echo "<tr style='background: #343a40; color: white;'>";
    echo "<th>Posição</th><th>ID</th><th>Título</th><th>Usuário ID</th><th>Vendedor</th><th>Data</th>";
    echo "</tr>";
    
    foreach ($anuncios_debug as $index => $anuncio) {
        $bgColor = $index < 2 ? '#ffebee' : '#e8f5e8';
        echo "<tr style='background: $bgColor;'>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td><strong>" . $anuncio['id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($anuncio['titulo']) . "</td>";
        echo "<td>" . $anuncio['utilizador_id'] . "</td>";
        echo "<td>" . htmlspecialchars($anuncio['vendedor']) . "</td>";
        echo "<td>" . $anuncio['data_criacao'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Verificar IDs duplicados
    echo "<h3>🔍 2. Verificação de IDs Duplicados:</h3>";
    $ids = array_column($anuncios_debug, 'id');
    $ids_unicos = array_unique($ids);
    
    if (count($ids) === count($ids_unicos)) {
        echo "<p style='color: green; font-weight: bold;'>✅ Todos os IDs são únicos</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ IDs duplicados encontrados!</p>";
        $duplicados = array_diff_assoc($ids, $ids_unicos);
        echo "<pre>IDs duplicados: " . print_r($duplicados, true) . "</pre>";
    }
    
    echo "<hr><p><a href='index.php'>🔙 Voltar ao site normal</a> | <a href='?fix=1'>🔧 Aplicar correções</a></p>";
    echo "</div>";
    exit;
}

// ===============================================
// CORREÇÃO AUTOMÁTICA
// ===============================================
if (isset($_GET['fix'])) {
    echo "<div style='background: #d4edda; border: 3px solid #28a745; padding: 2rem; margin: 2rem; border-radius: 10px;'>";
    echo "<h2>🔧 APLICANDO CORREÇÕES...</h2>";
    
    // Limpar cache/sessão
    if (isset($_SESSION['carousel_cache'])) {
        unset($_SESSION['carousel_cache']);
        echo "<p>✅ Cache de carrossel limpo</p>";
    }
    
    echo "<p><strong>✅ Correções aplicadas! <a href='index.php'>Recarregue a página normalmente</a>.</strong></p>";
    echo "</div>";
    exit;
}

// ===============================================
// CONSULTA DE ANÚNCIOS EXPANDIDA PARA 12 ANÚNCIOS
// ===============================================

// CONSULTA EXPANDIDA: Aumentar para 12 anúncios para preencher 4 linhas de 3 colunas
$stmt = $pdo->query("
    SELECT DISTINCT a.id, a.utilizador_id, a.titulo, a.marca, a.modelo, a.ano, 
           a.preco, a.telefone, a.imagem, a.imagem2, a.imagem3, a.imagem4, a.imagem5,
           a.descricao, a.data_criacao, u.nome as vendedor
    FROM anuncios a 
    JOIN utilizadores u ON a.utilizador_id = u.id 
    WHERE a.ativo = 1 AND a.pago = 1 
    ORDER BY a.data_criacao DESC 
    LIMIT 12
");
$anuncios_raw = $stmt->fetchAll();

// CORREÇÃO 2: Verificar e remover duplicatas explicitamente
$anuncios_limpos = [];
$ids_processados = [];

foreach ($anuncios_raw as $anuncio) {
    if (!in_array($anuncio['id'], $ids_processados)) {
        $anuncios_limpos[] = $anuncio;
        $ids_processados[] = $anuncio['id'];
    }
}

// CORREÇÃO 3: Processar imagens SEM usar referência (&)
$anuncios = [];
foreach ($anuncios_limpos as $index => $anuncio_original) {
    // Criar cópia limpa do anúncio (SEM referência!)
    $anuncio = $anuncio_original;
    
    $imagens = [];
    $camposImagem = ['imagem', 'imagem2', 'imagem3', 'imagem4', 'imagem5'];
    
    foreach ($camposImagem as $campo) {
        if (!empty($anuncio[$campo]) && !in_array($anuncio[$campo], $imagens)) {
            $imagens[] = $anuncio[$campo];
        }
    }
    
    // Se não há imagens, usar placeholder
    if (empty($imagens)) {
        $imagens[] = null;
    }
    
    $anuncio['galeria_imagens'] = $imagens;
    $anuncio['total_imagens'] = count(array_filter($imagens));
    $anuncio['_debug_index'] = $index;
    $anuncio['_debug_position'] = $index + 1;
    
    $anuncios[] = $anuncio;
}

// Buscar estatísticas
$totalAnuncios = $pdo->query("SELECT COUNT(DISTINCT id) FROM anuncios WHERE ativo = 1")->fetchColumn();
$totalVendedores = $pdo->query("SELECT COUNT(DISTINCT id) FROM utilizadores WHERE tipo = 'cliente'")->fetchColumn();
$totalVisitas = "150K+";
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NOME ?> - Marketplace de Motos em Portugal</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏍️</text></svg>">
    
    <style>
        /* ===============================================
           VARIÁVEIS CSS OTIMIZADAS PARA 3 COLUNAS
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
            --border-radius: 10px; /* Reduzido para layout mais compacto */
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
            max-width: 1400px; /* Aumentado para acomodar 3 colunas */
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ===============================================
           HEADER OTIMIZADO (MANTIDO IGUAL)
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

        /* Logo section */
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
            animation: logoEntrance 1s ease-out;
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
            -webkit-background-clip: initial !important;
            background-clip: initial !important;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5), 
                         0 0 10px rgba(255, 215, 0, 0.3),
                         0 0 15px rgba(255, 193, 7, 0.2);
            position: relative;
            filter: brightness(1.1) saturate(1.1);
        }

        .logo-main *,
        .logo-main span,
        .logo-highlight {
            color: #FFD700 !important;
            -webkit-text-fill-color: #FFD700 !important;
            background: none !important;
            -webkit-background-clip: initial !important;
            background-clip: initial !important;
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

        @keyframes logoEntrance {
            0% { opacity: 0; transform: translateY(-20px) scale(0.8); }
            50% { opacity: 0.8; transform: translateY(5px) scale(1.1); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
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

        .logo:hover .logo-main {
            animation: goldenShine 0.6s ease-in-out;
            filter: brightness(1.3) saturate(1.2);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5), 
                         0 0 15px rgba(255, 215, 0, 0.5),
                         0 0 25px rgba(255, 193, 7, 0.3),
                         0 0 35px rgba(255, 165, 0, 0.2);
        }

        .logo:hover .logo-highlight {
            filter: brightness(1.5) contrast(1.3);
            text-shadow: 0 0 20px rgba(255, 215, 0, 1),
                         0 0 30px rgba(255, 193, 7, 0.8),
                         0 0 40px rgba(255, 165, 0, 0.6);
        }

        @keyframes goldenShine {
            0%, 100% { 
                filter: brightness(1.1) saturate(1.1); 
                transform: scale(1);
            }
            50% { 
                filter: brightness(1.4) saturate(1.3); 
                transform: scale(1.02);
            }
        }

        /* ===============================================
           NAVEGAÇÃO (MANTIDA IGUAL)
           =============================================== */
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

        /* Dropdown */
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
           HERO SECTION (MANTIDA IGUAL)
           =============================================== */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }

        .hero::before {
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
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(2deg); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--pure-white);
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--light-gray);
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        .price-banner {
            background: linear-gradient(135deg, #FFD700, #FFC107, #FFDD44);
            padding: 2.5rem 3rem;
            border-radius: 25px;
            margin: 2rem auto;
            max-width: 550px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(255, 215, 0, 0.3);
            border: 3px solid var(--primary-black);
        }

        .price-text {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--primary-black);
            text-shadow: 1px 1px 2px rgba(255,255,255,0.5);
            position: relative;
            z-index: 2;
        }

        .price-subtitle {
            font-size: 1.1rem;
            color: var(--secondary-black);
            margin-top: 0.5rem;
            position: relative;
            z-index: 2;
            font-weight: 600;
        }

        .hero-buttons {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 3rem;
            flex-wrap: wrap;
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

        .btn-secondary {
            background: transparent;
            color: #FFD700;
            border: 2px solid #FFD700;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: var(--primary-black);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        /* ===============================================
           SEÇÕES
           =============================================== */
        .announcements-section {
            background: linear-gradient(to bottom, transparent, rgba(255, 215, 0, 0.05));
            padding: 5rem 0;
            position: relative;
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

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--light-gray);
            max-width: 600px;
            margin: 0 auto;
        }

        /* ===============================================
           GRID DE ANÚNCIOS OTIMIZADO PARA 3 COLUNAS
           =============================================== */
        .announcements-grid {
            display: grid;
            /* 🎯 PRINCIPAL MUDANÇA: 3 colunas fixas em desktop */
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem; /* Reduzido para layout mais compacto */
            margin-top: 3rem;
            padding: 0 1rem;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        .announcement-card::before,
        .announcement-card::after,
        .carousel-container::before,
        .carousel-container::after {
            display: none !important;
            content: none !important;
        }

        .announcement-card > *:not(.card-click-indicator):not(.debug-info) {
            position: relative;
        }

        /* ===============================================
           CARDS OTIMIZADOS PARA 3 COLUNAS - MAIS COMPACTOS
           =============================================== */
        .announcement-card {
            background: var(--pure-white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            border: 2px solid transparent;
            cursor: pointer;
            /* 🎯 ALTURA OTIMIZADA PARA 3 COLUNAS: Mais compacta */
            height: auto;
            min-height: 520px; /* Reduzido de 580px */
            display: flex;
            flex-direction: column;
            width: 100%;
            min-width: 0;
            max-width: 100%;
            isolation: isolate;
            contain: layout style paint;
        }

        .announcement-card:hover {
            transform: translateY(-6px) scale(1.015); /* Reduzido para layout mais sutil */
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2), 
                        0 6px 20px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-yellow);
            z-index: 10;
        }

        .announcement-card > * {
            max-width: 100%;
            box-sizing: border-box;
        }

        /* ===============================================
           CAROUSEL OTIMIZADO PARA 3 COLUNAS - MAIS COMPACTO
           =============================================== */
        .carousel-container {
            position: relative;
            height: 220px; /* Reduzido de 260px para layout mais compacto */
            overflow: hidden;
            background: linear-gradient(45deg, #E0E0E0, #F8F9FA);
            flex-shrink: 0;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        .carousel {
            display: flex;
            height: 100%;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
            width: 100%;
            min-width: 100%;
        }

        .carousel-slide {
            min-width: 100%;
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .carousel-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
            display: block;
            backface-visibility: hidden;
            max-width: 100%;
            max-height: 100%;
        }

        .announcement-card:hover .carousel-image {
            transform: scale(1.03); /* Reduzido para efeito mais sutil */
        }

        .carousel-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #000000, #1a1a1a);
            color: #FFD700;
            font-size: 2.5rem; /* Reduzido para layout compacto */
            flex-direction: column;
            text-align: center;
        }

        .placeholder-text {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            opacity: 0.8;
            font-weight: 500;
        }

        .carousel-controls {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: #FFD700;
            border: 2px solid #FFD700;
            width: 36px; /* Reduzido para layout compacto */
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            backdrop-filter: blur(5px);
            z-index: 15;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            user-select: none;
            pointer-events: none;
        }

        .carousel-container:hover .carousel-controls {
            opacity: 1;
            pointer-events: auto;
        }

        .carousel-controls:hover {
            background: #FFD700;
            color: #000000;
            border-color: #000000;
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
        }

        .carousel-prev {
            left: 8px;
        }

        .carousel-next {
            right: 8px;
        }

        .carousel-indicators {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 4px; /* Reduzido */
            background: rgba(0, 0, 0, 0.7);
            padding: 4px 8px; /* Reduzido */
            border-radius: 15px;
            backdrop-filter: blur(10px);
            z-index: 5;
        }

        .carousel-dot {
            width: 6px; /* Reduzido */
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: none;
            padding: 0;
        }

        .carousel-dot.active {
            background: #FFD700;
            transform: scale(1.3);
            box-shadow: 0 0 6px rgba(255, 215, 0, 0.6);
        }

        .carousel-dot:hover {
            background: #FFD700;
            transform: scale(1.1);
        }

        .carousel-counter {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: #FFD700;
            padding: 3px 8px; /* Reduzido */
            border-radius: 10px;
            font-size: 0.7rem; /* Reduzido */
            font-weight: 700;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.3);
            z-index: 5;
        }

        /* ===============================================
           CONTEÚDO DO CARD - OTIMIZADO PARA 3 COLUNAS
           =============================================== */
        .card-content {
            padding: 1.25rem; /* Reduzido de 1.5rem */
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            min-height: 240px; /* Reduzido */
            box-sizing: border-box;
        }

        /* ⭐ TÍTULO OTIMIZADO PARA 3 COLUNAS */
        .card-title {
            /* 🎯 TAMANHO REDUZIDO PARA 3 COLUNAS */
            font-size: 1.2rem !important; /* Reduzido de 1.4rem */
            font-weight: 700;
            
            /* Fonte profissional */
            font-family: 'Poppins', 'Inter', 'Segoe UI', sans-serif !important;
            
            /* Cor elegante */
            color: #1a1a1a !important;
            
            /* Espaçamento reduzido */
            margin-bottom: 0.6rem; /* Reduzido */
            padding: 0;
            
            /* Line-height otimizado */
            line-height: 1.3 !important;
            
            /* Controle de overflow */
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            
            /* Altura calculada: 1.2rem * 1.3 * 2 = 3.12rem */
            max-height: 3.2rem !important;
            min-height: 1.6rem !important;
            
            /* Quebra de palavra otimizada */
            word-wrap: break-word !important;
            word-break: break-word !important;
            hyphens: auto !important;
            
            /* Espaçamento de letras */
            letter-spacing: -0.005em;
            word-spacing: 0.01em;
            
            /* Transição suave */
            transition: all 0.3s ease;
            
            /* Suavização da fonte */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            
            /* Garantir que não ultrapasse o container */
            width: 100%;
            box-sizing: border-box;
        }

        /* 🎯 PREÇO OTIMIZADO PARA 3 COLUNAS */
        .card-price {
            font-size: 1.6rem; /* Reduzido de 1.9rem */
            font-weight: 900;
            /* Fonte profissional */
            font-family: 'Poppins', 'Inter', 'Segoe UI', sans-serif !important;
            /* Cor dourada otimizada */
            color: #B8860B !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.12);
            margin-bottom: 0.8rem; /* Reduzido */
            background: none !important;
            -webkit-text-fill-color: #B8860B !important;
            -webkit-background-clip: initial !important;
            background-clip: initial !important;
            transition: all 0.3s ease;
        }

        /* 📝 DETALHES OTIMIZADOS PARA 3 COLUNAS */
        .card-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.4rem; /* Reduzido */
            margin-bottom: 0.8rem; /* Reduzido */
        }

        .card-detail {
            display: flex;
            align-items: center;
            gap: 0.4rem; /* Reduzido */
            color: #4a4a4a !important;
            font-size: 0.85rem; /* Reduzido */
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .card-detail span:first-child {
            font-size: 0.9rem; /* Ícone ligeiramente menor */
        }

        .contact-btn {
            background: linear-gradient(45deg, #FFD700, #FFC107);
            color: var(--primary-black);
            border: 2px solid var(--primary-black);
            padding: 0.6rem 1.2rem; /* Reduzido */
            border-radius: 20px; /* Reduzido */
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 0.8rem; /* Reduzido */
            margin-bottom: 0.8rem; /* Reduzido */
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 15px rgba(255, 215, 0, 0.4);
            background: linear-gradient(45deg, #FFDD44, #FFB347);
        }

        /* 👥 RODAPÉ OTIMIZADO PARA 3 COLUNAS */
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.6rem; /* Reduzido */
            border-top: 2px solid var(--light-gray);
            gap: 0.4rem; /* Reduzido */
            font-size: 0.8rem; /* Reduzido */
        }

        /* Vendedor */
        .card-footer span:first-child {
            color: #8B7355 !important;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 60%; /* Limitado para não quebrar layout */
        }

        /* Data */
        .card-footer span:last-child {
            color: #6c757d !important;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ===============================================
           EFEITOS HOVER PARA 3 COLUNAS
           =============================================== */

        /* Hover no título */
        .announcement-card:hover .card-title {
            color: #000000 !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        /* Hover no preço */
        .announcement-card:hover .card-price {
            color: #DAA520 !important;
            -webkit-text-fill-color: #DAA520 !important;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.18);
            transform: scale(1.02);
        }

        /* Hover nos detalhes */
        .announcement-card:hover .card-detail {
            color: #2a2a2a !important;
        }

        /* Hover no vendedor */
        .announcement-card:hover .card-footer span:first-child {
            color: #A0815C !important;
        }

        /* ===============================================
           ELEMENTOS ADICIONAIS
           =============================================== */
        .card-click-indicator {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(45deg, #FFD700, #FFC107);
            color: #000000;
            padding: 4px 8px; /* Reduzido */
            border-radius: 15px;
            font-size: 0.7rem; /* Reduzido */
            font-weight: 700;
            border: 1px solid #000000;
            text-transform: uppercase;
            z-index: 6;
            box-shadow: 0 2px 6px rgba(255, 215, 0, 0.3);
            animation: pulse 2s infinite;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--medium-gray);
            grid-column: 1 / -1; /* Ocupar todas as colunas */
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .empty-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 1rem;
        }

        .empty-subtitle {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        /* ===============================================
           FOOTER (MANTIDO IGUAL)
           =============================================== */
        .footer {
            background: var(--primary-black);
            color: var(--pure-white);
            text-align: center;
            padding: 3rem 0;
            margin-top: 5rem;
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
           MODAL OTIMIZADO COM TÍTULOS PROFISSIONAIS
           =============================================== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            max-width: 1200px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            transform: scale(0.8) translateY(50px);
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .modal-overlay.show .modal-container {
            transform: scale(1) translateY(0);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 50px;
            height: 50px;
            background: rgba(0, 0, 0, 0.8);
            color: #FFD700;
            border: 2px solid #FFD700;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .modal-close:hover {
            background: #FFD700;
            color: #000000;
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        .modal-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 100%;
            min-height: 600px;
            flex: 1;
        }

        .modal-left {
            position: relative;
            background: #f8f9fa;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 500px;
            isolation: isolate;
            contain: layout style paint;
        }

        .modal-carousel-container {
            position: relative;
            height: 100%;
            min-height: 500px;
            overflow: hidden;
            background: #f8f9fa;
            flex: 1;
            display: flex;
            align-items: stretch;
            justify-content: center;
            padding: 10px;
        }

        .modal-carousel {
            display: flex;
            height: 100%;
            width: 100%;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
            position: relative;
            transform: translateX(0%);
        }

        .modal-carousel-slide {
            min-width: 100%;
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #f8f9fa;
            overflow: hidden;
            padding: 10px;
        }

        .modal-carousel-slide:not(.active) {
            opacity: 0 !important;
            visibility: hidden !important;
            z-index: 1 !important;
            pointer-events: none !important;
        }

        .modal-carousel-slide.active {
            opacity: 1 !important;
            visibility: visible !important;
            z-index: 10 !important;
            pointer-events: auto !important;
        }

        .modal-carousel-image {
            width: 100%;
            height: 100%;
            max-width: none;
            max-height: none;
            min-width: 100%;
            min-height: 100%;
            object-fit: contain;
            object-position: center;
            transition: transform 0.3s ease;
            display: block;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            background: #ffffff;
        }

        .modal-carousel-image:hover {
            transform: scale(1.03);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-carousel-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
            color: #6c757d;
            font-size: 4rem;
            flex-direction: column;
            text-align: center;
            padding: 2rem;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
            min-height: 300px;
        }

        .modal-placeholder-text {
            font-size: 1.3rem;
            margin-top: 1rem;
            opacity: 0.8;
            font-weight: 600;
            line-height: 1.4;
            color: #6c757d;
        }

        .modal-carousel-controls {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: #FFD700;
            border: 2px solid #FFD700;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 2rem;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
            z-index: 50;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            user-select: none;
            outline: none;
        }

        .modal-carousel-controls:hover {
            background: #FFD700;
            color: #000000;
            border-color: #000000;
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        .modal-carousel-prev {
            left: 20px;
        }

        .modal-carousel-next {
            right: 20px;
        }

        .modal-carousel-indicators {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px 20px;
            border-radius: 30px;
            backdrop-filter: blur(10px);
            z-index: 50;
            max-width: 90%;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .modal-carousel-dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            padding: 0;
            outline: none;
            flex-shrink: 0;
        }

        .modal-carousel-dot.active {
            background: #FFD700;
            transform: scale(1.5);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.8);
        }

        .modal-carousel-counter {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: #FFD700;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 1.1rem;
            font-weight: 700;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 215, 0, 0.3);
            z-index: 50;
            user-select: none;
        }

        /* MODAL RIGHT - TÍTULOS PROFISSIONAIS */
        .modal-right {
            padding: 2.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            background: #ffffff;
        }

        /* Título do modal */
        .modal-title {
            font-size: 2.4rem !important;
            font-weight: 800;
            font-family: 'Poppins', 'Inter', 'Segoe UI', sans-serif !important;
            color: #000000 !important;
            line-height: 1.25;
            margin-bottom: 0.75rem;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            letter-spacing: -0.025em;
            word-spacing: 0.025em;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            word-wrap: break-word;
            hyphens: auto;
        }

        /* Preço do modal */
        .modal-price {
            font-size: 2.8rem;
            font-weight: 900;
            color: #DAA520 !important;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.15);
            margin-bottom: 1rem;
        }

        .modal-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            border: 2px solid #e9ecef;
        }

        .modal-detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #424242;
            font-size: 1rem;
            font-weight: 600;
        }

        .modal-detail-item span:last-child {
            color: #2a2a2a !important;
            font-weight: 600;
        }

        .modal-detail-icon {
            font-size: 1.3rem;
            width: 30px;
            text-align: center;
        }

        .modal-description {
            background: #fff;
            padding: 1.5rem;
            border-radius: 15px;
            border: 2px solid #e9ecef;
            margin: 1rem 0;
        }

        .modal-description h4 {
            color: #1a1a1a !important;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-description p {
            color: #4a4a4a !important;
            line-height: 1.7;
            font-size: 1rem;
            font-weight: 400;
        }

        .modal-seller-info {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            padding: 1.5rem;
            border-radius: 15px;
            border: 2px solid #000000;
            text-align: center;
        }

        .modal-seller-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #000000 !important;
            margin-bottom: 0.5rem;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.3);
        }

        .modal-seller-date {
            color: #2a2a2a !important;
            font-size: 1rem;
            font-weight: 500;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.2);
        }

        .modal-contact-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 1.2rem 2.5rem;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .modal-contact-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.5);
            background: linear-gradient(45deg, #20c997, #17a2b8);
        }

        /* ===============================================
           DEBUG STYLES
           =============================================== */
        .debug-info {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.8);
            color: #FFD700;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            z-index: 1000;
        }

        .card-problematico {
            border: 3px solid #ff4444 !important;
            box-shadow: 0 0 10px rgba(255, 68, 68, 0.5) !important;
        }

        .card-ok {
            border: 3px solid #44ff44 !important;
            box-shadow: 0 0 10px rgba(68, 255, 68, 0.5) !important;
        }

        /* ===============================================
           RESPONSIVIDADE OTIMIZADA PARA 3 COLUNAS
           =============================================== */

        /* Desktop extra large (1400px+) */
        @media (min-width: 1400px) {
            .announcements-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 2rem;
                max-width: 1400px;
                margin: 3rem auto 0;
            }
            
            .card-title {
                font-size: 1.3rem !important;
                max-height: 3.38rem !important; /* 1.3 * 1.3 * 2 */
            }
            
            .card-price {
                font-size: 1.7rem;
            }
            
            .carousel-container {
                height: 240px;
            }
            
            .announcement-card {
                min-height: 540px;
            }
        }

        /* Desktop large (1200-1399px) */
        @media (max-width: 1399px) and (min-width: 1200px) {
            .announcements-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.75rem;
            }
            
            .card-title {
                font-size: 1.25rem !important;
                max-height: 3.25rem !important; /* 1.25 * 1.3 * 2 */
            }
            
            .card-price {
                font-size: 1.65rem;
            }
        }

        /* Desktop normal (992-1199px) */
        @media (max-width: 1199px) and (min-width: 992px) {
            .announcements-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.5rem;
            }
            
            .card-title {
                font-size: 1.2rem !important;
                max-height: 3.12rem !important; /* 1.2 * 1.3 * 2 */
            }
            
            .card-price {
                font-size: 1.6rem;
            }
            
            .card-content {
                padding: 1.2rem;
            }
        }

        /* Tablet (768-991px) - 2 COLUNAS */
        @media (max-width: 991px) and (min-width: 769px) {
            .announcements-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 colunas no tablet */
                gap: 1.5rem;
            }
            
            .card-title {
                font-size: 1.3rem !important;
                max-height: 3.38rem !important;
            }
            
            .card-price {
                font-size: 1.7rem;
            }
            
            .carousel-container {
                height: 230px;
            }
            
            .announcement-card {
                min-height: 520px;
            }
        }

        /* Mobile large (481-768px) - 1 COLUNA */
        @media (max-width: 768px) and (min-width: 481px) {
            .announcements-grid {
                grid-template-columns: 1fr; /* 1 coluna no mobile */
                gap: 1.5rem;
                padding: 0 0.5rem;
            }

            .announcement-card {
                min-height: 500px;
            }

            .carousel-container {
                height: 220px;
            }
            
            .card-title {
                font-size: 1.4rem !important;
                max-height: 3.64rem !important; /* 1.4 * 1.3 * 2 */
                margin-bottom: 0.7rem;
            }
            
            .card-price {
                font-size: 1.8rem;
            }
            
            .card-content {
                padding: 1.4rem;
                min-height: 220px;
            }
            
            .card-details {
                gap: 0.6rem;
                margin-bottom: 1rem;
            }
            
            .card-detail {
                font-size: 0.9rem;
                gap: 0.5rem;
            }
            
            .contact-btn {
                padding: 0.75rem 1.5rem;
                font-size: 0.85rem;
                margin-bottom: 1rem;
            }
            
            .card-footer {
                font-size: 0.85rem;
                gap: 0.5rem;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .header-content {
                flex-wrap: wrap;
                gap: 1rem;
                padding: 0 15px;
            }
            
            .logo-section {
                gap: 1rem;
            }
            
            .custom-logo-placeholder {
                width: 45px;
                height: 45px;
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

            .logo-container {
                gap: 0.5rem;
            }

            .logo-main {
                font-size: 1.4rem;
                color: #FFD700 !important;
                background: none !important;
                -webkit-text-fill-color: #FFD700 !important;
                -webkit-background-clip: initial !important;
                background-clip: initial !important;
                filter: brightness(1.1) saturate(1.1);
            }

            .logo-sub {
                font-size: 0.6rem;
                letter-spacing: 1px;
            }
            
            .carousel-controls {
                opacity: 1;
                width: 32px;
                height: 32px;
                font-size: 1rem;
            }

            .modal-title {
                font-size: 2.2rem !important;
                line-height: 1.25;
                font-family: 'Poppins', 'Inter', 'Segoe UI', sans-serif !important;
                color: #000000 !important;
            }
            
            .modal-price {
                font-size: 2.2rem;
                color: #DAA520 !important;
            }

            .modal-container {
                margin: 10px;
                max-height: 95vh;
                max-width: 95vw;
            }

            .modal-content {
                grid-template-columns: 1fr;
                grid-template-rows: 55vh 1fr;
                min-height: auto;
            }

            .modal-carousel-container {
                min-height: 55vh;
                padding: 5px;
            }

            .modal-right {
                padding: 1.5rem;
                gap: 1rem;
                max-height: 40vh;
                overflow-y: auto;
            }

            .modal-details-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .modal-carousel-controls {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .modal-close {
                width: 45px;
                height: 45px;
                font-size: 1.5rem;
                top: 10px;
                right: 10px;
            }
        }

        /* Mobile small (≤480px) - 1 COLUNA COMPACTA */
        @media (max-width: 480px) {
            .announcements-grid {
                grid-template-columns: 1fr;
                gap: 1.25rem;
                padding: 0 0.25rem;
            }

            .announcement-card {
                min-height: 460px;
            }

            .carousel-container {
                height: 200px;
            }

            .card-content {
                padding: 1.1rem;
                min-height: 200px;
            }

            .card-title {
                font-size: 1.3rem !important;
                max-height: 3.38rem !important;
                margin-bottom: 0.6rem;
                line-height: 1.3 !important;
            }

            .card-price {
                font-size: 1.6rem;
                margin-bottom: 0.7rem;
            }
            
            .card-details {
                gap: 0.5rem;
                margin-bottom: 0.8rem;
            }
            
            .card-detail {
                font-size: 0.8rem;
                gap: 0.4rem;
            }

            .contact-btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.75rem;
                margin-bottom: 0.8rem;
            }
            
            .card-footer {
                font-size: 0.75rem;
                padding-top: 0.5rem;
            }

            .logo-section {
                gap: 0.75rem;
            }
            
            .custom-logo-placeholder {
                width: 40px;
                height: 40px;
            }
            
            .logo-main {
                font-size: 1.2rem;
                color: #FFD700 !important;
                background: none !important;
                -webkit-text-fill-color: #FFD700 !important;
                -webkit-background-clip: initial !important;
                background-clip: initial !important;
                filter: brightness(1.1) saturate(1.1);
            }

            .modal-overlay {
                padding: 5px;
            }

            .modal-container {
                border-radius: 15px;
            }

            .modal-content {
                grid-template-rows: 45vh 1fr;
            }

            .modal-carousel-container {
                min-height: 45vh;
                padding: 5px;
            }

            .modal-right {
                padding: 1rem;
                max-height: 50vh;
            }

            .modal-carousel-controls {
                width: 45px;
                height: 45px;
                font-size: 1.3rem;
            }

            .modal-carousel-prev {
                left: 10px;
            }

            .modal-carousel-next {
                right: 10px;
            }

            .modal-title {
                font-size: 1.9rem !important;
                line-height: 1.3;
                font-family: 'Poppins', 'Inter', 'Segoe UI', sans-serif !important;
                color: #000000 !important;
            }

            .modal-price {
                font-size: 2rem;
                color: #DAA520 !important;
            }
        }

        /* ===============================================
           ANIMAÇÕES
           =============================================== */
        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .fade-in-up {
            animation: slideInUp 0.8s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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

        /* ===============================================
           UTILITÁRIOS PARA DEBUG DE TÍTULOS
           =============================================== */

        .debug-title-info {
            position: relative;
        }

        .debug-title-info::after {
            content: attr(data-debug-info);
            position: absolute;
            top: -25px;
            left: 0;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 3px;
            white-space: nowrap;
            z-index: 1000;
            pointer-events: none;
        }

        .titulo-overflow {
            background: rgba(255, 255, 0, 0.2) !important;
            border: 1px dashed orange !important;
        }

        .titulo-muito-longo {
            background: rgba(255, 0, 0, 0.1) !important;
            border: 1px dashed red !important;
        }

        /* ===============================================
           MELHORIAS ESPECÍFICAS PARA 3 COLUNAS
           =============================================== */

        /* Garantir que o grid se mantém consistente */
        .announcements-grid {
            justify-items: stretch;
            align-items: stretch;
        }

        /* Padronizar altura dos cards */
        .announcement-card {
            display: flex;
            flex-direction: column;
        }

        /* Garantir que o carrossel ocupe espaço consistente */
        .carousel-container {
            flex-shrink: 0;
        }

        /* Garantir que o conteúdo se distribua bem */
        .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Container específico para os detalhes */
        .card-main-content {
            flex: 1;
        }

        /* Container para footer fixo na base */
        .card-bottom-content {
            margin-top: auto;
        }
    </style>

    <!-- SCRIPT DE CORREÇÃO AUTOMÁTICA DOS TÍTULOS PARA 3 COLUNAS -->
    <script>
        /* ===============================================
           SCRIPT DE CORREÇÃO PARA LAYOUT 3 COLUNAS
           =============================================== */

        (function() {
            'use strict';
            
            function fixCardTitlesFor3Columns() {
                console.log('🔧 Aplicando correção para layout 3 colunas...');
                
                const titles = document.querySelectorAll('.card-title');
                let fixed = 0;
                
                titles.forEach((title, index) => {
                    try {
                        // Reset completo de estilos inline
                        title.style.cssText = '';
                        
                        // Remover classes debug
                        title.classList.remove('debug-title-info', 'titulo-overflow', 'titulo-muito-longo');
                        title.removeAttribute('data-debug-info');
                        
                        // Garantir que o texto não tenha espaços desnecessários
                        const originalText = title.textContent;
                        const cleanText = originalText.trim().replace(/\s+/g, ' ');
                        
                        if (cleanText !== originalText) {
                            title.textContent = cleanText;
                        }
                        
                        // Forçar aplicação das classes CSS para 3 colunas
                        title.classList.add('card-title');
                        
                        // Aplicar tamanhos específicos para 3 colunas baseados no viewport
                        const width = window.innerWidth;
                        let targetFontSize, targetMaxHeight;
                        
                        if (width <= 480) {
                            // Mobile small - 1 coluna
                            targetFontSize = '1.3rem';
                            targetMaxHeight = '3.38rem';
                        } else if (width <= 768) {
                            // Mobile large - 1 coluna  
                            targetFontSize = '1.4rem';
                            targetMaxHeight = '3.64rem';
                        } else if (width <= 991) {
                            // Tablet - 2 colunas
                            targetFontSize = '1.3rem';
                            targetMaxHeight = '3.38rem';
                        } else if (width <= 1199) {
                            // Desktop normal - 3 colunas
                            targetFontSize = '1.2rem';
                            targetMaxHeight = '3.12rem';
                        } else if (width <= 1399) {
                            // Desktop large - 3 colunas
                            targetFontSize = '1.25rem';
                            targetMaxHeight = '3.25rem';
                        } else {
                            // Desktop XL - 3 colunas
                            targetFontSize = '1.3rem';
                            targetMaxHeight = '3.38rem';
                        }
                        
                        // Aplicar estilos otimizados
                        title.style.fontSize = targetFontSize;
                        title.style.maxHeight = targetMaxHeight;
                        title.style.lineHeight = '1.3';
                        title.style.webkitLineClamp = '2';
                        title.style.display = '-webkit-box';
                        title.style.webkitBoxOrient = 'vertical';
                        title.style.overflow = 'hidden';
                        title.style.textOverflow = 'ellipsis';
                        title.style.wordWrap = 'break-word';
                        title.style.wordBreak = 'break-word';
                        title.style.hyphens = 'auto';
                        title.style.fontFamily = "'Poppins', 'Inter', 'Segoe UI', sans-serif";
                        title.style.fontWeight = '700';
                        title.style.color = '#1a1a1a';
                        
                        console.log(`✅ Título ${index + 1}: ${targetFontSize} / ${targetMaxHeight} para ${Math.floor(width/100)*100}px`);
                        fixed++;
                        
                    } catch (error) {
                        console.error(`❌ Erro ao corrigir título ${index + 1}:`, error);
                    }
                });
                
                console.log(`🎉 Correção 3 colunas: ${fixed}/${titles.length} títulos corrigidos`);
                
                // Ajustar altura dos cards para 3 colunas
                document.querySelectorAll('.announcement-card').forEach(card => {
                    const width = window.innerWidth;
                    if (width <= 480) {
                        card.style.minHeight = '460px';
                    } else if (width <= 768) {
                        card.style.minHeight = '500px';
                    } else if (width <= 991) {
                        card.style.minHeight = '520px';
                    } else {
                        card.style.minHeight = '520px';
                    }
                });
                
                return { total: titles.length, fixed: fixed };
            }
            
            // Expor função globalmente
            window.fixCardTitlesFor3Columns = fixCardTitlesFor3Columns;
            
            // Aplicar correção automaticamente
            function autoFixFor3Columns() {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => {
                        setTimeout(fixCardTitlesFor3Columns, 500);
                    });
                } else {
                    setTimeout(fixCardTitlesFor3Columns, 100);
                }
            }
            
            // Aplicar em resize
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(fixCardTitlesFor3Columns, 300);
            });
            
            // Inicializar
            autoFixFor3Columns();
            
            console.log('🎯 Sistema de correção para 3 colunas carregado');
            
        })();
    </script>
</head>
<body>
    <!-- Alerta personalizado -->
    <div id="customAlert" class="custom-alert">
        <span id="alertMessage"></span>
        <button class="close-btn" onclick="hideAlert()">&times;</button>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <!-- Seção da Logo -->
            <div class="logo-section">
                <!-- Placeholder para logo personalizada -->
                <div class="custom-logo-placeholder" id="customLogoPlaceholder">
                    <img src="assets/logo.png" alt="Logo Personalizada">
                </div>
                
                <!-- Logo texto -->
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
                    <li><a href="index.php" class="active">Início</a></li>
                    <li><a href="#anuncios">Anúncios</a></li>
                    <li><a href="sobre.php">Sobre</a></li>
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
                                    <a href="admin.php" class="admin-link">👨‍ 💼 Painel Admin</a>
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

    <!-- Mensagens de sucesso -->
    <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'anuncio_criado'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert('🎉 Anúncio criado e publicado com sucesso!', 'success');
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['logout_success']) && $_GET['logout_success'] == '1'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert('✅ Logout realizado com sucesso! Obrigado por usar o Sell U Motorcycle.', 'success');
            });
        </script>
    <?php endif; ?>

    <!-- Hero section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Venda Sua Moto com Facilidade</h1>
                <p class="hero-subtitle">
                    A plataforma mais moderna e eficiente para anunciar sua motocicleta. 
                    Conecte-se com milhares de compradores em potencial.
                </p>
                
                <div class="price-banner pulse-animation">
                    <div class="price-text">Apenas €<?= PRECO_ANUNCIO ?> por anúncio</div>
                </div>
                
                <div class="hero-buttons">
                    <a href="anuncio.php" class="btn btn-primary">
                        🏍️ Publicar Anúncio
                    </a>
                    <a href="#anuncios" class="btn btn-secondary">
                        👀 Ver Anúncios
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

    <!-- Seção de anúncios OTIMIZADA PARA 3 COLUNAS -->
    <section class="announcements-section" id="anuncios">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">🔥 Motos em Destaque</h2>
                <p class="section-subtitle">
                    Descubra as melhores ofertas de motocicletas verificadas por nossa equipe
                </p>
            </div>
            
            <?php if (empty($anuncios)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🏍️</div>
                    <h3 class="empty-title">Ainda não há anúncios</h3>
                    <p class="empty-subtitle">Seja o primeiro a publicar uma mota incrível!</p>
                    <a href="anuncio.php" class="btn btn-primary">Criar Primeiro Anúncio</a>
                </div>
            <?php else: ?>
                <div class="announcements-grid">
                    <?php foreach ($anuncios as $index => $anuncio): ?>
                        <?php 
                        // Gerar ID único e seguro para cada card
                        $cardId = 'card_' . $anuncio['id'] . '_' . $index;
                        $isProblematic = $index < 2; // Marcar os primeiros 2 para debug
                        
                        // PREPARAR DADOS PARA O MODAL
                        $imagensParaModal = json_encode($anuncio['galeria_imagens'], JSON_HEX_APOS | JSON_HEX_QUOT);
                        $descricaoEscapada = htmlspecialchars($anuncio['descricao'] ?? 'Sem descrição disponível.', ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="announcement-card fade-in-up <?= isset($_GET['debug']) ? ($isProblematic ? 'card-problematico' : 'card-ok') : '' ?>" 
                             style="animation-delay: <?= $index * 0.1 ?>s;"
                             data-anuncio-id="<?= $anuncio['id'] ?>"
                             data-card-index="<?= $index ?>"
                             data-debug-position="<?= $anuncio['_debug_position'] ?>"
                             id="<?= $cardId ?>"
                             
                             <?php // DATA ATTRIBUTES PARA O MODAL ?>
                             data-anuncio-titulo="<?= htmlspecialchars($anuncio['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                             data-anuncio-preco="<?= $anuncio['preco'] ?>"
                             data-anuncio-marca="<?= htmlspecialchars($anuncio['marca'], ENT_QUOTES, 'UTF-8') ?>"
                             data-anuncio-modelo="<?= htmlspecialchars($anuncio['modelo'], ENT_QUOTES, 'UTF-8') ?>"
                             data-anuncio-ano="<?= $anuncio['ano'] ?>"
                             data-anuncio-telefone="<?= htmlspecialchars($anuncio['telefone'], ENT_QUOTES, 'UTF-8') ?>"
                             data-anuncio-vendedor="<?= htmlspecialchars($anuncio['vendedor'], ENT_QUOTES, 'UTF-8') ?>"
                             data-anuncio-data="<?= $anuncio['data_criacao'] ?>"
                             data-anuncio-descricao="<?= $descricaoEscapada ?>"
                             data-anuncio-imagens='<?= $imagensParaModal ?>'>
                            
                            <!-- Indicador de clique -->
                            <div class="card-click-indicator">Clique para ver</div>
                            
                            <!-- Debug info (apenas se ?debug na URL) -->
                            <?php if (isset($_GET['debug'])): ?>
                                <div class="debug-info">
                                    P<?= $index + 1 ?>|ID:<?= $anuncio['id'] ?>|I:<?= $anuncio['total_imagens'] ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- CAROUSEL OTIMIZADO PARA 3 COLUNAS -->
                            <div class="carousel-container" data-carousel-id="<?= $anuncio['id'] ?>">
                                
                                <!-- SLIDES -->
                                <div class="carousel" id="carousel_<?= $anuncio['id'] ?>_<?= $index ?>">
                                    <?php foreach ($anuncio['galeria_imagens'] as $imgIndex => $imagem): ?>
                                        <div class="carousel-slide">
                                            <?php if ($imagem): ?>
                                                <img src="<?= htmlspecialchars($imagem) ?>" 
                                                     alt="<?= htmlspecialchars($anuncio['titulo']) ?> - Imagem <?= $imgIndex + 1 ?>" 
                                                     class="carousel-image"
                                                     loading="lazy"
                                                     style="opacity: 0; transition: opacity 0.3s ease;"
                                                     onload="this.style.opacity='1'"
                                                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=&quot;carousel-placeholder&quot;>🏍️<div class=&quot;placeholder-text&quot;>Imagem não disponível</div></div>'">
                                            <?php else: ?>
                                                <div class="carousel-placeholder">
                                                    🏍️
                                                    <div class="placeholder-text">Sem imagem disponível</div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- CONTROLES (apenas se múltiplas imagens) -->
                                <?php if (count($anuncio['galeria_imagens']) > 1): ?>
                                    <button class="carousel-controls carousel-prev" 
                                            type="button"
                                            data-anuncio-target="<?= $anuncio['id'] ?>"
                                            data-card-position="<?= $index ?>"
                                            aria-label="Imagem anterior">‹</button>
                                    <button class="carousel-controls carousel-next" 
                                            type="button"
                                            data-anuncio-target="<?= $anuncio['id'] ?>"
                                            data-card-position="<?= $index ?>"
                                            aria-label="Próxima imagem">›</button>
                                    
                                    <!-- INDICADORES -->
                                    <div class="carousel-indicators" id="indicators_<?= $anuncio['id'] ?>_<?= $index ?>">
                                        <?php foreach ($anuncio['galeria_imagens'] as $imgIndex => $imagem): ?>
                                            <button class="carousel-dot <?= $imgIndex === 0 ? 'active' : '' ?>" 
                                                    type="button"
                                                    data-anuncio-target="<?= $anuncio['id'] ?>"
                                                    data-slide-index="<?= $imgIndex ?>"
                                                    data-card-position="<?= $index ?>"
                                                    aria-label="Ir para imagem <?= $imgIndex + 1 ?>"></button>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- CONTADOR -->
                                    <div class="carousel-counter" id="counter_<?= $anuncio['id'] ?>_<?= $index ?>">
                                        1/<?= count(array_filter($anuncio['galeria_imagens'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- CONTEÚDO DO CARD OTIMIZADO PARA 3 COLUNAS -->
                            <div class="card-content">
                                <div class="card-main-content">
                                    <h3 class="card-title"><?= htmlspecialchars($anuncio['titulo']) ?></h3>
                                    <div class="card-price">€<?= number_format($anuncio['preco'], 2, ',', '.') ?></div>
                                    
                                    <div class="card-details">
                                        <div class="card-detail">
                                            <span>🏭</span>
                                            <span><?= htmlspecialchars($anuncio['marca']) ?></span>
                                        </div>
                                        <div class="card-detail">
                                            <span>🔧</span>
                                            <span><?= htmlspecialchars($anuncio['modelo']) ?></span>
                                        </div>
                                        <div class="card-detail">
                                            <span>📅</span>
                                            <span><?= $anuncio['ano'] ?></span>
                                        </div>
                                        <div class="card-detail">
                                            <span>📞</span>
                                            <span><?= htmlspecialchars($anuncio['telefone']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-bottom-content">
                                    <button class="contact-btn" 
                                            onclick="event.stopPropagation(); contactSeller('<?= htmlspecialchars($anuncio['telefone']) ?>', '<?= htmlspecialchars($anuncio['titulo']) ?>')">
                                        💬 Contactar Vendedor
                                    </button>
                                    
                                    <div class="card-footer">
                                        <span style="font-weight: 600;">
                                            👤 <?= htmlspecialchars($anuncio['vendedor']) ?>
                                        </span>
                                        <span>
                                            📅 <?= date('d/m/Y', strtotime($anuncio['data_criacao'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 4rem;">
                    <a href="anuncio.php" class="btn btn-primary">
                        ➕ Publicar Sua Mota Agora
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

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
                © 2025 - Projeto Académico - Todos os direitos reservados
            </p>
        </div>
    </footer>

    <!-- MODAL HTML CORRIGIDO -->
    <div id="anuncioModal" class="modal-overlay">
        <div class="modal-container">
            <button class="modal-close" onclick="closeAnuncioModal()">&times;</button>
            
            <div class="modal-content">
                <!-- Left Side - Carousel -->
                <div class="modal-left">
                    <div class="modal-carousel-container">
                        <div class="modal-carousel" id="modalCarousel">
                            <!-- Slides will be populated by JavaScript -->
                        </div>
                        
                        <!-- Controls -->
                        <button class="modal-carousel-controls modal-carousel-prev" onclick="changeModalSlide(-1)" id="modalPrevBtn">‹</button>
                        <button class="modal-carousel-controls modal-carousel-next" onclick="changeModalSlide(1)" id="modalNextBtn">›</button>
                        
                        <!-- Indicators -->
                        <div class="modal-carousel-indicators" id="modalIndicators">
                            <!-- Dots will be populated by JavaScript -->
                        </div>
                        
                        <!-- Counter -->
                        <div class="modal-carousel-counter" id="modalCounter">1/1</div>
                    </div>
                </div>
                
                <!-- Right Side - Details -->
                <div class="modal-right">
                    <h2 class="modal-title" id="modalTitle">Título do Anúncio</h2>
                    <div class="modal-price" id="modalPrice">€0,00</div>
                    
                    <div class="modal-details-grid" id="modalDetailsGrid">
                        <!-- Details will be populated by JavaScript -->
                    </div>
                    
                    <div class="modal-description" id="modalDescription">
                        <h4>📝 Descrição</h4>
                        <p>Descrição do anúncio...</p>
                    </div>
                    
                    <div class="modal-seller-info">
                        <div class="modal-seller-name" id="modalSellerName">Nome do Vendedor</div>
                        <div class="modal-seller-date" id="modalSellerDate">Data de criação</div>
                        
                        <button class="modal-contact-btn" id="modalContactBtn">
                            💬 Contactar pelo WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT COMPLETO PARA 3 COLUNAS -->
    <script>
        // ===============================================
        // SISTEMA DE CAROUSEL CORRIGIDO PARA 3 COLUNAS
        // ===============================================

        (function() {
            'use strict';
            
            window.SellUMotorcycle = window.SellUMotorcycle || {};
            
            class Carousel3Columns {
                constructor(cardElement) {
                    this.cardElement = cardElement;
                    this.anuncioId = String(cardElement.dataset.anuncioId);
                    this.cardIndex = parseInt(cardElement.dataset.cardIndex) || 0;
                    this.debugPosition = cardElement.dataset.debugPosition || 'unknown';
                    
                    // ID único para 3 colunas
                    this.uniqueId = `${this.anuncioId}_${this.cardIndex}`;
                    
                    this.currentSlide = 0;
                    this.totalSlides = 0;
                    this.isTransitioning = false;
                    
                    // Encontrar elementos específicos
                    this.carouselContainer = cardElement.querySelector('.carousel-container');
                    this.carousel = cardElement.querySelector(`#carousel_${this.uniqueId}`);
                    this.slides = cardElement.querySelectorAll('.carousel-slide');
                    this.prevBtn = cardElement.querySelector('.carousel-prev');
                    this.nextBtn = cardElement.querySelector('.carousel-next');
                    this.dots = cardElement.querySelectorAll('.carousel-dot');
                    this.counter = cardElement.querySelector(`#counter_${this.uniqueId}`);
                    
                    this.totalSlides = this.slides.length;
                    
                    console.log(`🎠 [POSIÇÃO ${this.debugPosition}] Carousel 3 colunas: ${this.anuncioId} com ${this.totalSlides} slides`);
                    
                    this.init();
                }
                
                init() {
                    if (this.totalSlides <= 1) {
                        console.log(`⚠️ [POSIÇÃO ${this.debugPosition}] Carousel ${this.anuncioId}: ${this.totalSlides} slide(s)`);
                        return;
                    }
                    
                    this.setupEventListeners();
                    this.updateDisplay();
                    
                    console.log(`✅ [POSIÇÃO ${this.debugPosition}] Carousel ${this.anuncioId} inicializado`);
                }
                
                setupEventListeners() {
                    if (this.prevBtn) {
                        this.prevBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            this.changeSlide(-1);
                        });
                    }
                    
                    if (this.nextBtn) {
                        this.nextBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            this.changeSlide(1);
                        });
                    }
                    
                    this.dots.forEach((dot, index) => {
                        dot.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            this.goToSlide(index);
                        });
                    });
                }
                
                changeSlide(direction) {
                    if (this.isTransitioning) return;
                    
                    this.isTransitioning = true;
                    
                    const oldSlide = this.currentSlide;
                    this.currentSlide += direction;
                    
                    if (this.currentSlide < 0) {
                        this.currentSlide = this.totalSlides - 1;
                    } else if (this.currentSlide >= this.totalSlides) {
                        this.currentSlide = 0;
                    }
                    
                    this.updateDisplay();
                    
                    setTimeout(() => {
                        this.isTransitioning = false;
                    }, 400);
                }
                
                goToSlide(slideIndex) {
                    if (this.isTransitioning || slideIndex === this.currentSlide) return;
                    
                    this.isTransitioning = true;
                    this.currentSlide = slideIndex;
                    this.updateDisplay();
                    
                    setTimeout(() => {
                        this.isTransitioning = false;
                    }, 400);
                }
                
                updateDisplay() {
                    if (this.carousel) {
                        const translateX = -this.currentSlide * 100;
                        this.carousel.style.transform = `translateX(${translateX}%)`;
                    }
                    
                    this.dots.forEach((dot, index) => {
                        if (index === this.currentSlide) {
                            dot.classList.add('active');
                        } else {
                            dot.classList.remove('active');
                        }
                    });
                    
                    if (this.counter) {
                        this.counter.textContent = `${this.currentSlide + 1}/${this.totalSlides}`;
                    }
                }
            }
            
            class CarouselManager3Columns {
                constructor() {
                    this.carousels = new Map();
                }
                
                init() {
                    console.log('🚀 Inicializando CarouselManager para 3 colunas...');
                    
                    const cards = document.querySelectorAll('.announcement-card[data-anuncio-id]');
                    console.log(`📦 Cards encontrados: ${cards.length}`);
                    
                    cards.forEach((card, index) => {
                        const anuncioId = String(card.dataset.anuncioId);
                        const cardIndex = parseInt(card.dataset.cardIndex) || index;
                        const uniqueKey = `${anuncioId}_${cardIndex}`;
                        
                        const hasCarousel = card.querySelector('.carousel-container');
                        if (!hasCarousel) return;
                        
                        if (this.carousels.has(uniqueKey)) {
                            this.carousels.delete(uniqueKey);
                        }
                        
                        const carousel = new Carousel3Columns(card);
                        this.carousels.set(uniqueKey, carousel);
                    });
                    
                    console.log(`✅ CarouselManager 3 colunas: ${this.carousels.size} carousels ativos`);
                }
            }
            
            // Inicialização para 3 colunas
            function initializeCarousels3Columns() {
                console.log('🎬 Inicializando sistema para 3 colunas...');
                
                if (window.SellUMotorcycle.carouselManager) {
                    window.SellUMotorcycle.carouselManager.carousels.clear();
                }
                
                window.SellUMotorcycle.carouselManager = new CarouselManager3Columns();
                window.SellUMotorcycle.carouselManager.init();
                
                console.log('🎉 Sistema 3 colunas inicializado!');
            }
            
            // Inicialização segura
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeCarousels3Columns);
            } else {
                initializeCarousels3Columns();
            }
            
            console.log('🏍️ Sistema carousel 3 colunas carregado');
            
        })();

        // ===============================================
        // MODAL CAROUSEL SYSTEM (MANTIDO IGUAL)
        // ===============================================

        class ModalCarouselFixed {
            constructor() {
                this.currentSlide = 0;
                this.totalSlides = 0;
                this.slides = [];
                this.isTransitioning = false;
                this.initialized = false;
            }

            init(images) {
                this.slides = this.processImages(images);
                this.totalSlides = this.slides.length;
                this.currentSlide = 0;
                this.isTransitioning = false;
                this.initialized = false;

                if (this.totalSlides === 0) {
                    this.slides = [{ type: 'placeholder', src: null, index: 0 }];
                    this.totalSlides = 1;
                }

                this.renderSlides();
                this.renderIndicators();
                this.setupControls();
                this.updateDisplay();
                
                this.initialized = true;
            }

            processImages(rawImages) {
                let processedImages = [];
                const imageArray = Array.isArray(rawImages) ? rawImages : [rawImages];
                
                imageArray.forEach((img, index) => {
                    if (img && typeof img === 'string' && img.trim() !== '' && img.trim() !== 'null') {
                        const trimmedImg = img.trim();
                        const isDuplicate = processedImages.some(processed => processed.src === trimmedImg);
                        
                        if (!isDuplicate) {
                            processedImages.push({
                                type: 'image',
                                src: trimmedImg,
                                index: processedImages.length,
                                originalIndex: index
                            });
                        }
                    }
                });
                
                return processedImages;
            }

            renderSlides() {
                const carousel = document.getElementById('modalCarousel');
                if (!carousel) return;

                carousel.innerHTML = '';
                carousel.style.transform = 'translateX(0%)';

                this.slides.forEach((slideData, index) => {
                    const slide = document.createElement('div');
                    slide.className = 'modal-carousel-slide';
                    slide.setAttribute('data-slide-index', index);
                    slide.setAttribute('data-slide-type', slideData.type);
                    
                    slide.style.position = 'absolute';
                    slide.style.top = '0';
                    slide.style.left = '0';
                    slide.style.width = '100%';
                    slide.style.height = '100%';
                    slide.style.opacity = '0';
                    slide.style.visibility = 'hidden';
                    slide.style.zIndex = '1';
                    
                    if (index === 0) {
                        slide.classList.add('active');
                        slide.style.opacity = '1';
                        slide.style.visibility = 'visible';
                        slide.style.zIndex = '10';
                    }
                    
                    if (slideData.type === 'image' && slideData.src) {
                        const img = document.createElement('img');
                        img.src = slideData.src;
                        img.alt = `Imagem ${index + 1} de ${this.slides.length}`;
                        img.className = 'modal-carousel-image';
                        img.setAttribute('data-slide-index', index);
                        img.style.opacity = '0';
                        img.style.transition = 'opacity 0.3s ease';
                        
                        img.onload = () => {
                            img.style.opacity = '1';
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.objectFit = 'contain';
                            img.style.objectPosition = 'center';
                        };
                        
                        img.onerror = () => {
                            slide.innerHTML = `
                                <div class="modal-carousel-placeholder">
                                    🏍️
                                    <div class="modal-placeholder-text">Imagem ${index + 1} não disponível</div>
                                </div>
                            `;
                        };
                        
                        slide.appendChild(img);
                    } else {
                        slide.innerHTML = `
                            <div class="modal-carousel-placeholder">
                                🏍️
                                <div class="modal-placeholder-text">Imagem ${index + 1} não disponível</div>
                            </div>
                        `;
                    }
                    
                    carousel.appendChild(slide);
                });
            }

            renderIndicators() {
                const indicators = document.getElementById('modalIndicators');
                if (!indicators) return;

                indicators.innerHTML = '';

                if (this.totalSlides <= 1) {
                    indicators.style.display = 'none';
                    return;
                }

                indicators.style.display = 'flex';

                this.slides.forEach((slideData, index) => {
                    const dot = document.createElement('button');
                    dot.className = `modal-carousel-dot ${index === 0 ? 'active' : ''}`;
                    dot.setAttribute('data-slide-index', index);
                    dot.setAttribute('type', 'button');
                    
                    dot.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.goToSlide(index);
                    });
                    
                    indicators.appendChild(dot);
                });
            }

            setupControls() {
                const prevBtn = document.getElementById('modalPrevBtn');
                const nextBtn = document.getElementById('modalNextBtn');

                if (this.totalSlides <= 1) {
                    if (prevBtn) prevBtn.style.display = 'none';
                    if (nextBtn) nextBtn.style.display = 'none';
                } else {
                    if (prevBtn) prevBtn.style.display = 'flex';
                    if (nextBtn) nextBtn.style.display = 'flex';
                }
            }

            changeSlide(direction) {
                if (!this.initialized || this.isTransitioning || this.totalSlides <= 1) return;
                
                this.isTransitioning = true;
                const oldSlide = this.currentSlide;

                if (direction > 0) {
                    this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
                } else {
                    this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
                }

                this.updateDisplay();

                setTimeout(() => {
                    this.isTransitioning = false;
                }, 450);
            }

            goToSlide(slideIndex) {
                if (!this.initialized) return;

                const targetIndex = parseInt(slideIndex);
                
                if (this.isTransitioning || 
                    isNaN(targetIndex) || 
                    targetIndex === this.currentSlide || 
                    targetIndex < 0 || 
                    targetIndex >= this.totalSlides) return;
                
                this.isTransitioning = true;
                this.currentSlide = targetIndex;
                this.updateDisplay();

                setTimeout(() => {
                    this.isTransitioning = false;
                }, 450);
            }

            updateDisplay() {
                const carousel = document.getElementById('modalCarousel');
                if (!carousel) return;

                const allSlides = carousel.querySelectorAll('.modal-carousel-slide');
                allSlides.forEach((slide, index) => {
                    slide.classList.remove('active');
                    slide.style.opacity = '0';
                    slide.style.visibility = 'hidden';
                    slide.style.zIndex = '1';
                    slide.style.pointerEvents = 'none';
                });

                if (allSlides[this.currentSlide]) {
                    const currentSlide = allSlides[this.currentSlide];
                    currentSlide.classList.add('active');
                    currentSlide.style.opacity = '1';
                    currentSlide.style.visibility = 'visible';
                    currentSlide.style.zIndex = '10';
                    currentSlide.style.pointerEvents = 'auto';
                }

                const indicators = document.querySelectorAll('.modal-carousel-dot');
                indicators.forEach((dot, index) => {
                    if (index === this.currentSlide) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });

                const counter = document.getElementById('modalCounter');
                if (counter) {
                    counter.textContent = `${this.currentSlide + 1}/${this.totalSlides}`;
                }
            }

            destroy() {
                const carousel = document.getElementById('modalCarousel');
                if (carousel) {
                    carousel.innerHTML = '';
                    carousel.style.transform = 'translateX(0%)';
                }
                
                const indicators = document.getElementById('modalIndicators');
                if (indicators) {
                    indicators.innerHTML = '';
                }
                
                const counter = document.getElementById('modalCounter');
                if (counter) {
                    counter.textContent = '1/1';
                }
                
                this.initialized = false;
                this.currentSlide = 0;
                this.totalSlides = 0;
                this.slides = [];
                this.isTransitioning = false;
            }
        }

        // ===============================================
        // GLOBAL MODAL INSTANCE
        // ===============================================

        let modalCarouselInstance = null;

        // ===============================================
        // FUNÇÕES MODAIS
        // ===============================================

        function openAnuncioModal(anuncioData) {
            try {
                if (modalCarouselInstance) {
                    modalCarouselInstance.destroy();
                    modalCarouselInstance = null;
                }
                
                // Populate modal content
                document.getElementById('modalTitle').textContent = anuncioData.titulo || 'Título não disponível';
                
                const preco = parseFloat(anuncioData.preco || 0);
                document.getElementById('modalPrice').textContent = `€${preco.toLocaleString('pt-PT', {minimumFractionDigits: 2})}`;
                
                // Details grid
                const detailsGrid = document.getElementById('modalDetailsGrid');
                detailsGrid.innerHTML = `
                    <div class="modal-detail-item">
                        <span class="modal-detail-icon">🏭</span>
                        <span>Marca: ${anuncioData.marca || 'N/A'}</span>
                    </div>
                    <div class="modal-detail-item">
                        <span class="modal-detail-icon">🔧</span>
                        <span>Modelo: ${anuncioData.modelo || 'N/A'}</span>
                    </div>
                    <div class="modal-detail-item">
                        <span class="modal-detail-icon">📅</span>
                        <span>Ano: ${anuncioData.ano || 'N/A'}</span>
                    </div>
                    <div class="modal-detail-item">
                        <span class="modal-detail-icon">📞</span>
                        <span>Telefone: ${anuncioData.telefone || 'N/A'}</span>
                    </div>
                `;
                
                // Description
                const description = document.getElementById('modalDescription');
                description.innerHTML = `
                    <h4>📝 Descrição</h4>
                    <p>${anuncioData.descricao || 'Sem descrição disponível.'}</p>
                `;
                
                // Seller info
                document.getElementById('modalSellerName').textContent = `👤 ${anuncioData.vendedor || 'Vendedor'}`;
                
                if (anuncioData.data) {
                    const dataFormatada = new Date(anuncioData.data).toLocaleDateString('pt-PT');
                    document.getElementById('modalSellerDate').textContent = `📅 Publicado em ${dataFormatada}`;
                } else {
                    document.getElementById('modalSellerDate').textContent = '📅 Data não disponível';
                }
                
                // Contact button
                const contactBtn = document.getElementById('modalContactBtn');
                if (anuncioData.telefone) {
                    contactBtn.onclick = () => {
                        const mensagem = `Olá! Tenho interesse na sua moto: ${anuncioData.titulo}`;
                        const whatsappUrl = `https://wa.me/351${anuncioData.telefone}?text=${encodeURIComponent(mensagem)}`;
                        window.open(whatsappUrl, '_blank');
                    };
                    contactBtn.style.display = 'block';
                } else {
                    contactBtn.style.display = 'none';
                }
                
                // Inicializar carousel
                let imagensParaModal = [];
                
                if (anuncioData.imagens && Array.isArray(anuncioData.imagens)) {
                    imagensParaModal = anuncioData.imagens;
                } else if (anuncioData.imagens && typeof anuncioData.imagens === 'string') {
                    try {
                        imagensParaModal = JSON.parse(anuncioData.imagens);
                    } catch (e) {
                        imagensParaModal = [];
                    }
                }
                
                modalCarouselInstance = new ModalCarouselFixed();
                modalCarouselInstance.init(imagensParaModal);
                
                // Show modal
                const modal = document.getElementById('anuncioModal');
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
                
            } catch (error) {
                console.error('❌ Erro ao abrir modal:', error);
            }
        }

        function closeAnuncioModal() {
            const modal = document.getElementById('anuncioModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
            
            if (modalCarouselInstance) {
                modalCarouselInstance.destroy();
                modalCarouselInstance = null;
            }
        }

        function changeModalSlide(direction) {
            if (modalCarouselInstance && modalCarouselInstance.initialized) {
                modalCarouselInstance.changeSlide(direction);
            }
        }

        function goToModalSlide(slideIndex) {
            if (modalCarouselInstance && modalCarouselInstance.initialized) {
                modalCarouselInstance.goToSlide(slideIndex);
            }
        }

        // ===============================================
        // CARD CLICK HANDLERS
        // ===============================================

        function setupCardClickHandlers() {
            const cards = document.querySelectorAll('.announcement-card[data-anuncio-id]');
            
            cards.forEach((card, index) => {
                const anuncioId = card.dataset.anuncioId;
                
                // Add click indicator if not exists
                const existingIndicator = card.querySelector('.card-click-indicator');
                if (!existingIndicator) {
                    const indicator = document.createElement('div');
                    indicator.className = 'card-click-indicator';
                    indicator.textContent = 'Clique para ver';
                    card.style.position = 'relative';
                    card.appendChild(indicator);
                }
                
                card.addEventListener('click', function(e) {
                    // Prevent if clicking on carousel controls or contact button
                    if (e.target.closest('.carousel-controls') || 
                        e.target.closest('.carousel-dot') ||
                        e.target.closest('.contact-btn')) {
                        return;
                    }
                    
                    const anuncioData = extractAnuncioData(this);
                    
                    if (anuncioData) {
                        openAnuncioModal(anuncioData);
                    }
                });
            });
        }

        function extractAnuncioData(cardElement) {
            try {
                const basicData = {
                    id: cardElement.dataset.anuncioId,
                    titulo: cardElement.dataset.anuncioTitulo,
                    preco: cardElement.dataset.anuncioPreco,
                    marca: cardElement.dataset.anuncioMarca,
                    modelo: cardElement.dataset.anuncioModelo,
                    ano: cardElement.dataset.anuncioAno,
                    telefone: cardElement.dataset.anuncioTelefone,
                    vendedor: cardElement.dataset.anuncioVendedor,
                    data: cardElement.dataset.anuncioData,
                    descricao: cardElement.dataset.anuncioDescricao
                };
                
                let imagens = [];
                
                const imagensRaw = cardElement.dataset.anuncioImagens;
                
                if (imagensRaw) {
                    try {
                        const imagensParsed = JSON.parse(imagensRaw);
                        
                        if (Array.isArray(imagensParsed)) {
                            imagens = imagensParsed
                                .filter(img => img && typeof img === 'string' && img.trim() !== '' && img.trim() !== 'null')
                                .map(img => img.trim())
                                .filter((img, index, arr) => arr.indexOf(img) === index);
                        }
                    } catch (parseError) {
                        if (imagensRaw.trim() !== '' && imagensRaw.trim() !== 'null') {
                            imagens = [imagensRaw.trim()];
                        }
                    }
                }
                
                if (imagens.length === 0) {
                    const imagensDOM = cardElement.querySelectorAll('.carousel-image');
                    imagensDOM.forEach((img, index) => {
                        if (img.src && img.src !== '' && !img.src.includes('placeholder')) {
                            const imgSrc = img.src.trim();
                            if (!imagens.includes(imgSrc)) {
                                imagens.push(imgSrc);
                            }
                        }
                    });
                }
                
                const finalData = {
                    ...basicData,
                    imagens: imagens
                };
                
                if (!finalData.id) {
                    return null;
                }
                
                return finalData;
                
            } catch (error) {
                console.error('❌ Erro ao extrair dados do card:', error);
                return null;
            }
        }

        // ===============================================
        // EVENT LISTENERS
        // ===============================================

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAnuncioModal();
            }
        });

        // Close modal on overlay click
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'anuncioModal') {
                closeAnuncioModal();
            }
        });

        // ===============================================
        // OUTRAS FUNÇÕES UTILITÁRIAS
        // ===============================================
        
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
                    window.location.href = '?logout=1';
                }, 1000);
            }
        }

        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            const arrow = document.getElementById('dropdownArrow');
            
            dropdown.classList.toggle('show');
            arrow.style.transform = dropdown.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        function contactSeller(telefone, titulo) {
            const mensagem = `Olá! Tenho interesse na sua moto: ${titulo}`;
            const whatsappUrl = `https://wa.me/351${telefone}?text=${encodeURIComponent(mensagem)}`;
            
            if (window.innerWidth > 768) {
                window.open(whatsappUrl, '_blank');
            } else {
                window.location.href = whatsappUrl;
            }
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

        // ===============================================
        // INICIALIZAÇÃO PRINCIPAL
        // ===============================================

        document.addEventListener('DOMContentLoaded', function() {
            // Animar cards quando carregar
            document.querySelectorAll('.announcement-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(50px)';
                card.style.transition = 'all 0.8s ease-out';
                observer.observe(card);
            });

            // Inicializar sistema de modal
            setTimeout(() => {
                setupCardClickHandlers();
                
                const cards = document.querySelectorAll('.announcement-card[data-anuncio-id]');
                console.log(`📊 Total de cards configurados para 3 colunas: ${cards.length}`);
                
            }, 500);
        });

        // ===============================================
        // CONSOLE LOGS FINAIS
        // ===============================================

        console.log('🎨 ===== LAYOUT 3 COLUNAS OTIMIZADO =====');
        console.log('📐 Desktop: 3 colunas fixas');
        console.log('📱 Tablet: 2 colunas');
        console.log('📱 Mobile: 1 coluna');
        console.log('📏 Cards: Altura compacta (520px)');
        console.log('🖼️ Carousel: Height reduzida (220px)');
        console.log('📝 Títulos: Tamanhos otimizados para 3 colunas');
        console.log('💰 Preços: Redimensionados proporcionalmente');
        console.log('📱 Responsivo: Breakpoints ajustados');
        console.log('🎯 Gap: 1.5rem para melhor aproveitamento');
        console.log('⚡ Performance: Otimizada para mais cards');
        console.log('🏍️ Index.php 3 COLUNAS COMPLETO carregado!');
        console.log('============================================');
    </script>
</body>
</html>