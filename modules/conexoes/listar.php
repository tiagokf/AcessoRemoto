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
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Construir a consulta SQL
$whereClause = '';
if (!empty($busca)) {
    $whereClause = " WHERE cliente LIKE '%$busca%' OR tipo_acesso_remoto LIKE '%$busca%'";
}

$sql = "SELECT * FROM conexoes $whereClause ORDER BY cliente ASC LIMIT $limite OFFSET $offset";
$result = dbQuery($sql);
$conexoes = dbFetchAll($result);

// Obter o total de registros para a paginação
$sqlTotal = "SELECT COUNT(*) as total FROM conexoes $whereClause";
$resultTotal = dbQuery($sqlTotal);
$rowTotal = dbFetchAssoc($resultTotal);
$totalRegistros = $rowTotal['total'];
$totalPaginas = ceil($totalRegistros / $limite);
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
    
    <!-- Barra de ações -->
    <div class="ui grid">
        <div class="eight wide column">
            <a href="adicionar.php" class="ui primary button">
                <i class="plus icon"></i> Nova Conexão
            </a>
        </div>
        <div class="eight wide column">
            <form class="ui form" method="GET" action="">
                <div class="ui action input fluid">
                    <input type="text" name="busca" placeholder="Buscar conexões..." value="<?php echo htmlspecialchars($busca ?? ''); ?>">
                    <button class="ui icon button" type="submit">
                        <i class="search icon"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="ui hidden divider"></div>
    
    <!-- Tabela de conexões -->
    <table class="ui celled table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>ID de Acesso</th>
                <th>Observações</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($conexoes) > 0): ?>
                <?php foreach ($conexoes as $conexao): ?>
                    <tr>
                        <td><?php echo $conexao['id']; ?></td>
                        <td><?php echo htmlspecialchars($conexao['cliente'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($conexao['tipo_acesso_remoto'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($conexao['id_acesso_remoto'] ?? ''); ?></td>
                        <td><?php 
                            $observacao = htmlspecialchars($conexao['observacoes'] ?? '');
                            echo (strlen($observacao) > 50) ? substr($observacao, 0, 50) . '...' : $observacao;
                        ?></td>
                        <td class="center aligned collapsing">
                            <div class="ui mini buttons">
                                <button onclick="acessarConexao(<?php echo $conexao['id']; ?>, 
                                    '<?php echo htmlspecialchars($conexao['cliente'] ?? ''); ?>', 
                                    '<?php echo htmlspecialchars($conexao['tipo_acesso_remoto'] ?? ''); ?>', 
                                    '<?php echo htmlspecialchars($conexao['id_acesso_remoto'] ?? ''); ?>', 
                                    '<?php echo htmlspecialchars($conexao['senha_acesso_remoto'] ?? ''); ?>')" 
                                    class="ui blue button">
                                    <i class="external alternate icon"></i> Acessar
                                </button>
                                
                                <a href="editar.php?id=<?php echo $conexao['id']; ?>" class="ui green button">
                                    <i class="edit icon"></i> Editar
                                </a>
                                <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $conexao['id']; ?>)" class="ui red button">
                                    <i class="trash icon"></i> Excluir
                                </a>
                            </div>
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
                            <?php if ($pagina > 1): ?>
                                <a class="item" href="?pagina=1<?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>">
                                    <i class="angle double left icon"></i>
                                </a>
                                <a class="item" href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>">
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
                                    echo "<a class='item' href='?pagina=$i" . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . "'>$i</a>";
                                }
                            }
                            ?>
                            
                            <?php if ($pagina < $totalPaginas): ?>
                                <a class="item" href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>">
                                    <i class="angle right icon"></i>
                                </a>
                                <a class="item" href="?pagina=<?php echo $totalPaginas; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>">
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

<script>
    function confirmarExclusao(id) {
        $('#modal-excluir').modal({
            closable: false,
            onApprove: function() {
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
        showCopyFeedback();
    }
    
    function copiarSenhaPadrao() {
        const senha = document.getElementById('select-senha-padrao').value;
        navigator.clipboard.writeText(senha).then(function() {
            showCopyFeedback();
        });
    }
    
    function showCopyFeedback() {
        // Criar e mostrar um toast
        $('body')
            .toast({
                class: 'success',
                message: 'Copiado para a área de transferência!',
                showProgress: 'bottom',
                displayTime: 2000
            });
    }
    
    $(document).ready(function() {
        $('.ui.dropdown').dropdown();
    });
</script>

<?php
// Incluir rodapé
include '../../includes/footer.php';
?>