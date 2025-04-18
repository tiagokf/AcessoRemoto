<?php
// modules/conexoes/listar.php
// Página para listar as conexões remotas

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário está logado
requireLogin();

// Incluir cabeçalho
include '../../includes/header.php';

// Incluir sidebar
include '../../includes/sidebar.php';

// Processar exclusão de conexão, se aplicável
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    
    // Verificar se há acessos vinculados
    $result = dbQuery("SELECT COUNT(*) as total FROM acessos WHERE id_conexao = $id");
    $row = dbFetchAssoc($result);
    
    if ($row['total'] > 0) {
        showAlert('Não é possível excluir esta conexão pois existem acessos vinculados a ela.', 'negative');
    } else {
        // Excluir a conexão
        dbQuery("DELETE FROM conexoes WHERE id = $id");
        showAlert('Conexão excluída com sucesso!', 'positive');
    }
}

// Configuração de busca e paginação
$busca = isset($_GET['busca']) ? dbEscape($_GET['busca']) : '';
$filtro_tipo = isset($_GET['filtro_tipo']) ? dbEscape($_GET['filtro_tipo']) : '';
$filtro_cliente = isset($_GET['filtro_cliente']) ? dbEscape($_GET['filtro_cliente']) : '';
$filtro_id_acesso = isset($_GET['filtro_id_acesso']) ? dbEscape($_GET['filtro_id_acesso']) : '';
$filtro_observacoes = isset($_GET['filtro_observacoes']) ? dbEscape($_GET['filtro_observacoes']) : '';
$filtro_data_inicio = isset($_GET['filtro_data_inicio']) ? dbEscape($_GET['filtro_data_inicio']) : '';
$filtro_data_fim = isset($_GET['filtro_data_fim']) ? dbEscape($_GET['filtro_data_fim']) : '';
$filtro_sem_acesso = isset($_GET['filtro_sem_acesso']) ? true : false;
$filtro_ordem = isset($_GET['filtro_ordem']) ? dbEscape($_GET['filtro_ordem']) : 'cliente_asc';

$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
$offset = ($pagina - 1) * $limite;

// Construir a consulta SQL
$whereClause = '';
$conditions = array();
if (!empty($busca)) {
    $conditions[] = "(cliente LIKE '%$busca%' OR tipo_acesso_remoto LIKE '%$busca%' OR id_acesso_remoto LIKE '%$busca%')";
}
if (!empty($filtro_tipo)) {
    $conditions[] = "tipo_acesso_remoto = '$filtro_tipo'";
}
if (!empty($filtro_cliente)) {
    $conditions[] = "cliente LIKE '%$filtro_cliente%'";
}
if (!empty($filtro_id_acesso)) {
    $conditions[] = "id_acesso_remoto LIKE '%$filtro_id_acesso%'";
}
if (!empty($filtro_observacoes)) {
    $conditions[] = "observacoes LIKE '%$filtro_observacoes%'";
}

// Adicionar condições de data usando uma subconsulta para o último acesso
if (!empty($filtro_data_inicio) || !empty($filtro_data_fim) || $filtro_sem_acesso) {
    // Subconsulta para acessos
    $acessosSubquery = "SELECT id_conexao, MAX(data_acesso) as ultimo_acesso FROM acessos GROUP BY id_conexao";
    
    if ($filtro_sem_acesso) {
        $conditions[] = "c.id NOT IN (SELECT id_conexao FROM acessos)";
    } else {
        if (!empty($filtro_data_inicio)) {
            $date_inicio = date('Y-m-d', strtotime($filtro_data_inicio));
            $acessosConditions[] = "ultimo_acesso >= '$date_inicio 00:00:00'";
        }
        
        if (!empty($filtro_data_fim)) {
            $date_fim = date('Y-m-d', strtotime($filtro_data_fim));
            $acessosConditions[] = "ultimo_acesso <= '$date_fim 23:59:59'";
        }
        
        if (!empty($acessosConditions)) {
            $acessosWhere = " WHERE " . implode(" AND ", $acessosConditions);
            $acessosSubquery .= $acessosWhere;
            $conditions[] = "c.id IN (SELECT id_conexao FROM ($acessosSubquery) as a)";
        }
    }
}

if (count($conditions) > 0) {
    $whereClause = ' WHERE ' . implode(' AND ', $conditions);
}

// Definir ordem
$orderClause = 'ORDER BY cliente ASC';
switch ($filtro_ordem) {
    case 'cliente_desc':
        $orderClause = 'ORDER BY cliente DESC';
        break;
    case 'tipo_asc':
        $orderClause = 'ORDER BY tipo_acesso_remoto ASC';
        break;
    case 'tipo_desc':
        $orderClause = 'ORDER BY tipo_acesso_remoto DESC';
        break;
    case 'id_asc':
        $orderClause = 'ORDER BY id ASC';
        break;
    case 'id_desc':
        $orderClause = 'ORDER BY id DESC';
        break;
    case 'recente':
        $orderClause = 'ORDER BY id DESC';
        break;
    case 'antigo':
        $orderClause = 'ORDER BY id ASC';
        break;
    default:
        $orderClause = 'ORDER BY cliente ASC';
}

// Para usar a subconsulta com o último acesso, precisamos do alias para a tabela principal
$sql = "SELECT c.* FROM conexoes c $whereClause $orderClause LIMIT $limite OFFSET $offset";
$result = dbQuery($sql);
$conexoes = dbFetchAll($result);

// Obter o total de registros para a paginação
$sqlTotal = "SELECT COUNT(*) as total FROM conexoes c $whereClause";
$resultTotal = dbQuery($sqlTotal);
$rowTotal = dbFetchAssoc($resultTotal);
$totalRegistros = $rowTotal['total'];
$totalPaginas = ceil($totalRegistros / $limite);

// Obter lista de tipos de acesso únicos para o filtro
dbQuery("SET SESSION group_concat_max_len = 10000");
$sqlTipos = "SELECT GROUP_CONCAT(DISTINCT tipo_acesso_remoto) as tipos FROM conexoes";
$resultTipos = dbQuery($sqlTipos);
$rowTipos = dbFetchAssoc($resultTipos);
$tiposAcesso = explode(',', $rowTipos['tipos']);
if (!in_array('RustDesk', $tiposAcesso)) {
    $tiposAcesso[] = 'RustDesk';
}
sort($tiposAcesso);

// Obter lista de clientes únicos para o filtro
$sqlClientes = "SELECT DISTINCT cliente FROM conexoes ORDER BY cliente";
$resultClientes = dbQuery($sqlClientes);
$clientes = array();
while ($row = dbFetchAssoc($resultClientes)) {
    $clientes[] = $row['cliente'];
}
?>

<!-- Conteúdo principal -->
<div class="main-content">
    <h1 class="ui header">
        <i class="server icon"></i>
        <div class="content">
            Conexões Remotas
            <div class="sub header">Gerenciar conexões de acesso remoto</div>
        </div>
    </h1>

    <div class="ui divider"></div>

    <!-- Estilos para a tabela de conexões -->
    <style>
        .ui.table td.ellipsis {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 250px;
        }
        .ui.table td.id-column {
            width: 60px;
        }
        .ui.table td.cliente-column {
            width: 20%;
            min-width: 150px;
        }
        .ui.table td.tipo-column {
            width: 100px;
        }
        .ui.table td.id-acesso-column {
            width: 15%;
            min-width: 120px;
        }
        .ui.table td.observacoes-column {
            width: 25%;
            min-width: 180px;
        }
        .ui.table td.acoes-column {
            width: 280px;
            text-align: center;
            white-space: nowrap;
        }
        .ui.table th.sorted {
            background-color: rgba(0, 0, 0, 0.05) !important;
        }
        .connection-row:hover {
            background-color: rgba(0, 0, 0, 0.03) !important;
        }
        .ui.table {
            border-collapse: collapse !important;
        }
        .ui.table tr.connection-row td {
            padding-top: 0.7em;
            padding-bottom: 0.7em;
        }
        .ui.popup {
            max-width: 350px;
            word-wrap: break-word;
        }
    </style>

    <!-- Barra de ações -->
    <div class="ui grid">
        <div class="eight wide column">
            <a onclick="abrirModalAdicionar()" class="ui primary button" href="javascript:void(0);">
                <i class="plus icon"></i> Nova Conexão
            </a>
            <a class="ui button" href="exportar.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>">
                <i class="download icon"></i> Exportar
            </a>
        </div>
        <div class="eight wide column">
            <form class="ui form" method="GET" action="">
                <div class="ui action input fluid">
                    <input type="text" name="busca" placeholder="Buscar conexões..."
                        value="<?php echo htmlspecialchars($busca ?? ''); ?>">
                    <button class="ui icon button" type="submit">
                        <i class="search icon"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="ui hidden divider"></div>

    <!-- Filtros Avançados -->
    <div class="ui styled fluid accordion">
        <div class="title">
            <i class="filter icon"></i> Filtros Avançados
            <?php if (count($conditions) > (empty($busca) ? 0 : 1)): ?>
                <div class="ui blue tiny label"><?php echo count($conditions) - (empty($busca) ? 0 : 1); ?> filtros ativos</div>
            <?php endif; ?>
        </div>
        <div class="content">
            <form class="ui form" method="GET" action="">
                <!-- Manter a busca se existir -->
                <?php if (!empty($busca)): ?>
                    <input type="hidden" name="busca" value="<?php echo htmlspecialchars($busca); ?>">
                <?php endif; ?>
                
                <div class="ui segments">
                    <div class="ui segment">
                        <h4 class="ui dividing header">Filtros Básicos</h4>
                        <div class="fields">
                            <div class="seven wide field">
                                <label>Cliente</label>
                                <div class="ui search selection dropdown" id="dropdown-cliente">
                                    <input type="hidden" name="filtro_cliente" value="<?php echo htmlspecialchars($filtro_cliente ?? ''); ?>">
                                    <i class="dropdown icon"></i>
                                    <div class="default text">Selecione o cliente</div>
                                    <div class="menu">
                                        <div class="item" data-value="">Todos os clientes</div>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <div class="item" data-value="<?php echo htmlspecialchars($cliente); ?>">
                                                <?php echo htmlspecialchars($cliente); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="five wide field">
                                <label>Tipo de Acesso</label>
                                <select name="filtro_tipo" class="ui dropdown">
                                    <option value="">Todos os Tipos</option>
                                    <?php foreach ($tiposAcesso as $tipo): ?>
                                        <option value="<?php echo htmlspecialchars($tipo); ?>" 
                                                <?php echo $filtro_tipo == $tipo ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="four wide field">
                                <label>ID de Acesso</label>
                                <input type="text" name="filtro_id_acesso" placeholder="ID de acesso" 
                                       value="<?php echo htmlspecialchars($filtro_id_acesso ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="ui segment">
                        <h4 class="ui dividing header">Filtros de Acesso</h4>
                        <div class="fields">
                            <div class="five wide field">
                                <label>Último Acesso - De</label>
                                <div class="ui calendar" id="data-inicio">
                                    <div class="ui input left icon">
                                        <i class="calendar icon"></i>
                                        <input type="text" name="filtro_data_inicio" placeholder="Data inicial" 
                                               value="<?php echo htmlspecialchars($filtro_data_inicio ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="five wide field">
                                <label>Último Acesso - Até</label>
                                <div class="ui calendar" id="data-fim">
                                    <div class="ui input left icon">
                                        <i class="calendar icon"></i>
                                        <input type="text" name="filtro_data_fim" placeholder="Data final" 
                                               value="<?php echo htmlspecialchars($filtro_data_fim ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="six wide field">
                                <label>Situação de Acesso</label>
                                <div class="ui checkbox">
                                    <input type="checkbox" name="filtro_sem_acesso" <?php echo $filtro_sem_acesso ? 'checked' : ''; ?>>
                                    <label>Mostrar apenas conexões nunca acessadas</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ui segment">
                        <h4 class="ui dividing header">Ordenação e Exibição</h4>
                        <div class="fields">
                            <div class="eight wide field">
                                <label>Ordenar por</label>
                                <select name="filtro_ordem" class="ui dropdown">
                                    <option value="cliente_asc" <?php echo $filtro_ordem == 'cliente_asc' ? 'selected' : ''; ?>>Cliente (A-Z)</option>
                                    <option value="cliente_desc" <?php echo $filtro_ordem == 'cliente_desc' ? 'selected' : ''; ?>>Cliente (Z-A)</option>
                                    <option value="tipo_asc" <?php echo $filtro_ordem == 'tipo_asc' ? 'selected' : ''; ?>>Tipo (A-Z)</option>
                                    <option value="tipo_desc" <?php echo $filtro_ordem == 'tipo_desc' ? 'selected' : ''; ?>>Tipo (Z-A)</option>
                                    <option value="recente" <?php echo $filtro_ordem == 'recente' ? 'selected' : ''; ?>>Mais recentes primeiro</option>
                                    <option value="antigo" <?php echo $filtro_ordem == 'antigo' ? 'selected' : ''; ?>>Mais antigos primeiro</option>
                                </select>
                            </div>
                            
                            <div class="four wide field">
                                <label>Itens por página</label>
                                <select name="limite" class="ui dropdown">
                                    <option value="10" <?php echo $limite == 10 ? 'selected' : ''; ?>>10 itens</option>
                                    <option value="25" <?php echo $limite == 25 ? 'selected' : ''; ?>>25 itens</option>
                                    <option value="50" <?php echo $limite == 50 ? 'selected' : ''; ?>>50 itens</option>
                                    <option value="100" <?php echo $limite == 100 ? 'selected' : ''; ?>>100 itens</option>
                                </select>
                            </div>
                            
                            <div class="four wide field">
                                <label>Observações</label>
                                <input type="text" name="filtro_observacoes" placeholder="Pesquisar em observações" 
                                       value="<?php echo htmlspecialchars($filtro_observacoes ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 1em;">
                    <button type="submit" class="ui primary button">
                        <i class="filter icon"></i> Aplicar Filtros
                    </button>
                    
                    <a href="listar.php" class="ui button">
                        <i class="times icon"></i> Limpar Filtros
                    </a>
                    
                    <button type="button" class="ui right floated button" id="btn-salvar-filtro">
                        <i class="save icon"></i> Salvar Filtro
                    </button>
                    
                    <div class="ui right floated dropdown button" id="btn-filtros-salvos">
                        <i class="folder open icon"></i> Filtros Salvos
                        <div class="menu" id="menu-filtros-salvos">
                            <div class="header">
                                <i class="tags icon"></i> Seus filtros salvos
                            </div>
                            <div class="divider"></div>
                            <!-- Filtros salvos serão carregados via JavaScript -->
                            <div class="item disabled">Nenhum filtro salvo</div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="ui hidden divider"></div>
    
    <!-- Resumo do filtro -->
    <?php if (count($conditions) > 0): ?>
    <div class="ui info message">
        <i class="close icon"></i>
        <div class="header">Filtros Aplicados</div>
        <p>Mostrando <?php echo $totalRegistros; ?> <?php echo $totalRegistros == 1 ? 'conexão' : 'conexões'; ?> que correspondem aos filtros selecionados.</p>
    </div>
    <?php endif; ?>

    <!-- Tabela de conexões -->
    <table class="ui celled table selectable">
        <thead>
            <tr>
                <th class="center aligned id-column">ID</th>
                <th class="cliente-column">Cliente</th>
                <th class="tipo-column">Tipo</th>
                <th class="id-acesso-column">ID de Acesso</th>
                <th class="observacoes-column">Observações</th>
                <th class="center aligned acoes-column">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($conexoes) > 0): ?>
                <?php foreach ($conexoes as $conexao): ?>
                    <tr class="connection-row">
                        <td class="center aligned id-column"><?php echo $conexao['id']; ?></td>
                        <td class="ellipsis cliente-column" data-tooltip="<?php echo htmlspecialchars($conexao['cliente'] ?? ''); ?>" data-position="top left">
                            <?php echo htmlspecialchars($conexao['cliente'] ?? ''); ?>
                        </td>
                        <td class="ellipsis tipo-column" data-tooltip="<?php echo htmlspecialchars($conexao['tipo_acesso_remoto'] ?? ''); ?>" data-position="top left">
                            <div class="ui label" style="
                                padding: 4px 8px;
                                font-size: 0.85em;
                                background-color: <?php 
                                switch($conexao['tipo_acesso_remoto'] ?? '') {
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
                                <?php echo htmlspecialchars($conexao['tipo_acesso_remoto'] ?? ''); ?>
                            </div>
                        </td>
                        <td class="ellipsis id-acesso-column" data-tooltip="<?php echo htmlspecialchars($conexao['id_acesso_remoto'] ?? ''); ?>" data-position="top left">
                            <?php echo htmlspecialchars($conexao['id_acesso_remoto'] ?? ''); ?>
                        </td>
                        <td class="ellipsis observacoes-column" data-tooltip="<?php echo htmlspecialchars($conexao['observacoes'] ?? ''); ?>" data-position="top left">
                            <?php echo htmlspecialchars($conexao['observacoes'] ?? ''); ?>
                        </td>
                        <td class="acoes-column">
                            <a class="ui mini blue button" href="javascript:void(0);" onclick="acessarConexao(<?php echo $conexao['id']; ?>, 
                                '<?php echo htmlspecialchars(addslashes($conexao['cliente'] ?? '')); ?>', 
                                '<?php echo htmlspecialchars(addslashes($conexao['tipo_acesso_remoto'] ?? '')); ?>', 
                                '<?php echo htmlspecialchars(addslashes($conexao['id_acesso_remoto'] ?? '')); ?>', 
                                '<?php echo htmlspecialchars(addslashes($conexao['senha_acesso_remoto'] ?? '')); ?>')">
                                <i class="external alternate icon"></i> Acessar
                            </a>
                            
                            <a class="ui mini green button" href="javascript:void(0);" onclick="abrirModalEditar(<?php echo $conexao['id']; ?>, 
                                '<?php echo htmlspecialchars(addslashes($conexao['cliente'] ?? '')); ?>', 
                                '<?php echo htmlspecialchars(addslashes($conexao['tipo_acesso_remoto'] ?? '')); ?>', 
                                '<?php echo htmlspecialchars(addslashes($conexao['id_acesso_remoto'] ?? '')); ?>', 
                                '<?php echo htmlspecialchars(addslashes($conexao['senha_acesso_remoto'] ?? '')); ?>', 
                                '<?php echo htmlspecialchars(addslashes($conexao['observacoes'] ?? '')); ?>')">
                                <i class="edit icon"></i> Editar
                            </a>
                            
                            <a class="ui mini red button" href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $conexao['id']; ?>)">
                                <i class="trash icon"></i> Excluir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="center aligned">Nenhuma conexão encontrada</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6">
                    <?php if ($totalPaginas > 1): ?>
                        <div class="ui right floated pagination menu">
                            <?php 
                            // Construir a query string para manter os filtros na paginação
                            $queryParams = $_GET;
                            unset($queryParams['pagina']);
                            $queryString = http_build_query($queryParams);
                            $queryPrefix = !empty($queryString) ? "?$queryString&pagina=" : "?pagina=";
                            ?>
                            
                            <?php if ($pagina > 1): ?>
                                <a class="item" href="<?php echo $queryPrefix; ?>1">
                                    <i class="angle double left icon"></i>
                                </a>
                                <a class="item" href="<?php echo $queryPrefix . ($pagina - 1); ?>">
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
                                    echo "<a class='item' href='" . $queryPrefix . $i . "'>$i</a>";
                                }
                            }
                            ?>
                            
                            <?php if ($pagina < $totalPaginas): ?>
                                <a class="item" href="<?php echo $queryPrefix . ($pagina + 1); ?>">
                                    <i class="angle right icon"></i>
                                </a>
                                <a class="item" href="<?php echo $queryPrefix . $totalPaginas; ?>">
                                    <i class="angle double right icon"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        Exibindo <?php echo count($conexoes); ?> de <?php echo $totalRegistros; ?> conexões
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
        <p>Tem certeza que deseja excluir esta conexão?</p>
    </div>
    <div class="actions">
        <div class="ui cancel button">Cancelar</div>
        <div class="ui red approve button" id="btn-confirmar-exclusao">Excluir</div>
    </div>
</div>

<!-- Modal de acesso remoto -->
<div class="ui small modal" id="modal-acesso">
    <div class="header">
        <i class="external alternate icon"></i> Acessar Conexão Remota
    </div>
    <div class="content">
        <div class="ui form">
            <div class="field">
                <label>Cliente</label>
                <div class="ui disabled input">
                    <input type="text" id="modal-cliente" readonly>
                </div>
            </div>

            <div class="field">
                <label>Tipo de Acesso</label>
                <div class="ui disabled input">
                    <input type="text" id="modal-tipo" readonly>
                </div>
            </div>

            <div class="field">
                <label>ID de Acesso</label>
                <div class="ui action input">
                    <input type="text" id="modal-id-acesso" readonly>
                    <button class="ui icon button" onclick="copiarParaClipboard('modal-id-acesso')">
                        <i class="copy icon"></i>
                    </button>
                </div>
            </div>

            <div class="field" id="campo-senha">
                <label>Senha de Acesso</label>
                <div class="ui action input">
                    <input type="text" id="modal-senha" readonly>
                    <button class="ui icon button" onclick="copiarParaClipboard('modal-senha')">
                        <i class="copy icon"></i>
                    </button>
                </div>
            </div>

            <div class="field" id="campo-senha-padrao" style="display: none;">
                <label>Senha Padrão</label>
                <div class="ui action input">
                    <select class="ui dropdown" id="select-senha-padrao">
                        <option value="SemSenha">SemSenha</option>
                        <option value="RustDesk@2020">RustDesk@2020</option>
                    </select>
                    <button class="ui icon button" onclick="copiarSenhaPadrao()">
                        <i class="copy icon"></i>
                    </button>
                </div>
            </div>

            <div class="ui info message">
                <div class="header">Instruções</div>
                <p>1. Copie o ID de acesso</p>
                <p>2. Abra seu aplicativo de acesso remoto</p>
                <p>3. Cole o ID e use a senha informada</p>
            </div>
        </div>
    </div>
    <div class="actions">
        <div class="ui approve primary button">Fechar</div>
    </div>
</div>

<!-- Modal para Adicionar/Editar Conexão -->
<div class="ui small modal" id="modal-conexao">
    <div class="header" id="modal-conexao-titulo">
        <i class="server icon"></i> Nova Conexão
    </div>
    <div class="content">
        <form class="ui form" id="form-conexao">
            <input type="hidden" name="id" id="conexao-id">
            <div class="field">
                <label>Cliente</label>
                <input type="text" name="cliente" id="conexao-cliente" placeholder="Nome do cliente" required>
            </div>
            <div class="field">
                <label>Tipo de Acesso</label>
                <select name="tipo_acesso" id="conexao-tipo" class="ui fluid dropdown" required>
                    <?php foreach ($tiposAcesso as $tipo): ?>
                    <option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>ID de Acesso</label>
                <input type="text" name="id_acesso" id="conexao-id-acesso" placeholder="ID de acesso remoto" required>
            </div>
            <div class="field">
                <label>Senha de Acesso (opcional)</label>
                <input type="text" name="senha_acesso" id="conexao-senha" placeholder="Senha de acesso remoto">
            </div>
            <div class="field">
                <label>Observações</label>
                <textarea name="observacoes" id="conexao-observacoes"
                    placeholder="Observações sobre a conexão"></textarea>
            </div>
        </form>
    </div>
    <div class="actions">
        <div class="ui cancel button">Cancelar</div>
        <button class="ui primary approve button" onclick="salvarConexao()">Salvar</button>
    </div>
</div>

<!-- Modal para Salvar Filtro -->
<div class="ui tiny modal" id="modal-salvar-filtro">
    <div class="header"><i class="save icon"></i> Salvar Filtro</div>
    <div class="content">
        <form class="ui form" id="form-salvar-filtro">
            <div class="field">
                <label>Nome do Filtro</label>
                <input type="text" name="nome_filtro" id="nome-filtro" placeholder="Digite um nome para identificar este filtro">
            </div>
        </form>
    </div>
    <div class="actions">
        <div class="ui cancel button">Cancelar</div>
        <div class="ui primary approve button" id="btn-confirmar-salvar-filtro">Salvar</div>
    </div>
</div>

<script>
    function confirmarExclusao(id) {
        $('#modal-excluir').modal({
            closable: false,
            onApprove: function () {
                window.location.href = '?excluir=' + id;
            }
        }).modal('show');
    }

    function acessarConexao(id, cliente, tipo, idAcesso, senha) {
        // Preencher os campos do modal
        $('#modal-cliente').val(cliente);
        $('#modal-tipo').val(tipo);
        $('#modal-id-acesso').val(idAcesso);

        // Exibir/ocultar campos de senha de acordo com as informações
        if (senha) {
            $('#modal-senha').val(senha);
            $('#campo-senha').show();
            $('#campo-senha-padrao').hide();
        } else {
            $('#campo-senha').hide();
            $('#campo-senha-padrao').show();

            // Selecionar senha padrão com base no tipo
            if (tipo === 'RustDesk') {
                $('#select-senha-padrao').val('RustDesk@2020');
            } else {
                $('#select-senha-padrao').val('SemSenha');
            }
            $('.ui.dropdown').dropdown('refresh');
        }

        // Registrar o acesso
        $.post('registrar_acesso.php', {
            id_conexao: id,
            detalhes: 'Acesso via botão Acessar'
        });

        // Exibir o modal
        $('#modal-acesso').modal('show');
    }

    function copiarParaClipboard(elementId) {
        const elemento = document.getElementById(elementId);
        elemento.select();
        document.execCommand('copy');

        // Feedback visual
        $('body')
            .toast({
                class: 'success',
                message: 'Copiado para a área de transferência',
                showProgress: 'bottom',
                displayTime: 2000
            });
    }

    function copiarSenhaPadrao() {
        const senha = document.getElementById('select-senha-padrao').value;
        navigator.clipboard.writeText(senha).then(function () {
            showCopyFeedback();
        });
    }

    function showCopyFeedback() {
        // Criar e mostrar um toast
        $('body')
            .toast({
                class: 'success',
                message: 'Copiado para a área de transferência',
                showProgress: 'bottom',
                displayTime: 2000
            });
    }

    function abrirModalAdicionar() {
        $('#modal-conexao-titulo').html('<i class="server icon"></i> Nova Conexão');
        $('#form-conexao')[0].reset();
        $('#conexao-id').val('');
        $('.ui.dropdown').dropdown('refresh');
        $('#modal-conexao').modal('show');
    }

    function abrirModalEditar(id, cliente, tipo, idAcesso, senha, observacoes) {
        $('#modal-conexao-titulo').html('<i class="edit icon"></i> Editar Conexão');
        $('#form-conexao')[0].reset();
        $('#conexao-id').val(id);
        $('#conexao-cliente').val(cliente);
        $('#conexao-tipo').val(tipo);
        $('#conexao-id-acesso').val(idAcesso);
        $('#conexao-senha').val(senha);
        $('#conexao-observacoes').val(observacoes);
        $('.ui.dropdown').dropdown('refresh');
        $('#modal-conexao').modal('show');
    }

    function salvarConexao() {
        if ($('#form-conexao').form('is valid')) {
            $.ajax({
                url: 'salvar_conexao.php',
                method: 'POST',
                data: $('#form-conexao').serialize(),
                success: function (response) {
                    try {
                        var data = JSON.parse(response);
                        if (data.success) {
                            $('body').toast({
                                class: 'success',
                                message: data.message || 'Conexão salva com sucesso!',
                                showProgress: 'bottom',
                                displayTime: 2000
                            });
                            $('#modal-conexao').modal('hide');
                            setTimeout(function () {
                                window.location.reload();
                            }, 2000);
                        } else {
                            $('body').toast({
                                class: 'error',
                                message: data.message || 'Erro ao salvar conexão.',
                                showProgress: 'bottom',
                                displayTime: 2000
                            });
                        }
                    } catch (e) {
                        $('body').toast({
                            class: 'error',
                            message: 'Erro ao processar resposta do servidor.',
                            showProgress: 'bottom',
                            displayTime: 2000
                        });
                    }
                },
                error: function () {
                    $('body').toast({
                        class: 'error',
                        message: 'Erro de conexão com o servidor.',
                        showProgress: 'bottom',
                        displayTime: 2000
                    });
                }
            });
        }
    }

    $(document).ready(function () {
        $('.ui.dropdown').dropdown();
        $('.ui.accordion').accordion();
        $('.ui.checkbox').checkbox();
        $('.message .close').on('click', function() {
            $(this).closest('.message').transition('fade');
        });
        
        // Inicializar datepickers
        $('#data-inicio').calendar({
            type: 'date',
            formatter: {
                date: function(date, settings) {
                    if (!date) return '';
                    var day = date.getDate();
                    var month = date.getMonth() + 1;
                    var year = date.getFullYear();
                    return (day < 10 ? '0' + day : day) + '/' + 
                           (month < 10 ? '0' + month : month) + '/' + 
                           year;
                }
            }
        });
        
        $('#data-fim').calendar({
            type: 'date',
            formatter: {
                date: function(date, settings) {
                    if (!date) return '';
                    var day = date.getDate();
                    var month = date.getMonth() + 1;
                    var year = date.getFullYear();
                    return (day < 10 ? '0' + day : day) + '/' + 
                           (month < 10 ? '0' + month : month) + '/' + 
                           year;
                }
            }
        });
        
        // Inicializar dropdown de cliente com busca
        $('#dropdown-cliente').dropdown({
            fullTextSearch: true,
            allowAdditions: false
        });
        
        // Inicializar dropdown de filtros salvos
        $('#btn-filtros-salvos').dropdown();
        
        // Carregar filtros salvos logo ao carregar a página
        carregarFiltrosSalvos();
        
        // Salvar filtro atual
        $('#btn-salvar-filtro').on('click', function() {
            $('#nome-filtro').val(''); // Limpar o campo
            $('#modal-salvar-filtro').modal({
                closable: false,
                onApprove: function() {
                    salvarFiltroAtual();
                    return true;
                }
            }).modal('show');
        });
        
        // Inicializar tooltips para células com texto truncado
        $('.ellipsis').popup({
            hoverable: true,
            delay: {
                show: 300,
                hide: 100
            }
        });
        
        // Validação do formulário
        $('#form-conexao').form({
            fields: {
                cliente: {
                    identifier: 'cliente',
                    rules: [{
                        type: 'empty',
                        prompt: 'Por favor, insira o nome do cliente'
                    }]
                },
                tipo_acesso: {
                    identifier: 'tipo_acesso',
                    rules: [{
                        type: 'empty',
                        prompt: 'Por favor, selecione o tipo de acesso'
                    }]
                },
                id_acesso: {
                    identifier: 'id_acesso',
                    rules: [{
                        type: 'empty',
                        prompt: 'Por favor, insira o ID de acesso'
                    }]
                }
            }
        });
        
        // Validação do formulário de salvar filtro
        $('#form-salvar-filtro').form({
            fields: {
                nome_filtro: {
                    identifier: 'nome_filtro',
                    rules: [{
                        type: 'empty',
                        prompt: 'Por favor, insira um nome para o filtro'
                    }]
                }
            }
        });
    });
    
    // Função para salvar o filtro atual
    function salvarFiltroAtual() {
        const nomeFiltro = $('#nome-filtro').val();
        if (!nomeFiltro) {
            $('body').toast({
                class: 'error',
                message: 'Digite um nome para o filtro',
                showProgress: 'bottom',
                displayTime: 2000
            });
            return false;
        }
        
        // Obter a query string atual
        const queryString = window.location.search.substring(1);
        if (!queryString) {
            $('body').toast({
                class: 'warning',
                message: 'Não há filtros para salvar',
                showProgress: 'bottom',
                displayTime: 2000
            });
            return false;
        }
        
        // Salvar no localStorage
        const filtrosSalvos = JSON.parse(localStorage.getItem('filtrosSalvos') || '[]');
        
        // Verificar se já existe um filtro com este nome
        const nomeExistente = filtrosSalvos.findIndex(f => f.nome.toLowerCase() === nomeFiltro.toLowerCase()) >= 0;
        if (nomeExistente) {
            if (!confirm(`Já existe um filtro chamado "${nomeFiltro}". Deseja substituí-lo?`)) {
                return false;
            }
            // Remover o filtro existente
            const index = filtrosSalvos.findIndex(f => f.nome.toLowerCase() === nomeFiltro.toLowerCase());
            if (index >= 0) {
                filtrosSalvos.splice(index, 1);
            }
        }
        
        // Adicionar o novo filtro
        filtrosSalvos.push({
            nome: nomeFiltro,
            query: queryString,
            data: new Date().toISOString()
        });
        
        // Ordenar por nome
        filtrosSalvos.sort((a, b) => a.nome.localeCompare(b.nome));
        
        // Salvar no localStorage
        localStorage.setItem('filtrosSalvos', JSON.stringify(filtrosSalvos));
        
        // Recarregar lista de filtros
        carregarFiltrosSalvos();
        
        // Feedback para o usuário
        $('body').toast({
            class: 'success',
            message: 'Filtro salvo com sucesso!',
            showProgress: 'bottom',
            displayTime: 2000
        });
        
        return true;
    }
    
    function carregarFiltrosSalvos() {
        const filtrosSalvos = JSON.parse(localStorage.getItem('filtrosSalvos') || '[]');
        const menu = $('#menu-filtros-salvos');
        
        // Limpar menu exceto o cabeçalho e o divider
        menu.find('.item:not(.header):not(.disabled)').remove();
        
        if (filtrosSalvos.length === 0) {
            // Verificar se já existe um item "Nenhum filtro salvo"
            if (menu.find('.item.disabled').length === 0) {
                menu.append('<div class="item disabled">Nenhum filtro salvo</div>');
            }
            return;
        } else {
            // Remover o item "Nenhum filtro salvo" se existir
            menu.find('.item.disabled').remove();
        }
        
        // Adicionar filtros salvos ao menu
        filtrosSalvos.forEach((filtro, index) => {
            try {
                const dataFormatada = new Date(filtro.data).toLocaleDateString('pt-BR');
                
                const item = $(`
                    <div class="item" data-value="${index}">
                        <span class="text">${filtro.nome}</span>
                        <span class="description">${dataFormatada}</span>
                        <i class="trash alternate outline icon right floated delete-filter" data-index="${index}"></i>
                    </div>
                `);
                
                menu.append(item);
            } catch (e) {
                console.error('Erro ao processar filtro:', e);
            }
        });
        
        // Configurar eventos de click nos filtros
        menu.find('.item:not(.header):not(.disabled)').on('click', function(e) {
            // Verificar se clicou no ícone de lixeira
            if ($(e.target).hasClass('delete-filter') || $(e.target).closest('.delete-filter').length > 0) {
                e.stopPropagation();
                const index = $(e.target).data('index') || $(e.target).closest('.delete-filter').data('index');
                excluirFiltro(index);
                return false;
            }
            
            // Aplicar o filtro
            const index = $(this).data('value');
            if (typeof index !== 'undefined' && filtrosSalvos[index]) {
                const query = filtrosSalvos[index].query;
                window.location.href = `?${query}`;
            }
        });
    }
    
    function excluirFiltro(index) {
        const filtrosSalvos = JSON.parse(localStorage.getItem('filtrosSalvos') || '[]');
        
        if (index >= 0 && index < filtrosSalvos.length) {
            const nome = filtrosSalvos[index].nome;
            filtrosSalvos.splice(index, 1);
            localStorage.setItem('filtrosSalvos', JSON.stringify(filtrosSalvos));
            
            // Recarregar lista de filtros
            carregarFiltrosSalvos();
            
            // Feedback
            $('body').toast({
                class: 'info',
                message: `Filtro "${nome}" removido`,
                showProgress: 'bottom',
                displayTime: 2000
            });
        }
    }
</script>

<?php
// Incluir rodapé
include '../../includes/footer.php';
?>