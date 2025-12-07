// js/ordem_escala.js

// Guarda o BC atual que está como "próximo" na ordem
let proximoBcId = null;

/**
 * Helper para POST x-www-form-urlencoded e tratar JSON.
 */
async function postForm(url, data = {}) {
    const resp = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: new URLSearchParams(data),
    });

    const txt = await resp.text();
    try {
        return JSON.parse(txt);
    } catch (e) {
        console.error('Resposta não-JSON de', url, txt);
        console.error('Corpo da resposta:', txt);
        return { success: false, message: 'Resposta não-JSON de ' + url };
    }
}

/**
 * Atualiza as variáveis globais usadas pelo modal (script.js)
 * para manter o "Próximo a escolher" sincronizado.
 */
function syncProximoGlobals(bombeiro) {
    if (bombeiro && bombeiro.id) {
        window.PROXIMO_GLOBAL_ID   = Number(bombeiro.id);
        // No PHP você grava só o nome; mantém o mesmo padrão aqui
        window.PROXIMO_GLOBAL_NOME = bombeiro.nome_completo || '';
    } else {
        window.PROXIMO_GLOBAL_ID   = null;
        window.PROXIMO_GLOBAL_NOME = '';
    }
}

/**
 * Atualiza o card com o próximo BC da ordem.
 */
async function carregarProximoDaOrdem() {
    const card      = document.getElementById('proximo-bc-card');
    const btnPular  = document.getElementById('btn-pular-bc');
    const btnReset  = document.getElementById('btn-reset-ordem');

    if (!card) return;

    card.innerHTML = 'Carregando próximo da ordem...';
    proximoBcId = null;
    syncProximoGlobals(null); // zera globals enquanto carrega

    if (btnPular) btnPular.disabled = true;
    // btnReset fica sempre habilitado para você conseguir resetar
    if (btnReset) btnReset.disabled = false;

    try {
        const resp = await fetch('api/api_ordem.php?action=next');
        const data = await resp.json();

        if (!data.success || !data.bombeiro) {
            // Exemplo: "Não há BCs ativos na ordem de escolha."
            card.innerHTML = `<span>${data.message || 'Não foi possível carregar o próximo.'}</span>`;
            if (btnPular) btnPular.disabled = true;
            syncProximoGlobals(null);
            // btnReset continua habilitado para você clicar e resetar
            return;
        }

        proximoBcId = data.bombeiro.id;

        card.innerHTML = `
            <div class="proximo-bc-nome">
                <span class="label">Próximo a escolher:</span>
                <span class="nome">${data.bombeiro.nome_completo} (${data.bombeiro.tipo})</span>
            </div>
        `;

        // 🔗 Mantém as variáveis globais em sincronia com o card
        syncProximoGlobals(data.bombeiro);

        if (btnPular) btnPular.disabled = false;

    } catch (e) {
        console.error('Erro ao carregar próximo da ordem:', e);
        card.innerHTML = 'Erro ao carregar próximo da ordem.';
        if (btnPular) btnPular.disabled = true;
        syncProximoGlobals(null);
        if (typeof showToast === 'function') {
            showToast('Erro ao carregar próximo da ordem.', 'error');
        }
    }
}

/**
 * Marca o BC atual como "não participa mais da ordem" (pular até o próximo reset).
 */
async function pularBcAtual() {
    if (!proximoBcId) return;

    const btnPular = document.getElementById('btn-pular-bc');
    if (btnPular) {
        btnPular.disabled = true;
        btnPular.innerText = 'Pulando...';
    }

    try {
        const data = await postForm('api/api_ordem.php?action=skip', {
            bombeiro_id: String(proximoBcId),
        });

        if (!data.success) {
            const msg = data.message || 'Erro ao pular bombeiro na ordem.';
            if (typeof showToast === 'function') showToast(msg, 'error');
            else alert(msg);
            return;
        }

        if (typeof showToast === 'function') {
            showToast(data.message || 'BC removido da ordem deste ciclo.', 'success');
        }

        // Depois de pular, recarrega o próximo da fila (já sincroniza globals)
        await carregarProximoDaOrdem();

    } catch (e) {
        console.error('Erro ao pular bombeiro da ordem:', e);
        const msg = 'Erro ao pular bombeiro da ordem.';
        if (typeof showToast === 'function') showToast(msg, 'error');
        else alert(msg);
    } finally {
        if (btnPular) {
            btnPular.disabled = false;
            btnPular.innerText = 'Não quer mais escolher (pular)';
        }
    }
}

/**
 * Reseta a ordem:
 *  - ativa todos na tabela ordem_escolha
 *  - zera bc_ultimo_escolheu_id
 */
async function resetarOrdem() {
    const btnReset = document.getElementById('btn-reset-ordem');
    if (!btnReset) return;

    btnReset.disabled = true;
    const textoOriginal = btnReset.innerText;
    btnReset.innerText = 'Resetando...';

    try {
        const data = await postForm('api/api_ordem.php?action=reset');

        if (!data.success) {
            const msg = data.message || 'Erro ao resetar a ordem.';
            if (typeof showToast === 'function') showToast(msg, 'error');
            else alert(msg);
            return;
        }

        // Depois de resetar, recarrega o card (vai mostrar o primeiro da ordem)
        await carregarProximoDaOrdem();

        if (typeof showToast === 'function') {
            showToast(data.message || 'Ordem reiniciada. Todos voltaram para a fila.', 'success');
        }

    } catch (e) {
        console.error('Erro ao resetar a ordem:', e);
        const msg = 'Erro ao resetar a ordem.';
        if (typeof showToast === 'function') showToast(msg, 'error');
        else alert(msg);
    } finally {
        btnReset.disabled = false;
        btnReset.innerText = textoOriginal;
    }
}

/**
 * Salva no backend qual BC é o "início da ordem" (select do topo).
 */
async function salvarInicioOrdem(bombeiroId) {
    if (!bombeiroId) return;

    console.log('[ordem_escala] Salvando início da ordem para BC ID:', bombeiroId);

    try {
        const data = await postForm('api/api_set_inicio_ordem.php', {
            bombeiro_id: String(bombeiroId),
        });

        console.log('[ordem_escala] Resposta salvarInicioOrdem:', data);

        if (!data.success) {
            const msg = data.message || 'Não foi possível salvar o início da ordem.';
            if (typeof showToast === 'function') showToast(msg, 'error');
            else alert(msg);
            return;
        }

        if (typeof showToast === 'function') {
            showToast(data.message || 'Início da ordem atualizado.', 'success');
        }

        // Após alterar o início, recarrega o próximo (já sincroniza globals)
        await carregarProximoDaOrdem();

    } catch (e) {
        console.error('Erro ao salvar início da ordem:', e);
        const msg = 'Erro ao salvar início da ordem.';
        if (typeof showToast === 'function') showToast(msg, 'error');
        else alert(msg);
    }
}

// Inicializa ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    carregarProximoDaOrdem();

    const btnPular   = document.getElementById('btn-pular-bc');
    const btnReset   = document.getElementById('btn-reset-ordem');
    const selInicio  = document.getElementById('selectInicioOrdem');

    // --- CORREÇÃO DO BOTÃO PULAR ---
    if (btnPular) {
        btnPular.addEventListener('click', async (e) => {
            e.preventDefault();
            const mensagem = 'Tem certeza que este BC não quer mais escolher neste ciclo?';

            let ok;
            // Se showConfirm existir, usa await (Promise). Se não, fallback para confirm nativo.
            if (typeof showConfirm === 'function') {
                ok = await showConfirm(mensagem);
            } else {
                ok = confirm(mensagem);
            }

            if (!ok) return;

            pularBcAtual();
        });
    }

    // --- CORREÇÃO DO BOTÃO RESET ---
    if (btnReset) {
        btnReset.addEventListener('click', async (e) => {
            e.preventDefault();
            const mensagem = 'Resetar a ordem vai colocar TODOS os BCs de volta na fila. Deseja continuar?';

            let ok;
            if (typeof showConfirm === 'function') {
                ok = await showConfirm(mensagem);
            } else {
                ok = confirm(mensagem);
            }

            if (!ok) return;

            resetarOrdem();
        });
    }

    // Quando mudar o "Começou no mês de ... com"
    if (selInicio) {
        selInicio.addEventListener('change', (e) => {
            const val = e.target.value;
            console.log('[ordem_escala] selectInicioOrdem change, value =', val);

            // Se voltar para "-- Selecione --", não salva nada
            if (!val) return;

            salvarInicioOrdem(val);
        });
    }
});