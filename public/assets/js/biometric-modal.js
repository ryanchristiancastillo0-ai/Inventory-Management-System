/* =========================================================
   PREMIUM BIOMETRIC MODAL — shared factory
   Creates a single reusable dialog instance per page.
   Pairs with /assets/css/biometric.css
   ========================================================= */
(function () {
    'use strict';

    var ICONS = {
        face: '<i class="bi bi-person-bounding-box" aria-hidden="true"></i>',
        camera: '<i class="bi bi-camera-video" aria-hidden="true"></i>',
        shield: '<i class="bi bi-shield-check" aria-hidden="true"></i>',
        lock: '<i class="bi bi-person-lock" aria-hidden="true"></i>',
        check: '<i class="bi bi-check-lg" aria-hidden="true"></i>',
        error: '<i class="bi bi-exclamation-lg" aria-hidden="true"></i>'
    };

    var FACE_ART =
        '<svg viewBox="0 0 240 200" fill="none" role="img" aria-label="Face outline">' +
        '  <ellipse cx="120" cy="102" rx="56" ry="70" stroke="#171717" stroke-opacity=".14" stroke-width="2"/>' +
        '  <ellipse cx="120" cy="102" rx="46" ry="60" stroke="#171717" stroke-opacity=".9" stroke-width="2.4"/>' +
        '  <circle cx="102" cy="92" r="3.2" fill="#171717"/>' +
        '  <circle cx="138" cy="92" r="3.2" fill="#171717"/>' +
        '  <path d="M104 122c6 6.5 10.5 9 16 9s10-2.5 16-9" stroke="#171717" stroke-width="2.4" stroke-linecap="round"/>' +
        '  <path d="M88 156c10 8 21 12 32 12s22-4 32-12" stroke="#171717" stroke-opacity=".25" stroke-width="2.2" stroke-linecap="round"/>' +
        '</svg>';

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function BioModal() {
        var overlay = document.createElement('div');
        overlay.className = 'bio-overlay';

        overlay.innerHTML =
            '<div class="bio-shell" role="dialog" aria-modal="true" aria-labelledby="bioModalTitle">' +
            '  <div class="bio-head">' +
            '    <span class="bio-head-icon" data-bio-icon>' + ICONS.face + '</span>' +
            '    <div class="bio-head-copy">' +
            '      <h2 class="bio-title" id="bioModalTitle" data-bio-title>Face verification</h2>' +
            '      <p class="bio-sub" data-bio-sub>Verify your identity quickly and securely using your camera.</p>' +
            '    </div>' +
            '    <button type="button" class="bio-close" data-bio-close aria-label="Close">' +
            '      <i class="bi bi-x-lg" aria-hidden="true"></i>' +
            '    </button>' +
            '  </div>' +
            '  <div class="bio-body">' +
            '    <div class="bio-camera is-hidden" data-bio-camera>' +
            '      <video autoplay muted playsinline data-bio-video></video>' +
            '      <span class="bio-corner tl"></span><span class="bio-corner tr"></span>' +
            '      <span class="bio-corner bl"></span><span class="bio-corner br"></span>' +
            '      <div class="bio-guide" aria-hidden="true">' +
            '        <span class="bio-guide-oval"></span>' +
            '        <span class="bio-guide-ring"></span>' +
            '      </div>' +
            '      <div class="bio-camera-note" data-bio-note hidden></div>' +
            '    </div>' +
            '    <div class="bio-state" data-bio-state></div>' +
            '    <div class="bio-scan-meta is-hidden" data-bio-meta>' +
            '      <div class="bio-status" data-bio-status>' +
            '        <span class="bio-status-dot"></span>' +
            '        <span data-bio-status-text>Ready</span>' +
            '      </div>' +
            '      <div class="bio-progress" data-bio-progress>' +
            '        <span data-bio-progress-fill></span>' +
            '      </div>' +
            '    </div>' +
            '    <div class="bio-actions" data-bio-actions></div>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(overlay);

        var iconEl = overlay.querySelector('[data-bio-icon]');
        var titleEl = overlay.querySelector('[data-bio-title]');
        var subEl = overlay.querySelector('[data-bio-sub]');
        var cameraEl = overlay.querySelector('[data-bio-camera]');
        var noteEl = overlay.querySelector('[data-bio-note]');
        var stateEl = overlay.querySelector('[data-bio-state]');
        var metaEl = overlay.querySelector('[data-bio-meta]');
        var statusEl = overlay.querySelector('[data-bio-status]');
        var statusTextEl = overlay.querySelector('[data-bio-status-text]');
        var progressEl = overlay.querySelector('[data-bio-progress]');
        var progressFillEl = overlay.querySelector('[data-bio-progress-fill]');
        var actionsEl = overlay.querySelector('[data-bio-actions]');
        var videoEl = overlay.querySelector('[data-bio-video]');

        var open = false;
        var closeHandlers = [];

        function onKeydown(event) {
            if (event.key === 'Escape') api.close();
        }

        var api = {
            overlay: overlay,
            video: videoEl,
            escapeHtml: escapeHtml,
            faceArt: function () { return FACE_ART; },
            isOpen: function () { return open; },

            open: function () {
                if (open) return;
                open = true;
                overlay.classList.add('is-open');
                document.documentElement.classList.add('bio-lock');
                document.body.classList.add('bio-lock');
                document.addEventListener('keydown', onKeydown);
            },

            close: function () {
                if (!open) return;
                open = false;
                overlay.classList.remove('is-open');
                document.documentElement.classList.remove('bio-lock');
                document.body.classList.remove('bio-lock');
                document.removeEventListener('keydown', onKeydown);
                closeHandlers.forEach(function (fn) {
                    try { fn(); } catch (error) { /* cleanup must never throw */ }
                });
            },

            onClose: function (fn) {
                if (typeof fn === 'function') closeHandlers.push(fn);
            },

            header: function (title, subtitle, iconName) {
                titleEl.textContent = title;
                subEl.textContent = subtitle || '';
                iconEl.innerHTML = ICONS[iconName] || ICONS.face;
            },

            state: function (html) {
                stateEl.innerHTML = html || '';
            },

            showCamera: function (show) {
                cameraEl.classList.toggle('is-hidden', !show);
            },

            compactCamera: function (compact) {
                cameraEl.classList.toggle('is-compact', !!compact);
            },

            cameraBusy: function (busy) {
                cameraEl.classList.toggle('is-busy', !!busy);
            },

            meta: function (show) {
                metaEl.classList.toggle('is-hidden', !show);
            },

            status: function (text, tone) {
                statusEl.classList.remove('is-warn', 'is-error');
                if (tone === 'warn' || tone === 'error' || tone === 'success') {
                    statusEl.classList.add('is-' + tone);
                }
                statusTextEl.textContent = text;
            },

            progress: function (percent, success) {
                var value = Math.max(0, Math.min(100, Number(percent) || 0));
                progressFillEl.style.width = value + '%';
                progressFillEl.classList.toggle('is-success', !!success);
            },

            progressVisible: function (visible) {
                progressEl.style.display = visible ? '' : 'none';
            },

            note: function (text) {
                if (!text) {
                    noteEl.hidden = true;
                    noteEl.textContent = '';
                } else {
                    noteEl.hidden = false;
                    noteEl.textContent = text;
                }
            },

            actions: function (items) {
                actionsEl.innerHTML = '';

                (items || []).forEach(function (item) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'bio-btn ' + (
                        item.variant === 'primary' ? 'bio-btn-primary' :
                        item.variant === 'ghost' ? 'bio-btn-ghost' :
                        'bio-btn-secondary'
                    );
                    button.innerHTML = (item.icon ? item.icon : '') +
                        '<span>' + escapeHtml(item.label) + '</span>';
                    if (item.id) button.id = item.id;
                    if (item.disabled) button.disabled = true;
                    button.addEventListener('click', function () {
                        if (button.disabled) return;
                        item.onClick();
                    });
                    actionsEl.appendChild(button);
                    if (item.ref) item.ref(button);
                });
            },

            icon: function (name) {
                iconEl.innerHTML = ICONS[name] || ICONS.face;
            }
        };

        overlay.querySelector('[data-bio-close]').addEventListener('click', function () {
            api.close();
        });

        // Click on the dimmed backdrop closes (content clicks ignored).
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) api.close();
        });

        return api;
    }

    window.BioModal = BioModal;
})();
