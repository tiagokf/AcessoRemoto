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

    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

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

        /* Ajustes para o conteúdo principal */
        .main-content {
            margin-left: 250px;
            padding-top: 10px;
            padding-bottom: 40px;
            padding-left: 25px;
            padding-right: 25px;
            min-height: calc(100vh - 60px);
            transition: margin-left 0.3s ease;
        }
    </style>
</head>

<body>
    <?php if ($current_page != 'login.php'): ?>
    <!-- Barra de navegação superior -->
    <div class="ui top fixed menu">
        <!-- Logo no header -->
        <div class="header-logo-container">
            <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Logo" class="header-logo">
        </div>

        <div class="right menu">
            <div class="item">
                <?php if (isLoggedIn()): ?>
                <i class="fas fa-user-circle"></i>
                <span>Bem-vindo, <?php echo $_SESSION['user_name']; ?></span>
                <?php endif; ?>
            </div>

            <div class="ui dropdown item">
                <i class="fas fa-cog"></i>
                <div class="menu">
                    <a href="<?php echo SITE_URL; ?>/modules/usuarios/editar.php?id=<?php echo $_SESSION['user_id']; ?>"
                        class="item">
                        <i class="fas fa-user-edit"></i> Meu Perfil
                    </a>
                    <div class="divider"></div>
                    <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="item">
                        <i class="fas fa-sign-out-alt"></i> Sair
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