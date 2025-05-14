# WooCommerce Custom Fixes

A collection of PHP fixes for WooCommerce integrations on WordPress, enhancing event tracking with tools like GTM4WP and Klaviyo.

## Available Fixes

### `custom_purchase_datalayer.php`

**Why Required:** The default WooCommerce `woocommerce_thankyou` hook can fail due to theme or plugin conflicts, breaking GA4 purchase tracking via GTM4WP. Renaming the event and ensuring `item_id` is an integer improves compatibility.

**What It Does:** Pushes a `custom_purchase` event to the DataLayer for GA4, including essential order details like currency, value, transaction ID, and item information.

**How It Works:** Primarily uses the `woocommerce_thankyou` hook. Includes a fallback using `wp_footer` on the checkout page. Outputs an asynchronous script for better performance.

**Example DataLayer Push:**

```javascript
window.dataLayer.push({
    'event': 'custom_purchase',
    'ecommerce': {
        'currency': 'USD',
        'value': 199.00,
        'transaction_id': '12345',
        'items': [{'item_id': 83, 'item_name': 'Emotiv Flex Cap', /* ... */}]
    }
});
