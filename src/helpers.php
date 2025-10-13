<?php

/**
 * Calcula os horários disponíveis para agendamento
 * 
 * @param PDO $db Conexão com o banco de dados
 * @param string $data Data no formato Y-m-d
 * @param int $duracao_servico Duração do serviço em horas (padrão: 2)
 * @return array Lista de horários disponíveis
 */
function calcularHorariosDisponiveis($db, $data, $duracao_servico = 2) {
    // Horário de trabalho: 8h às 18h
    $hora_inicio = 8;
    $hora_fim = 18;
    $intervalo = 1; // Intervalos de 1 hora
    
    $horarios_disponiveis = [];
    
    // Gerar todos os horários possíveis
    for ($hora = $hora_inicio; $hora < $hora_fim; $hora += $intervalo) {
        $horario_inicio = sprintf("%s %02d:00:00", $data, $hora);
        $horario_fim = date('Y-m-d H:i:s', strtotime($horario_inicio . ' +' . $duracao_servico . ' hours'));
        
        // Verificar se o horário está disponível
        $query = "SELECT COUNT(*) as count FROM agendamentos 
                  WHERE status = 'agendado' 
                  AND ((data_hora_inicio <= :inicio AND data_hora_fim > :inicio) 
                  OR (data_hora_inicio < :fim AND data_hora_fim >= :fim)
                  OR (data_hora_inicio >= :inicio AND data_hora_fim <= :fim))";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':inicio', $horario_inicio);
        $stmt->bindParam(':fim', $horario_fim);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            $horarios_disponiveis[] = [
                'inicio' => $horario_inicio,
                'fim' => $horario_fim,
                'hora_display' => sprintf("%02d:00", $hora)
            ];
        }
    }
    
    return $horarios_disponiveis;
}

/**
 * Obtém os agendamentos de uma semana específica
 * 
 * @param PDO $db Conexão com o banco de dados
 * @param string $data_inicio Data de início da semana (Y-m-d)
 * @return array Lista de agendamentos da semana
 */
function obterAgendamentosSemana($db, $data_inicio) {
    $data_fim = date('Y-m-d', strtotime($data_inicio . ' +7 days'));
    
    $query = "SELECT a.*, u.nome as cliente_nome, u.telefone as cliente_telefone, 
              s.nome as servico_nome, s.preco_base, s.preco_tipo 
              FROM agendamentos a 
              JOIN usuarios u ON a.id_cliente = u.id 
              JOIN servicos s ON a.id_servico = s.id 
              WHERE DATE(a.data_hora_inicio) >= :data_inicio 
              AND DATE(a.data_hora_inicio) < :data_fim
              ORDER BY a.data_hora_inicio ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':data_inicio', $data_inicio);
    $stmt->bindParam(':data_fim', $data_fim);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Agrupa agendamentos por dia da semana
 * 
 * @param array $agendamentos Lista de agendamentos
 * @return array Agendamentos agrupados por dia
 */
function agruparAgendamentosPorDia($agendamentos) {
    $agrupados = [];
    
    foreach ($agendamentos as $agendamento) {
        $data = date('Y-m-d', strtotime($agendamento['data_hora_inicio']));
        $dia_semana = date('w', strtotime($agendamento['data_hora_inicio']));
        $nome_dia = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$dia_semana];
        
        if (!isset($agrupados[$data])) {
            $agrupados[$data] = [
                'data' => $data,
                'data_formatada' => date('d/m/Y', strtotime($data)),
                'dia_semana' => $nome_dia,
                'agendamentos' => []
            ];
        }
        
        $agrupados[$data]['agendamentos'][] = $agendamento;
    }
    
    return $agrupados;
}

?>

