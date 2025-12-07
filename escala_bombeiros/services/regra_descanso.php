<?php
/**
 * Regra de descanso obrigatório de 24h entre plantões.
 *
 * - Plantão Integral (I) ou I_SUB = 24h de trabalho
 * - 12h + 12h consecutivos (D + N ou N + D) = também contam como 24h
 *
 * Regra que você pediu:
 *  - Se o bombeiro fizer 24h (integral OU 12+12), ele precisa folgar 24h.
 *  - Na prática: se o "pacote" de 24h termina no dia X,
 *      ele NÃO PODE pegar plantão em:
 *          - dia X
 *          - dia X + 1
 *      Só pode voltar a pegar a partir de X + 2.
 *
 * Exemplo 1:
 *   - 09: I  -> pacote termina dia 09
 *   - NÃO pode: 09 (outro turno), 10 (12 ou 24)
 *   - Pode voltar: dia 11 pra frente
 *
 * Exemplo 2:
 *   - 08: N
 *   - 09: D  -> 12 + 12 (N + D), pacote termina dia 09
 *   - NÃO pode: 09 N, 10 D/N/I
 *   - Pode voltar: dia 11 pra frente
 */

class RegraDescanso
{
    /**
     * @param array  $historico        Lista de plantões anteriores do bombeiro.
     *                                 Formato de cada item:
     *                                 ['data' => 'YYYY-MM-DD', 'turno' => 'D'|'N'|'I'|'I_SUB']
     * @param string $dataNovoPlantao  Data do plantão que se quer registrar (YYYY-MM-DD)
     * @param string $turnoNovo        Turno do plantão novo ('D','N','I','I_SUB')
     *
     * @return array ['ok' => bool, 'motivo' => string]
     */
    public static function verificarDescanso(array $historico, string $dataNovoPlantao, string $turnoNovo): array
    {
        if (empty($historico)) {
            return ['ok' => true, 'motivo' => ''];
        }

        // Ordena do mais recente para o mais antigo
        usort($historico, function ($a, $b) {
            return strcmp($b['data'], $a['data']);
        });

        $ultimo      = $historico[0];
        $dataUltimo  = $ultimo['data'];
        $turnoUltimo = strtoupper($ultimo['turno']);

        try {
            $dtNovo = new DateTimeImmutable($dataNovoPlantao);
            $dtUlt  = new DateTimeImmutable($dataUltimo);
        } catch (Throwable $e) {
            return ['ok' => false, 'motivo' => 'Erro ao interpretar datas de plantão.'];
        }

        $precisaDescanso = false;
        $dataFimPacote   = $dtUlt; // data em que termina o pacote de 24h

        // --- CASO 1: último plantão foi Integral (I ou I_SUB) ---
        if (in_array($turnoUltimo, ['I', 'I_SUB'], true)) {
            $precisaDescanso = true;
        }

        // --- CASO 2: combo 12 + 12 (D + N ou N + D) em dias consecutivos ---
        if (count($historico) > 1) {
            $penultimo      = $historico[1];
            $dataPenultimo  = $penultimo['data'];
            $turnoPenultimo = strtoupper($penultimo['turno']);

            try {
                $dtPenultimo = new DateTimeImmutable($dataPenultimo);
            } catch (Throwable $e) {
                $dtPenultimo = null;
            }

            if ($dtPenultimo) {
                // Verifica se são dias consecutivos (penúltimo = último - 1 dia)
                $dtUltMenosUm = $dtUlt->modify('-1 day');

                if ($dtPenultimo->format('Y-m-d') === $dtUltMenosUm->format('Y-m-d')) {
                    // Verifica se formam 12+12 (D/N ou N/D)
                    $combo12x12 =
                        ($turnoPenultimo === 'D' && $turnoUltimo === 'N') ||
                        ($turnoPenultimo === 'N' && $turnoUltimo === 'D');

                    if ($combo12x12) {
                        $precisaDescanso = true;
                        $dataFimPacote   = $dtUlt; // pacote termina no dia do último plantão
                    }
                }
            }
        }

        if (!$precisaDescanso) {
            // Não há pacote de 24h recente que exija descanso
            return ['ok' => true, 'motivo' => ''];
        }

        // Regras de bloqueio:
        //  - Se o pacote termina em data X (dataFimPacote),
        //    o bombeiro NÃO PODE trabalhar em:
        //       X       (mesmo dia)
        //       X + 1   (dia seguinte)
        //    Só pode a partir de X + 2.
        $dtBloqueioAte = $dataFimPacote->modify('+1 day'); // X + 1
        // Se dataNova <= X + 1  → BLOQUEIA
        if ($dtNovo <= $dtBloqueioAte) {
            return [
                'ok'     => false,
                'motivo' => 'Bombeiro precisa folgar 24h após um conjunto de 24h de serviço (integral ou 12+12).',
            ];
        }

        return ['ok' => true, 'motivo' => ''];
    }
}
