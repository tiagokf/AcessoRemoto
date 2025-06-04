<?php
// modules/dashboard/atualizar.php
// Script para atualizar dados do dashboard via AJAX

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../auth/auth.php';

// Verificar se o usuário está logado
if (!estaLogado()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Obter estatísticas atualizadas
// Total de conexões
$result = dbQuery("SELECT COUNT(*) as total FROM conexoes");
$total_conexoes = dbFetchAssoc($result)['total'];

// Total de acessos
$result = dbQuery("SELECT COUNT(*) as total FROM acessos");
$total_acessos = dbFetchAssoc($result)['total'];

// Acessos hoje
$result = dbQuery("SELECT COUNT(*) as total FROM acessos WHERE DATE(data_acesso) = CURDATE()");
$acessos_hoje = dbFetchAssoc($result)['total'];

// Total de usuários
$result = dbQuery("SELECT COUNT(*) as total FROM usuarios");
$total_usuarios = dbFetchAssoc($result)['total'];

// Preparar resposta JSON
$response = [
    'total_conexoes' => $total_conexoes,
    'total_acessos' => $total_acessos,
    'acessos_hoje' => $acessos_hoje,
    'total_usuarios' => $total_usuarios,
    'timestamp' => date('Y-m-d H:i:s')
];

// Enviar resposta
header('Content-Type: application/json');
echo json_encode($response);
?>