<?php
// modules/usuarios/buscar_usuario.php
// Endpoint para buscar dados de um usuário específico

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT id, nome, email, nivel_acesso FROM usuarios WHERE id = ?";
    $result = dbQueryPrepared($sql, [$id], "i");
    
    if ($result && $result->num_rows > 0) {
        $usuario = dbFetchAssoc($result);
        echo json_encode($usuario);
    } else {
        echo json_encode(['error' => 'Usuário não encontrado']);
    }
} else {
    echo json_encode(['error' => 'ID inválido']);
}
?> 