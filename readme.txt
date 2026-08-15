=== Musomo Quote ===
Contributors: musomo
Donate link: https://ko-fi.com/musomo
Tags: woocommerce, quote request, request a quote, contact form 7, product inquiry
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a customizable product quote request workflow to WooCommerce using Contact Form 7.

== Description ==

Musomo Quote adds a lightweight, customizable quote request workflow to WooCommerce product pages. Customers can open a product-aware form and send an inquiry using Contact Form 7, while the store owner keeps control of product eligibility, appearance, text, security options, and email templates.

The plugin does not create a separate customer-submission database. Contact Form 7 remains responsible for form validation, submission, and email delivery.

= Main features =

* Add a quote button alongside Add to cart.
* Replace Add to cart with a quote request on eligible products.
* Enable quote requests only for selected products.
* Support simple and variable WooCommerce products.
* Pass product name, ID, SKU, URL, image, price, type, variation, and quantity to Contact Form 7.
* Customize the quote button and modal appearance.
* Edit frontend labels and messages.
* Apply restrictions by category, stock status, price, product type, and user login status.
* Use optional honeypot, time-trap, content-filter, and rate-protection features.
* Generate Contact Form 7 form code and HTML email templates.
* Configure multilingual text and form overrides when Polylang is active.
* Export, import, diagnose, and safely reset Musomo Quote settings.

= Contact Form 7 integration =

Musomo Quote uses a Contact Form 7 form for quote submissions. Its template builder can generate form markup and an HTML email template containing product and customer information. The plugin never overwrites a Contact Form 7 form automatically: the generated code is copied and pasted by the site administrator.

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* WooCommerce 7.0 or higher
* Contact Form 7 for quote form submissions
* Polylang is optional

= Privacy =

Quote submissions are handled by Contact Form 7. Musomo Quote does not maintain its own customer-submission database or permanent raw IP log. Optional rate protection uses a temporary transient derived from a hashed IP address.

For documentation and setup guidance, visit the [Musomo Quote plugin page](https://musomo.net/musomo-quote-woocommerce-plugin/).

== Installation ==

1. Upload the `musomo-quote` folder to `/wp-content/plugins/`, or install the ZIP through Plugins > Add New > Upload Plugin.
2. Activate WooCommerce and Contact Form 7.
3. Activate Musomo Quote.
4. Open Musomo Quote > Settings and select a Contact Form 7 form.
5. Choose the quote mode and configure appearance, texts, restrictions, and templates.
6. Test the complete quote request flow on a WooCommerce product page.

== Frequently Asked Questions ==

= Does Musomo Quote replace WooCommerce? =

No. It adds a quote request workflow to WooCommerce products.

= Is Contact Form 7 required? =

Contact Form 7 is required to submit the quote form and send its email. Musomo Quote supplies product context and configuration around that form.

= Can I keep the Add to cart button? =

Yes. ADD mode displays both actions. REPLACE mode substitutes the purchase action on eligible products, while SELECTED mode limits quote requests to products explicitly enabled by the administrator.

= Does it support variable products? =

Yes. The selected variation and quantity can be passed to the quote request.

= Can I customize the button and quote window? =

Yes. You can inherit the theme style, use the Musomo preset, or configure custom colors, sizes, spacing, and modal options.

= Does it support multilingual stores? =

Yes. When Polylang is active, Musomo Quote can use language-specific text and Contact Form 7 form overrides. Polylang is optional.

= Does the plugin store customer quote requests? =

No. Contact Form 7 handles submissions and email delivery. Musomo Quote does not create a separate customer-request database.

== Screenshots ==

1. Quote request button on a WooCommerce product page.
2. Product-aware quote request modal.
3. Setup dashboard and integration status.
4. General quote mode and Contact Form 7 settings.
5. Button and modal appearance controls with live preview.
6. Editable global and multilingual frontend text.
7. Product visibility and quote restrictions.
8. Security and anti-spam configuration.
9. Contact Form 7 form template builder and preview.
10. HTML email template builder and preview.

== Changelog ==

= 2.0.0 =

* Initial public release.
* Added WooCommerce quote modes, Contact Form 7 integration, appearance controls, restrictions, multilingual overrides, security options, template builders, diagnostics, and settings tools.
* Added WordPress.org-ready readme metadata for the 2.0.0 presentation package.

== Upgrade Notice ==

= 2.0.0 =

Initial public release of Musomo Quote.
