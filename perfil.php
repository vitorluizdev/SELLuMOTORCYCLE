<?php 
require_once 'config/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// ===============================================
// PROCESSAMENTO DE FORMULÁRIOS (mantido original)
// ===============================================

// Atualizar perfil do usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    
    $errors = [];
    
    // Validações
    if (empty($nome)) {
        $errors[] = "Nome é obrigatório";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email válido é obrigatório";
    }
    
    // Verificar se email já existe (exceto o próprio usuário)
    $stmt = $pdo->prepare("SELECT id FROM utilizadores WHERE email = ? AND id != ?");
    $stmt->execute([$email, getUserId()]);
    if ($stmt->fetch()) {
        $errors[] = "Este email já está em uso";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE utilizadores SET nome = ?, email = ?, telefone = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $telefone, getUserId()]);
        
        // Atualizar sessão
        $_SESSION['user_name'] = $nome;
        
        header('Location: perfil.php?success=profile_updated&tab=configuracoes');
        exit;
    } else {
        $profile_errors = $errors;
    }
}

// Atualizar anúncio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_anuncio') {
    $anuncio_id = (int)$_POST['anuncio_id'];
    
    // Verificar se o anúncio pertence ao usuário
    $stmt = $pdo->prepare("SELECT * FROM anuncios WHERE id = ? AND utilizador_id = ?");
    $stmt->execute([$anuncio_id, getUserId()]);
    $anuncio_existente = $stmt->fetch();
    
    if (!$anuncio_existente) {
        die("Anúncio não encontrado ou não autorizado");
    }
    
    $titulo = trim($_POST['titulo']);
    $marca = trim($_POST['marca']);
    $modelo = trim($_POST['modelo']);
    $ano = (int)$_POST['ano'];
    $preco = (float)$_POST['preco'];
    $telefone = trim($_POST['telefone']);
    $descricao = trim($_POST['descricao']);
    
    $errors = [];
    
    // Validações
    if (empty($titulo)) $errors[] = "Título é obrigatório";
    if (empty($marca)) $errors[] = "Marca é obrigatória";
    if (empty($modelo)) $errors[] = "Modelo é obrigatório";
    if ($ano < 1900 || $ano > date('Y') + 1) $errors[] = "Ano inválido";
    if ($preco <= 0) $errors[] = "Preço deve ser maior que zero";
    if (empty($telefone)) $errors[] = "Telefone é obrigatório";
    
    // Processar upload de imagens
    $imagens = [];
    for ($i = 1; $i <= 5; $i++) {
        $field_name = $i === 1 ? 'imagem' : "imagem$i";
        
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES[$field_name]['tmp_name'], $upload_path)) {
                    $imagens[$field_name] = $upload_path;
                }
            }
        } else {
            // Manter imagem existente se não foi enviada nova
            $imagens[$field_name] = $anuncio_existente[$field_name];
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE anuncios SET 
            titulo = ?, marca = ?, modelo = ?, ano = ?, preco = ?, 
            telefone = ?, descricao = ?, imagem = ?, imagem2 = ?, 
            imagem3 = ?, imagem4 = ?, imagem5 = ?
            WHERE id = ? AND utilizador_id = ?
        ");
        
        $stmt->execute([
            $titulo, $marca, $modelo, $ano, $preco, $telefone, $descricao,
            $imagens['imagem'], $imagens['imagem2'], $imagens['imagem3'], 
            $imagens['imagem4'], $imagens['imagem5'],
            $anuncio_id, getUserId()
        ]);
        
        header('Location: perfil.php?success=anuncio_updated&tab=anuncios');
        exit;
    } else {
        $anuncio_errors = $errors;
        $editing_anuncio_id = $anuncio_id;
    }
}

// ===============================================
// CORREÇÃO PRINCIPAL: CONSULTA COM IDENTIFICADORES ÚNICOS
// ===============================================

// Buscar anúncios do utilizador COM identificadores únicos
$stmt = $pdo->prepare("SELECT * FROM anuncios WHERE utilizador_id = ? ORDER BY data_criacao DESC");
$stmt->execute([getUserId()]);
$anuncios_raw = $stmt->fetchAll();

// CORREÇÃO: Processar anúncios com identificadores únicos baseados em posição
$meus_anuncios = [];
foreach ($anuncios_raw as $position => $anuncio_original) {
    // Criar cópia do anúncio
    $anuncio = $anuncio_original;
    
    // CHAVE ÚNICA baseada em ID + posição
    $anuncio['unique_key'] = $anuncio['id'] . '_' . $position;
    $anuncio['card_position'] = $position;
    
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
    
    $meus_anuncios[] = $anuncio;
}

// Buscar estatísticas do usuário
$stmt = $pdo->prepare("SELECT COUNT(*) FROM anuncios WHERE utilizador_id = ? AND ativo = 1");
$stmt->execute([getUserId()]);
$anuncios_ativos = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM anuncios WHERE utilizador_id = ?");
$stmt->execute([getUserId()]);
$total_anuncios = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(valor) FROM pagamentos WHERE utilizador_id = ?");
$stmt->execute([getUserId()]);
$total_gasto = $stmt->fetchColumn() ?? 0;

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM utilizadores WHERE id = ?");
$stmt->execute([getUserId()]);
$usuario = $stmt->fetch();

// Garantir que o tipo de utilizador esteja na sessão para isAdmin()
if (!isset($_SESSION['user_tipo']) && isset($usuario['tipo'])) {
    $_SESSION['user_tipo'] = $usuario['tipo'];
}

// Remover anúncio
if (isset($_GET['remover'])) {
    $stmt = $pdo->prepare("DELETE FROM anuncios WHERE id = ? AND utilizador_id = ?");
    $stmt->execute([$_GET['remover'], getUserId()]);
    header('Location: perfil.php?tab=anuncios');
    exit;
}

$tab_ativa = $_GET['tab'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - <?= SITE_NOME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <!-- FONTE INTER IGUAL AO INDEX.PHP -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏍️</text></svg>">
    
    <style>
        /* ===============================================
           FONTES E TIPOGRAFIA CONSISTENTES COM INDEX.PHP
           =============================================== */

        /* LOGO SEM FUNCIONALIDADE DE UPLOAD */
        .custom-logo-placeholder::before {
            display: none !important;
            content: none !important;
        }

        .custom-logo-placeholder:not(.has-image)::after {
            content: 'LOGO';
            font-size: 0.7rem;
            font-weight: 900;
            color: var(--primary-black);
            text-align: center;
            letter-spacing: 1px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
        }

        /* ===============================================
           VARIÁVEIS CSS ATUALIZADAS
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
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            --shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.25);
            --shadow-yellow: 0 4px 20px rgba(255, 215, 0, 0.4);
            --transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        /* ===============================================
           TIPOGRAFIA PRINCIPAL - IGUAL AO INDEX.PHP
           =============================================== */
        
        body {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            line-height: 1.6;
            color: var(--primary-black);
            background: linear-gradient(135deg, var(--primary-black) 0%, var(--secondary-black) 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        /* Garantir que todos os elementos herdem a fonte correta */
        * {
            font-family: inherit;
        }

        /* Botões, inputs e elementos de formulário */
        button, input, select, textarea, .btn, .modal-btn {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif !important;
            font-weight: 500;
        }

        /* Headings com pesos específicos */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            font-weight: 700;
            line-height: 1.2;
        }

        /* Labels e textos importantes */
        label, .modal-form-label, .stat-label {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            font-weight: 600;
        }

        /* Títulos principais */
        .profile-details h1, .section-title, .modal-title {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            font-weight: 900;
        }

        /* Números e valores */
        .stat-number, .user-card-price, .card-price {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            font-weight: 900;
        }

        /* ===============================================
           HEADER CORRIGIDO - SEM ÍCONE DE MOTO
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

        /* ===============================================
           LOGO SECTION - SEM FUNCIONALIDADE DE UPLOAD
           =============================================== */
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex: 1;
        }

        /* Logo personalizada SEM funcionalidade de upload */
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
            transition: var(--transition);
        }

        /* Remover cursor pointer e efeitos de hover para upload */
        .custom-logo-placeholder {
            cursor: default !important;
        }

        .custom-logo-placeholder:hover {
            transform: none !important;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3) !important;
            border-color: var(--primary-black) !important;
        }

        .custom-logo-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 9px;
        }

        .custom-logo-placeholder.has-image::before {
            display: none !important;
        }

        /* Remover tooltips de upload */
        .custom-logo-placeholder::after {
            display: none !important;
        }

        /* Logo texto SEM ícone de moto */
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

        /* Texto principal dourado (sem dependência de ícone) */
        .logo-main {
            font-family: 'Inter', sans-serif;
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
            font-family: 'Inter', sans-serif;
        }

        .logo-highlight {
            font-family: 'Inter', sans-serif;
            font-size: 2.2rem;
            font-weight: 900;
            text-shadow: 0 0 15px rgba(255, 215, 0, 0.8),
                         0 0 25px rgba(255, 193, 7, 0.6),
                         0 0 35px rgba(255, 165, 0, 0.4);
            animation: balancedGlow 2s ease-in-out infinite alternate;
            filter: brightness(1.3) contrast(1.2);
            display: inline-block;
            position: relative;
        }

        .logo-sub {
            font-family: 'Inter', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--light-gray);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: -2px;
            opacity: 0.9;
        }

        /* Animações */
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

        /* Efeitos hover atualizados (sem ícone) */
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

        /* Navegação */
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
            font-family: 'Inter', sans-serif;
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
            background: var(--primary-yellow);
            color: var(--primary-black);
            border-color: var(--primary-yellow);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        /* Container para manter consistência */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ===============================================
           CAROUSEL NO PERFIL - SISTEMA CORRIGIDO
           =============================================== */
        
        /* Container do carousel para cards do perfil */
        .profile-carousel-container {
            position: relative;
            height: 150px;
            overflow: hidden;
            background: linear-gradient(45deg, var(--light-gray), var(--off-white));
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            isolation: isolate;
            cursor: pointer;
        }

        .profile-carousel {
            display: flex;
            height: 100%;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }

        .profile-carousel-slide {
            min-width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .profile-carousel-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
            backface-visibility: hidden;
        }

        .profile-carousel-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-black), var(--secondary-black));
            color: var(--primary-yellow);
            font-size: 3rem;
            flex-direction: column;
        }

        .profile-placeholder-text {
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.5rem;
            opacity: 0.8;
        }

        .profile-carousel-controls {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: var(--primary-yellow);
            border: 2px solid var(--primary-yellow);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: bold;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            backdrop-filter: blur(5px);
            z-index: 10;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            user-select: none;
        }

        .profile-carousel-container:hover .profile-carousel-controls {
            opacity: 1;
            transform: translateY(-50%) scale(1.1);
        }

        .profile-carousel-controls:hover {
            background: var(--primary-yellow);
            color: var(--primary-black);
            border-color: var(--primary-black);
            transform: translateY(-50%) scale(1.2);
        }

        .profile-carousel-controls:active {
            transform: translateY(-50%) scale(0.95);
        }

        .profile-carousel-prev {
            left: 6px;
        }

        .profile-carousel-next {
            right: 6px;
        }

        .profile-carousel-controls:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            transform: translateY(-50%) scale(0.9);
        }

        .profile-carousel-indicators {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 4px;
            background: rgba(0, 0, 0, 0.7);
            padding: 4px 8px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .profile-carousel-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: var(--transition);
            border: none;
            padding: 0;
        }

        .profile-carousel-dot.active {
            background: var(--primary-yellow);
            transform: scale(1.3);
        }

        .profile-carousel-dot:hover {
            background: var(--primary-yellow);
            transform: scale(1.2);
        }

        .profile-image-counter {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: var(--primary-yellow);
            padding: 2px 6px;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            backdrop-filter: blur(10px);
        }

        .profile-multiple-images-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: linear-gradient(45deg, var(--primary-yellow), var(--secondary-yellow));
            color: var(--primary-black);
            padding: 2px 6px;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.6rem;
            font-weight: 700;
            border: 1px solid var(--primary-black);
            text-transform: uppercase;
            z-index: 10;
        }

        .user-announcement-card {
            background: var(--pure-white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 2px solid var(--light-gray);
            position: relative;
            isolation: isolate;
            contain: layout style;
            cursor: pointer;
        }

        .user-announcement-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary-yellow);
            box-shadow: var(--shadow-hover);
        }

        .profile-clickable-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255, 215, 0, 0.9);
            color: var(--primary-black);
            padding: 4px 8px;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            z-index: 15;
            backdrop-filter: blur(5px);
            border: 1px solid var(--primary-black);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* ===============================================
           MODAL MAIOR DO PERFIL
           =============================================== */

        .profile-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .profile-modal-overlay.show {
            display: flex;
            animation: profileModalFadeIn 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        @keyframes profileModalFadeIn {
            from { 
                opacity: 0; 
                backdrop-filter: blur(0px);
            }
            to { 
                opacity: 1; 
                backdrop-filter: blur(20px);
            }
        }

        .profile-modal-container {
            width: 95vw;
            max-width: 1400px;
            height: 90vh;
            max-height: 900px;
            background: var(--pure-white);
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
            border: 3px solid var(--primary-yellow);
            position: relative;
            overflow: hidden;
            animation: profileModalSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes profileModalSlideIn {
            from { 
                opacity: 0; 
                transform: translateY(-100px) scale(0.8);
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1);
            }
        }

        .profile-modal-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            height: 100%;
            min-height: 600px;
        }

        .profile-modal-left {
            position: relative;
            background: #f8f9fa;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 500px;
            isolation: isolate;
            contain: layout style paint;
        }

        .profile-modal-carousel-container {
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
            isolation: isolate;
            contain: layout style paint;
        }

        .profile-modal-carousel {
            display: flex;
            height: 100%;
            width: 100%;
            position: relative;
            overflow: hidden;
            backface-visibility: hidden;
            transition: none;
        }

        .profile-modal-carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            overflow: hidden;
            padding: 10px;
            isolation: isolate;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }

        .profile-modal-carousel-slide.active {
            opacity: 1;
            visibility: visible;
            z-index: 10;
        }

        .profile-modal-carousel-slide:not(.active) {
            opacity: 0 !important;
            visibility: hidden !important;
            z-index: 1 !important;
            pointer-events: none !important;
        }

        .profile-modal-carousel-slide:not(.active) * {
            display: none !important;
        }

        .profile-modal-carousel-slide.active * {
            display: block !important;
        }

        .profile-modal-carousel-slide.active .profile-modal-carousel-placeholder {
            display: flex !important;
        }

        .profile-modal-carousel-image {
            width: 100%;
            height: 100%;
            max-width: none;
            max-height: none;
            min-width: 100%;
            min-height: 100%;
            object-fit: contain;
            object-position: center;
            transition: transform 0.3s ease, opacity 0.3s ease;
            display: block;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            background: #ffffff;
            backface-visibility: hidden;
            transform-style: preserve-3d;
        }

        .profile-modal-carousel-image:hover {
            transform: scale(1.03);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
        }

        .profile-modal-carousel-placeholder {
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

        .profile-modal-placeholder-text {
            font-size: 1.3rem;
            margin-top: 1rem;
            opacity: 0.8;
            font-weight: 600;
            line-height: 1.4;
            color: #6c757d;
        }

        .profile-modal-carousel-controls {
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

        .profile-modal-carousel-controls:hover {
            background: #FFD700;
            color: #000000;
            border-color: #000000;
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        .profile-modal-carousel-controls:active {
            transform: translateY(-50%) scale(0.95);
        }

        .profile-modal-carousel-prev {
            left: 20px;
        }

        .profile-modal-carousel-next {
            right: 20px;
        }

        .profile-modal-carousel-indicators {
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
            -ms-overflow-style: none;
        }

        .profile-modal-carousel-indicators::-webkit-scrollbar {
            display: none;
        }

        .profile-modal-carousel-dot {
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

        .profile-modal-carousel-dot.active {
            background: #FFD700;
            transform: scale(1.5);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.8);
        }

        .profile-modal-carousel-dot:hover {
            background: #FFD700;
            transform: scale(1.3);
        }

        .profile-modal-carousel-counter {
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

        .profile-modal-right {
            background: var(--pure-white);
            padding: 2rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            border-left: 3px solid var(--primary-yellow);
        }

        .profile-modal-title {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary-black);
            margin: 0;
            line-height: 1.2;
        }

        .profile-modal-price {
            font-size: 2.5rem;
            font-weight: 900;
            color: #B8860B;
            margin: 0;
        }

        .profile-modal-details-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            padding: 1.5rem;
            background: var(--off-white);
            border-radius: var(--border-radius);
            border: 2px solid var(--light-gray);
        }

        .profile-modal-detail-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.95rem;
            padding: 1rem;
            background: var(--pure-white);
            border-radius: 8px;
            border-left: 4px solid var(--primary-yellow);
            min-height: 60px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
        }

        .profile-modal-detail-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .profile-modal-detail-icon {
            font-size: 1.3rem;
            width: 30px;
            min-width: 30px;
            text-align: center;
            flex-shrink: 0;
        }

        .profile-modal-detail-content {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex: 1;
            min-width: 0;
        }

        .profile-modal-detail-label {
            font-weight: 700;
            color: var(--primary-black);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }

        .profile-modal-detail-value {
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 1rem;
            word-break: break-word;
        }

        .profile-modal-description {
            background: var(--off-white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 2px solid var(--light-gray);
            margin: 1rem 0;
        }

        .profile-modal-description h3 {
            margin: 0 0 1rem 0;
            font-weight: 700;
            color: var(--primary-black);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }

        .profile-modal-description p {
            margin: 0;
            line-height: 1.7;
            color: var(--dark-gray);
            font-size: 1rem;
            text-align: justify;
            word-break: break-word;
        }

        .profile-modal-actions {
            background: linear-gradient(135deg, var(--primary-yellow), var(--secondary-yellow));
            padding: 2rem;
            border-radius: var(--border-radius);
            border: 2px solid var(--primary-black);
        }

        .profile-modal-contact-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .profile-modal-contact-info h3 {
            margin: 0;
            font-weight: 700;
            color: var(--primary-black);
        }

        .profile-modal-phone {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-black);
        }

        .profile-modal-whatsapp-btn {
            background: #25D366;
            color: var(--pure-white);
            border: 2px solid var(--primary-black);
            padding: 1rem 2rem;
            border-radius: 25px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
            font-size: 1.1rem;
            width: 100%;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-modal-whatsapp-btn:hover {
            background: #1DA851;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
        }

        .profile-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary-black);
            color: var(--primary-yellow);
            border: 2px solid var(--primary-yellow);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            font-weight: bold;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .profile-modal-close:hover {
            background: var(--danger-red);
            color: var(--pure-white);
            border-color: var(--pure-white);
            transform: scale(1.1) rotate(90deg);
        }

        /* RESTO DOS ESTILOS DO PERFIL */
        .profile-container {
            background: linear-gradient(135deg, var(--primary-black), var(--secondary-black));
            min-height: 100vh;
            padding-top: 100px;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-yellow), var(--secondary-yellow));
            color: var(--primary-black);
            padding: 3rem 0;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0,0,0,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
        }

        .profile-info {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary-black), var(--secondary-black));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-yellow);
            border: 5px solid var(--primary-black);
            box-shadow: var(--shadow);
        }

        .profile-details h1 {
            font-family: 'Inter', sans-serif;
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
        }

        .profile-details p {
            font-family: 'Inter', sans-serif;
            font-size: 1.2rem;
            font-weight: 500;
            opacity: 0.8;
        }

        .profile-stats {
            display: flex;
            gap: 3rem;
            margin-top: 2rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-family: 'Inter', sans-serif;
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary-black);
        }

        .stat-label {
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .main-content {
            background: var(--pure-white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 3px solid var(--primary-yellow);
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .sidebar-card {
            background: var(--pure-white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 2px solid var(--light-gray);
            transition: var(--transition);
        }

        .sidebar-card:hover {
            border-color: var(--primary-yellow);
            transform: translateY(-2px);
        }

        .sidebar-card h3 {
            font-family: 'Inter', sans-serif;
            color: var(--primary-black);
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .quick-action {
            background: linear-gradient(45deg, var(--primary-yellow), var(--secondary-yellow));
            color: var(--primary-black);
            border: 2px solid var(--primary-black);
            padding: 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            text-align: center;
            transition: var(--transition);
            display: block;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .quick-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-yellow);
        }

        .profile-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .tab-btn {
            background: var(--light-gray);
            color: var(--dark-gray);
            border: none;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tab-btn.active {
            background: linear-gradient(45deg, var(--primary-yellow), var(--secondary-yellow));
            color: var(--primary-black);
            border: 2px solid var(--primary-black);
        }

        .tab-btn:hover {
            background: var(--primary-yellow);
            color: var(--primary-black);
        }

        .user-announcements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .user-card-content {
            padding: 1.5rem;
        }

        .user-card-title {
            font-family: 'Inter', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 0.5rem;
        }

        .user-card-price {
            font-family: 'Inter', sans-serif;
            font-size: 1.3rem;
            font-weight: 900;
            color: #B8860B;
            margin-bottom: 1rem;
        }

        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-family: 'Inter', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: var(--success-green);
            color: var(--pure-white);
        }

        .status-pending {
            background: var(--warning-orange);
            color: var(--pure-white);
        }

        .status-inactive {
            background: var(--danger-red);
            color: var(--pure-white);
        }

        .card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 15px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-edit {
            background: var(--primary-yellow);
            color: var(--primary-black);
        }

        .btn-delete {
            background: var(--danger-red);
            color: var(--pure-white);
        }

        .btn-small:hover {
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--medium-gray);
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .empty-title {
            font-family: 'Inter', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 1rem;
        }

        .empty-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 2rem;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.show {
            display: flex;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal {
            background: var(--pure-white);
            border-radius: 20px;
            box-shadow: var(--shadow-hover);
            border: 3px solid var(--primary-yellow);
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from { 
                opacity: 0; 
                transform: translateY(-50px) scale(0.9); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-yellow), var(--secondary-yellow));
            color: var(--primary-black);
            padding: 2rem;
            border-radius: 17px 17px 0 0;
            position: relative;
        }

        .modal-title {
            font-family: 'Inter', sans-serif;
            font-size: 1.8rem;
            font-weight: 900;
            margin: 0;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary-black);
            color: var(--primary-yellow);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 1.2rem;
            font-weight: bold;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: var(--danger-red);
            color: var(--pure-white);
            transform: scale(1.1);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-form-group {
            margin-bottom: 1.5rem;
        }

        .modal-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .modal-form-label {
            display: block;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .modal-form-input,
        .modal-form-select,
        .modal-form-textarea {
            width: 100%;
            padding: 1rem;
            border: 3px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition);
            background: var(--pure-white);
        }

        .modal-form-input:focus,
        .modal-form-select:focus,
        .modal-form-textarea:focus {
            outline: none;
            border-color: var(--primary-yellow);
            box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.2);
        }

        .modal-form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .modal-actions {
            padding: 2rem;
            border-top: 2px solid var(--light-gray);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 1rem 2rem;
            border-radius: 25px;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-btn-primary {
            background: linear-gradient(45deg, var(--primary-yellow), var(--secondary-yellow));
            color: var(--primary-black);
            border-color: var(--primary-black);
        }

        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-yellow);
        }

        .modal-btn-secondary {
            background: transparent;
            color: var(--dark-gray);
            border-color: var(--medium-gray);
        }

        .modal-btn-secondary:hover {
            background: var(--medium-gray);
            color: var(--pure-white);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            font-family: 'Inter', sans-serif;
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

        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(135deg, var(--success-green), #66BB6A);
            color: var(--pure-white);
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-hover);
            z-index: 2001;
            display: none;
            align-items: center;
            gap: 0.75rem;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            max-width: 400px;
            animation: slideInRight 0.5s ease-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }

        .notification.show {
            display: flex;
        }

        .notification.error {
            background: linear-gradient(135deg, var(--danger-red), #F44336);
        }

        /* Responsivo */
        @media (max-width: 768px) {
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

            .logo-main {
                font-size: 1.4rem;
            }

            .logo-sub {
                font-size: 0.6rem;
                letter-spacing: 1px;
            }
            
            .modal {
                margin: 10px;
                max-height: 95vh;
            }
            
            .modal-form-row {
                grid-template-columns: 1fr;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .profile-carousel-controls {
                width: 30px;
                height: 30px;
                font-size: 0.9rem;
                opacity: 1;
            }
            
            .profile-carousel-container {
                height: 140px;
            }

            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .profile-info {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-stats {
                justify-content: center;
            }

            .user-announcements-grid {
                grid-template-columns: 1fr;
            }

            .profile-modal-content {
                grid-template-columns: 1fr;
                grid-template-rows: 60vh 1fr;
            }

            .profile-modal-right {
                max-height: 40vh;
                overflow-y: auto;
                border-left: none;
                border-top: 3px solid var(--primary-yellow);
            }
        }

        @media (max-width: 480px) {
            .logo-section {
                gap: 0.75rem;
            }
            
            .custom-logo-placeholder {
                width: 40px;
                height: 40px;
            }
            
            .logo-main {
                font-size: 1.2rem;
            }
            
            .profile-carousel-container {
                height: 120px;
            }
            
            .profile-carousel-controls {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
            }
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
    </style>
</head>
<body>
    <!-- Header SEM ícone de moto e SEM upload -->
    <header class="header">
        <div class="header-content">
            <!-- Seção da Logo SEM ícone de moto e SEM upload -->
            <div class="logo-section">
                <!-- Logo personalizada SEM funcionalidade de upload -->
                <div class="custom-logo-placeholder" id="customLogoPlaceholder">
                    <img src="assets/logo.png" alt="Logo Personalizada">   
                </div>
                
                <!-- Logo texto SEM ícone de moto -->
                <a href="index.php" class="logo">
                    <div class="logo-container">
                        <div class="logo-text">
                            <div class="logo-main">
                                SELL<span class="logo-highlight">u</span>MOTORCYCLE
                            </div>
                            <div class="logo-sub">Premium Marketplace</div>
                        </div>
                    </div>
                </a>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="anuncio.php">Criar Anúncio</a></li>
                    <li><a href="perfil.php" class="active">Perfil</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin/">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="?logout=1">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <?php if (isset($_GET['logout'])) logout(); ?>

    <!-- Notificação -->
    <div id="notification" class="notification">
        <span id="notificationMessage"></span>
    </div>

    <div class="profile-container">
        <!-- Header do Perfil -->
        <div class="profile-header">
            <div class="container">
                <div class="profile-info">
                    <div class="profile-avatar">
                        👤
                    </div>
                    <div class="profile-details">
                        <h1 style="display:flex;align-items:center;gap:12px;">
                            <?= htmlspecialchars(getUserName()) ?>
                            <?php if (isAdmin() || (isset($usuario['tipo']) && $usuario['tipo'] === 'admin')): ?>
                                <a id="btn-dashboard" href="admin.php" style="margin-left:8px;padding:6px 10px;border-radius:8px;background:linear-gradient(90deg,#FFD700,#FFC107);color:#000;text-decoration:none;font-weight:700;border:2px solid #000;">Dashboard</a>
                            <?php endif; ?>
                        </h1>
                        <p>Membro desde <?= date('F Y', strtotime($usuario['data_registo'])) ?></p>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= $total_anuncios ?></div>
                                <div class="stat-label">Anúncios Criados</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $anuncios_ativos ?></div>
                                <div class="stat-label">Anúncios Ativos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">€<?= number_format($total_gasto, 2) ?></div>
                                <div class="stat-label">Total Investido</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo do Perfil -->
        <div class="container">
            <div class="profile-content">
                <div class="main-content">
                    <!-- Tabs de Navegação -->
                    <div class="profile-tabs">
                        <button class="tab-btn <?= $tab_ativa == 'overview' ? 'active' : '' ?>" onclick="changeTab('overview')">
                            📊 Visão Geral
                        </button>
                        <button class="tab-btn <?= $tab_ativa == 'anuncios' ? 'active' : '' ?>" onclick="changeTab('anuncios')">
                            🏍️ Meus Anúncios
                        </button>
                        <button class="tab-btn <?= $tab_ativa == 'configuracoes' ? 'active' : '' ?>" onclick="changeTab('configuracoes')">
                            ⚙️ Configurações
                        </button>
                    </div>

                    <!-- Tab: Visão Geral -->
                    <div id="tab-overview" class="tab-content" style="display: <?= $tab_ativa == 'overview' ? 'block' : 'none' ?>;">
                        <h2 style="margin-bottom: 2rem; font-weight: 900; color: var(--primary-black);">📈 Dashboard</h2>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                            <div style="background: linear-gradient(135deg, var(--primary-yellow), var(--secondary-yellow)); padding: 2rem; border-radius: var(--border-radius); text-align: center; border: 3px solid var(--primary-black);">
                                <div style="font-size: 2.5rem; font-weight: 900; color: var(--primary-black);"><?= $total_anuncios ?></div>
                                <div style="font-weight: 700; color: var(--secondary-black);">Total de Anúncios</div>
                            </div>
                            <div style="background: linear-gradient(135deg, var(--success-green), #66BB6A); padding: 2rem; border-radius: var(--border-radius); text-align: center; border: 3px solid var(--primary-black);">
                                <div style="font-size: 2.5rem; font-weight: 900; color: var(--pure-white);"><?= $anuncios_ativos ?></div>
                                <div style="font-weight: 700; color: var(--pure-white);">Anúncios Ativos</div>
                            </div>
                            <div style="background: linear-gradient(135deg, var(--warning-orange), #FFB74D); padding: 2rem; border-radius: var(--border-radius); text-align: center; border: 3px solid var(--primary-black);">
                                <div style="font-size: 2.5rem; font-weight: 900; color: var(--pure-white);">€<?= number_format($total_gasto, 0) ?></div>
                                <div style="font-weight: 700; color: var(--pure-white);">Investimento Total</div>
                            </div>
                        </div>

                        <div style="background: var(--off-white); padding: 2rem; border-radius: var(--border-radius); border: 2px solid var(--light-gray);">
                            <h3 style="margin-bottom: 1rem; color: var(--primary-black); font-weight: 700;">🎯 Dicas para Vender Mais</h3>
                            <ul style="list-style: none; padding: 0;">
                                <li style="margin-bottom: 1rem; padding: 1rem; background: var(--pure-white); border-radius: 8px; border-left: 4px solid var(--primary-yellow);">
                                    <strong>📸 Fotos de Qualidade:</strong> Anúncios com fotos têm 5x mais visualizações
                                </li>
                                <li style="margin-bottom: 1rem; padding: 1rem; background: var(--pure-white); border-radius: 8px; border-left: 4px solid var(--primary-yellow);">
                                    <strong>💰 Preço Competitivo:</strong> Pesquise preços similares no mercado
                                </li>
                                <li style="margin-bottom: 1rem; padding: 1rem; background: var(--pure-white); border-radius: 8px; border-left: 4px solid var(--primary-yellow);">
                                    <strong>📝 Descrição Completa:</strong> Inclua marca, modelo, ano e estado
                                </li>
                                <li style="padding: 1rem; background: var(--pure-white); border-radius: 8px; border-left: 4px solid var(--primary-yellow);">
                                    <strong>📞 Contacto Rápido:</strong> Responda rapidamente aos interessados
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Tab: Meus Anúncios -->
                    <div id="tab-anuncios" class="tab-content" style="display: <?= $tab_ativa == 'anuncios' ? 'block' : 'none' ?>;">
                        <h2 style="margin-bottom: 2rem; font-weight: 900; color: var(--primary-black);">🏍️ Meus Anúncios</h2>
                        
                        <?php if (empty($meus_anuncios)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">🏍️</div>
                                <h3 class="empty-title">Ainda não tem anúncios</h3>
                                <p class="empty-subtitle">Comece agora e venda sua primeira moto!</p>
                                <a href="anuncio.php" class="btn btn-primary">Criar Primeiro Anúncio</a>
                            </div>
                        <?php else: ?>
                            <div class="user-announcements-grid">
                                <?php foreach ($meus_anuncios as $index => $anuncio): ?>
                                    <?php 
                                    $uniqueKey = $anuncio['unique_key'];
                                    $cardPosition = $anuncio['card_position'];
                                    ?>
                                    <div class="user-announcement-card fade-in-up" 
                                         style="animation-delay: <?= $index * 0.1 ?>s;"
                                         data-profile-anuncio-id="<?= $anuncio['id'] ?>"
                                         data-unique-key="<?= $uniqueKey ?>"
                                         data-card-position="<?= $cardPosition ?>"
                                         id="profile-card-<?= $uniqueKey ?>"
                                         onclick="openProfileModal(<?= $anuncio['id'] ?>, '<?= $uniqueKey ?>')">
                                        
                                        <div class="profile-clickable-badge">
                                            👁️ Ver Detalhes
                                        </div>
                                        
                                        <!-- CAROUSEL -->
                                        <div class="profile-carousel-container" data-carousel-unique="<?= $uniqueKey ?>">
                                            <div class="profile-carousel" id="profile-carousel-<?= $uniqueKey ?>">
                                                <?php foreach ($anuncio['galeria_imagens'] as $imgIndex => $imagem): ?>
                                                    <div class="profile-carousel-slide">
                                                        <?php if ($imagem): ?>
                                                            <img src="<?= htmlspecialchars($imagem) ?>" 
                                                                 alt="<?= htmlspecialchars($anuncio['titulo']) ?> - Imagem <?= $imgIndex + 1 ?>" 
                                                                 class="profile-carousel-image"
                                                                 loading="lazy">
                                                        <?php else: ?>
                                                            <div class="profile-carousel-placeholder">
                                                                🏍️
                                                                <div class="profile-placeholder-text">Sem imagem disponível</div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <?php if (count($anuncio['galeria_imagens']) > 1): ?>
                                                <button class="profile-carousel-controls profile-carousel-prev" 
                                                        type="button"
                                                        data-unique-target="<?= $uniqueKey ?>"
                                                        aria-label="Imagem anterior"
                                                        onclick="event.stopPropagation()">‹</button>
                                                <button class="profile-carousel-controls profile-carousel-next" 
                                                        type="button"
                                                        data-unique-target="<?= $uniqueKey ?>"
                                                        aria-label="Próxima imagem"
                                                        onclick="event.stopPropagation()">›</button>
                                                
                                                <div class="profile-carousel-indicators" id="profile-indicators-<?= $uniqueKey ?>" onclick="event.stopPropagation()">
                                                    <?php foreach ($anuncio['galeria_imagens'] as $imgIndex => $imagem): ?>
                                                        <button class="profile-carousel-dot <?= $imgIndex === 0 ? 'active' : '' ?>" 
                                                                type="button"
                                                                data-unique-target="<?= $uniqueKey ?>"
                                                                data-slide-index="<?= $imgIndex ?>"
                                                                aria-label="Ir para imagem <?= $imgIndex + 1 ?>"></button>
                                                    <?php endforeach; ?>
                                                </div>
                                                
                                                <div class="profile-image-counter" id="profile-counter-<?= $uniqueKey ?>">
                                                    1/<?= count(array_filter($anuncio['galeria_imagens'])) ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (count(array_filter($anuncio['galeria_imagens'])) > 1): ?>
                                                <div class="profile-multiple-images-badge">
                                                    📸 <?= count(array_filter($anuncio['galeria_imagens'])) ?> fotos
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- STATUS BADGE -->
                                        <div class="status-badge <?php 
                                            if ($anuncio['pago'] && $anuncio['ativo']) echo 'status-active';
                                            elseif ($anuncio['pago']) echo 'status-pending';
                                            else echo 'status-inactive';
                                        ?>">
                                            <?php 
                                                if ($anuncio['pago'] && $anuncio['ativo']) echo '✅ ATIVO';
                                                elseif ($anuncio['pago']) echo '⏳ APROVAÇÃO';
                                                else echo '❌ NÃO PAGO';
                                            ?>
                                        </div>
                                        
                                        <!-- CONTEÚDO DO CARD -->
                                        <div class="user-card-content">
                                            <h3 class="user-card-title"><?= htmlspecialchars($anuncio['titulo']) ?></h3>
                                            <div class="user-card-price">€<?= number_format($anuncio['preco'], 2, ',', '.') ?></div>
                                            
                                            <div style="font-size: 0.9rem; color: var(--dark-gray); margin-bottom: 1rem;">
                                                <strong><?= $anuncio['marca'] ?> <?= $anuncio['modelo'] ?></strong><br>
                                                Ano: <?= $anuncio['ano'] ?> | 📞 <?= $anuncio['telefone'] ?><br>
                                                Criado: <?= date('d/m/Y', strtotime($anuncio['data_criacao'])) ?>
                                                <?php if (count(array_filter($anuncio['galeria_imagens'])) > 1): ?>
                                                    <br><strong>📸 <?= count(array_filter($anuncio['galeria_imagens'])) ?> fotos</strong>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="card-actions" onclick="event.stopPropagation()">
                                                <?php if (!$anuncio['pago']): ?>
                                                    <a href="pagamento.php?id=<?= $anuncio['id'] ?>" class="btn-small" style="background: var(--success-green); color: var(--pure-white);">
                                                        💳 Pagar
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <button class="btn-small btn-edit" onclick="editarAnuncio(<?= $anuncio['id'] ?>)">
                                                    ✏️ Editar
                                                </button>
                                                
                                                <button class="btn-small btn-delete" onclick="removerAnuncio(<?= $anuncio['id'] ?>, '<?= htmlspecialchars($anuncio['titulo']) ?>')">
                                                    🗑️ Remover
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab: Configurações -->
                    <div id="tab-configuracoes" class="tab-content" style="display: <?= $tab_ativa == 'configuracoes' ? 'block' : 'none' ?>;">
                        <h2 style="margin-bottom: 2rem; font-weight: 900; color: var(--primary-black);">⚙️ Configurações da Conta</h2>
                        
                        <div style="background: var(--off-white); padding: 2rem; border-radius: var(--border-radius); border: 2px solid var(--light-gray);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                <h3 style="color: var(--primary-black); font-weight: 700;">📋 Informações Pessoais</h3>
                                <button class="modal-btn modal-btn-primary" onclick="openProfileEditModal()">
                                    ✏️ Editar Perfil
                                </button>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                <div>
                                    <label style="display: block; font-weight: 700; color: var(--primary-black); margin-bottom: 0.5rem;">Nome:</label>
                                    <div style="padding: 1rem; background: var(--pure-white); border-radius: 8px; border: 2px solid var(--light-gray);">
                                        <?= htmlspecialchars($usuario['nome']) ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <label style="display: block; font-weight: 700; color: var(--primary-black); margin-bottom: 0.5rem;">Email:</label>
                                    <div style="padding: 1rem; background: var(--pure-white); border-radius: 8px; border: 2px solid var(--light-gray);">
                                        <?= htmlspecialchars($usuario['email']) ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <label style="display: block; font-weight: 700; color: var(--primary-black); margin-bottom: 0.5rem;">Telefone:</label>
                                    <div style="padding: 1rem; background: var(--pure-white); border-radius: 8px; border: 2px solid var(--light-gray);">
                                        <?= $usuario['telefone'] ? htmlspecialchars($usuario['telefone']) : 'Não informado' ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <label style="display: block; font-weight: 700; color: var(--primary-black); margin-bottom: 0.5rem;">Tipo de Conta:</label>
                                    <div style="padding: 1rem; background: var(--pure-white); border-radius: 8px; border: 2px solid var(--light-gray);">
                                        <span style="background: var(--primary-yellow); color: var(--primary-black); padding: 0.25rem 0.75rem; border-radius: 15px; font-weight: 700; text-transform: uppercase;">
                                            <?= ucfirst($usuario['tipo']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar">
                    <div class="sidebar-card">
                        <h3>🚀 Ações Rápidas</h3>
                        <a href="anuncio.php" class="quick-action">
                            ➕ Criar Novo Anúncio
                        </a>
                        <a href="index.php" class="quick-action">
                            🏠 Voltar ao Início
                        </a>
                        <a href="?logout=1" class="quick-action" style="background: var(--danger-red); border-color: var(--primary-black);">
                            🚪 Sair da Conta
                        </a>
                    </div>
                    
                    <div class="sidebar-card">
                        <h3>📊 Estatísticas</h3>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 900; color: var(--primary-yellow);"><?= $total_anuncios ?></div>
                            <div style="font-size: 0.9rem; color: var(--dark-gray);">Anúncios Totais</div>
                        </div>
                        <hr style="margin: 1rem 0; border: 1px solid var(--light-gray);">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 900; color: var(--success-green);"><?= $anuncios_ativos ?></div>
                            <div style="font-size: 0.9rem; color: var(--dark-gray);">Anúncios Ativos</div>
                        </div>
                    </div>
                    
                    <div class="sidebar-card">
                        <h3>💡 Dica do Dia</h3>
                        <p style="font-size: 0.9rem; color: var(--dark-gray); line-height: 1.5;">
                            📸 Anúncios com múltiplas fotos de qualidade recebem até <strong>300% mais</strong> visualizações!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal do Perfil -->
    <div id="profileModalOverlay" class="profile-modal-overlay">
        <div class="profile-modal-container">
            <button class="profile-modal-close" onclick="closeProfileModal()">&times;</button>
            
            <div class="profile-modal-content">
                <div class="profile-modal-left">
                    <div class="profile-modal-carousel-container">
                        <div class="profile-modal-carousel" id="profileModalCarousel">
                        </div>
                        
                        <button class="profile-modal-carousel-controls profile-modal-carousel-prev" 
                                onclick="changeProfileModalSlide(-1)">‹</button>
                        <button class="profile-modal-carousel-controls profile-modal-carousel-next" 
                                onclick="changeProfileModalSlide(1)">›</button>
                        
                        <div class="profile-modal-carousel-indicators" id="profileModalIndicators">
                        </div>
                        
                        <div class="profile-modal-carousel-counter" id="profileModalCounter">1/1</div>
                    </div>
                </div>
                
                <div class="profile-modal-right" id="profileModalInfo">
                </div>
            </div>
        </div>
    </div>

    <!-- Modais de edição -->
    <div id="profileEditModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">✏️ Editar Perfil</h2>
                <button class="modal-close" onclick="closeModal('profileEditModal')">&times;</button>
            </div>
            <form method="POST" id="profileEditForm">
                <input type="hidden" name="action" value="update_profile">
                <div class="modal-body">
                    <?php if (isset($profile_errors)): ?>
                        <div class="modal-alert modal-alert-error">
                            <span>❌</span>
                            <div>
                                <?php foreach ($profile_errors as $error): ?>
                                    <div><?= htmlspecialchars($error) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="modal-form-group">
                        <label class="modal-form-label">Nome Completo *</label>
                        <input type="text" name="nome" class="modal-form-input" 
                               value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                    </div>
                    
                    <div class="modal-form-group">
                        <label class="modal-form-label">Email *</label>
                        <input type="email" name="email" class="modal-form-input" 
                               value="<?= htmlspecialchars($usuario['email']) ?>" required>
                    </div>
                    
                    <div class="modal-form-group">
                        <label class="modal-form-label">Telefone</label>
                        <input type="tel" name="telefone" class="modal-form-input" 
                               value="<?= htmlspecialchars($usuario['telefone']) ?>" 
                               placeholder="Ex: 912345678">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('profileEditModal')">
                        Cancelar
                    </button>
                    <button type="submit" class="modal-btn modal-btn-primary">
                        💾 Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="anuncioEditModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">🏍️ Editar Anúncio</h2>
                <button class="modal-close" onclick="closeModal('anuncioEditModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="anuncioEditForm">
                <input type="hidden" name="action" value="update_anuncio">
                <input type="hidden" name="anuncio_id" id="editAnuncioId">
                <div class="modal-body">
                    <div id="anuncioEditContent">
                        <!-- Conteúdo será carregado dinamicamente -->
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('anuncioEditModal')">
                        Cancelar
                    </button>
                    <button type="submit" class="modal-btn modal-btn-primary">
                        💾 Atualizar Anúncio
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Dados dos anúncios para JavaScript
        const anunciosData = <?= json_encode($meus_anuncios) ?>;
        
        // Sistema de modal maior do perfil
        let currentProfileModalSlide = 0;
        let profileModalImages = [];
        let currentProfileModalData = null;

        // Função para abrir o modal do perfil
        function openProfileModal(anuncioId, uniqueKey) {
            console.log(`🔍 [PERFIL MODAL] Abrindo modal para anúncio ${anuncioId}, chave ${uniqueKey}`);
            
            const anuncio = anunciosData.find(a => a.id == anuncioId);
            if (!anuncio) {
                console.error(`❌ [PERFIL MODAL] Anúncio não encontrado: ${anuncioId}`);
                showNotification('❌ Anúncio não encontrado!', 'error');
                return;
            }
            
            currentProfileModalData = anuncio;
            profileModalImages = anuncio.galeria_imagens.filter(img => img !== null && img !== '');
            currentProfileModalSlide = 0;
            
            console.log(`📸 [PERFIL MODAL] ${profileModalImages.length} imagens carregadas`);
            
            buildProfileModal(anuncio);
            
            const overlay = document.getElementById('profileModalOverlay');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            updateProfileModalCarousel();
            
            console.log(`✅ [PERFIL MODAL] Modal aberto com sucesso`);
        }

        // Função para construir o conteúdo do modal
        function buildProfileModal(anuncio) {
            const carouselContainer = document.getElementById('profileModalCarousel');
            carouselContainer.innerHTML = '';
            
            if (profileModalImages.length === 0) {
                carouselContainer.innerHTML = `
                    <div class="profile-modal-carousel-slide active">
                        <div class="profile-modal-carousel-placeholder">
                            🏍️
                            <div class="profile-modal-placeholder-text">
                                Nenhuma imagem disponível<br>
                                para este anúncio
                            </div>
                        </div>
                    </div>
                `;
            } else {
                profileModalImages.forEach((imagem, index) => {
                    const slideDiv = document.createElement('div');
                    slideDiv.className = `profile-modal-carousel-slide ${index === 0 ? 'active' : ''}`;
                    slideDiv.innerHTML = `
                        <img src="${imagem}" 
                             alt="${anuncio.titulo} - Imagem ${index + 1}" 
                             class="profile-modal-carousel-image"
                             loading="lazy">
                    `;
                    carouselContainer.appendChild(slideDiv);
                });
            }
            
            const indicatorsContainer = document.getElementById('profileModalIndicators');
            indicatorsContainer.innerHTML = '';
            
            if (profileModalImages.length > 1) {
                profileModalImages.forEach((_, index) => {
                    const dot = document.createElement('button');
                    dot.className = `profile-modal-carousel-dot ${index === 0 ? 'active' : ''}`;
                    dot.onclick = () => goToProfileModalSlide(index);
                    indicatorsContainer.appendChild(dot);
                });
            }
            
            const infoContainer = document.getElementById('profileModalInfo');
            infoContainer.innerHTML = `
                <h1 class="profile-modal-title">${anuncio.titulo}</h1>
                <div class="profile-modal-price">€${parseFloat(anuncio.preco).toLocaleString('pt-PT', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                
                <div class="profile-modal-details-grid">
                    <div class="profile-modal-detail-item">
                        <span class="profile-modal-detail-icon">🏭</span>
                        <div class="profile-modal-detail-content">
                            <span class="profile-modal-detail-label">Marca:</span>
                            <span class="profile-modal-detail-value">${anuncio.marca}</span>
                        </div>
                    </div>
                    <div class="profile-modal-detail-item">
                        <span class="profile-modal-detail-icon">🏍️</span>
                        <div class="profile-modal-detail-content">
                            <span class="profile-modal-detail-label">Modelo:</span>
                            <span class="profile-modal-detail-value">${anuncio.modelo}</span>
                        </div>
                    </div>
                    <div class="profile-modal-detail-item">
                        <span class="profile-modal-detail-icon">📅</span>
                        <div class="profile-modal-detail-content">
                            <span class="profile-modal-detail-label">Ano:</span>
                            <span class="profile-modal-detail-value">${anuncio.ano}</span>
                        </div>
                    </div>
                    <div class="profile-modal-detail-item">
                        <span class="profile-modal-detail-icon">📞</span>
                        <div class="profile-modal-detail-content">
                            <span class="profile-modal-detail-label">Contato:</span>
                            <span class="profile-modal-detail-value">${anuncio.telefone}</span>
                        </div>
                    </div>
                    <div class="profile-modal-detail-item">
                        <span class="profile-modal-detail-icon">📊</span>
                        <div class="profile-modal-detail-content">
                            <span class="profile-modal-detail-label">Status:</span>
                            <span class="profile-modal-detail-value" style="color: ${getStatusColor(anuncio)}; font-weight: 700;">${getStatusText(anuncio)}</span>
                        </div>
                    </div>
                    <div class="profile-modal-detail-item">
                        <span class="profile-modal-detail-icon">📸</span>
                        <div class="profile-modal-detail-content">
                            <span class="profile-modal-detail-label">Fotos:</span>
                            <span class="profile-modal-detail-value">${profileModalImages.length} imagem${profileModalImages.length !== 1 ? 's' : ''}</span>
                        </div>
                    </div>
                </div>
                
                ${anuncio.descricao ? `
                <div class="profile-modal-description">
                    <h3>📝 Descrição</h3>
                    <p>${anuncio.descricao}</p>
                </div>
                ` : ''}
                
                <div class="profile-modal-actions">
                    <div class="profile-modal-contact-info">
                        <h3>📞 Contato</h3>
                        <div class="profile-modal-phone">${anuncio.telefone}</div>
                    </div>
                    <a href="https://wa.me/351${anuncio.telefone.replace(/\D/g, '')}?text=${encodeURIComponent(`Olá! Tenho interesse na sua ${anuncio.marca} ${anuncio.modelo} (${anuncio.ano}) anunciada por €${parseFloat(anuncio.preco).toLocaleString('pt-PT', {minimumFractionDigits: 2, maximumFractionDigits: 2}).replace('.', ',')}. Pode dar-me mais informações?`)}" 
                       target="_blank" 
                       class="profile-modal-whatsapp-btn">
                        💬 Contactar via WhatsApp
                    </a>
                </div>
            `;
        }

        function getStatusText(anuncio) {
            if (anuncio.pago && anuncio.ativo) return '✅ Ativo';
            if (anuncio.pago) return '⏳ Pendente Aprovação';
            return '❌ Não Pago';
        }

        function getStatusColor(anuncio) {
            if (anuncio.pago && anuncio.ativo) return '#4CAF50';
            if (anuncio.pago) return '#FF9800';
            return '#F44336';
        }

        function changeProfileModalSlide(direction) {
            if (profileModalImages.length <= 1) return;
            
            currentProfileModalSlide += direction;
            
            if (currentProfileModalSlide < 0) {
                currentProfileModalSlide = profileModalImages.length - 1;
            } else if (currentProfileModalSlide >= profileModalImages.length) {
                currentProfileModalSlide = 0;
            }
            
            updateProfileModalCarousel();
        }

        function goToProfileModalSlide(slideIndex) {
            if (slideIndex < 0 || slideIndex >= profileModalImages.length) return;
            currentProfileModalSlide = slideIndex;
            updateProfileModalCarousel();
        }

        function updateProfileModalCarousel() {
            const slides = document.querySelectorAll('#profileModalCarousel .profile-modal-carousel-slide');
            slides.forEach((slide, index) => {
                if (index === currentProfileModalSlide) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });
            
            const dots = document.querySelectorAll('#profileModalIndicators .profile-modal-carousel-dot');
            dots.forEach((dot, index) => {
                if (index === currentProfileModalSlide) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
            
            const counter = document.getElementById('profileModalCounter');
            if (counter && profileModalImages.length > 0) {
                counter.textContent = `${currentProfileModalSlide + 1}/${profileModalImages.length}`;
            }
        }

        function closeProfileModal() {
            const overlay = document.getElementById('profileModalOverlay');
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
            
            currentProfileModalSlide = 0;
            profileModalImages = [];
            currentProfileModalData = null;
            
            console.log('🚪 [PERFIL MODAL] Modal fechado');
        }

        // Sistema de carousel para perfil
        (function() {
            'use strict';
            
            console.log('🏍️ Inicializando sistema de perfil...');
            
            class ProfileCarouselFixed {
                constructor(cardElement) {
                    this.cardElement = cardElement;
                    this.uniqueKey = cardElement.dataset.uniqueKey;
                    this.anuncioId = cardElement.dataset.profileAnuncioId;
                    this.cardPosition = parseInt(cardElement.dataset.cardPosition) || 0;
                    
                    this.currentSlide = 0;
                    this.totalSlides = 0;
                    this.isTransitioning = false;
                    
                    this.carouselContainer = cardElement.querySelector(`[data-carousel-unique="${this.uniqueKey}"]`);
                    this.carousel = cardElement.querySelector(`#profile-carousel-${this.uniqueKey}`);
                    this.slides = cardElement.querySelectorAll('.profile-carousel-slide');
                    this.prevBtn = cardElement.querySelector(`[data-unique-target="${this.uniqueKey}"].profile-carousel-prev`);
                    this.nextBtn = cardElement.querySelector(`[data-unique-target="${this.uniqueKey}"].profile-carousel-next`);
                    this.dots = cardElement.querySelectorAll(`[data-unique-target="${this.uniqueKey}"].profile-carousel-dot`);
                    this.counter = cardElement.querySelector(`#profile-counter-${this.uniqueKey}`);
                    this.indicators = cardElement.querySelector(`#profile-indicators-${this.uniqueKey}`);
                    
                    this.totalSlides = this.slides.length;
                    
                    console.log(`🎠 [PERFIL] Inicializando carousel ${this.uniqueKey} com ${this.totalSlides} slides`);
                    
                    this.init();
                }
                
                init() {
                    if (this.totalSlides <= 1) {
                        console.log(`⚠️ [PERFIL] Carousel ${this.uniqueKey}: Apenas ${this.totalSlides} slide(s)`);
                        return;
                    }
                    
                    if (!this.carousel) {
                        console.error(`❌ [PERFIL] Carousel não encontrado para ${this.uniqueKey}`);
                        return;
                    }
                    
                    this.setupEventListeners();
                    this.updateDisplay();
                    
                    console.log(`✅ [PERFIL] Carousel ${this.uniqueKey} inicializado`);
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
                    
                    this.setupTouchSupport();
                }
                
                setupTouchSupport() {
                    if (!this.carouselContainer) return;
                    
                    let startX = 0;
                    let endX = 0;
                    
                    this.carouselContainer.addEventListener('touchstart', (e) => {
                        startX = e.touches[0].clientX;
                    }, { passive: true });
                    
                    this.carouselContainer.addEventListener('touchend', (e) => {
                        endX = e.changedTouches[0].clientX;
                        const diff = startX - endX;
                        
                        if (Math.abs(diff) > 50) {
                            if (diff > 0) {
                                this.changeSlide(1);
                            } else {
                                this.changeSlide(-1);
                            }
                        }
                    }, { passive: true });
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
                    
                    console.log(`🔄 [PERFIL] Carousel ${this.uniqueKey}: ${oldSlide} → ${this.currentSlide}`);
                    
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
            
            class ProfileCarouselManager {
                constructor() {
                    this.carousels = new Map();
                }
                
                init() {
                    console.log('🚀 [PERFIL] Inicializando ProfileCarouselManager...');
                    
                    const cards = document.querySelectorAll('[data-unique-key]');
                    console.log(`📦 [PERFIL] Cards encontrados: ${cards.length}`);
                    
                    cards.forEach((card, index) => {
                        const uniqueKey = card.dataset.uniqueKey;
                        const anuncioId = card.dataset.profileAnuncioId;
                        
                        const hasCarousel = card.querySelector('.profile-carousel-container');
                        if (!hasCarousel) {
                            return;
                        }
                        
                        if (this.carousels.has(uniqueKey)) {
                            this.carousels.delete(uniqueKey);
                        }
                        
                        const carousel = new ProfileCarouselFixed(card);
                        this.carousels.set(uniqueKey, carousel);
                    });
                    
                    console.log(`✅ [PERFIL] ProfileCarouselManager: ${this.carousels.size} carousels ativos`);
                }
                
                getCarousel(uniqueKey) {
                    return this.carousels.get(uniqueKey);
                }
            }
            
            function initializeProfileCarousels() {
                if (window.ProfileCarouselSystem) {
                    window.ProfileCarouselSystem.carousels.clear();
                }
                
                window.ProfileCarouselSystem = new ProfileCarouselManager();
                window.ProfileCarouselSystem.init();
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeProfileCarousels);
            } else {
                initializeProfileCarousels();
            }
            
        })();

        // Funções do perfil
        function changeTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById('tab-' + tabName).style.display = 'block';
            
            event.target.classList.add('active');
            
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
            
            if (tabName === 'anuncios') {
                setTimeout(() => {
                    if (window.ProfileCarouselSystem) {
                        window.ProfileCarouselSystem.init();
                    }
                }, 100);
            }
        }

        function openProfileEditModal() {
            document.getElementById('profileEditModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function editarAnuncio(id) {
            const anuncio = anunciosData.find(a => a.id == id);
            if (!anuncio) {
                showNotification('❌ Anúncio não encontrado!', 'error');
                return;
            }

            document.getElementById('editAnuncioId').value = id;

            const content = `
                <div class="modal-form-group">
                    <label class="modal-form-label">Título do Anúncio *</label>
                    <input type="text" name="titulo" class="modal-form-input" 
                           value="${anuncio.titulo}" required>
                </div>

                <div class="modal-form-row">
                    <div class="modal-form-group">
                        <label class="modal-form-label">Marca *</label>
                        <input type="text" name="marca" class="modal-form-input" 
                               value="${anuncio.marca}" required>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-form-label">Modelo *</label>
                        <input type="text" name="modelo" class="modal-form-input" 
                               value="${anuncio.modelo}" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <div class="modal-form-group">
                        <label class="modal-form-label">Ano *</label>
                        <input type="number" name="ano" class="modal-form-input" 
                               value="${anuncio.ano}" min="1900" max="${new Date().getFullYear() + 1}" required>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-form-label">Preço (€) *</label>
                        <input type="number" name="preco" class="modal-form-input" 
                               value="${anuncio.preco}" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="modal-form-group">
                    <label class="modal-form-label">Telefone de Contato *</label>
                    <input type="tel" name="telefone" class="modal-form-input" 
                           value="${anuncio.telefone}" required>
                </div>

                <div class="modal-form-group">
                    <label class="modal-form-label">Descrição</label>
                    <textarea name="descricao" class="modal-form-textarea" 
                              placeholder="Descreva sua motocicleta...">${anuncio.descricao || ''}</textarea>
                </div>
            `;

            document.getElementById('anuncioEditContent').innerHTML = content;
            document.getElementById('anuncioEditModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function removerAnuncio(id, titulo) {
            if (confirm(`⚠️ Tem certeza que deseja remover o anúncio:\n\n"${titulo}"\n\nEsta ação não pode ser desfeita!`)) {
                window.location.href = `perfil.php?remover=${id}&tab=anuncios`;
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const messageEl = document.getElementById('notificationMessage');
            
            messageEl.textContent = message;
            notification.className = `notification ${type} show`;
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Modal overlay clicks
            const profileModalOverlay = document.getElementById('profileModalOverlay');
            if (profileModalOverlay) {
                profileModalOverlay.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeProfileModal();
                    }
                });
            }
            
            // Keyboard support
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('profileModalOverlay');
                if (modal && modal.classList.contains('show')) {
                    switch(e.key) {
                        case 'Escape':
                            closeProfileModal();
                            break;
                        case 'ArrowLeft':
                            changeProfileModalSlide(-1);
                            break;
                        case 'ArrowRight':
                            changeProfileModalSlide(1);
                            break;
                    }
                }
            });

            // Fechar modais ao clicar no overlay
            document.querySelectorAll('.modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });

            // Mensagens de sucesso
            <?php if (isset($_GET['success'])): ?>
                <?php if ($_GET['success'] === 'profile_updated'): ?>
                    showNotification('✅ Perfil atualizado com sucesso!', 'success');
                <?php elseif ($_GET['success'] === 'anuncio_updated'): ?>
                    showNotification('🏍️ Anúncio atualizado com sucesso!', 'success');
                <?php endif; ?>
            <?php endif; ?>
            
            // Abrir modais se há erros
            <?php if (isset($profile_errors)): ?>
                openProfileEditModal();
            <?php endif; ?>
            
            <?php if (isset($anuncio_errors) && isset($editing_anuncio_id)): ?>
                editarAnuncio(<?= $editing_anuncio_id ?>);
            <?php endif; ?>
            
            // Animações dos cards
            const cards = document.querySelectorAll('.user-announcement-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Efeitos de hover
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    if (this.classList.contains('user-announcement-card')) {
                        this.style.transform = 'translateY(-8px) scale(1.02)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    if (this.classList.contains('user-announcement-card')) {
                        this.style.transform = 'translateY(0) scale(1)';
                    }
                });
            });
        });

        console.log('🏍️ Sistema de perfil SEM UPLOAD carregado completamente');
        console.log('✅ CORREÇÃO APLICADA: Funcionalidade de upload de logo REMOVIDA');
    </script>
</body>
</html>