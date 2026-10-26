/**
 * Accessible front-end tooltips for CKEditor glossary links.
 *
 * Progressive enhancement: glossary links remain readable without JS. On hover/focus the
 * definition is fetched from the plugin API (deduplicated + cached in memory) and shown in an
 * ARIA-described tooltip. Keyboard: Tab to focus, Esc to dismiss.
 */
(function () {
    'use strict';

    var ENDPOINT = window.ckeditorGlossaryEndpoint || '/actions/ckeditor-glossary/api/definition';
    var SELECTOR = 'a.glossary-link[data-glossary-uid]';
    var cache = {};
    var tooltipEl = null;
    var activeLink = null;
    var hideTimer = null;
    var counter = 0;

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function ensureTooltip() {
        if (tooltipEl) {
            return tooltipEl;
        }
        tooltipEl = document.createElement('div');
        tooltipEl.className = 'glossary-tooltip';
        tooltipEl.setAttribute('role', 'tooltip');
        tooltipEl.hidden = true;
        document.body.appendChild(tooltipEl);
        return tooltipEl;
    }

    function fetchDefinition(uid) {
        if (cache[uid]) {
            return cache[uid];
        }
        var url = ENDPOINT + (ENDPOINT.indexOf('?') === -1 ? '?' : '&') + 'uid=' + encodeURIComponent(uid);
        cache[uid] = fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (r) {
                return r.ok ? r.json() : null;
            })
            .catch(function () {
                return null;
            });
        return cache[uid];
    }

    function positionTooltip(link) {
        var rect = link.getBoundingClientRect();
        var el = ensureTooltip();
        var top = rect.bottom + window.scrollY + 8;
        var left = rect.left + window.scrollX;
        el.style.top = top + 'px';
        el.style.left = left + 'px';
    }

    function show(link) {
        clearTimeout(hideTimer);
        var uid = link.getAttribute('data-glossary-uid');
        if (!uid) {
            return;
        }

        activeLink = link;

        fetchDefinition(uid).then(function (data) {
            if (activeLink !== link || !data || !data.definition) {
                return;
            }
            var el = ensureTooltip();
            el.innerHTML = data.definition;

            if (!link.id) {
                link.id = 'glossary-link-' + ++counter;
            }
            el.id = 'glossary-tooltip-' + link.id;
            link.setAttribute('aria-describedby', el.id);

            positionTooltip(link);
            el.hidden = false;
            el.classList.add('is-visible');
        });
    }

    function hide() {
        hideTimer = setTimeout(function () {
            if (tooltipEl) {
                tooltipEl.hidden = true;
                tooltipEl.classList.remove('is-visible');
            }
            if (activeLink) {
                activeLink.removeAttribute('aria-describedby');
            }
            activeLink = null;
        }, 120);
    }

    function enhance(link) {
        if (link.dataset.glossaryReady) {
            return;
        }
        link.dataset.glossaryReady = '1';

        // Links without an href still need to be keyboard focusable.
        if (!link.hasAttribute('href') && !link.hasAttribute('tabindex')) {
            link.setAttribute('tabindex', '0');
        }

        link.addEventListener('mouseenter', function () {
            show(link);
        });
        link.addEventListener('mouseleave', hide);
        link.addEventListener('focus', function () {
            show(link);
        });
        link.addEventListener('blur', hide);
    }

    ready(function () {
        document.querySelectorAll(SELECTOR).forEach(enhance);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && tooltipEl && !tooltipEl.hidden) {
                tooltipEl.hidden = true;
                tooltipEl.classList.remove('is-visible');
                if (activeLink) {
                    activeLink.removeAttribute('aria-describedby');
                }
            }
        });

        window.addEventListener(
            'scroll',
            function () {
                if (activeLink && tooltipEl && !tooltipEl.hidden) {
                    positionTooltip(activeLink);
                }
            },
            { passive: true }
        );
    });
})();
