<?php
// modules/usuarios/editar.php
// Página para editar usuário existente

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário é administrador
exigirAdmin();

// Verificar se o ID foi informado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    showAlert('ID inválido', 'negative');
    header('Location: listar.php');
    exit;
}

$id = intval($_GET['id']);

// Obter dados do usuário
$result = dbQuery("SELECT * FROM usuarios WHERE id = $id");
if ($result->num_rows == 0) {
    showAlert('Usuário não encontrado', 'negative');
    header('Location: listar.php');
    exit;
}

$usuario = dbFetchAssoc($result);

// Incluir cabeçalho
include '../../includes/header.php';

// Incluir sidebar
include '../../includes/sidebar.php';

// Processar o formulário, se enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obter dados do formulário
    $nome = isset($_POST['nome']) ? dbEscape($_POST['nome']) : '';
    $email = isset($_POST['email']) ? dbEscape($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
    $confirmar_senha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';
    $nivel_acesso = isset($_POST['nivel_acesso']) ? dbEscape($_POST['nivel_acesso']) : 'usuario';
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($email)) {
        showAlert('Nome e e-mail são obrigatórios', 'negative');
    } else {
        // Verificar se o e-mail já existe (exceto para o próprio usuário)
        $sql = "SELECT * FROM usuarios WHERE email = '$email' AND id != $id";
        $result = dbQuery($sql);
        
        if ($result->num_rows > 0) {
            showAlert('Este e-mail já está sendo utilizado por outro usuário', 'negative');
        } else {
            // Verificar se está tentando alterar o nível do último administrador
            if ($usuario['nivel_acesso'] == 'admin' && $nivel_acesso != 'admin') {
                $result = dbQuery("SELECT COUNT(*) as total FROM usuarios WHERE nivel_acesso = 'admin'");
                $row = dbFetchAssoc($result);
                
                if ($row['total'] <= 1) {
                    showAlert('Não é possível rebaixar o último administrador do sistema.', 'negative');
                    $atualizar = false;
                } else {
                    $atualizar = true;
                }
            } else {
                $atualizar = true;
            }
            
            if ($atualizar) {
                // Construir a consulta SQL
                $sql = "UPDATE usuarios SET nome = '$nome', email = '$email', nivel_acesso = '$nivel_acesso'";
                
                // Só atualiza a senha se uma nova for fornecida
                if (!empty($senha)) {
                    if ($senha != $confirmar_senha) {
                        showAlert('As senhas não conferem', 'negative');
                        $atualizar = false;
                    } elseif (strlen($senha) < 6) {
                        showAlert('A senha deve ter pelo menos 6 caracteres', 'negative');
                        $atualizar = false;
                    } else {
                        $senha_hash = gerarHash($senha);
                        $sql .= ", senha = '$senha_hash'";
                    }
                }
                
                if ($atualizar) {
                    $sql .= " WHERE id = $id";
                    
                    if (dbQuery($sql)) {
                        showAlert('Usuário atualizado com sucesso!', 'positive');
                        // Atualizar os dados na sessão se for o usuário atual
                        if ($id == $_SESSION['user_id']) {
                            $_SESSION['user_name'] = $nome;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['nivel_acesso'] = $nivel_acesso;
                        }
                        
                        // Recarregar dados atualizados
                        $result = dbQuery("SELECT * FROM usuarios WHERE id = $id");
                        $usuario = dbFetchAssoc($result);
                    } else {
                        showAlert('Erro ao atualizar usuário', 'negative');
                    }
                }
            }
        }
    }
}
?>

<!-- Conteúdo principal -->
<div class="main-content">
    <h1 class="ui header">
        <i class="edit icon"></i>
        <div class="content">
            Editar Usuário
            <div class="sub header">Modificar informações do usuário</div>
        </div>
    </h1>
    
    <div class="ui divider"></div>
    
    <!-- Formulário de edição -->
    <div class="ui segment">
        <form class="ui form" method="POST" action="">
            <div class="two fields">
                <div class="required field">
                    <label>Nome</label>
                    <input type="text" name="nome" placeholder="Nome completo" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                </div>
                <div class="required field">
                    <label>E-mail</label>
                    <input type="email" name="email" placeholder="E-mail" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                </div>
            </div>
            
            <div class="two fields">
                <div class="field">
                    <label>Nova Senha</label>
                    <input type="password" name="senha" placeholder="Deixe em branco para manter a senha atual">
                    <small>Deixe em branco para manter a senha atual</small>
                </div>
                <div class="field">
                    <label>Confirmar Nova Senha</label>
                    <input type="password" name="confirmar_senha" placeholder="Digite a nova senha novamente">
                </div>
            </div>
            
            <div class="field">
                <label>Nível de Acesso</label>
                <select class="ui dropdown" name="nivel_acesso">
                    <option value="usuario" <?php echo ($usuario['nivel_acesso'] == 'usuario') ? 'selected' : ''; ?>>Usuário</option>
                    <option value="admin" <?php echo ($usuario['nivel_acesso'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                </select>
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