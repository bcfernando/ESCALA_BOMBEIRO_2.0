<?php
declare(strict_types=1);

require_once __DIR__ . '/funcoes.php';
require_once __DIR__ . '/regra_escala.php';

/**
 * Camada de serviço para regras de escala.
 *
 * Objetivos:
 *  - Centralizar leitura do estado do dia (fixo, contagens de turnos).
 *  - Centralizar regras de capacidade/vagas por turno (reaproveitando o que já existe).
 *  - Deixar os arquivos da API (api_*.php) mais limpos.
 */
class EscalaService
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Carrega o "estado" bruto de um dia:
     *  - fixo do dia
     *  - se o fixo é válido (sem exceção)
     *  - contagem de plantoes por turno (D, N, I, I_SUB)
     *  - capacidade restante de dia e noite (seguindo regra atual de 2 vagas)
     *
     * IMPORTANTE: faz SELECT ... FOR UPDATE, então deve ser usada dentro de transação.
     */
    public function carregarEstadoDia(string $dataYmd): array
    {
        // Fixo (do ciclo) para o dia
        $fixo = get_fixo_de_servico($dataYmd, $this->conn);
        $fixoValido = false;
        $temExcecao = false;

        if ($fixo) {
            $temExcecao = verificar_excecao_fixo((int)$fixo['id'], $dataYmd, $this->conn);
            $fixoValido = !$temExcecao;
        }

        // Contagem dos plantões já registrados no dia (com travamento da linha)
        $cntD = $cntN = $cntI = $cntSUB = 0;

        $st = mysqli_prepare($this->conn, "SELECT turno FROM plantoes WHERE data = ? FOR UPDATE");
        if (!$st) {
            throw new \RuntimeException('Erro ao preparar SELECT de plantoes: ' . mysqli_error($this->conn));
        }

        mysqli_stmt_bind_param($st, "s", $dataYmd);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);

        while ($r = mysqli_fetch_assoc($rs)) {
            if     ($r['turno'] === 'D')     $cntD++;
            elseif ($r['turno'] === 'N')     $cntN++;
            elseif ($r['turno'] === 'I')     $cntI++;
            elseif ($r['turno'] === 'I_SUB') $cntSUB++;
        }
        mysqli_stmt_close($st);

        // Mesma lógica da API atual: capacidade de 2 por "janela" (dia/noite),
        // considerando D/N + I + I_SUB.
        $capDiaRest   = max(0, 2 - ($cntD + $cntI + $cntSUB));
        $capNoiteRest = max(0, 2 - ($cntN + $cntI + $cntSUB));

        return [
            'data'          => $dataYmd,
            'fixo'          => $fixo,          // pode ser null
            'tem_excecao'   => $temExcecao,
            'fixo_valido'   => $fixoValido,
            'contagens'     => [
                'D'     => $cntD,
                'N'     => $cntN,
                'I'     => $cntI,
                'I_SUB' => $cntSUB,
            ],
            'capacidade'    => [
                'dia_restante'   => $capDiaRest,
                'noite_restante' => $capNoiteRest,
            ],
        ];
    }

    /**
     * Converte o estado do dia em "vagas" por turno.
     *
     * Isso é útil para:
     *  - alimentar o modal (data.vagas.D/N/I_SUB)
     *  - alimentar o calendário (badges de disponibilidade)
     *
     * Mantém a mesma lógica de capacidade que já existia no backend.
     */
    public function calcularVagasPorTurno(array $estadoDia): array
    {
        $cont = $estadoDia['contagens']     ?? ['D'=>0,'N'=>0,'I'=>0,'I_SUB'=>0];
        $fixoValido = (bool)($estadoDia['fixo_valido'] ?? false);

        $cntD   = (int)($cont['D']     ?? 0);
        $cntN   = (int)($cont['N']     ?? 0);
        $cntI   = (int)($cont['I']     ?? 0);
        $cntSUB = (int)($cont['I_SUB'] ?? 0);

        // Mesma ideia da API: capacidade base 2 por janela (dia/noite)
        $capDiaRest   = max(0, 2 - ($cntD + $cntI + $cntSUB));
        $capNoiteRest = max(0, 2 - ($cntN + $cntI + $cntSUB));

        // Heurística para expor "vagas" para o frontend:
        //  - Diurno: quantas posições de DIA ainda cabem
        //  - Noturno: quantas posições de NOITE ainda cabem
        //  - I_SUB: 1 vaga se:
        //        * não existe SUB ainda
        //        * não há fixo válido
        //        * ainda há capacidade nas duas janelas
        $vagasD   = $capDiaRest;
        $vagasN   = $capNoiteRest;
        $vagasSub = 0;

        if (!$fixoValido && $cntSUB < 1 && $capDiaRest > 0 && $capNoiteRest > 0) {
            $vagasSub = 1;
        }

        // Se você estiver usando constantes no RegraEscala,
        // aqui também dá pra cruzar as informações:
        // return RegraEscala::calcularVagasPorTurno([...]);

        return [
            'D'     => $vagasD,
            'N'     => $vagasN,
            'I_SUB' => $vagasSub,
        ];
    }

    /**
     * Valida se um turno pode ser registrado para o dia, com base nas
     * regras de capacidade e tipo do bombeiro (BC / outros).
     *
     * Retorna:
     *  - ['ok' => true]        se estiver tudo certo
     *  - ['ok' => false, 'status' => 400|409, 'message' => '...'] em caso de erro
     *
     * Essa é basicamente a parte de "Regras por turno" que hoje está
     * dentro do api_registrar_plantao.php.
     */
    public function validarTurnoECapacidade(array $estadoDia, string $turno, array $bombeiro): array
    {
        $turno = strtoupper(trim($turno));
        $tipoBombeiro = $bombeiro['tipo'] ?? null;

        $fixoValido = (bool)($estadoDia['fixo_valido'] ?? false);
        $cont       = $estadoDia['contagens']  ?? ['D'=>0,'N'=>0,'I'=>0,'I_SUB'=>0];
        $cap        = $estadoDia['capacidade'] ?? ['dia_restante'=>0,'noite_restante'=>0];

        $cntSUB      = (int)($cont['I_SUB']       ?? 0);
        $capDiaRest   = (int)($cap['dia_restante']   ?? 0);
        $capNoiteRest = (int)($cap['noite_restante'] ?? 0);

        // Regra idêntica à que está hoje na API:
        if ($turno === 'I_SUB') {

            if ($fixoValido) {
                return [
                    'ok'      => false,
                    'status'  => 400,
                    'message' => 'I_SUB só existe quando não há fixo válido.',
                ];
            }

            if ($cntSUB >= 1) {
                return [
                    'ok'      => false,
                    'status'  => 409,
                    'message' => 'A vaga de Substituto já está ocupada.',
                ];
            }

            if (!($capDiaRest > 0 && $capNoiteRest > 0)) {
                return [
                    'ok'      => false,
                    'status'  => 409,
                    'message' => 'Sem capacidade simultânea para I_SUB.',
                ];
            }

        } elseif ($turno === 'I') {

            if (!($capDiaRest > 0 && $capNoiteRest > 0)) {
                return [
                    'ok'      => false,
                    'status'  => 409,
                    'message' => 'Sem capacidade simultânea para Integral.',
                ];
            }

        } elseif ($turno === 'D') {

            if ($tipoBombeiro !== 'BC') {
                return [
                    'ok'      => false,
                    'status'  => 400,
                    'message' => 'Turno Diurno é exclusivo de BC.',
                ];
            }

            if ($capDiaRest <= 0) {
                return [
                    'ok'      => false,
                    'status'  => 409,
                    'message' => 'Sem vagas no Diurno.',
                ];
            }

        } elseif ($turno === 'N') {

            if ($tipoBombeiro !== 'BC') {
                return [
                    'ok'      => false,
                    'status'  => 400,
                    'message' => 'Turno Noturno é exclusivo de BC.',
                ];
            }

            if ($capNoiteRest <= 0) {
                return [
                    'ok'      => false,
                    'status'  => 409,
                    'message' => 'Sem vagas no Noturno.',
                ];
            }

        } else {
            return [
                'ok'      => false,
                'status'  => 400,
                'message' => 'Turno inválido.',
            ];
        }

        return ['ok' => true];
    }
}

