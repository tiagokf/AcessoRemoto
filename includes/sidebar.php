<?php
// includes/sidebar.php
// Determinar a página atual para destacar o item do menu
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<!-- Sidebar Menu -->
<div class="sidebar">
    <div class="logo-container">
        <img src="<?php echo SITE_URL; ?>/assets/images/logo-white.png" alt="Logo" class="sidebar-logo">
    </div>
    
    <a href="<?php echo SITE_URL; ?>/index.php" class="item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
    </a>
    
    <a href="<?php echo SITE_URL; ?>/modules/conexoes/listar.php" class="item <?php echo ($current_dir == 'conexoes') ? 'active' : ''; ?>">
        <i class="fas fa-server"></i>
        <span>Conexões Remotas</span>
    </a>
    
    <a href="<?php echo SITE_URL; ?>/modules/acessos/listar.php" class="item <?php echo ($current_dir == 'acessos') ? 'active' : ''; ?>">
        <i class="fas fa-key"></i>
        <span>Acessos</span>
    </a>
    
    <a href="<?php echo SITE_URL; ?>/modules/relatorios/relatorios.php" class="item <?php echo ($current_dir == 'relatorios') ? 'active' : ''; ?>">
        <i class="fas fa-chart-bar"></i>
        <span>Relatórios</span>
    </a>
    
    <?php if (ehAdmin()): ?>
    <a href="<?php echo SITE_URL; ?>/modules/usuarios/listar.php" class="item <?php echo ($current_dir == 'usuarios') ? 'active' : ''; ?>">
        <i class="fas fa-users"></i>
        <span>Usuários</span>
    </a>
    <?php endif; ?>
    
    <?php if (estaLogado()): ?>
    <div class="user-info">
        <div class="user-profile">
            <i class="fas fa-user-circle"></i>
            <div class="user-details">
                <div class="user-name"><?php echo $_SESSION['user_name']; ?></div>
                <div class="user-role"><?php echo ($_SESSION['nivel_acesso'] == 'admin') ? 'Administrador' : 'Usuário'; ?></div>
            </div>
        </div>
        <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="ui mini red fluid button">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
    <?php endif; ?>
</div>

<style>
    /* Estilização para a sidebar */
    .sidebar {
        background-color: #1b1c1d;
        width: 250px;
        height: 100%;
        position: fixed;
        top: 0;
        left: 0;
        overflow-y: auto;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        z-index: 900;
        padding-top: 60px; /* Espaço para o header fixo */
    }
    
    .sidebar .logo-container {
        padding: 20px 15px;
        text-align: center;
    }
    
    .sidebar-logo {
        height: 50px;
        max-width: 100%;
    }
    
    .sidebar .item {
        padding: 15px 20px;
        color: rgba(255, 255, 255, 0.7);
        display: flex;
        align-items: center;
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }
    
    .sidebar .item i {
        margin-right: 10px;
        font-size: 1.2em;
        width: 20px;
        text-align: center;
    }
    
    .sidebar .item:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: white;
        border-left-color: rgba(33, 133, 208, 0.5);
    }
    
    .sidebar .item.active {
        background-color: rgba(33, 133, 208, 0.15);
        color: white;
        border-left-color: #2185d0;
        font-weight: 500;
    }
    
    .sidebar .user-info {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        position: absolute;
        bottom: 0;
        width: 100%;
        padding: 15px;
        background-color: rgba(0, 0, 0, 0.2);
    }
    
    .sidebar .user-profile {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .sidebar .user-profile i {
        font-size: 2.5em;
        margin-right: 10px;
        color: #2185d0;
    }
    
    .sidebar .user-details {
        flex: 1;
    }
    
    .sidebar .user-name {
        color: white;
        font-weight: 500;
        margin-bottom: 3px;
    }
    
    .sidebar .user-role {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.85em;
    }
    
    /* Ajustes para telas menores */
    @media only screen and (max-width: 768px) {
        .sidebar {
            width: 60px;
        }
        
        .sidebar .item span {
            display: none;
        }
        
        .sidebar .item i {
            margin-right: 0;
            font-size: 1.4em;
        }
        
        .sidebar .user-info {
            display: none;
        }
        
        .main-content {
            margin-left: 60px !important;
        }
    }
</style>