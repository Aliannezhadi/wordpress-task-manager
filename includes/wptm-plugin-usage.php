if ( ! defined( 'ABSPATH' ) ) exit;
<?php
// مانیتور مصرف RAM توسط هر پلاگین در زمان لود کامل سایت

add_action('plugins_loaded', 'wptm_track_plugins_memory_usage', 100);

function wptm_track_plugins_memory_usage() {
    global $wptm_plugins_usage;
    $wptm_plugins_usage = [];

    $active_plugins = get_option('active_plugins', []);
    foreach ($active_plugins as $plugin_file) {
        $plugin_path = plugin_dir_path( __FILE__ ) . $plugin_file;

        if (!file_exists($plugin_path)) continue;

        $before_memory = memory_get_usage();
        include_once $plugin_path;
        $after_memory = memory_get_usage();

        $used_memory = $after_memory - $before_memory;

        $wptm_plugins_usage[] = [
            'plugin' => $plugin_file,
            'used_memory' => $used_memory,
            'formatted' => size_format($used_memory)
        ];
    }
}

// نمایش در پنل مدیریت
add_action('admin_menu', 'wptm_plugin_usage_menu');
function wptm_plugin_usage_menu() {
    add_submenu_page(
        'wp-task-manager',
        'Plugin Resource Usage',
        'Plugin Usage',
        'manage_options',
        'wptm-plugin-usage',
        'wptm_render_plugin_usage'
    );
}

function wptm_render_plugin_usage() {
    global $wptm_plugins_usage;

    echo '<div class="wrap"><h1>🔍 Plugin Memory Usage</h1>';
    echo '<table class="widefat striped"><thead><tr><th>Plugin</th><th>Memory Used</th></tr></thead><tbody>';

    if (!empty($wptm_plugins_usage)) {
        foreach ($wptm_plugins_usage as $entry) {
            echo '<tr>';
            echo '<td>' . esc_html($entry['plugin']) . '</td>';
            echo '<td>' . esc_html($entry['formatted']) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="2">No usage data collected yet.</td></tr>';
    }

    echo '</tbody></table></div>';
}
?>
