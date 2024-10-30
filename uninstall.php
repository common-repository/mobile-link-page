<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$option_name = 'mobile_links_option';

delete_option($option_name);

// For site options in Multisite
delete_site_option($option_name);

// Use WordPress functions for database manipulation
function mobile_links_custom_table_uninstall() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'mobile_links_mytable';

    $cache_key = 'mobile_links_table_exists_' . $table_name;
    $cache_group = 'mobile_links_custom_tables';

    // Try to get the cached result
    $table_exists = wp_cache_get($cache_key, $cache_group);

    if ($table_exists === false) {
        // Cache miss, so make the database call
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

        // Save the result to the cache
        wp_cache_set($cache_key, $table_exists, $cache_group);
    }

    if ($table_exists) {
        // Use dbDelta function to drop the table more safely
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta("DROP TABLE IF EXISTS $table_name");

        // After modifying the database, clear the related cache
        wp_cache_delete($cache_key, $cache_group);
    }
}

// Call the uninstall function
mobile_links_custom_table_uninstall();
?>
