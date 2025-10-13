<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Buscar serviços disponíveis
$query = "SELECT * FROM servicos ORDER BY nome ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar agendamentos do cliente
$query = "SELECT a.*, s.nome as servico_nome, s.preco_base, s.preco_tipo 
          FROM agendamentos a 
          JOIN servicos s ON a.id_servico = s.id 
          WHERE a.id_cliente = :id_cliente 
          ORDER BY a.data_hora_inicio DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_cliente', $_SESSION['usuario_id']);
$stmt->execute();
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente - Sistema de Atendimentos</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="navbar">
            <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h2>
            <div>
                <a href="logout.php">Sair</a>
            </div>
        </div>
        
        <h2>Serviços Disponíveis</h2>
        
        <div class="services-grid">
            <?php foreach ($servicos as $servico): ?>
                <div class="service-card" onclick="window.location.href='agendar.php?servico_id=<?php echo $servico['id']; ?>'">
                    <h4><?php echo htmlspecialchars($servico['nome']); ?></h4>
                    <p><?php echo htmlspecialchars($servico['descricao']); ?></p>
                    <div class="price">
                        <?php 
                        if ($servico['preco_tipo'] === 'fixo') {
                            echo 'R$ ' . number_format($servico['preco_base'], 2, ',', '.');
                        } else {
                            echo 'A Combinar';
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <h2>Meus Agendamentos</h2>
        
        <?php if (count($agendamentos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Serviço</th>
                        <th>Preço</th>
                        <th>Valor Final</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendamentos as $agendamento): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($agendamento['data_hora_inicio'])); ?></td>
                            <td><?php echo htmlspecialchars($agendamento['servico_nome']); ?></td>
                            <td>
                                <?php 
                                if ($agendamento['preco_tipo'] === 'fixo') {
                                    echo 'R$ ' . number_format($agendamento['preco_base'], 2, ',', '.');
                                } else {
                                    echo 'A Combinar';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($agendamento['valor_final']) {
                                    echo 'R$ ' . number_format($agendamento['valor_final'], 2, ',', '.');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $agendamento['status']; ?>">
                                    <?php echo ucfirst($agendamento['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="card">
                <p>Você ainda não possui agendamentos.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

