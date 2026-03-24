<?php
/**
 * LearnDash Group Leader Dashboard – Visual shortcodes
 *
 * Shortcodes:
 * - [ld_group_leader_pie_chart]  – Pie chart: Ikke startet / Pågår / Fullført
 * - [ld_group_leader_summary]   – Summary box (students + courses)
 * - [ld_group_leader_table]     – Table with filters, search, progress bars, pagination
 *   Attr: per_page (default 20)
 *
 * Performance optimisations vs. original:
 *  1. Request-level static cache → data function runs once per leader per request,
 *     even when all three shortcodes appear on the same page.
 *  2. WordPress transient cache (default 5 minutes, filterable via
 *     `ld_gl_report_cache_ttl`) → avoids heavy queries on every page load.
 *  3. Batch user fetch per group (get_users with include) → replaces N × get_user_by().
 *  4. Deduped course-title lookups via a static title cache.
 *  5. Single primary activity-date strategy using direct wpdb query, with
 *     LearnDash API fallbacks per course only when needed.
 *  6. Transient is cleared automatically when LearnDash records a course activity.
 */

declare(strict_types=1);

if ( ! defined('ABSPATH') ) {
    return;
}

// ─── Cache helpers ─────────────────────────────────────────────────────────

/**
 * Returns a stable transient key for a given leader.
 */
function ld_group_leader_cache_key( int $leader_id ): string {
    return 'ld_gl_report_' . $leader_id;
}

/**
 * Clears the cached report for a leader whenever LearnDash records activity.
 * Hooked into `learndash_activity_course_completed` and `learndash_course_step_completed`.
 *
 * @param int $user_id  The user whose activity triggered the hook.
 */
function ld_group_leader_bust_cache( int $user_id ): void {
    if ( ! function_exists('learndash_get_administrators_group_ids') ) {
        return;
    }
    // Find every group leader who manages this user's groups and bust their cache.
    $group_ids = function_exists('learndash_get_users_group_ids')
        ? (array) learndash_get_users_group_ids($user_id)
        : [];

    foreach ( $group_ids as $group_id ) {
        $leader_ids = function_exists('learndash_get_groups_administrator_ids')
            ? (array) learndash_get_groups_administrator_ids((int) $group_id)
            : [];
        foreach ( $leader_ids as $lid ) {
            delete_transient( ld_group_leader_cache_key((int) $lid) );
        }
    }
}
add_action('learndash_activity_course_completed',  'ld_group_leader_bust_cache');
add_action('learndash_course_step_completed',       'ld_group_leader_bust_cache');

// ─── Data layer ─────────────────────────────────────────────────────────────

/**
 * Fetches and returns report data for a group leader.
 *
 * Results are cached at two levels:
 *  • Static in-memory cache: prevents duplicate work within the same request.
 *  • WordPress transient: prevents expensive DB work across requests. TTL is
 *    filterable via `ld_gl_report_cache_ttl` (default 5 minutes).
 *
 * @return array<int, array{group_id: int, group_name: string, user_id: int, user_name: string,
 *                           user_email: string, course_id: int, course_title: string,
 *                           percent: int, status: string, steps_done: int, steps_total: int,
 *                           course_started_on: string}>
 */
function ld_group_leader_get_report_data( int $leader_id ): array {
    // Level 1: request-level cache – all three shortcodes on one page share this.
    static $request_cache = [];
    if ( isset($request_cache[ $leader_id ]) ) {
        return $request_cache[ $leader_id ];
    }

    if ( ! function_exists('learndash_get_administrators_group_ids') ) {
        return [];
    }

    // Level 2: transient cache – expensive across requests.
    $transient_key = ld_group_leader_cache_key($leader_id);
    $cached        = get_transient($transient_key);
    if ( $cached !== false && is_array($cached) ) {
        $request_cache[ $leader_id ] = $cached;
        return $cached;
    }

    $group_ids  = (array) learndash_get_administrators_group_ids($leader_id);
    $rows       = [];
    $title_cache = []; // Deduped course-title lookups within this request.

    foreach ( $group_ids as $group_id ) {
        $group_id   = (int) $group_id;
        $group_name = html_entity_decode(get_the_title($group_id), ENT_QUOTES, 'UTF-8');

        if ( ! function_exists('learndash_get_groups_user_ids') ) {
            continue;
        }
        $member_ids = array_map('intval', (array) learndash_get_groups_user_ids($group_id));
        if ( empty($member_ids) ) {
            continue;
        }

        // Batch-fetch all users in this group in one query instead of N × get_user_by().
        $users = get_users([
            'include' => $member_ids,
            'fields'  => ['ID', 'display_name', 'user_email'],
            'number'  => count($member_ids),
        ]);

        // Build a lookup map so inner loop is O(1).
        $user_map = [];
        foreach ( $users as $u ) {
            $user_map[(int) $u->ID] = $u;
        }

        foreach ( $member_ids as $uid ) {
            if ( ! isset($user_map[ $uid ]) ) {
                continue;
            }
            $u = $user_map[ $uid ];

            $course_ids = [];
            if ( function_exists('learndash_user_get_enrolled_courses') ) {
                $course_ids = (array) learndash_user_get_enrolled_courses($uid);
            } elseif ( function_exists('ld_get_mycourses') ) {
                $course_ids = (array) ld_get_mycourses($uid);
            }

            if ( empty($course_ids) ) {
                continue;
            }

            // Fetch all course start dates for this user in ONE query instead of
            // three cascading API calls per course.
            $started_map = ld_group_leader_get_started_dates($uid, $course_ids);

            foreach ( $course_ids as $course_id ) {
                $course_id = (int) $course_id;
                if ( $course_id <= 0 ) {
                    continue;
                }

                // Cache course titles – same course appears for many users.
                if ( ! isset($title_cache[ $course_id ]) ) {
                    $title_cache[ $course_id ] = html_entity_decode(
                        get_the_title($course_id),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                }
                $course_title = $title_cache[ $course_id ];

                $percent      = 0;
                $steps_done   = 0;
                $steps_total  = 0;
                $is_completed = false;

                if ( function_exists('learndash_course_progress') ) {
                    $p = learndash_course_progress([
                        'user_id'   => $uid,
                        'course_id' => $course_id,
                        'array'     => true,
                    ]);
                    if ( is_array($p) ) {
                        $percent     = (int) ($p['percentage'] ?? 0);
                        $steps_done  = (int) ($p['completed']  ?? 0);
                        $steps_total = (int) ($p['total']      ?? 0);
                        // Derive completion from progress data first – avoids
                        // extra DB queries in the common case.
                        if ( $percent >= 100 && $steps_total > 0 && $steps_done >= $steps_total ) {
                            $is_completed = true;
                        }
                    }
                }

                // Fallback: only hit learndash_course_completed() when progress
                // data is ambiguous (e.g. 100 % on a course with 0 steps).
                if ( ! $is_completed && $percent >= 100 && function_exists('learndash_course_completed') ) {
                    $is_completed = (bool) learndash_course_completed($uid, $course_id);
                }

                if ( $is_completed ) {
                    $status  = 'Fullført';
                    $percent = 100;
                } else {
                    $status = ($percent <= 0) ? 'Ikke startet' : 'Pågår';
                }

                $started_ts = $started_map[ $course_id ] ?? 0;
                if ( $started_ts > 0 ) {
                    $course_started_on = wp_date('Y-m-d', $started_ts);
                } else {
                    // Only when the direct DB query has no data do we fall
                    // back to the LearnDash reporting APIs for this course.
                    $course_started_on = ld_group_leader_get_course_started_fallback($uid, $course_id, $leader_id);
                }

                $rows[] = [
                    'group_id'          => $group_id,
                    'group_name'        => $group_name,
                    'user_id'           => $uid,
                    'user_name'         => $u->display_name,
                    'user_email'        => $u->user_email,
                    'course_id'         => $course_id,
                    'course_title'      => $course_title,
                    'percent'           => $percent,
                    'status'            => $status,
                    'steps_done'        => $steps_done,
                    'steps_total'       => $steps_total,
                    'course_started_on' => $course_started_on,
                ];
            }
        }
    }

    // Store in both cache levels.
    $ttl = (int) apply_filters('ld_gl_report_cache_ttl', 5 * MINUTE_IN_SECONDS);
    set_transient($transient_key, $rows, $ttl);
    $request_cache[ $leader_id ] = $rows;

    return $rows;
}

/**
 * Returns a map of course_id => earliest activity_started timestamp for a user,
 * fetched in a SINGLE database query instead of one per course.
 *
 * @param  int   $user_id
 * @param  int[] $course_ids
 * @return array<int, int>  course_id => unix timestamp (0 if not found)
 */
function ld_group_leader_get_started_dates( int $user_id, array $course_ids ): array {
    global $wpdb;

    $course_ids = array_filter(array_map('intval', $course_ids));
    if ( empty($course_ids) ) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($course_ids), '%d'));

    // LearnDash stores activity in {prefix}learndash_user_activity.
    // We want the EARLIEST start for each course.
    $table = $wpdb->prefix . 'learndash_user_activity';

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $sql = $wpdb->prepare(
        "SELECT post_id, MIN(activity_started) AS started
           FROM {$table}
          WHERE user_id      = %d
            AND post_id      IN ({$placeholders})
            AND activity_type = 'course'
            AND activity_started > 0
          GROUP BY post_id",
        array_merge([$user_id], $course_ids)
    );

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $results = $wpdb->get_results($sql);

    $map = [];
    if ( is_array($results) ) {
        foreach ( $results as $row ) {
            $map[(int) $row->post_id] = (int) $row->started;
        }
    }
    return $map;
}

/**
 * Fallback chain for course start date when the direct DB query has no data
 * for a given user+course combination. Uses LearnDash APIs in a safe order
 * and stops on first hit.
 */
function ld_group_leader_get_course_started_fallback( int $uid, int $course_id, int $leader_id ): string {
    if ( $uid <= 0 || $course_id <= 0 ) {
        return '';
    }

    // Method 1: learndash_reports_get_activity (LD reporting tables).
    if ( function_exists('learndash_reports_get_activity') ) {
        $activities = learndash_reports_get_activity([
            'user_id'       => $uid,
            'course_id'     => $course_id,
            'activity_type' => 'course',
            'per_page'      => 1,
            'order'         => 'ASC',
        ], $leader_id);
        $act = is_array($activities) && ! empty($activities) ? $activities[0] : null;
        if ( $act && is_object($act) && ! empty($act->activity_started) ) {
            return wp_date('Y-m-d', (int) $act->activity_started);
        }
    }

    // Method 2: helper introduced in newer LD versions.
    if ( function_exists('learndash_activity_course_get_earliest_started') ) {
        $ts = (int) learndash_activity_course_get_earliest_started($uid, $course_id, 0);
        if ( $ts > 0 ) {
            return wp_date('Y-m-d', $ts);
        }
    }

    // Method 3: generic activity query fallback.
    if ( function_exists('learndash_get_user_activity') ) {
        $activity = learndash_get_user_activity([
            'user_id'       => $uid,
            'course_id'     => $course_id,
            'activity_type' => 'course',
        ]);
        $act = is_array($activity) && ! empty($activity)
            ? $activity[0]
            : (is_object($activity) ? $activity : null);
        if ( $act && isset($act->activity_started) && (int) $act->activity_started > 0 ) {
            return wp_date('Y-m-d', (int) $act->activity_started);
        }
    }

    return '';
}

// ─── Permission check ───────────────────────────────────────────────────────

/**
 * Returns true when the current user is allowed to view the group leader reports.
 */
function ld_group_leader_can_view(): bool {
    if ( ! is_user_logged_in() ) {
        return false;
    }
    $user_id = get_current_user_id();
    return function_exists('learndash_is_group_leader_user') && learndash_is_group_leader_user($user_id);
}

// ─── Shortcode: Pie chart ────────────────────────────────────────────────────

add_shortcode('ld_group_leader_pie_chart', function (): string {
    if ( ! ld_group_leader_can_view() ) {
        return '<p>Kun gruppeledere har tilgang til denne rapporten.</p>';
    }

    $rows   = ld_group_leader_get_report_data(get_current_user_id());
    $counts = [
        'Ikke startet' => 0,
        'Pågår'        => 0,
        'Fullført'     => 0,
    ];
    foreach ( $rows as $r ) {
        $counts[ $r['status'] ] = ($counts[ $r['status'] ] ?? 0) + 1;
    }

    $total = array_sum($counts);
    if ( $total === 0 ) {
        return '<div class="ld-gl-pie-wrap"><p>Ingen data å vise.</p></div>';
    }

    $id   = 'ld-gl-pie-' . wp_rand(1000, 9999);
    $data = [
        'labels'   => array_keys($counts),
        'values'   => array_values($counts),
        'colors'   => ['#9ca3af', '#3b82f6', '#65bc7b'],
        'chart_id' => $id,
    ];

    $chart_handle = 'ld-gl-chartjs';
    wp_enqueue_script(
        $chart_handle,
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
        [],
        '4.4.1',
        true
    );

    $chart_script = sprintf(
        "(function(){var d=%s;var el=document.getElementById(d.chart_id);if(el&&typeof Chart!=='undefined'){new Chart(el,{type:'doughnut',data:{labels:d.labels,datasets:[{data:d.values,backgroundColor:d.colors,borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{position:'bottom'}}}});}})();",
        wp_json_encode($data)
    );
    wp_add_inline_script($chart_handle, $chart_script, 'after');

    return sprintf(
        '<div class="ld-gl-pie-wrap" style="max-width:350px;margin:0 auto;"><canvas id="%s" role="img" aria-label="Progresjonsfordeling" width="350" height="350"></canvas></div>',
        esc_attr($id)
    );
});

// ─── Shortcode: Summary box ──────────────────────────────────────────────────

add_shortcode('ld_group_leader_summary', function (): string {
    if ( ! ld_group_leader_can_view() ) {
        return '<p>Kun gruppeledere har tilgang til denne rapporten.</p>';
    }

    $rows          = ld_group_leader_get_report_data(get_current_user_id());
    $student_count = count(array_unique(array_column($rows, 'user_id')));
    $course_count  = count(array_unique(array_column($rows, 'course_id')));

    ob_start();
    ?>
    <div class="ld-gl-summary" style="display:flex;gap:1.5rem;flex-wrap:wrap;margin:1rem 0;">
        <div class="ld-gl-summary-item" style="padding:1rem 1.5rem;background:#0085ba;border-radius:8px;min-width:140px;text-align:center;">
            <div style="font-size:0.8rem;color:#fff;">Antall studenter</div>
            <div style="font-size:1.75rem;font-weight:700;color:#fff;"><?php echo (int) $student_count; ?></div>
        </div>
        <div class="ld-gl-summary-item" style="padding:1rem 1.5rem;background:#0085ba;border-radius:8px;min-width:140px;text-align:center;">
            <div style="font-size:0.8rem;color:#fff;">Kurs</div>
            <div style="font-size:1.75rem;font-weight:700;color:#fff;"><?php echo (int) $course_count; ?></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// ─── Shortcode: Table with filters and search ────────────────────────────────

add_shortcode('ld_group_leader_table', function ( $atts = [] ): string {
    if ( ! ld_group_leader_can_view() ) {
        return '<p>Kun gruppeledere har tilgang til denne rapporten.</p>';
    }

    $atts     = shortcode_atts(['per_page' => 20], $atts, 'ld_group_leader_table');
    $per_page = max(1, (int) $atts['per_page']);

    $rows = ld_group_leader_get_report_data(get_current_user_id());
    $id   = 'ld-gl-table-' . wp_rand(1000, 9999);

    $groups = [];
    foreach ( $rows as $r ) {
        $groups[ $r['group_id'] ] = $r['group_name'];
    }

    ob_start();
    ?>
    <div class="ld-gl-table-wrap" id="<?php echo esc_attr($id); ?>" data-per-page="<?php echo (int) $per_page; ?>">
        <div class="ld-gl-filters" style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;align-items:center;">
            <label>
                <span style="font-size:0.875rem;margin-right:0.25rem;">Gruppe:</span>
                <select class="ld-gl-filter-group" style="padding:0.4rem 0.6rem;border:1px solid #cbd5e1;border-radius:4px;">
                    <option value="">Alle grupper</option>
                    <?php foreach ( $groups as $gid => $gname ) : ?>
                        <option value="<?php echo esc_attr((string) $gid); ?>"><?php echo esc_html($gname); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span style="font-size:0.875rem;margin-right:0.25rem;">Status:</span>
                <select class="ld-gl-filter-status" style="padding:0.4rem 0.6rem;border:1px solid #cbd5e1;border-radius:4px;">
                    <option value="">Alle</option>
                    <option value="Ikke startet">Ikke startet</option>
                    <option value="Pågår">Pågår</option>
                    <option value="Fullført">Fullført</option>
                </select>
            </label>
            <label style="flex:1;min-width:180px;">
                <input type="search" class="ld-gl-search" placeholder="Søk i tabellen..." style="width:100%;padding:0.4rem 0.6rem;border:1px solid #cbd5e1;border-radius:4px;">
            </label>
        </div>
        <div class="ld-gl-table-scroll" style="overflow-x:auto;">
            <table class="ld-gl-table" style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                <thead>
                    <tr style="text-align:left;">
                        <th class="ld-gl-sort" data-sort="user"    data-label="Bruker"     style="padding:0.6rem;background:#e2e7ed;border-radius:6px 0 0 6px;cursor:pointer;user-select:none;" title="Klikk for å sortere">Bruker</th>
                        <th class="ld-gl-sort" data-sort="group"   data-label="Gruppe"     style="padding:0.6rem;background:#e2e7ed;cursor:pointer;user-select:none;" title="Klikk for å sortere">Gruppe</th>
                        <th class="ld-gl-sort" data-sort="course"  data-label="Kurs"       style="padding:0.6rem;background:#e2e7ed;cursor:pointer;user-select:none;" title="Klikk for å sortere">Kurs</th>
                        <th class="ld-gl-sort" data-sort="date"    data-label="Startdato"  style="padding:0.6rem;background:#e2e7ed;cursor:pointer;user-select:none;" title="Klikk for å sortere">Startdato</th>
                        <th class="ld-gl-sort" data-sort="percent" data-label="Progresjon" style="padding:0.6rem;background:#e2e7ed;cursor:pointer;user-select:none;" title="Klikk for å sortere">Progresjon</th>
                        <th class="ld-gl-sort" data-sort="status"  data-label="Status"     style="padding:0.6rem;background:#e2e7ed;border-radius:0 6px 6px 0;cursor:pointer;user-select:none;" title="Klikk for å sortere">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $r ) :
                        $start_display = ! empty($r['course_started_on'])
                            ? wp_date('d.m.Y', strtotime($r['course_started_on']))
                            : '—';
                        $status_order   = ['Ikke startet' => 0, 'Pågår' => 1, 'Fullført' => 2];
                        $status_num     = $status_order[ $r['status'] ] ?? 0;
                        $status_display = $r['status'] === 'Fullført'
                            ? '<span style="color:#22c55e;" aria-hidden="true">✓</span> ' . esc_html($r['status'])
                            : esc_html($r['status']);
                        $bar_color      = $r['percent'] >= 100 ? '#22c55e' : ($r['percent'] > 0 ? '#3b82f6' : '#94a3b8');
                    ?>
                        <tr class="ld-gl-row"
                            data-group="<?php echo esc_attr((string) $r['group_id']); ?>"
                            data-status="<?php echo esc_attr($r['status']); ?>"
                            data-search="<?php echo esc_attr(strtolower($r['user_name'] . ' ' . $r['user_email'] . ' ' . $r['group_name'] . ' ' . $r['course_title'])); ?>"
                            data-sort-user="<?php echo esc_attr($r['user_name']); ?>"
                            data-sort-group="<?php echo esc_attr($r['group_name']); ?>"
                            data-sort-course="<?php echo esc_attr($r['course_title']); ?>"
                            data-sort-date="<?php echo esc_attr($r['course_started_on'] ?: '9999-99-99'); ?>"
                            data-sort-percent="<?php echo (int) $r['percent']; ?>"
                            data-sort-status="<?php echo (int) $status_num; ?>"
                            style="border-bottom:1px solid #e2e8f0;">
                            <td style="padding:0.6rem;">
                                <strong><?php echo esc_html($r['user_name']); ?></strong><br>
                                <a href="mailto:<?php echo esc_attr($r['user_email']); ?>"><small style="color:rgb(83,98,119);"><?php echo esc_html($r['user_email']); ?></small></a>
                            </td>
                            <td style="padding:0.6rem;"><?php echo esc_html($r['group_name']); ?></td>
                            <td style="padding:0.6rem;"><?php echo esc_html($r['course_title']); ?></td>
                            <td style="padding:0.6rem;"><?php echo esc_html($start_display); ?></td>
                            <td style="padding:0.6rem;min-width:140px;">
                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                    <div style="flex:1;height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                                        <div style="height:100%;width:<?php echo (int) $r['percent']; ?>%;background:<?php echo $bar_color; ?>;border-radius:4px;transition:width 0.2s;"></div>
                                    </div>
                                    <span style="font-weight:600;min-width:2.5em;"><?php echo (int) $r['percent']; ?>%</span>
                                </div>
                            </td>
                            <td style="padding:0.6rem;"><?php echo $status_display; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="ld-gl-no-results" style="display:none;padding:1rem;color:#64748b;">Ingen treff.</p>
        <div class="ld-gl-pagination" style="display:flex;align-items:center;gap:0.75rem;margin-top:1rem;flex-wrap:wrap;">
            <button type="button" class="ld-gl-prev" style="padding:0.4rem 0.75rem;cursor:pointer;" disabled>← Forrige</button>
            <span class="ld-gl-page-info" style="font-size:0.875rem;">Side 1 av 1</span>
            <button type="button" class="ld-gl-next" style="padding:0.4rem 0.75rem;cursor:pointer;" disabled>Neste →</button>
        </div>
    </div>
    <script>
    (function() {
        var wrap = document.getElementById('<?php echo esc_js($id); ?>');
        if (!wrap) return;
        var tbody      = wrap.querySelector('.ld-gl-table tbody');
        var rows       = Array.prototype.slice.call(wrap.querySelectorAll('.ld-gl-row'));
        var groupSel   = wrap.querySelector('.ld-gl-filter-group');
        var statusSel  = wrap.querySelector('.ld-gl-filter-status');
        var searchInp  = wrap.querySelector('.ld-gl-search');
        var noResults  = wrap.querySelector('.ld-gl-no-results');
        var prevBtn    = wrap.querySelector('.ld-gl-prev');
        var nextBtn    = wrap.querySelector('.ld-gl-next');
        var pageInfo   = wrap.querySelector('.ld-gl-page-info');
        var perPage    = parseInt(wrap.dataset.perPage || '20', 10) || 20;
        var currentPage = 1;
        var sortCol    = null;
        var sortDir    = 1;

        function getFilteredRows() {
            var group  = (groupSel  && groupSel.value)                     || '';
            var status = (statusSel && statusSel.value)                    || '';
            var q      = (searchInp && searchInp.value.trim().toLowerCase()) || '';
            return rows.filter(function(row) {
                return (!group  || row.dataset.group  === group)
                    && (!status || row.dataset.status === status)
                    && (!q      || (row.dataset.search || '').indexOf(q) !== -1);
            });
        }

        function filter() { currentPage = 1; paginate(); }

        function paginate() {
            var visible    = getFilteredRows();
            var totalPages = Math.max(1, Math.ceil(visible.length / perPage));
            currentPage    = Math.min(Math.max(1, currentPage), totalPages);
            var start      = (currentPage - 1) * perPage;
            var end        = start + perPage;

            rows.forEach(function(row) {
                var idx  = visible.indexOf(row);
                row.style.display = (idx >= 0 && idx >= start && idx < end) ? '' : 'none';
            });

            if (noResults) noResults.style.display = visible.length ? 'none' : 'block';
            if (prevBtn)   { prevBtn.disabled = currentPage <= 1;           prevBtn.style.display = totalPages <= 1 ? 'none' : ''; }
            if (nextBtn)   { nextBtn.disabled = currentPage >= totalPages;  nextBtn.style.display = totalPages <= 1 ? 'none' : ''; }
            if (pageInfo)  pageInfo.textContent = 'Side ' + currentPage + ' av ' + totalPages + ' (' + visible.length + ' kurs)';
        }

        function sort(col) {
            var key   = 'data-sort-' + col;
            var isNum = col === 'percent' || col === 'status';
            if (sortCol === col) sortDir = -sortDir; else { sortCol = col; sortDir = 1; }
            rows.sort(function(a, b) {
                var va = a.getAttribute(key) || '';
                var vb = b.getAttribute(key) || '';
                if (isNum) return sortDir * (parseInt(va, 10) - parseInt(vb, 10));
                return sortDir * String(va).localeCompare(String(vb), 'no');
            });
            rows.forEach(function(row) { tbody.appendChild(row); });
            paginate();
        }

        wrap.querySelectorAll('.ld-gl-sort').forEach(function(th) {
            th.addEventListener('click', function() {
                wrap.querySelectorAll('.ld-gl-sort').forEach(function(h) {
                    h.textContent = (h.dataset.label || '').replace(/\s*[\u2191\u2193]\s*$/, '').trim();
                });
                sort(th.dataset.sort);
                th.textContent = (th.dataset.label || th.textContent) + ' ' + (sortDir === 1 ? '\u2191' : '\u2193');
            });
        });

        if (groupSel)  groupSel.addEventListener('change', filter);
        if (statusSel) statusSel.addEventListener('change', filter);
        if (searchInp) searchInp.addEventListener('input',  filter);
        if (prevBtn)   prevBtn.addEventListener('click', function() { if (currentPage > 1) { currentPage--; paginate(); } });
        if (nextBtn)   nextBtn.addEventListener('click', function() { currentPage++; paginate(); });

        filter();
    })();
    </script>
    <?php
    return ob_get_clean();
});
