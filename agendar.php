<?php
session_start();
require_once 'config/database.php';
require_once 'src/helpers.php';

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Verificar se o serviço foi selecionado
if (!isset($_GET['servico_id'])) {
    header('Location: cliente.php');
    exit;
}

$servico_id = $_GET['servico_id'];

// Buscar informações do serviço
$query = "SELECT * FROM servicos WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $servico_id);
$stmt->execute();
$servico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    header('Location: cliente.php');
    exit;
}

// Processar o agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    $data_hora_inicio = $_POST['data_hora_inicio'] ?? '';
    $duracao = 2; // Duração padrão de 2 horas
    
    if (!empty($data_hora_inicio)) {
        // Calcular data/hora de fim
        $data_hora_fim = date('Y-m-d H:i:s', strtotime($data_hora_inicio . ' +' . $duracao . ' hours'));
        
        // Verificar se o horário está disponível
        $query = "SELECT COUNT(*) as count FROM agendamentos 
                  WHERE status = 'agendado' 
                  AND ((data_hora_inicio <= :inicio AND data_hora_fim > :inicio) 
                  OR (data_hora_inicio < :fim AND data_hora_fim >= :fim))";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':inicio', $data_hora_inicio);
        $stmt->bindParam(':fim', $data_hora_fim);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $error = 'Este horário não está disponível. Por favor, escolha outro horário.';
        } else {
            // Inserir agendamento
            $query = "INSERT INTO agendamentos (id_cliente, id_servico, data_hora_inicio, data_hora_fim, status) 
                      VALUES (:id_cliente, :id_servico, :data_hora_inicio, :data_hora_fim, 'agendado')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_cliente', $_SESSION['usuario_id']);
            $stmt->bindParam(':id_servico', $servico_id);
            $stmt->bindParam(':data_hora_inicio', $data_hora_inicio);
            $stmt->bindParam(':data_hora_fim', $data_hora_fim);
            
            if ($stmt->execute()) {
                $success = 'Agendamento realizado com sucesso!';
            } else {
                $error = 'Erro ao realizar agendamento. Tente novamente.';
            }
        }
    } else {
        $error = 'Por favor, selecione uma data e horário.';
    }
}

// Obter data selecionada ou usar a data atual
$data_selecionada = $_GET['data'] ?? date('Y-m-d');

// Calcular horários disponíveis para a data selecionada
$horarios_disponiveis = calcularHorariosDisponiveis($db, $data_selecionada, 2);

// Gerar próximos 14 dias para seleção
$dias_disponiveis = [];
for ($i = 0; $i < 14; $i++) {
    $data = date('Y-m-d', strtotime('+' . $i . ' days'));
    $dias_disponiveis[] = [
        'data' => $data,
        'data_formatada' => date('d/m/Y', strtotime($data)),
        'dia_semana' => ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][date('w', strtotime($data))]
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Serviço - Sistema de Atendimentos</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .days-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        
        .day-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .day-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .day-card.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .day-card .day-name {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .day-card .day-date {
            font-size: 14px;
        }
        
        .times-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        
        .time-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .time-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .time-card.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .no-times {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="navbar">
            <h2>Agendar Serviço</h2>
            <div>
                <a href="cliente.php">Voltar</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo htmlspecialchars($servico['nome']); ?></h3>
            <p><?php echo htmlspecialchars($servico['descricao']); ?></p>
            <p><strong>Preço: </strong>
                <?php 
                if ($servico['preco_tipo'] === 'fixo') {
                    echo 'R$ ' . number_format($servico['preco_base'], 2, ',', '.');
                } else {
                    echo 'A Combinar';
                }
                ?>
            </p>
            <p><strong>Duração: </strong>Aproximadamente 2 horas</p>
        </div>
        
        <h3>Selecione um Dia</h3>
        <div class="days-grid">
            <?php foreach ($dias_disponiveis as $dia): ?>
                <a href="?servico_id=<?php echo $servico_id; ?>&data=<?php echo $dia['data']; ?>" style="text-decoration: none;">
                    <div class="day-card <?php echo ($dia['data'] === $data_selecionada) ? 'selected' : ''; ?>">
                        <div class="day-name"><?php echo $dia['dia_semana']; ?></div>
                        <div class="day-date"><?php echo $dia['data_formatada']; ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <h3>Horários Disponíveis para <?php echo date('d/m/Y', strtotime($data_selecionada)); ?></h3>
        
        <?php if (count($horarios_disponiveis) > 0): ?>
            <form method="POST">
                <input type="hidden" name="confirmar" value="1">
                <div class="times-grid">
                    <?php foreach ($horarios_disponiveis as $horario): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="data_hora_inicio" value="<?php echo $horario['inicio']; ?>" style="display: none;" required>
                            <div class="time-card" onclick="this.parentElement.querySelector('input').checked = true; document.querySelectorAll('.time-card').forEach(c => c.classList.remove('selected')); this.classList.add('selected');">
                                <?php echo $horario['hora_display']; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit">Confirmar Agendamento</button>
            </form>
        <?php else: ?>
            <div class="no-times">
                <p>Não há horários disponíveis para este dia.</p>
                <p>Por favor, selecione outro dia.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

