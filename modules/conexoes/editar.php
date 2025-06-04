<?php
// modules/conexoes/editar.php
// Página para editar uma conexão existente

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário está logado
exigirLogin();

// Verificar se o ID foi informado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    showAlert('ID inválido', 'negative');
    header('Location: listar.php');
    exit;
}

$id = intval($_GET['id']);

// Obter dados da conexão
$result = dbQuery("SELECT * FROM conexoes WHERE id = $id");
if ($result->num_rows == 0) {
    showAlert('Conexão não encontrada', 'negative');
    header('Location: listar.php');
    exit;
}

$conexao = dbFetchAssoc($result);

// Incluir cabeçalho
include '../../includes/header.php';

// Incluir sidebar
include '../../includes/sidebar.php';

// Processar o formulário, se enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obter dados do formulário
    $cliente = isset($_POST['cliente']) ? dbEscape($_POST['cliente']) : '';
    $tipo_acesso = isset($_POST['tipo_acesso']) ? dbEscape($_POST['tipo_acesso']) : '';
    $id_acesso = isset($_POST['id_acesso']) ? dbEscape($_POST['id_acesso']) : '';
    $senha_acesso = isset($_POST['senha_acesso']) ? dbEscape($_POST['senha_acesso']) : '';
    $observacoes = isset($_POST['observacoes']) ? dbEscape($_POST['observacoes']) : '';
    
    // Validar campos obrigatórios
    if (empty($cliente)) {
        showAlert('O campo Cliente é obrigatório', 'negative');
    } else {
        // Atualizar no banco de dados
        $sql = "UPDATE conexoes SET 
                cliente = '$cliente', 
                tipo_acesso_remoto = '$tipo_acesso', 
                id_acesso_remoto = '$id_acesso', 
                observacoes = '$observacoes'";
        
        // Só atualiza a senha se uma nova for fornecida
        if (!empty($senha_acesso)) {
            $sql .= ", senha_acesso_remoto = '$senha_acesso'";
        }
        
        $sql .= " WHERE id = $id";
        
        if (dbQuery($sql)) {
            showAlert('Conexão atualizada com sucesso!', 'positive');
            // Recarregar dados atualizados
            $result = dbQuery("SELECT * FROM conexoes WHERE id = $id");
            $conexao = dbFetchAssoc($result);
        } else {
            showAlert('Erro ao atualizar conexão', 'negative');
        }
    }
}
?>

<!-- Conteúdo principal -->
<div class="main-content">
    <h1 class="ui header">
        <i class="edit icon"></i>
        <div class="content">
            Editar Conexão
            <div class="sub header">Modificar informações da conexão</div>
        </div>
    </h1>
    
    <div class="ui divider"></div>
    
    <!-- Formulário de edição -->
    <div class="ui segment">
        <form class="ui form" method="POST" action="">
            <div class="two fields">
                <div class="required field">
                    <label>Cliente</label>
                    <input type="text" name="cliente" placeholder="Nome do Cliente" value="<?php echo htmlspecialchars($conexao['cliente']); ?>" required>
                </div>
                <div class="field">
                    <label>Tipo de Acesso</label>
                    <select class="ui dropdown" name="tipo_acesso">
                        <option value="AnyDesk" <?php echo ($conexao['tipo_acesso_remoto'] == 'AnyDesk') ? 'selected' : ''; ?>>AnyDesk</option>
                        <option value="TeamViewer" <?php echo ($conexao['tipo_acesso_remoto'] == 'TeamViewer') ? 'selected' : ''; ?>>TeamViewer</option>
                        <option value="VPN" <?php echo ($conexao['tipo_acesso_remoto'] == 'VPN') ? 'selected' : ''; ?>>VPN</option>
                        <option value="RDP" <?php echo ($conexao['tipo_acesso_remoto'] == 'RDP') ? 'selected' : ''; ?>>RDP</option>
                        <option value="SSH" <?php echo ($conexao['tipo_acesso_remoto'] == 'SSH') ? 'selected' : ''; ?>>SSH</option>
                        <option value="Outro" <?php echo ($conexao['tipo_acesso_remoto'] == 'Outro') ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="two fields">
                <div class="field">
                    <label>ID de Acesso</label>
                    <input type="text" name="id_acesso" placeholder="ID para acesso remoto" value="<?php echo htmlspecialchars($conexao['id_acesso_remoto']); ?>">
                </div>
                <div class="field">
                    <label>Senha de Acesso</label>
                    <input type="password" name="senha_acesso" placeholder="Deixe em branco para manter a senha atual">
                    <small>Deixe em branco para manter a senha atual</small>
                </div>
            </div>
            
            <div class="field">
                <label>Observações</label>
                <textarea name="observacoes" rows="3" placeholder="Informações adicionais sobre o acesso"><?php echo htmlspecialchars($conexao['observacoes']); ?></textarea>
            </div>
            
            <div class="ui hidden divider"></div>
            
            <div class="ui buttons">
                <a href="listar.php" class="ui button">Cancelar</a>
                <div class="or" data-text="ou"></div>
                <button type="submit" class="ui positive button">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.ui.dropdown').dropdown();
    });
</script>

<?php
// Incluir rodapé
include '../../includes/footer.php';
?>