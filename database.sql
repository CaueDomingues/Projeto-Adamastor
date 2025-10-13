CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    tipo VARCHAR(20) NOT NULL -- ENUM('profissional', 'cliente')
);

CREATE TABLE IF NOT EXISTS enderecos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_cliente INTEGER NOT NULL,
    rua VARCHAR(255),
    numero VARCHAR(50),
    complemento VARCHAR(255),
    bairro VARCHAR(255),
    cidade VARCHAR(255),
    estado VARCHAR(255),
    cep VARCHAR(20),
    ponto_referencia TEXT,
    FOREIGN KEY (id_cliente) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS servicos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    preco_base REAL,
    preco_tipo VARCHAR(20) NOT NULL -- ENUM('fixo', 'a_combinar')
);

CREATE TABLE IF NOT EXISTS agendamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_cliente INTEGER NOT NULL,
    id_servico INTEGER NOT NULL,
    data_hora_inicio DATETIME NOT NULL,
    data_hora_fim DATETIME NOT NULL,
    valor_final REAL,
    status VARCHAR(20) NOT NULL DEFAULT 'agendado', -- ENUM('agendado', 'concluido', 'cancelado')
    FOREIGN KEY (id_cliente) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_servico) REFERENCES servicos(id) ON DELETE CASCADE
);

-- Inserir um usuário profissional padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, telefone, tipo) VALUES 
('Adamastor Silva', 'adamastor@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(11) 98765-4321', 'profissional');

-- Inserir alguns serviços de exemplo
INSERT INTO servicos (nome, descricao, preco_base, preco_tipo) VALUES 
('Instalação de Prateleira', 'Instalação de prateleiras em diversos tamanhos', 80.00, 'fixo'),
('Reparo de Vazamento', 'Reparo de vazamentos em pias, torneiras e tubulações', 100.00, 'a_combinar'),
('Instalação de Chuveiro', 'Instalação completa de chuveiro elétrico', 120.00, 'fixo'),
('Troca de Torneira', 'Troca de torneiras de cozinha ou banheiro', 60.00, 'fixo'),
('Serviço Elétrico', 'Instalação e reparos elétricos diversos', 150.00, 'a_combinar');
