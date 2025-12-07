<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funcoes.php';

$response = ['success' => false, 'message' => 'Data não fornecida.'];

if (isset($_GET['date'])) {
    $data_str = $_GET['date'];

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_str)) {
        $response['message'] = 'Formato de data inválido.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    try {
        // --- 1. Buscar Bombeiros de Plantão (Extras, ou seja, BCs) ---
        $extras = [];
        // ATENÇÃO: A query foi ajustada para buscar plantões de TODOS os tipos,
        // pois agora um Fixo pode cobrir outro Fixo, ou um BC pode cobrir um Fixo.
        // A lógica de contagem de vagas é que vai determinar a disponibilidade.
        $sql_extras = "SELECT p.id as plantao_id, p.turno, b.id as bombeiro_id, b.nome_completo, b.tipo
                       FROM plantoes p JOIN bombeiros b ON p.bombeiro_id = b.id
                       WHERE p.data = ?";
        $stmt_extras = mysqli_prepare($conn, $sql_extras);
        mysqli_stmt_bind_param($stmt_extras, "s", $data_str);
        mysqli_stmt_execute($stmt_extras);
        $result_extras = mysqli_stmt_get_result($stmt_extras);
        while ($row = mysqli_fetch_assoc($result_extras)) {
            $extras[] = $row;
        }
        mysqli_stmt_close($stmt_extras);

        // ===== INÍCIO DA SEÇÃO COM A LÓGICA DE VAGAS CORRIGIDA =====
        // --- 2. Calcular Vagas ---
        
        // A REGRA AGORA É:
        // Há 1 vaga para BC no turno Diurno.
        // Há 1 vaga para BC no turno Noturno.
        // A presença de um Fixo não impacta a vaga do BC.
        
       // ===== INÍCIO DA SEÇÃO COM A LÓGICA DE VAGAS (com I_SUB) =====

// 2. Calcular Vagas
$vagas_bc_d = 1;
$vagas_bc_n = 1;

// Fixo do dia (e exceção)
$fixo_calculado = get_fixo_de_servico($data_str, $conn);
if ($fixo_calculado) {
    $tem_excecao = verificar_excecao_fixo((int)$fixo_calculado['id'], $data_str, $conn);
    $fixo_calculado['tem_excecao'] = $tem_excecao;
}

// Sem fixo válido? Abre 1 vaga de Integral Substituto
$fixo_invalido = empty($fixo_calculado) || (!empty($fixo_calculado['tem_excecao']) && $fixo_calculado['tem_excecao'] === true);
$vaga_fixo_integral = $fixo_invalido ? 1 : 0;

// Descontar conforme extras já existentes
foreach ($extras as $plantao) {
    if ($plantao['tipo'] == 'BC') {
        if ($plantao['turno'] == 'D') $vagas_bc_d--;
        elseif ($plantao['turno'] == 'N') $vagas_bc_n--;
        elseif ($plantao['turno'] == 'I') { $vagas_bc_d--; $vagas_bc_n--; }
        elseif ($plantao['turno'] == 'I_SUB') { $vaga_fixo_integral--; } // NOVO
    }
}

// ===== FIM DA SEÇÃO COM A LÓGICA DE VAGAS (com I_SUB) =====

        // ===== FIM DA SEÇÃO COM A LÓGICA DE VAGAS CORRIGIDA =====

        // --- 3. Buscar todos os Bombeiros Ativos para o dropdown ---
        $bombeiros_ativos = [];
        $sql_ativos = "SELECT id, nome_completo, tipo FROM bombeiros WHERE ativo = 1 ORDER BY nome_completo ASC";
        $result_ativos = mysqli_query($conn, $sql_ativos);
        while ($row = mysqli_fetch_assoc($result_ativos)) {
            $bombeiros_ativos[] = $row;
        }

        // --- 4. Buscar o próximo sugerido na ordem ---
        $proximo_sugerido_id = get_proximo_a_escolher_id($conn);
        $proximo_sugerido = null;
        if ($proximo_sugerido_id) {
            $proximo_sugerido = [
                'id' => $proximo_sugerido_id,
                'nome' => get_bombeiro_nome($proximo_sugerido_id, $conn)
            ];
        }

        // --- 5. Montar a resposta final ---
        $response = [
            'success' => true,
            'fixo_calculado' => $fixo_calculado,
            'extras' => $extras, // Retorna os plantões extras (agora pode incluir Fixos em troca)
           'vagas' => [
    'D'     => max(0, $vagas_bc_d),
    'N'     => max(0, $vagas_bc_n),
    'I_SUB' => max(0, $vaga_fixo_integral),
],

            'bombeiros_ativos' => $bombeiros_ativos,
            'proximo_sugerido' => $proximo_sugerido
        ];

    } catch (Exception $e) {
        http_response_code(500);
        $response = ['success' => false, 'message' => 'Erro interno no servidor: ' . $e->getMessage()];
    }

} else {
    http_response_code(400); // Bad Request
}

echo json_encode($response);
?>