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
    syncProximoGlobals(null);

    if (btnPular) btnPular.disabled = true;
    if (btnReset) btnReset.disabled = false;

    try {
        const resp = await fetch('api/api_ordem.php?action=next');
        const data = await resp.json();

        if (!data.success || !data.bombeiro) {
            card.innerHTML = `<span>${data.message || 'Não foi possível carregar o próximo.'}</span>`;
            if (btnPular) btnPular.disabled = true;
            syncProximoGlobals(null);
            return;
        }

        proximoBcId = data.bombeiro.id;
        console.log('[carregarProximoDaOrdem] proximoBcId =', proximoBcId, data.bombeiro);

        card.innerHTML = `
            <div class="proximo-bc-nome">
                <span class="label">Próximo a escolher:</span>
                <span class="nome">${data.bombeiro.nome_completo} (${data.bombeiro.tipo})</span>
            </div>
        `;

        syncProximoGlobals(data.bombeiro);

        if (btnPular) btnPular.disabled = false;

    } catch (e) {
        console.error('Erro ao carregar próximo da ordem:', e);
        card.innerHTML = 'Erro ao carregar próximo da ordem.';
        if (btnPular) btnPular.disabled = true;
        syncProximoGlobals(null);
        if (typeof window.showToast === 'function') {
            window.showToast('Erro ao carregar próximo da ordem.', 'error');
        }
    }
}

/**
 * Marca o BC atual como "não participa mais da ordem" (pular até o próximo reset).
 */
async function pularBcAtual() {
    console.log('[pularBcAtual] chamado. proximoBcId =', proximoBcId);

    if (!proximoBcId) {
        console.warn('[pularBcAtual] proximoBcId está vazio, abortando.');
        return;
    }

    const btnPular = document.getElementById('btn-pular-bc');
    if (btnPular) {
        btnPular.disabled = true;
        btnPular.innerText = 'Pulando...';
    }

    try {
        const data = await postForm('api/api_ordem.php?action=skip', {
            bombeiro_id: String(proximoBcId),
        });

        console.log('[pularBcAtual] resposta API skip:', data);

        if (!data.success) {
            const msg = data.message || 'Erro ao pular bombeiro na ordem.';
            if (typeof window.showToast === 'function') window.showToast(msg, 'error');
            else alert(msg);
            return;
        }

        if (typeof window.showToast === 'function') {
            window.showToast(data.message || 'BC removido da ordem deste ciclo.', 'success');
        }

        // Depois de pular, recarrega o próximo da fila
        await carregarProximoDaOrdem();

    } catch (e) {
        console.error('Erro ao pular bombeiro da ordem:', e);
        const msg = 'Erro ao pular bombeiro da ordem.';
        if (typeof window.showToast === 'function') window.showToast(msg, 'error');
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
 */
async function resetarOrdem() {
    const btnReset = document.getElementById('btn-reset-ordem');
    if (!btnReset) return;

    btnReset.disabled = true;
    const textoOriginal = btnReset.innerText;
    btnReset.innerText = 'Resetando...';

    try {
        const data = await postForm('api/api_ordem.php?action=reset');

        console.log('[resetarOrdem] resposta API reset:', data);

        if (!data.success) {
            const msg = data.message || 'Erro ao resetar a ordem.';
            if (typeof window.showToast === 'function') window.showToast(msg, 'error');
            else alert(msg);
            return;
        }

        await carregarProximoDaOrdem();

        if (typeof window.showToast === 'function') {
            window.showToast(data.message || 'Ordem reiniciada. Todos voltaram para a fila.', 'success');
        }

    } catch (e) {
        console.error('Erro ao resetar a ordem:', e);
        const msg = 'Erro ao resetar a ordem.';
        if (typeof window.showToast === 'function') window.showToast(msg, 'error');
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

    console.log('[salvarInicioOrdem] BC ID:', bombeiroId);

    try {
        const data = await postForm('api/api_set_inicio_ordem.php', {
            bombeiro_id: String(bombeiroId),
        });

        console.log('[salvarInicioOrdem] resposta API:', data);

        if (!data.success) {
            const msg = data.message || 'Não foi possível salvar o início da ordem.';
            if (typeof window.showToast === 'function') window.showToast(msg, 'error');
            else alert(msg);
            return;
        }

        if (typeof window.showToast === 'function') {
            window.showToast(data.message || 'Início da ordem atualizado.', 'success');
        }

        await carregarProximoDaOrdem();

    } catch (e) {
        console.error('Erro ao salvar início da ordem:', e);
        const msg = 'Erro ao salvar início da ordem.';
        if (typeof window.showToast === 'function') window.showToast(msg, 'error');
        else alert(msg);
    }
}

// Inicializa ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    console.log('[ordem_escala] DOMContentLoaded disparado');
    carregarProximoDaOrdem();

    const btnPular   = document.getElementById('btn-pular-bc');
    const btnReset   = document.getElementById('btn-reset-ordem');
    const selInicio  = document.getElementById('selectInicioOrdem');

    // Botão PULAR
    if (btnPular) {
        btnPular.addEventListener('click', async (e) => {
            e.preventDefault();
            console.log('[ordem_escala] clique em btn-pular-bc, proximoBcId =', proximoBcId);

            const mensagem = 'Tem certeza que este BC não quer mais escolher neste ciclo?';

            let ok;
            if (typeof window.showConfirm === 'function') {
                ok = await window.showConfirm(mensagem);
            } else {
                ok = confirm(mensagem);
            }

            console.log('[ordem_escala] resultado confirmação pular =', ok);

            if (!ok) return;

            await pularBcAtual();
        });
    }

    // Botão RESET
    if (btnReset) {
        btnReset.addEventListener('click', async (e) => {
            e.preventDefault();
            console.log('[ordem_escala] clique em btn-reset-ordem');

            const mensagem = 'Resetar a ordem vai colocar TODOS os BCs de volta na fila. Deseja continuar?';

            let ok;
            if (typeof window.showConfirm === 'function') {
                ok = await window.showConfirm(mensagem);
            } else {
                ok = confirm(mensagem);
            }

            console.log('[ordem_escala] resultado confirmação reset =', ok);

            if (!ok) return;

            await resetarOrdem();
        });
    }

    // Select "Começou no mês de..."
    if (selInicio) {
        selInicio.addEventListener('change', (e) => {
            const val = e.target.value;
            console.log('[ordem_escala] selectInicioOrdem change, value =', val);
            if (!val) return;
            salvarInicioOrdem(val);
        });
    }
});
