<?php
/**
 * Forgot password — step 1 (frontend only).
 *
 * Email entry -> success panel with "Proceed" -> OTP modal ->
 * redirects to /reset-password (step 2).
 *
 * @var string $title
 * @var string $csrfToken
 */
$csrfToken = (string) ($csrfToken ?? '');
?>
<link rel="stylesheet" href="<?= asset('/assets/css/auth-flow.css?v=2026110702') ?>">

<main class="fp-page">
    <div class="fp-wrap">
        <a class="fp-back" href="/auth?auth=login">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Back to login
        </a>

        <section class="fp-card" aria-labelledby="fpTitle">
            <div class="fp-steps" aria-hidden="true">
                <span class="fp-step is-done"></span>
                <span class="fp-step"></span>
            </div>

            <!-- ==============================
                 STATE 1: EMAIL FORM
                 ============================== -->
            <div id="fpEmailState">
                <header class="fp-head">
                    <span class="fp-icon-badge"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                    <h1 class="fp-title" id="fpTitle">Forgot password?</h1>
                    <p class="fp-subtitle">
                        Enter the email linked to your account and we&rsquo;ll send you a verification code.
                    </p>
                </header>

                <form id="fpEmailForm" novalidate>
                    <input type="hidden" id="fpCsrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="fp-field">
                        <label class="fp-label" for="fpEmail">Email address</label>
                        <div class="fp-input-wrap">
                            <i class="bi bi-envelope fp-input-icon" aria-hidden="true"></i>
                            <input
                                class="fp-input has-icon"
                                type="email"
                                id="fpEmail"
                                name="email"
                                placeholder="you@example.com"
                                autocomplete="email"
                                inputmode="email"
                                required
                            >
                        </div>
                        <p class="fp-error" id="fpEmailError">Enter a valid email address.</p>
                    </div>

                    <div class="fp-actions">
                        <button type="submit" class="fp-btn">
                            Continue
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>

                <p class="fp-note">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <span>For your security, the code expires after 10 minutes and can only be used once.</span>
                </p>
            </div>

            <!-- ==============================
                 STATE 2: CHECK YOUR EMAIL
                 ============================== -->
            <div class="fp-success" id="fpSuccessState">
                <span class="fp-success__icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
                <h2 class="fp-success__title">Check your email</h2>
                <p class="fp-success__text">
                    We sent a 6-digit verification code to
                    <span class="fp-email-strong" id="fpSentEmail">your email</span>.
                    Open your inbox and enter the code to continue.
                </p>
                <p class="fp-success__sub">Don&rsquo;t see it? Check your spam or junk folder.</p>

                <div class="fp-actions">
                    <button type="button" class="fp-btn" id="fpProceedBtn">
                        Proceed
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="fp-help-row">
                    <button type="button" class="fp-btn-link" id="fpChangeEmailBtn">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                        Use a different email
                    </button>
                </div>
            </div>
        </section>
    </div>
</main>

<!-- ==========================================================
     MODAL: verification code
     ========================================================== -->
<div class="modal fade fp-modal" id="fpCodeModal" tabindex="-1" aria-labelledby="fpCodeTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <span class="fp-modal__icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                <h2 class="fp-modal__title" id="fpCodeTitle">Enter verification code</h2>
                <p class="fp-modal__text">
                    Enter the 6-digit code we sent to
                    <span class="fp-email-strong" id="fpModalEmail">your email</span>.
                </p>

                <form id="fpCodeForm" novalidate>
                    <div class="fp-otp" id="fpOtpInputs">
                        <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" aria-label="Digit 1">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 2">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 3">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 4">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 5">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 6">
                    </div>

                    <p class="fp-error" id="fpCodeError">Enter the full 6-digit code.</p>

                    <div class="fp-modal__meta">
                        <button type="button" class="fp-btn-link" id="fpResendBtn">Resend code</button>
                        <span class="fp-success__sub" id="fpResendHint"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="fp-btn fp-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="fpCodeForm" class="fp-btn" id="fpVerifyBtn">Verify code</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    "use strict";

    var emailState   = document.getElementById('fpEmailState');
    var successState = document.getElementById('fpSuccessState');
    var emailForm    = document.getElementById('fpEmailForm');
    var emailInput   = document.getElementById('fpEmail');
    var emailError   = document.getElementById('fpEmailError');
    var sentEmail    = document.getElementById('fpSentEmail');
    var modalEmail   = document.getElementById('fpModalEmail');
    var proceedBtn   = document.getElementById('fpProceedBtn');
    var changeBtn    = document.getElementById('fpChangeEmailBtn');
    var codeForm     = document.getElementById('fpCodeForm');
    var codeError    = document.getElementById('fpCodeError');
    var otpInputs    = Array.prototype.slice.call(
        document.querySelectorAll('#fpOtpInputs input')
    );
    var resendBtn    = document.getElementById('fpResendBtn');
    var resendHint   = document.getElementById('fpResendHint');
    var modalEl      = document.getElementById('fpCodeModal');
    var codeModal    = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;
    var resendTimer  = null;

    var CSRF_TOKEN  = document.getElementById('fpCsrf')
        ? document.getElementById('fpCsrf').value
        : '';
    var EMAIL_KEY   = 'resetEmail';
    var API_REQUEST = '/api/auth/password-reset/request';
    var API_VERIFY  = '/api/auth/password-reset/verify';

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

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);
    }

    function showState(state) {
        emailState.style.display   = state === 'email' ? '' : 'none';
        successState.classList.toggle('is-visible', state === 'success');
    }

    /* ---------- Step 1: submit email (hits the real API) ---------- */
    emailForm.addEventListener('submit', function (event) {
        event.preventDefault();

        var email = emailInput.value.trim();

        if (!isValidEmail(email)) {
            emailInput.classList.add('is-invalid');
            emailError.textContent = 'Enter a valid email address.';
            emailError.classList.add('is-visible');
            emailInput.focus();
            return;
        }

        emailInput.classList.remove('is-invalid');
        emailError.classList.remove('is-visible');

        var submitButton = emailForm.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        apiPost(API_REQUEST, { email: email })
            .then(function (data) {
                if (data.ok) {
                    sentEmail.textContent = email;
                    modalEmail.textContent = email;

                    try { window.sessionStorage.setItem(EMAIL_KEY, email); } catch (e) { /* no-op */ }

                    showState('success');

                    // Keep the email in the URL for the reset page display.
                    try {
                        var url = new URL(window.location.href);
                        url.searchParams.set('email', email);
                        window.history.replaceState({}, '', url.toString());
                    } catch (e) { /* no-op */ }
                } else {
                    emailInput.classList.add('is-invalid');
                    emailError.textContent = data.error || 'Something went wrong. Please try again.';
                    emailError.classList.add('is-visible');
                }
            })
            .catch(function () {
                emailError.textContent = 'Network error. Please try again.';
                emailError.classList.add('is-visible');
            })
            .finally(function () {
                if (submitButton) submitButton.disabled = false;
            });
    });

    emailInput.addEventListener('input', function () {
        if (emailInput.classList.contains('is-invalid')) {
            emailInput.classList.remove('is-invalid');
            emailError.classList.remove('is-visible');
        }
    });

    /* ---------- Use a different email ---------- */
    changeBtn.addEventListener('click', function () {
        showState('email');
        emailInput.focus();
    });

    /* ---------- Proceed -> OTP modal ---------- */
    proceedBtn.addEventListener('click', function () {
        resetOtp();
        codeError.classList.remove('is-visible');
        codeError.textContent = 'Enter the full 6-digit code.';

        if (codeModal) {
            codeModal.show();
        }

        window.setTimeout(function () {
            if (otpInputs[0]) otpInputs[0].focus();
        }, 250);
    });

    /* ---------- OTP behaviour ---------- */
    function otpValue() {
        return otpInputs.map(function (input) { return input.value; }).join('');
    }

    function resetOtp() {
        otpInputs.forEach(function (input) {
            input.value = '';
            input.classList.remove('is-invalid');
        });
    }

    otpInputs.forEach(function (input, index) {
        input.addEventListener('input', function () {
            input.value = input.value.replace(/\D/g, '').slice(0, 1);
            codeError.classList.remove('is-visible');
            otpInputs.forEach(function (el) { el.classList.remove('is-invalid'); });

            if (input.value !== '' && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && input.value === '' && index > 0) {
                otpInputs[index - 1].focus();
                otpInputs[index - 1].value = '';
                event.preventDefault();
            }

            if (event.key === 'ArrowLeft' && index > 0) {
                otpInputs[index - 1].focus();
                event.preventDefault();
            }

            if (event.key === 'ArrowRight' && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
                event.preventDefault();
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                codeForm.requestSubmit
                    ? codeForm.requestSubmit()
                    : codeForm.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });

        input.addEventListener('paste', function (event) {
            var text = (event.clipboardData || window.clipboardData).getData('text') || '';
            var digits = text.replace(/\D/g, '').slice(0, 6);

            if (digits === '') return;

            event.preventDefault();

            digits.split('').forEach(function (digit, i) {
                if (otpInputs[i]) otpInputs[i].value = digit;
            });

            var next = Math.min(digits.length, otpInputs.length - 1);
            otpInputs[next].focus();
        });
    });

    /* ---------- Verify code (hits the real API) ---------- */
    codeForm.addEventListener('submit', function (event) {
        event.preventDefault();

        var code = otpValue();
        var email = emailInput.value.trim()
            || (function () { try { return window.sessionStorage.getItem(EMAIL_KEY) || ''; } catch (e) { return ''; } })();
        var verifyButton = document.getElementById('fpVerifyBtn');

        if (!/^\d{6}$/.test(code)) {
            codeError.classList.add('is-visible');
            codeError.textContent = 'Enter the full 6-digit code.';
            otpInputs.forEach(function (el) { el.classList.add('is-invalid'); });
            otpInputs[0].focus();
            return;
        }

        if (verifyButton) verifyButton.disabled = true;

        apiPost(API_VERIFY, { email: email, code: code })
            .then(function (data) {
                if (data.ok && data.token) {
                    if (codeModal) codeModal.hide();

                    showState('email'); // prevents a flash of the old form on navigation

                    var target = '/reset-password?token=' + encodeURIComponent(data.token);
                    window.location.href = target;
                } else {
                    codeError.classList.add('is-visible');
                    codeError.textContent = data.error || 'Something went wrong. Please try again.';
                    otpInputs.forEach(function (el) { el.classList.add('is-invalid'); });
                    resetOtp();
                    if (otpInputs[0]) otpInputs[0].focus();
                }
            })
            .catch(function () {
                codeError.classList.add('is-visible');
                codeError.textContent = 'Network error. Please try again.';
            })
            .finally(function () {
                if (verifyButton) verifyButton.disabled = false;
            });
    });

    /* ---------- Resend (simulated) ---------- */
    function startResendCooldown() {
        var seconds = 30;

        resendBtn.disabled = true;
        resendHint.textContent = 'Code resent';

        if (resendTimer) window.clearInterval(resendTimer);

        resendTimer = window.setInterval(function () {
            seconds -= 1;

            if (seconds <= 0) {
                window.clearInterval(resendTimer);
                resendTimer = null;
                resendBtn.disabled = false;
                resendHint.textContent = '';
                return;
            }

            resendHint.textContent = 'Resend in ' + seconds + 's';
        }, 1000);
    }

    resendBtn.addEventListener('click', function () {
        var email = emailInput.value.trim()
            || (function () { try { return window.sessionStorage.getItem(EMAIL_KEY) || ''; } catch (e) { return ''; } })();

        if (!isValidEmail(email)) {
            codeError.classList.add('is-visible');
            codeError.textContent = 'Please go back and enter your email again.';
            return;
        }

        resendBtn.disabled = true;
        codeError.classList.remove('is-visible');
        resetOtp();

        apiPost(API_REQUEST, { email: email })
            .then(function (data) {
                if (data.ok) {
                    startResendCooldown();
                    if (otpInputs[0]) otpInputs[0].focus();
                } else {
                    codeError.classList.add('is-visible');
                    codeError.textContent = data.error || 'Could not resend the code. Please try again.';
                    resendBtn.disabled = false;
                }
            })
            .catch(function () {
                codeError.classList.add('is-visible');
                codeError.textContent = 'Network error. Please try again.';
                resendBtn.disabled = false;
            });
    });

    /* Auto-focus first OTP box whenever the modal opens */
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            if (otpInputs[0]) otpInputs[0].focus();
        });
    }
})();
</script>
