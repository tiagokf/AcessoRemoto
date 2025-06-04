<?php
// config/config.php
// Configurações gerais do sistema

// Informações do Site
define('SITE_NAME', 'Sistema de Acesso Remoto');
define('SITE_URL', 'http://localhost:8092');

// Configuração de sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir o arquivo de autenticação
require_once __DIR__ . '/../auth/auth.php';

// Função para gerar hash seguro de senha
function gerarHash($senha) {
    return password_hash($senha, PASSWORD_BCRYPT);
}

// Função para verificar senha
function verificarSenha($senha, $hash) {
    return password_verify($senha, $hash);
}

// Função para registro de atividades
function registrarAcesso($id_conexao, $detalhes = '') {
    if (!isLoggedIn()) return false;
    
    $id_usuario = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $detalhes = dbEscape($detalhes);
    
    $sql = "INSERT INTO acessos (id_conexao, id_usuario, ip_acesso, detalhes) 
            VALUES ($id_conexao, $id_usuario, '$ip', '$detalhes')";
    
    return dbQuery($sql);
}

// Função para mostrar mensagens de alerta
function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Função para exibir as mensagens de alerta
function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        // Garantir que $alert['type'] seja seguro para classes CSS (alfanumérico)
        $type = preg_replace('/[^a-zA-Z0-9-]/', '', $alert['type'] ?? 'info');
        echo '<div class="ui ' . $type . ' message">';
        echo htmlspecialchars($alert['message'] ?? ''); // Escapar a mensagem aqui
        echo '</div>';
        unset($_SESSION['alert']);
    }
}

// Funções CSRF
function generateCsrfToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start(); // Garantir que a sessão esteja ativa
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getCsrfToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start(); // Garantir que a sessão esteja ativa
    }
    // Gera o token se não existir ao tentar obter
    if (empty($_SESSION['csrf_token'])) {
        generateCsrfToken();
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token_from_form) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start(); // Garantir que a sessão esteja ativa
    }
    if (!empty($token_from_form) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token_from_form)) {
        return true;
    }
    // Log CSRF failure attempt
    error_log("CSRF token validation failed. Form token: " . $token_from_form . " Session token: " . ($_SESSION['csrf_token'] ?? 'Not Set'));
    // Invalidar o token atual para forçar a regeneração e evitar replay attacks com o token antigo da sessão
    unset($_SESSION['csrf_token']);
    return false;
}

// Gera o token uma vez por carregamento de config, se não existir, ou se foi invalidado.
generateCsrfToken();
?>