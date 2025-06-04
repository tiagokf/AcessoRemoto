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

// 5. Escapar Dados para SQL (dbEscape deve usar a conexão Singleton)
$cliente_sql = dbEscape($cliente);
$tipo_acesso_sql = dbEscape($tipo_acesso);
$id_acesso_remoto_sql = dbEscape($id_acesso_remoto);
$senha_acesso_remoto_sql = dbEscape($senha_acesso_remoto);
$observacoes_sql = dbEscape($observacoes);

// 6. Lógica de Banco de Dados (INSERT ou UPDATE)
$id_usuario_logado = $_SESSION['user_id']; // Garantido por estaLogado()
$sql = "";
$is_update = false;

if ($id) {
    // UPDATE
    // Verificar se a conexão pertence ao usuário ou se o usuário é admin
    $checkSql = "SELECT id_usuario FROM conexoes WHERE id = {$id}";
    $resultCheck = dbQuery($checkSql);
    $conexaoExistente = dbFetchAssoc($resultCheck);

    if (!$conexaoExistente) {
        $response['message'] = 'Conexão não encontrada para atualização.';
        echo json_encode($response);
        exit;
    }

    // Permitir edição se for admin ou se for o dono da conexão
    // A função ehAdmin() deve estar definida e funcional.
    if (!ehAdmin() && $conexaoExistente['id_usuario'] != $id_usuario_logado) {
        $response['message'] = 'Você não tem permissão para editar esta conexão.';
        http_response_code(403); // Forbidden
        echo json_encode($response);
        exit;
    }

    $sql = "UPDATE conexoes SET
                cliente = '{$cliente_sql}',
                tipo_acesso_remoto = '{$tipo_acesso_sql}',
                id_acesso_remoto = '{$id_acesso_remoto_sql}',
                senha_acesso_remoto = '{$senha_acesso_remoto_sql}',
                observacoes = '{$observacoes_sql}'
            WHERE id = {$id}";
    $is_update = true;
} else {
    // INSERT
    $sql = "INSERT INTO conexoes (cliente, tipo_acesso_remoto, id_acesso_remoto, senha_acesso_remoto, observacoes, id_usuario, data_criacao)
            VALUES ('{$cliente_sql}', '{$tipo_acesso_sql}', '{$id_acesso_remoto_sql}', '{$senha_acesso_remoto_sql}', '{$observacoes_sql}', {$id_usuario_logado}, NOW())";
}

// 7. Executar Query e Retornar Resposta
if (dbQuery($sql)) {
    $response['success'] = true;
    if ($is_update) {
        $response['message'] = 'Conexão atualizada com sucesso!';
    } else {
        $response['message'] = 'Conexão criada com sucesso!';
        $response['new_id'] = dbInsertId(); // Enviar o novo ID para o cliente, se necessário
    }
} else {
    $response['message'] = 'Erro ao salvar conexão no banco de dados.';
    // Em ambiente de desenvolvimento, pode ser útil logar o erro SQL específico.
    // Ex: error_log("Erro SQL em salvar_conexao.php: Detalhes do erro aqui...");
}

echo json_encode($response);
exit;
?>
