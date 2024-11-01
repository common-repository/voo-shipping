<?php

/**
 * Plugin Name: SIDEUP EG
 * Description: WooCommerce integration for SIDEUP - EGYPT
 * Author: SIDEUP
 * Author URI: https://eg.sideup.co/
 * Version: 2.0.1
 * Requires at least: 5.0
 * Tested up to: 6.5
 * WC requires at least: 2.6
 * WC tested up to: 4.4.1
 * Text Domain: sideup-woocommerce
 * Domain Path: /languages
 *
 */

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$citiesCode = array(
    "EG-01" => "Cairo",
    "EG-02" => "Alexandria",
    "EG-03" => "Sahel",
    "EG-04" => "Behira",
    "EG-05" => "Dakahlia",
    "EG-06" => "El Kalioubia",
    "EG-07" => "Gharbia",
    "EG-08" => "Kafr Alsheikh",
    "EG-09" => "Monufia",
    "EG-10" => "Sharqia",
    "EG-11" => "Isamilia",
    "EG-12" => "Suez",
    "EG-13" => "Port Said",
    "EG-14" => "Damietta",
    "EG-15" => "Fayoum",
    "EG-16" => "Bani Suif",
    "EG-17" => "Assuit",
    "EG-18" => "Sohag",
    "EG-19" => "Menya",
    "EG-20" => "Qena",
    "EG-21" => "Aswan",
    "EG-22" => "Luxor",
);

$specific_zones = [
    "Cairo" => [],
    "Alexandria" => "Alexandria",
    "Sahel" => "North Coast",
    "Behira" => "Behira",
    "Dakahlia" => "Mansoura",
    "El Kalioubia" => "Quliob",
    "Gharbia" => [
        "Tanta",
        "Mahla"
    ],
    "Kafr Alsheikh" => "Kafr el Sheikh",
    "Monufia" => "Monofia",
    "Sharqia" => "El Sharkia",
    "Isamilia" => "Ismailya",
    "Suez" => "suez",
    "Port Said" => "Port said",
    "Damietta" => "Dommyat",
    "Fayoum" => "Fayoum",
    "Bani Suif" => "Benisuif",
    "Assuit" => "Asyut",
    "Sohag" => "Souhag",
    "Menya" => "Minya",
    "Qena" => "Quena",
    "Aswan" => "Aswan",
    "Luxor" => "Luxor",
];
// add notice to config plugin
add_action('admin_notices', 'sideup_woocommerce_notice');
function sideup_woocommerce_notice()
{
    //check if woocommerce installed and activated
    if (!class_exists('WooCommerce')) {
        echo
        '<div class="error notice-warning text-bold">
        <p>
        <img src="' . esc_url(plugins_url('assets/images/sideup.png', __FILE__)) . '" alt="SIDEUP" style="height:13px; width:25px;">
        <strong>' . sprintf(esc_html__('SIDEUP requires WooCommerce to be installed and active. You can download %s here.'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong>
        </p>
        </div>';
        return;
    }
}

add_action('admin_enqueue_scripts', 'sideup_custom_admin_css');
function sideup_custom_admin_css() {
    echo '<style>
        /* Adjust the position of the SVG icon */
        .toplevel_page_sideup-woocommerce .wp-menu-image img {
            width: 20px; /* Adjust size */
            height: auto; /* Maintain aspect ratio */
            vertical-align: middle; /* Align with the text */
            padding: 0; /* Remove padding */
        }

        /* Additional styles if necessary */
        .toplevel_page_sideup-woocommerce .wp-menu-name {
            line-height: 20px; /* Ensure text is centered with icon */
        }
    </style>';
}


add_action('admin_menu', 'sideup_setup_menu');
function sideup_setup_menu()
{
    //check if woocommerce is activated
    if (!class_exists('WooCommerce')) {
        return;
    }

    add_menu_page(
        'SIDEUP',
        'SIDEUP',
        'manage_options',
        'sideup-woocommerce',
        'sideup_setting',
        plugin_dir_url(__FILE__) . 'assets/images/logo.svg', // Path to the SVG icon
        56 // Position below Products
    );

    // link to plugin settings
    add_submenu_page(
        'sideup-woocommerce',
        'Setting',
        'Setting',
        'manage_options',
        'sideup-woocommerce',
        'sideup_setting'
    );

    // link to woocommerce orders
    add_submenu_page(
        'sideup-woocommerce',
        'Send Orders',
        'Send Orders',
        'manage_options',
        'sideup-woocommerce-orders',
        'sideup_orders'
    );

    // link to sideup shipments
    add_submenu_page(
        'sideup-woocommerce',
        'Track SIDEUP Orders',
        'Track SIDEUP Orders',
        'manage_options',
        'sideup-woocommerce-shipments',
        'sideup_dashboard'
    );
}

function sideup_setting()
{
    $redirect_url = admin_url('admin.php?') . 'page=wc-settings&tab=shipping&section=sideup';
    wp_redirect($redirect_url);
}

function sideup_orders()
{
    $redirect_url = admin_url('edit.php?') . 'post_type=shop_order&paged=1';
    wp_redirect($redirect_url);
}

function sideup_dashboard()
{
    $redirect_url = 'https://portal.eg.sideup.co/merchants/shipments';
    wp_redirect($redirect_url);
}

add_action('load-edit.php', 'sideup_wco_load', 20);

function sideup_wco_load()
{
    $orders = wc_get_orders(array('return' => 'ids'));
    $keys = [];
    foreach ($orders as $order) {
        $key = get_post($order)->post_password;
        $keys['keys'][] = $key;
    }

    $url = 'https://portal.eg.sideup.co/api/getWooCommerceData/';
    $APIKey = get_option('woocommerce_sideup_settings')['APIKey'];
    $result = wp_remote_get(
        $url,
        array(
            'timeout' => 30,
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'authorization' => $APIKey,
                'X-Requested-By' => 'WooCommerce',
            ),
            'body' => json_encode($keys),
        )
    );


    if (!is_wp_error($result)) {
        foreach ($orders as $index => $order) {
            $tracking_number = json_decode($result['body'])->data[$index]->code;
            $tracking_status = json_decode($result['body'])->data[$index]->status;
            $payment_link = json_decode($result['body'])->data[$index]->payment_link;
            $cash_collection = json_decode($result['body'])->data[$index]->cash_collection;
            $payment_status = json_decode($result['body'])->data[$index]->payment_status;

            if ($tracking_status && $tracking_status != 'cancelled') {
                delete_post_meta($order, 'sideup_status');
                delete_post_meta($order, 'sideup_tracking_number');
                delete_post_meta($order, 'sideup_payment_link');
                delete_post_meta($order, 'sideup_cash_collection');
                delete_post_meta($order, 'sideup_payment_status');
                add_post_meta($order, 'sideup_status', $tracking_status);
                add_post_meta($order, 'sideup_tracking_number', $tracking_number);
                add_post_meta($order, 'sideup_cash_collection', $cash_collection);
                if ($payment_link) {
                    add_post_meta($order, 'sideup_payment_link', $payment_link);
                }
                if ($payment_status) {
                    add_post_meta($order, 'sideup_payment_status', $payment_status);
                }
            } else {
                delete_post_meta($order, 'sideup_status');
                delete_post_meta($order, 'sideup_tracking_number');
                delete_post_meta($order, 'sideup_payment_link');
                delete_post_meta($order, 'sideup_cash_collection');
                delete_post_meta($order, 'sideup_payment_status');
            }
        }
    } else {
        die($result->get_error_message());
    }
    // $test = ;
    // die(json_encode($test));
    $screen = get_current_screen();
    if (!isset($screen->post_type) || 'shop_order' != $screen->post_type) {
        return;
    }

    add_filter("manage_{$screen->id}_columns", 'sideup_wco_add_columns');
    add_action("manage_{$screen->post_type}_posts_custom_column", 'sideup_wco_column_cb_data', 10, 2);
}

function sideup_wco_add_columns($columns)
{
    $order_total = $columns['order_total'];
    $order_date = $columns['order_date'];
    unset($columns['order_total']);
    unset($columns['order_date']);

    $columns["sideup_status"] = __("SIDEUP Status", "themeprefix");
    $columns["sideup_tracking_number"] = __("SIDEUP Tracking Number", "themeprefix");
    $columns['order_date'] = $order_date;
    $columns["order_price"] = __("Order Price", "themeprefix");
    // $columns['order_total'] = $order_total;
    $columns['shipping_request'] = __("Ship With SIDEUP", "themeprefix");
    $columns['payment_column'] = __("Payment Link", "themeprefix");
    $columns['order_total2'] = __("Total Price", "themeprefix");

    return $columns;
}

function sideup_wco_column_cb_data($colname, $orderId)
{
    $key = get_post_meta($orderId, '_order_key', true);
    $url = 'https://portal.eg.sideup.co/api/getWooCommerceData/' . $key;
    $sideup_status = get_post_meta($orderId, 'sideup_status', true);
    $trackingNumber = get_post_meta($orderId, 'sideup_tracking_number', true);
    $isTrashed = get_post_meta($orderId, '_wp_trash_meta_time', true);
    $APIKey = get_option('woocommerce_sideup_settings')['APIKey'];

    $custom_status = ['To be picked', 'To be delivered', 'Delivered', 'Returned'];
    if ($colname == 'order_total2') {
        $order_total = get_post_meta($orderId, 'sideup_cash_collection', true);
        if (!empty($order_total)) {
            echo $order_total;
        } else {
            echo "---";
        }
    }
    if ($colname == 'order_price') {
        $order_price = get_post_meta($orderId, '_order_total', true);
        if (!empty($order_price)) {
            echo $order_price;
        } else {
            echo "---";
        }
    }
    if ($colname == 'shipping_request' && $sideup_status == 'to_be_picked') {
        $bearerToken = get_option('woocommerce_sideup_settings')['APIKey'];
        echo ("<a href='#'><span class='btn btn-danger cancel-button' data-key='$key' bearerToken='$bearerToken'>Cancel</span></a>");
    }
    if (!$isTrashed && $colname == "shipping_request" && (empty($sideup_status) || $sideup_status == 'cancelled') && !in_array($sideup_status, $custom_status)) {
        $city = get_post_meta($orderId, '_shipping_state', true);
        $total = get_post_meta($orderId, '_order_total', true);
        $key = get_post_meta($orderId, '_order_key', true);
        $shipping_address = get_post_meta($orderId, '_billing_state', true)
            . ' - ' . get_post_meta($orderId, '_billing_city', true)
            . ' - ' . get_post_meta($orderId, '_billing_address_1', true);
        $mobile = get_post_meta($orderId, '_billing_phone', true);
        $customer_first_name = get_post_meta($orderId, '_billing_first_name', true);
        $customer_last_name = get_post_meta($orderId, '_billing_last_name', true);
        $bearerToken = get_option('woocommerce_sideup_settings')['APIKey'];

        $args = array('post__in' => [$orderId]);
        $order = wc_get_orders($args);
        $items = $order[0]->get_items();
        $desc = 'Products: ';
        foreach ($items as $item_id => $item_data) {
            $product = $item_data->get_product();
            $product_name = $product->get_name();
            $item_quantity = $item_data->get_quantity();
            $desc .= $product_name . " ( " . $item_quantity . " ) ";
        }

        echo ("<a href='#'><span class='btn btn-success shipping-button' data-key='$key' bearerToken='$bearerToken' data-city='$city' data-total='$total'>Ship</span></a>
                <input type='hidden' bearerToken='$bearerToken' id='$key' data-city='$city' data-description=" . '" ' . $desc . ' "' . " data-total='$total' 
                    data-address='$shipping_address' data-mobile='$mobile' data-name='$customer_first_name $customer_last_name' data-wordpress-id='$orderId'
                    data-backup-phone=''>
                <input type='hidden' class='fedex-total-fees' value='0'>
                <input type='hidden' class='fetchr-total-fees' value='0'>
                <input type='hidden' class='aramex-total-fees' value='0'>
                <input type='hidden' class='mylerz-total-fees' value='0'>
                <input type='hidden' class='payment-way' value='4'");
    }

    if ($colname == 'sideup_status') {
        $sideup_status = get_post_meta($orderId, 'sideup_status', true);
        if (!empty($sideup_status)) {
            echo $sideup_status;
        } else {
            echo "---";
        }
    }

    if ($colname == 'payment_column') {
        $sideup_payment = get_post_meta($orderId, 'sideup_payment_link', true);
        $sideup_payment_status = get_post_meta($orderId, 'sideup_payment_status', true);
        if (!empty($sideup_payment)) {
            echo "$sideup_payment <br> <span class='badge badge-warning'>$sideup_payment_status</span>";
        } else {
            echo "---";
        }
    }

    if ($colname == 'sideup_tracking_number') {
        $trackingNumber = get_post_meta($orderId, 'sideup_tracking_number', true);
        $sideup_status = get_post_meta($orderId, 'sideup_status', true);
        if (!empty($trackingNumber) && $sideup_status != 'Cancelled') {
            echo $trackingNumber;
        } else {
            echo "---";
        }
    }
}

/**
 * Enqueue a script in the WordPress admin on edit.php.
 *
 * @param int $hook Hook suffix for the current admin page.
 */
function sideup_wpdocs_selectively_enqueue_admin_script($hook)
{
    if ('edit.php' != $hook) {
        return;
    }
    echo ("
            <style>
                .swal2-popup.swal2-modal {
                    width: fit-content;
                }
                .top2 {
                    display: flex;
                    -webkit-box-orient: vertical;
                    -webkit-box-direction: normal;
                    -ms-flex-direction: column;
                    flex-direction: column;
                    -webkit-box-align: center;
                    -ms-flex-align: center;
                    align-items: center;
                    -webkit-box-pack: center;
                    -ms-flex-pack: center;
                    justify-content: center;
                    width: 100%;
                    height: 100px;
                }
            </style>");
    wp_enqueue_style('bootstrap_style_sheet', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css', array(), '1.0');
    wp_enqueue_style('sweetalert2_style_sheet', plugin_dir_url(__FILE__) . 'css/sweetalert2.min.css', array(), '1.0');
    wp_enqueue_script('popper_script', plugin_dir_url(__FILE__) . 'js/popper.min.js', array(), '1.0');
    wp_enqueue_script('sweetalert2_script', plugin_dir_url(__FILE__) . 'js/sweetalert2.min.js', array(), '1.0');
    wp_enqueue_script('my_custom_script', plugin_dir_url(__FILE__) . 'js/sideup-1.9.js', array(), '1.0');
}
add_action('admin_enqueue_scripts', 'sideup_wpdocs_selectively_enqueue_admin_script');


add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sideup_plugin_action_links');
function sideup_plugin_action_links($links)
{
    $plugin_links = array(
        '<a href="' . menu_page_url('sideup-woocommerce', false) . '">' . __('Settings') . '</a>',
    );

    return array_merge($plugin_links, $links);
}

add_action('plugins_loaded', 'init_sideup_shipping_class');
function init_sideup_shipping_class()
{
    //check if woocommerce is activated
    if (!class_exists('WooCommerce')) {
        return;
    }

    if (!class_exists('sideup_Shipping_Method')) {
        class sideup_Shipping_Method extends WC_Shipping_Method
        {
            public function __construct()
            {
                $this->id = 'sideup';
                $this->method_title = __('SIDEUP Shipping', 'sideup');
                $this->method_description = __('Custom Shipping Method for sideup', 'sideup');

                $this->init();
                $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
                $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('sideup Shipping', 'sideup');
            }

            function init()
            {
                $this->init_form_fields();
                $this->init_settings();
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            function init_form_fields()
            {
                $this->form_fields = array(
                    'APIKey' => array(
                        'title' => __('APIKey', 'sideup'),
                        'type' => 'text',
                    ),
                );
            }
        }
    }
}

add_action('woocommerce_shipping_init', 'init_sideup_shipping_class');
function add_sideup_shipping_method($methods)
{
    $methods[] = 'sideup_Shipping_Method';
    return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_sideup_shipping_method');

add_filter('woocommerce_states', 'sideup_cities_and_zones');
function sideup_cities_and_zones($states)
{
    $states['EG'] = [
        // Cairo Cities
        "5th Settlement" => "Cairo / 5th Settlement",
        "Abbaseya" => "Cairo / Abbaseya",
        "Downtown" => "Cairo / Downtown",
        "El Manyal" => "Cairo / El Manyal",
        "El Salam City" => "Cairo / El Salam City",
        "Heliopolis" => "Cairo / Heliopolis",
        "Maadi" => "Cairo / Maadi",
        "Madinaty" => "Cairo / Madinaty",
        "Mohandeseen" => "Cairo / Mohandeseen",
        "Mokatam" => "Cairo / Mokatam",
        "Nasr City" => "Cairo / Nasr City",
        "New Cairo" => "Cairo / New Cairo",
        "Rehab" => "Cairo / Rehab",
        "Shubra" => "Cairo / Shubra",
        "Shubra El Kheima" => "Cairo / Shubra El Kheima",
        "Zamalek" => "Cairo / Zamalek",
        "al badrashin" => "Cairo / al badrashin",
        "badr city" => "Cairo / badr city",
        "el obour city" => "Cairo / el obour city",
        "sherouk city" => "Cairo / sherouk city",
        "al matariyyah" => "Cairo / Al Matariyyah",
        "el-marg" => "Cairo / El-Marg",
        "gesr al suez" => "Cairo / Gesr Al Suez",


        // Helwan Cities
        'Helwan' => 'Helwan / Helwan',
        '15 of may city' => 'Helwan / 15 of may city',

        // Giza Cities
        // 'Giza' => "Giza / Giza",
        'Mohandeseen' => "Giza / Mohandeseen",
        'Haram' => "Giza / Haram",
        'Faisal' => "Giza / Faisal",
        'Imbaba' => "Giza / Imbaba",
        'Dokki' => "Giza / Dokki",
        'Elwahat' => "Giza / Elwahat",
        'al ayat' => "Gize / Al Ayat",
        "ausim (giza)" => "Giza / Ausim (giza)",
        "el saff" => "Giza / El Saff",
        "el-hawamdeyya" => "Giza / El-Hawamdeyya",
        "hadayek el ahram" => "Giza / Hadayek El Ahram",


        // 6 of october cities
        '6th of October City' => '6th Of October / 6th of October City',
        'Sheikh Zayed' => '6th Of October / Sheikh Zayed',

        // Alexanderia
        'Alexandria' => 'Alexandria / Alexandria',
        'North Coast' => 'Alexandria/ North Coast',

        // Gharbia Cities
        'Tanta' => 'Gharbia / Tanta',
        'Mahla' => 'Gharbia / Mahla',

        // Sharm El Shekih
        'Sharm el-sheikh' => 'Sharm el-sheikh',

        // Normal Cases
        "El Sharkia" => "El Sharkia",
        "Aswan" => "Aswan",
        "Asyut" => "Asyut",
        "Behira" => "Behira",
        "Benisuif" => "Benisuif",
        "Mansoura" => "Mansoura",
        "Dommyat" => "Dommyat",
        "Fayoum" => "Fayoum",
        "Ismailya" => "Ismailya",
        "Kafr el Sheikh" => "Kafr el Sheikh",
        "Luxor" => "Luxor",
        "Marsa Matrouh" => "Marsa Matrouh",
        "Minya" => "Minya",
        "Monofia" => "Monofia",
        "elwadi elgedid" => "elwadi elgedid",
        "Port said" => "Port said",
        "Quliob" => "Quliob",
        "Quena" => "Quena",
        "el gouna" => "El gouna",
        "Souhag" => "Souhag",
        "suez" => "Suez",
        "Hurghada" => "Hurghada",
        "New Valley" => "New Valley / El Wadi El Gedid",
        "Elwahat" => "Elwahat",
        "oases" => "oases",
        "Sokhna" => "Sokhna / El Ain El Sokhna",
        "sadat city" => "El Sadat city",
        "shebin el koum" => "Shebin El Koum",
        "damanhour" => "Damanhour",

    ];
    return $states;
}

add_action('handle_bulk_actions-edit-shop_order', 'my_bulk_action_handler', 10, 3);

function my_bulk_action_handler($redirect_to, $doaction, $post_ids)
{
    if ($doaction !== 'send_to_sideup') {
        return $redirect_to;
    }

    foreach ($post_ids as $post_id) {

        $orders = wc_get_orders(array('include' => array($post_id), 'return' => 'ids'));
        $keys = [];
        foreach ($orders as $order) {
            $key = get_post($order)->post_password;
            $keys['keys'][] = $key;
        }
        $url = 'https://portal.eg.sideup.co/api/getWooCommerceData/';
        $APIKey = get_option('woocommerce_sideup_settings')['APIKey'];
        $result = wp_remote_post($url, array(
            'timeout' => 30,
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'authorization' => $APIKey,
                'X-Requested-By' => 'WooCommerce',
            ),
            'body' => json_encode($keys),
        ));

        if (!is_wp_error($result)) {
            $response_data = json_decode($result['body']);
            foreach ($orders as $index => $order) {
                $tracking_number = $response_data->data[$index]->code;
                $tracking_status = $response_data->data[$index]->status;
                $payment_link = $response_data->data[$index]->payment_link;
                $cash_collection = $response_data->data[$index]->cash_collection;
                $payment_status = $response_data->data[$index]->payment_status;

                if ($tracking_status && $tracking_status != 'cancelled') {
                    update_post_meta($order, 'sideup_status', $tracking_status);
                    update_post_meta($order, 'sideup_tracking_number', $tracking_number);
                    update_post_meta($order, 'sideup_cash_collection', $cash_collection);
                    if ($payment_link) {
                        update_post_meta($order, 'sideup_payment_link', $payment_link);
                    }
                    if ($payment_status) {
                        update_post_meta($order, 'sideup_payment_status', $payment_status);
                    }
                } else {
                    delete_post_meta($order, 'sideup_status');
                    delete_post_meta($order, 'sideup_tracking_number');
                    delete_post_meta($order, 'sideup_payment_link');
                    delete_post_meta($order, 'sideup_cash_collection');
                    delete_post_meta($order, 'sideup_payment_status');
                }
            }
        } else {
            // Handle error
            $error_message = $result->get_error_message();
            wp_die($error_message);
        }
    }

    // Build the redirect URL with success message
    $redirect_to = add_query_arg('bulk_sent_orders', count($post_ids), $redirect_to);
    return $redirect_to;
}

// Add bulk action for bulk shipping
add_filter('bulk_actions-edit-shop_order', 'register_bulk_shipping_action');
function register_bulk_shipping_action($bulk_actions)
{
    $bulk_actions['bulk_ship_orders'] = __('Bulk Ship Orders', 'sideup-woocommerce');
    return $bulk_actions;
}

// Inject WooCommerce orders data directly into the HTML
add_action('admin_footer', 'inject_orders_data_to_html');
function inject_orders_data_to_html()
{
    // Retrieve all orders with any status
    $orders = wc_get_orders(array(
        'limit' => -1,
        'status' => 'any',
    ));

    $bearerToken = get_option('woocommerce_sideup_settings')['APIKey'];

    $order_data = array();

    foreach ($orders as $order) {
        // Retrieve the post (order) to get the post_password (order key)
        $post = get_post($order->get_id());
        $order_key = $post->post_password;

        // Extract order items (products)
        $items = $order->get_items();
        $item_description = array();

        foreach ($items as $item) {
            // Create a description as product name followed by the quantity
            $item_description[] = $item->get_name() . ' (' . $item->get_quantity() . ')';
        }

        // Combine all items into a single string separated by commas
        $item_description_str = implode(', ', $item_description);

        // Collect order data, including the post_password (order key)
        $order_data[] = array(
            'orderId' => $order->get_id(),
            'order_key' => $order_key, // Add the order key (post_password)
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'phone' => $order->get_billing_phone(),
            'total_cost' => $order->get_total(),
            'address' => $order->get_billing_address_1() . ', ' . $order->get_billing_city() . ', ' . $order->get_billing_state(),
            'state' => $order->get_billing_state(),
            'item_description' => $item_description_str // Add the new description
        );
    }
    echo '<script type="text/javascript">var sideupOrdersData = ' . json_encode($order_data) . ';</script>';
}


add_action('admin_enqueue_scripts', 'enqueue_sideup_script');

function enqueue_sideup_script()
{
    // Enqueue the main JavaScript file
    wp_enqueue_script('sideup-js', plugin_dir_url(__FILE__) . 'js/sideup-1.9.js', array('jquery'), '1.0', true);

    // Get the bearer token
    $bearerToken = get_option('woocommerce_sideup_settings')['APIKey'];

    // Pass the bearer token to the JavaScript file
    wp_localize_script('sideup-js', 'sideupSettings', array(
        'bearerToken' => $bearerToken,
    ));
}
