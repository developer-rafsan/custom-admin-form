<?php
/**
 * Plugin Name: Custom Admin Form
 * Description: Admin-only form to submit air and ocean freight quotes, save entries, and send email notifications.
 * Version: 1.5
 * Author: Your Name
 */

 // Enqueue the custom admin CSS file
add_action('admin_enqueue_scripts', 'caf_enqueue_custom_css');

function caf_enqueue_custom_css() {
    wp_enqueue_style('custom-admin-form-css', plugin_dir_url(__FILE__) . 'custom-admin-form.css');
}

// Admin Menu
add_action('admin_menu', function () {
    add_menu_page('Custom Form', 'Custom Form', 'manage_options', 'custom-form-landing', 'caf_display_landing', 'dashicons-feedback', 6);

    add_submenu_page('custom-form-landing', 'Air Freight Form', 'Air Freight', 'manage_options', 'air-freight-form', 'caf_display_air_form');
    add_submenu_page('custom-form-landing', 'Ocean Freight Form', 'Ocean Freight', 'manage_options', 'ocean-freight-form', 'caf_display_ocean_form');
    add_submenu_page('custom-form-landing', 'Form Entries', 'View Entries', 'manage_options', 'form-entries', 'caf_display_entries');

    // Manual dev tool
    add_submenu_page('custom-form-landing', 'Recreate Tables (Dev)', 'Recreate Tables', 'manage_options', 'caf-recreate', 'caf_create_all_tables');
});

// Activation Hook – create tables
register_activation_hook(__FILE__, 'caf_create_all_tables');

// Table Creation Function (can be used manually too)
function caf_create_all_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Define tables for air and ocean freight
    $air_table = $wpdb->prefix . 'caf_air_entries';
    $ocean_table = $wpdb->prefix . 'caf_ocean_entries';

    // Air Freight table fields
    $air_fields = "
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        quote_number varchar(100),
        shipping_type varchar(100),
        quotation_date date,
        dimensions varchar(100),
        dimension_unit varchar(10),
        cargo_weight varchar(100),
        weight_unit varchar(10),
        nz_duties varchar(100),
        nz_gst varchar(100),
        transit_time varchar(100),
        cargo_details text,
        shipping_total varchar(100),
        customer_name varchar(100),
        customer_email varchar(100),
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ";

    // Ocean Freight table fields (adding Subject field)
    $ocean_fields = "
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        quote_number varchar(100),
        quotation_date date,
        shipping_type varchar(100),
        customer_name varchar(100),
        customer_email varchar(100),
        subject varchar(100),  -- New field for subject in Ocean Freight
        shipping_total varchar(100),
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ";

    // Create the tables using dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta("CREATE TABLE $air_table ($air_fields) $charset_collate;");
    dbDelta("CREATE TABLE $ocean_table ($ocean_fields) $charset_collate;");

    if (!defined('DOING_AJAX') && !defined('WP_CLI')) {
        echo "<div class='notice notice-success is-dismissible'><p>Tables created successfully.</p></div>";
    }
}

// Shared Form Renderer
function caf_render_form($type = 'Air') {
    $action_slug = ($type === 'Ocean') ? 'ocean-freight-form' : 'air-freight-form';
    $table_name = ($type === 'Ocean') ? 'caf_ocean_entries' : 'caf_air_entries';

    ?>
    <div class="wrap">
        <h2><?php echo $type; ?> Freight Quote Form</h2>
        <form method="post">
            <style>
                .caf-field { margin-bottom: 15px; }
                .caf-field input, .caf-field select, .caf-field textarea { width: 100%; padding: 8px; }
                .caf-row { display: flex; gap: 20px; }
                .caf-col { flex: 1; }
            </style>

            <?php if ($type === 'Air') : ?>
                <!-------------------------------------------------- Air Freight Fields -------------------------------------------------------------->
                <div class="caf-row">
                    <div class="caf-col caf-field"><input type="text" name="quote_number" placeholder="Quote Number" required></div>
                    <div class="caf-col caf-field">
                        <select placeholder="Select" name="shipping_type">
                            <option>Select Shipping Type</option>
                            <option value="USPS Economy">USPS Economy</option>
                            <option value="DHL Economy">DHL Economy</option>
                            <option value="FedEx Priority">FedEx Priority</option>
                        </select>
                    </div>
                    <div class="caf-col caf-field"><input type="date" name="quotation_date" required></div>
                </div>



                <div class="caf-row">
                    <div class="px_group_input caf-col caf-field">
                        <div class="caf-col caf-field"><input type="text" name="dimensions" placeholder="Dimensions (30x30x30)" required></div>
                        <div class="caf-col caf-field">
                            <select name="dimension_unit">
                                <option value="cm">cm</option>
                                <option value="in">in</option>
                            </select>
                        </div>
                    </div>

                    <div class="px_group_input caf-col caf-field">
                        <div class="caf-col caf-field"><input type="text" name="cargo_weight" placeholder="Cargo Weight" required></div>
                        <div class="caf-col caf-field">
                            <select name="cargo_unit">
                                <option value="lbs">lbs</option>
                                <option value="kgs">kgs</option>
                            </select>
                        </div>
                    </div>


                </div>

                <div class="caf-row">
                    <div class="caf-col caf-field"><input type="text" name="nz_duties" placeholder="NZ Duties Amou"></div>
                    <div class="caf-col caf-field"><input type="text" name="nz_gst" placeholder="NZ GST"></div>
                </div>

                <div class="caf-field"><input type="time" name="transit_time" value="00:00" required></div>
                <div class="caf-field"><textarea name="cargo_details" placeholder="Cargo Details" required></textarea></div>
                <div class="caf-field"><input type="text" name="shipping_total" placeholder="Total Shipping Cost (NZD)" required></div>

                <div class="caf-row">
                    <div class="caf-col caf-field"><input type="text" name="customer_name" placeholder="Customer Name" required></div>
                    <div class="caf-col caf-field"><input type="email" name="customer_email" placeholder="Customer Email" required></div>
                </div>
<!-------------------------------------------------- Air Freight Fields -------------------------------------------------------------->
            <?php else : ?>
                <!-- Ocean Freight Fields -->
                <div class="caf-col caf-field"><input type="text" name="quote_number" placeholder="Quote Number" required></div>
                <div class="caf-row">
                    <div class="caf-col caf-field"><input type="text" name="customer_name" placeholder="Customer Name" required></div>
                    <div class="caf-col caf-field"><input type="email" name="customer_email" placeholder="Customer Email" required></div>
                </div>
                <div class="caf-field"><input type="text" name="shipping_total" placeholder="Total Shipping Cost (NZD)" required></div>

                <div class="caf-field"><input type="date" name="quotation_date" placeholder="Date" required></div>
                <div class="caf-field"><input type="text" name="subject" placeholder="Subject" required></div>
            <?php endif; ?>

            <p><input type="submit" name="caf_submit_<?php echo strtolower($type); ?>" class="button button-primary" value="Submit Quote"></p>
        </form>
    </div>
    <?php

    if (isset($_POST['caf_submit_' . strtolower($type)])) {
        global $wpdb;
        $table = $wpdb->prefix . $table_name;

        $data = [
            'quote_number'     => sanitize_text_field($_POST['quote_number']),
            'quotation_date'   => sanitize_text_field($_POST['quotation_date']),
            'shipping_type'    => $type,  
            'submitted_at'     => current_time('mysql'),
        ];
        
        // For Air Freight (additional fields for Air Freight form)
        if ($type === 'Air') {
            $data['dimensions']     = sanitize_text_field($_POST['dimensions']);
            $data['dimension_unit'] = sanitize_text_field($_POST['dimension_unit']);
            $data['cargo_weight']   = sanitize_text_field($_POST['cargo_weight']);
            $data['weight_unit']    = sanitize_text_field($_POST['weight_unit']);
            $data['nz_duties']      = sanitize_text_field($_POST['nz_duties']);
            $data['nz_gst']         = sanitize_text_field($_POST['nz_gst']);
            $data['transit_time']   = sanitize_text_field($_POST['transit_time']);
            $data['cargo_details']  = sanitize_textarea_field($_POST['cargo_details']);
            $data['shipping_total'] = sanitize_text_field($_POST['shipping_total']);
            $data['customer_name']  = sanitize_text_field($_POST['customer_name']);
            $data['customer_email'] = sanitize_email($_POST['customer_email']);
        }
        
        // For Ocean Freight (additional fields for Ocean Freight form)
        if ($type === 'Ocean') {
            $data['customer_name']  = sanitize_text_field($_POST['customer_name']);
            $data['customer_email'] = sanitize_email($_POST['customer_email']);
            $data['subject']        = sanitize_text_field($_POST['subject']); 
            $data['shipping_total'] = sanitize_text_field($_POST['shipping_total']);
        }

        // Insert into the respective table
        $wpdb->insert($table, $data);

        // Email notifications
        $admin_email = get_option('admin_email');
        $customer_email = $data['customer_email'];

        wp_mail($admin_email, "New $type Freight Quote", print_r($data, true));
        wp_mail($customer_email, "Your $type Freight Quote", "Hi {$data['customer_name']},\n\nThanks for submitting your quote.\n\nRegards,\nAdmin", ['From: ' . $admin_email]);

        echo "<div class='updated'><p>$type Freight quote submitted successfully!</p></div>";
    }
}

// Page callbacks
function caf_display_air_form() { caf_render_form('Air'); }
function caf_display_ocean_form() { caf_render_form('Ocean'); }
function caf_display_landing() {
    echo "<div class='wrap'><h1>Custom Admin Form</h1><p>Select a form from the submenu to begin.</p></div>";
}

// View all entries
function caf_display_entries() {
    global $wpdb;
    $air = $wpdb->prefix . 'caf_air_entries';
    $ocean = $wpdb->prefix . 'caf_ocean_entries';

    $entries = array_merge(
        $wpdb->get_results("SELECT *, 'Air' as source FROM $air"),
        $wpdb->get_results("SELECT *, 'Ocean' as source FROM $ocean")
    );

    usort($entries, fn($a, $b) => strtotime($b->submitted_at) - strtotime($a->submitted_at));

    echo "<div class='wrap'><h2>Submitted Quotes</h2><table class='widefat'><thead><tr>
        <th>Quote No</th><th>Customer</th><th>Email</th><th>Type</th><th>Date</th><th>Action</th>
    </tr></thead><tbody>";

    foreach ($entries as $row) {
        echo "<tr>
            <td>" . esc_html($row->quote_number) . "</td>
            <td>" . esc_html($row->customer_name) . "</td>
            <td>" . esc_html($row->customer_email) . "</td>
            <td>" . esc_html($row->source) . "</td>
            <td>" . esc_html($row->submitted_at) . "</td>
            <td><button>click</button></td>
        </tr>";
    }

    echo "</tbody></table></div>";
}
