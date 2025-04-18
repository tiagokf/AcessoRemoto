<?php
// auth/logout.php
// Script para realizar logout do sistema

// Incluir arquivo de configuração
require_once '../config/config.php';

// Destruir a sessão
session_start();
$_SESSION = array();
session_destroy();

// Redirecionar para a página de login
header('Location: login.php');
exit;
?>