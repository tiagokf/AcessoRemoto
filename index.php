<?php
// index.php
// Arquivo principal que carrega o dashboard

// Incluir arquivos de configuração
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'auth/auth.php';
require_once 'includes/dashboard_functions.php'; // Novo arquivo de funções

// Verificar se o usuário está logado
if (!estaLogado()) {
    // Redirecionar para a página de login
    header('Location: auth/login.php');
    exit;
}

// Incluir cabeçalho
include 'includes/header.php';

// Incluir sidebar
include 'includes/sidebar.php';

// Obter estatísticas principais para o dashboard
$dashboardStats = getDashboardPrincipalStats();
$total_conexoes = $dashboardStats['total_conexoes'];
$total_acessos = $dashboardStats['total_acessos'];
$acessos_hoje = $dashboardStats['acessos_hoje'];
$total_usuarios = $dashboardStats['total_usuarios'];

// Obter dados para o gráfico de acessos recentes
$recentAccessChartData = getRecentAccessDataForChart();

// Obter últimas conexões acessadas
$ultimas_conexoes = getLastAccessedConnections();

// Obter dados para o gráfico de tipos de conexão
$connectionTypesChartData = getConnectionTypesDataForChart();

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

    <style>
        .dashboard-stat {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .dashboard-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .dashboard-stat .value {
            font-size: 2.5em !important;
            font-weight: bold !important;
            line-height: 1.2;
            margin-bottom: 10px;
            text-align: center;
        }

        .dashboard-stat .label {
            font-size: 1.2em !important;
            text-transform: uppercase;
            opacity: 0.8;
            text-align: center;
        }

        .dashboard-stat.blue {
            background: linear-gradient(135deg, #e9f5ff 0%, #dcf0ff 100%);
        }

        .dashboard-stat.blue .value {
            color: #2185d0;
        }

        .dashboard-stat.green {
            background: linear-gradient(135deg, #e6f7ee 0%, #d8f2e3 100%);
        }

        .dashboard-stat.green .value {
            color: #21ba45;
        }

        .dashboard-stat.orange {
            background: linear-gradient(135deg, #fff0e6 0%, #ffead8 100%);
        }

        .dashboard-stat.orange .value {
            color: #f2711c;
        }

        .dashboard-stat.purple {
            background: linear-gradient(135deg, #f0e6ff 0%, #e8d8ff 100%);
        }

        .dashboard-stat.purple .value {
            color: #6435c9;
        }

        .dashboard-stat .icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2.5em;
            opacity: 0.2;
        }
    </style>

    <!-- Cards com estatísticas em uma linha única -->
    <div class="ui four statistics" style="display: flex; flex-wrap: wrap; margin-bottom: 30px;">
        <div class="statistic dashboard-stat blue">
            <i class="server icon"></i>
            <div class="value">
                <?php echo $total_conexoes; ?>
            </div>
            <div class="label">CONEXÕES</div>
        </div>
        <div class="statistic dashboard-stat green">
            <i class="exchange icon"></i>
            <div class="value">
                <?php echo $total_acessos; ?>
            </div>
            <div class="label">ACESSOS</div>
        </div>
        <div class="statistic dashboard-stat orange">
            <i class="calendar check icon"></i>
            <div class="value">
                <?php echo $acessos_hoje; ?>
            </div>
            <div class="label">ACESSOS HOJE</div>
        </div>
        <div class="statistic dashboard-stat purple">
            <i class="users icon"></i>
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
            <div class="ui segment" style="height: 400px;">
                <h3 class="ui header">
                    <i class="chart line icon"></i>
                    <div class="content">Acessos Recentes</div>
                </h3>
                <div class="chart-container" style="height: 320px; position: relative;">
                    <canvas id="acessosChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de tipos de conexão -->
        <div class="eight wide column">
            <div class="ui segment" style="height: 400px;">
                <h3 class="ui header">
                    <i class="chart bar icon"></i>
                    <div class="content">Tipos de Conexão</div>
                </h3>
                <div class="chart-container" style="height: 320px; position: relative;">
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
                                        <button onclick="visualizarConexao(<?php echo $conexao['id']; ?>, 
                                            '<?php echo htmlspecialchars($conexao['cliente']); ?>', 
                                            '<?php echo htmlspecialchars($conexao['tipo_acesso_remoto']); ?>', 
                                            '<?php echo htmlspecialchars($conexao['id_acesso_remoto']); ?>', 
                                            '<?php echo htmlspecialchars($conexao['senha_acesso_remoto'] ?? ''); ?>', 
                                            '<?php echo htmlspecialchars(addslashes($conexao['observacoes'] ?? '')); ?>', 
                                            '<?php echo !empty($conexao['ultimo_acesso']) ? date('d/m/Y H:i', strtotime($conexao['ultimo_acesso'])) : 'Nunca acessada'; ?>')" 
                                            class="ui mini primary button">
                                            <i class="eye icon"></i> Visualizar
                                        </button>
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

    <!-- Modal para Visualizar Conexão -->
    <div class="ui small modal" id="modal-visualizar">
        <div class="header">
            <i class="eye icon"></i> Detalhes da Conexão
        </div>
        <div class="content">
            <div class="ui form">
                <div class="field">
                    <label>Cliente</label>
                    <div class="ui disabled input">
                        <input type="text" id="visualizar-cliente" readonly>
                    </div>
                </div>
                
                <div class="field">
                    <label>Tipo de Acesso</label>
                    <div class="ui disabled input">
                        <input type="text" id="visualizar-tipo" readonly>
                    </div>
                </div>
                
                <div class="field">
                    <label>ID de Acesso</label>
                    <div class="ui action input">
                        <input type="text" id="visualizar-id-acesso" readonly>
                        <button class="ui icon button" onclick="copiarParaClipboard('visualizar-id-acesso')">
                            <i class="copy icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="field" id="campo-senha-visualizar">
                    <label>Senha de Acesso</label>
                    <div class="ui action input">
                        <input type="text" id="visualizar-senha" readonly>
                        <button class="ui icon button" onclick="copiarParaClipboard('visualizar-senha')">
                            <i class="copy icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="field">
                    <label>Último Acesso</label>
                    <div class="ui disabled input">
                        <input type="text" id="visualizar-ultimo-acesso" readonly>
                    </div>
                </div>
                
                <div class="field">
                    <label>Observações</label>
                    <textarea id="visualizar-observacoes" readonly style="height: 80px; resize: none;"></textarea>
                </div>
            </div>
        </div>
        <div class="actions">
            <a href="#" class="ui blue button" id="btn-acessar-conexao">
                <i class="external link icon"></i> Acessar Conexão
            </a>
            <div class="ui approve primary button">Fechar</div>
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
        
        const dataAcessos = {
            labels: <?php echo json_encode($recentAccessChartData['labels']); ?>,
            datasets: [{
                label: 'Número de Acessos',
                data: <?php echo json_encode($recentAccessChartData['data']); ?>,
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
        
        const dataTipos = {
            labels: <?php echo json_encode($connectionTypesChartData['labels']); ?>,
            datasets: [{
                label: 'Quantidade',
                data: <?php echo json_encode($connectionTypesChartData['data']); ?>,
                backgroundColor: chartColors,
                borderColor: chartColors.map(color => color.replace('0.8', '1')),
                borderWidth: 1,
                barPercentage: 0.6,
                categoryPercentage: 0.8
            }]
        };
        
        const tiposChart = new Chart(ctxTipos, {
            type: 'bar',
            data: dataTipos,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
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
                                const label = context.dataset.label || '';
                                const value = context.raw || 0;
                                return `${label}: ${value}`;
                            }
                        }
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

        function visualizarConexao(id, cliente, tipo, idAcesso, senha, observacoes, ultimoAcesso) {
            // Preencher os campos do modal
            $('#visualizar-cliente').val(cliente);
            $('#visualizar-tipo').val(tipo);
            $('#visualizar-id-acesso').val(idAcesso);
            $('#visualizar-senha').val(senha);
            $('#visualizar-ultimo-acesso').val(ultimoAcesso);
            $('#visualizar-observacoes').val(observacoes);
            
            // Configurar o botão de acessar
            $('#btn-acessar-conexao').attr('href', '<?php echo SITE_URL; ?>/modules/conexoes/acessar.php?id=' + id);
            
            // Exibir/ocultar campo de senha
            if (senha) {
                $('#campo-senha-visualizar').show();
            } else {
                $('#campo-senha-visualizar').hide();
            }
            
            // Exibir o modal
            $('#modal-visualizar').modal('show');
        }
        
        function copiarParaClipboard(elementId) {
            const elemento = document.getElementById(elementId);
            elemento.select();
            document.execCommand('copy');
            
            $('body').toast({
                class: 'success',
                message: 'Copiado para a área de transferência',
                showProgress: 'bottom',
                displayTime: 2000
            });
        }
    </script>
</div>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
?>