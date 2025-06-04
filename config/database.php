<?php
// config/database.php
// Configurações do banco de dados

// Verifica se as constantes já estão definidas
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'root');
if (!defined('DB_NAME')) define('DB_NAME', 'remote_access_db');

// Variável global para armazenar a instância da conexão
global $conn_instance;
$conn_instance = null;

// Estabelecer conexão com o banco de dados
function dbConnect() {
    global $conn_instance;

    // Verifica se a instância já existe e está ativa
    // A simples verificação de null é a mais comum para este padrão Singleton simples.
    // Adicionar $conn_instance->ping() pode ser excessivo e causar erro se $conn_instance for null.
    if ($conn_instance === null || !$conn_instance instanceof mysqli || $conn_instance->connect_errno) {
        $conn_instance = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Verificar conexão
        if ($conn_instance->connect_error) {
            error_log("Falha na conexão com o banco de dados: " . $conn_instance->connect_error);
            return null; // Retorna null em vez de die()
        }

        // Definir charset para utf8
        $conn_instance->set_charset("utf8");
    }
    
    return $conn_instance;
}

// Função para executar consultas SQL
function dbQuery($sql) {
    $conn = dbConnect(); // Agora obtém a instância única
    if ($conn === null) {
        error_log("Tentativa de query com conexão nula. SQL: " . $sql);
        return false;
    }
    $result = $conn->query($sql);
    
    if (!$result) {
        // Registrar o erro em logs
        error_log("Erro na consulta SQL: " . $conn->error . " - Query: " . $sql);
        return false; // Retorna false em vez de die()
    }
    
    return $result;
}

// Função para obter um único registro
function dbFetchAssoc($result) {
    // $result já é um objeto mysqli_result, não precisa de $conn aqui diretamente
    // Contudo, é importante que $result tenha vindo de uma query executada com a conexão única
    if ($result instanceof mysqli_result) {
        return $result->fetch_assoc();
    }
    return null; // Ou lançar um erro/warning
}

// Função para obter todos os registros
function dbFetchAll($result) {
    // Similar a dbFetchAssoc
    if ($result instanceof mysqli_result) {
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    return array(); // Ou lançar um erro/warning
}

// Função para escapar strings (prevenir SQL Injection)
function dbEscape($string) {
    $conn = dbConnect(); // Agora obtém a instância única
    if ($conn === null) {
        error_log("Tentativa de escape com conexão nula.");
        return $string; // Retorna a string original se não houver conexão
    }
    return $conn->real_escape_string($string);
}

// Função para obter o ID do último registro inserido
function dbInsertId() {
    $conn = dbConnect(); // Agora obtém a instância única
    if ($conn === null) {
        error_log("Tentativa de obter insert_id com conexão nula.");
        return 0; // Retorna 0 ou null se não houver conexão
    }
    return $conn->insert_id;
}

// Função para executar consultas SQL preparadas
function dbQueryPrepared($sql, $params, $types) {
    $conn = dbConnect();
    if ($conn === null) {
        error_log("Tentativa de query preparada com conexão nula. SQL: " . $sql);
        return false;
    }
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Erro ao preparar statement: " . $conn->error . " - Query: " . $sql);
        return false; // Retorna false em vez de die()
    }

    if (!empty($params) && !empty($types)) {
        if (!$stmt->bind_param($types, ...$params)) {
            error_log("Erro ao fazer bind_param: " . $stmt->error . " - Query: " . $sql);
            $stmt->close();
            return false;
        }
    }

    if (!$stmt->execute()) {
        error_log("Erro ao executar statement: " . $stmt->error . " - Query: " . $sql);
        $stmt->close();
        return false; // Retorna false em vez de die()
    }

    $result = $stmt->get_result();
    // $stmt->error pode não ser setado aqui se get_result() falhar por outros motivos,
    // mas execute() já teria retornado false.
    // Se get_result() em si falhar (e.g., para queries que não retornam result sets como INSERT),
    // $result será false. Para INSERT/UPDATE/DELETE, affected_rows é mais útil.
    // No entanto, para manter a consistência de retornar um "result set like" object ou true/false,
    // e dado que $stmt->error foi checado após execute(), podemos confiar no $result.
    // Se $result for false (ex: em INSERTs), mas não houve erro, é um comportamento esperado.
    // Se houve erro em execute(), já retornamos false.

    $stmt->close();
    return $result; // Pode ser um objeto mysqli_result ou false para DML/erro.
}

// Função para fechar a conexão
function dbClose() {
    global $conn_instance;
    if ($conn_instance !== null && $conn_instance instanceof mysqli && !$conn_instance->connect_errno) {
        $conn_instance->close();
        $conn_instance = null; // Permite recriar se necessário
    }
}

// Opcional: Registrar uma função para fechar a conexão ao final do script
// register_shutdown_function('dbClose');
?>