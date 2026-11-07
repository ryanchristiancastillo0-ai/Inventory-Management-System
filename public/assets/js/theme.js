/* ================================================================
   THEME JS — exposes window.AppTheme for the Settings preferences
   UI (settings-preferences.js) and keeps the html attribute in
   sync with the user's last choice between reloads.
   Reconstructed from the settings page contract:
     AppTheme.get(), AppTheme.apply('SYSTEM'|'LIGHT'|'DARK')
   ================================================================ */
(function () {
    'use strict';

    var KEY = 'inventory.themePreference';

    function valid(preference) {
        return preference === 'SYSTEM' || preference === 'LIGHT' || preference === 'DARK';
    }

    function get() {
        return document.documentElement.getAttribute('data-theme-preference') || 'SYSTEM';
    }

    function apply(preference) {
        if (!valid(preference)) preference = 'SYSTEM';
        document.documentElement.setAttribute('data-theme-preference', preference);
        try { localStorage.setItem(KEY, preference); } catch (error) { /* private mode */ }
        document.dispatchEvent(new CustomEvent('appthemechange', { detail: { theme: preference } }));
    }

    /* On load, re-apply the locally remembered choice (the server value
       is already there; localStorage only wins if it differs). */
    try {
        var saved = localStorage.getItem(KEY);
        if (saved && valid(saved) && saved !== get()) apply(saved);
    } catch (error) { /* storage unavailable */ }

    window.AppTheme = { get: get, apply: apply };
})();
