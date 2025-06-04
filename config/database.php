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
            // Usar die() pode não ser ideal em produção, mas mantém o comportamento original.
            die("Falha na conexão: " . $conn_instance->connect_error);
        }

        // Definir charset para utf8
        $conn_instance->set_charset("utf8");
    }
    
    return $conn_instance;
}

// Função para executar consultas SQL
function dbQuery($sql) {
    $conn = dbConnect(); // Agora obtém a instância única
    $result = $conn->query($sql);
    
    if (!$result) {
        // Registrar o erro em logs seria melhor em produção
        error_log("Erro na consulta SQL: " . $conn->error . " - Query: " . $sql);
        die("Erro na consulta: " . $conn->error); // Mantém comportamento original de morrer, mas loga primeiro
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
    return $conn->real_escape_string($string);
}

// Função para obter o ID do último registro inserido
function dbInsertId() {
    $conn = dbConnect(); // Agora obtém a instância única
    return $conn->insert_id;
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