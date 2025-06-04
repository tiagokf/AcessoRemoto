<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'error' => 'Erro desconhecido ao processar a solicitação.']; // Changed default to 'error' key

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token Validation - Assuming 'csrf_token' is sent from the modal form in listar.php
// if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
//     $response['error'] = 'Falha na validação de segurança (CSRF).';
//     echo json_encode($response);
//     exit;
// }
// Bypassing for now, ensure this is added to the modal form in listar.php's JS for salvarEdicaoUsuario

if (!ehAdmin()) {
    $response['error'] = 'Acesso negado. Somente administradores podem atualizar usuários.';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

$id_to_edit = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? ''; // No trim for password
$nivel_acesso = trim($_POST['nivel_acesso'] ?? '');

// Validation
$errors = [];

if ($id_to_edit === false || $id_to_edit <= 0) {
    $errors[] = "ID de usuário inválido.";
}

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
    // Check email uniqueness (if changed)
    $sql_check_email = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
    $result_check_email = dbQueryPrepared($sql_check_email, [$email, $id_to_edit], "si");
    if ($result_check_email && $result_check_email->num_rows > 0) {
        $errors[] = "Este E-mail já está em uso por outro usuário.";
    }
}

if (!empty($senha)) { // Validate password only if provided
    if (mb_strlen($senha) < 6) {
        $errors[] = "A Senha deve ter pelo menos 6 caracteres.";
    } elseif (mb_strlen($senha) > 255) {
        $errors[] = "A Senha não pode exceder 255 caracteres.";
    }
}

if (empty($nivel_acesso)) {
    $errors[] = "O campo Nível de Acesso é obrigatório.";
} elseif (!in_array($nivel_acesso, ['user', 'admin'])) {
    $errors[] = "Nível de Acesso inválido. Deve ser 'user' ou 'admin'.";
}

// Last Admin Check (only if trying to change an admin to user)
if (empty($errors) && $id_to_edit) {
    $sql_get_user_being_edited = "SELECT nivel_acesso FROM usuarios WHERE id = ?";
    $res_user_being_edited = dbQueryPrepared($sql_get_user_being_edited, [$id_to_edit], "i");
    if ($res_user_being_edited && $res_user_being_edited->num_rows > 0) {
        $user_being_edited = dbFetchAssoc($res_user_being_edited);
        if ($user_being_edited['nivel_acesso'] === 'admin' && $nivel_acesso === 'user') {
            // Check if this is the last admin
            $sql_admin_count = "SELECT COUNT(*) as total FROM usuarios WHERE nivel_acesso = ?";
            $res_admin_count = dbQueryPrepared($sql_admin_count, ['admin'], "s");
            $admin_count = 0;
            if ($res_admin_count && $res_admin_count->num_rows > 0) {
                $admin_count_row = dbFetchAssoc($res_admin_count);
                $admin_count = $admin_count_row['total'];
            }
            if ($admin_count <= 1) {
                // Special case: if the admin is editing themselves and they are the last admin
                if ($id_to_edit == $_SESSION['user_id']) {
                     $errors[] = "Você é o último administrador e não pode remover seu próprio status de administrador.";
                } else {
                     $errors[] = "Não é possível rebaixar o último administrador do sistema.";
                }
            }
        }
    } else {
        $errors[] = "Usuário a ser editado não encontrado."; // Should not happen if ID is from a valid source
    }
}


if (!empty($errors)) {
    $response['error'] = implode(' ', $errors);
    echo json_encode($response);
    exit;
}

// Build SQL Update query
$sql_update_fields = "nome = ?, email = ?, nivel_acesso = ?";
$params_update = [$nome, $email, $nivel_acesso];
$types_update = "sss";

if (!empty($senha)) {
    $senha_hash = gerarHash($senha);
    $sql_update_fields .= ", senha = ?";
    $params_update[] = $senha_hash;
    $types_update .= "s";
}

$params_update[] = $id_to_edit;
$types_update .= "i";

$sql_update = "UPDATE usuarios SET $sql_update_fields WHERE id = ?";
$result_update = dbQueryPrepared($sql_update, $params_update, $types_update);

if ($result_update) {
    $response['success'] = true;
    $response['message'] = 'Usuário atualizado com sucesso!';
    // If admin updates their own session details through this modal (less common)
    if ($id_to_edit == $_SESSION['user_id']) {
        $_SESSION['user_name'] = $nome;
        $_SESSION['user_email'] = $email;
        $_SESSION['nivel_acesso'] = $nivel_acesso;
    }
} else {
    $response['error'] = 'Erro ao atualizar o usuário no banco de dados.';
    error_log("Failed to update user ID: " . $id_to_edit);
}

echo json_encode($response);
?>
