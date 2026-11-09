    <!-- ==========================================================
         Reports table
         ========================================================== -->
    <section class="section-gap">
        <div class="panel">

            <?php if (empty($reports)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-flag" aria-hidden="true"></i></div>
                    <h3 class="empty-title">No reports yet</h3>
                    <p class="empty-text">Reports submitted by users will appear here for review and response.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table activity-table align-middle">
                        <caption>Reports: subject, reporter, priority, status, submitted date and actions.</caption>
                        <thead>
                            <tr>
                                <th scope="col">Subject</th>
                                <th scope="col">Reporter</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Status</th>
                                <th scope="col">Submitted</th>
                                <th scope="col" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody id="reportsBody">
                            <?php foreach ($reports as $r):
                                $rid        = (int) ($r['id'] ?? 0);
                                $subject    = $pickStr($r, ['subject', 'title', 'reason', 'topic'], 'Report #' . $rid);
                                $desc       = $pickStr($r, ['description', 'content', 'message', 'details', 'body'], '');
                                $type       = $pickStr($r, ['type', 'category', 'report_type', 'kind'], '');
                                $statusRaw  = $pickStr($r, ['status', 'state'], 'PENDING');
                                $priorityRaw = $pickStr($r, ['priority', 'severity', 'urgency'], 'MEDIUM');
                                $reporter   = $pickStr($r, ['username', 'user_name', 'reporter_name', 'reporter', 'name'], 'User #' . $pickStr($r, ['user_id', 'reporter_id'], '?'));
                                $reporterId = $pickStr($r, ['user_id', 'reporter_id', 'actor_id'], '');
                                $email      = $pickStr($r, ['email', 'user_email', 'reporter_email'], '');
                                $when       = $pickStr($r, ['created_at', 'created_on', 'submitted_at', 'date', 'when'], '');
                                $response   = $pickStr($r, ['admin_response', 'response', 'reply', 'admin_reply'], '');

                                $sMeta  = $statusMeta($statusRaw);
                                $pMeta  = $priorityMeta($priorityRaw);
                                $initial = strtoupper(substr($reporter !== '' ? $reporter : '?', 0, 1));
                                $shortDesc = $desc !== '' ? mb_substr($desc, 0, 90) . (mb_strlen($desc) > 90 ? '…' : '') : '';
                            ?>
                                <tr class="report-row"
                                    data-report-id="<?= $rid ?>"
                                    data-status="<?= $e($sMeta['key']) ?>"
                                    data-subject="<?= $e($subject) ?>"
                                    data-reporter="<?= $e($reporter) ?>"
                                    data-search="<?= $e(strtolower($subject . ' ' . $reporter . ' ' . $email . ' ' . $type . ' ' . $desc)) ?>">
                                    <td class="subject-cell">
                                        <p class="subject-title"><?= $e($subject) ?></p>
                                        <?php if ($shortDesc !== ''): ?>
                                            <p class="subject-desc" title="<?= $e($desc) ?>">
                                                <?= $e($shortDesc) ?>
                                            </p>
                                        <?php elseif ($type !== ''): ?>
                                            <p class="subject-desc"><?= $e(ucfirst(str_replace('_', ' ', $type))) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="reporter-cell">
                                            <span class="reporter-avatar" aria-hidden="true"><?= $e($initial) ?></span>
                                            <div class="reporter-meta">
                                                <p class="reporter-name"><?= $e($reporter) ?></p>
                                                <?php if ($email !== ''): ?>
                                                    <p class="reporter-sub"><?= $e($email) ?></p>
                                                <?php elseif ($reporterId !== ''): ?>
                                                    <p class="reporter-sub">ID #<?= $e($reporterId) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="priority-badge <?= $e($pMeta['cls']) ?>"><?= $e($pMeta['label']) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $e($sMeta['cls']) ?>">
                                            <i class="bi <?= $e($sMeta['icon']) ?>" aria-hidden="true"></i>
                                            <?= $e($sMeta['label']) ?>
                                        </span>
                                    </td>
                                    <td class="cell-when">
                                        <?= $e($fmtWhen($when)) ?>
                                        <?php if ($when !== ''): ?>
                                            <div class="rel"><?= $e($fmtRelative($when)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-actions">
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
                                                            data-action="manage"
                                                            data-report-id="<?= $rid ?>"
                                                            data-report-subject="<?= $e($subject) ?>"
                                                            data-report-reporter="<?= $e($reporter) ?>"
                                                            data-report-email="<?= $e($email) ?>"
                                                            data-report-when="<?= $e($fmtWhen($when)) ?>"
                                                            data-report-type="<?= $e($type) ?>"
                                                            data-report-priority="<?= $e($priorityRaw) ?>"
                                                            data-report-status="<?= $e(strtoupper($statusRaw)) ?>"
                                                            data-report-desc="<?= $e($desc) ?>"
                                                            data-report-response="<?= $e($response) ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#manageReportModal">
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>Manage report
                                                    </button>
                                                </li>
                                                <?php if ($email !== ''): ?>
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                                data-action="copy-email"
                                                                data-email="<?= $e($email) ?>">
                                                            <i class="bi bi-envelope" aria-hidden="true"></i>Copy reporter email
                                                        </button>
                                                    </li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="/admin/users?id=<?= (int) $reporterId ?>" target="_blank" rel="noopener">
                                                        <i class="bi bi-person" aria-hidden="true"></i>Open reporter profile
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="no-results" id="noResults" role="status">No reports match your filters.</div>

                <div class="table-footer">
                    <span id="showingLabel">
                        Showing 1–<?= number_format(count($reports)) ?> of <?= number_format(count($reports)) ?>
                    </span>
                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
                </div>
            <?php endif; ?>

        </div>
    </section>
