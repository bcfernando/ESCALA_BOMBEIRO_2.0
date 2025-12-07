<?php
// ARQUIVO PRINCIPAL ATUALIZADO COM O CARD E O NOVO JS
session_start();
if (empty($_SESSION['usuario_id'])) {
    header('Location: /escala_bombeiros/login.php');
    exit;
}
if (($_SESSION['usuario_tipo'] ?? '') !== 'admin') {
    header('Location: /escala_bombeiros/portal_usuario.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funcoes.php';

// --- Lógica do Calendário ---
$mes_atual = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$ano_atual = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($mes_atual < 1 || $mes_atual > 12) $mes_atual = (int)date('m');
if ($ano_atual < 1970 || $ano_atual > 2100) $ano_atual = (int)date('Y');

$timestamp_primeiro_dia = mktime(0, 0, 0, $mes_atual, 1, $ano_atual);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
$nome_mes = ucfirst(strftime('%B', $timestamp_primeiro_dia));
$dias_no_mes = (int)date('t', $timestamp_primeiro_dia);
$dia_semana_primeiro = (int)date('w', $timestamp_primeiro_dia);

$mes_anterior = $mes_atual - 1;
$ano_anterior = $ano_atual;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $ano_anterior--;
}

$mes_proximo = $mes_atual + 1;
$ano_proximo = $ano_atual;
if ($mes_proximo > 12) {
    $mes_proximo = 1;
    $ano_proximo++;
}

$data_inicio_mes = sprintf('%04d-%02d-01', $ano_atual, $mes_atual);
$data_fim_mes    = sprintf('%04d-%02d-%02d', $ano_atual, $mes_atual, $dias_no_mes);

// Carrega plantões do mês
$plantoes_mes = [];
$fixos_servico_mes = [];
$vagas_dia_mes = [];

$sql_plantoes_mes = "
    SELECT p.id as plantao_id,
           p.bombeiro_id,
           p.data,
           p.turno,
           b.nome_completo,
           b.tipo
    FROM plantoes p
    JOIN bombeiros b ON p.bombeiro_id = b.id
    WHERE p.data BETWEEN ? AND ? AND b.ativo = 1
";

if ($stmt_plantoes = mysqli_prepare($conn, $sql_plantoes_mes)) {
    mysqli_stmt_bind_param($stmt_plantoes, "ss", $data_inicio_mes, $data_fim_mes);
    mysqli_stmt_execute($stmt_plantoes);
    $result_plantoes = mysqli_stmt_get_result($stmt_plantoes);
    while ($row = mysqli_fetch_assoc($result_plantoes)) {
        if (!isset($plantoes_mes[$row['data']])) {
            $plantoes_mes[$row['data']] = [];
        }
        $plantoes_mes[$row['data']][$row['bombeiro_id']] = $row;
    }
    mysqli_free_result($result_plantoes);
    mysqli_stmt_close($stmt_plantoes);
} else {
    echo "Erro ao buscar plantões: " . mysqli_error($conn);
}

// === Cálculo de vagas por dia (inclui I_SUB) ===
for ($dia = 1; $dia <= $dias_no_mes; $dia++) {
    $data_corrente = sprintf('%04d-%02d-%02d', $ano_atual, $mes_atual, $dia);

    // Fixo do dia
    $fixo_dia = get_fixo_de_servico($data_corrente, $conn);
    $fixos_servico_mes[$data_corrente] = $fixo_dia;

    // Vagas padrão de BC (1 por turno)
    $vagas_bc_d = 1;
    $vagas_bc_n = 1;

    // Detecta se o fixo é inválido (ausente ou com exceção) para liberar a vaga I_SUB
    $tem_excecao = false;
    if ($fixo_dia) {
        $tem_excecao = verificar_excecao_fixo((int)$fixo_dia['id'], $data_corrente, $conn);
    }
    $fixo_invalido = empty($fixo_dia) || $tem_excecao;

    // Vaga adicional: substituto integral do fixo (I_SUB) se não há fixo válido
    $vaga_fixo_integral = $fixo_invalido ? 1 : 0;

    // Desconta vagas conforme plantões já existentes
    if (isset($plantoes_mes[$data_corrente])) {
        foreach ($plantoes_mes[$data_corrente] as $plantao) {
            if ($plantao['tipo'] == 'BC') {
                if ($plantao['turno'] === 'D') {
                    $vagas_bc_d--;
                } elseif ($plantao['turno'] === 'N') {
                    $vagas_bc_n--;
                } elseif ($plantao['turno'] === 'I') {
                    $vagas_bc_d--;
                    $vagas_bc_n--;
                } elseif ($plantao['turno'] === 'I_SUB') {
                    $vaga_fixo_integral--;
                }
            }
        }
    }

    $vagas_dia_mes[$data_corrente] = [
        'D'     => max(0, $vagas_bc_d),
        'N'     => max(0, $vagas_bc_n),
        'I_SUB' => max(0, $vaga_fixo_integral),
    ];
}

// === Ordem sugerida + próximo / último ===
$ordem_mes_ids = get_ordem_escolha_ids($conn);

$primeiro_da_ordem_nome = '(Nenhum BC ativo)';
if (!empty($ordem_mes_ids)) {
    $primeiro_nome_temp = get_bombeiro_nome($ordem_mes_ids[0], $conn);
    if ($primeiro_nome_temp) {
        $primeiro_da_ordem_nome = $primeiro_nome_temp;
    }
}

$proximo_sugerido_id = get_proximo_a_escolher_id($conn);
$proximo_sugerido_nome = '(Nenhum)';
if ($proximo_sugerido_id) {
    $nome_temp = get_bombeiro_nome($proximo_sugerido_id, $conn);
    if ($nome_temp) {
        $proximo_sugerido_nome = $nome_temp;
    } else {
        // proximo_sugerido_id está inválido -> zera bc_da_vez
        set_config('bc_da_vez_id', null, $conn);
        $proximo_sugerido_id = null;
    }
}

// "Último que escolheu": primeiro tenta pegar do config salvo na API
$ultimo_que_escolheu_id = null;
$cfgUltimo = get_config('bc_ultimo_escolheu_id', $conn);
if ($cfgUltimo !== null && $cfgUltimo !== '') {
    $ultimo_que_escolheu_id = (int)$cfgUltimo;
}

// Se não houver nada salvo ainda, calcula pelo anterior ao próximo da vez
if (!$ultimo_que_escolheu_id && $proximo_sugerido_id && !empty($ordem_mes_ids)) {
    $idx = array_search($proximo_sugerido_id, $ordem_mes_ids, true);
    if ($idx !== false) {
        if ($idx === 0) {
            $ultimo_que_escolheu_id = end($ordem_mes_ids);
            reset($ordem_mes_ids);
        } else {
            $ultimo_que_escolheu_id = $ordem_mes_ids[$idx - 1];
        }
    }
}

// === Lista para o select "Começou no mês de..." ===
$bombeiros_inicio = [];
$sql_bombeiros_inicio = "
    SELECT id, nome_completo, tipo
    FROM bombeiros
    WHERE ativo = 1
    ORDER BY (tipo = 'Fixo') ASC, nome_completo ASC
";

if ($result_bi = mysqli_query($conn, $sql_bombeiros_inicio)) {
    while ($row = mysqli_fetch_assoc($result_bi)) {
        $bombeiros_inicio[] = $row;
    }
    mysqli_free_result($result_bi);
}

// início atual salvo na config (pode ser null)
$inicio_ordem_atual = (int) (get_config('bc_inicio_ordem_id', $conn) ?? 0);

// Nome do mês de referência (mês anterior ao da tela)
$timestamp_mes_ref       = mktime(0, 0, 0, $mes_anterior, 1, $ano_anterior);
$nome_mes_referencia     = ucfirst(strftime('%B', $timestamp_mes_ref));
$dias_semana             = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escala de Plantões - <?php echo htmlspecialchars($nome_mes) . ' ' . $ano_atual; ?></title>
    <link rel="stylesheet" href="css/style.css">

    <!-- =================================================================== -->
    <!-- KIT FAVICON COMPLETO -->
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="shortcut icon" href="favicon.ico">
    <!-- =================================================================== -->
</head>
<body>
    <div class="main-title-container">
        <img src="img/logo.png" alt="Logo do Sistema de Escala" class="main-logo">
        <h1>Escala de Plantões</h1>
    </div>

    <!-- Bloco de definição do início da ordem -->
    <div class="controles-escala inicio-ordem-wrapper">
        <label for="selectInicioOrdem">
            Começou no mês de
            <span class="mes-anterior-destaque">
                <?php echo htmlspecialchars($nome_mes_referencia, ENT_QUOTES, 'UTF-8'); ?>
            </span>
            com:
        </label>

        <div class="inicio-ordem-controls">
            <select id="selectInicioOrdem" class="inicio-ordem-select">
                <option value="">-- Selecione --</option>
                <?php foreach ($bombeiros_inicio as $b): ?>
                    <option value="<?= (int)$b['id'] ?>"
                        <?= ($b['id'] == $inicio_ordem_atual ? 'selected' : '') ?>>
                        <?= htmlspecialchars($b['nome_completo'], ENT_QUOTES, 'UTF-8') ?>
                        <?= ($b['tipo'] === 'Fixo') ? ' (Fixo)' : ' (BC)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- NOVO BLOCO: Card do próximo + Botão Pular + Reset -->
        <div class="proximo-bc-wrapper">
            <div id="proximo-bc-card" class="proximo-bc-card">
                Carregando próximo da ordem...
            </div>

            <button type="button" id="btn-pular-bc" class="btn btn-sm btn-secondary proximo-bc-btn">
                Não quer mais escolher (pular)
            </button>

            <button type="button" id="btn-reset-ordem" class="btn btn-sm btn-secondary proximo-bc-btn">
                Resetar ordem
            </button>
        </div>
        <!-- FIM DO NOVO BLOCO -->

    </div>

    <!-- ===== NOVA BARRA DE BOTÕES ===== -->
    <div class="barra-botoes">
        <!-- Botão Exportar -->
        <a href="exportar_tabela_formatada.php?month=<?php echo $mes_atual; ?>&year=<?php echo $ano_atual; ?>"
           class="button-link btn-secondary" target="_blank" title="Exportar escala em formato de tabela">
            <span class="turno-icon">📄</span> Exportar
        </a>

        <!-- Botão Gerenciar -->
        <a href="bombeiros.php" class="button-link btn-secondary" title="Adicionar ou editar bombeiros">
            <span class="turno-icon">⚙️</span> Gerenciar
        </a>

        <!-- Botão Tema (Dark Mode) -->
        <button id="theme-toggle" class="button-link btn-secondary" title="Alternar tema claro/escuro">
            🌗
        </button>
    </div>
    <!-- ================================ -->

    <div class="calendar-nav">
        <a href="?month=<?php echo $mes_anterior; ?>&year=<?php echo $ano_anterior; ?>" class="button-link">« Mês Anterior</a>
        <h2><?php echo htmlspecialchars($nome_mes) . ' ' . $ano_atual; ?></h2>
        <a href="?month=<?php echo $mes_proximo; ?>&year=<?php echo $ano_proximo; ?>" class="button-link">Próximo Mês »</a>
    </div>

    <table class="calendar">
        <thead>
            <tr>
                <?php foreach ($dias_semana as $dia_nome): ?>
                    <th><?php echo $dia_nome; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php
                // Espaços em branco antes do primeiro dia
                for ($i = 0; $i < $dia_semana_primeiro; $i++) {
                    echo '<td class="other-month"></td>';
                }
                $dia_atual_semana = $dia_semana_primeiro;

                for ($dia = 1; $dia <= $dias_no_mes; $dia++):
                    $data_corrente   = sprintf('%04d-%02d-%02d', $ano_atual, $mes_atual, $dia);
                    $fixo_do_dia     = $fixos_servico_mes[$data_corrente] ?? null;
                    $plantoes_do_dia = $plantoes_mes[$data_corrente] ?? [];
                    $vagas_do_dia    = $vagas_dia_mes[$data_corrente];

                    $is_weekend = ($dia_atual_semana == 0 || $dia_atual_semana == 6);

                    // Status geral considera vagas D/N de BC
                    $status_dot_class = ($vagas_do_dia['D'] > 0 || $vagas_do_dia['N'] > 0) ? 'green' : 'red';
                    $pode_integral    = ($vagas_do_dia['D'] > 0 && $vagas_do_dia['N'] > 0);
                ?>
                <td class="<?php
                  $classes = [];
                  if ($is_weekend) $classes[] = 'weekend';
                  if ($status_dot_class === 'red') $classes[] = 'marked-x'; // dia sem vagas -> X grande
                  echo implode(' ', $classes);
                ?>">

                    <span class="day-number"><?php echo $dia; ?></span>

                    <div class="cell-icons-top">
                        <button class="btn-detalhes"
                                data-date="<?php echo $data_corrente; ?>"
                                title="Ver detalhes e registrar plantão">
                            👁️
                        </button>
                        <span class="status-dot <?php echo $status_dot_class; ?>"
                              title="Status Vagas BC (Verde=Vagas, Vermelho=Lotado)"></span>

                        <div class="cell-availability-info">
                            <span class="availability-slot availability-D <?php echo ($vagas_do_dia['D'] > 0) ? 'disponivel' : 'lotado'; ?>"
                                  title="Vagas BC Diurnas: <?php echo $vagas_do_dia['D']; ?>">
                                <span class="turno-icon turno-D">☀️</span> <?php echo $vagas_do_dia['D']; ?>
                            </span>

                            <span class="availability-slot availability-N <?php echo ($vagas_do_dia['N'] > 0) ? 'disponivel' : 'lotado'; ?>"
                                  title="Vagas BC Noturnas: <?php echo $vagas_do_dia['N']; ?>">
                                <span class="turno-icon turno-N">★</span> <?php echo $vagas_do_dia['N']; ?>
                            </span>

                            <?php if (!empty($vagas_do_dia['I_SUB']) && $vagas_do_dia['I_SUB'] > 0): ?>
                                <span class="availability-slot availability-ISUB disponivel"
                                      title="Vaga Integral (Substituto do Fixo)">
                                    <span class="turno-icon turno-I">📅</span> SUB
                                </span>
                            <?php endif; ?>

                            <?php if ($pode_integral): ?>
                                <span class="availability-slot availability-I disponivel"
                                      title="Vaga Integral BC (24h) Disponível">
                                    <span class="turno-icon turno-I">📅</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="plantoes-do-dia">
                        <?php if ($fixo_do_dia): ?>
                            <span class="plantao-item fixo">
                                <?php echo htmlspecialchars(abreviar_nome($fixo_do_dia['nome_completo'], 30)); ?>
                                <?php echo get_turno_icon(null, true); ?>
                            </span>
                        <?php endif; ?>

                        <?php foreach ($plantoes_do_dia as $plantao): ?>
                            <span class="plantao-item bc">
                                <?php echo htmlspecialchars(abreviar_nome($plantao['nome_completo'], 30)); ?>
                                <?php echo get_turno_icon($plantao['turno']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </td>
                <?php
                    $dia_atual_semana++;
                    if ($dia_atual_semana > 6) {
                        echo '</tr><tr>';
                        $dia_atual_semana = 0;
                    }
                endfor;

                // completa a última linha com dias vazios
                while ($dia_atual_semana > 0 && $dia_atual_semana <= 6) {
                    echo '<td class="other-month"></td>';
                    $dia_atual_semana++;
                }
                ?>
            </tr>
        </tbody>
    </table>

    <!-- MODAL DE DETALHES -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 id="modalDate">Data do Plantão</h2>

            <h3>Ocupantes do Dia:</h3>
            <ul id="modalOcupantesList"><!-- preenchido via JS --></ul>

            <div id="modalSelecao" style="display:none;">
                <h3>Registrar Novo Plantão</h3>
                <p id="modalSugestao">Sugestão: ...</p>

                <select id="modalSelectBombeiro">
                    <option value="">-- Selecione um Bombeiro --</option>
                </select>

                <div class="modal-buttons">
                    <button id="modalBtnD" disabled>
                        ☀️ Diurno <span class="vagas"></span>
                    </button>
                    <button id="modalBtnN" disabled>
                        ★ Noturno <span class="vagas"></span>
                    </button>
                    <button id="modalBtnI" disabled>
                        📅 Integral <span class="vagas"></span>
                    </button>
                    <button id="modalBtnISUB" disabled>
                        📅 Integral (Substituto) <span class="vagas"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <!-- MODAL DE CONFIRMAÇÃO -->
    <div id="confirm-modal" class="confirm-modal-overlay" style="display: none;">
        <div class="confirm-modal-box">
            <p id="confirm-modal-text">Você tem certeza?</p>
            <div class="confirm-modal-buttons">
                <button id="confirm-modal-btn-yes" class="btn-confirm-yes">Sim</button>
                <button id="confirm-modal-btn-no" class="btn-confirm-no">Não</button>
            </div>
        </div>
    </div>

    <footer>
        <p>Sistema de Escala de Plantões - Desenvolvido por Luiz Fernando Hohn</p>
    </footer>

    <!-- Expor infos importantes para o JS -->
    <script>
        // Último que escolheu (para destacar de verde)
        window.ULTIMO_ESCOLHEU_ID = <?php
            echo json_encode($ultimo_que_escolheu_id ? (int)$ultimo_que_escolheu_id : null);
        ?>;

        // Próximo da vez (para seleção automática no modal)
        window.PROXIMO_GLOBAL_ID = <?php
            echo json_encode($proximo_sugerido_id ? (int)$proximo_sugerido_id : null);
        ?>;

        window.PROXIMO_GLOBAL_NOME = <?php
            echo json_encode($proximo_sugerido_nome);
        ?>;
    </script>

    <script src="js/script.js"></script>
    <script src="js/notificacoes.js"></script>
    <script src="js/ordem_escala.js?v=<?= time() ?>"></script>
    <script src="js/notificacoes.js?v=<?= time() ?>"></script>

</body>
</html>
