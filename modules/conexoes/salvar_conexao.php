<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
// auth.php é incluído por config.php, que define exigirLogin, estaLogado, ehAdmin, etc.

// Iniciar sessão se não estiver iniciada para acessar $_SESSION (CSRF token, user_id, etc.)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Erro desconhecido ao processar a solicitação.'];

// 1. Validar CSRF Token
// A função validateCsrfToken deve estar definida em config/config.php
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    $response['message'] = 'Falha na validação de segurança (CSRF). Ação bloqueada.';
    // Log para depuração interna, não exponha tokens no output
    error_log("CSRF validation failed for salvar_conexao.php. User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ". Submitted token: " . ($_POST['csrf_token'] ?? 'NOT_SENT'));
    echo json_encode($response);
    exit;
}

// 2. Exigir Login
// Modificado para retornar JSON em vez de redirecionar, pois é uma chamada AJAX.
if (!estaLogado()) {
    $response['message'] = 'Sessão expirada ou não autenticada. Por favor, faça login novamente.';
    $response['action'] = 'redirect_login'; // O frontend pode usar isso para redirecionar
    http_response_code(401); // Unauthorized
    echo json_encode($response);
    exit;
}

// 3. Receber Dados do POST
// Os nomes dos campos em $_POST devem corresponder aos atributos 'name' dos inputs no formulário HTML do modal.
// O JavaScript usa $('#form-conexao').serialize(), que pega os names dos inputs.
// No modal (listar.php), os names são: id, cliente, tipo_acesso, id_acesso, senha_acesso, observacoes.
$id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : null;
$cliente = trim($_POST['cliente'] ?? '');
$tipo_acesso = trim($_POST['tipo_acesso'] ?? '');
$id_acesso_remoto = trim($_POST['id_acesso'] ?? '');
$senha_acesso_remoto = trim($_POST['senha_acesso'] ?? ''); // Não hashear, armazenar como texto por enquanto conforme estrutura.
$observacoes = trim($_POST['observacoes'] ?? '');

// 4. Validar Dados Obrigatórios
if (empty($cliente) || empty($tipo_acesso) || empty($id_acesso_remoto)) {
    $response['message'] = 'Os campos Cliente, Tipo de Acesso e ID de Acesso são obrigatórios.';
    echo json_encode($response);
    exit;
}

// 5. Validação Adicional
// 5.1. Buscar tipos de acesso válidos do banco
$sqlTipos = "SELECT DISTINCT tipo_acesso_remoto FROM conexoes";
$resultTipos = dbQueryPrepared($sqlTipos, [], ""); // Usar dbQueryPrepared para consistência
$validAccessTypes = [];
if ($resultTipos) {
    while ($row = dbFetchAssoc($resultTipos)) {
        if (!empty($row['tipo_acesso_remoto'])) {
            $validAccessTypes[] = $row['tipo_acesso_remoto'];
        }
    }
}
if (!in_array('RustDesk', $validAccessTypes)) {
    $validAccessTypes[] = 'RustDesk'; // Garantir que RustDesk seja uma opção
}
// Adicionar outros tipos padrão se necessário, ex: AnyDesk, TeamViewer, etc.
// Se a lista do DB for a autoridade máxima, não adicionar manualmente outros além de RustDesk (se for um default fixo)

if (!in_array($tipo_acesso, $validAccessTypes)) {
    $response['message'] = 'Tipo de Acesso inválido selecionado. Valores válidos: ' . implode(', ', $validAccessTypes);
    echo json_encode($response);
    exit;
}

// 5.2. Validação de Comprimento
$errors = [];
if (mb_strlen($cliente) > 255) {
    $errors[] = 'O nome do Cliente não pode exceder 255 caracteres.';
}
if (mb_strlen($id_acesso_remoto) > 255) {
    $errors[] = 'O ID de Acesso Remoto não pode exceder 255 caracteres.';
}
if (mb_strlen($senha_acesso_remoto) > 255) {
    $errors[] = 'A Senha de Acesso Remoto não pode exceder 255 caracteres.';
}
if (mb_strlen($observacoes) > 1000) {
    $errors[] = 'As Observações não podem exceder 1000 caracteres.';
}

if (!empty($errors)) {
    $response['message'] = implode(' ', $errors); // Concatenar erros ou retornar o primeiro
    echo json_encode($response);
    exit;
}

// Não é mais necessário dbEscape aqui, pois usaremos prepared statements.

// 6. Lógica de Banco de Dados (INSERT ou UPDATE)
$id_usuario_logado = $_SESSION['user_id']; // Garantido por estaLogado()
$sql = "";
$params = [];
$types = "";
$is_update = false;

if ($id) {
    // UPDATE
    // Verificar se a conexão pertence ao usuário ou se o usuário é admin
    // Para esta verificação, podemos usar dbQueryPrepared também para consistência, ou manter dbQuery se preferir para queries simples sem input direto do usuário (além do ID)
    $checkSql = "SELECT id_usuario FROM conexoes WHERE id = ?";
    $resultCheck = dbQueryPrepared($checkSql, [$id], "i");
    $conexaoExistente = dbFetchAssoc($resultCheck);

    if (!$conexaoExistente) {
        $response['message'] = 'Conexão não encontrada para atualização.';
        echo json_encode($response);
        exit;
    }

    if (!ehAdmin() && $conexaoExistente['id_usuario'] != $id_usuario_logado) {
        $response['message'] = 'Você não tem permissão para editar esta conexão.';
        http_response_code(403); // Forbidden
        echo json_encode($response);
        exit;
    }

    $sql = "UPDATE conexoes SET
                cliente = ?,
                tipo_acesso_remoto = ?,
                id_acesso_remoto = ?,
                senha_acesso_remoto = ?,
                observacoes = ?
            WHERE id = ?";
    $params = [$cliente, $tipo_acesso, $id_acesso_remoto, $senha_acesso_remoto, $observacoes, $id];
    $types = "sssssi";
    $is_update = true;
} else {
    // INSERT
    $sql = "INSERT INTO conexoes (cliente, tipo_acesso_remoto, id_acesso_remoto, senha_acesso_remoto, observacoes, id_usuario, data_criacao)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $params = [$cliente, $tipo_acesso, $id_acesso_remoto, $senha_acesso_remoto, $observacoes, $id_usuario_logado];
    $types = "sssssi";
}

// 7. Executar Query e Retornar Resposta
$main_query_executed = false;
if ($id) { // Se é uma tentativa de UPDATE
    if ($resultCheck === false) { // A verificação da conexão existente falhou
        $response['success'] = false;
        $response['message'] = 'Ocorreu um erro ao verificar a conexão existente. Tente novamente.';
    } elseif (!$conexaoExistente) { // A conexão não foi encontrada (já tratado antes, mas como fallback)
        $response['success'] = false;
        $response['message'] = 'Conexão não encontrada para atualização.';
    } elseif (!ehAdmin() && $conexaoExistente['id_usuario'] != $id_usuario_logado) { // Permissão (já tratado antes)
        $response['success'] = false;
        $response['message'] = 'Você não tem permissão para editar esta conexão.';
        http_response_code(403);
    } else {
        // Prossiga com a query de UPDATE
        $result = dbQueryPrepared($sql, $params, $types);
        $main_query_executed = true;
    }
} else { // É uma tentativa de INSERT
    $result = dbQueryPrepared($sql, $params, $types);
    $main_query_executed = true;
}

if ($main_query_executed) {
    if ($result !== false) {
        $response['success'] = true;
        if ($is_update) {
            $response['message'] = 'Conexão atualizada com sucesso!';
        } else {
            $new_id = dbInsertId();
            $response['message'] = 'Conexão criada com sucesso!';
            if ($new_id > 0) {
                $response['new_id'] = $new_id;
            }
        }
    } else { // $result é false, a query principal (INSERT/UPDATE) falhou
        $response['success'] = false;
        $response['message'] = 'Ocorreu um erro ao salvar a conexão. Tente novamente.';
    }
}
// Se $main_query_executed for false, a resposta já foi definida pelo bloco do if($id)


echo json_encode($response);
exit;
?>
