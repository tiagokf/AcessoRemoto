<?php
// modules/acessos/listar.php
// Página para listar histórico de acessos

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário está logado
exigirLogin();

// Processar exclusão de acesso, se aplicável
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    
    // Excluir o acesso
    $result = dbQueryPrepared("DELETE FROM acessos WHERE id = ?", [$id], "i");
    
    if ($result) { // dbQueryPrepared returns true on successful DML if no result set, or a result object
        // For DELETE, check affected rows if dbQueryPrepared was adapted to return it,
        // or assume success if it doesn't return false.
        // For now, we assume if it's not false, it worked.
        showAlert('Registro de acesso excluído com sucesso!', 'positive');
    } else {
        showAlert('Erro ao excluir registro de acesso.', 'negative');
    }
    
    // Redirecionar de volta para a URL atual sem o parâmetro 'excluir'
    $currentUrl = $_SERVER['REQUEST_URI'];
    $redirectUrl = preg_replace('/&?excluir=\\d+/', '', $currentUrl);
    header("Location: $redirectUrl");
    exit;
}

// Incluir cabeçalho
include '../../includes/header.php';

// Incluir sidebar
include '../../includes/sidebar.php';

// Processar filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-7 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$cliente = isset($_GET['cliente']) ? dbEscape($_GET['cliente']) : '';
$usuario = isset($_GET['usuario']) ? dbEscape($_GET['usuario']) : '';
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

// Construir cláusula WHERE para filtros e parâmetros para prepared statements
$conditions = [];
$params = [];
$types = "";

// Filtro de data (obrigatório)
$conditions[] = "a.data_acesso BETWEEN ? AND ?";
$params[] = $data_inicio . " 00:00:00";
$params[] = $data_fim . " 23:59:59";
$types .= "ss";

if (!empty($cliente)) {
    $conditions[] = "c.cliente LIKE ?";
    $params[] = "%" . $cliente . "%";
    $types .= "s";
}

if (ehAdmin() && !empty($usuario)) {
    $conditions[] = "u.nome LIKE ?";
    $params[] = "%" . $usuario . "%";
    $types .= "s";
} elseif (!ehAdmin()) {
    $conditions[] = "a.id_usuario = ?";
    $params[] = $_SESSION['user_id'];
    $types .= "i";
}

$whereClause = "";
if (!empty($conditions)) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// Consulta para obter os acessos
$sqlBase = "FROM acessos a
            JOIN conexoes c ON a.id_conexao = c.id
            JOIN usuarios u ON a.id_usuario = u.id
            $whereClause";

$sql = "SELECT a.*, c.cliente, c.tipo_acesso_remoto, u.nome as usuario_nome 
        $sqlBase
        ORDER BY a.data_acesso DESC 
        LIMIT ? OFFSET ?";
$paramsWithPagination = array_merge($params, [$limite, $offset]);
$typesWithPagination = $types . "ii";

$result = dbQueryPrepared($sql, $paramsWithPagination, $typesWithPagination);
$acessos = $result ? dbFetchAll($result) : [];

// Obter o total de registros para a paginação
$sqlTotal = "SELECT COUNT(*) as total $sqlBase";
$resultTotal = dbQueryPrepared($sqlTotal, $params, $types); // Usa os mesmos params e types do WHERE clause
$totalRegistros = 0;
if ($resultTotal) {
    $rowTotal = dbFetchAssoc($resultTotal);
    if ($rowTotal) {
        $totalRegistros = $rowTotal['total'];
    }
}
$totalPaginas = ceil($totalRegistros / $limite);

// Obter lista de usuários para o filtro (apenas para admins)
$usuarios = [];
if (ehAdmin()) {
    $sqlUsers = "SELECT id, nome FROM usuarios ORDER BY nome";
    $resultUsers = dbQueryPrepared($sqlUsers, [], "");
    if ($resultUsers) {
        $usuarios = dbFetchAll($resultUsers);
    }
}
?>

<!-- Conteúdo principal -->
<div class="main-content">
    <h1 class="ui header">
        <i class="key icon"></i>
        <div class="content">
            Histórico de Acessos
            <div class="sub header">Registro de todos os acessos às conexões remotas</div>
        </div>
    </h1>
    
    <div class="ui divider"></div>
    
    <!-- Filtros -->
    <div class="ui segment">
        <form class="ui form" method="GET" action="">
            <h4 class="ui dividing header">Filtros</h4>
            
            <div class="two fields">
                <div class="field">
                    <label>Data Inicial</label>
                    <input type="date" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                <div class="field">
                    <label>Data Final</label>
                    <input type="date" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
            </div>
            
            <div class="two fields">
                <div class="field">
                    <label>Cliente</label>
                    <input type="text" name="cliente" placeholder="Nome do cliente" value="<?php echo htmlspecialchars($cliente); ?>">
                </div>
                <?php if (ehAdmin()): ?>
                <div class="field">
                    <label>Usuário</label>
                    <select class="ui dropdown" name="usuario">
                        <option value="">Todos os usuários</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?php echo htmlspecialchars($u['nome']); ?>" <?php echo ($usuario == $u['nome']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="field">
                <button class="ui primary button" type="submit">
                    <i class="filter icon"></i> Filtrar
                </button>
                <a href="listar.php" class="ui button">
                    <i class="undo icon"></i> Limpar Filtros
                </a>
            </div>
        </form>
    </div>
    
    <!-- Tabela de acessos -->
    <table class="ui celled table">
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Cliente</th>
                <th>Tipo de Acesso</th>
                <?php if (ehAdmin()): ?>
                <th>Usuário</th>
                <?php endif; ?>
                <th>IP</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($acessos) > 0): ?>
                <?php foreach ($acessos as $acesso): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($acesso['data_acesso'])); ?></td>
                        <td><?php echo htmlspecialchars($acesso['cliente']); ?></td>
                        <td>
                            <span class="ui label" style="
                                padding: 4px 8px;
                                font-size: 0.85em;
                                background-color: <?php
                                switch(htmlspecialchars($acesso['tipo_acesso_remoto'] ?? '')) {
                                    case 'AnyDesk': echo '#ef443b'; break;
                                    case 'TeamViewer': echo '#1a68d6'; break;
                                    case 'RDP': echo '#2c82c9'; break;
                                    case 'VPN': echo '#27ae60'; break;
                                    case 'SSH': echo '#333333'; break;
                                    case 'RustDesk': echo '#4d4d4d'; break;
                                    case 'Supremo': echo '#7928CA'; break;
                                    default: echo '#7f8c8d'; break;
                                }
                                ?>;
                                color: white;"
                            >
                                <?php echo htmlspecialchars($acesso['tipo_acesso_remoto']); ?>
                            </span>
                        </td>
                        <?php if (ehAdmin()): ?>
                        <td><?php echo htmlspecialchars($acesso['usuario_nome']); ?></td>
                        <?php endif; ?>
                        <td><?php echo htmlspecialchars($acesso['ip_acesso']); ?></td>
                        <td>
                            <a class="ui mini red button" href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $acesso['id']; ?>)">
                                <i class="trash icon"></i> Excluir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo ehAdmin() ? '6' : '5'; ?>" class="center aligned">
                        Nenhum acesso encontrado com os filtros selecionados
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="<?php echo ehAdmin() ? '6' : '5'; ?>">
                    <?php if ($totalPaginas > 1): ?>
                        <div class="ui right floated pagination menu">
                            <?php if ($pagina > 1): ?>
                                <a class="item" href="?pagina=1&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&cliente=<?php echo urlencode($cliente); ?>&usuario=<?php echo urlencode($usuario); ?>">
                                    <i class="angle double left icon"></i>
                                </a>
                                <a class="item" href="?pagina=<?php echo $pagina - 1; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&cliente=<?php echo urlencode($cliente); ?>&usuario=<?php echo urlencode($usuario); ?>">
                                    <i class="angle left icon"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            // Mostrar apenas algumas páginas
                            $intervalo = 2;
                            $inicio = max(1, $pagina - $intervalo);
                            $fim = min($totalPaginas, $pagina + $intervalo);
                            
                            for ($i = $inicio; $i <= $fim; $i++) {
                                if ($i == $pagina) {
                                    echo "<a class='active item'>$i</a>";
                                } else {
                                    echo "<a class='item' href='?pagina=$i&data_inicio=$data_inicio&data_fim=$data_fim&cliente=" . urlencode($cliente) . "&usuario=" . urlencode($usuario) . "'>$i</a>";
                                }
                            }
                            ?>
                            
                            <?php if ($pagina < $totalPaginas): ?>
                                <a class="item" href="?pagina=<?php echo $pagina + 1; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&cliente=<?php echo urlencode($cliente); ?>&usuario=<?php echo urlencode($usuario); ?>">
                                    <i class="angle right icon"></i>
                                </a>
                                <a class="item" href="?pagina=<?php echo $totalPaginas; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&cliente=<?php echo urlencode($cliente); ?>&usuario=<?php echo urlencode($usuario); ?>">
                                    <i class="angle double right icon"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        Exibindo <?php echo count($acessos); ?> de <?php echo $totalRegistros; ?> acessos
                    </div>
                </th>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="ui tiny modal" id="modal-excluir">
    <div class="header">Confirmar Exclusão</div>
    <div class="content">
        <p>Tem certeza que deseja excluir este registro de acesso?</p>
    </div>
    <div class="actions">
        <div class="ui cancel button">Cancelar</div>
        <div class="ui red approve button" id="btn-confirmar-exclusao">Excluir</div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.ui.dropdown').dropdown();
        
        // Configurar o modal de exclusão
        $('#modal-excluir').modal({
            closable: false
        });
    });
    
    function confirmarExclusao(id) {
        $('#modal-excluir').modal({
            closable: false,
            onApprove: function () {
                // Adicionar o parâmetro excluir à URL atual preservando outros parâmetros
                let currentUrl = window.location.href;
                if (currentUrl.indexOf('?') !== -1) {
                    currentUrl += '&excluir=' + id;
                } else {
                    currentUrl += '?excluir=' + id;
                }
                window.location.href = currentUrl;
            }
        }).modal('show');
    }
</script>

<?php
// Incluir rodapé
include '../../includes/footer.php';
?>