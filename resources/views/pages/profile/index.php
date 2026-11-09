<?php
/** @var array $profile */
/** @var array $old */
/** @var array $errors */
/** @var bool $editing */
/** @var array|null $security */
/** @var array|null $activity */
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$initial = mb_strtoupper(mb_substr($profile['username'], 0, 1));
$roleLabel = $profile['role'] === 'ADMIN' ? 'Administrator' : 'User';
$joined = !empty($profile['created_at']) ? strtotime($profile['created_at']) : false;
$joinedLabel = $joined !== false ? date('F j, Y', $joined) : 'Not available';
$securityItems = [
    ['icon' => 'bi-key', 'title' => 'Password', 'description' => 'Manage the password you use to sign in.',
     'status' => $security === null ? 'Unavailable' : (!empty($security['has_password']) ? 'Configured' : 'Not configured'),
     'href' => !empty($security['has_password']) ? '/settings#pwd-form' : null, 'action' => 'Change password'],
    ['icon' => 'bi-phone', 'title' => 'Mobile number', 'description' => 'Sign in with a code sent to your phone via SMS.',
     'status' => $security === null ? 'Unavailable'
         : ((!empty($security['phone']) && !empty($security['phone_verified'])) ? 'Verified' : 'Not set up'),
     'href' => '/profile#phoneCard', 'action' => 'Manage phone number'],
    ['icon' => 'bi-person-bounding-box', 'title' => 'Face recognition', 'description' => 'Manage your enrolled face scans for biometric sign-in.',
     'status' => $security === null ? 'Unavailable' : ((int) $security['face_count'] > 0 ? 'Enrolled' : 'Not enrolled'),
     'href' => '/settings#biometricCard', 'action' => 'Manage face login']
];
?>
<link rel="stylesheet" href="<?= asset('/assets/css/profile.css?v=2026110902') ?>">

<main class="dashboard-content profile-page" data-editing="<?= $editing ? 'true' : 'false' ?>">
    <header class="profile-page-header">
        <div>
            <h1 class="page-title">Profile</h1>
            <p class="page-subtitle">View and manage your account information.</p>
        </div>
        <span class="profile-page-caption"><i class="bi bi-person-circle" aria-hidden="true"></i>My account</span>
    </header>

    <?php if (!empty($flash['message'])): ?>
        <div class="profile-feedback <?= $flash['type'] === 'success' ? 'is-success' : 'is-error' ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle' ?>" aria-hidden="true"></i>
            <span><?= $e($flash['message']) ?></span>
        </div>
    <?php endif; ?>

    <div class="row profile-main-grid">
        <div class="col-lg-5">
            <section class="profile-card profile-identity" aria-labelledby="profile-name">
                <h2 id="profile-name"><?= $e($profile['username']) ?></h2>
                <span class="profile-role-badge"><i class="bi <?= $profile['role'] === 'ADMIN' ? 'bi-shield-check' : 'bi-person' ?>" aria-hidden="true"></i><?= $e($roleLabel) ?></span>
                <div class="profile-avatar-frame">
                    <div class="profile-avatar" role="img" aria-label="<?= $e('Profile initials for ' . $profile['username']) ?>"><?= $e($initial) ?></div>
                </div>
                <p class="profile-identity-email"><?= $e($profile['email']) ?></p>
                <p class="profile-joined"><i class="bi bi-calendar3" aria-hidden="true"></i>Member since <?= $e($joinedLabel) ?></p>
            </section>
        </div>

        <div class="col-lg-7">
            <section class="profile-card profile-details" id="profile-details" aria-labelledby="profile-details-title">
                <header class="profile-section-header">
                    <div>
                        <h2 id="profile-details-title">Personal information</h2>
                        <p>Your personal and account details.</p>
                    </div>
                    <a href="/profile?edit=1#profile-details" class="btn-secondary" id="profile-edit" aria-controls="profile-form" aria-expanded="<?= $editing ? 'true' : 'false' ?>" <?= $editing ? 'hidden' : '' ?>>
                        <i class="bi bi-pencil" aria-hidden="true"></i>Edit profile
                    </a>
                    <span class="profile-editing-label" data-edit-only <?= !$editing ? 'hidden' : '' ?>>Editing details</span>
                </header>

                <form method="post" action="/profile" id="profile-form">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                    <div class="profile-info-grid">
                        <?php foreach (['username' => 'Username', 'email' => 'Email address'] as $field => $label): ?>
                            <div class="profile-info-item">
                                <span class="profile-info-label" data-view-only <?= $editing ? 'hidden' : '' ?>><?= $label ?></span>
                                <div class="profile-info-value" data-view-only <?= $editing ? 'hidden' : '' ?>><?= $e($profile[$field]) ?></div>
                                <div data-edit-only <?= !$editing ? 'hidden' : '' ?>>
                                    <label class="profile-info-label" for="profile-<?= $field ?>"><?= $label ?></label>
                                    <input class="form-control-app" type="<?= $field === 'email' ? 'email' : 'text' ?>"
                                           id="profile-<?= $field ?>" name="<?= $field ?>" value="<?= $e($old[$field] ?? $profile[$field]) ?>"
                                           data-saved-value="<?= $e($profile[$field]) ?>" required maxlength="<?= $field === 'email' ? 150 : 50 ?>"
                                           autocomplete="<?= $field ?>" aria-invalid="<?= isset($errors[$field]) ? 'true' : 'false' ?>"
                                           aria-describedby="profile-<?= $field ?>-hint profile-<?= $field ?>-error" <?= !$editing ? 'disabled' : '' ?>>
                                    <p class="profile-field-hint" id="profile-<?= $field ?>-hint"><?= $field === 'email' ? 'Used to sign in to your account.' : 'Your name throughout the workspace.' ?></p>
                                    <p class="profile-field-error" id="profile-<?= $field ?>-error" <?= !isset($errors[$field]) ? 'hidden' : '' ?>><?= $e($errors[$field] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="profile-info-item"><span class="profile-info-label">Account role</span><div class="profile-info-value"><?= $e($roleLabel) ?></div></div>
                        <div class="profile-info-item"><span class="profile-info-label">Member since</span><div class="profile-info-value"><?= $e($joinedLabel) ?></div></div>
                        <div class="profile-info-item"><span class="profile-info-label">Account ID</span><div class="profile-info-value">#<?= (int) $profile['id'] ?></div></div>
                    </div>
                    <footer class="profile-form-footer">
                        <p class="profile-details-note" data-view-only <?= $editing ? 'hidden' : '' ?>><i class="bi bi-lock" aria-hidden="true"></i>Your account details are visible only to you and authorized administrators.</p>
                        <div class="profile-edit-actions" data-edit-only <?= !$editing ? 'hidden' : '' ?>>
                            <span class="profile-save-status" role="status" aria-live="polite"></span>
                            <a href="/profile#profile-details" class="btn-secondary" id="profile-cancel">Cancel</a>
                            <button type="submit" class="btn-primary-app"><i class="bi bi-check2" aria-hidden="true"></i>Save profile</button>
                        </div>
                    </footer>
                </form>
            </section>
        </div>
    </div>

    <section class="profile-card profile-phone <?php echo !empty($security['phone']) && !empty($security['phone_verified']) ? 'is-verified' : ''; ?>" id="phoneCard" aria-labelledby="phone-card-title">
        <header class="phone-head">
            <span class="phone-head__icon" aria-hidden="true"><i class="bi bi-phone"></i></span>
            <div class="phone-head__copy">
                <h2 id="phone-card-title">Mobile number</h2>
                <p>Sign in with a 6-digit code sent by SMS.</p>
            </div>
            <span class="phone-badge" id="phoneBadge">
                <?php if ($security !== null && !empty($security['phone']) && !empty($security['phone_verified'])): ?>
                    <span class="phone-badge__dot"></span> Verified
                <?php elseif ($security !== null && !empty($security['phone'])): ?>
                    <span class="phone-badge__dot"></span> Pending
                <?php else: ?>
                    <span class="phone-badge__dot"></span> Not set up
                <?php endif; ?>
            </span>
        </header>

        <div class="phone-body" id="phoneEntryStep">
            <?php if ($security !== null && !empty($security['phone'])): ?>
                <div class="phone-current">
                    <div>
                        <span class="phone-current__label">Current number</span>
                        <span class="phone-current__value"><?= $e($security['phone']) ?></span>
                    </div>
                    <span class="phone-current__state <?= empty($security['phone_verified']) ? 'is-pending' : 'is-ok' ?>">
                        <?= empty($security['phone_verified']) ? 'Awaiting verification' : 'Verified' ?>
                    </span>
                </div>
            <?php endif; ?>

            <form id="phoneRequestForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                <label class="phone-label" for="profilePhoneInput">
                    <?= (!empty($security['phone'])) ? 'Add or replace number' : 'Mobile number' ?>
                </label>
                <div class="phone-field">
                    <span class="phone-field__prefix">+63</span>
                    <input class="phone-field__input" type="tel" id="profilePhoneInput" name="phone"
                           inputmode="numeric" maxlength="10" placeholder="912 345 6789" required
                           aria-describedby="phoneRequestNote">
                    <button type="submit" class="phone-btn phone-btn--primary" id="phoneSendCodeBtn">
                        <span class="phone-btn__label">Send code</span>
                        <span class="phone-btn__spinner" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="phone-note" id="phoneRequestNote">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    We'll text a one-time code to confirm this number. It must be verified before SMS sign-in works.
                </p>
                <p class="phone-note">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <span>SMS verification supports Globe, TM and DITO networks. Smart, TNT and Sun are not supported by our current SMS provider.</span>
                </p>
                <p class="phone-error" id="phoneRequestError" hidden></p>
            </form>
        </div>

        <div class="phone-body" id="phoneVerifyStep" hidden>
            <div class="phone-verify-head">
                <span class="phone-verify-head__icon" aria-hidden="true"><i class="bi bi-chat-dots"></i></span>
                <div>
                    <h3>Enter verification code</h3>
                    <p>We sent a 6-digit code to <strong id="phoneVerifyMasked"></strong></p>
                </div>
            </div>

            <form id="phoneVerifyForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                <div class="phone-otp" id="profilePhoneOtpGroup" role="group" aria-label="6-digit verification code">
                    <input class="phone-otp__box" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" aria-label="Digit 1">
                    <input class="phone-otp__box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 2">
                    <input class="phone-otp__box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 3">
                    <input class="phone-otp__box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 4">
                    <input class="phone-otp__box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 5">
                    <input class="phone-otp__box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 6">
                </div>
                <p class="phone-error" id="phoneVerifyError" hidden></p>

                <div class="phone-verify-actions">
                    <button type="submit" class="phone-btn phone-btn--primary" id="phoneVerifySubmitBtn">
                        <span class="phone-btn__label">Verify &amp; save</span>
                        <span class="phone-btn__spinner" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="phone-btn phone-btn--ghost" id="phoneVerifyCancelBtn">Cancel</button>
                </div>

                <div class="phone-verify-foot">
                    <span class="phone-timer" id="phoneTimerText">Code expires in 5:00</span>
                    <button type="button" class="phone-link" id="phoneResendBtn" disabled>
                        Resend code <span id="phoneResendCountdown" class="phone-link__meta"></span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="profile-card profile-security" aria-labelledby="profile-security-title">
        <header class="profile-section-header">
            <div><h2 id="profile-security-title">Account security</h2><p>Manage how you sign in and protect your account.</p></div>
            <span class="profile-section-symbol" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
        </header>
        <div class="profile-security-list">
            <?php foreach ($securityItems as $item): ?>
                <div class="profile-security-row">
                    <span class="profile-security-icon" aria-hidden="true"><i class="bi <?= $item['icon'] ?>"></i></span>
                    <div class="profile-security-copy"><h3><?= $item['title'] ?></h3><p><?= $item['description'] ?></p></div>
                    <span class="profile-security-status"><?= $e($item['status']) ?></span>
                    <?php if ($item['href'] !== null): ?>
                        <a href="<?= $e($item['href']) ?>" class="btn-secondary"><?= $item['action'] ?><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($activity !== null): ?>
        <section class="profile-card profile-activity" aria-labelledby="profile-activity-title">
            <header class="profile-section-header">
                <div><h2 id="profile-activity-title">Recent account activity</h2><p>Your latest recorded actions in this workspace.</p></div>
                <span class="profile-section-symbol" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
            </header>
            <?php if ($activity === []): ?>
                <div class="profile-empty"><span class="profile-security-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span><div><h3>No activity recorded yet</h3><p>Recorded actions associated with your account will appear here.</p></div></div>
            <?php else: ?>
                <ul class="profile-activity-list">
                    <?php foreach ($activity as $entry):
                        $activityDate = strtotime((string) ($entry['created_at'] ?? ''));
                    ?>
                        <li>
                            <span class="profile-activity-dot" aria-hidden="true"></span>
                            <div class="profile-activity-copy"><h3><?= $e(ucfirst(strtolower(str_replace('_', ' ', $entry['action'])))) ?></h3><p><?= $e($entry['description']) ?></p></div>
                            <span class="profile-activity-date"><?= $activityDate !== false ? $e(date('M j, Y · g:i A', $activityDate)) : 'Date unavailable' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<script src="<?= asset('/assets/js/profile.js?v=2026110707') ?>" defer></script>
