<?php

add_action('admin_menu', 'mobile_links_settings_page');
add_action('admin_init', 'mobile_links_settings_init');

function mobile_links_settings_page() {
    add_submenu_page(
        'options-general.php',
        'Mobile Links Settings',
        'Mobile Links',
        'manage_options',
        'mobile-links-settings',
        'mobile_links_settings_html'
    );
}

function mobile_links_settings_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = get_option('mobile_links_settings');
    $slug = !empty($options['mobile_links_custom_slug']) ? esc_attr($options['mobile_links_custom_slug']) : 'links';
    $link_url = home_url('/' . $slug);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('mobile_links_settings_group');
            do_settings_sections('mobile_links');
            submit_button('Save Settings');
            ?>
            <a href="<?php echo esc_url($link_url); ?>" class="button button-secondary" target="_blank">View Links Page</a>
        </form>
    </div>
    <?php
}

function mobile_links_settings_init() {
    register_setting('mobile_links_settings_group', 'mobile_links_settings', 'mobile_links_sanitize_settings');
    add_settings_section('mobile_links_section', 'Mobile Links Settings', null, 'mobile_links');
    add_settings_field('mobile_links_custom_slug', 'Link Page URL', 'mobile_links_custom_slug_cb', 'mobile_links', 'mobile_links_section');
    add_settings_field('mobile_links_field_width', 'Max Width (px)', 'mobile_links_field_size_cb', 'mobile_links', 'mobile_links_section', array('id' => 'mobile_links_field_width'));
    add_settings_field('mobile_links_field_height', 'Max Height (px)', 'mobile_links_field_size_cb', 'mobile_links', 'mobile_links_section', array('id' => 'mobile_links_field_height'));
}

function mobile_links_settings_section_callback() {
    echo '<p>Set the custom URL for your Mobile Links page. Make sure it does not conflict with other URLs.</p>';
}

function mobile_links_custom_slug_cb() {
    $options = get_option('mobile_links_settings');
    $slug = !empty($options['mobile_links_custom_slug']) ? esc_attr($options['mobile_links_custom_slug']) : 'links';
    echo '<input type="text" id="mobile_links_custom_slug" name="mobile_links_settings[mobile_links_custom_slug]" value="' . esc_attr($slug) . '">';
}

function mobile_links_field_size_cb($args) {
    $options = get_option('mobile_links_settings');
    $id = isset($args['id']) ? $args['id'] : '';
    $size = !empty($options[$id]) ? esc_attr($options[$id]) : '150';
    echo '<input type="text" name="mobile_links_settings[' . esc_attr($id) . ']" value="' . esc_attr($size) . '">';
}

function mobile_links_update_page_slug($old_slug, $new_slug) {
    $page = get_page_by_path($old_slug);

    if ($page) {
        wp_update_post(array(
            'ID' => $page->ID,
            'post_name' => $new_slug
        ));
        flush_rewrite_rules(); 
    } 
}

function mobile_links_sanitize_settings($input) {
    $new_input = array();
    $options = get_option('mobile_links_settings');
    if (isset($input['mobile_links_field_width'])) {
        $new_input['mobile_links_field_width'] = absint($input['mobile_links_field_width']);
        if ($new_input['mobile_links_field_width'] < 100 || $new_input['mobile_links_field_width'] > 2000) {
            add_settings_error(
                'mobile_links_settings',
                'mobile_links_width_error',
                'Width must be between 100 and 2000 pixels',
                'error'
            );
            $new_input['mobile_links_field_width'] = 150; 
        }
    }

    if (isset($input['mobile_links_field_height'])) {
        $new_input['mobile_links_field_height'] = absint($input['mobile_links_field_height']);
        if ($new_input['mobile_links_field_height'] < 100 || $new_input['mobile_links_field_height'] > 2000) {
            add_settings_error(
                'mobile_links_settings',
                'mobile_links_height_error',
                'Height must be between 100 and 2000 pixels',
                'error'
            );
            $new_input['mobile_links_field_height'] = 150; 
        }
    }

    if (isset($input['mobile_links_custom_slug'])) {
        $sanitized_slug = sanitize_title($input['mobile_links_custom_slug']);
        if (!empty($sanitized_slug)) {
            $new_input['mobile_links_custom_slug'] = $sanitized_slug;
        } else {
            add_settings_error(
                'mobile_links_settings',
                'mobile_links_slug_error',
                'Invalid slug provided; defaulting to "links"',
                'error'
            );
            $new_input['mobile_links_custom_slug'] = 'links'; 
        }
    }

    $old_slug = isset($options['mobile_links_custom_slug']) ? $options['mobile_links_custom_slug'] : 'links';
    $new_slug = isset($input['mobile_links_custom_slug']) ? sanitize_title($input['mobile_links_custom_slug']) : 'links';

    if ($new_slug !== $old_slug) {
        $new_input['mobile_links_custom_slug'] = $new_slug;

        mobile_links_update_page_slug($old_slug, $new_slug);
    } else {
        $new_input['mobile_links_custom_slug'] = $old_slug;
    }

    return $new_input;
}
?>
