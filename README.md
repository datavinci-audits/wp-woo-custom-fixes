# WooCommerce Custom Fixes

A collection of PHP fixes for WooCommerce integrations on WordPress, enhancing event tracking with tools like GTM4WP.

## Available Fixes

### `custom_purchase_datalayer.php`

**Why Required:** The default WooCommerce `woocommerce_thankyou` hook can fail due to theme or plugin conflicts, breaking GA4 purchase tracking via GTM4WP. 

**What It Does:** Pushes a `custom_purchase` event to the DataLayer for GA4, including essential order details like currency, value, transaction ID, and item information.

**How It Works:** Primarily uses the `woocommerce_thankyou` hook. Includes a fallback using `wp_footer` on the checkout page. Outputs an asynchronous script for better performance.

**Example DataLayer Push:**

```javascript
{
    event: 'custom_purchase',
    ecommerce: {
        currency: 'USD',
        value: 100.00,
        subtotal: 90.00,
        transaction_id: '1234',
        discount: 10.00,
        tax: 8.00,
        shipping: 5.00,
        coupons: 'SAVE10',
        payment_method: 'Credit Card', // or 'Bank Transfer', 'Stripe', etc.
        items: [/* ... */]
    },
    customer: {
        email: 'customer@example.com',
        first_name: 'John',
        last_name: 'Doe',
        new_customer: 'Y',
        country: 'US',
        state: 'CA',
        address: {
            street: '123 Main St',
            city: 'San Francisco',
            region: 'CA',
            postal_code: '94105',
            country: 'US'
        }
    }
}
