<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado e é profissional
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'profissional') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'adicionar') {
            $nome = $_POST['nome'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $preco_base = $_POST['preco_base'] ?? null;
            $preco_tipo = $_POST['preco_tipo'] ?? 'fixo';
            
            if (!empty($nome)) {
                $query = "INSERT INTO servicos (nome, descricao, preco_base, preco_tipo) VALUES (:nome, :descricao, :preco_base, :preco_tipo)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':descricao', $descricao);
                $stmt->bindParam(':preco_base', $preco_base);
                $stmt->bindParam(':preco_tipo', $preco_tipo);
                
                if ($stmt->execute()) {
                    $success = 'Serviço adicionado com sucesso!';
                } else {
                    $error = 'Erro ao adicionar serviço.';
                }
            } else {
                $error = 'Por favor, preencha o nome do serviço.';
            }
        } elseif ($_POST['action'] === 'excluir') {
            $servico_id = $_POST['servico_id'];
            
            $query = "DELETE FROM servicos WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $servico_id);
            
            if ($stmt->execute()) {
                $success = 'Serviço excluído com sucesso!';
            } else {
                $error = 'Erro ao excluir serviço.';
            }
        }
    }
}

// Buscar serviços
$query = "SELECT * FROM servicos ORDER BY nome ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Serviços - Sistema de Atendimentos</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="navbar">
            <h2>Gerenciar Serviços</h2>
            <div>
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Sair</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Adicionar Novo Serviço</h3>
            <form method="POST">
                <input type="hidden" name="action" value="adicionar">
                
                <div class="form-group">
                    <label for="nome">Nome do Serviço *</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="preco_tipo">Tipo de Preço *</label>
                    <select id="preco_tipo" name="preco_tipo" required>
                        <option value="fixo">Preço Fixo</option>
                        <option value="a_combinar">A Combinar</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="preco_base">Preço Base (R$)</label>
                    <input type="number" id="preco_base" name="preco_base" step="0.01">
                </div>
                
                <button type="submit">Adicionar Serviço</button>
            </form>
        </div>
        
        <h2>Serviços Cadastrados</h2>
        
        <?php if (count($servicos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Tipo de Preço</th>
                        <th>Preço Base</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicos as $servico): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($servico['nome']); ?></td>
                            <td><?php echo htmlspecialchars($servico['descricao']); ?></td>
                            <td><?php echo $servico['preco_tipo'] === 'fixo' ? 'Preço Fixo' : 'A Combinar'; ?></td>
                            <td>
                                <?php 
                                if ($servico['preco_base']) {
                                    echo 'R$ ' . number_format($servico['preco_base'], 2, ',', '.');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="excluir">
                                    <input type="hidden" name="servico_id" value="<?php echo $servico['id']; ?>">
                                    <button type="submit" class="btn-small btn-danger" onclick="return confirm('Tem certeza que deseja excluir este serviço?')">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="card">
                <p>Nenhum serviço cadastrado.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

