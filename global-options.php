<?php

/**
 * Plugin Name: Global Options
 * Description: Custom global options page for contact details and site-wide settings
 * Version: 1.5
 * Author: Yasir Shabbir
 * Author URI: https://yasirshabbir.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Global_Options
{

    private $option_name = 'global_options_data';
    private $cleanup_option = 'global_options_cleanup_on_delete';

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_import_export'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Register dynamic data for Bricks Builder
        add_filter('bricks/dynamic_tags_list', array($this, 'register_bricks_tags'));
        add_filter('bricks/dynamic_tags/render_tag', array($this, 'render_bricks_tag'), 10, 3);
        add_filter('bricks/dynamic_data_options', array($this, 'add_bricks_data_group'));

        // Render tags in content/text - HIGHEST PRIORITY
        add_filter('bricks/frontend/render_data', array($this, 'render_content_tags'), 5, 2);

        // Process element settings to replace tags in URLs and attributes - EARLY PRIORITY
        add_filter('bricks/element/settings', array($this, 'process_element_settings'), 5, 2);
        
        // Additional filter to catch any remaining tags in rendered output
        add_filter('bricks/frontend/render_element', array($this, 'process_rendered_element'), 999, 2);
    }

    /**
     * Handle import and export actions
     */
    public function handle_import_export()
    {
        // Export functionality
        if (isset($_GET['action']) && $_GET['action'] === 'global_options_export' && 
            isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'global_options_export')) {
            
            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized access');
            }

            $options = get_option($this->option_name, array());
            $export_data = array(
                'version' => '1.5',
                'export_date' => current_time('mysql'),
                'data' => $options
            );

            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="global-options-' . date('Y-m-d-His') . '.json"');
            echo json_encode($export_data, JSON_PRETTY_PRINT);
            exit;
        }

        // Import functionality
        if (isset($_POST['global_options_import']) && check_admin_referer('global_options_import', 'global_options_import_nonce')) {
            
            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized access');
            }

            if (empty($_FILES['import_file']['tmp_name'])) {
                add_settings_error('global_options', 'import_error', 'Please select a file to import.', 'error');
                set_transient('global_options_import_error', 'Please select a file to import.', 45);
                return;
            }

            $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
            $import_data = json_decode($file_content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                add_settings_error('global_options', 'import_error', 'Invalid JSON file. Please upload a valid export file.', 'error');
                set_transient('global_options_import_error', 'Invalid JSON file.', 45);
                return;
            }

            if (!isset($import_data['data']) || !is_array($import_data['data'])) {
                add_settings_error('global_options', 'import_error', 'Invalid export file format.', 'error');
                set_transient('global_options_import_error', 'Invalid export file format.', 45);
                return;
            }

            // Sanitize imported data
            $sanitized_data = $this->sanitize_options($import_data['data']);
            update_option($this->option_name, $sanitized_data);

            add_settings_error('global_options', 'import_success', 'Settings imported successfully!', 'updated');
            set_transient('global_options_import_success', 'Settings imported successfully!', 45);
            
            wp_redirect(admin_url('admin.php?page=global-options&tab=settings'));
            exit;
        }
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'Global Options',
            'Global Options',
            'manage_options',
            'global-options',
            array($this, 'render_options_page'),
            'dashicons-admin-site-alt3',
            30
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting(
            'global_options_group',
            $this->option_name,
            array($this, 'sanitize_options')
        );

        register_setting(
            'global_options_group',
            $this->cleanup_option,
            array(
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => function ($value) {
                    return $value ? '1' : '0';
                }
            )
        );
    }

    /**
     * Sanitize options
     */
    public function sanitize_options($input)
    {
        $sanitized = array();

        // Sanitize contact details
        $sanitized['phone'] = sanitize_text_field($input['phone'] ?? '');
        $sanitized['phone_whatsapp'] = sanitize_text_field($input['phone_whatsapp'] ?? '');
        $sanitized['phone_tollfree'] = sanitize_text_field($input['phone_tollfree'] ?? '');
        $sanitized['phone_mobile'] = sanitize_text_field($input['phone_mobile'] ?? '');
        $sanitized['email'] = sanitize_email($input['email'] ?? '');
        $sanitized['email_support'] = sanitize_email($input['email_support'] ?? '');
        $sanitized['email_info'] = sanitize_email($input['email_info'] ?? '');
        $sanitized['email_billing'] = sanitize_email($input['email_billing'] ?? '');
        $sanitized['email_sales'] = sanitize_email($input['email_sales'] ?? '');
        $sanitized['address'] = sanitize_textarea_field($input['address'] ?? '');
        $sanitized['business_hours'] = sanitize_textarea_field($input['business_hours'] ?? '');

        // Sanitize business information
        $sanitized['company_name'] = sanitize_text_field($input['company_name'] ?? '');
        $sanitized['tagline'] = sanitize_text_field($input['tagline'] ?? '');
        $sanitized['registration_number'] = sanitize_text_field($input['registration_number'] ?? '');
        $sanitized['vat_number'] = sanitize_text_field($input['vat_number'] ?? '');
        $sanitized['tax_id'] = sanitize_text_field($input['tax_id'] ?? '');

        // Sanitize location details
        $sanitized['city'] = sanitize_text_field($input['city'] ?? '');
        $sanitized['state'] = sanitize_text_field($input['state'] ?? '');
        $sanitized['country'] = sanitize_text_field($input['country'] ?? '');
        $sanitized['zipcode'] = sanitize_text_field($input['zipcode'] ?? '');

        // Sanitize social links
        $social_fields = array('facebook', 'instagram', 'linkedin', 'twitter', 'youtube', 'tiktok', 'pinterest', 'whatsapp', 'telegram', 'discord', 'snapchat', 'reddit');
        foreach ($social_fields as $field) {
            $sanitized['social_' . $field] = esc_url_raw($input['social_' . $field] ?? '');
        }

        // Sanitize ecommerce fields
        $sanitized['shipping_info'] = sanitize_textarea_field($input['shipping_info'] ?? '');
        $sanitized['return_policy'] = sanitize_textarea_field($input['return_policy'] ?? '');
        $sanitized['free_shipping_threshold'] = sanitize_text_field($input['free_shipping_threshold'] ?? '');
        $sanitized['currency_symbol'] = sanitize_text_field($input['currency_symbol'] ?? '');
        $sanitized['sale_badge'] = sanitize_text_field($input['sale_badge'] ?? '{woo_product_on_sale} OFF');
        $sanitized['out_of_stock_badge'] = sanitize_text_field($input['out_of_stock_badge'] ?? 'Out of stock');
        $sanitized['in_stock_badge'] = sanitize_text_field($input['in_stock_badge'] ?? '{woo_product_stock} in stock');

        // Sanitize legal/policy URLs
        $sanitized['privacy_policy_url'] = esc_url_raw($input['privacy_policy_url'] ?? '');
        $sanitized['terms_url'] = esc_url_raw($input['terms_url'] ?? '');
        $sanitized['cookie_policy_url'] = esc_url_raw($input['cookie_policy_url'] ?? '');
        $sanitized['refund_policy_url'] = esc_url_raw($input['refund_policy_url'] ?? '');
        $sanitized['shipping_policy_url'] = esc_url_raw($input['shipping_policy_url'] ?? '');

        // Sanitize banner fields
        $sanitized['banner_prefix'] = sanitize_text_field($input['banner_prefix'] ?? '');
        $sanitized['banner_suffix'] = sanitize_text_field($input['banner_suffix'] ?? '');
        $sanitized['banner_enabled'] = isset($input['banner_enabled']) ? '1' : '0';

        // Sanitize animated typing fields
        $sanitized['animated_typing_1'] = sanitize_text_field($input['animated_typing_1'] ?? '');
        $sanitized['animated_typing_2'] = sanitize_text_field($input['animated_typing_2'] ?? '');
        $sanitized['animated_typing_3'] = sanitize_text_field($input['animated_typing_3'] ?? '');

        // Sanitize CTA fields
        $sanitized['cta_text'] = sanitize_text_field($input['cta_text'] ?? '');
        $sanitized['cta_url'] = esc_url_raw($input['cta_url'] ?? '');
        $sanitized['cta_secondary_text'] = sanitize_text_field($input['cta_secondary_text'] ?? '');
        $sanitized['cta_secondary_url'] = esc_url_raw($input['cta_secondary_url'] ?? '');

        return $sanitized;
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'toplevel_page_global-options') {
            return;
        }

        wp_enqueue_style('lato-font', 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;600;700&display=swap', array(), null);

        add_filter('admin_body_class', function ($classes) {
            return $classes . ' global-options-dark-mode';
        });

        wp_add_inline_style('wp-admin', $this->get_admin_styles());
        wp_add_inline_script('jquery', $this->get_admin_scripts());
    }

    /**
     * Get admin styles
     */
    private function get_admin_styles()
    {
        return "
            /* Yasir Shabbir Dark Mode Branding - Full Page Dark */
            body.global-options-dark-mode {
                background: #121212 !important;
            }
            
            body.global-options-dark-mode #wpcontent {
                background: #121212 !important;
            }
            
            body.global-options-dark-mode #wpbody-content {
                background: #121212 !important;
            }
            
            body.global-options-dark-mode .wrap {
                background: #121212 !important;
            }
            
            .global-options-wrap {
                margin: 20px 0;
                font-family: 'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .global-options-header {
                background: #1e1e1e;
                padding: 25px 30px;
                border-left: 4px solid #16e791;
                margin-bottom: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                border-radius: 3px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .global-options-header h1 {
                margin: 0;
                font-size: 26px;
                color: #ffffff;
                font-weight: 700;
                letter-spacing: -0.5px;
            }
            
            .go-header-actions {
                display: flex;
                gap: 10px;
            }

            .global-options-wrap form {
                max-width: 1200px;
            }
            
            /* Tab Navigation */
            .go-tabs-navigation {
                background: #1e1e1e;
                border-radius: 3px 3px 0 0;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                border: 1px solid #333333;
                border-bottom: none;
                display: flex;
                flex-wrap: wrap;
                padding: 0;
                margin: 0;
                overflow-x: auto;
            }
            
            .go-tab-button {
                flex: 1;
                min-width: 120px;
                padding: 18px 20px;
                background: transparent;
                border: none;
                border-bottom: 3px solid transparent;
                color: #e0e0e0;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                position: relative;
                font-family: 'Lato', sans-serif;
            }
            
            .go-tab-button:hover {
                background: #2a2a2a;
                color: #16e791;
            }
            
            .go-tab-button.active {
                background: #2a2a2a;
                color: #16e791;
                border-bottom-color: #16e791;
            }
            
            .go-tab-button.active::after {
                content: '';
                position: absolute;
                bottom: -1px;
                left: 0;
                right: 0;
                height: 3px;
                background: #16e791;
                box-shadow: 0 0 10px rgba(22, 231, 145, 0.5);
                animation: tabGlow 2s ease-in-out infinite;
            }
            
            @keyframes tabGlow {
                0%, 100% { box-shadow: 0 0 10px rgba(22, 231, 145, 0.5); }
                50% { box-shadow: 0 0 20px rgba(22, 231, 145, 0.8); }
            }
            
            .go-tab-icon {
                font-size: 16px;
            }
            
            /* Tab Content */
            .go-tabs-content {
                background: #1e1e1e;
                border: 1px solid #333333;
                border-radius: 0 0 3px 3px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            }
            
            .go-tab-panel {
                display: none;
                padding: 30px;
                animation: fadeIn 0.4s ease-in-out;
            }
            
            .go-tab-panel.active {
                display: block;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .go-form-group {
                margin-bottom: 20px;
            }
            
            .go-form-group:last-child {
                margin-bottom: 0;
            }
            
            .go-form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #ffffff;
                font-size: 14px;
            }
            
            .go-form-group input[type='text'],
            .go-form-group input[type='email'],
            .go-form-group input[type='url'],
            .go-form-group input[type='file'],
            .go-form-group textarea {
                width: 100%;
                padding: 11px 14px;
                border: 1px solid #444444;
                border-radius: 3px;
                font-size: 14px;
                font-family: 'Lato', sans-serif;
                background: #121212;
                color: #ffffff;
                transition: all 0.3s ease;
            }
            
            .go-form-group input::placeholder,
            .go-form-group textarea::placeholder {
                color: #6c757d;
            }
            
            .go-form-group input:focus,
            .go-form-group textarea:focus {
                border-color: #16e791;
                outline: none;
                box-shadow: 0 0 0 3px rgba(22, 231, 145, 0.15);
                background: #1e1e1e;
            }
            
            .go-form-group textarea {
                min-height: 100px;
                resize: vertical;
            }
            
            .go-form-group small {
                display: block;
                margin-top: 6px;
                color: #e0e0e0;
                font-size: 13px;
                opacity: 0.8;
            }
            
            .go-switch-field {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 15px;
                background: #121212;
                border-radius: 3px;
                border: 1px solid #333333;
                transition: all 0.3s ease;
            }
            
            .go-switch-field:hover {
                border-color: #16e791;
                box-shadow: 0 0 0 1px rgba(22, 231, 145, 0.1);
            }
            
            .go-switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 26px;
            }
            
            .go-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .go-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #444444;
                transition: .3s;
                border-radius: 26px;
            }
            
            .go-slider:before {
                position: absolute;
                content: '';
                height: 20px;
                width: 20px;
                left: 3px;
                bottom: 3px;
                background-color: #e0e0e0;
                transition: .3s;
                border-radius: 50%;
            }
            
            input:checked + .go-slider {
                background-color: #16e791;
                box-shadow: 0 0 10px rgba(22, 231, 145, 0.5);
            }
            
            input:checked + .go-slider:before {
                transform: translateX(24px);
                background-color: #ffffff;
            }
            
            .go-switch-label {
                font-weight: 600;
                color: #ffffff;
                font-size: 14px;
            }
            
            .go-animated-section {
                background: #2a2a2a;
                border: 1px solid #444444;
                border-radius: 3px;
                padding: 20px;
                margin-top: 20px;
                transition: all 0.3s ease;
            }
            
            .go-animated-section:hover {
                border-color: #16e791;
                box-shadow: 0 0 15px rgba(22, 231, 145, 0.1);
            }
            
            .go-animated-section-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
                padding-bottom: 12px;
                border-bottom: 2px solid #16e791;
            }
            
            .go-animated-section-title {
                font-size: 15px;
                font-weight: 700;
                color: #16e791;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .go-animated-icon {
                font-size: 18px;
            }
            
            .go-submit-wrap {
                background: #1e1e1e;
                padding: 20px 25px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                border-radius: 3px;
                border: 1px solid #333333;
                margin-top: 20px;
            }
            
            .go-submit-button {
                background: #16e791;
                color: #121212;
                border: none;
                padding: 13px 35px;
                font-size: 14px;
                font-weight: 700;
                border-radius: 3px;
                cursor: pointer;
                transition: all 0.3s ease;
                font-family: 'Lato', sans-serif;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .go-submit-button:hover {
                background: #13c97a;
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(22, 231, 145, 0.4);
            }
            
            .go-submit-button:active {
                transform: translateY(0);
            }

            .go-button-secondary {
                background: #444444;
                color: #ffffff;
                border: none;
                padding: 11px 24px;
                font-size: 13px;
                font-weight: 700;
                border-radius: 3px;
                cursor: pointer;
                transition: all 0.3s ease;
                font-family: 'Lato', sans-serif;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                text-decoration: none;
                display: inline-block;
            }
            
            .go-button-secondary:hover {
                background: #16e791;
                color: #121212;
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(22, 231, 145, 0.3);
            }
            
            .go-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .go-danger-zone {
                background: #2a1e1e;
                border: 1px solid #5c3333;
                border-radius: 3px;
                padding: 20px;
                margin-bottom: 20px;
                transition: all 0.3s ease;
            }
            
            .go-danger-zone:hover {
                border-color: #ff6b6b;
                box-shadow: 0 0 15px rgba(255, 107, 107, 0.1);
            }
            
            .go-danger-zone-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 15px;
                padding-bottom: 12px;
                border-bottom: 2px solid #c94444;
            }
            
            .go-danger-zone-title {
                font-size: 15px;
                font-weight: 700;
                color: #ff6b6b;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .go-danger-icon {
                font-size: 18px;
                color: #ff6b6b;
            }
            
            .go-danger-description {
                color: #e0e0e0;
                font-size: 13px;
                margin-bottom: 15px;
                opacity: 0.9;
            }

            .go-import-export-section {
                background: #2a2a2a;
                border: 1px solid #444444;
                border-radius: 3px;
                padding: 20px;
                margin-bottom: 20px;
                transition: all 0.3s ease;
            }
            
            .go-import-export-section:hover {
                border-color: #16e791;
                box-shadow: 0 0 15px rgba(22, 231, 145, 0.1);
            }
            
            .go-import-export-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 15px;
                padding-bottom: 12px;
                border-bottom: 2px solid #16e791;
            }
            
            .go-import-export-title {
                font-size: 15px;
                font-weight: 700;
                color: #16e791;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .go-import-export-description {
                color: #e0e0e0;
                font-size: 13px;
                margin-bottom: 15px;
                opacity: 0.9;
            }

            .go-import-export-actions {
                display: flex;
                gap: 20px;
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .go-export-action,
            .go-import-action {
                flex: 1;
                min-width: 280px;
            }
            
            .go-import-form-inline {
                display: flex;
                gap: 10px;
                align-items: flex-start;
                flex-wrap: wrap;
                margin-top: 10px;
            }
            
            .go-import-form-inline input[type='file'] {
                flex: 1;
                min-width: 250px;
                padding: 11px 14px;
                border: 1px solid #444444;
                border-radius: 3px;
                background: #121212;
                color: #ffffff;
                font-family: 'Lato', sans-serif;
                font-size: 14px;
            }
            
            body.global-options-dark-mode .updated.notice,
            body.global-options-dark-mode .settings-error.notice {
                background: #1e1e1e !important;
                border-left-color: #16e791 !important;
                color: #ffffff !important;
            }
            
            body.global-options-dark-mode .notice p {
                color: #ffffff !important;
            }

            body.global-options-dark-mode .notice.notice-error {
                border-left-color: #ff6b6b !important;
            }
            
            .global-options-wrap *::-webkit-scrollbar {
                width: 10px;
                height: 10px;
            }
            
            .global-options-wrap *::-webkit-scrollbar-track {
                background: #1e1e1e;
            }
            
            .global-options-wrap *::-webkit-scrollbar-thumb {
                background: #444444;
                border-radius: 3px;
            }
            
            .global-options-wrap *::-webkit-scrollbar-thumb:hover {
                background: #16e791;
            }

            .go-settings-modal {
                display: none;
                position: fixed;
                z-index: 999999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.8);
                backdrop-filter: blur(5px);
            }

            .go-settings-modal-content {
                background: #1e1e1e;
                margin: 5% auto;
                padding: 0;
                border: 1px solid #333333;
                border-radius: 3px;
                width: 90%;
                max-width: 600px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.5);
                animation: modalSlideIn 0.3s ease-out;
            }

            @keyframes modalSlideIn {
                from {
                    transform: translateY(-50px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            .go-modal-header {
                padding: 20px 25px;
                border-bottom: 1px solid #333333;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .go-modal-header h2 {
                margin: 0;
                color: #16e791;
                font-size: 18px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .go-modal-close {
                background: #444444;
                color: #ffffff;
                border: none;
                width: 32px;
                height: 32px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 20px;
                line-height: 1;
                transition: all 0.3s ease;
            }

            .go-modal-close:hover {
                background: #ff6b6b;
                transform: rotate(90deg);
            }

            .go-modal-body {
                padding: 25px;
            }

            .go-modal-section {
                margin-bottom: 25px;
            }

            .go-modal-section:last-child {
                margin-bottom: 0;
            }

            .go-modal-section h3 {
                color: #ffffff;
                font-size: 15px;
                font-weight: 700;
                margin: 0 0 12px 0;
            }

            .go-modal-section p {
                color: #e0e0e0;
                font-size: 13px;
                margin: 0 0 12px 0;
                opacity: 0.9;
            }
            
            @media (max-width: 782px) {
                .go-grid {
                    grid-template-columns: 1fr;
                }
                
                .global-options-header {
                    padding: 20px;
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 15px;
                }
                
                .go-header-actions {
                    width: 100%;
                }
                
                .go-tab-panel {
                    padding: 20px;
                }
                
                .go-tab-button {
                    min-width: 100px;
                    padding: 15px 10px;
                    font-size: 12px;
                }

                .go-import-export-actions,
                .go-import-form-inline {
                    flex-direction: column;
                }

                .go-settings-modal-content {
                    margin: 10% auto;
                    width: 95%;
                }
            }
        ";
    }

    /**
     * Get admin scripts
     */
    private function get_admin_scripts()
    {
        return "
            jQuery(document).ready(function($) {
                // Tab functionality
                $('.go-tab-button').on('click', function() {
                    var targetTab = $(this).data('tab');
                    
                    // Update buttons
                    $('.go-tab-button').removeClass('active');
                    $(this).addClass('active');
                    
                    // Update panels
                    $('.go-tab-panel').removeClass('active');
                    $('#' + targetTab).addClass('active');
                    
                    // Save active tab to localStorage
                    localStorage.setItem('go_active_tab', targetTab);
                });
                
                // Check URL for tab parameter
                var urlParams = new URLSearchParams(window.location.search);
                var tabParam = urlParams.get('tab');
                
                if (tabParam) {
                    // If tab parameter exists in URL, activate that tab
                    $('.go-tab-button[data-tab=\"tab-' + tabParam + '\"]').trigger('click');
                } else {
                    // Restore active tab from localStorage
                    var activeTab = localStorage.getItem('go_active_tab');
                    if (activeTab && $('#' + activeTab).length) {
                        $('.go-tab-button[data-tab=\"' + activeTab + '\"]').trigger('click');
                    } else {
                        // Default to first tab
                        $('.go-tab-button').first().addClass('active');
                        $('.go-tab-panel').first().addClass('active');
                    }
                }

                // Modal functionality
                $('.go-open-settings-modal').on('click', function(e) {
                    e.preventDefault();
                    $('#goSettingsModal').fadeIn(300);
                    $('body').css('overflow', 'hidden');
                });

                $('.go-modal-close, .go-settings-modal').on('click', function(e) {
                    if (e.target === this) {
                        $('#goSettingsModal').fadeOut(300);
                        $('body').css('overflow', 'auto');
                    }
                });

                // Prevent modal content clicks from closing modal
                $('.go-settings-modal-content').on('click', function(e) {
                    e.stopPropagation();
                });

                // ESC key to close modal
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape') {
                        $('#goSettingsModal').fadeOut(300);
                        $('body').css('overflow', 'auto');
                    }
                });
            });
        ";
    }

    /**
     * Render options page
     */
    public function render_options_page()
    {
        $options = get_option($this->option_name, array());
        
        // Set default values if not set
        if (!isset($options['sale_badge']) || $options['sale_badge'] === '') {
            $options['sale_badge'] = '{woo_product_on_sale} OFF';
        }
        if (!isset($options['out_of_stock_badge']) || $options['out_of_stock_badge'] === '') {
            $options['out_of_stock_badge'] = 'Out of stock';
        }
        if (!isset($options['in_stock_badge']) || $options['in_stock_badge'] === '') {
            $options['in_stock_badge'] = '{woo_product_stock} in stock';
        }
        
        $cleanup_enabled = get_option($this->cleanup_option, '0');
        $export_url = wp_nonce_url(admin_url('admin.php?page=global-options&action=global_options_export'), 'global_options_export');
        
        // Check for import messages
        $import_success = get_transient('global_options_import_success');
        $import_error = get_transient('global_options_import_error');
        
        if ($import_success) {
            delete_transient('global_options_import_success');
        }
        if ($import_error) {
            delete_transient('global_options_import_error');
        }
?>
<div class="wrap">
    <div class="global-options-wrap">

        <div class="global-options-header">
            <h1>🌐 Global Options</h1>
            <div class="go-header-actions">
                <button type="button" class="go-button-secondary go-open-settings-modal">⚙️ Settings</button>
            </div>
        </div>

        <?php settings_errors('global_options'); ?>

        <?php if ($import_success): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($import_success); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($import_error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($import_error); ?></p>
        </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('global_options_group'); ?>

            <!-- Tab Navigation -->
            <div class="go-tabs-navigation">
                <button type="button" class="go-tab-button" data-tab="tab-contact">
                    <span class="go-tab-icon">📞</span>
                    <span>Contact</span>
                </button>
                <button type="button" class="go-tab-button" data-tab="tab-business">
                    <span class="go-tab-icon">🏢</span>
                    <span>Business</span>
                </button>
                <button type="button" class="go-tab-button" data-tab="tab-social">
                    <span class="go-tab-icon">🔗</span>
                    <span>Social</span>
                </button>
                <button type="button" class="go-tab-button" data-tab="tab-ecommerce">
                    <span class="go-tab-icon">🛒</span>
                    <span>E-commerce</span>
                </button>
                <button type="button" class="go-tab-button" data-tab="tab-legal">
                    <span class="go-tab-icon">⚖️</span>
                    <span>Legal</span>
                </button>
                <button type="button" class="go-tab-button" data-tab="tab-banner">
                    <span class="go-tab-icon">🎉</span>
                    <span>Banner</span>
                </button>
                <button type="button" class="go-tab-button" data-tab="tab-cta">
                    <span class="go-tab-icon">🎯</span>
                    <span>CTA</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="go-tabs-content">

                <!-- Contact Details Tab -->
                <div id="tab-contact" class="go-tab-panel">
                    <div class="go-grid">
                        <div class="go-form-group">
                            <label>Phone Number</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[phone]"
                                value="<?php echo esc_attr($options['phone'] ?? ''); ?>"
                                placeholder="+1 (555) 123-4567">
                            <small>Main business phone number</small>
                        </div>

                        <div class="go-form-group">
                            <label>WhatsApp Number</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[phone_whatsapp]"
                                value="<?php echo esc_attr($options['phone_whatsapp'] ?? ''); ?>"
                                placeholder="+1 (555) 123-4567">
                            <small>WhatsApp contact number</small>
                        </div>

                        <div class="go-form-group">
                            <label>Toll-Free Number</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[phone_tollfree]"
                                value="<?php echo esc_attr($options['phone_tollfree'] ?? ''); ?>"
                                placeholder="1-800-123-4567">
                            <small>Toll-free contact number</small>
                        </div>

                        <div class="go-form-group">
                            <label>Mobile Number</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[phone_mobile]"
                                value="<?php echo esc_attr($options['phone_mobile'] ?? ''); ?>"
                                placeholder="+1 (555) 987-6543">
                            <small>Mobile contact number</small>
                        </div>
                    </div>

                    <div class="go-grid">
                        <div class="go-form-group">
                            <label>Email (General)</label>
                            <input type="email" name="<?php echo $this->option_name; ?>[email]"
                                value="<?php echo esc_attr($options['email'] ?? ''); ?>"
                                placeholder="contact@example.com">
                            <small>Primary business email</small>
                        </div>

                        <div class="go-form-group">
                            <label>Support Email</label>
                            <input type="email" name="<?php echo $this->option_name; ?>[email_support]"
                                value="<?php echo esc_attr($options['email_support'] ?? ''); ?>"
                                placeholder="support@example.com">
                            <small>Customer support email</small>
                        </div>

                        <div class="go-form-group">
                            <label>Info Email</label>
                            <input type="email" name="<?php echo $this->option_name; ?>[email_info]"
                                value="<?php echo esc_attr($options['email_info'] ?? ''); ?>"
                                placeholder="info@example.com">
                            <small>General information email</small>
                        </div>

                        <div class="go-form-group">
                            <label>Billing Email</label>
                            <input type="email" name="<?php echo $this->option_name; ?>[email_billing]"
                                value="<?php echo esc_attr($options['email_billing'] ?? ''); ?>"
                                placeholder="billing@example.com">
                            <small>Billing and invoices email</small>
                        </div>

                        <div class="go-form-group">
                            <label>Sales Email</label>
                            <input type="email" name="<?php echo $this->option_name; ?>[email_sales]"
                                value="<?php echo esc_attr($options['email_sales'] ?? ''); ?>"
                                placeholder="sales@example.com">
                            <small>Sales inquiries email</small>
                        </div>
                    </div>
                </div>

                <!-- Business Information Tab -->
                <div id="tab-business" class="go-tab-panel">
                    <div class="go-form-group">
                        <label>Company Name</label>
                        <input type="text" name="<?php echo $this->option_name; ?>[company_name]"
                            value="<?php echo esc_attr($options['company_name'] ?? ''); ?>"
                            placeholder="Your Company Ltd.">
                        <small>Official registered company name</small>
                    </div>

                    <div class="go-form-group">
                        <label>Company Tagline</label>
                        <input type="text" name="<?php echo $this->option_name; ?>[tagline]"
                            value="<?php echo esc_attr($options['tagline'] ?? ''); ?>"
                            placeholder="Your business slogan or tagline">
                        <small>Short description or motto</small>
                    </div>

                    <div class="go-grid">
                        <div class="go-form-group">
                            <label>Registration Number</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[registration_number]"
                                value="<?php echo esc_attr($options['registration_number'] ?? ''); ?>"
                                placeholder="12345678">
                            <small>Company registration number</small>
                        </div>

                        <div class="go-form-group">
                            <label>VAT Number</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[vat_number]"
                                value="<?php echo esc_attr($options['vat_number'] ?? ''); ?>" placeholder="GB123456789">
                            <small>VAT/GST registration number</small>
                        </div>

                        <div class="go-form-group">
                            <label>Tax ID</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[tax_id]"
                                value="<?php echo esc_attr($options['tax_id'] ?? ''); ?>" placeholder="12-3456789">
                            <small>Tax identification number</small>
                        </div>
                    </div>

                    <div class="go-form-group">
                        <label>Business Address</label>
                        <textarea name="<?php echo $this->option_name; ?>[address]"
                            placeholder="123 Main Street, City, State, ZIP"><?php echo esc_textarea($options['address'] ?? ''); ?></textarea>
                        <small>Full business address</small>
                    </div>

                    <div class="go-form-group">
                        <label>Business Hours</label>
                        <textarea name="<?php echo $this->option_name; ?>[business_hours]"
                            placeholder="Mon-Fri: 9:00 AM - 6:00 PM&#10;Sat: 10:00 AM - 4:00 PM&#10;Sun: Closed"><?php echo esc_textarea($options['business_hours'] ?? ''); ?></textarea>
                        <small>Your business operating hours</small>
                    </div>

                    <div class="go-grid">
                        <div class="go-form-group">
                            <label>City</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[city]"
                                value="<?php echo esc_attr($options['city'] ?? ''); ?>" placeholder="New York">
                            <small>City name</small>
                        </div>

                        <div class="go-form-group">
                            <label>State/Province</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[state]"
                                value="<?php echo esc_attr($options['state'] ?? ''); ?>" placeholder="New York">
                            <small>State or province</small>
                        </div>

                        <div class="go-form-group">
                            <label>Country</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[country]"
                                value="<?php echo esc_attr($options['country'] ?? ''); ?>" placeholder="United States">
                            <small>Country name</small>
                        </div>

                        <div class="go-form-group">
                            <label>ZIP/Postal Code</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[zipcode]"
                                value="<?php echo esc_attr($options['zipcode'] ?? ''); ?>" placeholder="10001">
                            <small>ZIP or postal code</small>
                        </div>
                    </div>
                </div>

                <!-- Social Media Links Tab -->
                <div id="tab-social" class="go-tab-panel">
                    <div class="go-grid">
                        <div class="go-form-group">
                            <label>Facebook</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_facebook]"
                                value="<?php echo esc_attr($options['social_facebook'] ?? ''); ?>"
                                placeholder="https://facebook.com/yourpage">
                        </div>

                        <div class="go-form-group">
                            <label>Instagram</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_instagram]"
                                value="<?php echo esc_attr($options['social_instagram'] ?? ''); ?>"
                                placeholder="https://instagram.com/yourpage">
                        </div>

                        <div class="go-form-group">
                            <label>LinkedIn</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_linkedin]"
                                value="<?php echo esc_attr($options['social_linkedin'] ?? ''); ?>"
                                placeholder="https://linkedin.com/company/yourcompany">
                        </div>

                        <div class="go-form-group">
                            <label>Twitter (X)</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_twitter]"
                                value="<?php echo esc_attr($options['social_twitter'] ?? ''); ?>"
                                placeholder="https://twitter.com/yourhandle">
                        </div>

                        <div class="go-form-group">
                            <label>YouTube</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_youtube]"
                                value="<?php echo esc_attr($options['social_youtube'] ?? ''); ?>"
                                placeholder="https://youtube.com/@yourchannel">
                        </div>

                        <div class="go-form-group">
                            <label>TikTok</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_tiktok]"
                                value="<?php echo esc_attr($options['social_tiktok'] ?? ''); ?>"
                                placeholder="https://tiktok.com/@yourhandle">
                        </div>

                        <div class="go-form-group">
                            <label>Pinterest</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_pinterest]"
                                value="<?php echo esc_attr($options['social_pinterest'] ?? ''); ?>"
                                placeholder="https://pinterest.com/yourhandle">
                        </div>

                        <div class="go-form-group">
                            <label>WhatsApp</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_whatsapp]"
                                value="<?php echo esc_attr($options['social_whatsapp'] ?? ''); ?>"
                                placeholder="https://wa.me/1234567890">
                        </div>

                        <div class="go-form-group">
                            <label>Telegram</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_telegram]"
                                value="<?php echo esc_attr($options['social_telegram'] ?? ''); ?>"
                                placeholder="https://t.me/yourchannel">
                        </div>

                        <div class="go-form-group">
                            <label>Discord</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_discord]"
                                value="<?php echo esc_attr($options['social_discord'] ?? ''); ?>"
                                placeholder="https://discord.gg/yourserver">
                        </div>

                        <div class="go-form-group">
                            <label>Snapchat</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_snapchat]"
                                value="<?php echo esc_attr($options['social_snapchat'] ?? ''); ?>"
                                placeholder="https://snapchat.com/add/yourhandle">
                        </div>

                        <div class="go-form-group">
                            <label>Reddit</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[social_reddit]"
                                value="<?php echo esc_attr($options['social_reddit'] ?? ''); ?>"
                                placeholder="https://reddit.com/r/yoursubreddit">
                        </div>
                    </div>
                </div>

                <!-- E-commerce Settings Tab -->
                <div id="tab-ecommerce" class="go-tab-panel">
                    <div class="go-form-group">
                        <label>Shipping Information</label>
                        <textarea name="<?php echo $this->option_name; ?>[shipping_info]"
                            placeholder="Free shipping on orders over $50&#10;Delivery in 3-5 business days"><?php echo esc_textarea($options['shipping_info'] ?? ''); ?></textarea>
                        <small>General shipping details</small>
                    </div>

                    <div class="go-form-group">
                        <label>Return Policy</label>
                        <textarea name="<?php echo $this->option_name; ?>[return_policy]"
                            placeholder="30-day return policy&#10;Full refund or exchange"><?php echo esc_textarea($options['return_policy'] ?? ''); ?></textarea>
                        <small>Return and refund policy summary</small>
                    </div>

                    <div class="go-grid">
                        <div class="go-form-group">
                            <label>Free Shipping Threshold</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[free_shipping_threshold]"
                                value="<?php echo esc_attr($options['free_shipping_threshold'] ?? ''); ?>"
                                placeholder="$50">
                            <small>Minimum order for free shipping</small>
                        </div>

                        <div class="go-form-group">
                            <label>Currency Symbol</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[currency_symbol]"
                                value="<?php echo esc_attr($options['currency_symbol'] ?? ''); ?>" placeholder="$">
                            <small>Your store's currency symbol</small>
                        </div>
                    </div>

                    <div class="go-animated-section">
                        <div class="go-animated-section-header">
                            <span class="go-animated-icon">🏷️</span>
                            <span class="go-animated-section-title">Product Badges</span>
                        </div>

                        <div class="go-form-group">
                            <label>Sale Badge</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[sale_badge]"
                                value="<?php echo esc_attr($options['sale_badge']); ?>"
                                placeholder="{woo_product_on_sale} OFF">
                            <small>Badge text for products on sale. Use Bricks dynamic tag {woo_product_on_sale} to
                                display discount percentage. Example output: -49% OFF</small>
                        </div>

                        <div class="go-form-group">
                            <label>Out of Stock Badge</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[out_of_stock_badge]"
                                value="<?php echo esc_attr($options['out_of_stock_badge']); ?>"
                                placeholder="Out of stock">
                            <small>Badge text for out of stock products. Example output: Out of stock</small>
                        </div>

                        <div class="go-form-group">
                            <label>In Stock Badge</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[in_stock_badge]"
                                value="<?php echo esc_attr($options['in_stock_badge']); ?>"
                                placeholder="{woo_product_stock} in stock">
                            <small>Badge text for in stock products. Use Bricks dynamic tag {woo_product_stock} to
                                display stock quantity. Example output: 15 in stock</small>
                        </div>
                    </div>
                </div>

                <!-- Legal & Policy URLs Tab -->
                <div id="tab-legal" class="go-tab-panel">
                    <div class="go-grid">
                        <div class="go-form-group">
                            <label>Privacy Policy URL</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[privacy_policy_url]"
                                value="<?php echo esc_attr($options['privacy_policy_url'] ?? ''); ?>"
                                placeholder="https://example.com/privacy-policy">
                            <small>Link to privacy policy page</small>
                        </div>

                        <div class="go-form-group">
                            <label>Terms & Conditions URL</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[terms_url]"
                                value="<?php echo esc_attr($options['terms_url'] ?? ''); ?>"
                                placeholder="https://example.com/terms">
                            <small>Link to terms and conditions</small>
                        </div>

                        <div class="go-form-group">
                            <label>Cookie Policy URL</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[cookie_policy_url]"
                                value="<?php echo esc_attr($options['cookie_policy_url'] ?? ''); ?>"
                                placeholder="https://example.com/cookie-policy">
                            <small>Link to cookie policy page</small>
                        </div>

                        <div class="go-form-group">
                            <label>Refund Policy URL</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[refund_policy_url]"
                                value="<?php echo esc_attr($options['refund_policy_url'] ?? ''); ?>"
                                placeholder="https://example.com/refund-policy">
                            <small>Link to refund policy page</small>
                        </div>

                        <div class="go-form-group">
                            <label>Shipping Policy URL</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[shipping_policy_url]"
                                value="<?php echo esc_attr($options['shipping_policy_url'] ?? ''); ?>"
                                placeholder="https://example.com/shipping-policy">
                            <small>Link to shipping policy page</small>
                        </div>
                    </div>
                </div>

                <!-- Sale Banner Tab -->
                <div id="tab-banner" class="go-tab-panel">
                    <div class="go-form-group">
                        <div class="go-switch-field">
                            <label class="go-switch">
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[banner_enabled]"
                                    value="1" <?php checked('1', $options['banner_enabled'] ?? '0'); ?>>
                                <span class="go-slider"></span>
                            </label>
                            <span class="go-switch-label">Enable Sale Banner</span>
                        </div>
                        <small>Toggle to show/hide the banner on your website</small>
                    </div>

                    <div class="go-form-group">
                        <label>Prefix</label>
                        <input type="text" name="<?php echo $this->option_name; ?>[banner_prefix]"
                            value="<?php echo esc_attr($options['banner_prefix'] ?? ''); ?>"
                            placeholder="e.g., Save big">
                        <small>Text before the animated words</small>
                    </div>

                    <div class="go-form-group">
                        <label>Suffix</label>
                        <input type="text" name="<?php echo $this->option_name; ?>[banner_suffix]"
                            value="<?php echo esc_attr($options['banner_suffix'] ?? ''); ?>"
                            placeholder="e.g., Limited time only!">
                        <small>Text after the animated words</small>
                    </div>

                    <div class="go-animated-section">
                        <div class="go-animated-section-header">
                            <span class="go-animated-icon">⚡</span>
                            <span class="go-animated-section-title">Animated Typing</span>
                        </div>

                        <div class="go-form-group">
                            <label>Typing 1</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[animated_typing_1]"
                                value="<?php echo esc_attr($options['animated_typing_1'] ?? ''); ?>"
                                placeholder="e.g., Summer Sale">
                            <small>First animated text</small>
                        </div>

                        <div class="go-form-group">
                            <label>Typing 2</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[animated_typing_2]"
                                value="<?php echo esc_attr($options['animated_typing_2'] ?? ''); ?>"
                                placeholder="e.g., Up to 50% OFF">
                            <small>Second animated text</small>
                        </div>

                        <div class="go-form-group">
                            <label>Typing 3</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[animated_typing_3]"
                                value="<?php echo esc_attr($options['animated_typing_3'] ?? ''); ?>"
                                placeholder="e.g., Free Shipping">
                            <small>Third animated text</small>
                        </div>
                    </div>
                </div>

                <!-- Call-to-Action Tab -->
                <div id="tab-cta" class="go-tab-panel">
                    <div class="go-grid">
                        <div class="go-form-group">
                            <label>Primary CTA Text</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[cta_text]"
                                value="<?php echo esc_attr($options['cta_text'] ?? ''); ?>" placeholder="Get Started">
                            <small>Main call-to-action button text</small>
                        </div>

                        <div class="go-form-group">
                            <label>Primary CTA URL</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[cta_url]"
                                value="<?php echo esc_attr($options['cta_url'] ?? ''); ?>"
                                placeholder="https://example.com/get-started">
                            <small>Main button destination URL</small>
                        </div>

                        <div class="go-form-group">
                            <label>Secondary CTA Text</label>
                            <input type="text" name="<?php echo $this->option_name; ?>[cta_secondary_text]"
                                value="<?php echo esc_attr($options['cta_secondary_text'] ?? ''); ?>"
                                placeholder="Learn More">
                            <small>Secondary call-to-action button text</small>
                        </div>

                        <div class="go-form-group">
                            <label>Secondary CTA URL</label>
                            <input type="url" name="<?php echo $this->option_name; ?>[cta_secondary_url]"
                                value="<?php echo esc_attr($options['cta_secondary_url'] ?? ''); ?>"
                                placeholder="https://example.com/learn-more">
                            <small>Secondary button destination URL</small>
                        </div>
                    </div>
                </div>

            </div>

            <div class="go-submit-wrap">
                <?php submit_button('Save Changes', 'primary go-submit-button', 'submit', false); ?>
            </div>
        </form>

        <div
            style="background: #1e1e1e; padding: 15px 25px; margin-top: 15px; border-radius: 3px; border: 1px solid #333333; text-align: center; color: #e0e0e0; font-size: 13px;">
            Made with 💚 by <a href="https://yasirshabbir.com" target="_blank"
                style="color: #16e791; text-decoration: none; font-weight: 600;">Yasir Shabbir</a> • Version 1.5
        </div>

    </div>
</div>

<!-- Settings Modal -->
<div id="goSettingsModal" class="go-settings-modal">
    <div class="go-settings-modal-content">
        <div class="go-modal-header">
            <h2>⚙️ Plugin Settings</h2>
            <button type="button" class="go-modal-close">×</button>
        </div>
        <div class="go-modal-body">

            <!-- Import/Export Section -->
            <div class="go-modal-section">
                <h3>💾 Backup & Restore</h3>
                <p>Export your settings to create a backup, or import previously exported settings to restore your
                    configuration.</p>

                <div style="margin-bottom: 20px;">
                    <h4 style="color: #ffffff; font-size: 14px; margin: 0 0 10px 0;">Export Settings</h4>
                    <a href="<?php echo esc_url($export_url); ?>" class="go-button-secondary"
                        style="display: inline-block;">📥 Download Settings</a>
                    <p style="color: #e0e0e0; font-size: 12px; margin: 8px 0 0 0; opacity: 0.8;">Download all your
                        current settings as a JSON file</p>
                </div>

                <div>
                    <h4 style="color: #ffffff; font-size: 14px; margin: 0 0 10px 0;">Import Settings</h4>
                    <form method="post" action="" enctype="multipart/form-data" style="margin: 0;">
                        <?php wp_nonce_field('global_options_import', 'global_options_import_nonce'); ?>
                        <div class="go-import-form-inline">
                            <input type="file" name="import_file" accept=".json" required
                                style="flex: 1; min-width: 200px;">
                            <button type="submit" name="global_options_import" class="go-button-secondary">📤 Import
                                Settings</button>
                        </div>
                        <p style="color: #e0e0e0; font-size: 12px; margin: 8px 0 0 0; opacity: 0.8;">Select a JSON
                            export file to restore settings</p>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="go-modal-section">
                <form method="post" action="options.php" style="margin: 0;">
                    <?php settings_fields('global_options_group'); ?>
                    <div class="go-danger-zone" style="margin: 0;">
                        <div class="go-danger-zone-header">
                            <span class="go-danger-icon">⚠️</span>
                            <span class="go-danger-zone-title">Danger Zone</span>
                        </div>
                        <p class="go-danger-description">
                            When enabled, all plugin data (including all your settings and configurations) will be
                            permanently deleted when you uninstall this plugin. This action cannot be undone.
                        </p>
                        <div class="go-form-group" style="margin-bottom: 15px;">
                            <div class="go-switch-field">
                                <label class="go-switch">
                                    <input type="checkbox" name="<?php echo $this->cleanup_option; ?>" value="1"
                                        <?php checked('1', $cleanup_enabled); ?>>
                                    <span class="go-slider"></span>
                                </label>
                                <span class="go-switch-label">Remove all plugin data on uninstall</span>
                            </div>
                            <small>Enable this to automatically clean up all data when deleting the plugin</small>
                        </div>
                        <?php submit_button('Save Cleanup Setting', 'primary go-submit-button', 'submit', false); ?>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<?php
    }

    /**
     * Add Global Options group to Bricks dynamic data
     */
    public function add_bricks_data_group($groups)
    {
        $groups[] = [
            'name'  => 'global_options',
            'label' => 'Global Options'
        ];
        return $groups;
    }

    /**
     * Register Bricks Builder dynamic data tags
     */
    public function register_bricks_tags($tags)
    {
        // Contact Details - Phone Numbers
        $tags[] = [
            'name'  => '{global_phone}',
            'label' => 'Phone Number',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_phone_whatsapp}',
            'label' => 'WhatsApp Number',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_phone_tollfree}',
            'label' => 'Toll-Free Number',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_phone_mobile}',
            'label' => 'Mobile Number',
            'group' => 'global_options',
        ];

        // Contact Details - Emails
        $tags[] = [
            'name'  => '{global_email}',
            'label' => 'Email (General)',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_email_support}',
            'label' => 'Support Email',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_email_info}',
            'label' => 'Info Email',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_email_billing}',
            'label' => 'Billing Email',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_email_sales}',
            'label' => 'Sales Email',
            'group' => 'global_options',
        ];

        // Contact Details - Address & Hours
        $tags[] = [
            'name'  => '{global_address}',
            'label' => 'Business Address',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_business_hours}',
            'label' => 'Business Hours',
            'group' => 'global_options',
        ];

        // Business Information
        $tags[] = [
            'name'  => '{global_company_name}',
            'label' => 'Company Name',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_tagline}',
            'label' => 'Company Tagline',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_registration_number}',
            'label' => 'Registration Number',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_vat_number}',
            'label' => 'VAT Number',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_tax_id}',
            'label' => 'Tax ID',
            'group' => 'global_options',
        ];

        // Location Details
        $tags[] = [
            'name'  => '{global_city}',
            'label' => 'City',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_state}',
            'label' => 'State/Province',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_country}',
            'label' => 'Country',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_zipcode}',
            'label' => 'ZIP/Postal Code',
            'group' => 'global_options',
        ];

        // Social Media Links
        $social_networks = [
            'facebook'  => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin'  => 'LinkedIn',
            'twitter'   => 'Twitter (X)',
            'youtube'   => 'YouTube',
            'tiktok'    => 'TikTok',
            'pinterest' => 'Pinterest',
            'whatsapp'  => 'WhatsApp',
            'telegram'  => 'Telegram',
            'discord'   => 'Discord',
            'snapchat'  => 'Snapchat',
            'reddit'    => 'Reddit'
        ];

        foreach ($social_networks as $key => $label) {
            $tags[] = [
                'name'  => '{global_social_' . $key . '}',
                'label' => $label,
                'group' => 'global_options',
            ];
        }

        // E-commerce Fields
        $tags[] = [
            'name'  => '{global_shipping_info}',
            'label' => 'Shipping Information',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_return_policy}',
            'label' => 'Return Policy',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_free_shipping_threshold}',
            'label' => 'Free Shipping Threshold',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_currency_symbol}',
            'label' => 'Currency Symbol',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_sale_badge}',
            'label' => 'Sale Badge',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_out_of_stock_badge}',
            'label' => 'Out of Stock Badge',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_in_stock_badge}',
            'label' => 'In Stock Badge',
            'group' => 'global_options',
        ];

        // Legal/Policy URLs
        $tags[] = [
            'name'  => '{global_privacy_policy_url}',
            'label' => 'Privacy Policy URL',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_terms_url}',
            'label' => 'Terms & Conditions URL',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_cookie_policy_url}',
            'label' => 'Cookie Policy URL',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_refund_policy_url}',
            'label' => 'Refund Policy URL',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_shipping_policy_url}',
            'label' => 'Shipping Policy URL',
            'group' => 'global_options',
        ];

        // Banner fields
        $tags[] = [
            'name'  => '{global_banner_prefix}',
            'label' => 'Banner Prefix',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_banner_suffix}',
            'label' => 'Banner Suffix',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_banner_enabled}',
            'label' => 'Banner Enabled',
            'group' => 'global_options',
        ];

        // Animated typing fields
        $tags[] = [
            'name'  => '{global_animated_typing_1}',
            'label' => 'Animated Typing 1',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_animated_typing_2}',
            'label' => 'Animated Typing 2',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_animated_typing_3}',
            'label' => 'Animated Typing 3',
            'group' => 'global_options',
        ];

        // Call-to-Action Fields
        $tags[] = [
            'name'  => '{global_cta_text}',
            'label' => 'CTA Button Text',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_cta_url}',
            'label' => 'CTA Button URL',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_cta_secondary_text}',
            'label' => 'Secondary CTA Text',
            'group' => 'global_options',
        ];

        $tags[] = [
            'name'  => '{global_cta_secondary_url}',
            'label' => 'Secondary CTA URL',
            'group' => 'global_options',
        ];

        return $tags;
    }

    /**
     * Render Bricks Builder dynamic data tag
     */
    public function render_bricks_tag($tag, $post_id, $context)
    {
        $options = get_option($this->option_name, array());
        $field_key = str_replace(['{', '}', 'global_'], '', $tag);

        // For banner_enabled, return proper boolean string for Bricks
        if ($field_key === 'banner_enabled') {
            $is_enabled = (isset($options[$field_key]) && $options[$field_key] === '1');
            return $is_enabled ? 'True' : 'False';
        }

        if (isset($options[$field_key]) && $options[$field_key] !== '') {
            return $options[$field_key];
        }

        return '';
    }

    /**
     * Render tags in text/content fields
     * This processes tags at the highest priority to ensure they're replaced early
     */
    public function render_content_tags($content, $post_id)
    {
        if (empty($content) || !is_string($content)) {
            return $content;
        }

        return $this->replace_global_tags($content);
    }

    /**
     * Process element settings to replace tags in URLs and attributes
     * This now properly handles tags in all element settings
     */
    public function process_element_settings($settings, $element)
    {
        if (empty($settings) || !is_array($settings)) {
            return $settings;
        }

        // Recursively process ALL settings to find and replace tags
        array_walk_recursive($settings, function (&$value) {
            if (is_string($value) && strpos($value, '{global_') !== false) {
                $value = $this->replace_global_tags($value);
            }
        });

        return $settings;
    }

    /**
     * Process rendered element output to catch any remaining tags
     * This is a final catch-all to ensure tags in HTML output are replaced
     */
    public function process_rendered_element($html, $element)
    {
        if (empty($html) || !is_string($html)) {
            return $html;
        }

        return $this->replace_global_tags($html);
    }

    /**
     * Replace all global tags in a string
     * Centralized function to ensure consistent tag replacement
     */
    private function replace_global_tags($content)
    {
        if (empty($content) || !is_string($content)) {
            return $content;
        }

        $options = get_option($this->option_name, array());

        // Replace all {global_*} tags in content
        $content = preg_replace_callback(
            '/{global_([a-z0-9_]+)}/i',
            function ($matches) use ($options) {
                $field_key = $matches[1];
                
                // Special handling for banner_enabled
                if ($field_key === 'banner_enabled') {
                    $is_enabled = (isset($options[$field_key]) && $options[$field_key] === '1');
                    return $is_enabled ? 'True' : 'False';
                }
                
                return isset($options[$field_key]) && $options[$field_key] !== '' ? $options[$field_key] : '';
            },
            $content
        );

        return $content;
    }
}

// Initialize the plugin
new Global_Options();

// Register uninstall hook to clean up data if enabled
register_uninstall_hook(__FILE__, 'global_options_uninstall');

/**
 * Uninstall function - removes all plugin data if cleanup option is enabled
 */
function global_options_uninstall()
{
    // Check if cleanup is enabled
    $cleanup_enabled = get_option('global_options_cleanup_on_delete', '0');

    if ($cleanup_enabled === '1') {
        // Delete all plugin options
        delete_option('global_options_data');
        delete_option('global_options_cleanup_on_delete');
    }
}