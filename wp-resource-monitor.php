if ( ! defined( 'ABSPATH' ) ) exit;
<?php
/*
Plugin Name: Site Task Manager
Plugin URI: https://aliannezhadi.com/plugins/site-task-manager
Description: Monitor WordPress resource usage, plugin memory, users and external requests.
Version: 1.2
Author: Meysam Aliannezhadi
Author URI: https://aliannezhadi.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: site-task-manager
*/

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page(
        'Site Task Manager',
        'Site Task Manager',
        'manage_options',
        'site-task-manager',
        'wptm_render_dashboard',
        'dashicons-performance',
        3
    );
});

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_site-task-manager') return;
    wp_enqueue_script('wptm-live', plugin_dir_url(__FILE__) . 'wptm-live.js', [], '1.2', true);
    wp_localize_script('wptm-live', 'wptm_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);
});

add_action('wp_ajax_wptm_get_memory', function() {
    echo esc_html(size_format(memory_get_usage()));
    wp_die();
});

function wptm_render_dashboard() {
    check_admin_referer('site_task_manager_tab');
    $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'dashboard';
    $is_rtl = is_rtl();
    ?>
    <div class="wrap">
        <h1><?php echo $is_rtl ? '📊 تسک منیجر وردپرس' : '📊 Site Task Manager'; ?></h1>
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo esc_url( wp_nonce_url(admin_url('admin.php?page=wp-task-manager&tab=dashboard'), 'site_task_manager_tab') ); ?>" class="nav-tab<?php echo $tab === 'dashboard' ? ' nav-tab-active' : ''; ?>">
                <?php echo $is_rtl ? 'داشبورد' : 'Dashboard'; ?>
            </a>
            <a href="<?php echo esc_url( wp_nonce_url(admin_url('admin.php?page=wp-task-manager&tab=plugins'), 'site_task_manager_tab') ); ?>" class="nav-tab<?php echo $tab === 'plugins' ? ' nav-tab-active' : ''; ?>">
                <?php echo $is_rtl ? 'افزونه‌ها' : 'Plugins'; ?>
            </a>
            <a href="<?php echo esc_url( wp_nonce_url(admin_url('admin.php?page=wp-task-manager&tab=users'), 'site_task_manager_tab') ); ?>" class="nav-tab<?php echo $tab === 'users' ? ' nav-tab-active' : ''; ?>">
                <?php echo $is_rtl ? 'کاربران' : 'Users'; ?>
            </a>
            <a href="<?php echo esc_url( wp_nonce_url(admin_url('admin.php?page=wp-task-manager&tab=requests'), 'site_task_manager_tab') ); ?>" class="nav-tab<?php echo $tab === 'requests' ? ' nav-tab-active' : ''; ?>">
                <?php echo $is_rtl ? 'درخواست‌های خارجی' : 'External Requests'; ?>
            </a>
        </h2>
    <?php
    switch ($tab) {
        case 'plugins': wptm_render_plugins_tab($is_rtl); break;
        case 'users': wptm_render_users_tab($is_rtl); break;
        case 'requests': wptm_render_requests_tab($is_rtl); break;
        default: wptm_render_main_dashboard($is_rtl); break;
    }
    echo '</div>';
}

function wptm_render_main_dashboard($rtl) {
    $num_queries = get_num_queries();
    $load_time = timer_stop(0);
    $memory_usage = size_format(memory_get_usage());
    $memory_peak = size_format(memory_get_peak_usage());
    ?>
    <div id="live-memory-box" style="background:#f9f9f9;padding:10px;border:1px solid #ccc;margin-bottom:10px;display:inline-block;">
        <strong>💾 <?php echo $rtl ? 'مصرف رم زنده' : 'Live Memory Usage'; ?>:</strong>
        <span id="live-memory-value"><?php echo esc_html($memory_usage); ?></span>
    </div>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo $rtl ? 'شاخص' : 'Metric'; ?></th>
                <th><?php echo $rtl ? 'مقدار' : 'Value'; ?></th>
            </tr>
        </thead>
        <tbody>
            <tr><td>⏱ <?php echo $rtl ? 'زمان بارگذاری صفحه' : 'Page Load Time'; ?></td><td><?php echo esc_html($load_time); ?> seconds</td></tr>
            <tr><td>💾 <?php echo $rtl ? 'مصرف حافظه' : 'Memory Usage'; ?></td><td><?php echo esc_html($memory_usage); ?></td></tr>
            <tr><td>📈 <?php echo $rtl ? 'بیشینه مصرف حافظه' : 'Peak Memory Usage'; ?></td><td><?php echo esc_html($memory_peak); ?></td></tr>
            <tr><td>🔄 <?php echo $rtl ? 'تعداد Query دیتابیس' : 'Database Queries'; ?></td><td><?php echo esc_html($num_queries); ?></td></tr>
        </tbody>
    </table>
    <?php
}

function wptm_render_plugins_tab($rtl) {
    $active_plugins = get_option('active_plugins', []);
    ?>
    <h2>🔌 <?php echo $rtl ? 'مصرف رم افزونه‌ها' : 'Plugin Memory Usage'; ?></h2>
    <table class="widefat striped">
        <thead><tr><th><?php echo $rtl ? 'افزونه' : 'Plugin'; ?></th><th><?php echo $rtl ? 'رم مصرف‌شده' : 'Memory Used'; ?></th></tr></thead>
        <tbody>
        <?php foreach ($active_plugins as $plugin_file):
            $plugin_path = plugin_dir_path( __FILE__ ) . $plugin_file;
            if (!file_exists($plugin_path)) continue;
            $before = memory_get_usage();
            @include_once($plugin_path);
            $after = memory_get_usage();
            $used = $after - $before;
        ?>
            <tr>
                <td><?php echo esc_html($plugin_file); ?></td>
                <td><?php echo esc_html(size_format($used)); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function wptm_render_users_tab($rtl) {
    $users = get_users();
    ?>
    <h2>👤 <?php echo $rtl ? 'کاربران واردشده' : 'Logged-in Users'; ?></h2>
    <table class="widefat striped">
        <thead><tr><th><?php echo $rtl ? 'نام کاربری' : 'Username'; ?></th><th><?php echo $rtl ? 'نقش' : 'Role'; ?></th></tr></thead>
        <tbody>
        <?php foreach ($users as $user):
            $role = implode(', ', $user->roles); ?>
            <tr><td><?php echo esc_html($user->user_login); ?></td><td><?php echo esc_html($role); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function wptm_render_requests_tab($rtl) {
    $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? 'unknown'));
    $agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    $time = current_time('mysql');
    ?>
    <h2>🌐 <?php echo $rtl ? 'آخرین درخواست بیرونی' : 'Last External Request'; ?></h2>
    <table class="widefat striped">
        <thead><tr>
            <th><?php echo $rtl ? 'زمان' : 'Time'; ?></th>
            <th><?php echo $rtl ? 'آی‌پی' : 'IP'; ?></th>
            <th><?php echo $rtl ? 'نشانی' : 'URL'; ?></th>
            <th><?php echo $rtl ? 'مرورگر' : 'User Agent'; ?></th>
        </tr></thead>
        <tbody>
            <tr>
                <td><?php echo esc_html($time); ?></td>
                <td><?php echo esc_html($ip); ?></td>
                <td><?php echo esc_html($uri); ?></td>
                <td><?php echo esc_html($agent); ?></td>
            </tr>
        </tbody>
    </table>
    <?php
}
?>
