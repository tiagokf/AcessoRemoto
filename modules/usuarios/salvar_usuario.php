<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Erro desconhecido.'];

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token Validation (assuming token is passed as 'csrf_token' in POST)
// if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
//     $response['message'] = 'Falha na validação de segurança (CSRF).';
//     echo json_encode($response);
//     exit;
// }
// For now, CSRF token is not sent by the current modal JS. This should be added.
// Temporarily bypassing for development of other logic.

if (!ehAdmin()) {
    $response['message'] = 'Acesso negado. Somente administradores podem adicionar usuários.';
    http_response_code(403); // Forbidden
    echo json_encode($response);
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? ''; // No trim for password before length validation
$nivel_acesso = trim($_POST['nivel_acesso'] ?? '');

// Validation
$errors = [];
if (empty($nome)) {
    $errors[] = "O campo Nome é obrigatório.";
} elseif (mb_strlen($nome) > 255) {
    $errors[] = "O Nome não pode exceder 255 caracteres.";
}

if (empty($email)) {
    $errors[] = "O campo E-mail é obrigatório.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Formato de E-mail inválido.";
} elseif (mb_strlen($email) > 255) {
    $errors[] = "O E-mail não pode exceder 255 caracteres.";
} else {
    // Check email uniqueness
    $sql_check_email = "SELECT id FROM usuarios WHERE email = ?";
    $result_check_email = dbQueryPrepared($sql_check_email, [$email], "s");
    if ($result_check_email && dbFetchAssoc($result_check_email)) {
        $errors[] = "Este E-mail já está em uso.";
    }
}

if (empty($senha)) {
    $errors[] = "O campo Senha é obrigatório.";
} elseif (mb_strlen($senha) < 6) {
    $errors[] = "A Senha deve ter pelo menos 6 caracteres.";
} elseif (mb_strlen($senha) > 255) { // Max length for password, if desired
    $errors[] = "A Senha não pode exceder 255 caracteres.";
}

if (empty($nivel_acesso)) {
    $errors[] = "O campo Nível de Acesso é obrigatório.";
} elseif (!in_array($nivel_acesso, ['user', 'admin'])) {
    $errors[] = "Nível de Acesso inválido. Deve ser 'user' ou 'admin'.";
}

if (!empty($errors)) {
    $response['message'] = implode(' ', $errors);
    // For AJAX calls in listar.php, the response.error is expected by current JS
    // Let's adapt to send response.message for consistency with other new handlers
    // or change JS to expect response.message. For now, stick to message.
    $response['error'] = implode(' ', $errors); // Keep this if JS expects .error
    echo json_encode($response);
    exit;
}

// Hash password
$senha_hash = gerarHash($senha);

// Insert user
$sql_insert = "INSERT INTO usuarios (nome, email, senha, nivel_acesso, data_criacao) VALUES (?, ?, ?, ?, NOW())";
$params_insert = [$nome, $email, $senha_hash, $nivel_acesso];
$types_insert = "ssss";

$result_insert = dbQueryPrepared($sql_insert, $params_insert, $types_insert);

if ($result_insert) {
    // Check if insert_id is available if needed, or affected_rows for mysqli
    // For now, if dbQueryPrepared doesn't return false, assume success.
    $response['success'] = true;
    $response['message'] = 'Usuário adicionado com sucesso!';
} else {
    $response['message'] = 'Erro ao salvar o usuário no banco de dados.';
    $response['error'] = 'Erro ao salvar o usuário no banco de dados.'; // Keep for JS if needed
    error_log("Failed to insert user: " . $email); // Log actual DB error if possible from dbQueryPrepared
}

echo json_encode($response);
?>
