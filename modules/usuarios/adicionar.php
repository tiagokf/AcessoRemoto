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
        $nome = isset($_POST['nome']) ? dbEscape($_POST['nome']) : '';
        $email = isset($_POST['email']) ? dbEscape($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
    $confirmar_senha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';
    $nivel_acesso = isset($_POST['nivel_acesso']) ? dbEscape($_POST['nivel_acesso']) : 'usuario';
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($email) || empty($senha)) {
        showAlert('Todos os campos são obrigatórios', 'negative');
    } elseif ($senha != $confirmar_senha) {
        showAlert('As senhas não conferem', 'negative');
    } elseif (strlen($senha) < 6) {
        showAlert('A senha deve ter pelo menos 6 caracteres', 'negative');
    } else {
        // Verificar se o e-mail já existe
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $result = dbQuery($sql);
        
        if ($result->num_rows > 0) {
            showAlert('Este e-mail já está cadastrado', 'negative');
        } else {
            // Gerar hash da senha
            $senha_hash = gerarHash($senha);
            
            // Inserir no banco de dados
            $sql = "INSERT INTO usuarios (nome, email, senha, nivel_acesso) 
                    VALUES ('$nome', '$email', '$senha_hash', '$nivel_acesso')";
            
            if (dbQuery($sql)) {
                showAlert('Usuário adicionado com sucesso!', 'positive');
                // Redirecionar após um breve atraso (para mostrar a mensagem)
                echo "<script>setTimeout(function(){ window.location.href = 'listar.php'; }, 1500);</script>";
            } else {
                showAlert('Erro ao adicionar usuário', 'negative');
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