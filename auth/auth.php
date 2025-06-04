<?php
// auth/auth.php
// Funções de autenticação

// Função para autenticar usuário
function autenticarUsuario($email, $senha) {
    // Buscar usuário pelo email
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $result = dbQueryPrepared($sql, [$email], "s");
    
    // Se $result for false (erro na query), ou não encontrar linhas, a autenticação falha.
    if ($result === false || $result->num_rows != 1) {
        // Opcional: logar se $result === false para distinguir erro DB de "não encontrado"
        if ($result === false) {
            error_log("Erro no DB ao buscar usuário para login: " . $email);
        }
        return false;
    }
    // Se chegou aqui, $result é um objeto mysqli_result válido e encontrou 1 linha.
    $usuario = dbFetchAssoc($result);
    // A lógica subsequente de verificar senha e atualizar último acesso permanece a mesma,
    // mas a query de update também precisa ser verificada.
        
    if (verificarSenha($senha, $usuario['senha'])) {
        // Iniciar sessão (regenerate_id já está no início da verificação de senha)
        session_regenerate_id(true);
            
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['nome'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
            
        // Atualizar último acesso
        $id_usuario = $usuario['id']; // Renomeado para clareza
        $update_sql = "UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?";
        if (!dbQueryPrepared($update_sql, [$id_usuario], "i")) {
            error_log("Falha ao atualizar ultimo_acesso para usuário ID: " . $id_usuario);
            // Considerar se isso deve impedir o login. Por ora, o login prossegue.
        }

        return true;
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
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $result = dbQueryPrepared($sql, [$id], "i");
    
    // Se $result for false (erro na query) ou não encontrar linhas, retorna null.
    if ($result === false || $result->num_rows != 1) {
        if ($result === false) {
            error_log("Erro no DB ao buscar usuário logado ID: " . $id);
        }
        return null;
    }
    
    return dbFetchAssoc($result);

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