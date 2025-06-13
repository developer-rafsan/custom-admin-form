<?php

/*
 * Plugin Name:       Custom Admin Form
 * Plugin URI:        
 * Description:       A WordPress admin-only plugin to manage air and ocean freight quote submissions, save entries to the database, and send email confirmations to both admin and customers.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Tested up to:      6.5
 * Requires PHP:      7.2
 * Author:            PixelCode
 * Author URI:        https://portfolio-client-y9gw.onrender.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pixelcode
 */



// Enqueue the custom admin CSS file
add_action('admin_enqueue_scripts', 'pixelcode_enqueue_custom_css');
function pixelcode_enqueue_custom_css() {
    wp_enqueue_style('pixelcode-admin-form-css', plugin_dir_url(__FILE__) . 'pixelcode-admin-form.css');
}

// Admin Menu
add_action('admin_menu', function () {
    add_menu_page('Custom Form','Custom Form','manage_options','custom-form-landing','pixelcode_display_landing','dashicons-feedback',6);
    add_submenu_page('custom-form-landing', 'Air Freight Form', 'Air Freight', 'manage_options', 'air-freight-form', 'pixelcode_display_air_form');
    add_submenu_page('custom-form-landing', 'Ocean Freight Form', 'Ocean Freight', 'manage_options', 'ocean-freight-form', 'pixelcode_display_ocean_form');
    add_submenu_page('custom-form-landing', 'Form Entries', 'View Entries', 'manage_options', 'form-entries', 'pixelcode_display_entries');
    add_submenu_page('custom-form-landing', 'Recreate Tables (Dev)', 'Recreate Tables', 'manage_options', 'pixelcode-recreate', 'pixelcode_create_all_tables');
}, 9);

// Remove the first auto-created submenu
add_action('admin_menu', function () {
    remove_submenu_page('custom-form-landing', 'custom-form-landing');
}, 11);




// Activation Hook
register_activation_hook(__FILE__, 'pixelcode_create_all_tables');

function pixelcode_create_all_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $air_table = $wpdb->prefix . 'pixelcode_air_entries';
    $ocean_table = $wpdb->prefix . 'pixelcode_ocean_entries';

    $air_fields = "
        id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        quote_number VARCHAR(100),
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        type VARCHAR(100),
        shipping_type VARCHAR(100),
        quotation_date DATE,
        dimensions VARCHAR(100),
        cargo_weight VARCHAR(100),
        nz_duties VARCHAR(100),
        nz_gst VARCHAR(100),
        transit_time VARCHAR(100),
        cargo_details TEXT,
        shipping_total VARCHAR(100),
        customer_name VARCHAR(100),
        customer_email VARCHAR(100),
        PRIMARY KEY (id)
    ";

    $ocean_fields = "
        id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        quote_number VARCHAR(100) NOT NULL,
        quotation_date DATE NOT NULL,
        departure_city VARCHAR(100) NOT NULL,
        type VARCHAR(100),
        destination_city VARCHAR(100) NOT NULL,
        cargo_type VARCHAR(50) NOT NULL,
        vehicle_type VARCHAR(50) NOT NULL,
        make VARCHAR(100) NOT NULL,
        model VARCHAR(100) NOT NULL,
        year VARCHAR(10) NOT NULL,
        shipping_method VARCHAR(50) NOT NULL,
        ship_departs VARCHAR(50),
        transit_time VARCHAR(100),
        cargo_volume VARCHAR(50) NOT NULL,
        exchange_rate DECIMAL(10,4),
        cost_breakdown VARCHAR(100),


        us_origin_charges_usd VARCHAR(100),
        us_origin_charges_nzd VARCHAR(100),
        

        ocean_freight_usd VARCHAR(100),
        ocean_freight_nzd VARCHAR(100),
        

        inland_transport_usd VARCHAR(100),
        inland_transport_nzd VARCHAR(100),
        

        crating_services_usd VARCHAR(100),
        crating_services_nzd VARCHAR(100),
        

        delivery_to_port_usd VARCHAR(100),
        delivery_to_port_nzd VARCHAR(100),
        
        nz_destination_charges DECIMAL(10,2),
        nz_duties_charge DECIMAL(10,2),
        gst_charge DECIMAL(10,2),
        other_charges DECIMAL(10,2),

        notes TEXT,
        total_nzd DECIMAL(10,2) NOT NULL,
        customer_email VARCHAR(100) NOT NULL,
        customer_name VARCHAR(100) NOT NULL,

        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        PRIMARY KEY (id)
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta("CREATE TABLE $air_table ($air_fields) $charset_collate;");
    dbDelta("CREATE TABLE $ocean_table ($ocean_fields) $charset_collate;");

    // ✅ Create 'view-page' if not exists
    $page_slug = 'quote-page';
    $existing_page = get_page_by_path($page_slug);

    if (!$existing_page) {
        wp_insert_post([
            'post_title'   => 'Quote View',
            'post_name'    => $page_slug,
            'post_content' => '[custom_section]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }

    if (!defined('DOING_AJAX') && !defined('WP_CLI')) {
        echo "<div class='notice notice-success is-dismissible'><p>Tables created successfully.</p></div>";
    }
}

function pixelcode_render_form($type = 'Air') {
    $action_slug = ($type === 'Ocean') ? 'ocean-freight-form' : 'air-freight-form';
    $table_name = ($type === 'Ocean') ? 'pixelcode_ocean_entries' : 'pixelcode_air_entries';
    ?>
<div class="form_wrap_pixelcode">
    <h2><?php echo $type; ?> Freight Quote Form</h2>
    <form method="post">
        <style>
        .pixelcode-field {
            margin-bottom: 15px;
        }

        .pixelcode-field input,
        .pixelcode-field select,
        .pixelcode-field textarea {
            width: 100%;
            padding: 8px;
        }

        .pixelcode-row {
            display: flex;
            gap: 20px;
        }

        .pixelcode-col {
            flex: 1;
        }
        </style>

        <?php if ($type === 'Air') : ?>
        <!-------------------------------------------------- Air Freight Fields -------------------------------------------------------------->
        <table class="form-table">
            <tr>
                <th><label for="quote_number">Quote Number *</label></th>
                <td><input style="text-transform: uppercase;" type="text" name="quote_number" required></td>

                <th><label for="shipping_type">Shipping Type *</label></th>
                <td>
                    <select name="shipping_type" required>
                        <option value="">Select Shipping Type</option>
                        <option value="USPS Economy">USPS Economy</option>
                        <option value="DHL Economy">DHL Economy</option>
                        <option value="FedEx Priority">FedEx Priority</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="quotation_date">Quotation Date *</label></th>
                <td><input type="date" name="quotation_date" required></td>
                <td colspan="2"></td>
            </tr>

            <tr>
                <th><label for="dimensions">Dimensions *</label></th>
                <td>
                    <input type="text" name="dimensions" placeholder="30x30x30" required />
                    <select name="dimension_unit">
                        <option value="cm">cm</option>
                        <option value="in">in</option>
                    </select>
                </td>

                <th><label for="cargo_weight">Cargo Weight *</label></th>
                <td>
                    <input type="text" name="cargo_weight" required />
                    <select name="weight_unit">
                        <option value="lbs">lbs</option>
                        <option value="kgs">kgs</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="nz_duties">NZ Duties</label></th>
                <td><input type="text" name="nz_duties" /></td>
                <th><label for="nz_gst">NZ GST</label></th>
                <td><input type="text" name="nz_gst" /></td>
            </tr>

            <tr>
                <th><label for="transit_time">Transit Time *</label></th>
                <td><input type="time" name="transit_time" value="00:00" required /></td>
                <td colspan="2"></td>
            </tr>

            <tr>
                <th><label for="cargo_details">Cargo Details *</label></th>
                <td colspan="3">
                    <textarea name="cargo_details" rows="3" style="width:100%;" required></textarea>
                </td>
            </tr>

            <tr>
                <th><label for="shipping_total">Total Shipping Cost (NZD) *</label></th>
                <td><input type="text" name="shipping_total" required /></td>
                <td colspan="2"></td>
            </tr>

            <tr>
                <th><label for="customer_name">Customer Name *</label></th>
                <td><input type="text" name="customer_name" required /></td>

                <th><label for="customer_email">Customer Email *</label></th>
                <td><input type="email" name="customer_email" required /></td>
            </tr>
        </table>

        <!-------------------------------------------------- Air Freight Fields -------------------------------------------------------------->
        <?php else : ?>
        <!-------------------------------------------------- Ocean Freight Fields -------------------------------------------------------------->
        <table class="form-table">
            <tr>
                <th><label for="quotation_date">Quote Date *</label></th>
                <td><input type="date" name="quotation_date" required /></td>
                <th><label for="quote_number">Quote Number *</label></th>
                <td><input type="text" name="quote_number" required /></td>
            </tr>
            <tr>
                <th><label for="departure_city">Departure City *</label></th>
                <td><input type="text" name="departure_city" required /></td>
                <th><label for="destination_city">Destination City *</label></th>
                <td><input type="text" name="destination_city" required /></td>
            </tr>
            <tr>
                <th><label for="cargo_type">Cargo Type *</label></th>
                <td>
                    <select name="cargo_type" required>
                        <option value="">Select</option>
                        <option value="Box">Box</option>
                        <option value="Crate">Crate</option>
                        <option value="Pallet">Pallet</option>
                    </select>
                </td>
                <th><label for="vehicle_type">Vehicle Type *</label></th>
                <td>
                    <select name="vehicle_type" required>
                        <option value="">Select</option>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Car">Car</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="make">Make *</label></th>
                <td><input type="text" name="make" required /></td>
            </tr>
            <tr>
                <th><label for="model">Model *</label></th>
                <td><input type="text" name="model" required /></td>
                <th><label for="year">Year *</label></th>
                <td><input type="text" name="year" required /></td>
            </tr>
            <tr>
                <th><label for="shipping_method">Shipping Method *</label></th>
                <td><select name="shipping_method" required>
                        <option value="">Select</option>
                        <option value="lcl">LCL</option>
                        <option value="20ft">20ft</option>
                        <option value="40ft">40ft</option>
                    </select></td>
                <th><label for="ship_departs">Ship Departs</label></th>
                <td><select name="ship_departs">
                        <option value="">Select</option>
                        <option value="weekly">Weekly</option>
                        <option value="Bi-Monthly">Bi-Monthly</option>
                        <option value="Monthly">Monthly</option>
                    </select></td>
            </tr>
            <tr>
                <th><label for="transit_time">Transit Time *</label></th>
                <td><input type="time" name="transit_time" value="00:00" required /></td>
                <th><label for="cargo_volume">Cargo Volume (CBM) *</label></th>
                <td><input type="text" name="cargo_volume" required /></td>
            </tr>
            <tr>
                <th><label for="exchange_rate">Exchange Rate</label></th>
                <td><input type="text" name="exchange_rate" /></td>
                <td>
                    <select name="carence" required>
                        <option value="">Select</option>
                        <option value="USD">USD</option>
                        <option value="NZD">NZD</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="cost_breakdown">Cost Breakdown *</label></th>
                <td>
                    <select name="cost_breakdown" required>
                        <option value="">Select</option>
                        <option value="US Origin Charges">US Origin Charges</option>
                        <option value="Ocean Freight">Ocean Freight</option>
                        <option value="Inland Transport">Inland Transport</option>
                        <option value="Crating Services">Crating Services</option>
                        <option value="Delivery to Port">Delivery to Port</option>
                        <option value="NZ Destination Charges">NZ Destination Charges</option>
                        <option value="NZ Duties Charge">NZ Duties Charge</option>
                        <option value="GST Charge">GST Charge</option>
                    </select>
                </td>
            </tr>

            <?php
                $charges = [
                    "US Origin Charges", "Ocean Freight", "Inland Transport",
                    "Crating Services", "Delivery to Port"
                ];
                foreach ($charges as $label) {
                    $slug = strtolower(str_replace(' ', '_', $label));
                    echo "<tr>
                        <th><input type='checkbox' name='{$slug}_checked' /> $label</th>
                        <td><input type='text' name='{$slug}_usd' placeholder='USD Price' /></td>
                        <td colspan='2'><input type='text' name='{$slug}_nzd' placeholder='NZD Price' /></td>
                    </tr>";
                }
                ?>
            <tr>
                <th><label for="nz_destination_charges">NZ Destination Charges</label></th>
                <td><input type="text" name="nz_destination_charges" /></td>
                <th><label for="nz_duties_charge">NZ Duties Charge</label></th>
                <td><input type="text" name="nz_duties_charge" /></td>
            </tr>
            <tr>
                <th><label for="gst_charge">GST Charge</label></th>
                <td><input type="text" name="gst_charge" /></td>
                <th><label for="other_charges">Other Charges</label></th>
                <td><input type="text" name="other_charges" /></td>
            </tr>

            <tr>
                <th><label for="notes">Notes</label></th>
                <td colspan="3"><textarea name="notes" rows="3" style="width:100%"></textarea></td>
            </tr>

            <tr>
                <th><label for="total_nzd">Total NZD *</label></th>
                <td><input type="text" name="total_nzd" required /></td>
                <th><label for="customer_email">Customer Email *</label></th>
                <td><input type="email" name="customer_email" required /></td>
            </tr>
            <tr>
                <th><label for="customer_name">Customer Name *</label></th>
                <td colspan="3"><input type="text" name="customer_name" required style="width:100%" /></td>
            </tr>
        </table>
        <!-------------------------------------------------- Ocean Freight Fields -------------------------------------------------------------->
        <?php endif; ?>

        <p><input type="submit" name="pixelcode_submit_<?php echo strtolower($type); ?>" class="button button-primary"
                value="Submit Quote"></p>
    </form>
</div>
<?php

    if (isset($_POST['pixelcode_submit_' . strtolower($type)])) {
        global $wpdb;
        $table = $wpdb->prefix . $table_name;

    $data = [
        'quote_number' => sanitize_text_field($_POST['quote_number']),
        'submitted_at' => current_time('mysql'),
        'type'         => $type,
    ];

// Handling for "Air" freight
    if ($type === 'Air') {
        $data['shipping_type']  = sanitize_text_field($_POST['shipping_type']);
        $data['quotation_date'] = sanitize_text_field($_POST['quotation_date']);
        $data['dimensions']     = sanitize_text_field($_POST['dimensions']) . ' ' . sanitize_text_field($_POST['dimension_unit']);
        $data['cargo_weight']   = sanitize_text_field($_POST['cargo_weight']) . ' ' . sanitize_text_field($_POST['weight_unit']);
        $data['nz_duties']      = sanitize_text_field($_POST['nz_duties']);
        $data['nz_gst']         = sanitize_text_field($_POST['nz_gst']);
        $data['transit_time']   = sanitize_text_field($_POST['transit_time']);
        $data['cargo_details']  = sanitize_textarea_field($_POST['cargo_details']);
        $data['shipping_total'] = sanitize_text_field($_POST['shipping_total']);
        $data['customer_name']  = sanitize_text_field($_POST['customer_name']);
        $data['customer_email'] = sanitize_email($_POST['customer_email']);
    } else {
        // Handling for "Ocean" freight
        $data['customer_name']  = sanitize_text_field($_POST['customer_name']);
        $data['customer_email'] = sanitize_email($_POST['customer_email']);
        $data['total_nzd'] = sanitize_text_field($_POST['total_nzd']);
        $data['quotation_date'] = sanitize_text_field($_POST['quotation_date']);
        
        // Additional fields for Ocean Freight (example fields)
        $data['departure_city'] = sanitize_text_field($_POST['departure_city']);
        $data['destination_city'] = sanitize_text_field($_POST['destination_city']);
        $data['cargo_type'] = sanitize_text_field($_POST['cargo_type']);
        $data['vehicle_type'] = sanitize_text_field($_POST['vehicle_type']);
        $data['make'] = sanitize_text_field($_POST['make']);
        $data['model'] = sanitize_text_field($_POST['model']);
        $data['year'] = sanitize_text_field($_POST['year']);
        $data['shipping_method'] = sanitize_text_field($_POST['shipping_method']);
        $data['ship_departs'] = sanitize_text_field($_POST['ship_departs']);
        $data['transit_time']   = sanitize_text_field($_POST['transit_time']);
        $data['cargo_volume'] = sanitize_text_field($_POST['cargo_volume']);
        $data['exchange_rate'] = sanitize_text_field($_POST['exchange_rate']) . ' ' . sanitize_text_field($_POST['carence']);
        $data['cost_breakdown'] = sanitize_text_field($_POST['cost_breakdown']);
        
        // Handling checkbox fields (for charges) for Ocean Freight
        $charges = [
            "us_origin_charges", "ocean_freight", "inland_transport",
            "crating_services", "delivery_to_port"
        ];
        
        foreach ($charges as $charge) {
            // Sanitize checkboxes and USD/NZD values for each charge
            $data["{$charge}_usd"] = isset($_POST["{$charge}_usd"]) ? sanitize_text_field($_POST["{$charge}_usd"]) : 'X';
            $data["{$charge}_nzd"] = isset($_POST["{$charge}_nzd"]) ? sanitize_text_field($_POST["{$charge}_nzd"]) : 'X';
        }
        
        // Additional charges fields
        $data['nz_destination_charges'] = isset($_POST['nz_destination_charges']) ? sanitize_text_field($_POST['nz_destination_charges']) : 'X';
        $data['nz_duties_charge'] = isset($_POST['nz_duties_charge']) ? sanitize_text_field($_POST['nz_duties_charge']) : 'X';
        $data['gst_charge'] = isset($_POST['gst_charge']) ? sanitize_text_field($_POST['gst_charge']) : 'X';
        $data['other_charges'] = isset($_POST['other_charges']) ? sanitize_text_field($_POST['other_charges']) : 'X';
        $data['notes'] = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : 'X';
        $data['total_nzd'] = sanitize_text_field($_POST['total_nzd']);
    }

    // Try inserting into database
    $result = $wpdb->insert($table, $data);

if ($result) {
        $insert_id = $wpdb->insert_id;
        $view_link = site_url("/quote-page?quote_id={$data['quote_number']}&id=$insert_id&type=$type");

        // Send email to admin and customer
        $admin_email = get_option('admin_email');
        $customer_email = $data['customer_email'];
        $subject = "$type Freight Quote - {$data['quote_number']}";

        $customer_message = "<html><body><h2>Hello {$data['customer_name']},</h2>
        <p>Thank you for your quote request. View your quote below:</p>
        <p><a href='$view_link' style='background:#0073aa;color:white;padding:10px 15px;border-radius:5px;text-decoration:none;'>View Quote</a></p>
        <p>Kind regards,<br>The Admin Team</p></body></html>";

        wp_mail($admin_email, "New $type Freight Quote", print_r($data, true));
        wp_mail($customer_email, $subject, $customer_message, ['Content-Type: text/html; charset=UTF-8', 'From: ' . $admin_email]);

        echo "<div class='updated'><p>$type Freight quote submitted successfully!</p></div>";
    } else {
        echo "<div class='error'><p>Failed to insert quote into database. Error: " . $wpdb->last_error . "</p></div>";
    }
}
}


function pixelcode_display_air_form() { pixelcode_render_form('Air'); }
function pixelcode_display_ocean_form() { pixelcode_render_form('Ocean'); }
function pixelcode_display_landing() {
    echo "<div class='wrap'><h1>Custom Admin Form</h1><p>Select a form from the submenu to begin.</p></div>";
}

function pixelcode_display_entries() {
    global $wpdb;
    $air = $wpdb->prefix . 'pixelcode_air_entries';
    $ocean = $wpdb->prefix . 'pixelcode_ocean_entries';
    $entries = array_merge(
        $wpdb->get_results("SELECT *, 'Air' as source FROM $air"),
        $wpdb->get_results("SELECT *, 'Ocean' as source FROM $ocean")
    );
    usort($entries, fn($a, $b) => strtotime($b->submitted_at) - strtotime($a->submitted_at));
    echo "<div class='wrap'><h2>Submitted Quotes</h2><table class='widefat'><thead><tr><th>Quote No</th><th>Customer</th><th>Email</th><th>Type</th><th>Date</th><th>Action</th></tr></thead><tbody>";
    foreach ($entries as $row) {
        echo "<tr><td>" . esc_html($row->quote_number) . "</td><td>" . esc_html($row->customer_name) . "</td><td>" . esc_html($row->customer_email) . "</td><td>" . esc_html($row->source) . "</td><td>" . esc_html($row->submitted_at) . "</td><td><a href='" . site_url("/quote-page?quote_id={$row->quote_number}&id={$row->id}&type={$row->source}") . "' class='button'>View</a></td></tr>";
    }
    echo "</tbody></table></div>";
}

function my_custom_shortcode() {
    ob_start();
    if (isset($_GET['quote_id'])) {
        global $wpdb;
        $id = intval($_GET['id']);
        $type = sanitize_text_field($_GET['type']);
        $quote_id = sanitize_text_field($_GET['quote_id']);

        $table = ($type === 'Air') ? $wpdb->prefix . 'pixelcode_air_entries' : (($type === 'Ocean') ? $wpdb->prefix . 'pixelcode_ocean_entries' : '');
        if (!$table) return 'Invalid quote type.';

        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($quote) {
            echo "<table border='1' cellpadding='8'>";
            foreach ($quote as $key => $value) {
                echo "<tr><th>" . esc_html(ucwords(str_replace('_', ' ', $key))) . "</th><td>" . esc_html($value) . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "No data found for this quote.";
        }
    }
    return ob_get_clean();
}
add_shortcode('custom_section', 'my_custom_shortcode');