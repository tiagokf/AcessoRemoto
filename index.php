<?php
// index.php
// Arquivo principal que carrega o dashboard

// Incluir arquivos de configuração
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'auth/auth.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    // Redirecionar para a página de login
    header('Location: auth/login.php');
    exit;
}

// Incluir cabeçalho
include 'includes/header.php';

// Incluir sidebar
include 'includes/sidebar.php';

// Obter estatísticas para o dashboard
// Total de conexões
$result = dbQuery("SELECT COUNT(*) as total FROM conexoes");
$total_conexoes = dbFetchAssoc($result)['total'];

// Total de acessos
$result = dbQuery("SELECT COUNT(*) as total FROM acessos");
$total_acessos = dbFetchAssoc($result)['total'];

// Acessos hoje
$result = dbQuery("SELECT COUNT(*) as total FROM acessos WHERE DATE(data_acesso) = CURDATE()");
$acessos_hoje = dbFetchAssoc($result)['total'];

// Total de usuários
$result = dbQuery("SELECT COUNT(*) as total FROM usuarios");
$total_usuarios = dbFetchAssoc($result)['total'];

// Acessos por dia nos últimos 7 dias
$sql = "SELECT DATE(data_acesso) as data, COUNT(*) as total
        FROM acessos
        WHERE data_acesso >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(data_acesso)
        ORDER BY data";
$result = dbQuery($sql);
$acessos_por_dia = dbFetchAll($result);

// Últimas conexões acessadas
$sql = "SELECT c.id, c.cliente, c.tipo_acesso_remoto, MAX(a.data_acesso) as ultimo_acesso
        FROM conexoes c
        LEFT JOIN acessos a ON c.id = a.id_conexao
        GROUP BY c.id
        ORDER BY ultimo_acesso DESC
        LIMIT 5";
$result = dbQuery($sql);
$ultimas_conexoes = dbFetchAll($result);

// Tipos de acesso (para gráfico de pizza)
$sql = "SELECT tipo_acesso_remoto, COUNT(*) as total FROM conexoes GROUP BY tipo_acesso_remoto";
$result = dbQuery($sql);
$tipos_acesso = dbFetchAll($result);
?>

<!-- Conteúdo principal -->
<div id="dashboard-page" class="main-content">
    <h1 class="ui header">
        <i class="tachometer alternate icon"></i>
        <div class="content">
            Dashboard
            <div class="sub header">Visão geral do sistema</div>
        </div>
    </h1>
    
    <!-- Cards com estatísticas em uma linha única -->
    <div class="ui four statistics" style="display: flex; flex-wrap: nowrap; margin-bottom: 30px;">
        <div class="statistic">
            <div class="value">
                <?php echo $total_conexoes; ?>
            </div>
            <div class="label">CONEXÕES</div>
        </div>
        <div class="statistic">
            <div class="value">
                <?php echo $total_acessos; ?>
            </div>
            <div class="label">ACESSOS</div>
        </div>
        <div class="statistic">
            <div class="value">
                <?php echo $acessos_hoje; ?>
            </div>
            <div class="label">ACESSOS HOJE</div>
        </div>
        <div class="statistic">
            <div class="value">
                <?php echo $total_usuarios; ?>
            </div>
            <div class="label">USUÁRIOS</div>
        </div>
    </div>
    
    <div class="ui hidden divider"></div>
    
    <!-- Gráficos e tabelas -->
    <div class="ui grid">
        <!-- Gráfico de acessos recentes -->
        <div class="eight wide column">
            <div class="ui segment">
                <h3 class="ui header">
                    <i class="chart line icon"></i>
                    <div class="content">Acessos Recentes</div>
                </h3>
                <div class="chart-container">
                    <canvas id="acessosChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de tipos de conexão -->
        <div class="eight wide column">
            <div class="ui segment">
                <h3 class="ui header">
                    <i class="pie chart icon"></i>
                    <div class="content">Tipos de Conexão</div>
                </h3>
                <div class="chart-container">
                    <canvas id="tiposChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tabela de conexões recentes -->
        <div class="sixteen wide column">
            <div class="ui segment">
                <h3 class="ui header">
                    <i class="clock icon"></i>
                    <div class="content">Últimas Conexões Acessadas</div>
                </h3>
                <table class="ui celled table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Último Acesso</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ultimas_conexoes) > 0): ?>
                            <?php foreach ($ultimas_conexoes as $conexao): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($conexao['cliente']); ?></td>
                                    <td>
                                        <div class="ui label" style="background-color: 
                                            <?php 
                                            switch($conexao['tipo_acesso_remoto']) {
                                                case 'AnyDesk': echo '#ef443b'; break;
                                                case 'TeamViewer': echo '#1a68d6'; break;
                                                case 'RDP': echo '#2c82c9'; break;
                                                case 'VPN': echo '#27ae60'; break;
                                                case 'SSH': echo '#333333'; break;
                                                case 'RustDesk': echo '#4d4d4d'; break;
                                                default: echo '#7f8c8d'; break;
                                            }
                                            ?>;
                                            color: white;">
                                            <?php echo htmlspecialchars($conexao['tipo_acesso_remoto']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($conexao['ultimo_acesso'])): ?>
                                            <i class="clock outline icon"></i> 
                                            <?php echo date('d/m/Y H:i', strtotime($conexao['ultimo_acesso'])); ?>
                                        <?php else: ?>
                                            <div class="ui grey text">Nunca acessada</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="center aligned">
                                        <a href="<?php echo SITE_URL; ?>/modules/conexoes/ver.php?id=<?php echo $conexao['id']; ?>" class="ui mini primary button">
                                            <i class="eye icon"></i> Visualizar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="center aligned">Nenhum acesso registrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="ui right aligned basic segment">
                    <a href="<?php echo SITE_URL; ?>/modules/conexoes/listar.php" class="ui primary button">
                        <i class="list icon"></i> Ver Todas
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script para gerar os gráficos -->
    <script>
        // Ajustar configurações padrão do Chart.js
        Chart.defaults.color = '#555';
        Chart.defaults.font.family = "'Lato', 'Helvetica Neue', Arial, sans-serif";
        Chart.defaults.font.size = 13;
        
        // Cores para gráficos
        const chartColors = [
            'rgba(33, 133, 208, 0.8)',
            'rgba(33, 186, 69, 0.8)',
            'rgba(242, 113, 28, 0.8)',
            'rgba(219, 40, 40, 0.8)',
            'rgba(163, 51, 200, 0.8)',
            'rgba(0, 181, 173, 0.8)',
            'rgba(251, 189, 8, 0.8)',
            'rgba(100, 53, 201, 0.8)'
        ];
        
        // 1. Gráfico de acessos por dia
        const ctxAcessos = document.getElementById('acessosChart').getContext('2d');
        
        <?php
        // Preparar arrays para o gráfico
        $datas = [];
        $totais = [];
        
        // Preencher com zeros para os dias sem registros
        for ($i = 6; $i >= 0; $i--) {
            $data = date('Y-m-d', strtotime("-$i days"));
            $datas[] = date('d/m', strtotime($data));
            $totais[] = 0;
            
            // Verificar se há registros para esta data
            foreach ($acessos_por_dia as $acesso) {
                if ($acesso['data'] == $data) {
                    $totais[count($totais) - 1] = intval($acesso['total']);
                    break;
                }
            }
        }
        ?>
        
        const dataAcessos = {
            labels: <?php echo json_encode($datas); ?>,
            datasets: [{
                label: 'Número de Acessos',
                data: <?php echo json_encode($totais); ?>,
                backgroundColor: 'rgba(33, 133, 208, 0.2)',
                borderColor: 'rgba(33, 133, 208, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'white',
                pointBorderColor: 'rgba(33, 133, 208, 1)',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        };
        
        const acessosChart = new Chart(ctxAcessos, {
            type: 'line',
            data: dataAcessos,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        padding: 10,
                        titleColor: '#fff',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyColor: '#fff',
                        bodySpacing: 5,
                        caretPadding: 5,
                        cornerRadius: 4
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // 2. Gráfico de tipos de conexão
        const ctxTipos = document.getElementById('tiposChart').getContext('2d');
        
        <?php
        // Preparar arrays para o gráfico de tipos
        $tipos = [];
        $quantidades = [];
        
        foreach ($tipos_acesso as $tipo) {
            $tipos[] = $tipo['tipo_acesso_remoto'];
            $quantidades[] = $tipo['total'];
        }
        
        // Se não houver dados, adicionar um placeholder
        if (empty($tipos)) {
            $tipos[] = 'Sem dados';
            $quantidades[] = 1;
        }
        ?>
        
        const dataTipos = {
            labels: <?php echo json_encode($tipos); ?>,
            datasets: [{
                data: <?php echo json_encode($quantidades); ?>,
                backgroundColor: chartColors,
                borderWidth: 0,
                hoverOffset: 15
            }]
        };
        
        const tiposChart = new Chart(ctxTipos, {
            type: 'doughnut',
            data: dataTipos,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        padding: 10,
                        titleColor: '#fff',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyColor: '#fff',
                        bodySpacing: 5,
                        cornerRadius: 4,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
    </script>
</div>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>