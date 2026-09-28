(() => {
    'use strict';

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const initFilterbar = (root) => {
        if (root.dataset.xdecaroFilterbarReady === 'true') {
            return;
        }

        const toggle = root.querySelector('[data-xdecaro-filterbar-toggle]');
        const panel = root.querySelector('[data-xdecaro-filterbar-open]');
        const close = root.querySelector('[data-xdecaro-filterbar-close]');

        if (!toggle || !panel) {
            return;
        }

        root.dataset.xdecaroFilterbarReady = 'true';
        let currentAnimation = null;

        const finishAnimation = () => {
            currentAnimation?.cancel();
            currentAnimation = null;
        };

        const setOpenInstant = (open) => {
            finishAnimation();
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        const setOpen = async (open) => {
            const currentlyOpen = toggle.getAttribute('aria-expanded') === 'true';

            if (currentlyOpen === open && panel.hidden === !open) {
                return;
            }

            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            finishAnimation();

            if (reducedMotion.matches || typeof panel.animate !== 'function') {
                panel.hidden = !open;
                return;
            }

            if (open) {
                panel.hidden = false;
            }

            const keyframes = open
                ? [
                    { opacity: 0, transform: 'translateY(-0.5rem)' },
                    { opacity: 1, transform: 'translateY(0)' },
                ]
                : [
                    { opacity: 1, transform: 'translateY(0)' },
                    { opacity: 0, transform: 'translateY(-0.5rem)' },
                ];

            const animation = panel.animate(keyframes, {
                duration: 180,
                easing: 'cubic-bezier(.2, .7, .2, 1)',
                fill: 'both',
            });
            currentAnimation = animation;

            try {
                await animation.finished;
            } catch (error) {
                return;
            }

            if (currentAnimation !== animation) {
                return;
            }

            currentAnimation = null;
            animation.cancel();
            panel.hidden = !open;
        };

        setOpenInstant(toggle.getAttribute('aria-expanded') === 'true' || panel.hidden === false);

        toggle.addEventListener('click', () => {
            void setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });

        close?.addEventListener('click', async () => {
            await setOpen(false);
            toggle.focus();
        });
    };

    const init = () => {
        document.querySelectorAll('[data-xdecaro-filterbar]').forEach(initFilterbar);
    };

    document.addEventListener('DOMContentLoaded', init, { once: true });
    document.addEventListener('joomla:updated', init);
})();
