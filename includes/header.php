<?php
// includes/header.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificar se o usuário está logado (exceto na página de login)
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page != 'login.php') {
    requireLogin();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    
    <!-- Semantic UI CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.css">
    
    <!-- Font Awesome para ícones adicionais -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/custom.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Semantic UI JS -->
    <script src="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.js"></script>
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Estilos para o tema dark no header */
        body {
            background-color: #f5f5f5;
        }
        
        .ui.top.fixed.menu {
            background-color: #1b1c1d;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
            height: 60px;
            padding: 0;
            border: none;
            z-index: 1000;
        }
        
        .ui.top.fixed.menu .item {
            color: #ffffff;
        }
        
        .ui.top.fixed.menu .right.menu .item {
            color: #ffffff;
            font-weight: 400;
            padding: 0 15px;
        }
        
        .ui.top.fixed.menu .right.menu .item i {
            margin-right: 5px;
        }
        
        .header-logo-container {
            display: flex;
            align-items: center;
            padding: 0 15px;
            height: 60px;
            background-color: rgba(0, 0, 0, 0.2);
        }
        
        .header-logo {
            height: 40px;
            max-width: 100%;
            object-fit: contain;
        }
        
        /* Ajustes para o dropdown no menu */
        .ui.dropdown .menu {
            background-color: #1b1c1d;
            border: 1px solid #333;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.5);
        }
        
        .ui.dropdown .menu .item {
            color: #ffffff;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .ui.dropdown .menu .item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .ui.dropdown .menu .divider {
            margin: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Página de conteúdo principal */
        .main-content {
            padding-top: 80px;
        }
        
        /* Ajuste para cards em uma linha */
        .ui.statistics {
            display: flex;
            flex-wrap: nowrap;
            margin-bottom: 30px;
            width: 100%;
        }
        
        .ui.statistic {
            margin: 0 10px !important;
            flex: 1;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .ui.statistic:first-child {
            margin-left: 0 !important;
        }
        
        .ui.statistic:last-child {
            margin-right: 0 !important;
        }
        
        /* Mensagem de bem-vindo no header */
        .welcome-message {
            display: flex;
            align-items: center;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .welcome-message i {
            font-size: 1.2em;
            margin-right: 8px;
            color: #2185d0;
        }
        
        /* Botão de configurações com hover */
        .settings-button {
            background-color: transparent;
            color: white;
            border: none;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .settings-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Responsividade */
        @media only screen and (max-width: 768px) {
            .ui.statistics {
                flex-wrap: wrap;
            }
            
            .ui.statistic {
                flex: 1 0 45%;
                margin-bottom: 15px !important;
            }
            
            .header-logo {
                height: 35px;
            }
        }
        
        @media only screen and (max-width: 480px) {
            .ui.statistic {
                flex: 1 0 100%;
            }
            
            .welcome-message span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php if ($current_page != 'login.php'): ?>
    <!-- Barra de navegação superior em tema dark -->
    <div class="ui top fixed menu">
        <!-- Logo no header -->
        <div class="header-logo-container">
            <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Logo" class="header-logo">
        </div>
        
        <div class="right menu">
            <div class="item welcome-message">
                <i class="user circle icon"></i>
                <span>Bem-vindo, <?php echo $_SESSION['user_name']; ?></span>
            </div>
            
            <div class="ui dropdown item">
                <div class="settings-button">
                    <i class="cog icon"></i>
                </div>
                <div class="menu">
                    <a href="<?php echo SITE_URL; ?>/modules/usuarios/editar.php?id=<?php echo $_SESSION['user_id']; ?>" class="item">
                        <i class="user edit icon"></i> Meu Perfil
                    </a>
                    <div class="divider"></div>
                    <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="item">
                        <i class="sign-out icon"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mensagens de alerta -->
    <div class="ui container" style="margin-top: 70px; margin-left: 270px;">
        <?php displayAlert(); ?>
    </div>
    <?php endif; ?>