<?php 
require_once 'config/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$erro = '';
$sucesso = '';

if ($_POST) {
    $titulo = trim($_POST['titulo']);
    $marca = $_POST['marca'];
    $modelo = trim($_POST['modelo']);
    $ano = $_POST['ano'];
    $preco = $_POST['preco'];
    $telefone = trim($_POST['telefone']);
    $descricao = trim($_POST['descricao']);
    $metodo_pagamento = $_POST['metodo_pagamento'];
    
    // Validações
    if (empty($titulo) || empty($marca) || empty($modelo) || empty($preco) || empty($telefone)) {
        $erro = 'Todos os campos obrigatórios devem ser preenchidos.';
    } else {
        // Upload de múltiplas imagens para os 5 campos
        $uploadedImages = [];
        $uploadDir = 'uploads/motos/';
        
        // Criar diretório se não existir
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Processar até 5 imagens
        if (!empty($_FILES['imagens']['name'][0])) {
            $maxImages = 5;
            $imageCount = min(count($_FILES['imagens']['name']), $maxImages);
            
            for ($i = 0; $i < $imageCount; $i++) {
                if ($_FILES['imagens']['error'][$i] === UPLOAD_ERR_OK) {
                    $extensoes = ['jpg', 'jpeg', 'png', 'gif'];
                    $extensao = strtolower(pathinfo($_FILES['imagens']['name'][$i], PATHINFO_EXTENSION));
                    
                    if (in_array($extensao, $extensoes) && $_FILES['imagens']['size'][$i] <= 5000000) {
                        $nomeArquivo = uniqid() . '_' . time() . '_' . $i . '.' . $extensao;
                        $caminho = $uploadDir . $nomeArquivo;
                        
                        if (move_uploaded_file($_FILES['imagens']['tmp_name'][$i], $caminho)) {
                            $uploadedImages[] = $caminho;
                        }
                    }
                }
            }
        }
        
        try {
            // Preparar os campos de imagem (até 5)
            $imagem1 = isset($uploadedImages[0]) ? $uploadedImages[0] : null;
            $imagem2 = isset($uploadedImages[1]) ? $uploadedImages[1] : null;
            $imagem3 = isset($uploadedImages[2]) ? $uploadedImages[2] : null;
            $imagem4 = isset($uploadedImages[3]) ? $uploadedImages[3] : null;
            $imagem5 = isset($uploadedImages[4]) ? $uploadedImages[4] : null;
            
            // Inserir anúncio com todas as imagens nos respectivos campos
            $stmt = $pdo->prepare("
                INSERT INTO anuncios (
                    utilizador_id, titulo, marca, modelo, ano, preco, telefone, descricao, 
                    imagem, imagem2, imagem3, imagem4, imagem5, pago, ativo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)
            ");
            
            $stmt->execute([
                getUserId(), $titulo, $marca, $modelo, $ano, $preco, $telefone, $descricao,
                $imagem1, $imagem2, $imagem3, $imagem4, $imagem5
            ]);
            
            $anuncio_id = $pdo->lastInsertId();
            
            // Processar "pagamento" fictício
            $stmt = $pdo->prepare("INSERT INTO pagamentos (anuncio_id, utilizador_id, valor, estado) VALUES (?, ?, ?, 'pago')");
            $stmt->execute([$anuncio_id, getUserId(), PRECO_ANUNCIO]);
            
            header('Location: index.php?sucesso=anuncio_criado');
            exit;
            
        } catch (PDOException $e) {
            $erro = 'Erro ao criar anúncio. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Anúncio - <?= SITE_NOME ?></title>
    <!-- FONTE INTER IGUAL AO PERFIL.PHP -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ===============================================
           VARIÁVEIS CSS CONSISTENTES COM PERFIL.PHP
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* ===============================================
           TIPOGRAFIA CONSISTENTE COM PERFIL.PHP
           =============================================== */
        
        body {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            line-height: 1.6;
            color: var(--primary-black);
            background: linear-gradient(135deg, var(--primary-black) 0%, var(--secondary-black) 100%);
            min-height: 100vh;
            padding-top: 90px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        /* Garantir que todos os elementos herdem a fonte correta */
        * {
            font-family: inherit;
        }

        /* Botões, inputs e elementos de formulário */
        button, input, select, textarea, .btn {
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
        label, .form-label {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            font-weight: 600;
        }

        /* Títulos principais */
        .form-title, .section-title {
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            font-weight: 900;
        }

        /* ===============================================
           HEADER CONSISTENTE COM PERFIL.PHP - SEM UPLOAD
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
           LOGO SECTION IGUAL AO PERFIL.PHP - SEM UPLOAD
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

        /* Logo texto SEM ícone de moto - IGUAL AO PERFIL.PHP */
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

        /* Container principal */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .form-container {
            background: var(--pure-white);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            border: 2px solid var(--primary-yellow);
            margin: 1.5rem auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(45deg, var(--primary-yellow), var(--secondary-yellow));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            color: var(--dark-gray);
            font-size: 1rem;
            font-weight: 500;
        }

        /* Seções do formulário */
        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--off-white);
            border-radius: var(--border-radius);
            border: 1px solid var(--light-gray);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 2px solid var(--primary-yellow);
            padding-bottom: 0.5rem;
        }

        /* Campos do formulário */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row.triple {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--primary-black);
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: var(--transition);
            background: var(--pure-white);
            font-weight: 500;
            font-family: inherit;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-yellow);
            box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.2);
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* Upload de imagens melhorado */
        .image-upload-section {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 193, 7, 0.1));
            border: 2px dashed var(--primary-yellow);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }

        .image-upload-section.dragover {
            border-color: var(--secondary-yellow);
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 193, 7, 0.2));
            transform: scale(1.01);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--primary-yellow);
            margin-bottom: 0.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .upload-text {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 0.25rem;
        }

        .upload-subtitle {
            color: var(--dark-gray);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .file-input {
            display: none;
        }

        .upload-btn {
            background: linear-gradient(45deg, var(--primary-yellow), var(--secondary-yellow));
            color: var(--primary-black);
            border: 2px solid var(--primary-black);
            padding: 0.75rem 1.5rem;
            border-radius: 20px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .upload-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        .image-counter {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: var(--primary-yellow);
            color: var(--primary-black);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-weight: 700;
            border: 1px solid var(--primary-black);
            font-size: 0.8rem;
        }

        /* Preview das imagens melhorado */
        .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
            opacity: 0;
            transform: translateY(15px);
            transition: all 0.4s ease;
        }

        .image-preview-grid.show {
            opacity: 1;
            transform: translateY(0);
        }

        .image-preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--light-gray);
            transition: var(--transition);
            animation: imageSlideIn 0.4s ease-out;
        }

        @keyframes imageSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .image-preview-item:hover {
            border-color: var(--primary-yellow);
            transform: translateY(-5px);
        }

        .preview-image {
            width: 100%;
            height: 110px;
            object-fit: cover;
            display: block;
        }

        .remove-image {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: var(--danger-red);
            color: var(--pure-white);
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.8rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-image:hover {
            background: #d32f2f;
            transform: scale(1.1);
        }

        .image-order {
            position: absolute;
            bottom: 0.25rem;
            left: 0.25rem;
            background: rgba(0, 0, 0, 0.8);
            color: var(--pure-white);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .image-field-indicator {
            position: absolute;
            bottom: 0.25rem;
            right: 0.25rem;
            background: var(--primary-yellow);
            color: var(--primary-black);
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            font-size: 0.65rem;
            font-weight: 700;
            border: 1px solid var(--primary-black);
        }

        /* Seção de pagamento */
        .payment-section {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.08), rgba(78, 205, 196, 0.08));
            border: 1px solid rgba(255, 107, 107, 0.2);
            margin-bottom: 0;
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .payment-option {
            background: white;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            padding: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .payment-option:hover {
            border-color: var(--success-green);
            background: rgba(78, 205, 196, 0.05);
        }

        .payment-option.selected {
            border-color: var(--success-green);
            background: rgba(78, 205, 196, 0.1);
        }

        .payment-option input[type="radio"] {
            margin: 0;
        }

        .payment-icon {
            background: linear-gradient(45deg, var(--warning-orange), var(--success-green));
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .payment-details strong {
            display: block;
            margin-bottom: 0.2rem;
            font-size: 0.9rem;
        }

        .payment-details small {
            font-size: 0.8rem;
            color: #666;
        }

        /* Botão de submissão */
        .submit-btn {
            background: linear-gradient(45deg, var(--success-green), #66BB6A);
            color: var(--pure-white);
            border: 2px solid var(--primary-black);
            padding: 1rem 1.5rem;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Alertas */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            border: 1px solid;
            font-size: 0.9rem;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-green);
            border-color: var(--success-green);
        }

        .alert-warning {
            background: rgba(255, 152, 0, 0.1);
            color: var(--warning-orange);
            border-color: var(--warning-orange);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger-red);
            border-color: var(--danger-red);
        }

        /* Responsive */
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

            .container {
                padding: 0 10px;
            }

            .form-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .form-section {
                padding: 1rem;
                margin-bottom: 1.5rem;
            }

            .form-row, .form-row.triple {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .payment-options {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .image-preview-grid {
                grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
                gap: 0.5rem;
            }

            .preview-image {
                height: 90px;
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
                color: #FFD700 !important;
                background: none !important;
                -webkit-text-fill-color: #FFD700 !important;
                -webkit-background-clip: initial !important;
                background-clip: initial !important;
                filter: brightness(1.1) saturate(1.1);
            }
        }
    </style>
</head>
<body>
    <!-- Header IGUAL AO PERFIL.PHP - SEM UPLOAD -->
    <header class="header">
        <div class="header-content">
            <!-- Seção da Logo IGUAL AO PERFIL.PHP - SEM UPLOAD -->
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
                            <div class="logo-sub">Marketplace</div>
                        </div>
                    </div>
                </a>
            </div>
            
            <nav style="margin-left: auto;">
                <ul class="nav-menu">
                    <li><a href="index.php">← Voltar ao Início</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2 class="form-title">🏍️ Publicar Nova Mota</h2>
                <p class="form-subtitle">Preencha os dados e publique o seu anúncio rapidamente</p>
            </div>
            
            <?php if ($erro): ?>
                <div class="alert alert-error">
                    ⚠️ <?= $erro ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="anuncioForm">
                <!-- Seção 1: Informações Básicas -->
                <div class="form-section">
                    <h3 class="section-title">📝 Informações da Mota</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Título do Anúncio*</label>
                        <input type="text" name="titulo" class="form-input" 
                               placeholder="Ex: Honda CBR 600RR 2020 - Estado impecável, poucos quilómetros" 
                               required maxlength="200" id="titulo" value="<?= $_POST['titulo'] ?? '' ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Marca*</label>
                            <select name="marca" class="form-select" required id="marca">
                                <option value="">Seleccione a marca</option>
                                <option value="Honda" <?= ($_POST['marca'] ?? '') == 'Honda' ? 'selected' : '' ?>>Honda</option>
                                <option value="Yamaha" <?= ($_POST['marca'] ?? '') == 'Yamaha' ? 'selected' : '' ?>>Yamaha</option>
                                <option value="Kawasaki" <?= ($_POST['marca'] ?? '') == 'Kawasaki' ? 'selected' : '' ?>>Kawasaki</option>
                                <option value="Suzuki" <?= ($_POST['marca'] ?? '') == 'Suzuki' ? 'selected' : '' ?>>Suzuki</option>
                                <option value="BMW" <?= ($_POST['marca'] ?? '') == 'BMW' ? 'selected' : '' ?>>BMW</option>
                                <option value="Ducati" <?= ($_POST['marca'] ?? '') == 'Ducati' ? 'selected' : '' ?>>Ducati</option>
                                <option value="KTM" <?= ($_POST['marca'] ?? '') == 'KTM' ? 'selected' : '' ?>>KTM</option>
                                <option value="Triumph" <?= ($_POST['marca'] ?? '') == 'Triumph' ? 'selected' : '' ?>>Triumph</option>
                                <option value="Harley-Davidson" <?= ($_POST['marca'] ?? '') == 'Harley-Davidson' ? 'selected' : '' ?>>Harley-Davidson</option>
                                <option value="Aprilia" <?= ($_POST['marca'] ?? '') == 'Aprilia' ? 'selected' : '' ?>>Aprilia</option>
                                <option value="Benelli" <?= ($_POST['marca'] ?? '') == 'Benelli' ? 'selected' : '' ?>>Benelli</option>
                                <option value="Outro" <?= ($_POST['marca'] ?? '') == 'Outro' ? 'selected' : '' ?>>Outro</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Modelo*</label>
                            <input type="text" name="modelo" class="form-input" 
                                   placeholder="Ex: CBR 600RR, MT-07, Ninja 650" 
                                   required id="modelo" value="<?= $_POST['modelo'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="form-row triple">
                        <div class="form-group">
                            <label class="form-label">Ano*</label>
                            <input type="number" name="ano" class="form-input" 
                                   min="1950" max="2025" required 
                                   placeholder="2020" id="ano" value="<?= $_POST['ano'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Preço (€)*</label>
                            <input type="number" name="preco" class="form-input" 
                                   step="0.01" min="0" required 
                                   placeholder="8500.00" id="preco" value="<?= $_POST['preco'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Quilómetros</label>
                            <input type="number" name="quilometragem" class="form-input" 
                                   min="0" placeholder="25000" id="quilometragem" value="<?= $_POST['quilometragem'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Telemovel de Contacto*</label>
                        <input type="tel" name="telefone" class="form-input" 
                               placeholder="912345678" required 
                               pattern="[0-9]{9}" id="telefone" value="<?= $_POST['telefone'] ?? '' ?>">
                        <small style="color: #666; font-size: 0.85rem;">Formato: 9 dígitos (ex: 912345678)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Descrição (Opcional)</label>
                        <textarea name="descricao" class="form-textarea" 
                                  placeholder="Descreva o estado, histórico, modificações..." 
                                  maxlength="500" id="descricao"><?= $_POST['descricao'] ?? '' ?></textarea>
                        <small style="color: #666; font-size: 0.85rem;">Máximo 500 caracteres</small>
                    </div>
                </div>

                <!-- Seção 2: Upload de Fotos (até 5) -->
                <div class="form-section">
                    <h3 class="section-title">📸 Fotos da Mota (Máximo 5)</h3>
                    
                    <div class="alert alert-warning">
                        💡 <strong>Dica:</strong> Adicione até 5 fotos de qualidade para aumentar as vendas! 
                        A primeira imagem será a principal.
                    </div>
                    
                    <div class="image-upload-section" id="uploadSection">
                        <div class="image-counter" id="imageCounter">0/5 fotos</div>
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">Adicione até 5 fotos da sua moto</div>
                        <div class="upload-subtitle">Arraste e largue as imagens aqui ou clique para seleccionar</div>
                        <div class="upload-subtitle">JPG, PNG ou GIF até 5MB cada - Primeira imagem será a principal</div>
                        <button type="button" class="upload-btn" onclick="document.getElementById('imageInput').click()">
                            📁 Escolher Fotos
                        </button>
                        <input type="file" id="imageInput" name="imagens[]" class="file-input" multiple accept="image/*">
                    </div>
                    
                    <div class="image-preview-grid" id="previewGrid"></div>
                </div>

                <!-- Seção 3: Pagamento -->
                <div class="form-section payment-section">
                    <h3 class="section-title">💳 Finalizar Publicação</h3>
                    
                    <div class="alert alert-success" style="margin-bottom: 2rem;">
                        💰 <strong>Custo de publicação:</strong> Apenas €4,99 - O seu anúncio ficará activo por 60 dias!
                    </div>
                    
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="metodo_pagamento" value="cartao" required>
                            <div class="payment-icon">💳</div>
                            <div class="payment-details">
                                <strong>Cartão de Crédito</strong>
                                <small>Visa, Mastercard</small>
                            </div>
                        </label>
                        
                        <label class="payment-option">
                            <input type="radio" name="metodo_pagamento" value="mbway">
                            <div class="payment-icon">📱</div>
                            <div class="payment-details">
                                <strong>MB WAY</strong>
                                <small>Pagamento móvel</small>
                            </div>
                        </label>
                        
                        <label class="payment-option">
                            <input type="radio" name="metodo_pagamento" value="paypal">
                            <div class="payment-icon">🅿️</div>
                            <div class="payment-details">
                                <strong>PayPal</strong>
                                <small>Conta PayPal</small>
                            </div>
                        </label>
                    </div>
                    
                    <div class="alert alert-error">
                        ⚠️ <strong>Projeto académico</strong> - Pagamento fictício, sem cobrança real.
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        🚀 Publicar Anúncio por €4,99
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Estado do formulário
        let selectedImages = [];
        const maxImages = 5;

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            setupDragAndDrop();
            setupFormValidation();
        });

        // Configurar event listeners
        function setupEventListeners() {
            // Upload de imagens
            document.getElementById('imageInput').addEventListener('change', function(e) {
                handleFileSelect(e.target.files);
            });
            
            // Formatação do telefone
            document.getElementById('telefone').addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').slice(0, 9);
            });
            
            // Métodos de pagamento
            setupPaymentMethods();
            
            // Submissão do formulário
            document.getElementById('anuncioForm').addEventListener('submit', handleFormSubmit);
        }

        // Upload de imagens
        function handleFileSelect(files) {
            const initialCount = selectedImages.length;
            let addedCount = 0;
            
            for (let i = 0; i < files.length && selectedImages.length < maxImages; i++) {
                const file = files[i];
                
                if (validateImage(file)) {
                    selectedImages.push(file);
                    createImagePreview(file, selectedImages.length - 1);
                    addedCount++;
                }
            }
            
            // Avisar se tentou adicionar mais que o limite
            if (files.length > addedCount && initialCount + files.length > maxImages) {
                const rejected = files.length - addedCount;
                showAlert(`Máximo de 5 fotos permitido. ${rejected} foto(s) não foram adicionadas.`, 'warning');
            }
            
            updateImageCounter();
            updateFileInput();
        }

        function validateImage(file) {
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!allowedTypes.includes(file.type)) {
                showAlert(`Formato não suportado: ${file.name}. Use apenas JPG, PNG ou GIF`, 'error');
                return false;
            }
            
            if (file.size > maxSize) {
                showAlert(`Ficheiro muito grande: ${file.name}. Tamanho máximo: 5MB`, 'error');
                return false;
            }
            
            return true;
        }

        function createImagePreview(file, index) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewGrid = document.getElementById('previewGrid');
                
                const previewItem = document.createElement('div');
                previewItem.className = 'image-preview-item';
                
                // Determinar qual campo da BD esta imagem vai ocupar
                const dbField = index === 0 ? 'Principal' : `Imagem ${index + 1}`;
                
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}" class="preview-image">
                    <button type="button" class="remove-image" onclick="removeImage(${index})">&times;</button>
                    <div class="image-order">${index + 1}</div>
                    <div class="image-field-indicator">${dbField}</div>
                `;
                
                previewGrid.appendChild(previewItem);
                previewGrid.classList.add('show');
            };
            reader.readAsDataURL(file);
        }

        function removeImage(index) {
            selectedImages.splice(index, 1);
            updatePreviewGrid();
            updateImageCounter();
            updateFileInput();
        }

        function updatePreviewGrid() {
            const previewGrid = document.getElementById('previewGrid');
            previewGrid.innerHTML = '';
            
            selectedImages.forEach((file, index) => {
                createImagePreview(file, index);
            });
            
            if (selectedImages.length === 0) {
                previewGrid.classList.remove('show');
            }
        }

        function updateImageCounter() {
            const counter = document.getElementById('imageCounter');
            counter.textContent = `${selectedImages.length}/5 fotos`;
            
            if (selectedImages.length >= maxImages) {
                counter.style.background = 'var(--danger-red)';
                counter.style.color = 'var(--pure-white)';
            } else {
                counter.style.background = 'var(--primary-yellow)';
                counter.style.color = 'var(--primary-black)';
            }
        }

        function updateFileInput() {
            const fileInput = document.getElementById('imageInput');
            const dt = new DataTransfer();
            
            selectedImages.forEach(file => {
                dt.items.add(file);
            });
            
            fileInput.files = dt.files;
        }

        // Drag and Drop
        function setupDragAndDrop() {
            const uploadSection = document.getElementById('uploadSection');

            uploadSection.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadSection.classList.add('dragover');
            });

            uploadSection.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadSection.classList.remove('dragover');
            });

            uploadSection.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadSection.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                handleFileSelect(files);
            });
        }

        // Métodos de pagamento
        function setupPaymentMethods() {
            document.querySelectorAll('.payment-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.payment-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                });
            });
        }

        // Validação do formulário
        function setupFormValidation() {
            const inputs = document.querySelectorAll('input[required], select[required]');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.checkValidity()) {
                        this.style.borderColor = 'var(--success-green)';
                    } else {
                        this.style.borderColor = 'var(--danger-red)';
                    }
                });
                
                input.addEventListener('input', function() {
                    if (this.style.borderColor === 'rgb(244, 67, 54)') {
                        this.style.borderColor = 'var(--light-gray)';
                    }
                });
            });
        }

        // Submissão do formulário
        function handleFormSubmit(e) {
            e.preventDefault();
            
            // Validar campos obrigatórios
            const requiredFields = ['titulo', 'marca', 'modelo', 'ano', 'preco', 'telefone'];
            for (let field of requiredFields) {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    showAlert(`Por favor, preencha o campo "${element.previousElementSibling.textContent}"`, 'error');
                    element.focus();
                    return;
                }
            }
            
            // Validar método de pagamento
            const paymentMethod = document.querySelector('input[name="metodo_pagamento"]:checked');
            if (!paymentMethod) {
                showAlert('Por favor, seleccione um método de pagamento!', 'error');
                return;
            }
            
            // Validar se há pelo menos uma imagem
            if (selectedImages.length === 0) {
                showAlert('Por favor, adicione pelo menos uma foto da mota!', 'error');
                return;
            }
            
            // Log das imagens que serão enviadas
            console.log('📷 Imagens a serem enviadas:', selectedImages.length);
            selectedImages.forEach((file, index) => {
                console.log(`Imagem ${index + 1}:`, file.name, `(Campo: ${index === 0 ? 'imagem' : 'imagem' + (index + 1)})`);
            });
            
            // Submeter o formulário real
            document.getElementById('anuncioForm').submit();
        }

        // Sistema de alertas
        function showAlert(message, type = 'success') {
            // Remover alertas existentes
            const existingAlerts = document.querySelectorAll('.alert-dynamic');
            existingAlerts.forEach(alert => alert.remove());
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dynamic`;
            alert.style.position = 'fixed';
            alert.style.top = '100px';
            alert.style.right = '20px';
            alert.style.zIndex = '2000';
            alert.style.maxWidth = '400px';
            alert.style.animation = 'slideInRight 0.5s ease-out';
            
            const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : '💡';
            alert.innerHTML = `
                ${icon} ${message}
                <button onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; margin-left: auto;">&times;</button>
            `;
            
            document.body.appendChild(alert);
            
            // Auto-remover após 5 segundos
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 5000);
        }

        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.form-section');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    section.style.transition = 'all 0.6s ease-out';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });

        console.log('🏍️ Sistema de anúncio SEM UPLOAD DE LOGO carregado completamente');
        console.log('✅ CONSISTÊNCIA APLICADA: Logo igual ao perfil.php, sem funcionalidade de upload');
    </script>
</body>
</html>