<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Falha na conexão com o banco.']);
    exit;
}

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

// garante que esse bombeiro existe e é ativo
$stmt = $conn->prepare("
    SELECT id 
    FROM bombeiros 
    WHERE id = ? AND ativo = 1 
    LIMIT 1
");
$stmt->bind_param('i', $bombeiroId);
$stmt->execute();
$res    = $stmt->get_result();
$existe = $res->fetch_assoc();
$stmt->close();

if (!$existe) {
    echo json_encode(['success' => false, 'message' => 'Bombeiro não encontrado ou inativo.']);
    exit;
}

// 🔴 AQUI ESTAVA O ERRO: passar int → precisa string
set_config('bc_inicio_ordem_id', (string)$bombeiroId, $conn);

// se quiser também resetar o ponteiro da ordem quando mudar o início, pode descomentar:
// set_config('bc_ultimo_escolheu_id', null, $conn);

echo json_encode([
    'success' => true,
    'message' => 'Início da ordem atualizado com sucesso.'
], JSON_UNESCAPED_UNICODE);
