<?php
/*
Plugin Name: Mobile Link Page / Link in Bio
Author URI: https://jacobmanus.com
Description: Add posts to a dedicated page for mobile viewing. Link in bio alternative.
Tags: link in bio, mobile link, link page
Version: 1.0.6
Requires PHP: 7.0
Requires at least: 6.2
Tested up to: 6.5.2
Author: <a href="https://jacobmanus.com">HumboldtK</a>
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit; 
}

require_once plugin_dir_path(__FILE__) . 'settings.php';

function mobile_links_create_page() {
    $options = get_option('mobile_links_settings');
    $slug = isset($options['mobile_links_custom_slug']) && !empty($options['mobile_links_custom_slug']) ? esc_attr($options['mobile_links_custom_slug']) : 'links';

    $page_check = get_page_by_path($slug);
    if ($page_check) {
        add_action('admin_notices', function() use ($slug) {
            echo '<div class="notice notice-error"><p>Warning: The slug "' . esc_html($slug) . '" is already in use. Please change the Mobile Links page slug in the settings.</p></div>';
        });
        return; 
    }

    $current_user_id = get_current_user_id();
    $links_page = array(
        'post_title'    => 'Links',
        'post_content'  => '[mobile_links]',
        'post_status'   => 'publish',
        'post_author'   => $current_user_id,
        'post_type'     => 'page',
        'post_name'     => $slug 
    );

    wp_insert_post($links_page);
}

register_activation_hook(__FILE__, 'mobile_links_create_page');

add_action('add_meta_boxes', 'mobile_links_add_meta_box');

function mobile_links_add_meta_box() {
    add_meta_box(
        'mobile_links_box_id',
        'Mobile Links',
        'mobile_links_meta_box_html',
        'post'
    );
}

function mobile_links_enqueue_scripts() {
    wp_enqueue_style('mobile-links', plugin_dir_url(__FILE__) . 'mobile-links.css', array(), '1.0.4');

    $options = get_option('mobile_links_settings');
    $max_width = isset($options['mobile_links_field_width']) ? esc_attr($options['mobile_links_field_width']) : '150';
    $max_height = isset($options['mobile_links_field_height']) ? esc_attr($options['mobile_links_field_height']) : '150';
    
    wp_add_inline_style('mobile-links', ".mobile-links-item img { max-width: {$max_width}px; max-height: {$max_height}px; }");
}

add_action('wp_enqueue_scripts', 'mobile_links_enqueue_scripts', 20); // Priority set to 20 to load after other plugins
add_action('admin_enqueue_scripts', 'mobile_links_enqueue_admin_scripts', 20); // Priority set to 20 to load after other plugins

function mobile_links_meta_box_html($post) {
    wp_nonce_field('mobile_links_meta_box', 'mobile_links_meta_box_nonce');
    $value = get_post_meta($post->ID, '_mobile_links_meta_key', true);
    $photo_url = get_post_meta($post->ID, '_mobile_links_custom_photo_url', true);
    $image_id = get_post_meta($post->ID, '_mobile_links_custom_photo_id', true);
    $image_src = wp_get_attachment_url($image_id);

    echo '<label for="mobile_links_field">Add this post to the Mobile Links page?</label>';
    echo '<input type="checkbox" id="mobile_links_field" name="mobile_links_field" value="1" ' . checked(1, $value, false) . ' />';
    echo '<p><label for="upload_custom_image_button">Custom Photo</label></p>';
    echo '<p><input id="upload_custom_image_button" type="button" class="button" value="' . esc_attr__('Upload Image', 'text-domain') . '" />';
    echo '<input id="mobile_links_custom_photo_id" name="mobile_links_custom_photo_id" type="hidden" value="' . esc_html($image_id) . '" /></p>';
    echo '<div id="custom-image-container">';
    if ($image_src) {
        echo '<div class="image-wrapper">';
        echo '<img src="' . esc_url($image_src) . '" style="max-width: 100%;" />';
        echo '<span id="remove_custom_image_button" class="remove-custom-image">';
        echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'xmark-solid.svg') . '" style="width: 24px; height: 24px;" alt="' . esc_attr__('Remove Image', 'text-domain') . '">';
        echo '</span>';
        echo '</div>';
    }
    echo '</div>';
}


add_action('admin_footer', 'mobile_links_add_script_to_admin');

function mobile_links_add_script_to_admin() {
    wp_enqueue_script('mobile-links-admin', plugin_dir_url(__FILE__) . 'mobile-links-admin.js', array('jquery'), '1.0.4', true);
}

function mobile_links_save_postdata($post_id) {
    if (!isset($_POST['mobile_links_meta_box_nonce'])) {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mobile_links_meta_box_nonce'])), 'mobile_links_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if ('page' === $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    $new_mobile_links_meta_value = (isset($_POST['mobile_links_field']) && $_POST['mobile_links_field'] === '1') ? '1' : '0';
    update_post_meta($post_id, '_mobile_links_meta_key', $new_mobile_links_meta_value);
    if (isset($_POST['mobile_links_custom_photo_id'])) {
        $new_photo_id = sanitize_text_field($_POST['mobile_links_custom_photo_id']);
        update_post_meta($post_id, '_mobile_links_custom_photo_id', $new_photo_id);
    } else {
        delete_post_meta($post_id, '_mobile_links_custom_photo_id');
    }
}

add_action('save_post', 'mobile_links_save_postdata');

function mobile_links_shortcode_func() {
    $args = array(
        'post_type' => 'post',
        'meta_key' => '_mobile_links_meta_key',
        'meta_value' => '1',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $output = '';

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $output = '<div class="mobile-links-grid">';
        
        while ($query->have_posts()) {
            $query->the_post();

            $output .= '<div class="mobile-links-item">';
            $output .= '<a href="' . get_permalink() . '">';
            $custom_photo_id = get_post_meta(get_the_ID(), '_mobile_links_custom_photo_id', true);
            $custom_photo_url = wp_get_attachment_url($custom_photo_id);
            if (!empty($custom_photo_url)) {
                $output .= '<img src="' . esc_url($custom_photo_url) . '">';
            } else {
                $output .= get_the_post_thumbnail(null, 'medium');
            }
            $output .= '</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }
    return '<p>No posts found.</p>';
}

add_shortcode('mobile_links', 'mobile_links_shortcode_func');

function mobile_links_add_action_links($links) {
    $mylinks = array(
        '<a href="' . admin_url('options-general.php?page=mobile-links-settings') . '">Settings</a>',
    );
    return array_merge($mylinks, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'mobile_links_add_action_links');

function mobile_links_enqueue_admin_scripts() {
    wp_enqueue_style('mobile-links-admin', plugin_dir_url(__FILE__) . 'mobile-links-admin.css', array(), '1.0.4');
    wp_enqueue_script('mobile-links-admin', plugin_dir_url(__FILE__) . 'mobile-links-admin.js', array('jquery'), '1.0.4', true);
    wp_localize_script('mobile-links-admin', 'mobileLinks', array(
        'pluginUrl' => plugin_dir_url(__FILE__)
    ));
}
?>
