<?php
/**
 * Custom DataLayer push for GA4 purchase event in WooCommerce.
 * Production-ready, single file, PSR-12, no debug logs.
 * Add to theme's functions.php or a custom plugin.
 */

/**
 * Get item data for DataLayer.
 *
 * @param WC_Product $product  The WooCommerce product.
 * @param int        $quantity The item quantity.
 * @return array|null Item data or null if invalid product.
 */
function get_woocommerce_purchase_item_data($product, $quantity = 1)
{
    if (!is_a($product, 'WC_Product')) {
        return null;
    }
    $category = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
    return [
        'item_id' => (string) $product->get_id(),
        'item_name' => $product->get_name(),
        'sku' => $product->get_sku() ?: (string) $product->get_id(),
        'price' => (float) $product->get_price(),
        'stockstatus' => $product->is_in_stock() ? 'instock' : 'outofstock',
        'item_category' => !empty($category) ? $category[0] : '',
        'quantity' => $quantity,
    ];
}

/**
 * Generate DataLayer script for purchase event.
 *
 * @param WC_Order $order The WooCommerce order.
 * @return void
 */
function generate_purchase_datalayer_script($order)
{
    $items = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product instanceof WC_Product) {
            $item_data = get_woocommerce_purchase_item_data($product, (int) $item->get_quantity());
            if ($item_data) {
                $items[] = $item_data;
            }
        }
    }

    if (empty($items)) {
        return;
    }

    $coupons = $order->get_coupon_codes();
    $coupon_string = !empty($coupons) ? implode(',', $coupons) : '';
    $discount = (float) $order->get_total_discount();
    $tax = (float) $order->get_total_tax();
    $shipping = (float) $order->get_shipping_total();

    ?>
    <script async>
        (function () {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'purchase',
                ecommerce: {
                    currency: '<?php echo esc_js($order->get_currency()); ?>',
                    value: <?php echo (float) $order->get_total(); ?>,
                    transaction_id: '<?php echo esc_js($order->get_id()); ?>',
                    coupon: '<?php echo esc_js($coupon_string); ?>',
                    discount: <?php echo $discount; ?>,
                    tax: <?php echo $tax; ?>,
                    shipping: <?php echo $shipping; ?>,
                    items: <?php echo wp_json_encode($items); ?>
                }
            });
        })();
    </script>
    <?php
}

/**
 * Purchase Event (Primary)
 * Trigger: On order confirmation (thank you) page.
 *
 * @param int $order_id The order ID.
 * @return void
 */
function datalayer_purchase_event($order_id)
{
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    generate_purchase_datalayer_script($order);
}
add_action('woocommerce_thankyou', 'datalayer_purchase_event', 10, 1);

/**
 * Fallback: Ensure DataLayer push on order received page.
 *
 * @return void
 */
function datalayer_purchase_fallback()
{
    if (is_order_received_page()) {
        $order_id = absint(get_query_var('order-received'));
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        static $pushed = false;
        if ($pushed) {
            return;
        }
        $pushed = true;

        generate_purchase_datalayer_script($order);
    }
}
add_action('wp_footer', 'datalayer_purchase_fallback');
?>