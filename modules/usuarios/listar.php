<?php
// modules/usuarios/listar.php
// Página para listar os usuários do sistema

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário é administrador
requireAdmin();

// Incluir cabeçalho
include '../../includes/header.php';

// Incluir sidebar
include '../../includes/sidebar.php';

// Processar exclusão de usuário, se aplicável
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    
    // Não permitir excluir o próprio usuário
    if ($id == $_SESSION['user_id']) {
        showAlert('Você não pode excluir seu próprio usuário.', 'negative');
    } else {
        // Verificar se é o último administrador
        if ($_SESSION['nivel_acesso'] == 'admin') {
            $result = dbQuery("SELECT COUNT(*) as total FROM usuarios WHERE nivel_acesso = 'admin'");
            $row = dbFetchAssoc($result);
            
            $result_user = dbQuery("SELECT nivel_acesso FROM usuarios WHERE id = $id");
            $user_row = dbFetchAssoc($result_user);
            
            if ($row['total'] <= 1 && $user_row['nivel_acesso'] == 'admin') {
                showAlert('Não é possível excluir o último administrador do sistema.', 'negative');
            } else {
                // Verificar se há acessos vinculados
                $result = dbQuery("SELECT COUNT(*) as total FROM acessos WHERE id_usuario = $id");
                $row = dbFetchAssoc($result);
                
                if ($row['total'] > 0) {
                    showAlert('Não é possível excluir este usuário pois existem acessos vinculados a ele.', 'negative');
                } else {
                    // Excluir o usuário
                    dbQuery("DELETE FROM usuarios WHERE id = $id");
                    showAlert('Usuário excluído com sucesso!', 'positive');
                }
            }
        }
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
    $whereClause = " WHERE nome LIKE '%$busca%' OR email LIKE '%$busca%'";
}

$sql = "SELECT * FROM usuarios $whereClause ORDER BY nome ASC LIMIT $limite OFFSET $offset";
$result = dbQuery($sql);
$usuarios = dbFetchAll($result);

// Obter o total de registros para a paginação
$sqlTotal = "SELECT COUNT(*) as total FROM usuarios $whereClause";
$resultTotal = dbQuery($sqlTotal);
$rowTotal = dbFetchAssoc($resultTotal);
$totalRegistros = $rowTotal['total'];
$totalPaginas = ceil($totalRegistros / $limite);
?>

<!-- Conteúdo principal -->
<div class="main-content">
    <h1 class="ui header">
        <i class="users icon"></i>
        <div class="content">
            Usuários
            <div class="sub header">Gerenciar usuários do sistema</div>
        </div>
    </h1>
    
    <div class="ui divider"></div>
    
    <!-- Barra de ações -->
    <div class="ui grid">
        <div class="eight wide column">
            <a href="adicionar.php" class="ui primary button">
                <i class="plus icon"></i> Novo Usuário
            </a>
        </div>
        <div class="eight wide column">
            <form class="ui form" method="GET" action="">
                <div class="ui action input fluid">
                    <input type="text" name="busca" placeholder="Buscar usuários..." value="<?php echo htmlspecialchars($busca); ?>">
                    <button class="ui icon button" type="submit">
                        <i class="search icon"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="ui hidden divider"></div>
    
    <!-- Tabela de usuários -->
    <table class="ui celled table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Nível de Acesso</th>
                <th>Último Acesso</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($usuarios) > 0): ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <?php if ($usuario['nivel_acesso'] == 'admin'): ?>
                                <div class="ui blue label">Administrador</div>
                            <?php else: ?>
                                <div class="ui green label">Usuário</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Nunca acessou'; ?>
                        </td>
                        <td class="center aligned collapsing">
                            <div class="ui mini buttons">
                                <a href="editar.php?id=<?php echo $usuario['id']; ?>" class="ui green button">
                                    <i class="edit icon"></i> Editar
                                </a>
                                <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                    <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $usuario['id']; ?>)" class="ui red button">
                                        <i class="trash icon"></i> Excluir
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="center aligned">Nenhum usuário encontrado</td>
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
                        Exibindo <?php echo count($usuarios); ?> de <?php echo $totalRegistros; ?> usuários
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
        <p>Tem certeza que deseja excluir este usuário?</p>
    </div>
    <div class="actions">
        <div class="ui cancel button">Cancelar</div>
        <div class="ui red approve button" id="btn-confirmar-exclusao">Excluir</div>
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
</script>

<?php
// Incluir rodapé
include '../../includes/footer.php';
?>