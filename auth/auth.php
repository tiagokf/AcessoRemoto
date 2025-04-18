<?php
// auth/auth.php
// Funções de autenticação

// Função para autenticar usuário
function autenticarUsuario($email, $senha) {
    $email = dbEscape($email);
    
    // Buscar usuário pelo email
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $result = dbQuery($sql);
    
    if ($result->num_rows == 1) {
        $usuario = dbFetchAssoc($result);
        
        // Verificar senha
        if (verificarSenha($senha, $usuario['senha'])) {
            // Iniciar sessão
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nome'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
            
            // Atualizar último acesso
            $id = $usuario['id'];
            dbQuery("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = $id");
            
            return true;
        }
    }
    
    return false;
}

// Função para verificar se o usuário está logado
function estaLogado() {
    return isset($_SESSION['user_id']);
}

// Função para obter informações do usuário logado
function getUsuarioLogado() {
    if (!estaLogado()) {
        return null;
    }
    
    $id = $_SESSION['user_id'];
    $sql = "SELECT * FROM usuarios WHERE id = $id";
    $result = dbQuery($sql);
    
    if ($result->num_rows == 1) {
        return dbFetchAssoc($result);
    }
    
    return null;
}

// Função para verificar se o usuário é administrador
function ehAdmin() {
    return isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin';
}

// Função para exigir login
function exigirLogin() {
    if (!estaLogado()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

// Função para exigir nível de administrador
function exigirAdmin() {
    exigirLogin();
    
    if (!ehAdmin()) {
        header('Location: ' . SITE_URL . '/index.php?error=acesso_negado');
        exit;
    }
}
?>