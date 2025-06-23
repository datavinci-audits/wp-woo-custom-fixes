# WooCommerce Custom Fixes

A collection of PHP fixes for WooCommerce integrations on WordPress, enhancing event tracking with tools like GTM4WP.

## Available Fixes

### `custom_purchase_datalayer.php`

**Why Required:** The default WooCommerce `woocommerce_thankyou` hook can fail due to theme or plugin conflicts, breaking GA4 purchase tracking via GTM4WP. 

**What It Does:** Pushes a `custom_purchase` event to the DataLayer for GA4, including essential order details like currency, value, transaction ID, and item information.

**How It Works:** Primarily uses the `woocommerce_thankyou` hook. Includes a fallback using `wp_footer` on the checkout page. Outputs an asynchronous script for better performance.

**Example DataLayer Push:**

```javascript
window.dataLayer.push({
  event: 'custom_purchase',
  ecommerce: {
    currency: 'USD',
    value: 120.00,
    subtotal: 100.00,
    transaction_id: '12345',
    discount: 10.00,
    tax: 8.00,
    shipping: 12.00,
    coupons: 'SAVE10,SUMMER20',
    items: [
      {
        item_id: '1001',
        item_name: 'Product Name',
        sku: 'SKU123',
        price: 50.00,
        stockstatus: 'instock',
        item_category: 'Category',
        quantity: 2,
        item_variant: 'VARIANT_SKU'
      }
    ]
  },
  customer: {
    email: 'customer@example.com',
    first_name: 'John',
    last_name: 'Doe',
    phone: '123-456-7890',
    new_customer: 'Y',
    country: 'US',
    state: 'CA',
    address: { /* ... */ }
  }
});
