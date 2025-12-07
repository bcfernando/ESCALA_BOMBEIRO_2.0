<?php
declare(strict_types=1);

/**
 * Arquivo: includes/regra_escala.php
 *
 * Responsável SOMENTE pelas regras de negócio da escala.
 * Nada de SQL, nada de HTML aqui.
 */
class RegraEscala
{
    // =====================================================================
    //  BLOCO 1 – REGRAS ANTIGAS (baseadas em limite_total / vagas_xxx)
    //           -> mantidas para compatibilidade se você estiver usando em
    //              outros lugares
    // =====================================================================

    /**
     * Monta o array de vagas por turno a partir dos dados do dia.
     *
     * Espera algo nesse formato em $dia:
     *  - $dia['vagas_diurno']
     *  - $dia['vagas_noturno']
     *  - $dia['vagas_integral_sub']  (se existir)
     *  - $dia['pode_integral']       (bool)
     */
    public static function calcularVagas(array $dia): array
    {
        $vagasDiurno  = isset($dia['vagas_diurno'])       ? (int)$dia['vagas_diurno']      : 0;
        $vagasNoturno = isset($dia['vagas_noturno'])      ? (int)$dia['vagas_noturno']     : 0;
        $vagasISub    = isset($dia['vagas_integral_sub']) ? (int)$dia['vagas_integral_sub']: 0;
        $podeIntegral = !empty($dia['pode_integral']);

        return [
            'D'     => max(0, $vagasDiurno),
            'N'     => max(0, $vagasNoturno),
            'I_SUB' => max(0, $vagasISub),
            'I'     => $podeIntegral ? 1 : 0, // integral é liberado ou não
        ];
    }

    /**
     * Calcula quantas pessoas já estão alocadas no dia.
     *
     * - $totalOcupantesBanco: valor vindo do banco, se você já calcula lá
     * - $temFixoValido: true se o FIXO está contado nesse dia (sem exceção)
     * - $extras: array de plantoes extras (podemos usar count($extras))
     */
    public static function calcularTotalOcupantes(
        ?int $totalOcupantesBanco,
        bool $temFixoValido,
        array $extras
    ): int
    {
        if (is_int($totalOcupantesBanco)) {
            return max(0, $totalOcupantesBanco);
        }

        $qtdExtras = count($extras);
        $qtdFixo   = $temFixoValido ? 1 : 0;

        return $qtdExtras + $qtdFixo;
    }

    /**
     * Retorna quantas pessoas ainda podem ser alocadas no dia,
     * respeitando limite_total.
     */
    public static function pessoasRestantes(int $limiteTotal, int $totalOcupantes): int
    {
        return max(0, $limiteTotal - $totalOcupantes);
    }

    /**
     * Decide se é permitido cadastrar um plantão nesse turno,
     * dado o estado atual do dia (versão antiga).
     *
     * Espera:
     * - $turno: 'D', 'N', 'I', 'I_SUB'
     * - $vagas: array vindo de calcularVagas()
     * - $restantes: valor de pessoasRestantes()
     *
     * Retorna:
     * - ['ok' => bool, 'motivo' => string]
     */
    public static function podeCadastrarTurno(
        string $turno,
        array $vagas,
        int $restantes
    ): array
    {
        // Sem espaço no teto do dia
        if ($restantes <= 0) {
            return [
                'ok'     => false,
                'motivo' => 'Limite máximo de bombeiros para o dia já foi atingido.'
            ];
        }

        switch ($turno) {
            case 'D':
            case 'N':
                if (empty($vagas[$turno]) || $vagas[$turno] <= 0) {
                    return [
                        'ok'     => false,
                        'motivo' => 'Não há vagas disponíveis para este turno.'
                    ];
                }
                return ['ok' => true, 'motivo' => ''];

            case 'I':
                // integral depende do backend ter liberado
                if (empty($vagas['I']) || $vagas['I'] <= 0) {
                    return [
                        'ok'     => false,
                        'motivo' => 'Escala integral não está liberada para este dia.'
                    ];
                }
                return ['ok' => true, 'motivo' => ''];

            case 'I_SUB':
                if (empty($vagas['I_SUB']) || $vagas['I_SUB'] <= 0) {
                    return [
                        'ok'     => false,
                        'motivo' => 'Não há vagas para integral como substituto neste dia.'
                    ];
                }
                return ['ok' => true, 'motivo' => ''];

            default:
                return [
                    'ok'     => false,
                    'motivo' => 'Turno inválido.'
                ];
        }
    }

    /**
     * Função de alto nível (versão antiga): recebe um "dia" já montado
     * e diz se pode cadastrar naquele turno.
     *
     * Espera em $dia:
     *  - limite_total (int)
     *  - total_ocupantes (int|null)
     *  - tem_fixo_valido (bool)
     *  - extras (array)
     *  - vagas_diurno, vagas_noturno, vagas_integral_sub, pode_integral
     */
    public static function validarCadastroPlantao(string $turno, array $dia): array
    {
        $limiteTotal     = isset($dia['limite_total']) ? (int)$dia['limite_total'] : 3;
        $totalBanco      = $dia['total_ocupantes'] ?? null;
        $temFixoValido   = !empty($dia['tem_fixo_valido']);
        $extras          = $dia['extras'] ?? [];

        $vagas      = self::calcularVagas($dia);
        $total      = self::calcularTotalOcupantes($totalBanco, $temFixoValido, $extras);
        $restantes  = self::pessoasRestantes($limiteTotal, $total);

        $resultado  = self::podeCadastrarTurno($turno, $vagas, $restantes);

        return array_merge($resultado, [
            'limite_total'    => $limiteTotal,
            'total_ocupantes' => $total,
            'restantes'       => $restantes,
            'vagas'           => $vagas,
        ]);
    }

    // =====================================================================
    //  BLOCO 2 – NOVAS REGRAS ALINHADAS COM EscalaService::carregarEstadoDia
    //           (usa $estadoDia['contagens'], ['fixo_valido'], ['capacidade'])
    // =====================================================================

    /**
     * Calcula as "vagas" por turno a partir de um $estadoDia
     * vindo de EscalaService::carregarEstadoDia().
     *
     * Espera em $estadoDia:
     *  - 'fixo_valido' (bool)
     *  - 'contagens'  => ['D','N','I','I_SUB']
     *
     * Retorna, por exemplo:
     *  [
     *    'D'     => 1,
     *    'N'     => 0,
     *    'I_SUB' => 1,
     *    'cap_dia_restante'   => 1,
     *    'cap_noite_restante' => 0,
     *  ]
     */
    public static function calcularVagasPorEstadoDia(array $estadoDia): array
    {
        $cont       = $estadoDia['contagens']  ?? ['D'=>0,'N'=>0,'I'=>0,'I_SUB'=>0];
        $fixoValido = (bool)($estadoDia['fixo_valido'] ?? false);

        $cntD   = (int)($cont['D']     ?? 0);
        $cntN   = (int)($cont['N']     ?? 0);
        $cntI   = (int)($cont['I']     ?? 0);
        $cntSUB = (int)($cont['I_SUB'] ?? 0);

        // Capacidade base de 2 por janela (dia/noite)
        $capDiaRest   = max(0, 2 - ($cntD + $cntI + $cntSUB));
        $capNoiteRest = max(0, 2 - ($cntN + $cntI + $cntSUB));

        // Vagas expostas para o frontend:
        $vagasD   = $capDiaRest;
        $vagasN   = $capNoiteRest;
        $vagasSub = 0;

        // Regra do I_SUB: apenas se
        //  - não existe fixo válido
        //  - ainda não existe SUB
        //  - há capacidade nas duas janelas
        if (!$fixoValido && $cntSUB < 1 && $capDiaRest > 0 && $capNoiteRest > 0) {
            $vagasSub = 1;
        }

        return [
            'D'                  => $vagasD,
            'N'                  => $vagasN,
            'I_SUB'              => $vagasSub,
            'cap_dia_restante'   => $capDiaRest,
            'cap_noite_restante' => $capNoiteRest,
        ];
    }

    /**
     * Valida se um turno pode ser registrado para o dia, com base no
     * $estadoDia (vindo do EscalaService) e no tipo do bombeiro.
     *
     * Espera:
     *  - $estadoDia: resultado de EscalaService::carregarEstadoDia()
     *  - $turno: 'D', 'N', 'I', 'I_SUB'
     *  - $bombeiro: array com pelo menos ['tipo' => 'BC' ou outro]
     *
     * Retorna:
     *  - ['ok' => true]
     *  - ['ok' => false, 'status' => 400|409, 'message' => '...']
     */
    public static function validarTurnoECapacidadeEstadoDia(
        array $estadoDia,
        string $turno,
        array $bombeiro
    ): array
    {
        $turno        = strtoupper(trim($turno));
        $tipoBombeiro = $bombeiro['tipo'] ?? null;

        $fixoValido = (bool)($estadoDia['fixo_valido'] ?? false);
        $cont       = $estadoDia['contagens']  ?? ['D'=>0,'N'=>0,'I'=>0,'I_SUB'=>0];

        $cntD   = (int)($cont['D']     ?? 0);
        $cntN   = (int)($cont['N']     ?? 0);
        $cntI   = (int)($cont['I']     ?? 0);
        $cntSUB = (int)($cont['I_SUB'] ?? 0);

        $capDiaRest   = max(0, 2 - ($cntD + $cntI + $cntSUB));
        $capNoiteRest = max(0, 2 - ($cntN + $cntI + $cntSUB));

        // -----------------------------------------------------------------
        // Segue exatamente a regra que você colocou no EscalaService
        // -----------------------------------------------------------------
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
