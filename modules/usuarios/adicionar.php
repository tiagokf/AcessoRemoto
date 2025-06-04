<?php
// modules/usuarios/adicionar.php
// Página para adicionar novo usuário

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário é administrador
exigirAdmin();

// Incluir cabeçalho
include '../../includes/header.php';

// Incluir sidebar
include '../../includes/sidebar.php';

// Processar o formulário, se enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        showAlert('Falha na validação de segurança. Por favor, tente novamente.', 'negative');
    } else {
        // Obter dados do formulário
        $nome = trim($_POST['nome'] ?? ''); // Trim para remover espaços extras
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? ''; // Senha não precisa de trim ou dbEscape
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        $nivel_acesso = trim($_POST['nivel_acesso'] ?? 'usuario'); // Trim para remover espaços extras
        
        $valid_data = true; // Flag para controlar o fluxo

        // Validar campos obrigatórios
        if (empty($nome) || empty($email) || empty($senha)) {
            showAlert('Todos os campos são obrigatórios', 'negative');
            $valid_data = false;
        }

        // Validar formato de e-mail
        if ($valid_data && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            showAlert('Formato de e-mail inválido.', 'negative');
            $valid_data = false;
        }

        // Validar comprimento do nome e email
        if ($valid_data && mb_strlen($nome) > 255) {
            showAlert('O nome não pode exceder 255 caracteres.', 'negative');
            $valid_data = false;
        }
        if ($valid_data && mb_strlen($email) > 255) {
            showAlert('O e-mail não pode exceder 255 caracteres.', 'negative');
            $valid_data = false;
        }

        // Validar nível de acesso
        $allowed_levels = ['usuario', 'admin'];
        if ($valid_data && !in_array($nivel_acesso, $allowed_levels)) {
            showAlert('Nível de acesso inválido.', 'negative');
            $valid_data = false;
        }

        // Validar senha
        if ($valid_data && $senha != $confirmar_senha) {
            showAlert('As senhas não conferem', 'negative');
            $valid_data = false;
        }
        if ($valid_data && strlen($senha) < 6) {
            showAlert('A senha deve ter pelo menos 6 caracteres', 'negative');
            $valid_data = false;
        }

        if ($valid_data) {
            // Verificar se o e-mail já existe
            $sql_check_email = "SELECT id FROM usuarios WHERE email = ?";
            $result_check_email = dbQueryPrepared($sql_check_email, [$email], "s");

            if ($result_check_email === false) {
                showAlert('Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.', 'negative');
                $valid_data = false; // Impede a continuação
            } elseif ($result_check_email && $result_check_email->num_rows > 0) {
                showAlert('Este e-mail já está cadastrado', 'negative');
                $valid_data = false; // Impede a continuação
            }
            
            if ($valid_data) { // Re-checar $valid_data antes de prosseguir
                // Gerar hash da senha
                $senha_hash = gerarHash($senha);

                // Inserir no banco de dados
                $sql_insert_user = "INSERT INTO usuarios (nome, email, senha, nivel_acesso)
                                    VALUES (?, ?, ?, ?)";
                $params_insert_user = [$nome, $email, $senha_hash, $nivel_acesso];
                $types_insert_user = "ssss";

                $insert_result = dbQueryPrepared($sql_insert_user, $params_insert_user, $types_insert_user);

                if ($insert_result) {
                    showAlert('Usuário adicionado com sucesso!', 'positive');
                    // Redirecionar após um breve atraso (para mostrar a mensagem)
                    echo "<script>setTimeout(function(){ window.location.href = 'listar.php'; }, 1500);</script>";
                } else {
                    showAlert('Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.', 'negative');
            }
        }
    }
}
?>

<!-- Conteúdo principal -->
<div class="main-content">
    <h1 class="ui header">
        <i class="user plus icon"></i>
        <div class="content">
            Novo Usuário
            <div class="sub header">Adicionar um novo usuário ao sistema</div>
        </div>
    </h1>
    
    <div class="ui divider"></div>
    
    <!-- Formulário de adição -->
    <div class="ui segment">
        <form class="ui form" method="POST" action="">
            <div class="two fields">
                <div class="required field">
                    <label>Nome</label>
                    <input type="text" name="nome" placeholder="Nome completo" required>
                </div>
                <div class="required field">
                    <label>E-mail</label>
                    <input type="email" name="email" placeholder="E-mail" required>
                </div>