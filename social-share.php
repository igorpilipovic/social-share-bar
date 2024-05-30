<?php
/*
Plugin Name: Social Share Bar
Description: A plugin to add a customizable social share bar to posts, pages, and custom post types.
Version: 1.0
Author: Igor Pilipovic
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add settings page
function ssb_add_settings_page() {
    add_options_page('Social Share Bar Settings', 'Social Share Bar', 'manage_options', 'ssb-settings', 'ssb_render_settings_page');
}
add_action('admin_menu', 'ssb_add_settings_page');

// Register settings
function ssb_register_settings() {
    register_setting('ssb_settings_group', 'ssb_settings', 'ssb_sanitize_settings');
}
add_action('admin_init', 'ssb_register_settings');

// Render settings page
function ssb_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Social Share Bar Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ssb_settings_group');
            $settings = get_option('ssb_settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Display on</th>
                    <td>
                        <label><input type="checkbox" name="ssb_settings[display_on][]" value="post" <?php checked(in_array('post', (array) $settings['display_on'])); ?> /> Posts</label><br/>
                        <label><input type="checkbox" name="ssb_settings[display_on][]" value="page" <?php checked(in_array('page', (array) $settings['display_on'])); ?> /> Pages</label><br/>
                        <?php
                        $post_types = get_post_types(['_builtin' => false], 'objects');
                        foreach ($post_types as $post_type) {
                            ?>
                            <label><input type="checkbox" name="ssb_settings[display_on][]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, (array) $settings['display_on'])); ?> /> <?php echo esc_html($post_type->labels->singular_name); ?></label><br/>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Activate buttons for</th>
                    <td>
                        <label><input type="checkbox" name="ssb_settings[networks][]" value="facebook" <?php checked(in_array('facebook', (array) $settings['networks'])); ?> /> Facebook</label><br/>
                        <label><input type="checkbox" name="ssb_settings[networks][]" value="twitter" <?php checked(in_array('twitter', (array) $settings['networks'])); ?> /> Twitter</label><br/>
                        <label><input type="checkbox" name="ssb_settings[networks][]" value="google_plus" <?php checked(in_array('google_plus', (array) $settings['networks'])); ?> /> Google+</label><br/>
                        <label><input type="checkbox" name="ssb_settings[networks][]" value="pinterest" <?php checked(in_array('pinterest', (array) $settings['networks'])); ?> /> Pinterest</label><br/>
                        <label><input type="checkbox" name="ssb_settings[networks][]" value="linkedin" <?php checked(in_array('linkedin', (array) $settings['networks'])); ?> /> LinkedIn</label><br/>
                        <label><input type="checkbox" name="ssb_settings[networks][]" value="whatsapp" <?php checked(in_array('whatsapp', (array) $settings['networks'])); ?> /> WhatsApp</label><br/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Button size</th>
                    <td>
                        <label><input type="radio" name="ssb_settings[button_size]" value="small" <?php checked('small', $settings['button_size']); ?> /> Small</label><br/>
                        <label><input type="radio" name="ssb_settings[button_size]" value="medium" <?php checked('medium', $settings['button_size']); ?> /> Medium</label><br/>
                        <label><input type="radio" name="ssb_settings[button_size]" value="large" <?php checked('large', $settings['button_size']); ?> /> Large</label><br/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Icon color</th>
                    <td>
                        <label><input type="radio" name="ssb_settings[icon_color]" value="original" <?php checked('original', $settings['icon_color']); ?> /> Original</label><br/>
                        <label><input type="radio" name="ssb_settings[icon_color]" value="custom" <?php checked('custom', $settings['icon_color']); ?> /> Custom</label>
                        <input type="text" name="ssb_settings[custom_color]" value="<?php echo esc_attr($settings['custom_color']); ?>" class="ssb-color-picker" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Icon order</th>
                    <td>
                        <input type="text" name="ssb_settings[icon_order]" value="<?php echo esc_attr($settings['icon_order']); ?>" placeholder="e.g., facebook,twitter,linkedin" />
                        <p class="description">Enter the order of icons separated by commas. Only selected icons will be displayed.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Display position</th>
                    <td>
                        <label><input type="checkbox" name="ssb_settings[display_position][]" value="below_title" <?php checked(in_array('below_title', (array) $settings['display_position'])); ?> /> Below the post title</label><br/>
                        <label><input type="checkbox" name="ssb_settings[display_position][]" value="floating_left" <?php checked(in_array('floating_left', (array) $settings['display_position'])); ?> /> Floating on the left area</label><br/>
                        <label><input type="checkbox" name="ssb_settings[display_position][]" value="after_content" <?php checked(in_array('after_content', (array) $settings['display_position'])); ?> /> After the post content</label><br/>
                        <label><input type="checkbox" name="ssb_settings[display_position][]" value="inside_image" <?php checked(in_array('inside_image', (array) $settings['display_position'])); ?> /> Inside the featured image</label><br/>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Enqueue color picker
function ssb_enqueue_color_picker($hook_suffix) {
    if ($hook_suffix === 'settings_page_ssb-settings') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('ssb-color-picker', plugins_url('color-picker.js', __FILE__), array('wp-color-picker'), false, true);
    }
}
add_action('admin_enqueue_scripts', 'ssb_enqueue_color_picker');

// Add shortcode
function ssb_shortcode() {
    return ssb_render_share_bar();
}
add_shortcode('social_share_bar', 'ssb_shortcode');

// Sanitize the input
function ssb_sanitize_settings($settings) {
    if (isset($settings['icon_order'])) {
        $settings['icon_order'] = sanitize_text_field($settings['icon_order']);
    }
    return $settings;
}

// Render share bar
function ssb_render_share_bar() {
    if (!ssb_should_display_share_bar()) {
        return '';
    }

    $settings = get_option('ssb_settings');
    
    if (empty($settings['networks'])) {
        return ''; // No networks selected
    }

    $networks = $settings['networks'];
    $button_size = !empty($settings['button_size']) ? $settings['button_size'] : 'medium';
    $icon_color = !empty($settings['icon_color']) ? $settings['icon_color'] : 'original';
    $custom_color = !empty($settings['custom_color']) ? $settings['custom_color'] : '';
    $icon_order = !empty($settings['icon_order']) ? explode(',', $settings['icon_order']) : $networks;

    // Reorder icons based on the user-defined order
    $ordered_icons = [];
    foreach ($icon_order as $icon) {
        if (in_array($icon, $networks)) {
            $ordered_icons[] = $icon;
        }
    }

    ob_start();
    ?>
    <div class="ssb-share-bar ssb-size-<?php echo esc_attr($button_size); ?>">
        <?php
        foreach ($ordered_icons as $network) {
            if (in_array($network, $networks)) {
                switch ($network) {
                    case 'facebook':
                        $url = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode(get_permalink());
                        echo '<a href="' . esc_url($url) . '" class="ssb-button ssb-facebook ' . ($icon_color === 'custom' ? 'ssb-custom-color' : '') . '" ' . ($icon_color === 'custom' ? 'style="background-color:' . esc_attr($custom_color) . ';"' : '') . ' title="Share on Facebook"><span class="screen-reader-text">Share on Facebook</span><i class="fab fa-facebook-f"></i></a>';
                        break;
                    case 'twitter':
                        $url = 'https://twitter.com/intent/tweet?url=' . urlencode(get_permalink());
                        echo '<a href="' . esc_url($url) . '" class="ssb-button ssb-twitter ' . ($icon_color === 'custom' ? 'ssb-custom-color' : '') . '" ' . ($icon_color === 'custom' ? 'style="background-color:' . esc_attr($custom_color) . ';"' : '') . ' title="Share on Twitter"><span class="screen-reader-text">Share on Twitter</span><i class="fab fa-twitter"></i></a>';
                        break;
                    case 'google_plus':
                        $url = 'https://plus.google.com/share?url=' . urlencode(get_permalink());
                        echo '<a href="' . esc_url($url) . '" class="ssb-button ssb-google-plus ' . ($icon_color === 'custom' ? 'ssb-custom-color' : '') . '" ' . ($icon_color === 'custom' ? 'style="background-color:' . esc_attr($custom_color) . ';"' : '') . ' title="Share on Google+"><span class="screen-reader-text">Share on Google+</span><i class="fab fa-google-plus-g"></i></a>';
                        break;
                    case 'pinterest':
                        $url = 'https://pinterest.com/pin/create/button/?url=' . urlencode(get_permalink()) . '&media=' . urlencode(wp_get_attachment_url(get_post_thumbnail_id())) . '&description=' . urlencode(get_the_title());
                        echo '<a href="' . esc_url($url) . '" class="ssb-button ssb-pinterest ' . ($icon_color === 'custom' ? 'ssb-custom-color' : '') . '" ' . ($icon_color === 'custom' ? 'style="background-color:' . esc_attr($custom_color) . ';"' : '') . ' title="Pin it on Pinterest"><span class="screen-reader-text">Pin it on Pinterest</span><i class="fab fa-pinterest"></i></a>';
                        break;
                    case 'linkedin':
                        $url = 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode(get_permalink()) . '&title=' . urlencode(get_the_title());
                        echo '<a href="' . esc_url($url) . '" class="ssb-button ssb-linkedin ' . ($icon_color === 'custom' ? 'ssb-custom-color' : '') . '" ' . ($icon_color === 'custom' ? 'style="background-color:' . esc_attr($custom_color) . ';"' : '') . ' title="Share on LinkedIn"><span class="screen-reader-text">Share on LinkedIn</span><i class="fab fa-linkedin"></i></a>';
                        break;
                    case 'whatsapp':
                        $url = 'whatsapp://send?text=' . urlencode(get_the_title() . ' - ' . get_permalink());
                        echo '<a href="' . esc_url($url) . '" class="ssb-button ssb-whatsapp ssb-whatsapp-mobile ' . ($icon_color === 'custom' ? 'ssb-custom-color' : '') . '" ' . ($icon_color === 'custom' ? 'style="background-color:' . esc_attr($custom_color) . ';"' : '') . ' title="Share on WhatsApp"><span class="screen-reader-text">Share on WhatsApp</span><i class="fab fa-whatsapp"></i></a>';
                        break;
                }
            }
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}

// Render share bar in posts/pages/custom post types
function ssb_should_display_share_bar() {
    $settings = get_option('ssb_settings');
    if (is_singular() && !empty($settings['display_on']) && in_array(get_post_type(), (array) $settings['display_on'])) {
        return true;
    }
    return false;
}

function ssb_get_share_bar() {
    return ssb_render_share_bar();
}

function ssb_add_share_bar_to_content($content) {
    if (ssb_should_display_share_bar()) {
        $settings = get_option('ssb_settings');
        $share_bar = ssb_get_share_bar();
        
        $floating_content = '';

        if (!empty($settings['display_position'])) {
            $display_positions = $settings['display_position'];

            // Display below the post title
            if (in_array('below_title', $display_positions)) {
                $content = $share_bar . $content;
            }

            // Floating on the left area
            if (in_array('floating_left', $display_positions)) {
                $floating_content = '<div class="ssb-floating-left">' . $share_bar . '</div>';
            }

            // Display after the post content
            if (in_array('after_content', $display_positions)) {
                $content .= $share_bar;
            }

            // Display inside the featured image
            if (in_array('inside_image', $display_positions)) {
                $content = '<div class="ssb-inside-image">' . $share_bar . '</div>' . $content;
            }
        }

        if ($floating_content) {
            $content = $floating_content . $content;
        }
    }

    return $content;
}
add_filter('the_content', 'ssb_add_share_bar_to_content');

// Add CSS for the share bar
function ssb_enqueue_styles() {
    wp_enqueue_style('ssb-styles', plugins_url('style.css', __FILE__));
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'ssb_enqueue_styles');