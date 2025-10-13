# Sistema de Gerenciamento de Atendimentos

Sistema web desenvolvido em PHP, HTML e CSS para gerenciamento de atendimentos de profissionais autônomos.

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache ou Nginx)

## Instalação

### 1. Configurar o Banco de Dados

Execute o script SQL para criar o banco de dados e as tabelas:

```bash
mysql -u root -p < database.sql
```

### 2. Configurar a Conexão com o Banco de Dados

Edite o arquivo `config/database.php` e ajuste as credenciais de acesso ao banco de dados:

```php
private $host = 'localhost';
private $db_name = 'sistema_atendimentos';
private $username = 'root';
private $password = '';
```

### 3. Configurar o Servidor Web

Aponte o servidor web para a pasta raiz do projeto.

**Apache:**
```apache
<VirtualHost *:80>
    DocumentRoot "/caminho/para/sistema_atendimentos"
    ServerName localhost
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name localhost;
    root /caminho/para/sistema_atendimentos;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### 4. Acessar o Sistema

Abra o navegador e acesse: `http://localhost`

## Credenciais Padrão

**Profissional:**
- E-mail: adamastor@email.com
- Senha: admin123

## Funcionalidades

### Área do Cliente
- Cadastro e login
- Visualização de serviços disponíveis
- Agendamento de serviços
- Visualização de agendamentos

### Área do Profissional
- Login
- Dashboard com lista de agendamentos
- Gerenciamento de serviços (CRUD)
- Marcação de atendimentos como concluídos
- Registro de valores finais

## Estrutura do Projeto

```
sistema_atendimentos/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   └── images/
├── config/
│   └── database.php
├── src/
│   ├── controllers/
│   ├── models/
│   └── views/
├── index.php
├── login.php
├── register.php
├── dashboard.php
├── cliente.php
├── agendar.php
├── servicos.php
├── logout.php
├── database.sql
└── README.md
```

## Tecnologias Utilizadas

- **PHP**: Linguagem de programação server-side
- **MySQL**: Sistema de gerenciamento de banco de dados
- **HTML5**: Estruturação das páginas
- **CSS3**: Estilização e design responsivo
- **PDO**: Interface para acesso ao banco de dados

## Observações

- O sistema utiliza sessões PHP para autenticação
- As senhas são armazenadas com hash usando `password_hash()`
- O design é responsivo e funciona em dispositivos móveis

