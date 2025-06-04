<?php
// modules/usuarios/editar.php
// Página para editar usuário existente

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário está logado (qualquer usuário logado pode editar seu perfil)
exigirLogin();

$id = $_SESSION['user_id']; // Usuário só pode editar seu próprio perfil

// Obter dados do usuário logado
// Selecionar apenas os campos necessários e não sensíveis para preencher o formulário
$sql_get_user = "SELECT id, nome, email, nivel_acesso FROM usuarios WHERE id = ?";
$result_get_user = dbQueryPrepared($sql_get_user, [$id], "i");

$usuario = null;
if ($result_get_user && $result_get_user->num_rows > 0) {
    $usuario = dbFetchAssoc($result_get_user);
} else {
    // Se não encontrar o usuário da sessão no DB (improvável, mas defensivo)
    showAlert('Erro ao carregar dados do usuário. Por favor, tente logar novamente.', 'negative');
    // Destruir sessão e redirecionar para login pode ser uma opção aqui
    // session_destroy(); header('Location: ' . SITE_URL . '/auth/login.php'); exit;
    // Por agora, apenas mostra o erro e impede a renderização do formulário mais abaixo.
}


// Incluir cabeçalho
include '../../includes/header.php';

// Incluir sidebar
include '../../includes/sidebar.php';

// Processar o formulário, se enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // O ID do usuário a ser editado é sempre o da sessão.
    $editing_user_id = $_SESSION['user_id'];

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        showAlert('Falha na validação de segurança (CSRF). Por favor, tente novamente.', 'negative');
    } else {
        // Obter dados do formulário
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? ''; // Senha não é trimada antes da validação de comprimento
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        // Nível de acesso não é pego do POST, pois não deve ser editável pelo usuário aqui.
        
        $proceed_to_save = true; // Flag para controlar o fluxo de salvamento

        // Validar campos obrigatórios
        if (empty($nome) || empty($email)) {
            showAlert('Nome e e-mail são obrigatórios', 'negative');
            $proceed_to_save = false;
        }

        // Validar formato de e-mail
        if ($proceed_to_save && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            showAlert('Formato de e-mail inválido.', 'negative');
            $proceed_to_save = false;
        }

        // Validar comprimento do nome e email
        if ($proceed_to_save && mb_strlen($nome) > 255) {
            showAlert('O nome não pode exceder 255 caracteres.', 'negative');
            $proceed_to_save = false;
        }
        if ($proceed_to_save && mb_strlen($email) > 255) {
            showAlert('O e-mail não pode exceder 255 caracteres.', 'negative');
            $proceed_to_save = false;
        }

        // Validações de senha (apenas se a senha foi fornecida)
        if ($proceed_to_save && !empty($senha)) {
            if ($senha != $confirmar_senha) {
                showAlert('As senhas não conferem', 'negative');
                $proceed_to_save = false;
            } elseif (strlen($senha) < 6) {
                showAlert('A senha deve ter pelo menos 6 caracteres', 'negative');
                $proceed_to_save = false;
            }
        }

        if ($proceed_to_save) {
            // Verificar se o e-mail já existe (exceto para o próprio usuário)
            $sql_check_email = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
            // $editing_user_id is $_SESSION['user_id']
            $result_check_email = dbQueryPrepared($sql_check_email, [$email, $editing_user_id], "si");

            if ($result_check_email === false) {
                showAlert('Ocorreu um erro ao verificar o e-mail. Por favor, tente novamente.', 'negative');
                $proceed_to_save = false;
            } elseif ($result_check_email && $result_check_email->num_rows > 0) {
                showAlert('Este e-mail já está sendo utilizado por outro usuário.', 'negative');
                $proceed_to_save = false;
            }
        }

        // Removida a verificação de "último administrador" pois usuário não edita seu nível aqui.
            
        if ($proceed_to_save) {
            // Construir a consulta SQL - usuário não pode mudar seu nivel_acesso aqui
            $sql_update_user = "UPDATE usuarios SET nome = ?, email = ?";
            $params_update_user = [$nome, $email];
            $types_update_user = "ss";

            if (!empty($senha)) { // A validação da senha (match, length) já ocorreu antes
                $senha_hash = gerarHash($senha);
                $sql_update_user .= ", senha = ?";
                $params_update_user[] = $senha_hash;
                $types_update_user .= "s";
            }

            $sql_update_user .= " WHERE id = ?";
            $params_update_user[] = $editing_user_id; // Sempre o ID da sessão
            $types_update_user .= "i";

            $update_result = dbQueryPrepared($sql_update_user, $params_update_user, $types_update_user);

            if ($update_result) {
                showAlert('Perfil atualizado com sucesso!', 'positive');
                // Atualizar dados da sessão
                $_SESSION['user_name'] = $nome;
                $_SESSION['user_email'] = $email;
                // $_SESSION['nivel_acesso'] não muda aqui.
                
                // Recarregar dados atualizados para exibir no formulário
                // Usar $editing_user_id que é o $_SESSION['user_id']
                $sql_reload_user = "SELECT id, nome, email, nivel_acesso FROM usuarios WHERE id = ?";
                $result_reload_user = dbQueryPrepared($sql_reload_user, [$editing_user_id], "i");
                if ($result_reload_user && $result_reload_user->num_rows > 0) {
                    $usuario = dbFetchAssoc($result_reload_user); // Atualiza $usuario para o formulário
                } else {
                     showAlert('Ocorreu um erro ao recarregar os dados do usuário após a atualização.', 'warning');
                }
            } else {
                showAlert('Erro ao atualizar o perfil. Tente novamente.', 'negative');
            }
        }
    } // Encerra o else do CSRF
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
    
    <?php if ($usuario === null): ?>
        <div class="ui error message">
            Não foi possível carregar os dados do usuário. Verifique os logs para mais detalhes ou tente novamente mais tarde.
        </div>
    <?php else: ?>
    <!-- Formulário de edição -->
    <div class="ui segment">
        <form class="ui form" method="POST" action="">
            <div class="two fields">
                <div class="required field">
                    <label>Nome</label>
                    <input type="text" name="nome" placeholder="Nome completo" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
                </div>
                <div class="required field">
                    <label>E-mail</label>
                    <input type="email" name="email" placeholder="E-mail" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
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
                <input type="text" readonly value="<?php echo htmlspecialchars(ucfirst($usuario['nivel_acesso'] ?? '')); ?>"
                       class="ui disabled input" title="Nível de acesso não pode ser alterado nesta página.">
                <!-- <select class="ui dropdown" name="nivel_acesso" disabled>
                    <option value="usuario" <?php //echo (($usuario['nivel_acesso'] ?? 'usuario') == 'usuario') ? 'selected' : ''; ?>>Usuário</option>
                    <option value="admin" <?php //echo (($usuario['nivel_acesso'] ?? 'usuario') == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                </select> -->
            </div>
            
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

            <div class="ui hidden divider"></div>
            
            <div class="ui buttons">
                <a href="listar.php" class="ui button">Cancelar</a>
                <div class="or" data-text="ou"></div>
                <button type="submit" class="ui positive button">Salvar Alterações</button>
            </div>
        </form>
    </div>
    <?php endif; ?> <!-- Encerra o else do if ($usuario === null) -->
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