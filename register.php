<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $tipo = 'cliente'; // Por padrão, novos usuários são clientes
    
    if (!empty($nome) && !empty($email) && !empty($senha)) {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar se o e-mail já existe
        $query = "SELECT id FROM usuarios WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = 'Este e-mail já está cadastrado.';
        } else {
            // Inserir novo usuário
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO usuarios (nome, email, senha, telefone, tipo) VALUES (:nome, :email, :senha, :telefone, :tipo)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha', $senha_hash);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':tipo', $tipo);
            
            if ($stmt->execute()) {
                $success = 'Cadastro realizado com sucesso! Você pode fazer login agora.';
            } else {
                $error = 'Erro ao realizar cadastro. Tente novamente.';
            }
        }
    } else {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de Atendimentos</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Cadastro</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nome">Nome Completo *</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha *</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit">Cadastrar</button>
        </form>
        
        <div class="link">
            Já tem uma conta? <a href="login.php">Faça login</a>
        </div>
    </div>
</body>
</html>

