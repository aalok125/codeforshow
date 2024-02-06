
<?php
/********#########################################################*************/
/* Wocommerce MultiStore Functionality Start */

function add_custom_taxonomies()
{
    register_taxonomy('location', 'product', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => _x('Locations', 'taxonomy general name'),
            'singular_name' => _x('Location', 'taxonomy singular name'),
            'search_items' =>  __('Search Locations'),
            'all_items' => __('All Locations'),
            'parent_item' => __('Parent Location'),
            'parent_item_colon' => __('Parent Location:'),
            'edit_item' => __('Edit Location'),
            'update_item' => __('Update Location'),
            'add_new_item' => __('Add New Location'),
            'new_item_name' => __('New Location Name'),
            'menu_name' => __('Locations'),
        ),

        'rewrite' => array(
            'slug' => 'locations',
            'with_front' => false,
            'hierarchical' => true
        ),
        'capabilities' => array(
            'manage_terms' => '',
            'edit_terms' => '',
            'delete_terms' => '',
            'assign_terms' => 'edit_posts'
        ),
    ));
}
add_action('init', 'add_custom_taxonomies', 55);


// general tabs

add_action('woocommerce_product_options_inventory_product_data', 'custom_inventory_fields');
add_action('woocommerce_process_product_meta', 'custom_inventory_fields_save');



function custom_inventory_fields()
{

    global $woocommerce, $post;
    $product_locations = get_the_terms($post->ID, 'location');
    echo '<div class="options_group">';
    if (!empty($product_locations) && count($product_locations) > 0) :
        foreach ($product_locations as $location) :
            woocommerce_wp_text_input(
                array(
                    'id'            => "variable_{$location->name}_stock",
                    'name'          => "locationstock[_{$location->slug}_stock]",
                    'value'         => get_post_meta($post->ID, "_{$location->slug}_stock", true) ?? 0,
                    'label'         => __('Stock quantity in ' . $location->name, 'woocommerce'),
                    'desc_tip'      => true,
                    'description'   => __('Add Stock quantity of Location ' . $location->name, 'woocommerce'),
                    'wrapper_class' => "form-row form-row-full",
                    'type'              => 'number',
                )
            );


        endforeach;
    endif;

    echo '</div>';
}


function custom_inventory_fields_save($post_id)
{

    if (!empty($_POST['locationstock'])) {
        $locationstocks = $_POST['locationstock'];

        $quantityCount = 0;
        foreach ($locationstocks as $key => $locationstock) {
            $quantityCount += (int) $locationstock;
            update_post_meta($post_id, $key, esc_attr($locationstock) ?? 0);
        }

        update_post_meta($post_id, '_stock',  $quantityCount);
    }
}



// variation tabs
add_action('woocommerce_product_after_variable_attributes', 'variation_settings_fields', 10, 3);
add_action('woocommerce_save_product_variation', 'save_variation_settings_fields', 10, 2);

function variation_settings_fields($loop, $variation_data, $variation)

{
    global $woocommerce, $post;
    $product_locations = get_the_terms($post->ID, 'location');


    woocommerce_wp_hidden_input(
        array(
            'id'    => 'variation_product_id',
            'name' => "variation_product_id",
            'value' => $post->ID,
        )
    );

?>

    <div class="show_if_variation_manage_stock" style="<?php if ($variation_data['_manage_stock'][0] == 'no') {
                                                            echo "display:none;";
                                                        } ?>">


        <?php
        if (isset($product_locations) && count($product_locations) > 0) :
            $loopcount = 0;
            foreach ($product_locations as $location) :
                if ($loopcount % 2 == 0) {
                    $rowclass = "form-row-first";
                } else {
                    $rowclass = "form-row-last";
                }
                woocommerce_wp_text_input(
                    array(
                        'id'            => "variable_{$location->name}_stock{$loop}",
                        'name'          => "locationstock[_{$location->slug}_stock][{$loop}]",
                        'value'         => get_post_meta($variation->ID, "_{$location->slug}_stock", true) ?? 0,
                        'label'         => __('Stock quantity in ' . $location->name, 'woocommerce'),
                        'desc_tip'      => true,
                        'description'   => __('Add Stock quantity of Location ' . $location->name, 'woocommerce'),
                        'wrapper_class' => "form-row {$rowclass}",
                        'type'              => 'number',
                    )
                );

                $loopcount++;

            endforeach;
        endif;

        ?>
    </div>

    <?php

}

function save_variation_settings_fields($variation_id, $loop)
{

    if (!empty($_POST['locationstock']) && !empty($_POST['variation_product_id'])) {
        $locationstocks = $_POST['locationstock'];
        $productID = (int)$_POST['variation_product_id'];
        $quantityCount = 0;
        foreach ($locationstocks as $key => $locationstock) {
            $quantityCount += (int) $locationstock[$loop];
            update_post_meta($variation_id, $key, esc_attr($locationstock[$loop]));
        }
        update_post_meta($variation_id, '_stock',  $quantityCount);
    }
}


add_action('woocommerce_new_product', 'update_total_stock_after_product_update', 10, 1);
add_action('woocommerce_update_product', 'update_total_stock_after_product_update', 10, 1);

function update_total_stock_after_product_update($post_id)
{
    $product = wc_get_product($post_id);
    update_post_meta(9999, "post_id_value",  $post_id);
    if ($product->is_type('variable')) {
        // Update Product Total Stock
        $productLocStock = [];
        $productTotalStock = 0;
        $variations = $product->get_available_variations();
        $product_locations = get_the_terms($post_id, 'location');
        foreach ($variations as $var) {
            $variations_id = $var['variation_id'];
            foreach ($product_locations as $location) {
                $locStock = (int)get_post_meta($variations_id, "_{$location->slug}_stock", true);
                $productLocStock[$location->slug] += $locStock;
                $productTotalStock += $locStock;
            }
        }
        foreach ($product_locations as $location) {
            update_post_meta($post_id, "_{$location->slug}_stock",  $productLocStock[$location->slug]);
        }
        update_post_meta($post_id, '_stock',  $productTotalStock);
    } elseif ($product->is_type('simple')) {
        $productTotalStock = 0;
        $product_locations = get_the_terms($post_id, 'location');
        foreach ($product_locations as $location) {
            $locStock = (int)get_post_meta($post_id, "_{$location->slug}_stock", true);
            $productTotalStock += $locStock;
        }
        update_post_meta($post_id, '_stock',  $productTotalStock);
    }
}


// get location based product
function scodus_change_product_query($q)
{
    if (!is_admin() && isset($q->query_vars['post_type']) && $q->query_vars['post_type'] == 'product') {
        $sessionLocation = $_SESSION['location'] ?? 'colton';
        $tax_query = array(
            array(
                'taxonomy' => 'location',
                'field'    => 'slug',
                'terms'    => $sessionLocation
            )
        );
        $q->set('tax_query', $tax_query);
    }
}
add_action('pre_get_posts', 'scodus_change_product_query');
// add_action('woocommerce_product_query', 'scodus_change_product_query');



function update_quantity_after_order_complete($order_id)
{
    $sessionLocation = $_SESSION['location'] ?? '';
    $locationStocks = "_{$sessionLocation}_stock";
    $order = wc_get_order($order_id); //returns WC_Order if valid order 
    $items = $order->get_items();   //returns an array of WC_Order_item or a child class (i.e. WC_Order_Item_Product)

    foreach ($items as $item) {
        $orderQuantity = (int) $item->get_quantity();
        $product      = $item->get_product();
        $product_id = $item->get_product_id();

        $getProductQuantity  = (int) get_post_meta($product_id, $locationStocks, true);
        $remberProductQuantity = $getProductQuantity - $orderQuantity;
        update_post_meta($product_id, $locationStocks, $remberProductQuantity);

        if ($product->is_type('variation')) {
            $variation_id = $item->get_variation_id();
            $getVariationQuantity  = (int) get_post_meta($variation_id, $locationStocks, true);
            $remberVariationQuantity = $getVariationQuantity - $orderQuantity;
            update_post_meta($variation_id, $locationStocks, $remberVariationQuantity);
        }
    }
}

add_action('woocommerce_order_status_completed', 'update_quantity_after_order_complete', 20, 2);

add_filter('woocommerce_quantity_input_args', 'scodus_woocommerce_quantity_input_args', 10, 2); // Simple products

function scodus_woocommerce_quantity_input_args($args, $product)
{
    $sessionLocation = $_SESSION['location'] ?? '';
    $locationStocks = "_{$sessionLocation}_stock";
    $getSimpleQuantity = (int) get_post_meta($product->get_id(), $locationStocks, true);
    $args['max_value'] = $getSimpleQuantity && $getSimpleQuantity < 0 ? 0 : $getSimpleQuantity;
    return $args;
}

add_filter('woocommerce_available_variation', 'scodus_woocommerce_available_variation');
function scodus_woocommerce_available_variation($args)
{
    $sessionLocation = $_SESSION['location'] ?? '';
    $locationStocks = "_{$sessionLocation}_stock";
    $getVariationManageStock = get_post_meta($args['variation_id'], "_manage_stock", true);
    if ($getVariationManageStock == "yes") {
        $getVariationQuantity = (int) get_post_meta($args['variation_id'], $locationStocks, true);
        $args['max_qty'] = $getVariationQuantity && $getVariationQuantity < 0 ? 0 : $getVariationQuantity;
        $args['qty'] = 1;
        if ($args['max_qty'] <= 0) {
            $args['is_in_stock'] = false;
            $args['availability_html'] = "Out of Stock";
        } else {
            $args['is_in_stock'] = true;
            $args['availability_html'] = $getVariationQuantity . " in Stock";
        }
    }
    return $args;
}



add_filter('woocommerce_format_stock_quantity', 'wpsh_stock_suffix', 9999, 2);

function wpsh_stock_suffix($stock_quantity, $product)
{
    if ($product->is_type('simple')) {
        $manageStock = $product->get_manage_stock();
        if ($manageStock) {
            $sessionLocation = $_SESSION['location'] ?? '';
            $locationStocks = "_{$sessionLocation}_stock";
            $getSimpleQuantity = (int) get_post_meta($product->get_id(), $locationStocks, true);
            if ($getSimpleQuantity && $getSimpleQuantity > 0 && $getSimpleQuantity != null) {
                $stock_quantity = $getSimpleQuantity;
            }
        }
    }
    return $stock_quantity;
}



add_filter('woocommerce_product_is_in_stock', 'wp_kama_woocommerce_product_is_in_stock_filter', 10, 2);

function wp_kama_woocommerce_product_is_in_stock_filter($stcokvalue, $productData)
{
    if ($productData->is_type('simple')) {
        $manageStock = $productData->get_manage_stock();
        if ($manageStock) {
            $sessionLocation = $_SESSION['location'] ?? '';
            $locationStocks = "_{$sessionLocation}_stock";
            $getSimpleQuantity = (int) get_post_meta($productData->get_id(), $locationStocks, true);

            if (!$getSimpleQuantity && $getSimpleQuantity <= 0 && $getSimpleQuantity == null) {
                $stcokvalue = false;
            }
        }
    }
    return $stcokvalue;
}



function register_session_new()
{
    if (!session_id()) {
        session_start();
        $location = $_SESSION['location'] ?? '';
        if (!$location || $location == "") {
            $_SESSION['location'] = "colton";
        }
    }
}
add_action('init', 'register_session_new');

add_action('admin_post_change_user_location', 'change_user_store_location');
add_action('admin_post_nopriv_change_user_location', 'change_user_store_location');

function change_user_store_location()
{

    if ($_REQUEST['action'] == "change_user_location") {
        if (isset($_POST['location']) && $_POST['location'] != null) {

            if (isset($_SESSION['location']) && $_SESSION['location'] != $_POST['location']) {
                if (function_exists('WC')) {
                    if (is_null(WC()->cart)) {
                        wc_load_cart();
                    }
                    WC()->cart->empty_cart();
                }
            }
            $_SESSION['location'] = $_POST['location'];
        } else {
            $_SESSION['location'] = null;
        }
    }
    wp_redirect(home_url());
    exit();
}


function handle_location_modal()
{
    $userLocation = $_SESSION['location'] ?? '';
    if ($userLocation == '') {
    ?>
        <script>
            var element = document.getElementsByClassName("puffnup_choose_location")[0];
            if (element) {
                element.click();
            }
        </script>
    <?php
    }
    ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="puffnup_change_location_form" style="display:none;">
        <input type="hidden" name="action" value="change_user_location">
        <input type="hidden" name="location" id="change_location_value" />
    </form>
    <script>
        function changeLocation(location) {
            var input = document.getElementById("change_location_value");
            if (input) {
                input.value = location;
                document.getElementById('puffnup_change_location_form').submit();
            } else {
                console.log("Error! Cannot change location");
            }
        }
    </script>
<?php
}
add_action('wp_footer', 'handle_location_modal');


/* Wocommerce MultiStore Functionality End */
/********#########################################################*************/



/********#########################################################*************/
/* Wocommerce Custom Tax Start */

/*
* Author : Scodus
* Function to fetch tax rates from Zip-Tax Api
*/
function fetchTaxUrl($postCode)
{
    $dbData = get_option("_puffnup_sales_tax_" . $postCode);
    if ($dbData) {
        $value = json_decode($dbData, true);
        if (isset($value["rate"]) && isset($value["expiry"]) && $value["rate"] && $value["expiry"]) {
            // check if expired
            $currentTime = time();
            if ($value["expiry"] < $currentTime) {
                return $value["rate"];
            }
        }
    }
    $response = wp_remote_get("https://api.zip-tax.com/request/v40?key=" . PUFFNUP_ZIP_TAX_API_KEY . "&postalcode=" . $postCode);
    $result = json_decode($response["body"], true);
    $maxTax = 0;
    foreach ($result["results"] as $value) {
        if (isset($value["taxSales"]) && $value["taxSales"] > $maxTax) {
            $maxTax = $value["taxSales"];
        }
    }
    $dbData = json_encode([
        "rate" => $maxTax,
        "expiry" => time() + 60 * 60 * 24 * 30
    ]);
    update_option("_puffnup_sales_tax_" . $postCode, $dbData, true);
    return $maxTax;
}

/*
* Author : Scodus
* Add tax to checkout
*/
add_action('woocommerce_cart_calculate_fees', 'custom_tax_for_puffnup', 10, 1);
function custom_tax_for_puffnup($cart)
{
    if (is_user_logged_in()) {
        $postCode = WC()->session->get('customer')['postcode'];
        $postCode = trim($postCode, " ");
        if ($postCode == "" || strlen($postCode) != 5) {
            return;
        }
        $percentValue = fetchTaxUrl($postCode);
        $percent = $percentValue * 100;
        // $percent = 10;
        // Calculation
        $surcharge = ($cart->cart_contents_total + $cart->shipping_total) * $percentValue;
        // Add the fee (tax third argument disabled: false)
        $cart->add_fee(__('Tax', 'woocommerce') . " ($percent %)", $surcharge, false);

        if (isset($_SESSION['location']) && $_SESSION['location'] == "colton") {
            $exciseAmountToAdded = findAttributesInCart('0 mg');
            if ($exciseAmountToAdded && $exciseAmountToAdded > 0) {
                $exciseAmount = (12 / 100) * $exciseAmountToAdded;
                $cart->add_fee(__('Excise Duty', 'woocommerce') . " (12 %)", $exciseAmount, false);
            }
        }
    }
}


function findAttributesInCart($attribute_slug_term)
{
    $foundAmount = 0;
    if (WC()->cart->is_empty())
        return $foundAmount;
    else {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['variation_id'] > 0) {

                $nonMgAmount = false;
                $exciseInMg = ["3mg", "6mg", "12mg"];
                foreach ($cart_item['variation'] as $term_slug) {
                    $cleanTremslug = makeStringClean($term_slug);

                    if ($cleanTremslug && in_array($cleanTremslug, $exciseInMg)) {
                        $nonMgAmount = true;
                    }
                }
                if ($nonMgAmount == true) {
                    $foundAmount += $cart_item['line_total'];
                }
            }
        }
        return $foundAmount;
    }
}



function makeStringClean($string)
{
    // Replaces all spaces with hyphens.
    $string = str_replace(' ', '-', $string);

    // Removes special chars.
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    // Replaces multiple hyphens with single one.
    $string = preg_replace('/-+/', '', $string);

    return $string;
}


/*
* Author : Scodus
* Validate zip code
*/
add_action('woocommerce_checkout_process', 'custom_checkout_validation_puffnup');
function custom_checkout_validation_puffnup()
{
    global $woocommerce;
    // Check if set, if its not set add an error. This one is only requite for companies
    if (!(preg_match('/^[0-9]{5}$/D', $_POST['billing_postcode']))) {
        wc_add_notice("Incorrect Zip code! Please enter correct number.", 'error');
    }
}

/*
* Author : Scodus
* Shortcode to get current location in session
*/
function get_session_location()
{
    if (isset($_SESSION['location']) && $_SESSION['location'] != null) {
        switch ($_SESSION['location']) {
            case 'colton':
                return "1705 E Washington St #112 Colton, CA 92324";
            case 'cathedral-city':
                return "35440 Date Palm Dr, Cathedral City, CA 92234";
            default:
                break;
        }
    }
    return "No Location";
}
add_shortcode('get-selected-location', 'get_session_location');

/* Wocommerce Custom Tax End */
/********#########################################################*************/


/********#########################################################*************/
/* Wocommerce Store Shop Location in Order and Display in Admin */

// Store the location of shop in order
add_action('woocommerce_checkout_update_order_meta', 'scodus_save_session_location');
function scodus_save_session_location($order_id)
{
    if (isset($_SESSION['location']) && $_SESSION['location'] != null) {
        switch ($_SESSION['location']) {
            case 'colton':
                update_post_meta($order_id, 'store_location', '1705 E Washington St #112 Colton, CA 92324');
                break;
            case 'cathedral-city':
                update_post_meta($order_id, 'store_location', '35440 Date Palm Dr, Cathedral City, CA 92234');
                break;
            default:
                break;
        }
    }
}

// Dsiplay user Shop Location for the Order in Order Details
add_action('woocommerce_admin_order_data_after_billing_address', 'misha_editable_order_meta_billing');
function misha_editable_order_meta_billing($order)
{

    $storeLocation = $order->get_meta('store_location');
?>
    <div class="address">
        <p>
            <strong>Customer Selected Store Location:</strong>
            <?php echo $storeLocation ? esc_html($storeLocation) : 'No location selected.' ?>
        </p>
    </div>
    <?php
}

// Adding Location Column to Order List Table
add_filter('manage_edit-shop_order_columns', 'puffnup_shop_order_column', 20);
function puffnup_shop_order_column($columns)
{
    $reordered_columns = array();

    // Inserting columns to a specific location
    foreach ($columns as $key => $column) {
        $reordered_columns[$key] = $column;
        if ($key ==  'order_status') {
            // Inserting after "Status" column
            $reordered_columns['store_location'] = __('Location', 'theme_domain');
            // $reordered_columns['sales_tax'] = __( 'Title2','theme_domain');
        }
    }
    return $reordered_columns;
}

// Adding location from order meta to order table
add_action('manage_shop_order_posts_custom_column', 'puffnup_orders_list_column_content', 20, 2);
function puffnup_orders_list_column_content($column, $post_id)
{
    switch ($column) {
        case 'store_location':
            // Get custom post meta data
            $my_var_one = get_post_meta($post_id, 'store_location', true);
            if (!empty($my_var_one))
                echo $my_var_one;
            // Empty value case
            else
                echo '<small>(<em>no value</em>)</small>';
            break;

        default:
            break;
    }
}


/**
 * Show store location to customer order dashboard
 */
function puffnup_display_order_location_data($order_id)
{
    $storeLocation = get_post_meta($order_id, 'store_location', true);
    if ($storeLocation && $storeLocation != "") {
    ?>
        <h4><?php _e('Extra Information'); ?></h2>
            <table class="shop_table shop_table_responsive additional_info">
                <tbody>
                    <tr>
                        <th><?php _e('Store Location'); ?></th>
                        <td><?php echo $storeLocation; ?></td>
                    </tr>
                </tbody>
            </table>
        <?php
    }
}
add_action('woocommerce_thankyou', 'puffnup_display_order_location_data', 20);
add_action('woocommerce_view_order', 'puffnup_display_order_location_data', 20);

/**
 * Add a custom field (in an order) to the emails sent to admin
 */
add_filter('woocommerce_email_order_meta_fields', 'puffnup_woocommerce_email_order_meta_fields', 10, 3);
function puffnup_woocommerce_email_order_meta_fields($fields, $sent_to_admin, $order)
{
    $fields['store_location'] = array(
        'label' => __('Store Location'),
        'value' => get_post_meta($order->id, 'store_location', true),
    );
    return $fields;
}

// Redirect to login page if not logged in during checkout
add_action('template_redirect', 'checkout_redirect_non_logged_to_login_access');
function checkout_redirect_non_logged_to_login_access()
{
    if (is_checkout() && !is_user_logged_in()) {
        $_SESSION['puffnup_referer_url'] = get_the_permalink();
        wp_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')));
        exit;
    }
}


// Redirect back to checkout after logged in
add_filter('woocommerce_registration_redirect', 'redirect_after_login_or_registration_to_checkout_page');
add_filter('woocommerce_login_redirect', 'redirect_after_login_or_registration_to_checkout_page');
function redirect_after_login_or_registration_to_checkout_page()
{
    // only if the cart is not empty
    if (!WC()->cart->is_empty()) {
        return WC()->cart->get_checkout_url();
    } else {
        return home_url();
    }
}


require get_template_directory() . '/inc/taxreport.php';



define('WC_MAX_LINKED_VARIATIONS', 300);




function add_custom_user_fields($user)
{
    $taxonomy_name = 'location';
    $post_type = 'product';

    $storeLocations = get_terms(array(
        'taxonomy' => $taxonomy_name,
        'object_type' => array($post_type),
        'hide_empty' => false,
    ));
    $user_location = get_user_meta($user->ID, 'store_location', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="my_custom_field">Employee Store Location</label></th>
                <td>
                    <select required name="store_location" id="store_location">
                        <option value="">-- Select a location --</option>
                        <?php foreach ($storeLocations as $location) : ?>
                            <option value="<?php echo esc_attr($location->slug); ?>" <?php selected($user_location, $location->slug); ?>><?php echo esc_html($location->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
    <?php
}
add_action('show_user_profile', 'add_custom_user_fields');
add_action('edit_user_profile', 'add_custom_user_fields');

function save_custom_user_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }
    update_user_meta($user_id, 'store_location', sanitize_text_field($_POST['store_location']));
}
add_action('personal_options_update', 'save_custom_user_fields');
add_action('edit_user_profile_update', 'save_custom_user_fields');



function scannerInventory($product_id, $quantity, $type = null)
{
    if (is_user_logged_in()) {
        $user_location = get_user_meta(get_current_user_id(), 'store_location', true);

        if ($user_location) {
            $product = wc_get_product($product_id);
            $locStock = (int)get_post_meta($product_id, "_{$user_location}_stock", true)??0;
            $mainStock = (int)get_post_meta($product_id, "_stock", true)??0;
            if ($product->get_type() == 'variable') {
                $parentStock = (int)get_post_meta($product->get_parent_id(), "_stock", true)??0;

                if($type = "plusqty"){
                    $plusLocQty = $locStock + $quantity;
                    $plusMainQty = $mainStock + $quantity;
                    $plusParentQty = $parentStock + $parentStock;
                    update_post_meta($product_id, "_{$user_location}_stock",  $plusLocQty);
                    update_post_meta($product_id, "_stock",  $plusMainQty);
                    update_post_meta($product->get_parent_id(), "_stock",  $plusParentQty);
                }elseif($type = "minusqty"){
                    $minusLocQty = $locStock - $quantity;
                    $minusMainQty = $mainStock - $quantity;
                    $minusParentQty = $parentStock - $parentStock;
                    update_post_meta($product_id, "_{$user_location}_stock",  $minusLocQty);
                    update_post_meta($product_id, "_stock",  $minusMainQty);
                    update_post_meta($product->get_parent_id(), "_stock",  $minusParentQty);
                    
                }elseif($type = "updateqty"){
                    $updateQty = $quantity;
                    $remaningQty = $locStock - $quantity;

                    if($remaningQty>0){
                        $updateMainQty = $mainStock + $remaningQty;
                        $updateParentQty = $parentStock + $remaningQty;
                    }else{
                        $updateMainQty = $mainStock - $remaningQty;
                        $updateParentQty = $parentStock - $remaningQty;
                    }
                   
                    update_post_meta($product_id, "_{$user_location}_stock",  $quantity);
                    update_post_meta($product_id, "_stock",  $updateMainQty);
                    update_post_meta($product->get_parent_id(), "_stock",  $updateParentQty);
                }

            } else {

                if($type = "plusqty"){
                    $plusLocQty = $locStock + $quantity;
                    $plusMainQty = $mainStock + $quantity;
                    update_post_meta($product_id, "_{$user_location}_stock",  $plusLocQty);
                    update_post_meta($product_id, "_stock",  $plusMainQty);
                }elseif($type = "minusqty"){
                    $minusLocQty = $locStock - $quantity;
                    $minusMainQty = $mainStock - $quantity;
                    update_post_meta($product_id, "_{$user_location}_stock",  $minusLocQty);
                    update_post_meta($product_id, "_stock",  $minusMainQty);
                    
                }elseif($type = "updateqty"){
                    $updateQty = $quantity;
                    $remaningQty = $locStock - $quantity;

                    if($remaningQty>0){
                        $updateMainQty = $mainStock + $remaningQty;
                    }else{
                        $updateMainQty = $mainStock - $remaningQty;
                    }
                   
                    update_post_meta($product_id, "_{$user_location}_stock",  $quantity);
                    update_post_meta($product_id, "_stock",  $updateMainQty);
                }

            }
        }
    }
}

