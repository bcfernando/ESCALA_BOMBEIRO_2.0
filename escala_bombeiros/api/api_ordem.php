<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funcoes.php'; // para get_config/set_config

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Falha na conexão com o banco.']);
    exit;
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

$action = $_GET['action'] ?? '';

try {

    switch ($action) {

        // ---------------------------------------------------------------------
        // Retorna o próximo BC da ordem
        // ---------------------------------------------------------------------
        case 'next':
            // Pega último BC que escolheu (config)
            $ultimoIdStr = get_config('bc_ultimo_escolheu_id', $conn);
            $ultimoId    = $ultimoIdStr !== null ? (int)$ultimoIdStr : 0;

            // Lista de BCs ativos na ordem
            $sql = "
                SELECT oe.bombeiro_id
                FROM ordem_escolha oe
                INNER JOIN bombeiros b ON b.id = oe.bombeiro_id
                WHERE oe.ativo = 1
                ORDER BY oe.ordem ASC
            ";
            $res   = $conn->query($sql);
            $lista = [];
            while ($row = $res->fetch_assoc()) {
                $lista[] = (int)$row['bombeiro_id'];
            }

            if (!$lista) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Não há BCs ativos na ordem de escolha.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Se não tem último ou ele não está mais na fila → pega o primeiro
            if (!$ultimoId || !in_array($ultimoId, $lista, true)) {
                $proximoId = $lista[0];
            } else {
                $posAtual = array_search($ultimoId, $lista, true);
                $proxPos  = $posAtual + 1;
                if ($proxPos >= count($lista)) {
                    $proxPos = 0;
                }
                $proximoId = $lista[$proxPos];
            }

            // Busca dados do bombeiro
            $stmt = $conn->prepare("
                SELECT id, nome_completo, tipo
                FROM bombeiros
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->bind_param('i', $proximoId);
            $stmt->execute();
            $resBc    = $stmt->get_result();
            $bombeiro = $resBc->fetch_assoc();
            $stmt->close();

            if (!$bombeiro) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Bombeiro da ordem não encontrado.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            echo json_encode([
                'success'  => true,
                'bombeiro' => $bombeiro,
            ], JSON_UNESCAPED_UNICODE);
            exit;

        // ---------------------------------------------------------------------
        // Marca um BC como "não participa mais da ordem" (ativo = 0)
        // ---------------------------------------------------------------------
        case 'skip':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
                exit;
            }

            $bombeiroId = isset($_POST['bombeiro_id']) ? (int)$_POST['bombeiro_id'] : 0;
            if ($bombeiroId <= 0) {
                echo json_encode(['success' => false, 'message' => 'bombeiro_id inválido.']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE ordem_escolha SET ativo = 0 WHERE bombeiro_id = ?");
            $stmt->bind_param('i', $bombeiroId);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            exit;

        // ---------------------------------------------------------------------
        // RESET: volta todos para a ordem e zera o último que escolheu
        // ---------------------------------------------------------------------
        case 'reset':
            // só aceita POST pra evitar reset acidental por GET
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
                exit;
            }

            // volta todo mundo pra ativo = 1
            $conn->query("UPDATE ordem_escolha SET ativo = 1");

            // zera o último que escolheu (vai voltar pro início da ordem)
            set_config('bc_ultimo_escolheu_id', null, $conn);

            echo json_encode([
                'success' => true,
                'message' => 'Ordem reiniciada. Todos voltaram para a fila.'
            ], JSON_UNESCAPED_UNICODE);
            exit;

        // ---------------------------------------------------------------------
        // Ação padrão / inválida
        // ---------------------------------------------------------------------
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida.'], JSON_UNESCAPED_UNICODE);
            exit;
    }

} catch (Throwable $e) {
    error_log('Erro em api_ordem.php: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno na API de ordem: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
