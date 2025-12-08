<?php
/**
 * Performance Debugging Tool for Kursagenten
 * 
 * This file helps identify performance bottlenecks in the admin area.
 * Add this to kursagenten.php temporarily to debug performance issues.
 * 
 * Usage:
 * 1. Add: require_once KURSAG_PLUGIN_DIR . '/includes/performance-debug.php';
 * 2. Activate performance tracking by setting KURSAG_PERF_DEBUG constant to true
 * 3. Check admin pages and review the debug log
 */

// Only load if debug is enabled
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    return;
}

// Enable performance debugging by setting this constant to true in wp-config.php
if (!defined('KURSAG_PERF_DEBUG') || !KURSAG_PERF_DEBUG) {
    return;
}

class Kursagenten_Performance_Debugger {
    private static $start_time;
    private static $hooks = [];
    private static $queries = [];
    private static $http_requests = [];
    private static $file_includes = [];

    public static function init() {
        if (!is_admin()) {
            return;
        }

        self::$start_time = microtime(true);
        
        // Track specific admin hooks instead of 'all' hook (which is too heavy)
        $hooks_to_track = [
            'admin_init',
            'admin_menu',
            'admin_enqueue_scripts',
            'admin_head',
            'admin_footer',
            'plugins_loaded',
            'init',
            'wp_loaded'
        ];
        
        foreach ($hooks_to_track as $hook) {
            add_action($hook, function() use ($hook) {
                self::track_hook_execution($hook);
            }, 1);
        }
        
        // Track database queries
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            add_action('wp_footer', [__CLASS__, 'log_queries'], 999);
            add_action('admin_footer', [__CLASS__, 'log_queries'], 999);
        }
        
        // Track HTTP requests
        add_filter('pre_http_request', [__CLASS__, 'track_http_start'], 10, 3);
        add_action('http_api_debug', [__CLASS__, 'track_http_end'], 10, 5);
        
        // Track file includes
        add_filter('wp_insert_post_data', [__CLASS__, 'track_file_includes'], 10, 2);
        
        // Log performance data
        add_action('admin_footer', [__CLASS__, 'log_performance_data'], 999);
    }

    public static function track_hook_execution($hook_name) {
        if (!is_admin()) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $caller = 'unknown';
        
        // Skip our own frames and find the actual caller
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && strpos($trace['file'], 'kursagenten') !== false) {
                // Skip our own performance-debug.php file
                if (strpos($trace['file'], 'performance-debug.php') === false) {
                    $caller = basename($trace['file']);
                    if (isset($trace['line'])) {
                        $caller .= ':' . $trace['line'];
                    }
                    break;
                }
            }
        }

        if (!isset(self::$hooks[$hook_name])) {
            self::$hooks[$hook_name] = [
                'count' => 0,
                'callers' => [],
                'total_time' => 0
            ];
        }
        
        self::$hooks[$hook_name]['count']++;
        if ($caller !== 'unknown' && !in_array($caller, self::$hooks[$hook_name]['callers'])) {
            self::$hooks[$hook_name]['callers'][] = $caller;
        }
    }

    public static function track_http_start($preempt, $parsed_args, $url) {
        if (!is_admin()) {
            return $preempt;
        }
        
        if ((strpos($url, 'kursagenten') !== false) || 
            (strpos($url, 'lanseres.no') !== false) || 
            (strpos($url, 'kursagenten.no') !== false)) {
            
            $key = md5($url . serialize($parsed_args));
            self::$http_requests[$key] = [
                'url' => $url,
                'start_time' => microtime(true),
                'method' => isset($parsed_args['method']) ? $parsed_args['method'] : 'GET'
            ];
        }
        
        return $preempt;
    }

    public static function track_http_end($response, $context, $class, $parsed_args, $url) {
        $key = md5($url . serialize($parsed_args));
        if (isset(self::$http_requests[$key])) {
            self::$http_requests[$key]['end_time'] = microtime(true);
            self::$http_requests[$key]['duration'] = 
                self::$http_requests[$key]['end_time'] - self::$http_requests[$key]['start_time'];
            self::$http_requests[$key]['status'] = is_wp_error($response) ? 'error' : 'success';
        }
    }

    public static function log_queries() {
        global $wpdb;
        
        if (!defined('SAVEQUERIES') || !SAVEQUERIES || !isset($wpdb->queries)) {
            return;
        }

        foreach ($wpdb->queries as $query) {
            if (is_array($query) && count($query) >= 2) {
                $sql = $query[0];
                $time = $query[1];
                
                // Only track queries related to Kursagenten
                if (strpos($sql, 'kursagenten') !== false || 
                    strpos($sql, 'ka_') !== false ||
                    strpos($sql, 'kag_') !== false) {
                    
                    self::$queries[] = [
                        'query' => substr($sql, 0, 200), // Truncate for readability
                        'time' => $time,
                        'caller' => isset($query[2]) ? $query[2] : 'unknown'
                    ];
                }
            }
        }
    }

    public static function track_file_includes($data, $postarr) {
        // Track when files are included
        $backtrace = debug_backtrace();
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && strpos($trace['file'], 'kursagenten') !== false) {
                $file = str_replace(KURSAG_PLUGIN_DIR, '', $trace['file']);
                if (!in_array($file, self::$file_includes)) {
                    self::$file_includes[] = $file;
                }
            }
        }
        return $data;
    }

    public static function log_performance_data() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $total_time = microtime(true) - self::$start_time;
        
        $log = "\n" . str_repeat('=', 80) . "\n";
        $log .= "KURSAGENTEN PERFORMANCE DEBUG REPORT\n";
        $log .= str_repeat('=', 80) . "\n";
        $log .= "Page: " . (isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'N/A') . "\n";
        $log .= "Screen: " . (function_exists('get_current_screen') ? 
            (get_current_screen() ? get_current_screen()->id : 'N/A') : 'N/A') . "\n";
        $log .= "Total Page Load Time: " . number_format($total_time, 3) . " seconds\n";
        $log .= str_repeat('-', 80) . "\n";

        // Log hooks
        if (!empty(self::$hooks)) {
            $log .= "\nADMIN HOOKS EXECUTED:\n";
            foreach (self::$hooks as $hook => $data) {
                $log .= sprintf(
                    "  %s: %d times (called from: %s)\n",
                    $hook,
                    $data['count'],
                    implode(', ', array_slice($data['callers'], 0, 3))
                );
            }
        }

        // Log HTTP requests
        if (!empty(self::$http_requests)) {
            $log .= "\nHTTP REQUESTS MADE:\n";
            $total_http_time = 0;
            foreach (self::$http_requests as $req) {
                if (isset($req['duration'])) {
                    $duration = number_format($req['duration'], 3);
                    $total_http_time += $req['duration'];
                    $log .= sprintf(
                        "  [%s] %s: %s seconds\n",
                        $req['method'],
                        $req['url'],
                        $duration
                    );
                }
            }
            $log .= "\nTotal HTTP Request Time: " . number_format($total_http_time, 3) . " seconds\n";
            $log .= "HTTP requests account for " . 
                number_format(($total_http_time / $total_time) * 100, 1) . "% of total load time\n";
        }

        // Log database queries
        if (!empty(self::$queries)) {
            $log .= "\nDATABASE QUERIES:\n";
            $total_query_time = 0;
            $slow_queries = [];
            
            foreach (self::$queries as $query) {
                $total_query_time += $query['time'];
                if ($query['time'] > 0.01) { // Queries slower than 10ms
                    $slow_queries[] = $query;
                }
            }
            
            $log .= sprintf(
                "  Total queries: %d\n  Total query time: %s seconds\n",
                count(self::$queries),
                number_format($total_query_time, 3)
            );
            
            if (!empty($slow_queries)) {
                $log .= "\nSLOW QUERIES (>10ms):\n";
                foreach ($slow_queries as $query) {
                    $log .= sprintf(
                        "  [%s] %s\n    Called from: %s\n",
                        number_format($query['time'], 3) . 's',
                        $query['query'],
                        $query['caller']
                    );
                }
            }
        }

        // Log file includes
        if (!empty(self::$file_includes)) {
            $log .= "\nFILES INCLUDED:\n";
            foreach (self::$file_includes as $file) {
                $log .= "  " . $file . "\n";
            }
        }

        $log .= "\n" . str_repeat('=', 80) . "\n\n";

        // Write to error log
        error_log($log);

        // Also display in admin if on plugin pages
        $screen = get_current_screen();
        if ($screen && (
            strpos($screen->id, 'kursagenten') !== false || 
            (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY)
        )) {
            echo '<div class="notice notice-info"><pre style="max-height:400px;overflow:auto;">' . 
                 esc_html($log) . '</pre></div>';
        }
    }
}

// Initialize if debugging is enabled
if (defined('KURSAG_PERF_DEBUG') && KURSAG_PERF_DEBUG) {
    add_action('admin_init', [Kursagenten_Performance_Debugger::class, 'init'], 1);
}
