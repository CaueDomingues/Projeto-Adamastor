<?php
session_start();
require_once 'config/database.php';
require_once 'src/helpers.php';

// Verificar se o usuário está logado e é profissional
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'profissional') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'concluir') {
            $agendamento_id = $_POST['agendamento_id'];
            $valor_final = $_POST['valor_final'] ?? null;
            
            $query = "UPDATE agendamentos SET status = 'concluido', valor_final = :valor_final WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':valor_final', $valor_final);
            $stmt->bindParam(':id', $agendamento_id);
            $stmt->execute();
        } elseif ($_POST['action'] === 'cancelar') {
            $agendamento_id = $_POST['agendamento_id'];
            
            $query = "UPDATE agendamentos SET status = 'cancelado' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $agendamento_id);
            $stmt->execute();
        }
    }
}

// Obter a semana selecionada (padrão: semana atual)
$semana_offset = isset($_GET['semana']) ? intval($_GET['semana']) : 0;
$data_inicio_semana = date('Y-m-d', strtotime('monday this week +' . $semana_offset . ' weeks'));

// Buscar agendamentos da semana
$agendamentos = obterAgendamentosSemana($db, $data_inicio_semana);
$agendamentos_por_dia = agruparAgendamentosPorDia($agendamentos);

// Gerar todos os dias da semana (Segunda a Domingo)
$dias_semana = [];
for ($i = 0; $i < 7; $i++) {
    $data = date('Y-m-d', strtotime($data_inicio_semana . ' +' . $i . ' days'));
    $dias_semana[] = [
        'data' => $data,
        'data_formatada' => date('d/m/Y', strtotime($data)),
        'dia_semana' => ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][date('w', strtotime($data))],
        'agendamentos' => isset($agendamentos_por_dia[$data]) ? $agendamentos_por_dia[$data]['agendamentos'] : []
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Atendimentos</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .week-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 10px;
        }
        
        .week-navigation button {
            width: auto;
            padding: 10px 20px;
        }
        
        .week-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
        }
        
        .week-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .day-column {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 15px;
            min-height: 200px;
        }
        
        .day-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .day-header .day-name {
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .day-header .day-date {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .appointment-card {
            background: white;
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .appointment-time {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .appointment-info {
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .appointment-actions {
            margin-top: 10px;
            display: flex;
            gap: 5px;
        }
        
        .no-appointments {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: 600;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="navbar">
            <h2>Dashboard - <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></h2>
            <div>
                <a href="servicos.php">Gerenciar Serviços</a>
                <a href="logout.php">Sair</a>
            </div>
        </div>
        
        <h2>Agenda Semanal</h2>
        
        <div class="week-navigation">
            <a href="?semana=<?php echo $semana_offset - 1; ?>">
                <button type="button">← Semana Anterior</button>
            </a>
            <div class="week-title">
                Semana de <?php echo date('d/m/Y', strtotime($data_inicio_semana)); ?> 
                a <?php echo date('d/m/Y', strtotime($data_inicio_semana . ' +6 days')); ?>
            </div>
            <a href="?semana=<?php echo $semana_offset + 1; ?>">
                <button type="button">Próxima Semana →</button>
            </a>
        </div>
        
        <?php
        // Calcular estatísticas da semana
        $total_agendamentos = count($agendamentos);
        $agendados = count(array_filter($agendamentos, fn($a) => $a['status'] === 'agendado'));
        $concluidos = count(array_filter($agendamentos, fn($a) => $a['status'] === 'concluido'));
        $receita_total = array_sum(array_map(fn($a) => $a['valor_final'] ?? 0, $agendamentos));
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_agendamentos; ?></div>
                <div class="stat-label">Total de Atendimentos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $agendados; ?></div>
                <div class="stat-label">Agendados</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $concluidos; ?></div>
                <div class="stat-label">Concluídos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">R$ <?php echo number_format($receita_total, 2, ',', '.'); ?></div>
                <div class="stat-label">Receita da Semana</div>
            </div>
        </div>
        
        <div class="week-grid">
            <?php foreach ($dias_semana as $dia): ?>
                <div class="day-column">
                    <div class="day-header">
                        <div class="day-name"><?php echo $dia['dia_semana']; ?></div>
                        <div class="day-date"><?php echo $dia['data_formatada']; ?></div>
                    </div>
                    
                    <?php if (count($dia['agendamentos']) > 0): ?>
                        <?php foreach ($dia['agendamentos'] as $agendamento): ?>
                            <div class="appointment-card">
                                <div class="appointment-time">
                                    <?php echo date('H:i', strtotime($agendamento['data_hora_inicio'])); ?> - 
                                    <?php echo date('H:i', strtotime($agendamento['data_hora_fim'])); ?>
                                </div>
                                <div class="appointment-info">
                                    <strong><?php echo htmlspecialchars($agendamento['cliente_nome']); ?></strong>
                                </div>
                                <div class="appointment-info">
                                    📞 <?php echo htmlspecialchars($agendamento['cliente_telefone']); ?>
                                </div>
                                <div class="appointment-info">
                                    🔧 <?php echo htmlspecialchars($agendamento['servico_nome']); ?>
                                </div>
                                <div class="appointment-info">
                                    <span class="status-badge status-<?php echo $agendamento['status']; ?>">
                                        <?php echo ucfirst($agendamento['status']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($agendamento['status'] === 'agendado'): ?>
                                    <div class="appointment-actions">
                                        <form method="POST" style="display: inline; flex: 1;">
                                            <input type="hidden" name="action" value="concluir">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            <input type="number" name="valor_final" placeholder="Valor (R$)" step="0.01" style="width: 100%; margin-bottom: 5px; padding: 5px;">
                                            <button type="submit" class="btn-small btn-success" style="width: 100%;">Concluir</button>
                                        </form>
                                    </div>
                                <?php elseif ($agendamento['status'] === 'concluido' && $agendamento['valor_final']): ?>
                                    <div class="appointment-info" style="margin-top: 10px; color: #28a745; font-weight: 600;">
                                        💰 R$ <?php echo number_format($agendamento['valor_final'], 2, ',', '.'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-appointments">
                            Nenhum atendimento
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

