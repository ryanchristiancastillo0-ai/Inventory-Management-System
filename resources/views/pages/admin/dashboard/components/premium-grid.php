<!-- ==========================================================
     COMPONENT: Premium grid (line chart + quick actions)
     ========================================================== -->
<section class="dash-grid" aria-label="Overview charts and actions">

    <div class="chart-card">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Registrations</h3>
                <p class="chart-subtitle">New user sign-ups over the last <?= count($chartData) ?> day<?= count($chartData) === 1 ? '' : 's' ?></p>
            </div>
            <div class="chart-total">
                <span class="chart-total-value"><?= number_format($chartTotal) ?></span>
                <span class="chart-total-label">total registrations</span>
            </div>
        </div>

        <?php if (empty($chartData)): ?>
            <div class="chart-empty">No registration data available for this period.</div>
        <?php else:
            /* Line chart geometry — x/y in % of the plot area (SVG viewBox is 0–100) */
            $n   = count($chartData);
            $pts = [];
            foreach ($chartData as $i => $d) {
                $x = $n > 1 ? ($i / ($n - 1)) * 100 : 50;
                $y = $chartMax > 0 ? 100 - (($d['value'] / $chartMax) * 100) : 100;
                $pts[] = [
                    'x' => number_format($x, 3, '.', ''),
                    'y' => number_format($y, 3, '.', ''),
                ];
            }
            $linePts = [];
            foreach ($pts as $p) { $linePts[] = $p['x'] . ',' . $p['y']; }
            $linePts = implode(' ', $linePts);
            $areaPts = $pts[0]['x'] . ',100 ' . $linePts . ' ' . $pts[$n - 1]['x'] . ',100';
        ?>
            <div class="chart-body">
                <div class="chart-plot">

                    <svg class="chart-svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                        <defs>
                            <linearGradient id="regAreaFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%"   style="stop-color:var(--info);stop-opacity:0.22"></stop>
                                <stop offset="100%" style="stop-color:var(--info);stop-opacity:0"></stop>
                            </linearGradient>
                        </defs>
                        <line class="chart-grid" x1="0" x2="100" y1="0"   y2="0"></line>
                        <line class="chart-grid" x1="0" x2="100" y1="25"  y2="25"></line>
                        <line class="chart-grid" x1="0" x2="100" y1="50"  y2="50"></line>
                        <line class="chart-grid" x1="0" x2="100" y1="75"  y2="75"></line>
                        <line class="chart-grid" x1="0" x2="100" y1="100" y2="100"></line>
                        <polygon  class="chart-area" points="<?= $e($areaPts) ?>" fill="url(#regAreaFill)"></polygon>
                        <polyline class="chart-line" points="<?= $e($linePts) ?>"></polyline>
                    </svg>

                    <?php foreach ($chartData as $i => $d):
                        $label     = $fmtChartLabel((string) $d['label']);
                        $val       = (int) round($d['value']);
                        $tipText   = $label . ' · ' . number_format($val) . ' registration' . ($val === 1 ? '' : 's');
                        $showLabel = ($i % 2 === 0) || ($i === $n - 1);
                        $bottomPct = number_format(100 - (float) $pts[$i]['y'], 3, '.', '');
                    ?>
                        <span class="chart-point"
                              style="left:<?= $pts[$i]['x'] ?>%;bottom:<?= $bottomPct ?>%"
                              tabindex="0"
                              role="img"
                              aria-label="<?= $e($tipText) ?>">
                            <span class="chart-point__tip"><?= $e($tipText) ?></span>
                        </span>
                        <?php if ($showLabel): ?>
                            <span class="chart-xlabel" style="left:<?= $pts[$i]['x'] ?>%"><?= $e($label) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>

                </div>
            </div>
        <?php endif; ?>

        <div class="chart-footer">
            <span>
                <?php if (!empty($chartData)):
                    $first = $fmtChartLabel((string) $chartData[0]['label']);
                    $last  = $fmtChartLabel((string) $chartData[count($chartData) - 1]['label']);
                ?>
                    <?= $e($first) ?> – <?= $e($last) ?>
                <?php else: ?>
                    Awaiting data
                <?php endif; ?>
            </span>
            <span>Hover a point for details</span>
        </div>
    </div>

    <div class="qa-card">
        <div class="qa-head">
            <h3 class="qa-title">Quick actions</h3>
            <p class="qa-subtitle">Jump to the most-used admin tools</p>
        </div>
        <div class="qa-list">
            <a class="qa-item" href="/admin/users">
                <span class="qa-icon is-primary"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
                <div class="qa-body">
                    <p class="qa-label">Add new user</p>
                    <p class="qa-desc">Invite a staff member or customer</p>
                </div>
                <i class="bi bi-chevron-right qa-chev" aria-hidden="true"></i>
            </a>
            <a class="qa-item" href="/admin/reports">
                <span class="qa-icon is-success"><i class="bi bi-journal" aria-hidden="true"></i></span>
                <div class="qa-body">
                    <p class="qa-label">Review reports</p>
                    <p class="qa-desc">Handle user feedback and issues</p>
                </div>
                <i class="bi bi-chevron-right qa-chev" aria-hidden="true"></i>
            </a>
            <a class="qa-item" href="/admin/activity-logs">
                <span class="qa-icon is-warning"><i class="bi bi-activity" aria-hidden="true"></i></span>
                <div class="qa-body">
                    <p class="qa-label">Activity logs</p>
                    <p class="qa-desc">Audit recent system events</p>
                </div>
                <i class="bi bi-chevron-right qa-chev" aria-hidden="true"></i>
            </a>
            <a class="qa-item" href="/settings">
                <span class="qa-icon is-danger"><i class="bi bi-gear" aria-hidden="true"></i></span>
                <div class="qa-body">
                    <p class="qa-label">System settings</p>
                    <p class="qa-desc">Tune preferences and account</p>
                </div>
                <i class="bi bi-chevron-right qa-chev" aria-hidden="true"></i>
            </a>
        </div>
    </div>

</section>
