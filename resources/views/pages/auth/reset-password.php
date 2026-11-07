<?php
/**
 * Reset password — step 2.
 *
 * New password + confirmation -> API -> success panel -> login.
 *
 * @var string $title
 * @var string $csrfToken
 */
$csrfToken = (string) ($csrfToken ?? '');
?>
<link rel="stylesheet" href="<?= asset('/assets/css/auth-flow.css?v=2026110702') ?>">

<main class="fp-page">
    <div class="fp-wrap">
        <a class="fp-back" href="/forgot-password">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Back
        </a>

        <section class="fp-card" aria-labelledby="rpTitle">
            <div class="fp-steps" aria-hidden="true">
                <span class="fp-step is-done"></span>
                <span class="fp-step is-done"></span>
            </div>

            <!-- ==============================
                 STATE 1: NEW PASSWORD
                 ============================== -->
            <div id="rpFormState">
                <header class="fp-head">
                    <span class="fp-icon-badge"><i class="bi bi-lock" aria-hidden="true"></i></span>
                    <h1 class="fp-title" id="rpTitle">Set a new password</h1>
                    <p class="fp-subtitle" id="rpSubtitle">
                        Choose a strong password you haven&rsquo;t used before.
                    </p>
                </header>

                <form id="rpForm" novalidate>
                    <input type="hidden" id="rpCsrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <p class="fp-error" id="rpSubmitError" style="margin:0 0 14px;"></p>
                    <div class="fp-field">
                        <label class="fp-label" for="rpPassword">New password</label>
                        <div class="fp-input-wrap">
                            <i class="bi bi-lock fp-input-icon" aria-hidden="true"></i>
                            <input
                                class="fp-input has-icon"
                                type="password"
                                id="rpPassword"
                                name="password"
                                placeholder="Enter new password"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="fp-eye" data-toggle-password="rpPassword" aria-label="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p class="fp-error" id="rpPasswordError">Password must be at least 8 characters.</p>
                    </div>

                    <div class="fp-field">
                        <label class="fp-label" for="rpConfirm">Confirm new password</label>
                        <div class="fp-input-wrap">
                            <i class="bi bi-lock fp-input-icon" aria-hidden="true"></i>
                            <input
                                class="fp-input has-icon"
                                type="password"
                                id="rpConfirm"
                                name="password_confirmation"
                                placeholder="Re-enter new password"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="fp-eye" data-toggle-password="rpConfirm" aria-label="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p class="fp-error" id="rpConfirmError">Passwords do not match.</p>
                    </div>

                    <ul class="fp-reqs" aria-label="Password requirements">
                        <li class="fp-req" id="reqLength">
                            <i class="bi bi-circle" aria-hidden="true"></i>
                            At least 8 characters
                        </li>
                        <li class="fp-req" id="reqMatch">
                            <i class="bi bi-circle" aria-hidden="true"></i>
                            Both passwords match
                        </li>
                    </ul>

                    <div class="fp-actions">
                        <button type="submit" class="fp-btn">
                            Reset password
                            <i class="bi bi-check2" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ==============================
                 STATE 2: SUCCESS
                 ============================== -->
            <div class="fp-success" id="rpSuccessState">
                <span class="fp-success__icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
                <h2 class="fp-success__title">Password updated</h2>
                <p class="fp-success__text">
                    Your password has been reset successfully. You can now log in with your new password.
                </p>

                <div class="fp-actions">
                    <a class="fp-btn" href="/auth?auth=login">
                        Back to login
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
(function () {
    "use strict";

    var formState    = document.getElementById('rpFormState');
    var successState = document.getElementById('rpSuccessState');
    var form         = document.getElementById('rpForm');
    var password     = document.getElementById('rpPassword');
    var confirm      = document.getElementById('rpConfirm');
    var passwordErr  = document.getElementById('rpPasswordError');
    var confirmErr   = document.getElementById('rpConfirmError');
    var reqLength    = document.getElementById('reqLength');
    var reqMatch     = document.getElementById('reqMatch');
    var subtitle     = document.getElementById('rpSubtitle');
    var submitError  = document.getElementById('rpSubmitError');

    var CSRF_TOKEN  = document.getElementById('rpCsrf')
        ? document.getElementById('rpCsrf').value
        : '';
    var EMAIL_KEY   = 'resetEmail';
    var API_RESET   = '/api/auth/password-reset/reset';

    var resetToken = (function () {
        try {
            return new URL(window.location.href).searchParams.get('token') || '';
        } catch (e) {
            return '';
        }
    })();

    var resetEmail = (function () {
        try {
            return window.sessionStorage.getItem(EMAIL_KEY) || '';
        } catch (e) {
            return '';
        }
    })();

    /* Show the email from step 1, when known. */
    if (resetEmail) {
        subtitle.textContent = 'Choose a new password for ' + resetEmail + '.';
    }

    function apiPost(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify(payload)
        }).then(function (response) {
            return response.json().catch(function () {
                return { ok: false, error: 'Something went wrong. Please try again.' };
            }).then(function (data) {
                data.status = response.status;
                return data;
            });
        });
    }

    function showSubmitError(message) {
        submitError.textContent = message;
        submitError.classList.add('is-visible');
    }

    function hideSubmitError() {
        submitError.textContent = '';
        submitError.classList.remove('is-visible');
    }

    /* Without a valid token the backend can't apply the reset. */
    if (!resetToken) {
        showSubmitError('This reset link is invalid or has expired. Request a new code to continue.');
        document.querySelector('#rpForm button[type="submit"]').disabled = true;
    }

    /* ---------- Show / hide password ---------- */
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-toggle-password'));
            if (!input) return;

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';

            var icon = button.querySelector('i');
            if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';

            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    /* ---------- Live validation ---------- */
    function isLengthValid() {
        return password.value.length >= 8;
    }

    function isMatchValid() {
        return password.value !== '' && password.value === confirm.value;
    }

    function paintRequirement(element, met) {
        element.classList.toggle('is-met', met);
        var icon = element.querySelector('i');
        if (icon) icon.className = met ? 'bi bi-check-circle-fill' : 'bi bi-circle';
    }

    function validatePassword() {
        var valid = isLengthValid();

        password.classList.toggle('is-invalid', password.value !== '' && !valid);
        passwordErr.classList.toggle('is-visible', password.value !== '' && !valid);
        paintRequirement(reqLength, valid);

        return valid;
    }

    function validateConfirm() {
        var valid = confirm.value !== '' && isMatchValid();

        confirm.classList.toggle('is-invalid', confirm.value !== '' && !valid);
        confirmErr.classList.toggle('is-visible', confirm.value !== '' && !valid);
        paintRequirement(reqMatch, valid);

        return valid;
    }

    password.addEventListener('input', function () {
        validatePassword();
        if (confirm.value !== '') validateConfirm();
    });

    confirm.addEventListener('input', validateConfirm);

    /* ---------- Submit (hits the real API) ---------- */
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var passwordOk = validatePassword();
        var confirmOk  = validateConfirm();
        var submitBtn  = form.querySelector('button[type="submit"]');

        if (resetToken === '') {
            showSubmitError('This reset link is invalid or has expired. Request a new code to continue.');
            return;
        }

        if (!passwordOk) {
            password.focus();
            return;
        }

        if (!confirmOk) {
            confirm.focus();
            return;
        }

        hideSubmitError();

        if (submitBtn) submitBtn.disabled = true;

        apiPost(API_RESET, {
            token: resetToken,
            password: password.value,
            password_confirmation: confirm.value
        })
            .then(function (data) {
                if (data.ok) {
                    try { window.sessionStorage.removeItem(EMAIL_KEY); } catch (e) { /* no-op */ }

                    formState.style.display = 'none';
                    successState.classList.add('is-visible');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    showSubmitError(data.error || 'Something went wrong. Please try again.');

                    // Expired/invalid tokens can't be retried here —
                    // swap the form for a "start over" panel.
                    if (data.status === 400 &&
                        /expired|invalid|too many|no longer/i.test(data.error || '')) {
                        formState.style.display = 'none';

                        var retry = document.createElement('div');

                        retry.innerHTML =
                            '<header class="fp-head">' +
                              '<span class="fp-icon-badge"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>' +
                              '<h2 class="fp-success__title">Code expired</h2>' +
                              '<p class="fp-success__text">Your reset code has expired or is no longer valid. Request a new code to try again.</p>' +
                            '</header>' +
                            '<div class="fp-actions">' +
                              '<a class="fp-btn" href="/forgot-password"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Request a new code</a>' +
                            '</div>' +
                            '<div class="fp-help-row"></div>';

                        form.insertAdjacentElement('afterend', retry);
                    }
                }
            })
            .catch(function () {
                showSubmitError('Network error. Please try again.');
            })
            .finally(function () {
                if (submitBtn) submitBtn.disabled = false;
            });
    });
})();
</script>
