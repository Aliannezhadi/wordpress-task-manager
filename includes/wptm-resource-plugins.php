if ( ! defined( 'ABSPATH' ) ) exit;
<?php
// این فایل فقط مصرف حافظه بعد از بارگذاری هر پلاگین یا قالب را لاگ می‌کند

global $wptm_resource_log;
$wptm_resource_log = [];

function wptm_track_plugin_memory_usage($plugin) {
    global $wptm_resource_log;

    $usage = memory_get_usage();
    $wptm_resource_log['plugins'][] = [
        'name' => $plugin,
        'usage' => $usage,
        'formatted' => size_format($usage)
    ];
}
add_action('activated_plugin', 'wptm_track_plugin_memory_usage');
add_action('deactivated_plugin', 'wptm_track_plugin_memory_usage');

function wptm_display_plugin_usage_table() {
    global $wptm_resource_log;

    if (!current_user_can('manage_options')) return;
    if (empty($wptm_resource_log['plugins'])) return;

    echo '<h2>Plugin Memory Usage (approx.)</h2>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Plugin</th><th>Memory Usage</th></tr></thead><tbody>';

    foreach ($wptm_resource_log['plugins'] as $plugin_data) {
        echo '<tr><td>' . esc_html($plugin_data['name']) . '</td><td>' . esc_html($plugin_data['formatted']) . '</td></tr>';
    }

    echo '</tbody></table>';
}
add_action('admin_notices', 'wptm_display_plugin_usage_table');
?>
