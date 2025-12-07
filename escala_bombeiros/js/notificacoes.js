// js/notificacoes.js
// Centraliza TOASTS e CONFIRMs bonitinhos

(function () {
    // ---------- TOAST ----------
    const toastContainerId = 'toast-container';

    function createToastContainer() {
        let cont = document.getElementById(toastContainerId);
        if (!cont) {
            cont = document.createElement('div');
            cont.id = toastContainerId;
            cont.className = 'toast-container';
            document.body.appendChild(cont);
        }
        return cont;
    }

    window.showToast = function (message, type = 'info', timeout = 3000) {
        const container = createToastContainer();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('toast-hide');
            setTimeout(() => toast.remove(), 300);
        }, timeout);
    };

    // ---------- CONFIRM BONITO ----------
    let confirmYesCb = null;
    let confirmNoCb  = null;

    function hideConfirm() {
        const overlay = document.getElementById('confirm-modal');
        if (!overlay) return;
        overlay.style.display = 'none';
    }

    window.showConfirm = function (message, onYes, onNo) {
        const overlay = document.getElementById('confirm-modal');
        const textEl  = document.getElementById('confirm-modal-text');
        const btnYes  = document.getElementById('confirm-modal-btn-yes');
        const btnNo   = document.getElementById('confirm-modal-btn-no');

        if (!overlay || !textEl || !btnYes || !btnNo) {
            // se por algum motivo o modal não existir, usa confirm padrão
            const ok = window.confirm(message);
            if (ok && typeof onYes === 'function') onYes();
            if (!ok && typeof onNo === 'function') onNo();
            return;
        }

        textEl.textContent = message;
        confirmYesCb = typeof onYes === 'function' ? onYes : null;
        confirmNoCb  = typeof onNo === 'function'  ? onNo  : null;

        overlay.style.display = 'flex'; // centraliza
    };

    document.addEventListener('DOMContentLoaded', () => {
        const overlay = document.getElementById('confirm-modal');
        const btnYes  = document.getElementById('confirm-modal-btn-yes');
        const btnNo   = document.getElementById('confirm-modal-btn-no');

        if (!overlay || !btnYes || !btnNo) return;

        btnYes.addEventListener('click', () => {
            hideConfirm();
            if (confirmYesCb) confirmYesCb();
        });

        btnNo.addEventListener('click', () => {
            hideConfirm();
            if (confirmNoCb) confirmNoCb();
        });

        // clicar fora da caixa fecha como "não"
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                hideConfirm();
                if (confirmNoCb) confirmNoCb();
            }
        });
    });
})();
