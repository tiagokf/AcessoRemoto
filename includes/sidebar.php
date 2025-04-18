<?php
// includes/sidebar.php
// Determinar a página atual para destacar o item do menu
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<!-- Sidebar Menu com tema dark -->
<div class="sidebar">
    <div class="logo-container">
        <div class="logo-wrapper">
            <img src="<?php echo SITE_URL; ?>/assets/images/logo-white.png" alt="Logo" class="sidebar-logo">
        </div>
        <h2><?php echo SITE_NAME; ?></h2>
    </div>
    
    <a href="<?php echo SITE_URL; ?>/index.php" class="item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
        <i class="tachometer alternate icon"></i>
        <span>Dashboard</span>
    </a>
    
    <a href="<?php echo SITE_URL; ?>/modules/conexoes/listar.php" class="item <?php echo ($current_dir == 'conexoes') ? 'active' : ''; ?>">
        <i class="network wired icon"></i>
        <span>Conexões Remotas</span>
    </a>
    
    <a href="<?php echo SITE_URL; ?>/modules/acessos/listar.php" class="item <?php echo ($current_dir == 'acessos') ? 'active' : ''; ?>">
        <i class="key icon"></i>
        <span>Acessos</span>
    </a>
    
    <a href="<?php echo SITE_URL; ?>/modules/relatorios/relatorios.php" class="item <?php echo ($current_dir == 'relatorios') ? 'active' : ''; ?>">
        <i class="chart bar icon"></i>
        <span>Relatórios</span>
    </a>
    
    <?php if (isAdmin()): ?>
    <a href="<?php echo SITE_URL; ?>/modules/usuarios/listar.php" class="item <?php echo ($current_dir == 'usuarios') ? 'active' : ''; ?>">
        <i class="users icon"></i>
        <span>Usuários</span>
    </a>
    <?php endif; ?>
    
    <?php if (isLoggedIn()): ?>
    <div class="user-info">
        <div class="user-profile">
            <i class="user circle icon"></i>
            <div class="user-details">
                <div class="user-name"><?php echo $_SESSION['user_name']; ?></div>
                <div class="user-role"><?php echo ($_SESSION['nivel_acesso'] == 'admin') ? 'Administrador' : 'Usuário'; ?></div>
            </div>
        </div>
        <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="ui mini red fluid button">
            <i class="sign-out icon"></i> Sair
        </a>
    </div>
    <?php endif; ?>
</div>

<style>
    /* Estilização melhorada para a sidebar no tema dark */
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
        display: none; /* Escondido porque temos logo no header */
    }
    
    .sidebar .logo-wrapper {
        display: flex;
        justify-content: center;
        margin-bottom: 10px;
    }
    
    .sidebar-logo {
        height: 50px;
        max-width: 100%;
    }
    
    .sidebar .logo-container h2 {
        color: white;
        font-size: 1.2rem;
        margin: 10px 0 0 0;
        font-weight: 400;
    }
    
    .sidebar .item {
        padding: 15px 20px;
        color: rgba(255, 255, 255, 0.7);
        display: flex;
        align-items: center;
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
        cursor: pointer;
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
    
    .sidebar .ui.button {
        margin-top: 10px;
        background-color: rgba(219, 40, 40, 0.8);
        transition: all 0.2s ease;
    }
    
    .sidebar .ui.button:hover {
        background-color: #db2828;
    }
    
    /* Ajustes para telas menores */
    @media only screen and (max-width: 768px) {
        .sidebar {
            width: 60px;
            padding-top: 60px;
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
    
    /* Ajuste o espaçamento do conteúdo principal */
    .main-content {
        margin-left: 250px;
        padding: 20px 25px;
        transition: margin-left 0.3s ease;
    }
</style>

<script>
    // Script para melhorar a navegação móvel
    $(document).ready(function() {
        // Inicializar dropdown
        $('.ui.dropdown').dropdown();
        
        // Adicionar efeito de hover aos itens do menu
        $('.sidebar .item').on('mouseenter', function() {
            $(this).addClass('hover');
        }).on('mouseleave', function() {
            $(this).removeClass('hover');
        });
        
        // Adicionar botão de toggle para telas pequenas
        function checkScreenSize() {
            if ($(window).width() < 768 && $("#mobile-menu-toggle").length === 0) {
                $(".ui.top.fixed.menu .right.menu").prepend(
                    '<a href="javascript:void(0);" id="mobile-menu-toggle" class="item"><i class="bars icon"></i></a>'
                );
                
                $("#mobile-menu-toggle").on("click", function() {
                    $(".sidebar").toggleClass("expanded");
                    if ($(".sidebar").hasClass("expanded")) {
                        $(".sidebar").css("width", "250px");
                        $(".sidebar .item span, .sidebar .user-info").fadeIn();
                    } else {
                        $(".sidebar").css("width", "60px");
                        $(".sidebar .item span, .sidebar .user-info").fadeOut();
                    }
                });
            } else if ($(window).width() >= 768) {
                $("#mobile-menu-toggle").remove();
                $(".sidebar").removeClass("expanded");
                $(".sidebar").css("width", "");
                $(".sidebar .item span, .sidebar .user-info").show();
            }
        }
        
        // Verificar tamanho da tela no carregamento e quando redimensionar
        checkScreenSize();
        $(window).resize(checkScreenSize);
    });
</script>