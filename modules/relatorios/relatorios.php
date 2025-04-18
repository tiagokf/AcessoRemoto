<?php
// modules/relatorios/relatorios.php
// Página de relatórios com métricas de acessos

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário está logado
requireLogin();

// Processar filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$cliente = isset($_GET['cliente']) ? dbEscape($_GET['cliente']) : '';
$tipo_acesso = isset($_GET['tipo_acesso']) ? dbEscape($_GET['tipo_acesso']) : '';

// Construir cláusula WHERE para filtros
$whereClause = "WHERE a.data_acesso BETWEEN '$data_inicio 00:00:00' AND '$data_fim 23:59:59'";

if (!empty($cliente)) {
    $whereClause .= " AND c.cliente LIKE '%$cliente%'";
}

if (!empty($tipo_acesso)) {
    $whereClause .= " AND c.tipo_acesso_remoto = '$tipo_acesso'";
}

// Consultas para relatórios
// 1. Total de acessos no período
$sql = "SELECT COUNT(*) as total FROM acessos a JOIN conexoes c ON a.id_conexao = c.id $whereClause";
$result = dbQuery($sql);
$total_acessos = dbFetchAssoc($result)['total'];

// 2. Acessos por tipo
$sql = "SELECT c.tipo_acesso_remoto, COUNT(*) as total 
        FROM acessos a 
        JOIN conexoes c ON a.id_conexao = c.id 
        $whereClause 
        GROUP BY c.tipo_acesso_remoto 
        ORDER BY total DESC";
$result = dbQuery($sql);
$acessos_por_tipo = dbFetchAll($result);

// 3. Acessos por cliente
$sql = "SELECT c.cliente, COUNT(*) as total 
        FROM acessos a 
        JOIN conexoes c ON a.id_conexao = c.id 
        $whereClause 
        GROUP BY c.cliente 
        ORDER BY total DESC 
        LIMIT 10";
$result = dbQuery($sql);
$acessos_por_cliente = dbFetchAll($result);

// 4. Acessos por usuário
$sql = "SELECT u.nome, COUNT(*) as total 
        FROM acessos a 
        JOIN conexoes c ON a.id_conexao = c.id 
        JOIN usuarios u ON a.id_usuario = u.id 
        $whereClause 
        GROUP BY u.nome 
        ORDER BY total DESC";
$result = dbQuery($sql);
$acessos_por_usuario = dbFetchAll($result);

// 5. Acessos por dia
$sql = "SELECT DATE(a.data_acesso) as data, COUNT(*) as total 
        FROM acessos a 
        JOIN conexoes c ON a.id_conexao = c.id 
        $whereClause 
        GROUP BY DATE(a.data_acesso) 
        ORDER BY data ASC";
$result = dbQuery($sql);
$acessos_por_dia = dbFetchAll($result);

// 6. Lista dos últimos acessos
$sql = "SELECT a.id, a.data_acesso, c.cliente, c.tipo_acesso_remoto, u.nome as usuario, a.ip_acesso 
        FROM acessos a 
        JOIN conexoes c ON a.id_conexao = c.id 
        JOIN usuarios u ON a.id_usuario = u.id 
        $whereClause 
        ORDER BY a.data_acesso DESC 
        LIMIT 20";
$result = dbQuery($sql);
$ultimos_acessos = dbFetchAll($result);

// Obter lista de tipos de acesso para filtro
$sql = "SELECT DISTINCT tipo_acesso_remoto FROM conexoes ORDER BY tipo_acesso_remoto";
$result = dbQuery($sql);
$tipos_acesso = dbFetchAll($result);

// Incluir cabeçalho
include '../../includes/header.php';

// Incluir sidebar
include '../../includes/sidebar.php';
?>

<!-- Conteúdo principal -->
<div class="main-content">
    <h1 class="ui header">
        <i class="chart bar icon"></i>
        <div class="content">
            Relatórios
            <div class="sub header">Métricas e estatísticas de acessos</div>
        </div>
    </h1>
    
    <div class="ui divider"></div>
    
    <!-- Filtros -->
    <div class="ui segment">
        <form class="ui form" method="GET" action="">
            <h4 class="ui dividing header">Filtros</h4>
            
            <div class="three fields">
                <div class="field">
                    <label>Data Inicial</label>
                    <input type="date" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                <div class="field">
                    <label>Data Final</label>
                    <input type="date" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                <div class="field">
                    <label>Cliente</label>
                    <input type="text" name="cliente" placeholder="Nome do cliente" value="<?php echo htmlspecialchars($cliente); ?>">
                </div>
            </div>
            
            <div class="two fields">
                <div class="field">
                    <label>Tipo de Acesso</label>
                    <select class="ui dropdown" name="tipo_acesso">
                        <option value="">Todos</option>
                        <?php foreach ($tipos_acesso as $tipo): ?>
                            <option value="<?php echo $tipo['tipo_acesso_remoto']; ?>" <?php echo ($tipo['tipo_acesso_remoto'] == $tipo_acesso) ? 'selected' : ''; ?>>
                                <?php echo $tipo['tipo_acesso_remoto']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="ui primary button" type="submit">
                        <i class="filter icon"></i> Filtrar
                    </button>
                    <a href="relatorios.php" class="ui button">
                        <i class="undo icon"></i> Limpar Filtros
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Resumo -->
    <div class="ui segment">
        <h3 class="ui header">Resumo do Período</h3>
        <p>
            <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($data_inicio)); ?> até <?php echo date('d/m/Y', strtotime($data_fim)); ?><br>
            <strong>Total de Acessos:</strong> <?php echo $total_acessos; ?>
        </p>
    </div>
    
    <!-- Gráficos e Estatísticas -->
    <div class="ui two column grid">
        <!-- Acessos por Dia -->
        <div class="column">
            <div class="ui segment">
                <h3 class="ui header">Acessos por Dia</h3>
                <canvas id="acessosDiaChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Acessos por Tipo -->
        <div class="column">
            <div class="ui segment">
                <h3 class="ui header">Acessos por Tipo</h3>
                <canvas id="acessosTipoChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Acessos por Cliente -->
        <div class="column">
            <div class="ui segment">
                <h3 class="ui header">Top 10 Clientes</h3>
                <canvas id="acessosClienteChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Acessos por Usuário -->
        <div class="column">
            <div class="ui segment">
                <h3 class="ui header">Acessos por Usuário</h3>
                <canvas id="acessosUsuarioChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Lista de Últimos Acessos -->
    <div class="ui segment">
        <h3 class="ui header">Últimos Acessos</h3>
        <table class="ui celled table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Usuário</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($ultimos_acessos) > 0): ?>
                    <?php foreach ($ultimos_acessos as $acesso): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($acesso['data_acesso'])); ?></td>
                            <td><?php echo htmlspecialchars($acesso['cliente']); ?></td>
                            <td><?php echo htmlspecialchars($acesso['tipo_acesso_remoto']); ?></td>
                            <td><?php echo htmlspecialchars($acesso['usuario']); ?></td>
                            <td><?php echo htmlspecialchars($acesso['ip_acesso']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="center aligned">Nenhum acesso encontrado no período selecionado</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Script para gerar os gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        $('.ui.dropdown').dropdown();
        
        // Dados para o gráfico de acessos por dia
        const ctxDia = document.getElementById('acessosDiaChart').getContext('2d');
        const acessosDiaChart = new Chart(ctxDia, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    foreach ($acessos_por_dia as $acesso) {
                        echo "'" . date('d/m', strtotime($acesso['data'])) . "',";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Acessos',
                    data: [
                        <?php 
                        foreach ($acessos_por_dia as $acesso) {
                            echo $acesso['total'] . ",";
                        }
                        ?>
                    ],
                    borderColor: 'rgba(33, 133, 208, 1)',
                    backgroundColor: 'rgba(33, 133, 208, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Dados para o gráfico de acessos por tipo
        const ctxTipo = document.getElementById('acessosTipoChart').getContext('2d');
        const acessosTipoChart = new Chart(ctxTipo, {
            type: 'pie',
            data: {
                labels: [
                    <?php 
                    foreach ($acessos_por_tipo as $acesso) {
                        echo "'" . $acesso['tipo_acesso_remoto'] . "',";
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        foreach ($acessos_por_tipo as $acesso) {
                            echo $acesso['total'] . ",";
                        }
                        ?>
                    ],
                    backgroundColor: [
                        'rgba(33, 133, 208, 0.7)',
                        'rgba(0, 181, 173, 0.7)',
                        'rgba(242, 113, 28, 0.7)',
                        'rgba(219, 40, 40, 0.7)',
                        'rgba(163, 51, 200, 0.7)',
                        'rgba(33, 186, 69, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
        
        // Dados para o gráfico de acessos por cliente
        const ctxCliente = document.getElementById('acessosClienteChart').getContext('2d');
        const acessosClienteChart = new Chart(ctxCliente, {
            type: 'bar',
            data: {
                labels: [
                    <?php 
                    foreach ($acessos_por_cliente as $acesso) {
                        echo "'" . substr($acesso['cliente'], 0, 15) . (strlen($acesso['cliente']) > 15 ? '...' : '') . "',";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Acessos',
                    data: [
                        <?php 
                        foreach ($acessos_por_cliente as $acesso) {
                            echo $acesso['total'] . ",";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(33, 133, 208, 0.7)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Dados para o gráfico de acessos por usuário
        const ctxUsuario = document.getElementById('acessosUsuarioChart').getContext('2d');
        const acessosUsuarioChart = new Chart(ctxUsuario, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    foreach ($acessos_por_usuario as $acesso) {
                        echo "'" . $acesso['nome'] . "',";
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        foreach ($acessos_por_usuario as $acesso) {
                            echo $acesso['total'] . ",";
                        }
                        ?>
                    ],
                    backgroundColor: [
                        'rgba(33, 133, 208, 0.7)',
                        'rgba(0, 181, 173, 0.7)',
                        'rgba(242, 113, 28, 0.7)',
                        'rgba(219, 40, 40, 0.7)',
                        'rgba(163, 51, 200, 0.7)',
                        'rgba(33, 186, 69, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    });
</script>

<?php
// Incluir rodapé
include '../../includes/footer.php';
?>