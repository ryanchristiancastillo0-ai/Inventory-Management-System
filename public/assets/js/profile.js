(function () {
    'use strict';
    var page = document.querySelector('main.profile-page');
    var form = document.getElementById('profile-form');
    if (!page || !form || !window.bootstrap) return;

    var edit = document.getElementById('profile-edit');
    var cancel = document.getElementById('profile-cancel');
    var fields = Array.from(form.querySelectorAll('[data-saved-value]'));
    var submit = form.querySelector('[type="submit"]');
    var status = form.querySelector('.profile-save-status');
    var initiallyEditing = page.dataset.editing === 'true';

    // Enhance the server-rendered edit form; the edit URL still works without JS.
    var display = document.createElement('div');
    form.before(display);
    display.append(form.querySelector('.profile-info-grid'), form.querySelector('.profile-form-footer'));
    var modalElement = document.createElement('div');
    modalElement.id = 'profileModal';
    modalElement.className = 'modal fade profile-page profile-edit-modal';
    modalElement.tabIndex = -1;
    modalElement.setAttribute('aria-labelledby', 'profileModalTitle');
    modalElement.setAttribute('aria-hidden', 'true');
    modalElement.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content modal-content-app"></div></div>';
    var header = document.createElement('div');
    header.className = 'modal-header-app';
    header.innerHTML = '<div><h2 class="modal-title-app" id="profileModalTitle">Edit profile</h2><p class="modal-subtitle-app">Update your username and email address.</p></div><button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg" aria-hidden="true"></i></button>';
    var body = document.createElement('div');
    body.className = 'modal-body-app';
    fields.forEach(function (field) {
        var group = field.closest('[data-edit-only]');
        group.hidden = false;
        group.classList.add('form-row');
        field.disabled = false;
        body.append(group);
    });
    var footer = document.createElement('div');
    footer.className = 'modal-footer-app';
    var actions = display.querySelector('.profile-edit-actions');
    actions.hidden = false;
    footer.append(actions);
    form.append(header, body, footer);
    modalElement.querySelector('.modal-content').append(form);
    document.body.append(modalElement);
    display.querySelectorAll('[data-view-only]').forEach(function (element) { element.hidden = false; });
    page.querySelector('.profile-editing-label').hidden = true;
    edit.hidden = false;
    edit.setAttribute('aria-controls', 'profileModal');
    edit.setAttribute('aria-expanded', 'false');
    var modal = new bootstrap.Modal(modalElement);
    var saving = false;

    edit.addEventListener('click', function (event) { event.preventDefault(); modal.show(); });
    cancel.addEventListener('click', function (event) { event.preventDefault(); modal.hide(); });
    modalElement.addEventListener('shown.bs.modal', function () {
        edit.setAttribute('aria-expanded', 'true');
        (form.querySelector('[aria-invalid="true"]') || fields[0]).focus();
    });
    modalElement.addEventListener('hide.bs.modal', function (event) { if (saving) event.preventDefault(); });
    modalElement.addEventListener('hidden.bs.modal', function () {
        fields.forEach(function (field) { field.value = field.dataset.savedValue; field.setAttribute('aria-invalid', 'false'); });
        form.querySelectorAll('.profile-field-error').forEach(function (element) { element.hidden = true; });
        status.textContent = '';
        edit.setAttribute('aria-expanded', 'false');
        edit.focus();
        if (new URL(location.href).searchParams.has('edit')) history.replaceState(null, '', '/profile');
    });
    form.addEventListener('submit', function () {
        saving = true;
        submit.disabled = true;
        status.textContent = 'Saving your profileâ€¦';
    });
    window.addEventListener('pageshow', function () { saving = false; submit.disabled = false; status.textContent = ''; });
    if (initiallyEditing) modal.show();
})();

/* =========================================================
   MOBILE NUMBER VERIFICATION (SMS sign-in)
   Endpoints (see routes/profile.php):
     POST /profile/phone/request -> { success, message, cooldown }
     POST /profile/phone/verify  -> { success, message, phoneNumber }
   ========================================================= */
(function () {
    'use strict';
    var page = document.querySelector('main.profile-page');
    var requestForm = document.getElementById('phoneRequestForm');
    var verifyForm = document.getElementById('phoneVerifyForm');
    if (!page || !requestForm || !verifyForm) return;

    var card = document.getElementById('phoneCard');
    var phoneInput = document.getElementById('profilePhoneInput');
    var sendCodeBtn = document.getElementById('phoneSendCodeBtn');
    var requestError = document.getElementById('phoneRequestError');

    var entryStep = document.getElementById('phoneEntryStep');
    var verifyStep = document.getElementById('phoneVerifyStep');
    var verifyMasked = document.getElementById('phoneVerifyMasked');
    var otpInputs = Array.prototype.slice.call(
        document.querySelectorAll('#profilePhoneOtpGroup .phone-otp__box')
    );
    var verifyError = document.getElementById('phoneVerifyError');
    var verifySubmitBtn = document.getElementById('phoneVerifySubmitBtn');
    var verifyCancelBtn = document.getElementById('phoneVerifyCancelBtn');
    var resendBtn = document.getElementById('phoneResendBtn');
    var resendCountdown = document.getElementById('phoneResendCountdown');
    var timerText = document.getElementById('phoneTimerText');

    var pendingPhone = '';
    var cooldownTimer = null;
    var expiryTimer = null;
    var TTL_SECONDS = 300;

    function showError(el, message) {
        el.textContent = message;
        el.hidden = false;
    }

    function clearError(el) {
        el.textContent = '';
        el.hidden = true;
    }

    function maskPhone(phone) {
        if (!phone || phone.length < 4) return phone || '';
        return '+63 \u2022\u2022\u2022 \u2022\u2022\u2022 ' + phone.slice(-4);
    }

    function formatTime(seconds) {
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        return m + ':' + String(s).padStart(2, '0');
    }

    function setBusy(btn, busy) {
        btn.classList.toggle('is-busy', busy);
        btn.disabled = busy;
    }

    function getOtpCode() {
        return otpInputs.map(function (input) { return input.value; }).join('');
    }

    function resetOtp() {
        otpInputs.forEach(function (input) {
            input.value = '';
            input.removeAttribute('aria-invalid');
            input.disabled = false;
        });
        clearError(verifyError);
    }

    function stopTimers() {
        if (cooldownTimer) { clearInterval(cooldownTimer); cooldownTimer = null; }
        if (expiryTimer) { clearInterval(expiryTimer); expiryTimer = null; }
    }

    function startCooldown(seconds) {
        var remaining = seconds || 60;
        resendBtn.disabled = true;
        resendCountdown.textContent = ' \u00B7 ' + remaining + 's';
        if (cooldownTimer) clearInterval(cooldownTimer);
        cooldownTimer = setInterval(function () {
            remaining--;
            if (remaining > 0) {
                resendCountdown.textContent = ' \u00B7 ' + remaining + 's';
            } else {
                clearInterval(cooldownTimer);
                cooldownTimer = null;
                resendBtn.disabled = false;
                resendCountdown.textContent = '';
            }
        }, 1000);
    }

    function startExpiry() {
        var remaining = TTL_SECONDS;
        timerText.textContent = 'Code expires in ' + formatTime(remaining);
        timerText.classList.remove('is-warning');
        if (expiryTimer) clearInterval(expiryTimer);
        expiryTimer = setInterval(function () {
            remaining--;
            timerText.textContent = 'Code expires in ' + formatTime(Math.max(remaining, 0));
            timerText.classList.toggle('is-warning', remaining <= 60);
            if (remaining <= 0) {
                clearInterval(expiryTimer);
                expiryTimer = null;
                showError(verifyError, 'The code has expired. Tap resend to get a new one.');
                otpInputs.forEach(function (input) { input.setAttribute('aria-invalid', 'true'); });
                verifySubmitBtn.disabled = true;
            }
        }, 1000);
    }

    function post(url, formData) {
        return fetch(url, { method: 'POST', body: formData })
            .then(function (response) { return response.json(); });
    }

    function renderCurrent(number) {
        if (!card) return;
        card.classList.add('is-verified');

        var badge = document.getElementById('phoneBadge');
        if (badge) badge.innerHTML = '<span class="phone-badge__dot"></span> Verified';

        var current = entryStep.querySelector('.phone-current');
        if (!current) {
            current = document.createElement('div');
            current.className = 'phone-current';
            current.innerHTML =
                '<div><span class="phone-current__label">Current number</span>' +
                '<span class="phone-current__value"></span></div>' +
                '<span class="phone-current__state is-ok">Verified</span>';
            entryStep.insertBefore(current, requestForm);
        }
        current.querySelector('.phone-current__value').textContent = number;
        var state = current.querySelector('.phone-current__state');
        state.className = 'phone-current__state is-ok';
        state.textContent = 'Verified';
    }

    /* ---------- OTP boxes: digits, backspace, paste ---------- */

    otpInputs.forEach(function (input, index) {
        input.addEventListener('input', function () {
            input.value = input.value.replace(/\D/g, '').slice(-1);
            if (input.value) {
                input.removeAttribute('aria-invalid');
                clearError(verifyError);
                if (index < otpInputs.length - 1) otpInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && !input.value && index > 0) {
                otpInputs[index - 1].focus();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                verifyForm.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });

        input.addEventListener('paste', function (event) {
            event.preventDefault();
            var digits = (event.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            if (!digits) return;
            otpInputs.forEach(function (box, i) { box.value = digits[i] || ''; });
            var nextEmpty = otpInputs.findIndex(function (box) { return !box.value; });
            (nextEmpty >= 0 ? otpInputs[nextEmpty] : otpInputs[otpInputs.length - 1]).focus();
        });
    });

    /* ---------- Step 1: request code ---------- */

    requestForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var raw = phoneInput.value.replace(/\D/g, '');

        clearError(requestError);
        if (!/^9\d{9}$/.test(raw)) {
            phoneInput.setAttribute('aria-invalid', 'true');
            showError(requestError, 'Enter a valid mobile number, e.g. 912 345 6789.');
            phoneInput.focus();
            return;
        }
        phoneInput.setAttribute('aria-invalid', 'false');

        var data = new FormData(requestForm);
        data.set('phone', '+63' + raw);

        setBusy(sendCodeBtn, true);
        post('/profile/phone/request', data)
            .then(function (result) {
                if (result.success) {
                    pendingPhone = '+63' + raw;
                    verifyMasked.textContent = maskPhone(pendingPhone);
                    resetOtp();
                    verifySubmitBtn.disabled = false;
                    entryStep.hidden = true;
                    verifyStep.hidden = false;
                    startExpiry();
                    startCooldown(result.cooldown || 60);
                    otpInputs[0].focus();
                } else {
                    showError(requestError, result.message || 'Unable to send the code.');
                    if (result.cooldown) startCooldown(result.cooldown);
                }
            })
            .catch(function () {
                showError(requestError, 'Network error. Please check your connection and try again.');
            })
            .finally(function () { setBusy(sendCodeBtn, false); });
    });

    phoneInput.addEventListener('input', function () {
        phoneInput.setAttribute('aria-invalid', 'false');
        clearError(requestError);
    });

    /* ---------- Step 2: verify code ---------- */

    verifyForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var code = getOtpCode();

        clearError(verifyError);
        if (!/^\d{6}$/.test(code)) {
            otpInputs.forEach(function (input) { input.setAttribute('aria-invalid', 'true'); });
            showError(verifyError, 'Enter the full 6-digit code.');
            (otpInputs.find(function (input) { return !input.value; }) || otpInputs[0]).focus();
            return;
        }

        var data = new FormData(verifyForm);
        data.set('code', code);

        setBusy(verifySubmitBtn, true);
        otpInputs.forEach(function (input) { input.disabled = true; });

        post('/profile/phone/verify', data)
            .then(function (result) {
                if (result.success) {
                    stopTimers();
                    verifyStep.hidden = true;
                    entryStep.hidden = false;
                    resetOtp();
                    renderCurrent(result.phoneNumber || pendingPhone);
                    showFlash('Your mobile number is verified. You can now sign in with it.');
                } else {
                    otpInputs.forEach(function (input) {
                        input.value = '';
                        input.setAttribute('aria-invalid', 'true');
                        input.disabled = false;
                    });
                    showError(verifyError, result.message || 'Verification failed.');
                    otpInputs[0].focus();
                }
            })
            .catch(function () {
                otpInputs.forEach(function (input) { input.disabled = false; });
                showError(verifyError, 'Network error. Please check your connection and try again.');
            })
            .finally(function () { setBusy(verifySubmitBtn, false); });
    });

    verifyCancelBtn.addEventListener('click', function () {
        stopTimers();
        resetOtp();
        verifyStep.hidden = true;
        entryStep.hidden = false;
        phoneInput.focus();
    });

    resendBtn.addEventListener('click', function () {
        if (resendBtn.disabled) return;
        clearError(verifyError);

        var data = new FormData();
        data.set('csrf_token', requestForm.querySelector('[name="csrf_token"]').value);
        data.set('phone', pendingPhone);

        setBusy(resendBtn, true);
        post('/profile/phone/request', data)
            .then(function (result) {
                if (result.success) {
                    resetOtp();
                    verifySubmitBtn.disabled = false;
                    startExpiry();
                    startCooldown(result.cooldown || 60);
                    otpInputs[0].focus();
                } else {
                    showError(verifyError, result.message || 'Unable to resend the code.');
                    if (result.cooldown) startCooldown(result.cooldown);
                }
            })
            .catch(function () {
                showError(verifyError, 'Network error. Please check your connection and try again.');
            })
            .finally(function () { setBusy(resendBtn, false); });
    });

    function showFlash(message) {
        var flash = document.createElement('div');
        flash.className = 'profile-feedback is-success';
        flash.setAttribute('role', 'status');
        flash.innerHTML = '<i class="bi bi-check-circle" aria-hidden="true"></i><span></span>';
        flash.querySelector('span').textContent = message;
        var header = page.querySelector('.profile-page-header');
        if (header && header.nextSibling) {
            header.parentNode.insertBefore(flash, header.nextSibling);
        } else if (header) {
            header.after(flash);
        }
        flash.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
})();
