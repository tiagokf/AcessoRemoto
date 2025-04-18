<?php
/**
 * Script de Instalação do Sistema de Gerenciamento de Acessos Remotos
 * 
 * Este script deve ser executado apenas uma vez para configurar o sistema.
 * Ele criará as tabelas no banco de dados e o primeiro usuário administrador.
 */

// Definir constantes de configuração do banco de dados
define('DB_HOST', 'localhost'); // Altere conforme necessário
define('DB_USER', 'root');      // Altere conforme necessário
define('DB_PASS', 'root');          // Altere conforme necessário
define('DB_NAME', 'remote_access_db'); // Altere conforme necessário

// Verificar se a instalação já foi realizada
if (file_exists('install_lock.php')) {
    die('A instalação já foi realizada. Por segurança, remova este arquivo do servidor.');
}

// Função para conectar ao banco de dados
function dbConnect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        die("Falha na conexão ao banco de dados: " . $conn->connect_error);
    }
    
    return $conn;
}

// Função para criar o banco de dados
function createDatabase($conn) {
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "Banco de dados criado com sucesso.<br>";
    } else {
        die("Erro ao criar o banco de dados: " . $conn->error);
    }
    
    $conn->select_db(DB_NAME);
}

// Função para criar as tabelas
function createTables($conn) {
    // Tabela de usuários
    $sql = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        nivel_acesso ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario',
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ultimo_acesso TIMESTAMP NULL
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Tabela 'usuarios' criada com sucesso.<br>";
    } else {
        die("Erro ao criar tabela 'usuarios': " . $conn->error);
    }
    
    // Tabela de conexões
    $sql = "CREATE TABLE IF NOT EXISTS conexoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente VARCHAR(255) NOT NULL,
        id_acesso_remoto VARCHAR(50),
        tipo_acesso_remoto VARCHAR(50),
        senha_acesso_remoto VARCHAR(255),
        observacoes TEXT,
        id_usuario INT,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Tabela 'conexoes' criada com sucesso.<br>";
    } else {
        die("Erro ao criar tabela 'conexoes': " . $conn->error);
    }
    
    // Tabela de acessos
    $sql = "CREATE TABLE IF NOT EXISTS acessos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_conexao INT NOT NULL,
        id_usuario INT NOT NULL,
        data_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_acesso VARCHAR(45),
        detalhes TEXT,
        FOREIGN KEY (id_conexao) REFERENCES conexoes(id),
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Tabela 'acessos' criada com sucesso.<br>";
    } else {
        die("Erro ao criar tabela 'acessos': " . $conn->error);
    }
}

// Função para gerar hash de senha
function gerarHash($senha) {
    return password_hash($senha, PASSWORD_BCRYPT);
}

// Função para criar o primeiro usuário administrador
function createAdminUser($conn) {
    $nome = 'Tiago';
    $email = 'tiago@tiremoto.com.br';
    $senha = 'Sucesso';
    $senha_hash = gerarHash($senha);
    $nivel_acesso = 'admin';
    
    $sql = "INSERT INTO usuarios (nome, email, senha, nivel_acesso) 
            VALUES ('$nome', '$email', '$senha_hash', '$nivel_acesso')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Usuário administrador criado com sucesso.<br>";
        echo "Email: $email<br>";
        echo "Senha: $senha<br>";
        echo "<strong>IMPORTANTE: Altere a senha do administrador após o primeiro login!</strong><br>";
    } else {
        die("Erro ao criar usuário administrador: " . $conn->error);
    }
}

// Criar estrutura de diretórios
function createDirectories() {
    $directories = [
        'assets',
        'assets/css',
        'assets/js',
        'assets/images',
        'config',
        'includes',
        'modules',
        'modules/dashboard',
        'modules/usuarios',
        'modules/conexoes',
        'modules/acessos',
        'modules/relatorios',
        'auth'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "Diretório '$dir' criado com sucesso.<br>";
            } else {
                echo "Erro ao criar diretório '$dir'.<br>";
            }
        } else {
            echo "Diretório '$dir' já existe.<br>";
        }
    }
}

// Verificar requisitos
function checkRequirements() {
    echo "<h2>Verificando requisitos...</h2>";
    
    // Verificar versão do PHP
    echo "Versão do PHP: " . phpversion();
    if (version_compare(PHP_VERSION, '7.0.0', '<')) {
        echo " <span style='color:red'>(Requer PHP 7.0 ou superior)</span><br>";
    } else {
        echo " <span style='color:green'>(OK)</span><br>";
    }
    
    // Verificar extensão MySQLi
    echo "Extensão MySQLi: ";
    if (extension_loaded('mysqli')) {
        echo "<span style='color:green'>Instalada (OK)</span><br>";
    } else {
        echo "<span style='color:red'>Não instalada (Requerida)</span><br>";
    }
    
    // Verificar permissões de diretório
    echo "Permissões de escrita no diretório atual: ";
    if (is_writable('.')) {
        echo "<span style='color:green'>OK</span><br>";
    } else {
        echo "<span style='color:red'>Sem permissão (Requerida)</span><br>";
    }
    
    echo "<hr>";
}

// Criar arquivo de configuração
function createConfigFile() {
    $config_content = "<?php
// Configurações do banco de dados
define('DB_HOST', '" . DB_HOST . "');
define('DB_USER', '" . DB_USER . "');
define('DB_PASS', '" . DB_PASS . "');
define('DB_NAME', '" . DB_NAME . "');

// Configurações do site
define('SITE_NAME', 'Sistema de Acesso Remoto');
define('SITE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['PHP_SELF']));

// Iniciar sessão
session_start();
?>";
    
    if (file_put_contents('config/config.php', $config_content)) {
        echo "Arquivo de configuração 'config.php' criado com sucesso.<br>";
    } else {
        echo "Erro ao criar arquivo 'config.php'.<br>";
    }
}

// Função para criar arquivo de trava da instalação
function createLockFile() {
    $content = "<?php
// Este arquivo indica que a instalação foi concluída.
// Por segurança, remova o arquivo install.php após a instalação.
die('A instalação já foi realizada.');
?>";
    
    if (file_put_contents('install_lock.php', $content)) {
        echo "Arquivo de trava 'install_lock.php' criado com sucesso.<br>";
    } else {
        echo "Erro ao criar arquivo de trava 'install_lock.php'.<br>";
    }
}

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Processar a instalação
    echo "<div style='font-family:Arial,sans-serif; max-width:800px; margin:0 auto; padding:20px; border:1px solid #ddd; border-radius:5px;'>";
    echo "<h1>Instalação em Andamento...</h1>";
    
    // Verificar requisitos
    checkRequirements();
    
    echo "<h2>Criando estrutura...</h2>";
    
    // Criar diretórios
    createDirectories();
    
    echo "<h2>Configurando banco de dados...</h2>";
    
    // Conectar ao banco de dados
    $conn = dbConnect();
    
    // Criar banco de dados
    createDatabase($conn);
    
    // Criar tabelas
    createTables($conn);
    
    // Criar usuário administrador
    createAdminUser($conn);
    
    // Criar arquivo de configuração
    createConfigFile();
    
    // Criar arquivo de trava
    createLockFile();
    
    $conn->close();
    
    echo "<h2>Instalação concluída com sucesso!</h2>";
    echo "<p>O sistema foi instalado e configurado corretamente.</p>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Remova o arquivo 'install.php' do servidor.</li>";
    echo "<li>Faça login com as credenciais do administrador.</li>";
    echo "<li>Altere a senha do administrador.</li>";
    echo "<li>Configure o sistema de acordo com suas necessidades.</li>";
    echo "</ol>";
    
    echo "<p><a href='index.php' style='display:inline-block; padding:10px 20px; background:#2185d0; color:#fff; text-decoration:none; border-radius:4px;'>Ir para o Login</a></p>";
    echo "</div>";
} else {
    // Exibir formulário de instalação
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema de Gerenciamento de Acessos Remotos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2185d0;
            margin-bottom: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-warning {
            color: #8a6d3b;
            background-color: #fcf8e3;
            border-color: #faebcc;
        }
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2185d0;
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #1678c2;
        }
        hr {
            border: 0;
            border-top: 1px solid #eee;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Instalação - Sistema de Gerenciamento de Acessos Remotos</h1>
        
        <div class="alert alert-warning">
            <strong>Atenção!</strong> Este script irá configurar o sistema e criar o banco de dados. 
            Execute apenas uma vez e remova o arquivo após a instalação.
        </div>
        
        <div class="alert alert-info">
            <strong>Requisitos:</strong>
            <ul>
                <li>PHP 7.0 ou superior</li>
                <li>Extensão MySQLi habilitada</li>
                <li>Permissão de escrita no diretório atual</li>
            </ul>
        </div>
        
        <hr>
        
        <h2>Configuração do Banco de Dados</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="db_host">Host:</label>
                <input type="text" id="db_host" name="db_host" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label for="db_user">Usuário:</label>
                <input type="text" id="db_user" name="db_user" value="root" required>
            </div>
            
            <div class="form-group">
                <label for="db_pass">Senha:</label>
                <input type="password" id="db_pass" name="db_pass" value="root">
            </div>
            
            <div class="form-group">
                <label for="db_name">Nome do Banco de Dados:</label>
                <input type="text" id="db_name" name="db_name" value="remote_access_db" required>
            </div>
            
            <hr>
            
            <h2>Conta do Administrador</h2>
            
            <div class="form-group">
                <label for="admin_email">E-mail:</label>
                <input type="text" id="admin_email" name="admin_email" value="tiago@tiremoto.com.br" required>
            </div>
            
            <div class="form-group">
                <label for="admin_pass">Senha:</label>
                <input type="password" id="admin_pass" name="admin_pass" value="Sucesso" required>
                <small>Esta será a senha inicial. Altere-a após o primeiro login.</small>
            </div>
            
            <hr>
            
            <button type="submit" class="btn">Instalar Sistema</button>
        </form>
    </div>
</body>
</html>
<?php
}
?>