(function () {
    'use strict';

    var form = document.getElementById('preferences-form');
    if (!form || !window.AppTheme) return;

    var themeInputs = form.querySelectorAll('[name="theme"]');
    var emailInput = form.elements.email_notifications;
    var submit = form.querySelector('[type="submit"]');
    var themeSummary = document.getElementById('summary-theme');
    var emailSummary = document.getElementById('summary-email-alerts');
    var status = document.createElement('p');
    status.className = 'preferences-save-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    submit.before(status);
    // Keep the native submit available without JavaScript; enhanced controls autosave.
    submit.hidden = true;

    var saved = readPreferences();
    var pending = null;
    var saving = false;

    function readPreferences() {
        return {
            theme: form.querySelector('[name="theme"]:checked').value,
            email_notifications: emailInput.checked ? 1 : 0
        };
    }

    function render(preferences) {
        window.AppTheme.apply(preferences.theme);
        themeInputs.forEach(function (input) { input.checked = input.value === preferences.theme; });
        emailInput.checked = preferences.email_notifications === 1;
        themeSummary.textContent = preferences.theme.charAt(0) + preferences.theme.slice(1).toLowerCase();
        emailSummary.textContent = emailInput.checked ? 'Enabled' : 'Disabled';
    }

    function setStatus(message, isError, state) {
        status.textContent = message;
        status.classList.toggle('is-error', !!isError);
        status.dataset.state = isError ? 'error' : (state || 'idle');
    }

    async function save() {
        if (saving) return;
        saving = true;
        submit.disabled = true;

        // Serialize writes and coalesce rapid edits so an older request cannot win.
        while (pending) {
            var requested = pending;
            pending = null;
            setStatus('Saving…', false, 'saving');

            var body = new URLSearchParams();
            body.set('_csrf', form.elements._csrf.value);
            body.set('theme', requested.theme);
            body.set('email_notifications', String(requested.email_notifications));

            try {
                var response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                    body: body,
                    keepalive: true
                });
                var data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to save your preferences.');
                }
                saved = data.settings;
                if (!pending) {
                    render(saved);
                    setStatus('Preferences saved.', false, 'saved');
                }
            } catch (error) {
                // A newer selection stays visible while its own save is queued.
                if (!pending) {
                    render(saved);
                    setStatus('Save failed. Previous preferences restored.', true);
                    if (window.showToast) {
                        window.showToast(error.message || 'Unable to save your preferences.', 'bi-exclamation-circle', true);
                    }
                }
            }
        }

        saving = false;
        submit.disabled = false;
    }

    function update() {
        pending = readPreferences();
        render(pending);
        save();
    }

    form.addEventListener('change', function (event) {
        if (event.target.name === 'theme' || event.target.name === 'email_notifications') update();
    });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        update();
    });

    render(saved);
    setStatus('Changes save automatically.');
})();
