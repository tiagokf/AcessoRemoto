<?php
// modules/conexoes/registrar_acesso.php
// Script para registrar acesso via AJAX

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário está logado
if (!estaLogado()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Verificar se os parâmetros foram enviados
if (!isset($_POST['id_conexao']) || !is_numeric($_POST['id_conexao'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

$id_conexao = intval($_POST['id_conexao']);
$detalhes = isset($_POST['detalhes']) ? dbEscape($_POST['detalhes']) : '';

// Registrar o acesso
$id_usuario = $_SESSION['user_id'];
$ip = $_SERVER['REMOTE_ADDR'];

$sql = "INSERT INTO acessos (id_conexao, id_usuario, ip_acesso, detalhes) 
        VALUES ($id_conexao, $id_usuario, '$ip', '$detalhes')";

if (dbQuery($sql)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Acesso registrado com sucesso']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar acesso']);
}
?>