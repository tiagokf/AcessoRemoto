<?php
// modules/conexoes/adicionar.php
// Página para adicionar nova conexão remota

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário está logado
requireLogin();

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
        // Inserir no banco de dados
        $id_usuario = $_SESSION['user_id'];
        $sql = "INSERT INTO conexoes (cliente, tipo_acesso_remoto, id_acesso_remoto, senha_acesso_remoto, observacoes, id_usuario) 
                VALUES ('$cliente', '$tipo_acesso', '$id_acesso', '$senha_acesso', '$observacoes', $id_usuario)";
        
        if (dbQuery($sql)) {
            showAlert('Conexão adicionada com sucesso!', 'positive');
            // Redirecionar após um breve atraso (para mostrar a mensagem)
            echo "<script>setTimeout(function(){ window.location.href = 'listar.php'; }, 1500);</script>";
        } else {
            showAlert('Erro ao adicionar conexão', 'negative');
        }
    }
}
?>

<!-- Conteúdo principal -->
<div class="main-content">
    <h1 class="ui header">
        <i class="plus icon"></i>
        <div class="content">
            Nova Conexão
            <div class="sub header">Adicionar uma nova conexão de acesso remoto</div>
        </div>
    </h1>
    
    <div class="ui divider"></div>
    
    <!-- Formulário de adição -->
    <div class="ui segment">
        <form class="ui form" method="POST" action="">
            <div class="two fields">
                <div class="required field">
                    <label>Cliente</label>
                    <input type="text" name="cliente" placeholder="Nome do Cliente" required>
                </div>
                <div class="field">
                    <label>Tipo de Acesso</label>
                    <select class="ui dropdown" name="tipo_acesso">
                        <option value="AnyDesk">AnyDesk</option>
                        <option value="TeamViewer">TeamViewer</option>
                        <option value="VPN">VPN</option>
                        <option value="RDP">RDP</option>
                        <option value="SSH">SSH</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="two fields">
                <div class="field">
                    <label>ID de Acesso</label>
                    <input type="text" name="id_acesso" placeholder="ID para acesso remoto">
                </div>
                <div class="field">
                    <label>Senha de Acesso</label>
                    <input type="password" name="senha_acesso" placeholder="Senha para acesso remoto">
                </div>
            </div>
            
            <div class="field">
                <label>Observações</label>
                <textarea name="observacoes" rows="3" placeholder="Informações adicionais sobre o acesso"></textarea>
            </div>
            
            <div class="ui hidden divider"></div>
            
            <div class="ui buttons">
                <a href="listar.php" class="ui button">Cancelar</a>
                <div class="or" data-text="ou"></div>
                <button type="submit" class="ui positive button">Salvar</button>
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