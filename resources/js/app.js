/*
 * Publikai — interações do shell global (Sprint 0.2).
 * JavaScript vanilla apenas para drawer e estados de acessibilidade.
 * Sem frameworks (sem Vue/React/Alpine).
 */

document.addEventListener('DOMContentLoaded', () => {
    const menu = document.getElementById('mobile-menu');
    const openButton = document.getElementById('menu-button');
    const closeButton = document.getElementById('menu-close');
    const backdrop = document.getElementById('mobile-backdrop');

    if (!menu || !openButton) {
        return;
    }

    const setMenu = (open) => {
        menu.classList.toggle('hidden', !open);
        openButton.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('overflow-hidden', open);

        if (open && closeButton) {
            closeButton.focus();
        } else if (!open) {
            openButton.focus();
        }
    };

    openButton.addEventListener('click', () => setMenu(true));
    closeButton?.addEventListener('click', () => setMenu(false));
    backdrop?.addEventListener('click', () => setMenu(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !menu.classList.contains('hidden')) {
            setMenu(false);
        }
    });

    // Proteção de revisão não salva (Sprint 5.2): enquanto o formulário de
    // revisão da proposta tiver alterações não persistidas, o botão de apply
    // fica desabilitado. Sem alterações, o apply funciona normalmente.
    const reviewForm = document.getElementById('proposal-review-form');
    const applyButton = document.getElementById('proposal-apply-button');
    const dirtyHelper = document.getElementById('proposal-dirty-helper');

    if (reviewForm && applyButton) {
        const snapshot = () => new URLSearchParams(new FormData(reviewForm)).toString();
        const baseline = snapshot();

        const refreshDirty = () => {
            const dirty = snapshot() !== baseline;
            applyButton.disabled = dirty;
            dirtyHelper?.classList.toggle('hidden', !dirty);
        };

        reviewForm.addEventListener('input', refreshDirty);
        reviewForm.addEventListener('change', refreshDirty);

        window.addEventListener('beforeunload', (event) => {
            if (snapshot() !== baseline) {
                event.preventDefault();
            }
        });
    }
});
