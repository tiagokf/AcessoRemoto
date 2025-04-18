<?php
// config/database.php
// Configurações do banco de dados

// Verifica se as constantes já estão definidas
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'root');
if (!defined('DB_NAME')) define('DB_NAME', 'remote_access_db');

// Estabelecer conexão com o banco de dados
function dbConnect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar conexão
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }
    
    // Definir charset para utf8
    $conn->set_charset("utf8");
    
    return $conn;
}

// Função para executar consultas SQL
function dbQuery($sql) {
    $conn = dbConnect();
    $result = $conn->query($sql);
    
    if (!$result) {
        die("Erro na consulta: " . $conn->error);
    }
    
    return $result;
}

// Função para obter um único registro
function dbFetchAssoc($result) {
    return $result->fetch_assoc();
}

// Função para obter todos os registros
function dbFetchAll($result) {
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

// Função para escapar strings (prevenir SQL Injection)
function dbEscape($string) {
    $conn = dbConnect();
    return $conn->real_escape_string($string);
}

// Função para obter o ID do último registro inserido
function dbInsertId() {
    $conn = dbConnect();
    return $conn->insert_id;
}

// Função para fechar a conexão
function dbClose($conn) {
    $conn->close();
}
?>