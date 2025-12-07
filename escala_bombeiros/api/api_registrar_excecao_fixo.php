<?php
// NÃO COLOQUE NADA ANTES DE <?php (nem espaços/linhas) — evita quebrar o JSON.
header('Content-Type: application/json; charset=utf-8');

// Nunca imprimir erros na resposta JSON:
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funcoes.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido.');
    }

    $bombeiro_id = isset($_POST['bombeiro_id']) ? (int)$_POST['bombeiro_id'] : 0;
    $data        = isset($_POST['data']) ? trim($_POST['data']) : '';

    if ($bombeiro_id <= 0 || !$data) {
        throw new Exception('Parâmetros ausentes.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        throw new Exception('Data em formato inválido.');
    }

    // Se você já tem helpers no funcoes.php, use-os:
    if (function_exists('registrar_excecao_fixo')) {
        $ok = registrar_excecao_fixo($bombeiro_id, $data, $conn); // deve retornar bool/array
        if (is_array($ok)) {
            echo json_encode($ok);
            exit;
        }
        if (!$ok) throw new Exception('Falha ao registrar exceção do fixo.');
    } else {
        // Implementação direta usando tabela `excecoes_fixo` (ajuste o nome, se diferente)
        $sql = "INSERT INTO excecoes_fixo (bombeiro_id, data) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) throw new Exception('Falha ao preparar a query.');
        mysqli_stmt_bind_param($stmt, "is", $bombeiro_id, $data);
        if (!mysqli_stmt_execute($stmt)) {
            // 1062 = duplicado
            if (mysqli_errno($conn) == 1062) {
                throw new Exception('Exceção já registrada para este fixo/data.');
            }
            throw new Exception('Erro ao inserir exceção do fixo.');
        }
        mysqli_stmt_close($stmt);
    }

    $response['success'] = true;
    $response['message'] = 'Exceção registrada com sucesso.';
    echo json_encode($response);
    exit;

} catch (Throwable $e) {
    error_log('api_registrar_excecao_fixo: ' . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    echo json_encode($response);
    exit;
}
