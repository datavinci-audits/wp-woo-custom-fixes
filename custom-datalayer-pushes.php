<?php
/**
 * Custom DataLayer push for GA4 purchase event in WooCommerce
 * Add to theme's functions.php or a custom plugin
 */

/**
 * Helper function to get item data for DataLayer
 */
function get_woocommerce_purchase_item_data($product, $quantity = 1) {
    if (!is_a($product, 'WC_Product')) return null;
    $category = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
    return [
        'item_id' => (string)$product->get_id(),
        'item_name' => $product->get_name(),
        'sku' => $product->get_sku() ?: (string)$product->get_id(),
        'price' => floatval($product->get_price()),
        'stockstatus' => $product->is_in_stock() ? 'instock' : 'outofstock',
        'item_category' => !empty($category) ? $category[0] : '',
        'quantity' => $quantity
    ];
}

/**
 * Purchase Event
 * Trigger: On order confirmation (thank you) page
 */
add_action('woocommerce_thankyou', 'datalayer_purchase_event', 10, 1);
function datalayer_purchase_event($order_id) {
    // Debug log to confirm hook is firing
    error_log('DataLayer: woocommerce_thankyou hook fired for order ' . $order_id);

    // Get the order
    $order = wc_get_order($order_id);
    if (!$order) {
        error_log('DataLayer Purchase Error: Invalid order ID ' . $order_id);
        return;
    }

    // Prepare items array
    $items = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product) {
            $item_data = get_woocommerce_purchase_item_data($product, $item->get_quantity());
            if ($item_data) {
                $items[] = $item_data;
            }
        }
    }

    // Skip if no items
    if (empty($items)) {
        error_log('DataLayer Purchase Error: No valid items for order ' . $order_id);
        return;
    }

    // Get additional parameters
    $coupons = $order->get_coupon_codes();
    $coupon_string = !empty($coupons) ? implode(',', $coupons) : '';
    $discount = floatval($order->get_total_discount());
    $tax = floatval($order->get_total_tax());
    $shipping = floatval($order->get_shipping_total());

    // DataLayer push
    ?>
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'purchase',
            ecommerce: {
                currency: '<?php echo esc_js($order->get_currency()); ?>',
                value: <?php echo floatval($order->get_total()); ?>,
                transaction_id: '<?php echo esc_js($order_id); ?>',
                coupon: '<?php echo esc_js($coupon_string); ?>',
                discount: <?php echo $discount; ?>,
                tax: <?php echo $tax; ?>,
                shipping: <?php echo $shipping; ?>,
                items: <?php echo wp_json_encode($items); ?>
            }
        });
        console.log('Purchase DataLayer Push (Primary):', {
            event: 'purchase',
            ecommerce: {
                currency: '<?php echo esc_js($order->get_currency()); ?>',
                value: <?php echo floatval($order->get_total()); ?>,
                transaction_id: '<?php echo esc_js($order_id); ?>',
                coupon: '<?php echo esc_js($coupon_string); ?>',
                discount: <?php echo $discount; ?>,
                tax: <?php echo $tax; ?>,
                shipping: <?php echo $shipping; ?>,
                items: <?php echo wp_json_encode($items); ?>
            }
        });
    </script>
    <?php
}

/**
 * Fallback: Ensure DataLayer push on order received page
 */
add_action('wp_footer', 'datalayer_purchase_fallback');
function datalayer_purchase_fallback() {
    if (is_order_received_page()) {
        // Debug log to confirm fallback
        error_log('DataLayer: Fallback triggered for order received page');

        $order_id = absint(get_query_var('order-received'));
        if (!$order_id) {
            error_log('DataLayer Purchase Fallback Error: No order ID found');
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('DataLayer Purchase Fallback Error: Invalid order ID ' . $order_id);
            return;
        }

        // Prevent duplicate push
        static $pushed = false;
        if ($pushed) return;
        $pushed = true;

        // Prepare items array
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $item_data = get_woocommerce_purchase_item_data($product, $item->get_quantity());
                if ($item_data) {
                    $items[] = $item_data;
                }
            }
        }

        if (empty($items)) {
            error_log('DataLayer Purchase Fallback Error: No valid items for order ' . $order_id);
            return;
        }

        // Get additional parameters
        $coupons = $order->get_coupon_codes();
        $coupon_string = !empty($coupons) ? implode(',', $coupons) : '';
        $discount = floatval($order->get_total_discount());
        $tax = floatval($order->get_total_tax());
        $shipping = floatval($order->get_shipping_total());

        ?>
        <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'purchase',
                ecommerce: {
                    currency: '<?php echo esc_js($order->get_currency()); ?>',
                    value: <?php echo floatval($order->get_total()); ?>,
                    transaction_id: '<?php echo esc_js($order_id); ?>',
                    coupon: '<?php echo esc_js($coupon_string); ?>',
                    discount: <?php echo $discount; ?>,
                    tax: <?php echo $tax; ?>,
                    shipping: <?php echo $shipping; ?>,
                    items: <?php echo wp_json_encode($items); ?>
                }
            });
            console.log('Purchase DataLayer Fallback Push:', {
                event: 'purchase',
                ecommerce: {
                    currency: '<?php echo esc_js($order->get_currency()); ?>',
                    value: <?php echo floatval($order->get_total()); ?>,
                    transaction_id: '<?php echo esc_js($order_id); ?>',
                    coupon: '<?php echo esc_js($coupon_string); ?>',
                    discount: <?php echo $discount; ?>,
                    tax: <?php echo $tax; ?>,
                    shipping: <?php echo $shipping; ?>,
                    items: <?php echo wp_json_encode($items); ?>
                }
            });
        </script>
        <?php
    }
}
?>