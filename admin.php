<?php 
require_once 'config/db.php';

// Verificar se é admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = 'success';

// Processar ações para utilizadores
if ($_POST && isset($_POST['acao_usuario'])) {
    $usuario_id = $_POST['usuario_id'] ?? 0;
    
    switch ($_POST['acao_usuario']) {
        case 'banir':
            $stmt = $pdo->prepare("UPDATE utilizadores SET ativo = 0 WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $mensagem = 'Utilizador banido com sucesso!';
            break;
            
        case 'ativar':
            $stmt = $pdo->prepare("UPDATE utilizadores SET ativo = 1 WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $mensagem = 'Utilizador ativado com sucesso!';
            break;
            
        case 'admin':
            $stmt = $pdo->prepare("UPDATE utilizadores SET tipo = 'admin' WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $mensagem = 'Utilizador promovido a admin!';
            break;
            
        case 'cliente':
            $stmt = $pdo->prepare("UPDATE utilizadores SET tipo = 'cliente' WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $mensagem = 'Utilizador alterado para cliente!';
            break;
            
        case 'eliminar':
            // Eliminar anúncios primeiro
            $stmt = $pdo->prepare("DELETE FROM anuncios WHERE utilizador_id = ?");
            $stmt->execute([$usuario_id]);
            
            // Eliminar pagamentos
            $stmt = $pdo->prepare("DELETE FROM pagamentos WHERE utilizador_id = ?");
            $stmt->execute([$usuario_id]);
            
            // Eliminar utilizador
            $stmt = $pdo->prepare("DELETE FROM utilizadores WHERE id = ?");
            $stmt->execute([$usuario_id]);
            
            $mensagem = 'Utilizador e todos os seus dados eliminados!';
            break;
    }
}

// Processar ações para anúncios
if ($_POST && isset($_POST['acao_anuncio'])) {
    $anuncio_id = $_POST['anuncio_id'] ?? 0;
    
    switch ($_POST['acao_anuncio']) {
        case 'ativar':
            $stmt = $pdo->prepare("UPDATE anuncios SET ativo = 1 WHERE id = ?");
            $stmt->execute([$anuncio_id]);
            $mensagem = 'Anúncio ativado com sucesso!';
            break;
            
        case 'desativar':
            $stmt = $pdo->prepare("UPDATE anuncios SET ativo = 0 WHERE id = ?");
            $stmt->execute([$anuncio_id]);
            $mensagem = 'Anúncio desativado com sucesso!';
            break;
            
        case 'destaque':
            $stmt = $pdo->prepare("UPDATE anuncios SET destaque = 1 WHERE id = ?");
            $stmt->execute([$anuncio_id]);
            $mensagem = 'Anúncio colocado em destaque!';
            break;
            
        case 'remover_destaque':
            $stmt = $pdo->prepare("UPDATE anuncios SET destaque = 0 WHERE id = ?");
            $stmt->execute([$anuncio_id]);
            $mensagem = 'Anúncio removido do destaque!';
            break;
            
            case 'eliminar':
                // Buscar imagem para eliminar
                $stmt = $pdo->prepare("SELECT imagem FROM anuncios WHERE id = ?");
                $stmt->execute([$anuncio_id]);
                $imagem = $stmt->fetchColumn();

                // Eliminar anúncio
                $stmt = $pdo->prepare("DELETE FROM anuncios WHERE id = ?");
                $stmt->execute([$anuncio_id]);

                // Eliminar imagem se existir (caminho armazenado relativo ao projeto)
                if ($imagem && file_exists($imagem)) {
                    @unlink($imagem);
                }

                $mensagem = 'Anúncio eliminado com sucesso!';
                break;
    }
}

// Buscar estatísticas gerais
$stats = [
    'total_usuarios' => $pdo->query("SELECT COUNT(*) FROM utilizadores")->fetchColumn(),
    'usuarios_ativos' => $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE ativo = 1")->fetchColumn(),
    'total_anuncios' => $pdo->query("SELECT COUNT(*) FROM anuncios")->fetchColumn(),
    'anuncios_ativos' => $pdo->query("SELECT COUNT(*) FROM anuncios WHERE ativo = 1")->fetchColumn(),
    'anuncios_pendentes' => $pdo->query("SELECT COUNT(*) FROM anuncios WHERE pago = 1 AND ativo = 0")->fetchColumn(),
    'total_receita' => $pdo->query("SELECT SUM(valor) FROM pagamentos")->fetchColumn() ?? 0,
    'novos_usuarios_semana' => $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE data_registo >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'novos_anuncios_semana' => $pdo->query("SELECT COUNT(*) FROM anuncios WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn()
];

// Buscar utilizadores recentes
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(a.id) as total_anuncios,
           COUNT(CASE WHEN a.ativo = 1 THEN 1 END) as anuncios_ativos
    FROM utilizadores u
    LEFT JOIN anuncios a ON u.id = a.utilizador_id
    GROUP BY u.id
    ORDER BY u.data_registo DESC 
    LIMIT 10
");
$usuarios_recentes = $stmt->fetchAll();

// Buscar anúncios recentes
$stmt = $pdo->query("
    SELECT a.*, u.nome as vendedor_nome, u.email as vendedor_email
    FROM anuncios a 
    JOIN utilizadores u ON a.utilizador_id = u.id 
    ORDER BY a.data_criacao DESC 
    LIMIT 10
");
$anuncios_recentes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Unificado - <?= SITE_NOME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Definir variáveis CSS diretamente */
        :root {
            --primary-yellow: #FFD700;
            --secondary-yellow: #FFC107;
            --primary-black: #1A1A1A;
            --pure-white: #FFFFFF;
            --off-white: #F8F9FA;
            --light-gray: #E0E0E0;
            --dark-gray: #666666;
            --primary-blue: #2196F3;
            --success-green: #4CAF50;
            --danger-red: #F44336;
            --warning-orange: #FF9800;
            --accent-coral: #FF5722;
            --primary-dark: #2C3E50;
            --secondary-dark: #34495E;
        }

        .admin-container { display: flex; min-height: 100vh; background: #F8F9FA; }
        .admin-sidebar { width: 280px; background: linear-gradient(180deg, #2C3E50, #34495E); color: #FFFFFF; padding: 2rem 0; position: fixed; height: 100vh; overflow-y: auto; z-index: 1000; }
        .admin-logo { padding: 0 2rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 2rem; }
        .admin-logo a { transition: all 0.3s ease; }
        .admin-logo a:hover { opacity: 0.8; transform: translateY(-1px); }
        .admin-menu { list-style: none; padding: 0; margin: 0; }
        .admin-menu li { margin-bottom: 0.5rem; }
        .admin-menu a { display: flex; align-items: center; padding: 1rem 2rem; color: #FFFFFF; text-decoration: none; transition: all 0.3s ease; border-left: 3px solid transparent; }
        .admin-menu a:hover, .admin-menu a.active { background: rgba(255,255,255,0.1); border-left-color: #FFD700; }
        .admin-menu a i { margin-right: 1rem; font-size: 1.2rem; }
        .admin-main { margin-left: 280px; padding: 2rem; width: calc(100% - 280px); }
        
        .page-header {
            background: #FFFFFF;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 2px solid #E0E0E0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: #FFFFFF;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 2px solid #E0E0E0;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666666;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .tabs-container {
            background: #FFFFFF;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 2px solid #E0E0E0;
            margin-bottom: 2rem;
        }
        
        .tabs-header {
            display: flex;
            background: #F8F9FA;
            border-bottom: 2px solid #E0E0E0;
        }
        
        .tab-button {
            flex: 1;
            padding: 1.5rem;
            background: none;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            color: #666666;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        
        .tab-button.active {
            color: #1A1A1A;
            background: #FFFFFF;
            border-bottom-color: #FFD700;
        }
        
        .tab-content {
            display: none;
            padding: 2rem;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .management-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .management-table th,
        .management-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #E0E0E0;
        }
        
        .management-table th {
            background: #F8F9FA;
            font-weight: 700;
            color: #1A1A1A;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(45deg, #FFD700, #FFC107);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #1A1A1A;
            margin-right: 0.75rem;
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active { background: #4CAF50; color: #FFFFFF; }
        .status-inactive { background: #F44336; color: #FFFFFF; }
        .status-admin { background: #FFD700; color: #1A1A1A; }
        .status-cliente { background: #2196F3; color: #FFFFFF; }
        .status-destaque { background: #FF5722; color: #FFFFFF; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-view { background: #2196F3; color: #FFFFFF; }
        .btn-edit { background: #FF9800; color: #FFFFFF; }
        .btn-ban { background: #F44336; color: #FFFFFF; }
        .btn-activate { background: #4CAF50; color: #FFFFFF; }
        .btn-promote { background: #FFD700; color: #1A1A1A; }
        .btn-feature { background: #FF5722; color: #FFFFFF; }
        
        .btn-small:hover {
            transform: translateY(-1px);
            filter: brightness(1.1);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }
        
        .moto-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #E0E0E0;
        }
        
        .price-tag {
            font-weight: 900;
            color: #FF5722;
            font-size: 1.1rem;
        }
        
        .search-box {
            margin-bottom: 1rem;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            border: 2px solid #E0E0E0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .search-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666666;
        }

        
        @media (max-width: 768px) {
            .admin-sidebar { width: 100%; height: auto; position: relative; }
            .admin-main { margin-left: 0; width: 100%; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .tabs-header { flex-direction: column; }
            .action-buttons { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: var(--primary-yellow); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">🏍️</div>
                    <div>
                        <div style="font-weight: 900; font-size: 1.1rem;">ADMIN PANEL</div>
                        <div style="font-size: 0.8rem; opacity: 0.7;">SELL u MOTORCYCLE</div>
                    </div>
                </div>
            </div>
            
            <nav>
                <ul class="admin-menu">
                    <li><a href="admin.php" class="active"><i>📊</i> Dashboard Unificado</a></li>
                    <li style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                        <a href="index.php"><i>🏠</i> Ver Site</a>
                    </li>
                    <li><a href="perfil.php"><i>👤</i> Meu Perfil</a></li>
                    <li><a href="?logout=1" style="color: #F44336;"><i>🚪</i> Sair</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Conteúdo Principal -->
        <main class="admin-main">
            <?php if (isset($_GET['logout'])) logout(); ?>
            
            <!-- Header -->
            <div class="page-header">
                <h1 style="margin: 0; font-weight: 900; color: var(--primary-black);">
                    🎛️ Dashboard Administrativo Unificado
                </h1>
                <p style="margin: 0.5rem 0 0; color: var(--dark-gray); font-size: 1.1rem;">
                    Gestão completa de utilizadores e anúncios numa única interface
                </p>
            </div>

            <?php if ($mensagem): ?>
                <div class="alert alert-<?= $tipo_mensagem ?>">
                    <?= $tipo_mensagem == 'success' ? '✅' : '⚠️' ?> <?= $mensagem ?>
                </div>
            <?php endif; ?>

            <!-- Estatísticas Gerais -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" style="color: #FFD700;"><?= $stats['total_usuarios'] ?></div>
                    <div class="stat-label">Total Utilizadores</div>
                    <div style="font-size: 0.8rem; color: #4CAF50; margin-top: 0.25rem;">
                        <?= $stats['usuarios_ativos'] ?> ativos
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number" style="color: #4CAF50;"><?= $stats['total_anuncios'] ?></div>
                    <div class="stat-label">Total Anúncios</div>
                    <div style="font-size: 0.8rem; color: #4CAF50; margin-top: 0.25rem;">
                        <?= $stats['anuncios_ativos'] ?> ativos
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number" style="color: #FF9800;"><?= $stats['anuncios_pendentes'] ?></div>
                    <div class="stat-label">Pendentes Aprovação</div>
                    <div style="font-size: 0.8rem; color: #FF9800; margin-top: 0.25rem;">
                        Requerem atenção
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number" style="color: #FF5722;">€<?= number_format($stats['total_receita'], 0) ?></div>
                    <div class="stat-label">Receita Total</div>
                    <div style="font-size: 0.8rem; color: #2196F3; margin-top: 0.25rem;">
                        Pagamentos processados
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number" style="color: #2196F3;">+<?= $stats['novos_usuarios_semana'] ?></div>
                    <div class="stat-label">Novos Utilizadores</div>
                    <div style="font-size: 0.8rem; color: #666666; margin-top: 0.25rem;">
                        Últimos 7 dias
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number" style="color: #34495E;">+<?= $stats['novos_anuncios_semana'] ?></div>
                    <div class="stat-label">Novos Anúncios</div>
                    <div style="font-size: 0.8rem; color: #666666; margin-top: 0.25rem;">
                        Últimos 7 dias
                    </div>
                </div>
            </div>

            <!-- Tabs de Gestão -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-button active" onclick="switchTab('usuarios', this)">
                        👥 Gestão de Utilizadores
                    </button>
                    <button class="tab-button" onclick="switchTab('anuncios', this)">
                        🏍️ Gestão de Anúncios
                    </button>
                </div>

                <!-- Tab Utilizadores -->
                <div id="usuarios" class="tab-content active">
                    <h3 style="margin-bottom: 1rem; color: #1A1A1A;">👥 Utilizadores Recentes</h3>
                    
                    <div class="search-box">
                        <input type="text" id="searchUsers" class="search-input" placeholder="Buscar utilizadores por nome ou email...">
                        <span class="search-icon">🔍</span>
                    </div>
                    


                    <table class="management-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>👤 Utilizador</th>
                                <th>📊 Atividade</th>
                                <th>🏷️ Tipo</th>
                                <th>📅 Registo</th>
                                <th>⚡ Status</th>
                                <th>🔧 Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios_recentes as $usuario): ?>
                            <tr class="user-row" data-name="<?= strtolower($usuario['nome']) ?>" data-email="<?= strtolower($usuario['email']) ?>">
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: #1A1A1A;">
                                                <?= htmlspecialchars($usuario['nome']) ?>
                                            </div>
                                            <div style="color: #666666; font-size: 0.9rem;">
                                                <?= htmlspecialchars($usuario['email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= $usuario['total_anuncios'] ?></strong> anúncios<br>
                                    <small style="color: #4CAF50;"><?= $usuario['anuncios_ativos'] ?> ativos</small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $usuario['tipo'] ?>">
                                        <?= ucfirst($usuario['tipo']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($usuario['data_registo'])) ?><br>
                                    <small style="color: #666666;">
                                        <?= date('H:i', strtotime($usuario['data_registo'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $usuario['ativo'] ? 'active' : 'inactive' ?>">
                                        <?= $usuario['ativo'] ? 'Ativo' : 'Banido' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($usuario['id'] != getUserId()): ?>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="acao_usuario" value="<?= $usuario['ativo'] ? 'banir' : 'ativar' ?>">
                                                <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                                <button type="submit" class="btn-small <?= $usuario['ativo'] ? 'btn-ban' : 'btn-activate' ?>"
                                                        onclick="return confirm('<?= $usuario['ativo'] ? 'Banir' : 'Ativar' ?> utilizador?')">
                                                    <?= $usuario['ativo'] ? '🚫 Banir' : '✅ Ativar' ?>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="acao_usuario" value="<?= $usuario['tipo'] == 'cliente' ? 'admin' : 'cliente' ?>">
                                                <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                                <button type="submit" class="btn-small <?= $usuario['tipo'] == 'cliente' ? 'btn-promote' : 'btn-edit' ?>"
                                                        onclick="return confirm('Alterar tipo de utilizador?')">
                                                    <?= $usuario['tipo'] == 'cliente' ? '👑 Admin' : '👤 Cliente' ?>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="acao_usuario" value="eliminar">
                                                <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                                <button type="submit" class="btn-small btn-ban"
                                                        onclick="return confirm('⚠️ ELIMINAR PERMANENTEMENTE este utilizador e TODOS os seus dados?')">
                                                    🗑️ Eliminar
                                                </button>
                                            </form>
                                            
                                        <?php else: ?>
                                            <span style="color: #666666; font-style: italic;">Você mesmo</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tab Anúncios -->
                <div id="anuncios" class="tab-content">
                    <h3 style="margin-bottom: 1rem; color: #1A1A1A;">🏍️ Anúncios Recentes</h3>
                    
                    <div class="search-box">
                        <input type="text" id="searchAds" class="search-input" placeholder="Buscar anúncios por título ou marca...">
                        <span class="search-icon">🔍</span>
                    </div>
                    


                    <table class="management-table" id="adsTable">
                        <thead>
                            <tr>
                                <th>🏍️ Motocicleta</th>
                                <th>👤 Vendedor</th>
                                <th>💰 Preço</th>
                                <th>📅 Criado</th>
                                <th>⚡ Status</th>
                                <th>🔧 Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($anuncios_recentes as $anuncio): ?>
                            <tr class="ad-row" data-title="<?= strtolower($anuncio['titulo']) ?>" data-marca="<?= strtolower($anuncio['marca']) ?>">
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <?php if ($anuncio['imagem']): ?>
                                            <img src="../<?= htmlspecialchars($anuncio['imagem']) ?>" 
                                                 alt="Moto" class="moto-image" style="margin-right: 1rem;">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: #E0E0E0; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 1rem; font-size: 1.5rem;">🏍️</div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight: 700; color: #1A1A1A;">
                                                <?= htmlspecialchars($anuncio['titulo']) ?>
                                            </div>
                                            <div style="color: #666666; font-size: 0.9rem;">
                                                <?= htmlspecialchars($anuncio['marca']) ?> • <?= htmlspecialchars($anuncio['modelo']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #1A1A1A;">
                                        <?= htmlspecialchars($anuncio['vendedor_nome']) ?>
                                    </div>
                                    <div style="color: #666666; font-size: 0.9rem;">
                                        <?= htmlspecialchars($anuncio['vendedor_email']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="price-tag">€<?= number_format($anuncio['preco'], 0) ?></div>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($anuncio['data_criacao'])) ?><br>
                                    <small style="color: #666666;">
                                        <?= date('H:i', strtotime($anuncio['data_criacao'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div>
                                        <span class="status-badge status-<?= $anuncio['ativo'] ? 'active' : 'inactive' ?>">
                                            <?= $anuncio['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                        <?php if ($anuncio['destaque'] ?? 0): ?>
                                            <br><span class="status-badge status-destaque" style="margin-top: 0.5rem;">Destaque</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="acao_anuncio" value="<?= $anuncio['ativo'] ? 'desativar' : 'ativar' ?>">
                                            <input type="hidden" name="anuncio_id" value="<?= $anuncio['id'] ?>">
                                            <button type="submit" class="btn-small <?= $anuncio['ativo'] ? 'btn-ban' : 'btn-activate' ?>">
                                                <?= $anuncio['ativo'] ? '❌ Desativar' : '✅ Ativar' ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="acao_anuncio" value="<?= ($anuncio['destaque'] ?? 0) ? 'remover_destaque' : 'destaque' ?>">
                                            <input type="hidden" name="anuncio_id" value="<?= $anuncio['id'] ?>">
                                            <button type="submit" class="btn-small <?= ($anuncio['destaque'] ?? 0) ? 'btn-edit' : 'btn-feature' ?>">
                                                <?= ($anuncio['destaque'] ?? 0) ? '⭐ Remover' : '⭐ Destacar' ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="acao_anuncio" value="eliminar">
                                            <input type="hidden" name="anuncio_id" value="<?= $anuncio['id'] ?>">
                                            <button type="submit" class="btn-small btn-ban"
                                                    onclick="return confirm('Eliminar anúncio permanentemente?')">
                                                🗑️ Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Função para trocar tabs
        function switchTab(tabName, btn) {
            // Esconder todos os conteúdos
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Desativar todos os botões
            document.querySelectorAll('.tab-button').forEach(b => {
                b.classList.remove('active');
            });

            // Mostrar tab selecionado e marcar botão como ativo
            document.getElementById(tabName).classList.add('active');
            if (btn) btn.classList.add('active');
        }

        // Funções de busca em tempo real
        document.getElementById('searchUsers').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');
            
            rows.forEach(row => {
                const name = row.dataset.name;
                const email = row.dataset.email;
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Busca em tempo real para anúncios
        document.getElementById('searchAds').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.ad-row');
            
            rows.forEach(row => {
                const title = row.dataset.title;
                const marca = row.dataset.marca;
                
                if (title.includes(searchTerm) || marca.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Animações na carga da página
        document.addEventListener('DOMContentLoaded', function() {
            // Animação das estatísticas
            document.querySelectorAll('.stat-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animação das linhas da tabela
            document.querySelectorAll('tbody tr').forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease-out';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, index * 50 + 300);
            });
        });

        // Efeitos hover nos botões
        document.querySelectorAll('.btn-small').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

    </script>
</body>
</html>