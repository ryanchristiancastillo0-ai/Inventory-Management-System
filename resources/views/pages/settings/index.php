<?php
/**
 * SETTINGS PAGE
 * Controller: SettingsController::index() / ::updateProfile() / ::updatePreferences() / ::changePassword()
 * Layout    : admin OR app (this page is layout-agnostic)
 *
 * @var array       $account    User's account record
 * @var array       $settings   User's preferences
 * @var array|null  $flash      ['type' => 'success'|'danger', 'message' => string]
 * @var string      $csrfToken
 */

$account   = $account   ?? [];
$settings  = $settings  ?? [];
$flash     = $flash     ?? null;
$csrfToken = $csrfToken ?? '';

/* ---------- Safe helpers ---------- */
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$pickStr = static function (array $rec, array $keys, string $default = '') {
    foreach ($keys as $k) {
        if (isset($rec[$k]) && $rec[$k] !== '' && $rec[$k] !== null) return (string) $rec[$k];
    }
    return $default;
};

/* ---------- Account fields ---------- */
$username = $pickStr($account, ['username', 'name', 'full_name'], '');
$email    = $pickStr($account, ['email', 'email_address', 'mail'], '');
$role     = strtoupper($pickStr($account, ['role', 'role_name', 'user_role'], 'USER'));
$accountId = $pickStr($account, ['id', 'user_id'], '');
$createdAt = $pickStr($account, ['created_at', 'created_on', 'joined_at', 'registered_at'], '');

/* Masked email shown in the forgot-password modal ("j•••@domain"). */
$maskedEmail = $email;
if ($email !== '' && strpos($email, '@') !== false) {
    [$mailLocal, $mailDomain] = explode('@', $email, 2);
    $maskedEmail = mb_substr($mailLocal, 0, 1)
        . str_repeat('•', max(2, mb_strlen($mailLocal) - 1))
        . '@' . $mailDomain;
}

$initial  = strtoupper(substr($username !== '' ? $username : '?', 0, 1));
$roleLabel = $role === 'ADMIN' ? 'Administrator' : 'Member';
$roleCls   = $role === 'ADMIN' ? 'is-admin' : 'is-user';

$fmtDate = static function (string $ts) {
    if ($ts === '') return '—';
    $t = strtotime($ts);
    return $t === false ? $ts : date('M j, Y', $t);
};

/* ---------- Preference fields ---------- */
$themeRaw       = strtoupper($pickStr($settings, ['theme', 'appearance'], 'SYSTEM'));
$theme          = in_array($themeRaw, ['SYSTEM', 'LIGHT', 'DARK'], true) ? $themeRaw : 'SYSTEM';
$emailNotifsRaw = $pickStr($settings, ['email_notifications', 'email_notifications_enabled', 'notifications'], '0');
$emailNotifs    = ($emailNotifsRaw === '1' || strtolower($emailNotifsRaw) === 'true');

/* ---------- Flash ---------- */
$flashType = (string) ($flash['type'] ?? '');
$flashMsg  = (string) ($flash['message'] ?? '');
?>


<style>
    /* ==========================================================
       SETTINGS — page-specific styles
       ========================================================== */

    /* ---------- Page header ---------- */
    .page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        min-width: 0;
    }
    .page-header > * { min-width: 0; }
    .page-header .page-title { margin: 0; }
    .page-header .page-subtitle {
        margin: 6px 0 0;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    /* ---------- Layout grid ---------- */
    .settings-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 320px);
        gap: 20px;
        margin-top: 24px;
        align-items: start;
    }
    .settings-grid > * { min-width: 0; }
    .settings-main { display: flex; flex-direction: column; gap: 20px; }
    .settings-aside { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 80px; }

    @media (max-width: 991.98px) {
        .settings-grid { grid-template-columns: minmax(0, 1fr); }
        .settings-aside { position: static; order: -1; }
    }

    /* ---------- Settings card ---------- */
    .settings-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        overflow: hidden;
    }
    .settings-card-head {
        padding: 18px 22px;
        border-bottom: 1px solid var(--border);
    }
    .settings-card-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--text);
        margin: 0;
        letter-spacing: -0.2px;
    }
    .settings-card-desc {
        font-size: 12px;
        color: var(--text-secondary);
        margin: 4px 0 0;
    }
    .settings-card-body {
        padding: 20px 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .settings-card-foot {
        padding: 14px 22px;
        border-top: 1px solid var(--border);
        background: var(--surface-muted);
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    /* ---------- Form fields ---------- */
    .field-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .field-grid > * { min-width: 0; }
    @media (max-width: 575.98px) {
        .field-grid { grid-template-columns: 1fr; }
    }

    .field-hint {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 6px 0 0;
    }

    /* Password field with show/hide toggle */
    .password-wrap {
        position: relative;
    }
    .password-wrap .form-control-app {
        padding-right: 38px;
    }
    .password-toggle {
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        width: 30px;
        height: 30px;
        border: 0;
        background: transparent;
        color: var(--text-tertiary);
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 120ms ease, color 120ms ease;
    }
    .password-toggle:hover { background: var(--surface-hover); color: var(--text); }

    /* ---------- "Forgot password?" link ---------- */
    .label-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 6px;
    }
    .label-row .form-label-app { margin: 0; }
    .link-btn {
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--info);
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.4;
        border-radius: 4px;
        cursor: pointer;
    }
    .link-btn:hover { text-decoration: underline; }
    .link-btn:focus-visible {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }

    /* ---------- Forgot-password modal ---------- */
    #forgotPasswordModal [hidden] { display: none !important; }

    .fpw-steps {
        display: flex;
        gap: 6px;
        padding: 0 24px;
        margin-top: -4px;
    }
    .fpw-step {
        flex: 1;
        height: 3px;
        border-radius: 999px;
        background: var(--border);
        transition: background-color 180ms ease;
    }
    .fpw-step.is-active,
    .fpw-step.is-done { background: var(--accent); }

    .fpw-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        margin-bottom: 16px;
        background: var(--info-bg);
        border-radius: 8px;
        color: var(--text-secondary);
        font-size: 12px;
        line-height: 1.5;
    }
    .fpw-note i {
        flex-shrink: 0;
        margin-top: 1px;
        color: var(--info);
        font-size: 15px;
    }
    .fpw-note strong { color: var(--text); font-weight: 600; }
    .fpw-note-sub {
        display: block;
        margin-top: 2px;
        color: var(--text-tertiary);
        font-size: 11px;
    }

    .fpw-otp {
        display: flex;
        gap: 8px;
    }
    .fpw-otp input {
        flex: 1;
        min-width: 0;
        height: 46px;
        padding: 0;
        text-align: center;
        font-family: inherit;
        font-size: 18px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: var(--text);
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        transition: border-color 120ms ease, box-shadow 120ms ease;
    }
    .fpw-otp input:focus {
        outline: none;
        border-color: var(--border-strong);
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
    }
    .fpw-otp input.is-invalid { border-color: var(--danger); }

    .fpw-error {
        display: none;
        margin: 10px 0 0;
        color: var(--danger);
        font-size: 12px;
    }
    .fpw-error.is-visible { display: block; }

    .fpw-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 14px;
    }
    .fpw-link {
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--info);
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        border-radius: 4px;
        cursor: pointer;
    }
    .fpw-link:hover { text-decoration: underline; }
    .fpw-link:focus-visible {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }
    .fpw-link:disabled {
        color: var(--text-tertiary);
        text-decoration: none;
        cursor: not-allowed;
    }
    .fpw-hint { color: var(--text-tertiary); font-size: 11px; }

    .fpw-success {
        padding-top: 34px;
        padding-bottom: 34px;
        text-align: center;
    }
    .fpw-success-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        margin-bottom: 14px;
        border-radius: 50%;
        background: var(--success-bg);
        color: var(--success);
        font-size: 24px;
    }
    .fpw-success-title {
        margin: 0 0 6px;
        color: var(--text);
        font-size: 16px;
        font-weight: 600;
    }
    .fpw-success-text {
        max-width: 320px;
        margin: 0 auto;
        color: var(--text-secondary);
        font-size: 13px;
        line-height: 1.6;
    }

    @media (max-width: 575.98px) {
        .fpw-otp { gap: 6px; }
        .fpw-otp input { height: 42px; font-size: 16px; }
    }

    /* ---------- Toggle row ---------- */
    .toggle-row {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 4px 0;
        cursor: pointer;
        min-width: 0;
    }
    .toggle-row > * { min-width: 0; }
    .toggle-text { flex: 1; min-width: 0; }
    .toggle-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
        margin: 0;
    }
    .toggle-desc {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 2px 0 0;
    }
    .toggle-row input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .toggle-switch {
        position: relative;
        width: 40px;
        height: 22px;
        background: var(--border-strong);
        border-radius: 999px;
        transition: background-color 200ms ease;
        flex-shrink: 0;
    }
    .toggle-switch::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 18px;
        height: 18px;
        background: #fff;
        border-radius: 50%;
        transition: transform 200ms cubic-bezier(0.2, 0.8, 0.2, 1);
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    .toggle-row input[type="checkbox"]:checked ~ .toggle-switch {
        background: var(--accent);
    }
    .toggle-row input[type="checkbox"]:checked ~ .toggle-switch::after {
        background: var(--accent-contrast);
        transform: translateX(18px);
    }
    .toggle-row input[type="checkbox"]:focus-visible ~ .toggle-switch {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }

    /* ---------- Account summary (aside) ---------- */
    .summary-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 22px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        text-align: center;
    }
    .summary-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--surface-hover), var(--surface-muted));
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 600;
        text-transform: uppercase;
        border: 1px solid var(--border);
        margin: 0 auto 14px;
    }
    .summary-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .summary-email {
        font-size: 12px;
        color: var(--text-secondary);
        margin: 2px 0 10px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 9px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
        white-space: nowrap;
    }
    .role-badge i { font-size: 10px; }
    .role-badge.is-admin { background: var(--info-bg);       color: var(--info); }
    .role-badge.is-user  { background: var(--surface-muted); color: var(--text-secondary); }

    .summary-divider {
        height: 1px;
        background: var(--border);
        margin: 18px 0;
    }
    .summary-rows {
        display: flex;
        flex-direction: column;
        gap: 10px;
        text-align: left;
    }
    .summary-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 12px;
        min-width: 0;
    }
    .summary-row dt {
        color: var(--text-tertiary);
        margin: 0;
        flex-shrink: 0;
    }
    .summary-row dd {
        margin: 0;
        color: var(--text);
        font-weight: 500;
        text-align: right;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
        font-variant-numeric: tabular-nums;
    }

    /* ---------- Aside tip card ---------- */
    .tip-card {
        background: var(--info-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 16px 18px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }
    .tip-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: var(--surface);
        color: var(--info);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
        border: 1px solid var(--border);
    }
    .tip-body { min-width: 0; flex: 1; }
    .tip-title {
        font-size: 12px;
        font-weight: 600;
        color: var(--text);
        margin: 0 0 3px;
    }
    .tip-text {
        font-size: 11px;
        color: var(--text-secondary);
        margin: 0;
        line-height: 1.5;
    }

    /* ---------- Password strength meter ---------- */
    .pwd-meter {
        display: flex;
        gap: 4px;
        margin-top: 8px;
    }
    .pwd-meter-bar {
        flex: 1;
        height: 4px;
        border-radius: 2px;
        background: var(--surface-hover);
        transition: background-color 200ms ease;
    }
    .pwd-meter-bar.is-filled-weak   { background: var(--danger); }
    .pwd-meter-bar.is-filled-medium { background: var(--warning); }
    .pwd-meter-bar.is-filled-strong { background: var(--success); }
    .pwd-meter-label {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 6px 0 0;
    }

    /* ---------- Toast ---------- */
    .toast-stack {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 2000;
        display: flex;
        flex-direction: column;
        gap: 8px;
        pointer-events: none;
    }
    .toast-app {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: var(--accent);
        color: var(--accent-contrast);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 500;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        pointer-events: auto;
        animation: toastIn 200ms ease both;
        max-width: 340px;
    }
    .toast-app.is-error { background: var(--danger); }
    .toast-app i { font-size: 14px; flex-shrink: 0; }
    .toast-app.toast-out { animation: toastOut 200ms ease both; }
    @keyframes toastIn  { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes toastOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(8px); } }
</style>


<link rel="stylesheet" href="<?= asset('/assets/css/settings-preferences.css?v=2026110707') ?>">

<main class="dashboard-content">

    <!-- ==========================================================
         Page header
         ========================================================== -->
    <section class="page-header" aria-labelledby="pageTitle">
        <div class="min-w-0">
            <h1 class="page-title" id="pageTitle">Settings</h1>
            <p class="page-subtitle">
                Manage your profile, security and preferences
            </p>
        </div>
    </section>

    <!-- ==========================================================
         Settings grid
         ========================================================== -->
    <div class="settings-grid">

        <!-- ===== MAIN COLUMN (forms) ===== -->
        <div class="settings-main">

            <!-- ---------- Profile card ---------- -->
            <div class="settings-card">
                <div class="settings-card-head">
                    <h2 class="settings-card-title">Profile</h2>
                    <p class="settings-card-desc">Update your display name and email address. <a href="/profile">View full profile</a></p>
                </div>

                <form method="post" action="/settings/profile" autocomplete="off">
                    <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">

                    <div class="settings-card-body">
                        <div class="field-grid">
                            <div>
                                <label class="form-label-app" for="p-username">Username</label>
                                <input class="form-control-app"
                                       type="text"
                                       id="p-username"
                                       name="username"
                                       value="<?= $e($username) ?>"
                                       required
                                       minlength="1"
                                       maxlength="50"
                                       placeholder="e.g. jdoe">
                            </div>
                            <div>
                                <label class="form-label-app" for="p-email">Email address</label>
                                <input class="form-control-app"
                                       type="email"
                                       id="p-email"
                                       name="email"
                                       value="<?= $e($email) ?>"
                                       required
                                       maxlength="150"
                                       placeholder="name@example.com">
                            </div>
                        </div>
                        <p class="field-hint">Your email is used for sign-in and notifications.</p>
                    </div>

                    <div class="settings-card-foot">
                        <button type="submit" class="btn-primary-app">
                            <i class="bi bi-check2" aria-hidden="true"></i>Save profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- ---------- Password card ---------- -->
            <div class="settings-card">
                <div class="settings-card-head">
                    <h2 class="settings-card-title">Change password</h2>
                    <p class="settings-card-desc">Use a strong password you don't reuse elsewhere.</p>
                </div>

                <form method="post" action="/settings/password" autocomplete="off" id="pwd-form">
                    <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">

                    <div class="settings-card-body">
                        <div>
                            <div class="label-row">
                                <label class="form-label-app" for="p-current">Current password</label>
                                <button type="button"
                                        class="link-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#forgotPasswordModal">
                                    Forgot password?
                                </button>
                            </div>
                            <div class="password-wrap">
                                <input class="form-control-app"
                                       type="password"
                                       id="p-current"
                                       name="current_password"
                                       required
                                       autocomplete="current-password"
                                       placeholder="Enter your current password">
                                <button type="button"
                                        class="password-toggle"
                                        data-target="p-current"
                                        aria-label="Show password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="field-grid">
                            <div>
                                <label class="form-label-app" for="p-new">New password</label>
                                <div class="password-wrap">
                                    <input class="form-control-app"
                                           type="password"
                                           id="p-new"
                                           name="new_password"
                                           required
                                           minlength="8"
                                           autocomplete="new-password"
                                           placeholder="Min. 8 characters">
                                    <button type="button"
                                            class="password-toggle"
                                            data-target="p-new"
                                            aria-label="Show password">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>

                                <!-- Password strength meter -->
                                <div class="pwd-meter" id="pwd-meter" aria-hidden="true">
                                    <div class="pwd-meter-bar"></div>
                                    <div class="pwd-meter-bar"></div>
                                    <div class="pwd-meter-bar"></div>
                                    <div class="pwd-meter-bar"></div>
                                </div>
                                <p class="pwd-meter-label" id="pwd-meter-label">At least 8 characters</p>
                            </div>

                            <div>
                                <label class="form-label-app" for="p-confirm">Confirm new password</label>
                                <div class="password-wrap">
                                    <input class="form-control-app"
                                           type="password"
                                           id="p-confirm"
                                           name="confirm_password"
                                           required
                                           minlength="8"
                                           autocomplete="new-password"
                                           placeholder="Re-enter new password">
                                    <button type="button"
                                            class="password-toggle"
                                            data-target="p-confirm"
                                            aria-label="Show password">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <p class="pwd-meter-label" id="pwd-match-label"></p>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card-foot">
                        <button type="submit" class="btn-primary-app">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>Change password
                        </button>
                    </div>
                </form>
            </div>

            <!-- ---------- Preferences card ---------- -->
            <section class="preferences-panel" aria-labelledby="preferences-title">
                <header class="preferences-header">
                    <span class="preferences-heading-icon" aria-hidden="true"><i class="bi bi-sliders2"></i></span>
                    <div>
                        <h2 id="preferences-title">Preferences</h2>
                        <p>A workspace that feels like you.</p>
                    </div>
                </header>

                <form method="post" action="/settings/preferences" autocomplete="off" id="preferences-form">
                    <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">

                    <fieldset class="preferences-appearance">
                        <legend><i class="bi bi-circle-half" aria-hidden="true"></i>Appearance</legend>
                        <p class="preferences-description" id="appearance-description">Choose how your workspace looks.</p>
                        <div class="preferences-theme-grid" aria-describedby="appearance-description">
                            <?php foreach (['LIGHT' => 'Light', 'DARK' => 'Dark', 'SYSTEM' => 'Auto'] as $optionValue => $optionLabel): ?>
                                <label class="preferences-theme-option">
                                    <input type="radio" name="theme" value="<?= $optionValue ?>"
                                           aria-label="<?= $optionValue === 'SYSTEM' ? 'Auto — match device appearance' : $optionLabel ?>"
                                           <?= $theme === $optionValue ? 'checked' : '' ?>>
                                    <span class="preferences-theme-card">
                                        <span class="preferences-preview preferences-preview--<?= strtolower($optionValue) ?>" aria-hidden="true">
                                            <?php foreach ($optionValue === 'SYSTEM' ? ['light', 'dark'] : [strtolower($optionValue)] as $previewMode): ?>
                                                <span class="preferences-mini preferences-mini--<?= $previewMode ?>">
                                                    <span class="preferences-mini-toolbar"><b></b><b></b><b></b><span></span></span>
                                                    <span class="preferences-mini-layout">
                                                        <span class="preferences-mini-sidebar">
                                                            <b class="preferences-mini-brand"></b><b></b><b></b><b></b><b></b>
                                                        </span>
                                                        <span class="preferences-mini-content">
                                                            <span class="preferences-mini-title"></span>
                                                            <span class="preferences-mini-stats"><b></b><b></b></span>
                                                            <span class="preferences-mini-table"><b></b><b></b><b></b></span>
                                                        </span>
                                                    </span>
                                                </span>
                                            <?php endforeach; ?>
                                        </span>
                                        <span class="preferences-theme-caption">
                                            <span><?= $optionLabel ?></span>
                                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="preferences-auto-hint">Auto follows your device’s light or dark appearance.</p>
                    </fieldset>

                    <label class="preferences-row toggle-row" for="email_notifications">
                        <i class="bi bi-envelope preferences-row-icon" aria-hidden="true"></i>
                        <span class="preferences-row-copy">
                            <span class="preferences-row-label">Email notifications</span>
                            <span class="preferences-row-description" id="preferences-email-description">Account, order and security updates.</span>
                        </span>
                        <input type="checkbox" id="email_notifications" name="email_notifications" value="1"
                               role="switch" aria-describedby="preferences-email-description"
                               <?= $emailNotifs ? 'checked' : '' ?>>
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>

                    <div class="preferences-footer">
                        <button type="submit" class="btn-primary-app preferences-save-button">
                            <i class="bi bi-check2" aria-hidden="true"></i>Save preferences
                        </button>
                    </div>
                </form>
            </section>

            <!-- ---------- Biometric security card ---------- -->
            <div class="settings-card" id="biometricCard">
                <div class="settings-card-head">
                    <h2 class="settings-card-title">Biometric login</h2>
                    <p class="settings-card-desc">Enroll your face and add your mobile number to sign in without typing.</p>
                </div>

                <div class="settings-card-body" style="display:flex;flex-direction:column;gap:14px;">

                    <div class="biometric-row">
                        <span class="biometric-icon" aria-hidden="true"><i class="bi bi-person-bounding-box"></i></span>
                        <div class="biometric-info">
                            <p class="toggle-label">Face recognition</p>
                            <p class="toggle-desc" id="faceStatus">Checking…</p>
                        </div>
                        <div class="biometric-actions">
                            <button type="button" class="btn-secondary" id="faceEnrollBtn">
                                <i class="bi bi-camera" aria-hidden="true"></i>Enroll face
                            </button>
                            <button type="button" class="btn-secondary" id="faceRemoveBtn" hidden>
                                <i class="bi bi-trash3" aria-hidden="true"></i>Remove
                            </button>
                        </div>
                    </div>

                    <hr style="margin:4px 0;border:0;border-top:1px solid var(--border)">

                    <div class="biometric-row">
                        <span class="biometric-icon" aria-hidden="true"><i class="bi bi-phone"></i></span>
                        <div class="biometric-info">
                            <p class="toggle-label">Mobile number</p>
                            <?php
                            $settingsPhone = (string) ($security['phone'] ?? '');
                            $settingsPhoneVerified = !empty($security['phone_verified']);
                            ?>
                            <?php if ($settingsPhone !== ''): ?>
                                <p class="toggle-desc" id="phoneStatus">
                                    <?= $e($settingsPhone) ?> —
                                    <?= $settingsPhoneVerified ? 'verified — SMS sign-in enabled.' : 'not yet verified — verify on the profile page.' ?>
                                </p>
                            <?php else: ?>
                                <p class="toggle-desc" id="phoneStatus">No mobile number added yet.</p>
                            <?php endif; ?>
                        </div>
                        <div class="biometric-actions">
                            <button type="button" class="btn-secondary" id="phoneManageBtn"
                                    onclick="window.location.href='/profile#phoneCard'">
                                <i class="bi bi-phone" aria-hidden="true"></i>
                                <?= $settingsPhone === '' ? 'Add number' : ($settingsPhoneVerified ? 'Manage' : 'Verify') ?>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- ===== ASIDE COLUMN (summary) ===== -->
        <aside class="settings-aside" aria-label="Account summary">

            <!-- Account snapshot -->
            <div class="summary-card">
                <div class="summary-avatar" aria-hidden="true"><?= $e($initial) ?></div>
                <p class="summary-name"><?= $e($username !== '' ? $username : 'User') ?></p>
                <p class="summary-email"><?= $e($email !== '' ? $email : '—') ?></p>
                <span class="role-badge <?= $e($roleCls) ?>">
                    <i class="bi <?= $role === 'ADMIN' ? 'bi-shield-lock' : 'bi-person' ?>" aria-hidden="true"></i>
                    <?= $e($roleLabel) ?>
                </span>

                <div class="summary-divider"></div>

                <dl class="summary-rows">
                    <?php if ($accountId !== ''): ?>
                        <div class="summary-row">
                            <dt>Account ID</dt>
                            <dd>#<?= $e($accountId) ?></dd>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <dt>Member since</dt>
                        <dd><?= $e($fmtDate($createdAt)) ?></dd>
                    </div>
                    <div class="summary-row">
                        <dt>Theme</dt>
                        <dd id="summary-theme"><?= $e(ucfirst(strtolower($theme))) ?></dd>
                    </div>
                    <div class="summary-row">
                        <dt>Email alerts</dt>
                        <dd id="summary-email-alerts"><?= $emailNotifs ? 'Enabled' : 'Disabled' ?></dd>
                    </div>
                </dl>

                <form method="post" action="/logout" style="margin-top:14px;">
                    <input type="hidden" name="csrf_token" value="<?= $e($authCsrfToken ?? '') ?>">
                    <button type="submit" class="btn-secondary" style="width:100%;justify-content:center;display:inline-flex;align-items:center;gap:8px;">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sign out
                    </button>
                </form>
            </div>

            <!-- Security tip -->
            <div class="tip-card">
                <span class="tip-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                <div class="tip-body">
                    <p class="tip-title">Stay secure</p>
                    <p class="tip-text">Use a unique password and rotate it every few months. Enable email alerts to be notified of sign-ins from new devices.</p>
                </div>
            </div>

        </aside>

    </div>

</main>


<!-- ==========================================================
     MODAL: Forgot password (signed-in user, no email input)
     ========================================================== -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <div class="modal-header-app">
                <div>
                    <h2 class="modal-title-app" id="forgotPasswordTitle">Reset password</h2>
                    <p class="modal-subtitle-app">Verify your identity with a code sent to your account email.</p>
                </div>
                <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="fpw-steps" aria-hidden="true">
                <span class="fpw-step is-active" data-fpw-stepdot="code"></span>
                <span class="fpw-step" data-fpw-stepdot="password"></span>
            </div>

            <!-- ---------- STEP 1: verification code ---------- -->
            <div class="modal-body-app" data-fpw-pane="code">
                <div class="fpw-note">
                    <i class="bi bi-envelope-check" aria-hidden="true"></i>
                    <div>
                        We sent a 6-digit code to
                        <strong id="fpwEmail"><?= $e($maskedEmail) ?></strong>.
                        <span class="fpw-note-sub">The code expires in 10 minutes and can only be used once.</span>
                    </div>
                </div>

                <form id="fpwCodeForm" novalidate>
                    <div class="fpw-otp" id="fpwOtp">
                        <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" aria-label="Digit 1">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 2">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 3">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 4">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 5">
                        <input type="text" inputmode="numeric" maxlength="1" aria-label="Digit 6">
                    </div>

                    <p class="fpw-error" id="fpwCodeError" role="alert"></p>

                    <div class="fpw-meta">
                        <button type="button" class="fpw-link" id="fpwResendBtn">Resend code</button>
                        <span class="fpw-hint" id="fpwResendHint"></span>
                    </div>
                </form>
            </div>

            <!-- ---------- STEP 2: new password ---------- -->
            <div class="modal-body-app" data-fpw-pane="password" hidden>
                <form id="fpwResetForm" novalidate>
                    <div class="field-grid">
                        <div>
                            <label class="form-label-app" for="fpwNew">New password</label>
                            <div class="password-wrap">
                                <input class="form-control-app"
                                       type="password"
                                       id="fpwNew"
                                       required
                                       minlength="8"
                                       autocomplete="new-password"
                                       placeholder="Min. 8 characters">
                                <button type="button"
                                        class="password-toggle"
                                        data-target="fpwNew"
                                        aria-label="Show password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="pwd-meter" id="fpwMeter" aria-hidden="true">
                                <div class="pwd-meter-bar"></div>
                                <div class="pwd-meter-bar"></div>
                                <div class="pwd-meter-bar"></div>
                                <div class="pwd-meter-bar"></div>
                            </div>
                            <p class="pwd-meter-label" id="fpwMeterLabel">At least 8 characters</p>
                        </div>

                        <div>
                            <label class="form-label-app" for="fpwConfirm">Confirm new password</label>
                            <div class="password-wrap">
                                <input class="form-control-app"
                                       type="password"
                                       id="fpwConfirm"
                                       required
                                       minlength="8"
                                       autocomplete="new-password"
                                       placeholder="Re-enter new password">
                                <button type="button"
                                        class="password-toggle"
                                        data-target="fpwConfirm"
                                        aria-label="Show password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <p class="pwd-meter-label" id="fpwMatchLabel"></p>
                        </div>
                    </div>

                    <p class="fpw-error" id="fpwResetError" role="alert"></p>
                </form>
            </div>

            <!-- ---------- STEP 3: success ---------- -->
            <div class="modal-body-app fpw-success" data-fpw-pane="done" hidden>
                <span class="fpw-success-icon">
                    <i class="bi bi-check2" aria-hidden="true"></i>
                </span>
                <h3 class="fpw-success-title">Password updated</h3>
                <p class="fpw-success-text">
                    Your new password is now active. Use it the next time you sign in.
                </p>
            </div>

            <div class="modal-footer-app">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal" data-fpw-foot="code">
                    Cancel
                </button>
                <button type="submit" form="fpwCodeForm" class="btn-primary-app" id="fpwVerifyBtn" data-fpw-foot="code">
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>Verify code
                </button>

                <button type="button" class="btn-secondary" id="fpwBackBtn" data-fpw-foot="password" hidden>
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>Back
                </button>
                <button type="submit" form="fpwResetForm" class="btn-primary-app" id="fpwResetBtn" data-fpw-foot="password" hidden>
                    <i class="bi bi-shield-check" aria-hidden="true"></i>Reset password
                </button>

                <button type="button" class="btn-primary-app" data-bs-dismiss="modal" data-fpw-foot="done" hidden>
                    <i class="bi bi-check2" aria-hidden="true"></i>Done
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==========================================================
     Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<script src="<?= asset('/assets/js/settings-preferences.js?v=2026110707') ?>" defer></script>

<script>
/* =========================================================
   TOAST
   ========================================================= */
(function () {
    var stack = document.getElementById('toastStack');

    window.showToast = function (message, icon, isError) {
        if (!stack) return;
        var t = document.createElement('div');
        t.className = 'toast-app' + (isError ? ' is-error' : '');
        t.innerHTML = '<i class="bi ' + (icon || (isError ? 'bi-exclamation-circle' : 'bi-check2-circle')) + '" aria-hidden="true"></i><span></span>';
        t.querySelector('span').textContent = message;
        stack.appendChild(t);
        setTimeout(function () {
            t.classList.add('toast-out');
            t.addEventListener('animationend', function () { t.remove(); }, { once: true });
        }, 3600);
    };
})();

/* =========================================================
   FLASH
   ========================================================= */
<?php if ($flashMsg !== ''): ?>
    document.addEventListener('DOMContentLoaded', function () {
        window.showToast(
            <?= json_encode($flashMsg) ?>,
            <?= json_encode($flashType === 'danger' ? 'bi-exclamation-circle' : 'bi-check2-circle') ?>,
            <?= $flashType === 'danger' ? 'true' : 'false' ?>
        );
    });
<?php endif; ?>

/* =========================================================
   PASSWORD SHOW / HIDE TOGGLES
   ========================================================= */
(function () {
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-target');
            var input = document.getElementById(id);
            if (!input) return;

            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            var icon = btn.querySelector('i');
            if (icon) {
                icon.className = 'bi ' + (isPassword ? 'bi-eye-slash' : 'bi-eye');
            }
            btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    });
})();

/* =========================================================
   PASSWORD STRENGTH + MATCH
   ========================================================= */
(function () {
    var input   = document.getElementById('p-new');
    var confirm = document.getElementById('p-confirm');
    var form    = document.getElementById('pwd-form');
    if (!input || !form) return;

    var meterBars = document.querySelectorAll('#pwd-meter .pwd-meter-bar');
    var meterLabel = document.getElementById('pwd-meter-label');
    var matchLabel = document.getElementById('pwd-match-label');

    function scorePassword(pw) {
        var score = 0;
        if (!pw) return 0;
        if (pw.length >= 8) score++;
        if (pw.length >= 12) score++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
        if (/\d/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return Math.min(4, score);
    }

    function renderMeter() {
        var pw = input.value || '';
        var s = scorePassword(pw);

        meterBars.forEach(function (bar, i) {
            bar.className = 'pwd-meter-bar';
            if (i < s) {
                if (s <= 1) bar.classList.add('is-filled-weak');
                else if (s <= 3) bar.classList.add('is-filled-medium');
                else bar.classList.add('is-filled-strong');
            }
        });

        if (!pw) {
            meterLabel.textContent = 'At least 8 characters';
        } else if (s <= 1) {
            meterLabel.textContent = 'Weak — add length, numbers and symbols';
        } else if (s <= 3) {
            meterLabel.textContent = 'Fair — consider making it longer';
        } else {
            meterLabel.textContent = 'Strong password';
        }
    }

    function renderMatch() {
        if (!confirm || !matchLabel) return;
        if (!confirm.value) {
            matchLabel.textContent = '';
            confirm.style.borderColor = '';
            return;
        }
        if (confirm.value === input.value) {
            matchLabel.textContent = 'Passwords match';
            matchLabel.style.color = 'var(--success)';
            confirm.style.borderColor = '';
        } else {
            matchLabel.textContent = 'Passwords do not match';
            matchLabel.style.color = 'var(--danger)';
            confirm.style.borderColor = 'var(--danger)';
        }
    }

    input.addEventListener('input', function () { renderMeter(); renderMatch(); });
    if (confirm) confirm.addEventListener('input', renderMatch);

    form.addEventListener('submit', function (e) {
        if (confirm && confirm.value !== input.value) {
            e.preventDefault();
            window.showToast('Passwords do not match', 'bi-exclamation-circle', true);
            confirm.focus();
        }
    });

    renderMeter();
})();

/* =========================================================
   OPTIONAL: live-update the summary card on field edits
   ========================================================= */
(function () {
    var usernameInput = document.getElementById('p-username');
    var emailInput    = document.getElementById('p-email');
    var nameEl  = document.querySelector('.summary-name');
    var emailEl = document.querySelector('.summary-email');
    var avatarEl = document.querySelector('.summary-avatar');

    function refresh() {
        if (usernameInput && nameEl && usernameInput.value.trim() !== '') {
            nameEl.textContent = usernameInput.value.trim();
        }
        if (usernameInput && avatarEl) {
            var first = usernameInput.value.trim().charAt(0).toUpperCase() || '?';
            avatarEl.textContent = first;
        }
        if (emailInput && emailEl && emailInput.value.trim() !== '') {
            emailEl.textContent = emailInput.value.trim();
        }
    }

    if (usernameInput) usernameInput.addEventListener('input', refresh);
    if (emailInput)    emailInput.addEventListener('input', refresh);
})();

/* =========================================================
   BIOMETRIC: FACE
   ========================================================= */
(function () {
    'use strict';

    /* Auth-level token for the /face/* endpoints
       (validated against $_SESSION['csrf_token'] — NOT the settings form token) */
    var CSRF_TOKEN = '<?= $e($authCsrfToken ?? '') ?>';
    var LOCAL_LIBRARY = '<?= asset('/assets/face-api/face-api.min.js?v=2026110707') ?>';
    var CDN_LIBRARY = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
    var MODEL_URIS = ['<?= asset('/assets/face-api/weights') ?>', 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights'];

    if (!window.isSecureContext) {
        setStatus('Requires a secure origin (serve over http://localhost).');
        return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('Camera is not available in this browser.');
        return;
    }

    function setStatus(text) {
        var el = document.getElementById('faceStatus');
        if (el) el.textContent = text;
    }

    /* ---------- shared helpers ---------- */

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var tag = document.createElement('script');
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
        for (var i = 0; i < MODEL_URIS.length; i++) {
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URIS[i]),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URIS[i]),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URIS[i])
                ]);
                return;
            } catch (error) { /* try next source */ }
        }
        throw new Error('Face model could not be loaded.');
    }

    function detectorOptions() {
        return new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.4 });
    }

    /* ---------- premium biometric modal ---------- */

    var faceModal = window.BioModal();
    var stream = null;

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
        faceModal.video.srcObject = null;
    }

    faceModal.onClose(stopCamera);

    async function startCamera() {
        if (stream && stream.getTracks().some(function (t) { return t.readyState === 'live'; })) {
            faceModal.video.srcObject = stream;
            try { await faceModal.video.play(); } catch (error) { /* already playing */ }
            return;
        }

        faceModal.cameraBusy(true);
        faceModal.note('Starting camera…');

        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
            audio: false
        });

        faceModal.video.srcObject = stream;
        await faceModal.video.play();
        faceModal.cameraBusy(false);
        faceModal.note('');
    }

    /* Detect one face descriptor; resolves null repeatedly until a face shows */
    function detectDescriptor() {
        return faceapi
            .detectSingleFace(faceModal.video, detectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor()
            .then(function (result) {
                return result ? Array.from(result.descriptor) : null;
            });
    }

    /* Polling UX */
    function watchFace(onFace, onNoFace) {
        var alive = true;
        var busy = false;

        function tick() {
            if (!alive) return;
            if (!faceModal.isOpen()) { alive = false; return; }
            if (busy) { setTimeout(tick, 300); return; }
            busy = true;
            detectDescriptor().then(function (descriptor) {
                busy = false;
                if (!alive) return;
                if (descriptor) onFace(descriptor); else onNoFace();
                setTimeout(tick, 300);
            }).catch(function () {
                busy = false;
                if (alive) { onNoFace(); setTimeout(tick, 600); }
            });
        }

        tick();
        return function stop() { alive = false; };
    }

    /* ---------- FACE ENROLLMENT ---------- */

    var enrollBtn = document.getElementById('faceEnrollBtn');
    var removeBtn = document.getElementById('faceRemoveBtn');

    async function refreshFaceStatus() {
        try {
            var res = await fetch('/face/enrollments', { headers: { 'X-CSRF-Token': CSRF_TOKEN } });
            var data = await res.json();
            if (data && data.success && data.count > 0) {
                setStatus(data.count + ' face scan' + (data.count > 1 ? 's' : '') + ' enrolled — last updated ' + (data.faces[data.faces.length - 1].created_at || '').slice(0, 10));
                removeBtn.hidden = false;
            } else {
                setStatus('Not enrolled yet — enroll to enable face login.');
                removeBtn.hidden = true;
            }
        } catch (error) {
            setStatus('Unable to check enrollment status.');
        }
    }

    enrollBtn.addEventListener('click', function () {
        faceModal.open();
        renderEnrollIntro();
    });

    function renderEnrollIntro() {
        faceModal.header('Enroll your face', 'Add your face so you can sign in without a password.', 'face');
        faceModal.icon('face');
        faceModal.showCamera(false);
        faceModal.meta(false);
        faceModal.state(
            '<div class="bio-illustration">' + faceModal.faceArt() + '</div>' +
            '<ul class="bio-guidance">' +
            '  <li><i class="bi bi-brightness-high" aria-hidden="true"></i><span>Capture in even, front-facing light</span></li>' +
            '  <li><i class="bi bi-person-bounding-box" aria-hidden="true"></i><span>Three scans: straight, slightly left, slightly right</span></li>' +
            '  <li><i class="bi bi-shield-lock" aria-hidden="true"></i><span>Only a mathematical face pattern is stored — never a photo</span></li>' +
            '</ul>'
        );
        faceModal.actions([
            { label: 'Start camera', variant: 'primary', onClick: beginEnrollment },
            { label: 'Cancel', variant: 'ghost', onClick: function () { faceModal.close(); } }
        ]);
    }

    async function beginEnrollment() {
        // Maximum number of scans allowed (mirrors backend MAX_FACES)
        const MAX_FACES = <?php echo (int) ($maxFaces ?? 3); ?>;
        var samples = [];
        var currentFace = null;
        var stopWatch = null;
        var captureBtn = null;
        var saveBtn = null;

        faceModal.header('Enroll your face', 'Capture up to ' + MAX_FACES + ' scans — vary the angle slightly each time.', 'face');
        faceModal.showCamera(true);
        faceModal.compactCamera(true);
        faceModal.meta(true);
        faceModal.progressVisible(false);
        faceModal.status('Starting camera…', null);
        faceModal.progress(0);
        renderSlots();
        renderActions();

        function renderSlots() {
            var dots = '';
            for (var i = 0; i < MAX_FACES; i++) {
                dots += '<span class="bio-slot' + (i < samples.length ? ' is-filled' : '') + '"></span>';
            }
            faceModal.state(
                '<div class="bio-slots">' + dots + '</div>' +
                '<p class="bio-slots-label">' + samples.length + ' of ' + MAX_FACES + ' scans captured</p>'
            );
        }

        function renderActions() {
            faceModal.actions([
                {
                    label: 'Capture scan (' + samples.length + '/' + MAX_FACES + ')',
                    variant: 'primary',
                    id: 'bioCaptureBtn',
                    disabled: true,
                    ref: function (btn) { captureBtn = btn; },
                    onClick: captureScan
                },
                {
                    label: 'Save enrollment',
                    variant: 'secondary',
                    id: 'bioSaveBtn',
                    disabled: true,
                    ref: function (btn) { saveBtn = btn; },
                    onClick: saveEnrollment
                },
                {
                    label: 'Cancel',
                    variant: 'ghost',
                    onClick: function () {
                        if (stopWatch) stopWatch();
                        faceModal.close();
                    }
                }
            ]);
        }

        function captureScan() {
            if (!captureBtn || captureBtn.disabled) return;
            captureBtn.disabled = true;
            faceModal.status('Capturing…', null);

            detectDescriptor().then(function (descriptor) {
                if (!faceModal.isOpen()) return;

                if (!descriptor) {
                    faceModal.status('No face visible — center your face', 'warn');
                    captureBtn.disabled = !currentFace;
                    return;
                }

                samples.push(descriptor);
                renderSlots();
                if (captureBtn) {
                    captureBtn.querySelector('span').textContent =
                        'Capture scan (' + samples.length + '/' + MAX_FACES + ')';
                }
                if (saveBtn) saveBtn.disabled = false;

                faceModal.status('Captured ' + samples.length + ' of ' + MAX_FACES, 'success');

                if (samples.length >= MAX_FACES) {
                    if (captureBtn) captureBtn.disabled = true;
                    faceModal.status('All scans captured — save to finish', 'success');
                } else {
                    setTimeout(function () {
                        if (faceModal.isOpen() && samples.length < MAX_FACES && currentFace && captureBtn) {
                            captureBtn.disabled = false;
                            faceModal.status('Face detected', 'success');
                        }
                    }, 700);
                }
            });
        }

        async function saveEnrollment() {
            if (!samples.length) return;
            if (saveBtn) saveBtn.disabled = true;
            if (stopWatch) { stopWatch(); stopWatch = null; }

            faceModal.header('Saving your face', 'Please keep this window open.', 'shield');
            faceModal.showCamera(false);
            faceModal.meta(false);
            faceModal.state(
                '<div class="bio-center">' +
                '  <span class="bio-loader" aria-hidden="true"></span>' +
                '  <p class="bio-processing-text">Saving your enrollment…</p>' +
                '</div>'
            );
            faceModal.actions([
                { label: 'Cancel', variant: 'ghost', onClick: function () { faceModal.close(); } }
            ]);

            try {
                var res = await fetch('/face/enroll', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                    body: JSON.stringify({ descriptors: samples })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.message || 'Enrollment failed.');

                faceModal.header('Face enrollment', 'Your face can now be used to sign in.', 'shield');
                faceModal.state(
                    '<div class="bio-state">' +
                    '  <span class="bio-state-icon is-success"><i class="bi bi-check-lg" aria-hidden="true"></i></span>' +
                    '  <h3>Face enrolled</h3>' +
                    '  <p>' + samples.length + ' scan' + (samples.length > 1 ? 's' : '') +
                    '      saved. You can now sign in with your face.</p>' +
                    '</div>'
                );
                faceModal.actions([
                    { label: 'Done', variant: 'primary', onClick: function () { faceModal.close(); } }
                ]);

                window.showToast('Face enrolled — you can now log in with your face!', 'bi-person-check');
                refreshFaceStatus();
            } catch (error) {
                renderEnrollError(error.message || 'Enrollment failed.');
            }
        }

        function renderEnrollError(message) {
            faceModal.header('Face enrollment', 'Add your face so you can sign in without a password.', 'face');
            faceModal.showCamera(false);
            faceModal.meta(false);
            faceModal.state(
                '<div class="bio-state">' +
                '  <span class="bio-state-icon is-error"><i class="bi bi-exclamation-lg" aria-hidden="true"></i></span>' +
                '  <h3>Enrollment failed</h3>' +
                '  <p>' + faceModal.escapeHtml(message) + '</p>' +
                '</div>'
            );
            faceModal.actions([
                { label: 'Try again', variant: 'primary', onClick: beginEnrollment },
                { label: 'Cancel', variant: 'ghost', onClick: function () { faceModal.close(); } }
            ]);
        }

        try {
            await startCamera();
        } catch (error) {
            var denied = error && (error.name === 'NotAllowedError' || error.name === 'SecurityError');
            faceModal.header('Camera access required', 'Allow camera access to enroll your face.', 'camera');
            faceModal.showCamera(false);
            faceModal.meta(false);
            faceModal.state(
                '<div class="bio-center" style="padding:10px 0 0;">' +
                '  <span class="bio-state-icon is-neutral"><i class="bi bi-camera-video" aria-hidden="true"></i></span>' +
                '  <p class="bio-permission-copy">' +
                (denied ? 'Camera permission was denied.' : 'The camera could not be started on this device.') +
                '  </p>' +
                '</div>'
            );
            faceModal.actions([
                { label: 'Enable camera', variant: 'primary', onClick: beginEnrollment },
                { label: 'Not now', variant: 'ghost', onClick: function () { faceModal.close(); } }
            ]);
            return;
        }

        if (!faceModal.isOpen()) return;

        faceModal.status('Loading face model…', null);
        try {
            await loadModels();
        } catch (error) {
            renderEnrollError('The face model could not be loaded. Check your connection and try again.');
            return;
        }
        if (!faceModal.isOpen()) return;

        faceModal.status('Look into the camera', null);
        stopWatch = watchFace(function (descriptor) {
            currentFace = descriptor;
            if (captureBtn && captureBtn.disabled && samples.length < MAX_FACES) {
                captureBtn.disabled = false;
                faceModal.status('Face detected', 'success');
            }
        }, function () {
            currentFace = null;
            if (captureBtn && samples.length < MAX_FACES && !captureBtn.disabled) {
                captureBtn.disabled = true;
                faceModal.status('Look into the camera', null);
            }
        });

        renderSlots();
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', async function () {
            if (!window.confirm('Remove your face enrollment? Face login will stop working on this account.')) return;
            try {
                var res = await fetch('/face/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                    body: JSON.stringify({})
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.message || 'Unable to remove.');
                window.showToast('Face enrollment removed.', 'bi-trash3');
                refreshFaceStatus();
            } catch (error) {
                window.showToast(error.message || 'Unable to remove.', 'bi-exclamation-circle', true);
            }
        });
    }

    /* ---------- Boot ---------- */
    refreshFaceStatus();
})();

/* Face enrollment card layout (row) */

/* =========================================================
   FORGOT PASSWORD MODAL (signed-in user)
   Step 1: verify emailed code -> Step 2: set new password
   ========================================================= */
(function () {
    'use strict';

    var modalEl = document.getElementById('forgotPasswordModal');
    if (!modalEl || !window.bootstrap) return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    var CSRF_TOKEN = '<?= $e($authCsrfToken ?? '') ?>';
    var API_BASE   = '/settings/password-reset';

    var panes    = modalEl.querySelectorAll('[data-fpw-pane]');
    var stepDots = modalEl.querySelectorAll('[data-fpw-stepdot]');
    var codeFoot = modalEl.querySelectorAll('[data-fpw-foot="code"]');
    var passFoot = modalEl.querySelectorAll('[data-fpw-foot="password"]');
    var doneFoot = modalEl.querySelectorAll('[data-fpw-foot="done"]');

    var emailEl     = document.getElementById('fpwEmail');
    var otpInputs   = Array.prototype.slice.call(document.querySelectorAll('#fpwOtp input'));
    var codeForm    = document.getElementById('fpwCodeForm');
    var codeError   = document.getElementById('fpwCodeError');
    var verifyBtn   = document.getElementById('fpwVerifyBtn');
    var resendBtn   = document.getElementById('fpwResendBtn');
    var resendHint  = document.getElementById('fpwResendHint');

    var resetForm   = document.getElementById('fpwResetForm');
    var newInput    = document.getElementById('fpwNew');
    var confirmInput = document.getElementById('fpwConfirm');
    var resetBtn    = document.getElementById('fpwResetBtn');
    var resetError  = document.getElementById('fpwResetError');
    var backBtn     = document.getElementById('fpwBackBtn');

    var meterBars   = document.querySelectorAll('#fpwMeter .pwd-meter-bar');
    var meterLabel  = document.getElementById('fpwMeterLabel');
    var matchLabel  = document.getElementById('fpwMatchLabel');

    var resetToken = '';
    var resendTimer = null;

    /* ---------- API ---------- */
    function apiPost(path, payload) {
        return fetch(API_BASE + path, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify(payload || {})
        }).then(function (response) {
            return response.json().catch(function () {
                return { ok: false, error: 'Something went wrong. Please try again.' };
            }).then(function (data) {
                data.status = response.status;
                return data;
            });
        });
    }

    /* ---------- UI helpers ---------- */
    function showStep(name) {
        panes.forEach(function (pane) {
            pane.hidden = pane.getAttribute('data-fpw-pane') !== name;
        });

        codeFoot.forEach(function (el) { el.hidden = name !== 'code'; });
        passFoot.forEach(function (el) { el.hidden = name !== 'password'; });
        doneFoot.forEach(function (el) { el.hidden = name !== 'done'; });

        stepDots.forEach(function (dot) {
            var key = dot.getAttribute('data-fpw-stepdot');
            dot.classList.toggle('is-active', key === name);
            dot.classList.toggle('is-done', name === 'done' || (name === 'password' && key === 'code'));
        });
    }

    function setError(el, message) {
        el.textContent = message || '';
        el.classList.toggle('is-visible', !!message);
    }

    function otpValue() {
        return otpInputs.map(function (input) { return input.value; }).join('');
    }

    function clearOtp() {
        otpInputs.forEach(function (input) {
            input.value = '';
            input.classList.remove('is-invalid');
        });
    }

    function stopResendTimer() {
        if (resendTimer) {
            window.clearInterval(resendTimer);
            resendTimer = null;
        }
    }

    function startResendCooldown() {
        var seconds = 30;

        stopResendTimer();
        resendBtn.disabled = true;
        resendHint.textContent = '';

        resendTimer = window.setInterval(function () {
            seconds -= 1;

            if (seconds <= 0) {
                stopResendTimer();
                resendBtn.disabled = false;
                resendHint.textContent = '';
                return;
            }

            resendHint.textContent = 'Resend in ' + seconds + 's';
        }, 1000);
    }

    function resetModalState() {
        stopResendTimer();
        resetToken = '';
        clearOtp();
        setError(codeError, '');
        setError(resetError, '');
        resendBtn.disabled = false;
        resendHint.textContent = '';

        resetForm.reset();
        [newInput, confirmInput].forEach(function (input) {
            input.style.borderColor = '';

            // Put the fields back to "hidden" if the user revealed them.
            if (input.type !== 'password') {
                input.type = 'password';

                var toggle = modalEl.querySelector(
                    '.password-toggle[data-target="' + input.id + '"]'
                );
                var icon = toggle && toggle.querySelector('i');

                if (icon) icon.className = 'bi bi-eye';
                if (toggle) toggle.setAttribute('aria-label', 'Show password');
            }
        });

        if (matchLabel) {
            matchLabel.textContent = '';
            matchLabel.style.color = '';
        }

        resetMeter();
    }

    /* ---------- SEND CODE ---------- */
    function sendCode(isResend) {
        if (resendBtn.disabled && !isResend) return;

        resendBtn.disabled = true;
        resendHint.textContent = 'Sending…';
        setError(codeError, '');

        return apiPost('/request', {})
            .then(function (data) {
                if (!data.ok) {
                    resendBtn.disabled = false;
                    resendHint.textContent = '';
                    throw new Error(data.error || 'Unable to send the code. Please try again.');
                }

                if (data.masked_email && emailEl) {
                    emailEl.textContent = data.masked_email;
                }

                startResendCooldown();
                return data;
            })
            .catch(function (error) {
                resendBtn.disabled = false;
                resendHint.textContent = '';
                setError(codeError, error.message || 'Unable to send the code. Please try again.');
            });
    }

    /* ---------- OTP BEHAVIOUR ---------- */
    otpInputs.forEach(function (input, index) {
        input.addEventListener('input', function () {
            input.value = input.value.replace(/\D/g, '').slice(0, 1);
            setError(codeError, '');
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

            otpInputs[Math.min(digits.length, otpInputs.length - 1)].focus();
        });
    });

    /* ---------- VERIFY CODE ---------- */
    codeForm.addEventListener('submit', function (event) {
        event.preventDefault();

        var code = otpValue();

        if (!/^\d{6}$/.test(code)) {
            setError(codeError, 'Enter the full 6-digit code.');
            otpInputs.forEach(function (el) { el.classList.add('is-invalid'); });
            otpInputs[0].focus();
            return;
        }

        verifyBtn.disabled = true;

        apiPost('/verify', { code: code })
            .then(function (data) {
                if (!data.ok || !data.token) {
                    throw new Error(data.error || 'The code you entered is incorrect.');
                }

                resetToken = data.token;
                setError(codeError, '');
                showStep('password');

                window.setTimeout(function () {
                    newInput.focus();
                }, 120);
            })
            .catch(function (error) {
                setError(codeError, error.message || 'The code you entered is incorrect.');
                otpInputs.forEach(function (el) { el.classList.add('is-invalid'); });
                clearOtp();
                otpInputs[0].focus();
            })
            .finally(function () {
                verifyBtn.disabled = false;
            });
    });

    resendBtn.addEventListener('click', function () {
        clearOtp();
        setError(codeError, '');
        sendCode(true).then(function () {
            otpInputs[0].focus();
        });
    });

    /* ---------- PASSWORD STRENGTH + MATCH ---------- */
    function scorePassword(pw) {
        var score = 0;
        if (!pw) return 0;
        if (pw.length >= 8) score++;
        if (pw.length >= 12) score++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
        if (/\d/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return Math.min(4, score);
    }

    function resetMeter() {
        meterBars.forEach(function (bar) { bar.className = 'pwd-meter-bar'; });
        if (meterLabel) meterLabel.textContent = 'At least 8 characters';
    }

    function renderMeter() {
        if (!newInput) return;

        var pw = newInput.value || '';
        var score = scorePassword(pw);

        meterBars.forEach(function (bar, i) {
            bar.className = 'pwd-meter-bar';
            if (i < score) {
                if (score <= 1) bar.classList.add('is-filled-weak');
                else if (score <= 3) bar.classList.add('is-filled-medium');
                else bar.classList.add('is-filled-strong');
            }
        });

        if (!meterLabel) return;

        if (!pw) {
            meterLabel.textContent = 'At least 8 characters';
        } else if (score <= 1) {
            meterLabel.textContent = 'Weak — add length, numbers and symbols';
        } else if (score <= 3) {
            meterLabel.textContent = 'Fair — consider making it longer';
        } else {
            meterLabel.textContent = 'Strong password';
        }
    }

    function renderMatch() {
        if (!matchLabel || !confirmInput) return;

        if (!confirmInput.value) {
            matchLabel.textContent = '';
            confirmInput.style.borderColor = '';
            return;
        }

        if (confirmInput.value === newInput.value) {
            matchLabel.textContent = 'Passwords match';
            matchLabel.style.color = 'var(--success)';
            confirmInput.style.borderColor = '';
        } else {
            matchLabel.textContent = 'Passwords do not match';
            matchLabel.style.color = 'var(--danger)';
            confirmInput.style.borderColor = 'var(--danger)';
        }
    }

    newInput.addEventListener('input', function () {
        renderMeter();
        if (confirmInput.value !== '') renderMatch();
    });
    confirmInput.addEventListener('input', renderMatch);

    /* ---------- RESET PASSWORD ---------- */
    resetForm.addEventListener('submit', function (event) {
        event.preventDefault();

        setError(resetError, '');

        if (resetToken === '') {
            setError(resetError, 'The verification code has expired. Request a new code.');
            return;
        }

        if (newInput.value.length < 8) {
            setError(resetError, 'Password must be at least 8 characters.');
            newInput.focus();
            return;
        }

        if (newInput.value !== confirmInput.value) {
            setError(resetError, 'Passwords do not match.');
            confirmInput.focus();
            return;
        }

        resetBtn.disabled = true;

        apiPost('/reset', {
            token: resetToken,
            password: newInput.value,
            password_confirmation: confirmInput.value
        })
            .then(function (data) {
                if (!data.ok) {
                    throw new Error(data.error || 'Unable to reset your password. Please try again.');
                }

                resetToken = '';
                showStep('done');

                if (window.showToast) {
                    window.showToast('Password updated successfully.', 'bi-shield-check');
                }
            })
            .catch(function (error) {
                setError(resetError, error.message || 'Unable to reset your password. Please try again.');
            })
            .finally(function () {
                resetBtn.disabled = false;
            });
    });

    backBtn.addEventListener('click', function () {
        setError(resetError, '');
        showStep('code');
        window.setTimeout(function () { otpInputs[0].focus(); }, 120);
    });

    /* ---------- OPEN / CLOSE ---------- */
    modalEl.addEventListener('show.bs.modal', function () {
        resetModalState();
        showStep('code');

        // Auto-send a fresh code as soon as the modal opens.
        sendCode(false);
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        otpInputs[0].focus();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        stopResendTimer();
        resetModalState();
    });
})();
</script>

<style>
    .biometric-row { display: flex; align-items: center; gap: 14px; }
    .biometric-icon {
        width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--surface-muted, #eef1f6); color: var(--accent, #2f6fed); font-size: 18px;
    }
    .biometric-info { flex: 1; min-width: 0; }
    .biometric-info .toggle-label { font-weight: 600; }
    .biometric-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
    @media (max-width: 767.98px) {
        .biometric-row { flex-wrap: wrap; }
        .biometric-actions { width: 100%; justify-content: flex-start; }
    }
</style>
