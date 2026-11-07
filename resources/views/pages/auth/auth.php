<?php
/**
 * auth/auth.php — single-page login + register.
 *
 * The active panel is decided by the server via ?auth=login|register,
 * and can be switched client-side by the tab buttons (URL is kept in sync).
 *
 * @var string      $csrfToken
 * @var string      $activeTab   'login' | 'register'
 * @var string|null $error
 * @var string|null $success
 * @var array       $old
 */

$activeTab  = (($activeTab ?? 'login') === 'register') ? 'register' : 'login';
$isRegister = $activeTab === 'register';

$error   = $error   ?? null;
$success = $success ?? null;
$old     = is_array($old ?? null) ? $old : [];

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>Inventory. — <?= $isRegister ? 'Sign up' : 'Log in' ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  

  <style>
    /* ==========================================================================
       1. CSS VARIABLES
       ========================================================================== */
    :root {
      --auth-bg: #F5F6F8;
      --auth-surface: #FFFFFF;
      --auth-subtle: #F5F6F8;

      --auth-text: #060609;
      --auth-text-secondary: #555C6A;
      --auth-label: #2B2E37;
      --auth-muted: #9697A0;
      --auth-icon: #82848F;
      --auth-gray: #A4A5AA;

      --auth-border: #E7E9ED;
      --auth-border-hover: #CBCED5;
      --auth-border-focus: #3A3F4B;
      --auth-ring: rgba(6, 6, 9, 0.08);

      --auth-primary: #060609;
      --auth-primary-hover: #262831;
      --auth-primary-text: #FFFFFF;
      --auth-danger: #B42318;

      --auth-radius: 20px;
      --auth-radius-field: 10px;
      --auth-field-h: 48px;
      --auth-shadow: 0 12px 40px rgba(6, 6, 9, 0.05);

      --auth-font: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      --auth-dur: 180ms;
      --auth-ease: cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ==========================================================================
       2. BASE
       ========================================================================== */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      background: var(--auth-bg);
      color: var(--auth-text);
      font-family: var(--auth-font);
      font-size: 14px;
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      text-rendering: optimizeLegibility;
    }

    /* ==========================================================================
       3. PAGE LAYOUT
       ========================================================================== */
    .auth-page {
      min-height: 100vh;
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 24px;
    }

    .auth-container {
      --bs-gutter-x: 0;
      width: 100%;
      max-width: 464px;
      margin: 0 auto;
      padding-inline: 0;
    }

    /* ==========================================================================
       4. AUTHENTICATION CARD
       ========================================================================== */
    .auth-card {
      background: var(--auth-surface);
      border: 1px solid var(--auth-border);
      border-radius: var(--auth-radius);
      box-shadow: var(--auth-shadow);
      padding: 40px;
    }

    .auth-panel {
      animation: auth-panel-in var(--auth-dur) var(--auth-ease) both;
    }

    @keyframes auth-panel-in {
      from { opacity: 0; transform: translateY(4px); }
      to   { opacity: 1; transform: none; }
    }

    /* ==========================================================================
       5. BRAND
       ========================================================================== */
    .auth-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 24px;
      color: var(--auth-text);
    }

    .auth-brand__mark {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: none;
      width: 30px;
      height: 30px;
      border-radius: 9px;
      background: var(--auth-primary);
      color: #FFFFFF;
    }

    .auth-brand__mark svg {
      display: block;
      width: 16px;
      height: 16px;
    }

    .auth-brand__name {
      font-size: 18px;
      font-weight: 700;
      line-height: 1;
      letter-spacing: -0.4px;
    }

    .auth-brand__dot {
      color: var(--auth-gray);
    }

    /* ==========================================================================
       6. TAB NAVIGATION
       ========================================================================== */
    .auth-tabs {
      position: relative;
      display: grid;
      grid-template-columns: 1fr 1fr;
      padding: 4px;
      background: var(--auth-subtle);
      border-radius: 12px;
    }

    .auth-tabs__thumb {
      position: absolute;
      top: 4px;
      bottom: 4px;
      left: 4px;
      width: calc((100% - 8px) / 2);
      background: var(--auth-surface);
      border-radius: 9px;
      box-shadow: 0 1px 2px rgba(6, 6, 9, 0.08), 0 0 0 1px rgba(6, 6, 9, 0.04);
      transition: transform var(--auth-dur) var(--auth-ease);
      pointer-events: none;
    }

    /* server + client both use "register" now */
    .auth-tabs[data-active="register"] .auth-tabs__thumb {
      transform: translateX(100%);
    }

    .auth-tab {
      position: relative;
      z-index: 1;
      height: 38px;
      padding: 0 12px;
      border: 0;
      border-radius: 9px;
      background: transparent;
      color: var(--auth-text-secondary);
      font: 500 14px/1 var(--auth-font);
      cursor: pointer;
      transition: color var(--auth-dur) var(--auth-ease);
    }

    .auth-tab:hover,
    .auth-tab[aria-selected="true"] {
      color: var(--auth-text);
    }

    /* ==========================================================================
       7. FLASH MESSAGES
       ========================================================================== */
    .auth-alert {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin-top: 20px;
      padding: 12px 14px;
      border: 1px solid;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 500;
      line-height: 1.45;
    }

    .auth-alert[hidden] {
      display: none !important;
    }

    .auth-alert p {
      margin: 0;
    }

    .auth-alert svg {
      display: block;
      flex: none;
      width: 18px;
      height: 18px;
      margin-top: 1px;
    }

    .auth-alert--error {
      background: #FEF3F2;
      border-color: #FECDCA;
      color: var(--auth-danger);
    }

    .auth-alert--success {
      background: #ECFDF3;
      border-color: #ABEFC6;
      color: #067647;
    }

    /* ==========================================================================
       8. HEADINGS
       ========================================================================== */
    .auth-heading {
      margin-top: 26px;
    }

    .auth-title {
      margin: 0;
      color: var(--auth-text);
      font-size: 28px;
      font-weight: 700;
      line-height: 1.2;
      letter-spacing: -0.8px;
    }

    .auth-subtitle {
      margin: 7px 0 0;
      color: var(--auth-text-secondary);
      font-size: 14px;
      font-weight: 400;
      line-height: 1.6;
    }

    /* ==========================================================================
       9. FORM CONTROLS
       ========================================================================== */
    .auth-fields {
      display: grid;
      gap: 17px;
      margin-top: 26px;
    }

    .auth-field {
      min-width: 0;
    }

    .auth-label {
      display: block;
      margin: 0 0 7px;
      color: var(--auth-label);
      font-size: 13px;
      font-weight: 600;
      line-height: 1.3;
    }

    .auth-field__row {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 7px;
    }

    .auth-field__row .auth-label {
      margin: 0;
    }

    /* Shared visual box: plain input + password group */
    .auth-input,
    .auth-input-group {
      width: 100%;
      height: var(--auth-field-h);
      background: var(--auth-surface);
      border: 1px solid var(--auth-border);
      border-radius: var(--auth-radius-field);
      color: var(--auth-text);
      font: 400 14px/1.4 var(--auth-font);
      transition: border-color var(--auth-dur) var(--auth-ease),
                  box-shadow var(--auth-dur) var(--auth-ease);
    }

    .auth-input {
      display: block;
      padding: 0 15px;
      -webkit-appearance: none;
      appearance: none;
    }

    .auth-input::placeholder,
    .auth-input-group__field::placeholder {
      color: var(--auth-muted);
      opacity: 1;
    }

    .auth-input:hover,
    .auth-input-group:hover {
      border-color: var(--auth-border-hover);
    }

    .auth-input:user-invalid {
      border-color: var(--auth-danger);
    }

    .auth-input-group:has(.auth-input-group__field:user-invalid) {
      border-color: var(--auth-danger);
    }

    .auth-input:focus,
    .auth-input-group:focus-within {
      outline: 0;
      border-color: var(--auth-border-focus);
      box-shadow: 0 0 0 3px var(--auth-ring);
    }

    /* Password group: input + toggle, laid out with flexbox (no overlap) */
    .auth-input-group {
      display: flex;
      align-items: center;
      padding-right: 6px;
    }

    .auth-input-group__field {
      flex: 1 1 auto;
      min-width: 0;
      height: 100%;
      padding: 0 0 0 15px;
      border: 0;
      background: transparent;
      color: inherit;
      font: inherit;
      outline: 0;
    }

    .auth-input:-webkit-autofill,
    .auth-input-group__field:-webkit-autofill {
      -webkit-text-fill-color: var(--auth-text);
      caret-color: var(--auth-text);
      transition: background-color 9999s ease-in-out 0s;
    }

    .auth-toggle {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: none;
      width: 36px;
      height: 36px;
      padding: 0;
      border: 0;
      border-radius: 8px;
      background: transparent;
      color: var(--auth-icon);
      cursor: pointer;
      transition: color var(--auth-dur) var(--auth-ease),
                  background-color var(--auth-dur) var(--auth-ease);
    }

    .auth-toggle:hover {
      color: var(--auth-text);
      background: var(--auth-subtle);
    }

    .auth-toggle svg {
      display: block;
      width: 18px;
      height: 18px;
    }

    .auth-toggle .icon-hide { display: none; }
    .auth-toggle.is-visible .icon-show { display: none; }
    .auth-toggle.is-visible .icon-hide { display: block; }

    .auth-help {
      margin: 7px 0 0;
      color: var(--auth-text-secondary);
      font-size: 12px;
      font-weight: 400;
      line-height: 1.5;
    }

    .auth-link-muted {
      color: var(--auth-text-secondary);
      font-size: 12px;
      font-weight: 500;
      line-height: 1.3;
      text-decoration: none;
      border-radius: 4px;
      transition: color var(--auth-dur) var(--auth-ease);
    }

    .auth-link-muted:hover {
      color: var(--auth-text);
      text-decoration: underline;
      text-underline-offset: 3px;
    }

    /* Remember me */
    .auth-check {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 14px;
    }

    .auth-check .form-check-input {
      flex: none;
      float: none;
      width: 16px;
      height: 16px;
      margin: 0;
      border: 1px solid #BFC2CA;
      border-radius: 5px;
      background-color: var(--auth-surface);
      cursor: pointer;
      transition: background-color var(--auth-dur) var(--auth-ease),
                  border-color var(--auth-dur) var(--auth-ease),
                  box-shadow var(--auth-dur) var(--auth-ease);
    }

    .auth-check .form-check-input:checked {
      background-color: var(--auth-primary);
      border-color: var(--auth-primary);
    }

    .auth-check .form-check-input:focus {
      border-color: var(--auth-border-focus);
      box-shadow: 0 0 0 3px var(--auth-ring);
    }

    .auth-check .form-check-label {
      margin: 0;
      padding: 4px 0;
      color: var(--auth-text-secondary);
      font-size: 13px;
      font-weight: 500;
      line-height: 1.3;
      cursor: pointer;
      user-select: none;
    }

    /* ==========================================================================
       10. PRIMARY BUTTON
       ========================================================================== */
    .auth-primary-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 48px;
      margin-top: 22px;
      padding: 0 20px;
      border: 0;
      border-radius: 10px;
      background: var(--auth-primary);
      color: var(--auth-primary-text);
      font: 600 14px/1 var(--auth-font);
      cursor: pointer;
      transition: background-color var(--auth-dur) var(--auth-ease),
                  transform var(--auth-dur) var(--auth-ease);
    }

    .auth-primary-btn:hover {
      background: var(--auth-primary-hover);
    }

    .auth-primary-btn:active {
      background: #000000;
      transform: translateY(1px);
    }

    /* ==========================================================================
       11. ALTERNATIVE AUTH — SMS OTP
       ========================================================================== */
    .auth-alt {
      margin-top: 24px;
    }

    .auth-divider {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 0 0 20px;
      color: var(--auth-muted);
      font-size: 12px;
      font-weight: 500;
      line-height: 1.2;
    }

    .auth-divider::before,
    .auth-divider::after {
      content: "";
      flex: 1 1 0;
      height: 1px;
      background: var(--auth-border);
    }

    .auth-sms-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      height: 46px;
      padding: 0 12px;
      border: 1px solid var(--auth-border);
      border-radius: 10px;
      background: var(--auth-surface);
      color: var(--auth-text);
      font: 500 13px/1 var(--auth-font);
      white-space: nowrap;
      cursor: pointer;
      transition: background-color var(--auth-dur) var(--auth-ease),
                  border-color var(--auth-dur) var(--auth-ease);
    }

    .auth-sms-btn:hover {
      background: #FAFAFB;
      border-color: var(--auth-border-hover);
    }

    .auth-sms-btn:active {
      background: var(--auth-subtle);
    }

    .auth-sms-btn svg {
      display: block;
      flex: none;
      width: 18px;
      height: 18px;
    }

    .auth-social-btn svg {
      display: block;
      flex: none;
      width: 18px;
      height: 18px;
    }

    .auth-social-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      height: 46px;
      padding: 0 12px;
      border: 1px solid var(--auth-border);
      border-radius: 10px;
      background: var(--auth-surface);
      color: var(--auth-text);
      font: 500 13px/1 var(--auth-font);
      white-space: nowrap;
      cursor: pointer;
      transition: background-color var(--auth-dur) var(--auth-ease),
                  border-color var(--auth-dur) var(--auth-ease);
    }

    .auth-social-btn:hover {
      background: #FAFAFB;
      border-color: var(--auth-border-hover);
    }

    .auth-social-btn:active {
      background: var(--auth-subtle);
    }

    /* Face login: multi-account chooser rows */
    .bio-account-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      width: 100%;
    }
    .bio-account-row {
      display: flex;
      align-items: center;
      gap: 12px;
      width: 100%;
      padding: 12px 14px;
      border: 1px solid var(--bio-border, #eaeae7);
      border-radius: 14px;
      background: var(--bio-bg, #fff);
      color: var(--bio-text, #171717);
      font: inherit;
      cursor: pointer;
      text-align: left;
      transition: border-color 140ms ease, background-color 140ms ease,
                  transform 140ms ease;
    }
    .bio-account-row:hover:not(:disabled) {
      border-color: var(--bio-border-strong, #e0e0db);
      background: var(--bio-bg-soft, #f7f7f5);
    }
    .bio-account-row:active:not(:disabled) { transform: scale(0.99); }
    .bio-account-row:disabled { opacity: .55; cursor: not-allowed; }
    .bio-account-row.is-busy {
      border-color: var(--bio-primary, #171717);
      opacity: .75;
    }
    .bio-account-avatar {
      flex: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--bio-bg-soft, #f7f7f5);
      border: 1px solid var(--bio-border, #eaeae7);
      font-size: 15px;
      font-weight: 600;
      color: var(--bio-muted, #777777);
    }
    .bio-account-copy {
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .bio-account-name {
      font-size: 14px;
      font-weight: 600;
      color: var(--bio-text, #171717);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .bio-account-email {
      font-size: 12px;
      color: var(--bio-muted, #777777);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .bio-account-role {
      margin-left: auto;
      flex: none;
      padding: 4px 9px;
      border-radius: 999px;
      background: var(--bio-bg-soft, #f7f7f5);
      border: 1px solid var(--bio-border, #eaeae7);
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .06em;
      color: var(--bio-muted, #777777);
    }
    .bio-account-role.is-admin {
      color: var(--bio-success, #16865d);
      background: var(--bio-success-soft, #e8f5ef);
      border-color: transparent;
    }

    /* ==========================================================================
       11b. SMS OTP MODAL
       ========================================================================== */
    .sms-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(6, 6, 9, 0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: opacity var(--auth-dur) var(--auth-ease),
                  visibility var(--auth-dur) var(--auth-ease);
    }

    .sms-modal-overlay.is-open {
      opacity: 1;
      visibility: visible;
    }

    .sms-modal {
      background: var(--auth-surface);
      border: 1px solid var(--auth-border);
      border-radius: var(--auth-radius);
      box-shadow: var(--auth-shadow);
      width: 100%;
      max-width: 400px;
      max-height: 90vh;
      overflow-y: auto;
      transform: translateY(16px) scale(0.96);
      transition: transform var(--auth-dur) var(--auth-ease);
    }

    .sms-modal-overlay.is-open .sms-modal {
      transform: none;
    }

    .sms-modal__header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px 12px;
      border-bottom: 1px solid var(--auth-border);
    }

    .sms-modal__title {
      margin: 0;
      font-size: 18px;
      font-weight: 700;
      color: var(--auth-text);
      line-height: 1.3;
    }

    .sms-modal__close {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border: 0;
      border-radius: 8px;
      background: transparent;
      color: var(--auth-icon);
      cursor: pointer;
      transition: background-color var(--auth-dur) var(--auth-ease),
                  color var(--auth-dur) var(--auth-ease);
    }

    .sms-modal__close:hover {
      background: var(--auth-subtle);
      color: var(--auth-text);
    }

    .sms-modal__close svg {
      width: 18px;
      height: 18px;
    }

    .sms-modal__body {
      padding: 20px 24px;
    }

    .sms-modal__step {
      display: none;
    }

    .sms-modal__step.is-active {
      display: block;
      animation: auth-panel-in var(--auth-dur) var(--auth-ease) both;
    }

    .sms-modal__subtitle {
      margin: 0 0 20px;
      color: var(--auth-text-secondary);
      font-size: 14px;
      line-height: 1.6;
    }

    /* Network-support notice for the SMS provider (UniSMS) */
    .sms-modal__network-note {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin: 12px 0 4px;
      padding: 10px 12px;
      border: 1px solid var(--auth-border);
      border-radius: 10px;
      background: var(--auth-subtle);
      color: var(--auth-text-secondary);
      font-size: 12px;
      line-height: 1.55;
    }
    .sms-modal__network-note svg {
      flex: none;
      width: 15px;
      height: 15px;
      margin-top: 2px;
      color: var(--auth-icon, var(--auth-text-secondary));
    }

    .sms-modal__phone-display {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 14px;
      background: var(--auth-subtle);
      border-radius: var(--auth-radius-field);
      font-size: 15px;
      font-weight: 500;
      color: var(--auth-text);
      margin-bottom: 20px;
    }

    .sms-modal__phone-display svg {
      flex: none;
      width: 18px;
      height: 18px;
      color: var(--auth-icon);
    }

    .sms-modal__otp-inputs {
      display: flex;
      gap: 8px;
      justify-content: center;
      margin-bottom: 20px;
    }

    .sms-modal__otp-input {
      width: 44px;
      height: 52px;
      border: 1px solid var(--auth-border);
      border-radius: var(--auth-radius-field);
      background: var(--auth-surface);
      color: var(--auth-text);
      font: 700 20px/1 var(--auth-font);
      text-align: center;
      transition: border-color var(--auth-dur) var(--auth-ease),
                  box-shadow var(--auth-dur) var(--auth-ease);
    }

    .sms-modal__otp-input:hover {
      border-color: var(--auth-border-hover);
    }

    .sms-modal__otp-input:focus {
      outline: 0;
      border-color: var(--auth-border-focus);
      box-shadow: 0 0 0 3px var(--auth-ring);
    }

    .sms-modal__otp-input::placeholder {
      color: var(--auth-muted);
    }

    .sms-modal__otp-input[aria-invalid="true"] {
      border-color: var(--auth-danger);
    }

    .sms-modal__timer {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-bottom: 20px;
      color: var(--auth-text-secondary);
      font-size: 13px;
    }

    .sms-modal__timer svg {
      width: 16px;
      height: 16px;
      color: var(--auth-icon);
    }

    .sms-modal__timer.is-warning {
      color: var(--auth-danger);
    }

    .sms-modal__error {
      display: none;
      align-items: center;
      gap: 8px;
      padding: 10px 12px;
      background: #FEF3F2;
      border: 1px solid #FECDCA;
      border-radius: var(--auth-radius-field);
      color: var(--auth-danger);
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 16px;
    }

    .sms-modal__error.is-visible {
      display: flex;
    }

    .sms-modal__error svg {
      flex: none;
      width: 16px;
      height: 16px;
    }

    .sms-modal__actions {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .sms-modal__btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      height: 44px;
      padding: 0 16px;
      border: 0;
      border-radius: 10px;
      font: 600 14px/1 var(--auth-font);
      cursor: pointer;
      transition: background-color var(--auth-dur) var(--auth-ease),
                  transform var(--auth-dur) var(--auth-ease);
    }

    .sms-modal__btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .sms-modal__btn--primary {
      background: var(--auth-primary);
      color: var(--auth-primary-text);
    }

    .sms-modal__btn--primary:hover:not(:disabled) {
      background: var(--auth-primary-hover);
    }

    .sms-modal__btn--primary:active:not(:disabled) {
      transform: translateY(1px);
    }

    .sms-modal__btn--ghost {
      background: transparent;
      color: var(--auth-text);
      border: 1px solid var(--auth-border);
    }

    .sms-modal__btn--ghost:hover:not(:disabled) {
      background: var(--auth-subtle);
      border-color: var(--auth-border-hover);
    }

    .sms-modal__resend {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 12px;
      color: var(--auth-text-secondary);
      font-size: 13px;
    }

    .sms-modal__resend-link {
      background: none;
      border: 0;
      padding: 0;
      color: var(--auth-primary);
      font: 600 13px/1 var(--auth-font);
      cursor: pointer;
      text-decoration: none;
    }

    .sms-modal__resend-link:hover:not(:disabled) {
      text-decoration: underline;
    }

    .sms-modal__resend-link:disabled {
      color: var(--auth-muted);
      cursor: not-allowed;
      text-decoration: none;
    }

    .sms-modal__change-number {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid var(--auth-border);
      color: var(--auth-text-secondary);
      font-size: 13px;
    }

    .sms-modal__change-link {
      background: none;
      border: 0;
      padding: 0;
      color: var(--auth-primary);
      font: 600 13px/1 var(--auth-font);
      cursor: pointer;
      text-decoration: none;
    }

    .sms-modal__change-link:hover {
      text-decoration: underline;
    }

    .sms-modal__success {
      text-align: center;
      padding: 20px 0;
    }

    .sms-modal__success-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: #ECFDF3;
      color: #067647;
      margin-bottom: 16px;
    }

    .sms-modal__success-icon svg {
      width: 28px;
      height: 28px;
    }

    .sms-modal__success-title {
      margin: 0 0 8px;
      font-size: 20px;
      font-weight: 700;
      color: var(--auth-text);
    }

    .sms-modal__success-text {
      margin: 0;
      color: var(--auth-text-secondary);
      font-size: 14px;
    }

    /* Loader for buttons */
    .sms-modal__btn-loader {
      display: none;
      width: 18px;
      height: 18px;
      border: 2px solid currentColor;
      border-right-color: transparent;
      border-radius: 50%;
      animation: sms-spin 0.75s linear infinite;
    }

    .sms-modal__btn.is-loading .sms-modal__btn-text {
      display: none;
    }

    .sms-modal__btn.is-loading .sms-modal__btn-loader {
      display: block;
    }

    @keyframes sms-spin {
      to { transform: rotate(360deg); }
    }

    @media (max-width: 575.98px) {
      .sms-modal {
        max-height: 95vh;
        border-radius: 18px;
      }

      .sms-modal__otp-input {
        width: 38px;
        height: 48px;
        font-size: 18px;
      }
    }

    /* ==========================================================================
       12. FOOTER
       ========================================================================== */
    .auth-footer {
      margin-top: 24px;
      color: var(--auth-text-secondary);
      font-size: 13px;
      line-height: 1.5;
      text-align: center;
    }

    .auth-footer p {
      margin: 0;
    }

    .auth-link {
      padding: 0;
      border: 0;
      border-radius: 4px;
      background: none;
      color: var(--auth-text);
      font: 600 13px/1.5 var(--auth-font);
      text-decoration: none;
      cursor: pointer;
    }

    .auth-link:hover {
      text-decoration: underline;
      text-underline-offset: 3px;
    }

    /* ==========================================================================
       13. INTERACTIVE STATES
       ========================================================================== */
    .auth-page :is(a, button):focus-visible {
      outline: 2px solid var(--auth-text);
      outline-offset: 2px;
    }

    .auth-page .auth-toggle:focus-visible {
      outline-offset: 0;
    }

    /* ==========================================================================
       14. RESPONSIVE + MOTION
       ========================================================================== */
    @media (max-width: 767.98px) {
      .auth-page {
        padding: 24px 16px;
      }
    }

    @media (max-width: 575.98px) {
      .auth-page {
        padding: 16px;
      }

      .auth-card {
        padding: 28px 20px;
        border-radius: 18px;
      }

      .auth-title {
        font-size: 25px;
      }
    }

    /* "Face recognition" needs room, so stack the two buttons on narrow screens */
    @media (max-width: 419.98px) {
      .auth-social-grid > .col-6 {
        width: 100%;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      :root {
        --auth-dur: 0ms;
      }
    }
  </style>
</head>

<body>
  <main class="auth-page">
    <div class="container auth-container">
      <div class="auth-card">

        <!-- Brand -->
        <div class="auth-brand">
          <span class="auth-brand__mark" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none" focusable="false">
              <rect x="1.5" y="1.5" width="5" height="5" rx="1.25" fill="currentColor"/>
              <rect x="9.5" y="1.5" width="5" height="5" rx="1.25" fill="currentColor" opacity=".55"/>
              <rect x="1.5" y="9.5" width="5" height="5" rx="1.25" fill="currentColor" opacity=".55"/>
              <rect x="9.5" y="9.5" width="5" height="5" rx="1.25" fill="currentColor"/>
            </svg>
          </span>
          <span class="auth-brand__name">Inventory<span class="auth-brand__dot">.</span></span>
        </div>

        <!-- Tabs (server decides which one is selected) -->
        <div class="auth-tabs" role="tablist" aria-label="Authentication" data-active="<?= $e($activeTab) ?>">
          <span class="auth-tabs__thumb" aria-hidden="true"></span>
          <button type="button" class="auth-tab" role="tab" id="tab-login"
                  aria-selected="<?= $isRegister ? 'false' : 'true' ?>" aria-controls="panel-login"
                  tabindex="<?= $isRegister ? '-1' : '0' ?>" data-auth-tab="login">Log in</button>
          <button type="button" class="auth-tab" role="tab" id="tab-register"
                  aria-selected="<?= $isRegister ? 'true' : 'false' ?>" aria-controls="panel-register"
                  tabindex="<?= $isRegister ? '0' : '-1' ?>" data-auth-tab="register">Sign up</button>
        </div>

        <!-- Flash messages -->
        <?php if ($success !== null && $success !== ''): ?>
          <div class="auth-alert auth-alert--success" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <circle cx="12" cy="12" r="9"/>
              <path d="m8.5 12 2.5 2.5 4.5-5"/>
            </svg>
            <p><?= $e($success) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($error !== null && $error !== ''): ?>
          <div class="auth-alert auth-alert--error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <circle cx="12" cy="12" r="9"/>
              <path d="M12 8v4.5"/>
              <path d="M12 16h.01"/>
            </svg>
            <p><?= $e($error) ?></p>
          </div>
        <?php endif; ?>

        <!-- Panel: Log in -->
        <div class="auth-panel" role="tabpanel" id="panel-login" aria-labelledby="tab-login"
             data-auth-panel="login"<?= $isRegister ? ' hidden' : '' ?>>
          <header class="auth-heading">
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-subtitle">Enter your details to access your account.</p>
          </header>

          <form class="auth-form" method="post" action="/login">
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

            <div class="auth-fields">
              <div class="auth-field">
                <label class="auth-label" for="login-email">Email address</label>
                <input class="auth-input" type="email" id="login-email" name="email"
                       value="<?= $e($old['email'] ?? '') ?>"
                       placeholder="name@example.com" autocomplete="username"
                       autocapitalize="none" spellcheck="false" required>
              </div>

              <div class="auth-field">
                <div class="auth-field__row">
                  <label class="auth-label" for="login-password">Password</label>
                  <a class="auth-link-muted" href="/forgot-password">Forgot password?</a>
                </div>
                <div class="auth-input-group">
                  <input class="auth-input-group__field" type="password" id="login-password" name="password"
                         placeholder="Enter your password" autocomplete="current-password" required>
                  <button type="button" class="auth-toggle" data-toggle-password="login-password"
                          aria-controls="login-password" aria-label="Show password">
                    <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                      <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                      <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
                      <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
                      <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>
                      <path d="m2 2 20 20"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <div class="auth-check">
              <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
              <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <button type="submit" class="auth-primary-btn">Log in</button>
          </form>
        </div>

        <!-- Panel: Sign up -->
        <div class="auth-panel" role="tabpanel" id="panel-register" aria-labelledby="tab-register"
             data-auth-panel="register"<?= $isRegister ? '' : ' hidden' ?>>
          <header class="auth-heading">
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-subtitle">Fill in your details to get started.</p>
          </header>

          <form class="auth-form" method="post" action="/register">
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

            <div class="auth-fields">
              <div class="auth-field">
                <label class="auth-label" for="signup-username">Username</label>
                <input class="auth-input" type="text" id="signup-username" name="username"
                       value="<?= $e($old['username'] ?? '') ?>"
                       placeholder="Choose a username" autocomplete="username"
                       autocapitalize="none" spellcheck="false" required>
              </div>

              <div class="auth-field">
                <label class="auth-label" for="signup-email">Email address</label>
                <input class="auth-input" type="email" id="signup-email" name="email"
                       value="<?= $e($old['email'] ?? '') ?>"
                       placeholder="name@example.com" autocomplete="email"
                       autocapitalize="none" spellcheck="false" required>
              </div>

              <div class="auth-field">
                <label class="auth-label" for="signup-password">Password</label>
                <div class="auth-input-group">
                  <input class="auth-input-group__field" type="password" id="signup-password" name="password"
                         placeholder="Create a password" autocomplete="new-password"
                         minlength="8" aria-describedby="signup-password-help" required>
                  <button type="button" class="auth-toggle" data-toggle-password="signup-password"
                          aria-controls="signup-password" aria-label="Show password">
                    <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                      <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                      <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
                      <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
                      <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>
                      <path d="m2 2 20 20"/>
                    </svg>
                  </button>
                </div>
                <p class="auth-help" id="signup-password-help">Use at least 8 characters.</p>
              </div>

              <div class="auth-field">
                <label class="auth-label" for="signup-confirm">Confirm password</label>
                <div class="auth-input-group">
                  <input class="auth-input-group__field" type="password" id="signup-confirm" name="password_confirmation"
                         placeholder="Re-enter your password" autocomplete="new-password" required>
                  <button type="button" class="auth-toggle" data-toggle-password="signup-confirm"
                          aria-controls="signup-confirm" aria-label="Show password">
                    <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                      <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                      <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
                      <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
                      <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>
                      <path d="m2 2 20 20"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <button type="submit" class="auth-primary-btn">Sign up</button>
          </form>
        </div>

        <!-- Alternative authentication: face recognition + mobile number (SMS) -->
        <div class="auth-alt">
          <p class="auth-divider">Or continue with</p>

          <div class="row g-2 auth-social-grid">
            <div class="col-6">
              <button type="button" class="auth-social-btn" id="biometricFaceBtn" aria-label="Continue with face recognition">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                  <path d="M3 7V5a2 2 0 0 1 2-2h2"/>
                  <path d="M17 3h2a2 2 0 0 1 2 2v2"/>
                  <path d="M21 17v2a2 2 0 0 1-2 2h-2"/>
                  <path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                  <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                  <path d="M9 9h.01"/>
                  <path d="M15 9h.01"/>
                </svg>
                <span>Face recognition</span>
              </button>
            </div>
            <div class="col-6">
              <button type="button" class="auth-sms-btn" id="smsOtpBtn" aria-label="Sign in with mobile number">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                  <path d="M5.5 3.21a2 2 0 0 0-1.82 1.16l-2.5 4.5a2 2 0 0 0 1.64 2.68h.05a2 2 0 0 1 1.64 2.68l-2.5 4.5a2 2 0 0 0 1.82 1.16h13a2 2 0 0 0 1.82-1.16l2.5-4.5a2 2 0 0 0-1.64-2.68h-.05a2 2 0 0 1-1.64-2.68l2.5-4.5a2 2 0 0 0-1.82-1.16z"/>
                  <path d="M12 8v8"/>
                  <path d="M9 11h6"/>
                </svg>
                <span>Mobile number</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Footer navigation -->
        <div class="auth-footer">
          <p data-auth-footer="login"<?= $isRegister ? ' hidden' : '' ?>>Don’t have an account?
            <button type="button" class="auth-link" data-auth-switch="register">Sign up</button>
          </p>
          <p data-auth-footer="register"<?= $isRegister ? '' : ' hidden' ?>>Already have an account?
            <button type="button" class="auth-link" data-auth-switch="login">Log in</button>
          </p>
        </div>

      </div>
    </div>
  </main>

  <!-- SMS OTP Modal -->
  <div class="sms-modal-overlay" id="smsModal" role="dialog" aria-modal="true" aria-labelledby="smsModalTitle" hidden>
    <div class="sms-modal">
      <header class="sms-modal__header">
        <h2 class="sms-modal__title" id="smsModalTitle">Sign in with mobile</h2>
        <button type="button" class="sms-modal__close" id="smsModalClose" aria-label="Close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </header>

      <div class="sms-modal__body">
        <!-- Step 1: Enter Phone Number -->
        <div class="sms-modal__step is-active" id="smsStepPhone" data-step="phone">
          <p class="sms-modal__subtitle">Enter your Philippine mobile number to receive a 6-digit verification code.</p>

          <div class="auth-field">
            <label class="auth-label" for="smsPhoneInput">Mobile number</label>
            <div class="auth-input-group" style="padding-left: 12px; padding-right: 12px;">
              <span style="flex: none; color: var(--auth-text); font: 500 14px/1 var(--auth-font);">+63</span>
              <input class="auth-input-group__field" type="tel" id="smsPhoneInput" name="phone"
                     placeholder="9XX XXX XXXX" autocomplete="tel"
                     inputmode="numeric" pattern="[0-9]*" maxlength="10" required>
            </div>
          </div>

          <p class="sms-modal__network-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <circle cx="12" cy="12" r="10"/>
              <path d="M12 16v-4"/>
              <path d="M12 8h.01"/>
            </svg>
            <span>SMS verification is available for Globe, TM and DITO. Smart, TNT and Sun numbers are not supported by our current SMS provider.</span>
          </p>

          <div class="sms-modal__error" id="smsPhoneError" role="alert" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <circle cx="12" cy="12" r="9"/>
              <path d="M12 8v4.5"/>
              <path d="M12 16h.01"/>
            </svg>
            <span></span>
          </div>

          <div class="sms-modal__actions">
            <button type="button" class="sms-modal__btn sms-modal__btn--primary" id="smsContinueBtn">
              <span class="sms-modal__btn-text">Continue</span>
              <span class="sms-modal__btn-loader" aria-hidden="true"></span>
            </button>
          </div>
        </div>

        <!-- Step 2: Verify OTP -->
        <div class="sms-modal__step" id="smsStepOtp" data-step="otp">
          <p class="sms-modal__subtitle">We sent a 6-digit code to</p>

          <div class="sms-modal__phone-display" id="smsPhoneDisplay">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <path d="M5.5 3.21a2 2 0 0 0-1.82 1.16l-2.5 4.5a2 2 0 0 0 1.64 2.68h.05a2 2 0 0 1 1.64 2.68l-2.5 4.5a2 2 0 0 0 1.82 1.16h13a2 2 0 0 0 1.82-1.16l2.5-4.5a2 2 0 0 0-1.64-2.68h-.05a2 2 0 0 1-1.64-2.68l2.5-4.5a2 2 0 0 0-1.82-1.16z"/>
              <path d="M12 8v8"/>
              <path d="M9 11h6"/>
            </svg>
            <span id="smsMaskedPhone"></span>
          </div>

          <div class="sms-modal__otp-inputs" role="group" aria-label="Enter the 6-digit verification code">
            <input class="sms-modal__otp-input" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" aria-label="Digit 1">
            <input class="sms-modal__otp-input" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" aria-label="Digit 2">
            <input class="sms-modal__otp-input" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" aria-label="Digit 3">
            <input class="sms-modal__otp-input" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" aria-label="Digit 4">
            <input class="sms-modal__otp-input" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" aria-label="Digit 5">
            <input class="sms-modal__otp-input" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" aria-label="Digit 6">
          </div>

          <div class="sms-modal__timer" id="smsTimer" role="timer" aria-live="polite">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <circle cx="12" cy="12" r="10"/>
              <path d="M12 6v6l4 2"/>
            </svg>
            <span id="smsTimerText">Code expires in 5:00</span>
          </div>

          <div class="sms-modal__error" id="smsOtpError" role="alert" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <circle cx="12" cy="12" r="9"/>
              <path d="M12 8v4.5"/>
              <path d="M12 16h.01"/>
            </svg>
            <span></span>
          </div>

          <div class="sms-modal__actions">
            <button type="button" class="sms-modal__btn sms-modal__btn--primary" id="smsVerifyBtn">
              <span class="sms-modal__btn-text">Verify</span>
              <span class="sms-modal__btn-loader" aria-hidden="true"></span>
            </button>
          </div>

          <div class="sms-modal__resend">
            <span id="smsResendText">Didn't receive the code?</span>
            <button type="button" class="sms-modal__resend-link" id="smsResendBtn" disabled aria-label="Resend code">
              Resend in 60s
            </button>
          </div>

          <div class="sms-modal__change-number">
            <span>Wrong number?</span>
            <button type="button" class="sms-modal__change-link" id="smsChangeNumberBtn">
              Change number
            </button>
          </div>
        </div>

        <!-- Step 3: Success -->
        <div class="sms-modal__step" id="smsStepSuccess" data-step="success">
          <div class="sms-modal__success">
            <div class="sms-modal__success-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
            </div>
            <h3 class="sms-modal__success-title">Verification successful</h3>
            <p class="sms-modal__success-text">Redirecting you to your dashboard…</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (() => {
      'use strict';

      const tabList  = document.querySelector('.auth-tabs');
      const tabs     = [...document.querySelectorAll('[data-auth-tab]')];
      const panels   = [...document.querySelectorAll('[data-auth-panel]')];
      const footers  = [...document.querySelectorAll('[data-auth-footer]')];
      const alertBox = document.querySelector('.auth-alert');

      /* Tabs: switch panel, footer text, ARIA state and the URL (?auth=...) */
      function activate(name, moveFocus) {
        tabList.dataset.active = name;

        tabs.forEach((tab) => {
          const isActive = tab.dataset.authTab === name;
          tab.setAttribute('aria-selected', String(isActive));
          tab.tabIndex = isActive ? 0 : -1;
          if (isActive && moveFocus) tab.focus();
        });

        panels.forEach((panel) => { panel.hidden = panel.dataset.authPanel !== name; });
        footers.forEach((footer) => { footer.hidden = footer.dataset.authFooter !== name; });

        // Keep the URL in sync so a refresh lands on the same panel
        try {
          const url = new URL(window.location.href);
          url.searchParams.set('auth', name);
          window.history.replaceState(null, '', url);
        } catch (_) { /* ignore */ }

        // A flash message belongs to the previous submit — hide it on switch
        if (alertBox) alertBox.hidden = true;
      }

      tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab.dataset.authTab));

        tab.addEventListener('keydown', (event) => {
          let next = null;
          if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
          else if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
          else if (event.key === 'Home') next = 0;
          else if (event.key === 'End') next = tabs.length - 1;
          if (next === null) return;
          event.preventDefault();
          activate(tabs[next].dataset.authTab, true);
        });
      });

      document.querySelectorAll('[data-auth-switch]').forEach((button) => {
        button.addEventListener('click', () => activate(button.dataset.authSwitch, true));
      });

      /* Password visibility toggles */
      document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        const input = document.getElementById(button.dataset.togglePassword);
        if (!input) return;

        button.addEventListener('click', () => {
          const show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          button.classList.toggle('is-visible', show);
          button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
      });

      /* Sign up: confirm password must match (client-side; server re-checks) */
      const password = document.getElementById('signup-password');
      const confirm  = document.getElementById('signup-confirm');

      function checkMatch() {
        confirm.setCustomValidity(
          confirm.value && confirm.value !== password.value ? 'Passwords do not match.' : ''
        );
      }

      password.addEventListener('input', checkMatch);
      confirm.addEventListener('input', checkMatch);

      document.querySelectorAll('a[href="#"]').forEach((link) => {
        link.addEventListener('click', (event) => event.preventDefault());
      });
    })();
  </script>

  <script>
    /* =========================================================
       FACE LOGIN (restored) — see routes/face.php
         GET  /face/login-options -> { challenge }
         POST /face/verify        -> { success, redirect }
       ========================================================= */
    (() => {
      'use strict';

      const CSRF_TOKEN = '<?= $e($csrfToken) ?>';
      const LOCAL_LIBRARY = '<?= asset('/assets/face-api/face-api.min.js?v=2026110702') ?>';
      const CDN_LIBRARY = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
      const MODEL_URIS = [
        '<?= asset('/assets/face-api/weights') ?>',
        'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights'
      ];

      function loadScript(src) {
        return new Promise((resolve, reject) => {
          const tag = document.createElement('script');
          tag.src = src;
          tag.onload = resolve;
          tag.onerror = reject;
          document.head.appendChild(tag);
        });
      }

      async function loadFaceApi() {
        if (window.faceapi) return;
        try {
          await loadScript(LOCAL_LIBRARY);
        } catch (error) {
          await loadScript(CDN_LIBRARY);
        }
      }

      async function loadModels() {
        await loadFaceApi();
        for (const uri of MODEL_URIS) {
          try {
            await Promise.all([
              faceapi.nets.tinyFaceDetector.loadFromUri(uri),
              faceapi.nets.faceLandmark68Net.loadFromUri(uri),
              faceapi.nets.faceRecognitionNet.loadFromUri(uri)
            ]);
            return;
          } catch (error) { /* try next source */ }
        }
        throw new Error('Face model could not be loaded.');
      }

      const detectorOptions = () =>
        new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.4 });

      const faceModal = window.BioModal();

      let faceStream = null;
      let flowId = 0;
      let successTimer = null;

      function stopFaceCamera() {
        if (successTimer) { clearTimeout(successTimer); successTimer = null; }
        if (faceStream) {
          faceStream.getTracks().forEach((track) => track.stop());
          faceStream = null;
        }
        faceModal.video.srcObject = null;
      }

      faceModal.onClose(stopFaceCamera);

      async function ensureCamera() {
        if (faceStream && faceStream.getTracks().some((track) => track.readyState === 'live')) {
          faceModal.video.srcObject = faceStream;
          try { await faceModal.video.play(); } catch (error) { /* already playing */ }
          return;
        }

        faceModal.cameraBusy(true);
        faceModal.note('Starting camera…');

        faceStream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
          audio: false
        });

        faceModal.video.srcObject = faceStream;
        await faceModal.video.play();
        faceModal.cameraBusy(false);
        faceModal.note('');
      }

      function detectDescriptor() {
        return faceapi
          .detectSingleFace(faceModal.video, detectorOptions())
          .withFaceLandmarks()
          .withFaceDescriptor()
          .then((result) => (result ? Array.from(result.descriptor) : null));
      }

      const faceButton = document.getElementById('biometricFaceBtn');

      function renderIntro() {
        faceModal.header(
          'Face verification',
          'Verify your identity quickly and securely using your camera.',
          'face'
        );
        faceModal.icon('face');
        faceModal.showCamera(false);
        faceModal.meta(false);
        faceModal.state(
          '<div class="bio-illustration">' + faceModal.faceArt() + '</div>' +
          '<ul class="bio-guidance">' +
          '  <li><i class="bi bi-brightness-high" aria-hidden="true"></i><span>Use even, front-facing lighting</span></li>' +
          '  <li><i class="bi bi-person-bounding-box" aria-hidden="true"></i><span>Keep your whole face in the frame</span></li>' +
          '  <li><i class="bi bi-eye-slash" aria-hidden="true"></i><span>Remove glasses if they reflect light</span></li>' +
          '</ul>'
        );
        faceModal.actions([
          { label: 'Verify face', variant: 'primary', onClick: startFaceFlow },
          { label: 'Cancel', variant: 'ghost', onClick: function () { faceModal.close(); } }
        ]);
      }

      function renderPermission(message) {
        faceModal.header(
          'Camera access required',
          'Allow camera access to verify your face. Nothing is uploaded — matching happens on your own server.',
          'camera'
        );
        faceModal.showCamera(false);
        faceModal.meta(false);
        faceModal.state(
          '<div class="bio-center" style="padding:10px 0 0;">' +
          '  <span class="bio-state-icon is-neutral"><i class="bi bi-camera-video" aria-hidden="true"></i></span>' +
          '  <p class="bio-permission-copy">' + faceModal.escapeHtml(message) + '</p>' +
          '  <p class="bio-fineprint">Blocked by mistake? Click the camera icon in your browser’s address bar, allow access, then try again.</p>' +
          '</div>'
        );
        faceModal.actions([
          { label: 'Enable camera', variant: 'primary', onClick: startFaceFlow },
          { label: 'Not now', variant: 'ghost', onClick: function () { faceModal.close(); } }
        ]);
      }

      function renderError(message, hint) {
        flowId++; // stop any running scan loop
        faceModal.header(
          'Face verification',
          'Verify your identity quickly and securely using your camera.',
          'face'
        );
        faceModal.showCamera(false);
        faceModal.meta(false);
        faceModal.state(
          '<div class="bio-state">' +
          '  <span class="bio-state-icon is-error"><i class="bi bi-exclamation-lg" aria-hidden="true"></i></span>' +
          '  <h3>Verification unsuccessful</h3>' +
          '  <p>' + faceModal.escapeHtml(message) + '</p>' +
          (hint ? '<p class="bio-fineprint">' + faceModal.escapeHtml(hint) + '</p>' : '') +
          '</div>'
        );
        faceModal.actions([
          { label: 'Try again', variant: 'primary', onClick: startFaceFlow },
          { label: 'Cancel', variant: 'ghost', onClick: function () { faceModal.close(); } }
        ]);
      }

      function renderSuccess(redirect) {
        flowId++;
        faceModal.header(
          'Face verification',
          'Verify your identity quickly and securely using your camera.',
          'shield'
        );
        faceModal.showCamera(false);
        faceModal.meta(false);
        faceModal.state(
          '<div class="bio-state">' +
          '  <span class="bio-state-icon is-success"><i class="bi bi-check-lg" aria-hidden="true"></i></span>' +
          '  <h3>Verification successful</h3>' +
          '  <p>Your face has been verified. Taking you to your dashboard…</p>' +
          '</div>'
        );
        const go = function () { window.location.href = redirect || '/dashboard'; };
        faceModal.actions([{ label: 'Continue', variant: 'primary', onClick: go }]);
        successTimer = setTimeout(go, 1800);
      }

      /* ---------- multi-account chooser ---------- */

      function renderAccountChoice(token, accounts) {
        flowId++; // stop any running scan loop
        stopFaceCamera(); // free the camera while the user reads the list
        faceModal.video.srcObject = null;
        faceModal.header(
          'Choose an account',
          'This face is linked to more than one account. Pick which one to open.',
          'shield'
        );
        faceModal.showCamera(false);
        faceModal.meta(false);

        const rows = (accounts || []).map(function (account) {
          const initial = String(account.username || '?').charAt(0).toUpperCase();
          const role = String(account.role || 'USER');
          return (
            '<button type="button" class="bio-account-row" data-bio-account="' +
            Number(account.id) + '" data-bio-token="' + faceModal.escapeHtml(token) + '">' +
            '  <span class="bio-account-avatar" aria-hidden="true">' +
            faceModal.escapeHtml(initial) + '</span>' +
            '  <span class="bio-account-copy">' +
            '    <span class="bio-account-name">' + faceModal.escapeHtml(account.username) + '</span>' +
            '    <span class="bio-account-email">' + faceModal.escapeHtml(account.email) + '</span>' +
            '  </span>' +
            '  <span class="bio-account-role' + (role === 'ADMIN' ? ' is-admin' : '') + '">' +
            faceModal.escapeHtml(role) + '</span>' +
            '</button>'
          );
        }).join('');

        faceModal.state(
          '<div class="bio-account-list">' + rows + '</div>' +
          '<p class="bio-fineprint">Not you? You can scan again or sign in with email and password.</p>'
        );

        faceModal.actions([
          { label: 'Scan again', variant: 'ghost', onClick: startFaceFlow },
          { label: 'Cancel', variant: 'ghost', onClick: function () { faceModal.close(); } }
        ]);

        const boxes = faceModal.overlay.querySelectorAll('[data-bio-account]');
        boxes.forEach(function (box) {
          box.addEventListener('click', function () {
            boxes.forEach(function (other) { other.disabled = true; });
            box.classList.add('is-busy');

            const go = function (redirect) {
              flowId++;
              faceModal.header(
                'Face verification',
                'Verify your identity quickly and securely using your camera.',
                'shield'
              );
              renderSuccessLocal(redirect);
            };

            const fail = function (message) {
              flowId++;
              renderError(message || 'Could not sign in to that account.', 'Scan again to restart.');
            };

            fetch('/face/login/select', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
              body: JSON.stringify({ token: token, userId: Number(box.getAttribute('data-bio-account')) })
            })
              .then(function (res) { return res.json(); })
              .then(function (data) {
                if (data.success) go(data.redirect);
                else fail(data.message);
              })
              .catch(function () { fail('Network error. Please try again.'); });
          });
        });
      }

      /* Local copy of the success screen without triggering camera cleanup */
      function renderSuccessLocal(redirect) {
        faceModal.showCamera(false);
        faceModal.meta(false);
        faceModal.icon('check');
        faceModal.state(
          '<div class="bio-state">' +
          '  <span class="bio-state-icon is-success"><i class="bi bi-check-lg" aria-hidden="true"></i></span>' +
          '  <h3>Verification successful</h3>' +
          '  <p>Taking you to your dashboard…</p>' +
          '</div>'
        );
        const go = function () { window.location.href = redirect || '/dashboard'; };
        faceModal.actions([{ label: 'Continue', variant: 'primary', onClick: go }]);
        successTimer = setTimeout(go, 1800);
      }

      async function getChallenge() {
        const res = await fetch('/face/login-options', {
          headers: { 'X-CSRF-Token': CSRF_TOKEN }
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Unable to start a secure face session.');
        return data.challenge;
      }

      async function waitForSamples(flow) {
        let busy = false;
        let samples = [];
        let lastDesc = null;
        let holdStart = Date.now();
        const STABLE_MS = 800;
        const dist = function (a, b) {
          let d = 0;
          for (let i = 0; i < 128; i++) { const x = a[i] - b[i]; d += x * x; }
          return Math.sqrt(d);
        };

        for (;;) {
          if (flow !== flowId || !faceModal.isOpen()) return null;
          if (busy) { await new Promise((r) => setTimeout(r, 240)); continue; }
          busy = true;
          let descriptor = null;
          try { descriptor = await detectDescriptor(); } finally { busy = false; }
          if (flow !== flowId || !faceModal.isOpen()) return null;

          if (!descriptor) {
            lastDesc = null;
            samples = [];
            holdStart = Date.now();
            faceModal.progress(0);
            faceModal.status('No face detected — move into frame', 'warn');
            await new Promise((r) => setTimeout(r, 240));
            continue;
          }

          if (!lastDesc || dist(descriptor, lastDesc) > 0.25) {
            samples = [descriptor];
            holdStart = Date.now();
          } else if (
            samples.length < 3 &&
            dist(descriptor, samples[samples.length - 1]) > 0.08
          ) {
            samples.push(descriptor);
          }
          lastDesc = descriptor;

          const percent = Math.min(100,
            Math.round(((Date.now() - holdStart) / STABLE_MS) * 100));
          faceModal.progress(percent, percent >= 100);

          if (percent >= 100) {
            faceModal.status('Face captured', 'success');
            return samples;
          }

          faceModal.status('Hold still… ' + percent + '%', null);
          await new Promise((r) => setTimeout(r, 240));
        }
      }

      async function startFaceFlow() {
        const flow = ++flowId;
        const alive = function () { return flow === flowId && faceModal.isOpen(); };

        faceModal.header('Position your face', 'Keep your face centered inside the frame.', 'face');
        faceModal.state('');
        faceModal.showCamera(true);
        faceModal.meta(true);
        faceModal.status('Starting camera…', null);
        faceModal.progress(0);
        faceModal.actions([
          { label: 'Cancel', variant: 'ghost', onClick: function () { faceModal.close(); } }
        ]);

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          renderError('Camera is not available in this browser.', 'Use a modern browser such as Chrome, Edge or Safari.');
          return;
        }

        try {
          await ensureCamera();
        } catch (error) {
          if (!alive()) return;
          const denied = error && (error.name === 'NotAllowedError' || error.name === 'SecurityError');
          renderPermission(denied
            ? 'Camera permission was denied.'
            : 'The camera could not be started on this device.');
          return;
        }
        if (!alive()) return;

        faceModal.status('Loading face model…', null);
        try {
          await loadModels();
        } catch (error) {
          if (alive()) renderError('The face model could not be loaded.', 'Check your internet connection and try again.');
          return;
        }
        if (!alive()) return;

        faceModal.status('Look into the camera', null);

        const samples = await waitForSamples(flow);
        if (!samples || !samples.length || !alive()) return;

        faceModal.header('Verifying your face', 'Please keep this window open.', 'shield');
        faceModal.showCamera(false);
        faceModal.meta(false);
        faceModal.state(
          '<div class="bio-center">' +
          '  <span class="bio-loader" aria-hidden="true"></span>' +
          '  <p class="bio-processing-text">Matching your face…</p>' +
          '</div>'
        );
        faceModal.actions([
          { label: 'Cancel', variant: 'ghost', onClick: function () { faceModal.close(); } }
        ]);

        let challenge;
        try {
          challenge = await getChallenge();
        } catch (error) {
          if (alive()) renderError(error.message || 'Unable to start a secure face session.', 'Check your connection and try again.');
          return;
        }
        if (!alive()) return;

        try {
          const res = await fetch('/face/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify({ challenge, descriptors: samples, descriptor: samples[0] })
          });
          const data = await res.json();
          if (!alive()) return;

          if (data.success && data.chooseAccount) {
            renderAccountChoice(data.token, data.accounts);
            return;
          }

          if (data.success) {
            renderSuccess(data.redirect);
            return;
          }

          renderError(
            data.message || 'Face not recognized.',
            'Improve lighting, look straight at the camera and keep your full face visible.'
          );
        } catch (error) {
          if (alive()) renderError('Verification could not be completed.', 'Check your internet connection and try again.');
        }
      }

      faceButton.addEventListener('click', function () {
        faceModal.open();
        renderIntro();
      });
    })();
  </script>

  <script>
    /* =========================================================
       SMS OTP LOGIN: Mobile number verification via UniSMS
       Endpoints (see routes/auth.php):
         POST /api/auth/sms/request  -> { success, message, challengeId, cooldown }
         POST /api/auth/sms/verify   -> { success, message, redirect }
         POST /api/auth/sms/resend   -> { success, message, cooldown }
       ========================================================= */
    (() => {
      'use strict';

      /* =========================================================
         SMS OTP LOGIN: Mobile number verification via UniSMS
         Endpoints (see routes/auth.php):
           POST /api/auth/sms/request  -> { success, message, challengeId, cooldown }
           POST /api/auth/sms/verify   -> { success, message, redirect }
           POST /api/auth/sms/resend   -> { success, message, cooldown }
         ========================================================= */

      const CSRF_TOKEN = '<?= $e($csrfToken) ?>';
      const OTP_TTL_SECONDS = 5 * 60; // 5 minutes
      const RESEND_COOLDOWN = 60; // 60 seconds

      /* ---------- DOM elements ---------- */

      const modal = document.getElementById('smsModal');
      const modalClose = document.getElementById('smsModalClose');

      // Step 1: Phone input
      const stepPhone = document.getElementById('smsStepPhone');
      const phoneInput = document.getElementById('smsPhoneInput');
      const phoneError = document.getElementById('smsPhoneError');
      const continueBtn = document.getElementById('smsContinueBtn');

      // Step 2: OTP verification
      const stepOtp = document.getElementById('smsStepOtp');
      const maskedPhoneEl = document.getElementById('smsMaskedPhone');
      const otpInputs = [...document.querySelectorAll('#smsStepOtp .sms-modal__otp-input')];
      const timerEl = document.getElementById('smsTimer');
      const timerText = document.getElementById('smsTimerText');
      const otpError = document.getElementById('smsOtpError');
      const verifyBtn = document.getElementById('smsVerifyBtn');
      const resendBtn = document.getElementById('smsResendBtn');
      const resendText = document.getElementById('smsResendText');
      const changeNumberBtn = document.getElementById('smsChangeNumberBtn');

      // Step 3: Success
      const stepSuccess = document.getElementById('smsStepSuccess');

      /* ---------- State ---------- */

      let timerInterval = null;
      let resendCooldownInterval = null;
      let timeRemaining = OTP_TTL_SECONDS;
      let resendRemaining = 0;

      /* ---------- Helpers ---------- */

      function showError(el, message) {
        const span = el.querySelector('span');
        if (span) span.textContent = message;
        el.hidden = false;
        el.classList.add('is-visible');
      }

      function hideError(el) {
        el.hidden = true;
        el.classList.remove('is-visible');
      }

      function setButtonLoading(btn, loading) {
        if (loading) {
          btn.classList.add('is-loading');
          btn.disabled = true;
        } else {
          btn.classList.remove('is-loading');
          btn.disabled = false;
        }
      }

      function showStep(step) {
        [stepPhone, stepOtp, stepSuccess].forEach((s) => s.classList.remove('is-active'));
        if (step === 'phone') stepPhone.classList.add('is-active');
        else if (step === 'otp') stepOtp.classList.add('is-active');
        else if (step === 'success') stepSuccess.classList.add('is-active');
      }

      function resetModal() {
        showStep('phone');
        phoneInput.value = '';
        hideError(phoneError);
        otpInputs.forEach((input) => {
          input.value = '';
          input.removeAttribute('aria-invalid');
          input.disabled = false;
        });
        hideError(otpError);
        timeRemaining = OTP_TTL_SECONDS;
        resendRemaining = 0;
        updateResendButton();
        clearTimers();
        const title = document.getElementById('smsModalTitle');
        if (title) title.textContent = 'Sign in with mobile';
        phoneInput.focus();
      }

      function openModal() {
        resetModal();
        modal.hidden = false;
        // Force reflow so the open transition plays
        void modal.offsetHeight;
        modal.classList.add('is-open');

        const firstFocusable = phoneInput;
        if (firstFocusable) firstFocusable.focus();
      }

      function closeModal() {
        modal.classList.remove('is-open');
        clearTimers();
        setTimeout(() => {
          modal.hidden = true;
          resetModal();
        }, 200);
      }

      function clearTimers() {
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        if (resendCooldownInterval) { clearInterval(resendCooldownInterval); resendCooldownInterval = null; }
      }

      function formatTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return m + ':' + String(s).padStart(2, '0');
      }

      function updateTimerDisplay() {
        timerText.textContent = 'Code expires in ' + formatTime(timeRemaining);
        timerEl.classList.toggle('is-warning', timeRemaining <= 60);
      }

      function startOtpTimer() {
        clearTimers();
        timeRemaining = OTP_TTL_SECONDS;
        updateTimerDisplay();

        timerInterval = setInterval(() => {
          timeRemaining--;
          updateTimerDisplay();

          if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            timerInterval = null;
            showError(otpError, 'The code has expired. Please request a new one.');
            setButtonLoading(verifyBtn, false);
          }
        }, 1000);
      }

      function updateResendButton() {
        if (resendRemaining > 0) {
          resendBtn.disabled = true;
          resendBtn.textContent = 'Resend in ' + resendRemaining + 's';
        } else {
          resendBtn.disabled = false;
          resendBtn.textContent = 'Resend code';
        }
      }

      function startResendCooldown(seconds) {
        resendRemaining = seconds || RESEND_COOLDOWN;
        updateResendButton();

        if (resendCooldownInterval) clearInterval(resendCooldownInterval);
        resendCooldownInterval = setInterval(() => {
          resendRemaining--;
          updateResendButton();
          if (resendRemaining <= 0) {
            clearInterval(resendCooldownInterval);
            resendCooldownInterval = null;
          }
        }, 1000);
      }

      function maskPhone(phone) {
        // phone arrives as +639XXXXXXXXX — show ***-***-XXXX
        if (phone.length >= 4) return '***-***-' + phone.slice(-4);
        return phone;
      }

      function getOtpCode() {
        return otpInputs.map((input) => input.value).join('');
      }

      /* ---------- OTP boxes: digits, backspace, paste ---------- */

      otpInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
          const value = input.value.replace(/\D/g, '');
          input.value = value.slice(0, 1);

          if (input.value) {
            input.removeAttribute('aria-invalid');
            hideError(otpError);
            if (index < otpInputs.length - 1) otpInputs[index + 1].focus();
          }
        });

        input.addEventListener('keydown', (event) => {
          if (event.key === 'Backspace' && !input.value && index > 0) {
            otpInputs[index - 1].focus();
          } else if (event.key === 'Enter') {
            event.preventDefault();
            handleVerify();
          }
        });

        input.addEventListener('paste', (event) => {
          event.preventDefault();
          const digits = (event.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
          if (!digits) return;
          otpInputs.forEach((box, i) => { box.value = digits[i] || ''; });
          const nextEmpty = otpInputs.findIndex((box) => !box.value);
          (nextEmpty >= 0 ? otpInputs[nextEmpty] : otpInputs[5]).focus();
        });
      });

      /* ---------- API ---------- */

      async function apiRequest(endpoint, body) {
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN,
          },
          body: JSON.stringify(body),
        });

        if (!response.ok && response.status === 403) {
          throw new Error('Your session expired. Reload the page and try again.');
        }

        return response.json();
      }

      /* ---------- Flow handlers ---------- */

      async function handleContinue() {
        hideError(phoneError);
        const rawPhone = phoneInput.value.trim().replace(/\D/g, '');

        if (!/^9\d{9}$/.test(rawPhone)) {
          showError(phoneError, 'Enter a valid Philippine mobile number (9XX XXX XXXX).');
          phoneInput.focus();
          return;
        }

        const fullPhone = '+63' + rawPhone;
        setButtonLoading(continueBtn, true);

        try {
          const result = await apiRequest('/api/auth/sms/request', { phone: fullPhone });

          if (result.success && result.challengeId) {
            maskedPhoneEl.textContent = maskPhone(fullPhone);
            document.getElementById('smsModalTitle').textContent = 'Verify your phone';
            showStep('otp');
            startOtpTimer();
            startResendCooldown(result.cooldown || RESEND_COOLDOWN);
            otpInputs[0].focus();
          } else {
            // Unregistered / unverified numbers must not stand out
            // (server answers them the same way), so mirror that here.
            if (result.success && !result.challengeId) {
              maskedPhoneEl.textContent = maskPhone(fullPhone);
              document.getElementById('smsModalTitle').textContent = 'Verify your phone';
              showStep('otp');
              startOtpTimer();
              startResendCooldown(RESEND_COOLDOWN);
              otpInputs[0].focus();
            } else {
              showError(phoneError, result.message || 'Unable to send code. Please try again.');
            }
          }
        } catch (error) {
          showError(phoneError, error.message || 'Network error. Please check your connection and try again.');
        } finally {
          setButtonLoading(continueBtn, false);
        }
      }

      async function handleVerify() {
        hideError(otpError);
        const code = getOtpCode();

        if (code.length !== 6) {
          otpInputs.forEach((input) => input.setAttribute('aria-invalid', 'true'));
          showError(otpError, 'Please enter the full 6-digit code.');
          otpInputs[0].focus();
          return;
        }

        setButtonLoading(verifyBtn, true);
        otpInputs.forEach((input) => { input.disabled = true; });

        try {
          const result = await apiRequest('/api/auth/sms/verify', { code });

          if (result.success) {
            clearTimers();
            showStep('success');
            setTimeout(() => {
              window.location.href = result.redirect || '/dashboard';
            }, 1200);
          } else {
            otpInputs.forEach((input) => {
              input.value = '';
              input.setAttribute('aria-invalid', 'true');
              input.disabled = false;
            });
            showError(otpError, result.message || 'Incorrect code. Please try again.');
            otpInputs[0].focus();
          }
        } catch (error) {
          otpInputs.forEach((input) => { input.disabled = false; });
          showError(otpError, error.message || 'Network error. Please check your connection and try again.');
        } finally {
          setButtonLoading(verifyBtn, false);
        }
      }

      async function handleResend() {
        if (resendRemaining > 0) return;

        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending…';

        try {
          const result = await apiRequest('/api/auth/sms/resend', {});

          if (result.success) {
            timeRemaining = OTP_TTL_SECONDS;
            updateTimerDisplay();
            hideError(otpError);
            otpInputs.forEach((input) => {
              input.value = '';
              input.removeAttribute('aria-invalid');
            });
            startOtpTimer();
            startResendCooldown(result.cooldown || RESEND_COOLDOWN);
            otpInputs[0].focus();
          } else {
            if (result.cooldown) {
              startResendCooldown(result.cooldown);
            } else {
              updateResendButton();
            }
            showError(otpError, result.message || 'Unable to resend code. Please try again.');
          }
        } catch (error) {
          updateResendButton();
          showError(otpError, error.message || 'Network error. Please check your connection and try again.');
        }
      }

      /* ---------- Event listeners ---------- */

      document.getElementById('smsOtpBtn').addEventListener('click', openModal);
      modalClose.addEventListener('click', closeModal);

      modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
      });

      continueBtn.addEventListener('click', handleContinue);
      phoneInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          handleContinue();
        }
      });

      verifyBtn.addEventListener('click', handleVerify);
      resendBtn.addEventListener('click', handleResend);
      changeNumberBtn.addEventListener('click', () => {
        clearTimers();
        resetModal();
      });
    })();
  </script>
</body>
</html>