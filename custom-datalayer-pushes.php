<?php
/*
 * Custom DataLayer push for GA4 custom_purchase event in WooCommerce.
 * Production-ready, single file, strict types.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get item data for DataLayer.
 *
 * @param WC_Product $product The WooCommerce product.
 * @param int        $quantity The item quantity.
 * @return array|null Item data or null if invalid product.
 */
function get_woocommerce_item_data(WC_Product $product, int $quantity = 1): ?array
{
    if (!is_a($product, 'WC_Product')) {
        return null;
    }
    $category = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
    return [
        'item_id' => $product->get_id(),
        'item_name' => $product->get_name(),
        'sku' => $product->get_sku() ?: (string) $product->get_id(),
        'price' => (float) $product->get_price(),
        'stockstatus' => $product->is_in_stock() ? 'instock' : 'outofstock',
        'item_category' => !empty($category) ? $category[0] : '',
        'quantity' => $quantity
    ];
}

/**
 * Generate DataLayer script for custom_purchase event.
 *
 * @param WC_Order $order The WooCommerce order.
 * @return void
 */
function generate_custom_purchase_datalayer_script(WC_Order $order): void
{
    $items = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product instanceof WC_Product) {
            $item_data = get_woocommerce_item_data($product, (int) $item->get_quantity());
            if ($item_data) {
                $items[] = $item_data;
            }
        }
    }

    if (empty($items)) {
        return;
    }

    $coupons = $order->get_coupon_codes();
    $discount = (float) $order->get_total_discount();
    $tax = (float) $order->get_total_tax();
    $shipping = (float) $order->get_shipping_total();

    ?>
    <script async>
        (function () {
            window.dataLayer = window.dataLayer || [];
            var ecommerce = {
                'currency': '<?php echo esc_js($order->get_currency()); ?>',
                'value': <?php echo $order->get_total(); ?>,
                'transaction_id': '<?php echo esc_js($order->get_id()); ?>',
                'discount': <?php echo $discount; ?>,
                'tax': <?php echo $tax; ?>,
                'shipping': <?php echo $shipping; ?>,
                'items': <?php echo wp_json_encode($items); ?>
            };
            <?php if (!empty($coupons)) : ?>
                ecommerce.coupon = '<?php echo esc_js(implode(',', $coupons)); ?>';
            <?php endif; ?>
            window.dataLayer.push({
                'event': 'custom_purchase',
                'ecommerce': ecommerce
            });
        })();
    </script>
    <?php
}

/**
 * Custom purchase event handler for thank you page.
 *
 * @param int $order_id The order ID.
 * @return void
 */
function datalayer_custom_purchase_event(int $order_id): void
{
    $order_id = (int) $order_id;
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    generate_custom_purchase_datalayer_script($order);
}
add_action('woocommerce_thankyou', 'datalayer_custom_purchase_event', 10, 1);

/**
 * Fallback handler for order received page.
 *
 * @return void
 */
function datalayer_custom_purchase_fallback(): void
{
    if (is_checkout() && isset($_GET['order-received'])) {
        $order_id = (int) $_GET['order-received'];
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
        generate_custom_purchase_datalayer_script($order);
    }
}
add_action('wp_footer', 'datalayer_custom_purchase_fallback', 10);
?>