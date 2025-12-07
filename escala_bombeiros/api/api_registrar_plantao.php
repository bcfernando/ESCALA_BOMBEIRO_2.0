<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Ajuste os caminhos conforme sua estrutura de pastas.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/escala_service.php';
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../services/regra_descanso.php'; // <<< NOVO

// Ativa exceptions automáticas para erros de MySQLi
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

/**
 * Envia uma resposta de erro em JSON e encerra o script.
 * Se receber a conexão, tenta dar rollback (ignorado se não houver transação ativa).
 */
function json_error(string $msg, int $httpCode = 400, ?mysqli $conn = null): void
{
    if ($conn instanceof mysqli) {
        @ $conn->rollback();
    }

    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $msg,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envia uma resposta de sucesso em JSON e encerra o script.
 */
function json_success(string $msg = 'OK', array $extra = [], int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode(array_merge([
        'success' => true,
        'message' => $msg,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------------------------------------------------------
// Validação básica de entrada
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método não permitido.', 405);
}

$bombeiroId = isset($_POST['bombeiro_id']) ? (int)$_POST['bombeiro_id'] : 0;
$data       = $_POST['data']   ?? '';
$turno      = $_POST['turno']  ?? '';

if ($bombeiroId <= 0 || $data === '' || $turno === '') {
    json_error('Parâmetros obrigatórios ausentes.');
}

// Valida formato de data (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    json_error('Data em formato inválido.');
}

$turno = strtoupper(trim($turno));
$turnosPermitidos = ['D', 'N', 'I', 'I_SUB'];
if (!in_array($turno, $turnosPermitidos, true)) {
    json_error('Turno inválido.');
}

// Conexão mysqli deve vir do db.php em $conn
if (!isset($conn) || !($conn instanceof mysqli)) {
    json_error('Falha de conexão com o banco de dados.', 500);
}

try {
    $conn->begin_transaction();

    // 1) Buscar dados do bombeiro
    $stmt = $conn->prepare('
        SELECT id, nome_completo, tipo, ativo
        FROM bombeiros
        WHERE id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $bombeiroId);
    $stmt->execute();
    $res      = $stmt->get_result();
    $bombeiro = $res->fetch_assoc();
    $stmt->close();

    if (!$bombeiro) {
        json_error('Bombeiro não encontrado.', 404, $conn);
    }

    if (isset($bombeiro['ativo']) && (int)$bombeiro['ativo'] === 0) {
        json_error('Bombeiro está inativo.', 400, $conn);
    }

    // 2) Verificar se já existe plantão desse bombeiro nesse dia/turno
    $stmt = $conn->prepare('
        SELECT id
        FROM plantoes
        WHERE bombeiro_id = ? AND data = ? AND turno = ?
        LIMIT 1
    ');
    $stmt->bind_param('iss', $bombeiroId, $data, $turno);
    $stmt->execute();
    $res      = $stmt->get_result();
    $jaExiste = $res->fetch_assoc();
    $stmt->close();

    if ($jaExiste) {
        json_error('Este bombeiro já possui plantão nesse dia/turno.', 409, $conn);
    }

    // 2.5) Regra de DESCANSO 24H (Integral ou 12+12)
    // Busca últimos plantões desse bombeiro (limitamos a alguns dias/linhas)
    $stmt = $conn->prepare('
        SELECT data, turno
        FROM plantoes
        WHERE bombeiro_id = ?
          AND data <= ?
        ORDER BY data DESC
        LIMIT 10
    ');
    $stmt->bind_param('is', $bombeiroId, $data);
    $stmt->execute();
    $resHistorico = $stmt->get_result();
    $historico    = [];
    while ($rowHist = $resHistorico->fetch_assoc()) {
        // garantimos o formato esperado pela RegraDescanso
        $historico[] = [
            'data'  => $rowHist['data'],
            'turno'=> strtoupper($rowHist['turno']),
        ];
    }
    $stmt->close();

    // Chama a regra de descanso
   $descanso = RegraDescanso::verificarDescanso($historico, $data, $turno);

if (empty($descanso['ok'])) {
    // se a regra negar, retorna 409 (conflito)
    $motivo = $descanso['motivo'] ?? 'Bombeiro precisa de descanso antes de novo plantão.';
    json_error($motivo, 409, $conn);
}


    // 3) Carregar estado do dia via serviço (fixo, contagens, capacidades)
    $service   = new EscalaService($conn);
    $estadoDia = $service->carregarEstadoDia($data);

    // 4) Validar regras de turno/capacidade usando o serviço
    $validacao = $service->validarTurnoECapacidade($estadoDia, $turno, $bombeiro);

    if (empty($validacao['ok'])) {
        $status  = isset($validacao['status']) ? (int)$validacao['status'] : 400;
        $message = $validacao['message'] ?? 'Não foi possível registrar o plantão.';
        json_error($message, $status, $conn);
    }

    // 5) Inserir plantão
    $stmt = $conn->prepare('
        INSERT INTO plantoes (bombeiro_id, data, turno)
        VALUES (?, ?, ?)
    ');
    $stmt->bind_param('iss', $bombeiroId, $data, $turno);
    $stmt->execute();
    $stmt->close();

    // 6) Atualiza quem foi o último BC que escolheu plantão
    set_config('bc_ultimo_escolheu_id', (string)$bombeiroId, $conn);

    $conn->commit();

    json_success('Plantão registrado com sucesso.');

} catch (Throwable $e) {
    if ($conn instanceof mysqli) {
        @ $conn->rollback();
    }

    // Loga no servidor
    error_log(
        'Erro registrar plantão: ' .
        $e->getMessage() . ' em ' .
        $e->getFile() . ':' . $e->getLine()
    );

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao registrar plantão: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
