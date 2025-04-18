-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL
);

-- Tabela de conexões (ajustada para incluir a foreign key)
CREATE TABLE IF NOT EXISTS conexoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente VARCHAR(255) NOT NULL,
    id_acesso_remoto VARCHAR(50),
    tipo_acesso_remoto VARCHAR(50),
    senha_acesso_remoto VARCHAR(255),
    observacoes TEXT,
    id_usuario INT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

-- Tabela de acessos
CREATE TABLE IF NOT EXISTS acessos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_conexao INT NOT NULL,
    id_usuario INT NOT NULL,
    data_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_acesso VARCHAR(45),
    detalhes TEXT,
    FOREIGN KEY (id_conexao) REFERENCES conexoes(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);