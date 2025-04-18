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

// Funções de autenticação - wrapper para compatibilidade com os nomes das funções
function isLoggedIn() {
    return estaLogado();
}

function requireLogin() {
    if (!estaLogado()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php?error=acesso_negado');
        exit;
    }
}

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
        echo '<div class="ui ' . $alert['type'] . ' message">';
        echo $alert['message'];
        echo '</div>';
        unset($_SESSION['alert']);
    }
}
?>