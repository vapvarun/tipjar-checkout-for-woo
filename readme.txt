=== TipJar at Checkout for Woo ===
Contributors: vapvarun
Tags: woocommerce, tips, checkout, donation, fee
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a highly configurable tip selector to your WooCommerce checkout page.

== Description ==
TipJar at Checkout for Woo introduces a powerful and flexible tip selector to the WooCommerce checkout. Customers can choose a preset amount or enter a custom tip, and the value is stored on the order for later reference.

With version 2.0, you can now manage all settings from the WordPress admin, view a detailed log of all tips, and even control when the tip field appears based on cart contents.

*   **Easy Configuration:** A dedicated settings page at `WooCommerce > Settings > TipJar Checkout` lets you enable/disable the feature, customize all labels, and set preset amounts without touching any code.
*   **Detailed Tip Log:** View a complete history of all tips in a new "Tip Log" page, complete with order links, amounts, and dates.
*   **CSV Export:** Export your complete tip history to a CSV file for easy accounting and analysis.
*   **Conditional Display:** Show the tip field only when specific conditions are met. You can set rules based on:
    *   Products in the cart
    *   Product categories in the cart
    *   Minimum or maximum cart total
*   **Developer Friendly:** Includes developer filters to adjust preset amounts, label text, and tax behaviour programmatically.

== Installation ==
1.  Upload the plugin folder to `/wp-content/plugins/`, or install it from the WordPress plugin screen.
2.  Activate **TipJar at Checkout for Woo** through the *Plugins* menu.
3.  Go to `WooCommerce > Settings > TipJar Checkout` to configure the plugin.
4.  Visit the checkout page to verify the tip selector appears as configured.

== Frequently Asked Questions ==
= How do I configure the plugin? =
All settings can be found in the WordPress admin area under `WooCommerce > Settings > TipJar Checkout`.

= Can I change the preset tip amounts? =
Yes. You can enter a comma-separated list of amounts in the settings page. For more advanced control, you can still use the `tipjar_checkout_tip_presets` filter.

= Are tips taxable? =
You can set whether tips are taxable from the settings page. By default, they are non-taxable.

= How can I see a list of all tips? =
A new "Tip Log" page is available under the main `WooCommerce` menu. It lists all tips and allows you to export them to a CSV file.

= Can I only show the tip field for certain orders? =
Yes. On the settings page, you can find a "Conditional Display" section where you can set rules based on products, categories, or the cart total.

== Changelog ==
= 2.0.0 =
*   **Feature:** Added a settings page under `WooCommerce > Settings > TipJar Checkout`.
*   **Feature:** Added a "Tip Log" page with CSV export functionality.
*   **Feature:** Added conditional display logic based on products, categories, and cart totals.
*   **Tweak:** Updated author details and plugin version.

= 1.0.0 =
*   Initial release.