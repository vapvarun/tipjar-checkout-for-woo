=== TipJar at Checkout for Woo ===
Contributors: shashankdubey
Tags: woocommerce, tips, checkout, donation, fee
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add optional tip buttons and a custom tip field to the WooCommerce checkout page.

== Description ==
TipJar at Checkout for Woo introduces a lightweight tip selector to the WooCommerce checkout totals. Customers can choose a preset amount or enter a custom tip, and the value is stored on the order for later reference.

* Add configurable quick-select tip buttons plus a custom tip input.
* Updates order totals instantly on classic and block-based checkout flows.
* Saves the tip as a fee line item and order meta for reporting.
* Includes developer filters to adjust preset amounts, label text, and tax behaviour.

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/`, or install it from the WordPress plugin screen.
2. Activate **TipJar at Checkout for Woo** through the *Plugins* menu.
3. Visit the checkout page to verify the tip selector appears.
4. Use the `tipjar_checkout_tip_presets` filter in your theme or a helper plugin to adjust preset amounts if needed.

== Frequently Asked Questions ==
= Can I change the preset tip amounts? =
Yes. Use the `tipjar_checkout_tip_presets` filter to return an array of numeric amounts (for example `[ 0, 1, 3, 5 ]`).

= Are tips taxable? =
By default tips are non-taxable. Hook into `tipjar_checkout_tip_taxable` and return `true` if you need to apply tax to the tip fee.

== Changelog ==
= 1.0.0 =
* Initial release.
