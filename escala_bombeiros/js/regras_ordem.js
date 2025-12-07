// js/regras_ordem.js
// Regras de ORDEM dos BCs (próximo / início da ordem / cabeçalho)

document.addEventListener('DOMContentLoaded', () => {
  const btnAvancarOrdem   = document.getElementById('btnAvancarOrdem');
  const selectInicioOrdem = document.getElementById('selectInicioOrdem');
  const displayProximo    = document.getElementById('displayProximoSugerido');
  const displayUltimo     = document.getElementById('displayUltimoEscolheu');

  // Função utilitária para postar x-www-form-urlencoded
  async function postForm(url, data) {
    const resp = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(data)
    });
    const txt = await resp.text();
    let json;
    try { json = JSON.parse(txt); }
    catch { json = { success: false, message: 'Retorno não-JSON da API: ' + txt.slice(0, 200) }; }
    return json;
  }

  // Atualiza os textos da “casinha” (próximo / último) e também o global do verdinho
  function atualizarCabecalhoOrdem(payload = {}) {
    if (payload.proximo_nome && displayProximo) {
      displayProximo.textContent = payload.proximo_nome;
    }
    if (payload.ultimo_nome && displayUltimo) {
      displayUltimo.textContent = payload.ultimo_nome;
    }
    if (typeof payload.ultimo_id !== 'undefined' && payload.ultimo_id !== null) {
      window.ULTIMO_ESCOLHEU_ID = Number(payload.ultimo_id);
    }
  }

  // --- Botão "Avançar Ordem" ---
  if (btnAvancarOrdem) {
    btnAvancarOrdem.addEventListener('click', async () => {
      const originalText = btnAvancarOrdem.textContent;
      btnAvancarOrdem.disabled = true;
      btnAvancarOrdem.textContent = 'Avançando.';

      try {
        const r = await postForm('api/api_avancar_ordem.php', {});
        if (r.success) {
          // Esperado: API devolver pelo menos novo nome do "próximo"
          // Exemplo de retorno que combina bem:
          // { success:true, proximo_nome:'FULANO', ultimo_id: 5, ultimo_nome:'CICLANO' }
          atualizarCabecalhoOrdem(r);

          if (typeof showToast === 'function') {
            showToast(r.message || 'Ordem avançada.', 'success');
          }
          setTimeout(() => location.reload(), 300);
        } else {
          if (typeof showToast === 'function') {
            showToast(r.message || 'Falha ao avançar ordem.', 'error');
          }
        }
      } catch (e) {
        console.error('Erro ao avançar a ordem:', e);
        if (typeof showToast === 'function') {
          showToast('Erro na comunicação: ' + (e.message || e), 'error');
        }
      } finally {
        btnAvancarOrdem.disabled = false;
        btnAvancarOrdem.textContent = originalText;
      }
    });
  }

  // --- Select "Começou no mês de" (define início da ordem) ---
  if (selectInicioOrdem) {
    selectInicioOrdem.addEventListener('change', async () => {
      const id = selectInicioOrdem.value;
      if (!id) return;

      const r = await postForm('api/api_set_inicio_ordem.php', { inicio_id: id });

      if (r.success) {
        // Mesma ideia: se a API devolver proximo_nome/ultimo_xx, atualiza a casinha
        atualizarCabecalhoOrdem(r);

        if (displayProximo && r.proximo_nome) {
          displayProximo.textContent = r.proximo_nome;
        }
        if (typeof showToast === 'function') {
          showToast('Início da ordem atualizado.', 'success');
        }
        setTimeout(() => location.reload(), 300);
      } else {
        if (typeof showToast === 'function') {
          showToast(r.message || 'Falha ao atualizar início.', 'error');
        }
      }
    });
  }

  // Deixa a função disponível se você quiser chamar de outros lugares
  window.atualizarCabecalhoOrdem = atualizarCabecalhoOrdem;
});
