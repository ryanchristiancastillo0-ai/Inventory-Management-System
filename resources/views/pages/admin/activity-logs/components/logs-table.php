    <!-- ==========================================================
         Logs table
         ========================================================== -->
    <section class="section-gap">
        <div class="panel">

            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></div>
                    <h3 class="empty-title">No activity logs yet</h3>
                    <p class="empty-text">
                        <?= $currentPage > 1
                            ? 'This page has no records. Try going back to page 1.'
                            : 'Activity will appear here as admins and users take action across the system.' ?>
                    </p>
                    <?php if ($currentPage > 1): ?>
                        <a href="/admin/activity-logs" class="btn-secondary">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i>Back to page 1
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table activity-table align-middle">
                        <caption>Activity logs: actor, action, target, IP address, details and timestamp.</caption>
                        <thead>
                            <tr>
                                <th scope="col">When</th>
                                <th scope="col">Actor</th>
                                <th scope="col">Action</th>
                                <th scope="col">Target</th>
                                <th scope="col">IP</th>
                                <th scope="col">Details</th>
                                <th scope="col" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody id="logsBody">
                            <?php foreach ($logs as $log):
                                $lid     = (int) ($log['id'] ?? 0);
                                $actorId = $pickStr($log, ['user_id', 'actor_id', 'admin_id'], '');
                                $actor   = $pickStr($log, ['username', 'user_name', 'actor_name', 'admin_name', 'name'], 'System');
                                $email   = $pickStr($log, ['email', 'user_email', 'actor_email', 'email_address'], '');
                                $raw     = $pickStr($log, ['action', 'action_type', 'event', 'type'], '');
                                $entity  = $pickStr($log, ['entity_type', 'target_type', 'model', 'resource'], '');
                                $entityId= $pickStr($log, ['entity_id', 'target_id', 'record_id', 'object_id'], '');
                                $details = $pickStr($log, ['description', 'details', 'message', 'notes', 'summary'], '');
                                $ip      = $pickStr($log, ['ip_address', 'ip', 'ip_addr', 'remote_addr'], '');
                                $ua      = $pickStr($log, ['user_agent', 'agent'], '');
                                $when    = $pickStr($log, ['created_at', 'created_on', 'timestamp', 'date', 'when'], '');

                                $meta    = $actionBadge($raw);
                                $group   = $categorize($raw);
                                $initial = strtoupper(substr($actor !== '' ? $actor : '?', 0, 1));

                                $targetText = '—';
                                if ($entity !== '' || $entityId !== '') {
                                    $targetText = ucfirst(str_replace('_', ' ', $entity !== '' ? $entity : 'record'))
                                                . ($entityId !== '' ? ' #' . $entityId : '');
                                }

                                $searchBlob = strtolower($actor . ' ' . $email . ' ' . $raw . ' ' . $targetText . ' ' . $details . ' ' . $ip);
                            ?>
                                <tr class="log-row"
                                    data-log-id="<?= $lid ?>"
                                    data-action-group="<?= $e($group) ?>"
                                    data-action-raw="<?= $e(strtolower($raw)) ?>"
                                    data-actor="<?= $e(strtolower($actor)) ?>"
                                    data-search="<?= $e($searchBlob) ?>">
                                    <td class="cell-when">
                                        <?= $e($fmtWhen($when)) ?>
                                        <?php if ($when !== ''): ?>
                                            <div class="rel"><?= $e($fmtRelative($when)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actor-cell">
                                            <span class="actor-avatar" aria-hidden="true"><?= $e($initial) ?></span>
                                            <div class="actor-meta">
                                                <p class="actor-name"><?= $e($actor) ?></p>
                                                <?php if ($email !== ''): ?>
                                                    <p class="actor-sub"><?= $e($email) ?></p>
                                                <?php elseif ($actorId !== ''): ?>
                                                    <p class="actor-sub">ID #<?= $e($actorId) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="action-badge <?= $e($meta['cls']) ?>">
                                            <i class="bi <?= $e($meta['icon']) ?>" aria-hidden="true"></i>
                                            <?= $e($raw !== '' ? $raw : '—') ?>
                                        </span>
                                    </td>
                                    <td class="target-cell">
                                        <?php if ($entity !== '' || $entityId !== ''): ?>
                                            <span class="entity"><?= $e($entity !== '' ? $entity : 'record') ?></span>
                                            <?php if ($entityId !== ''): ?>
                                                <span class="entity-id">#<?= $e($entityId) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-ip"><?= $ip !== '' ? $e($ip) : '—' ?></td>
                                    <td class="cell-details" title="<?= $e($details) ?>">
                                        <?= $details !== '' ? $e($details) : '—' ?>
                                    </td>
                                    <td class="cell-actions">
                                        <!-- Row actions — Bootstrap dropdown -->
                                        <div class="dropdown">
                                            <button type="button" class="icon-btn row-actions-btn"
                                                    data-bs-toggle="dropdown"
                                                    data-bs-boundary="viewport"
                                                    aria-expanded="false"
                                                    aria-label="Row actions">
                                                <i class="bi bi-three-dots" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                            data-action="view-detail"
                                                            data-log-id="<?= $lid ?>"
                                                            data-log-action="<?= $e($raw) ?>"
                                                            data-log-actor="<?= $e($actor) ?>"
                                                            data-log-email="<?= $e($email) ?>"
                                                            data-log-actor-id="<?= $e($actorId) ?>"
                                                            data-log-target="<?= $e($targetText) ?>"
                                                            data-log-ip="<?= $e($ip) ?>"
                                                            data-log-ua="<?= $e($ua) ?>"
                                                            data-log-when="<?= $e($fmtWhen($when)) ?>"
                                                            data-log-relative="<?= $e($fmtRelative($when)) ?>"
                                                            data-log-details="<?= $e($details) ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#logDetailModal">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>View details
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                            data-action="copy-id"
                                                            data-log-id="<?= $lid ?>">
                                                        <i class="bi bi-clipboard" aria-hidden="true"></i>Copy log ID
                                                    </button>
                                                </li>
                                                <?php if ($email !== ''): ?>
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                                data-action="copy-email"
                                                                data-email="<?= $e($email) ?>">
                                                            <i class="bi bi-envelope" aria-hidden="true"></i>Copy actor email
                                                        </button>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="no-results" id="noResults" role="status">No logs match your filters on this page.</div>

                <div class="table-footer">
                    <span id="showingLabel">
                        Page <?= $currentPage ?> of <?= $totalPages ?>
                        · <?= number_format($totalLogs) ?> total <?= $totalLogs === 1 ? 'entry' : 'entries' ?>
                    </span>
                    <nav class="pagination" id="pagination" aria-label="Pagination">
                        <?php
                        $prevDisabled = $currentPage <= 1;
                        $nextDisabled = $currentPage >= $totalPages;
                        ?>
                        <a href="?page=<?= max(1, $currentPage - 1) ?>"
                           class="<?= $prevDisabled ? 'is-disabled' : '' ?>"
                           aria-label="Previous page"
                           <?= $prevDisabled ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>

                        <?php foreach ($pageRange as $p): ?>
                            <?php if ($p === '…'): ?>
                                <span aria-hidden="true">…</span>
                            <?php else: ?>
                                <a href="?page=<?= (int) $p ?>"
                                   class="<?= (int) $p === $currentPage ? 'active' : '' ?>"
                                   <?= (int) $p === $currentPage ? 'aria-current="page"' : '' ?>>
                                    <?= (int) $p ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <a href="?page=<?= min($totalPages, $currentPage + 1) ?>"
                           class="<?= $nextDisabled ? 'is-disabled' : '' ?>"
                           aria-label="Next page"
                           <?= $nextDisabled ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </nav>
                </div>
            <?php endif; ?>

        </div>
    </section>
