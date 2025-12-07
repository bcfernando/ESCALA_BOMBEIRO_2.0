<?php
// /escala_bombeiros/api/portal_events.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'erro'=>'unauthorized']); exit;
}

require_once __DIR__ . '/../includes/db.php';

$y = max(1970, min(2100, intval($_GET['year'] ?? date('Y'))));
$m = max(1, min(12, intval($_GET['month'] ?? date('n'))));
$ini = sprintf('%04d-%02d-01', $y, $m);
$fim = date('Y-m-t', strtotime($ini)); // último dia do mês

// pega quem é o bombeiro do usuário logado
$uid = intval($_SESSION['usuario_id']);
$me = $conn->prepare("SELECT bombeiro_id, nome FROM usuarios WHERE id=? LIMIT 1");
$me->bind_param("i", $uid);
$me->execute();
$meRes = $me->get_result()->fetch_assoc();
$me->close();

$me_bombeiro_id = intval($meRes['bombeiro_id'] ?? 0);

// eventos do mês
$sql = "
  SELECT 
    p.id          AS plantao_id,
    p.bombeiro_id,
    p.data,
    p.turno,           -- 'D','N','I','I_SUB'
    b.nome_completo
  FROM plantoes p
  JOIN bombeiros b ON b.id = p.bombeiro_id
  WHERE p.data BETWEEN ? AND ?
  ORDER BY p.data, FIELD(p.turno,'I','D','N','I_SUB'), b.nome_completo
";
$st = $conn->prepare($sql);
$st->bind_param("ss", $ini, $fim);
$st->execute();
$rs = $st->get_result();

$ev = [];
$sum = ['I'=>0,'D'=>0,'N'=>0,'I_SUB'=>0, 'horas'=>0, 'valor'=>0];

while($r = $rs->fetch_assoc()){
  // horas/valor simples
  $horas = ($r['turno']==='I' || $r['turno']==='I_SUB') ? 24 : 12;
  $valor = ($horas===24) ? 250 : 125;

  $sum[$r['turno']]++;
  $sum['horas'] += $horas;
  $sum['valor'] += $valor;

  $ev[] = [
    'data'  => $r['data'],
    'turno' => $r['turno'],
    'nome'  => $r['nome_completo'],
    'meu'   => ($me_bombeiro_id && intval($r['bombeiro_id']) === $me_bombeiro_id),
    'horas' => $horas,
    'valor' => $valor
  ];
}
$st->close();

echo json_encode([
  'ok'=>true,
  'mes'=>['year'=>$y,'month'=>$m,'ini'=>$ini,'fim'=>$fim],
  'resumo'=>[
    'integral'=>$sum['I'],
    'diurno'  =>$sum['D'],
    'noturno' =>$sum['N'],
    'integral_sub'=>$sum['I_SUB'],
    'horas'=>$sum['horas'],
    'valor'=>$sum['valor']
  ],
  'eventos'=>$ev
]);
