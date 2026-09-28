(() => {
    'use strict';

    const initFilterbar = (root) => {
        const toggle = root.querySelector('[data-xdecaro-filterbar-toggle]');
        const panel = root.querySelector('[data-xdecaro-filterbar-open]');
        const close = root.querySelector('[data-xdecaro-filterbar-close]');

        if (!toggle || !panel) {
            return;
        }

        const setOpen = (open) => {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        setOpen(toggle.getAttribute('aria-expanded') === 'true' || panel.hidden === false);

        toggle.addEventListener('click', () => {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });

        close?.addEventListener('click', () => {
            setOpen(false);
            toggle.focus();
        });
    };

    const init = () => {
        document.querySelectorAll('[data-xdecaro-filterbar]').forEach(initFilterbar);
    };

    document.addEventListener('DOMContentLoaded', init, { once: true });
    document.addEventListener('joomla:updated', init);
})();
