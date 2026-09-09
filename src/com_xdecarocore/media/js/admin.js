(() => {
    'use strict';

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-xdecaro-package-toggle]');
        if (!button) {
            return;
        }

        const targetId = button.getAttribute('aria-controls');
        if (!targetId) {
            return;
        }

        const row = document.getElementById(targetId);
        if (!row) {
            return;
        }

        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        row.setAttribute('aria-hidden', expanded ? 'true' : 'false');
        row.classList.toggle('is-open', !expanded);
    });
})();
