=== WP All Import – Import SEO Settings for Rank Math SEO ===
Contributors: soflyy, wpallimport
Tags: seo, import seo, search engine optimization, import search engine optimization, import rankmath
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 1.2

Drag & drop to import from any CSV, Excel, XML, or Google Sheets file into Rank Math SEO's titles, meta descriptions, focus keywords, schema settings and more.

== Description ==

The Rank Math Add-On for [WP All Import](http://wordpress.org/plugins/wp-all-import "WordPress XML & CSV Import") makes it easy to bulk import your SEO setting to the WordPress SEO Plugin – Rank Math plugin in less than 10 minutes.

The left side shows all of the fields that you can import to and the right side displays the data from your XML/CSV file. Then you can simply drag & drop the data from your XML or CSV into the WordPress SEO Plugin – Rank Math fields to import them.

The importer is so intuitive it is almost like manually configuring Rank Math when adding/editing a post.

It's easy to begin with our [Quickstart Guide](https://www.wpallimport.com/documentation/getting-started/importing-an-xml-or-csv-file/).

= Why you should use the Rank Math Add-On for WP All Import =

* Import new posts, pages, or custom post types and configure their SEO settings all in the same import.

* Update SEO settings for existing posts, pages, or custom post types already published on your site with [Bulk Edit](https://www.wpallimport.com/bulk-edit-wordpress-data/).

* Compatible with all other [WP All Import add-ons](https://www.wpallimport.com/add-ons/).

* Complete support - configure all available SEO settings for your posts, including social media images.

= WP All Import Professional Edition =

The Rank Math Add-On for WP All Import is fully compatible with [the free version of WP All Import](http://wordpress.org/plugins/wp-all-import "WordPress XML & CSV Import"). 

However, [the professional edition of WP All Import](https://www.wpallimport.com/pricing/?utm_source=free-plugin&utm_medium=dot-org&utm_campaign=rankmath-seo) includes premium support and adds the following features:

* Import files from a URL: Download and import files from external websites, even if they are password protected with HTTP authentication. 

* Cron Job/Recurring Imports: WP All Import Pro can periodically check a file for updates then add, edit, delete, and update your posts.

* Custom PHP Functions: Pass your data through custom functions by using [my_function({data[1]})] in your import template. WP All Import will pass the value of {data[1]} through my_function and use whatever it returns.

* Access to premium technical support.

[Upgrade to the professional edition of WP All Import now.](https://www.wpallimport.com/pricing/?utm_source=free-plugin&utm_medium=dot-org&utm_campaign=rankmath-seo)

= Developers: Create Your Own Add-On =
This Add-On was created using the [Rapid Add-On API](https://www.wpallimport.com/documentation/addon-dev/overview/) for WP All Import. We've made it really easy to write your own Add-On.

= Related Plugins =
[Import Settings into All In One SEO](https://wordpress.org/plugins/import-xml-csv-settings-to-all-in-one-seo-pack/)  
[Import Settings into WordPress SEO by Yoast](https://wordpress.org/plugins/yoast-seo-settings-xml-csv-import/)

== Installation ==

First, install [WP All Import](http://wordpress.org/plugins/wp-all-import "WordPress XML & CSV Import").

Then install the Rank Math Add-On.

To install the Rank Math Add-On, either:

* Install the plugin from the Plugins > Add New page in WordPress

* Unzip the downloaded Rank Math Add-On's .zip archive and upload the contents to /wp-content/plugins/, and then activate the plugin from the Plugins page in WordPress

The Rank Math Add-On will appear in the Step 3 of [WP All Import](https://www.wpallimport.com/wordpress-xml-csv-import/).

== Changelog ==

= 1.2 =
* improvement: use WP All Import's built-in RapidAddon instead of a bundled copy
* improvement: set the primary term on import for any post type, not just WooCommerce products
* bug fix: correctly detect post and taxonomy type for new imports
* bug fix: prevent a fatal error when WP All Import is not active
* bug fix: schema field labels not hidden when Schema Type is set to None
* bug fix: don't set Product "Price Valid Until" and other date fields to 1970-01-01 when the source value is empty
* bug fix: don't create an "Off" schema when the Schema Type is set to None
* bug fix: remove empty values from generated schema so Rank Math falls back to its defaults
* bug fix: prevent "Undefined array key" notices when importing schema with unmapped fields

= 1.1.0 =
* improvement: add support for new schema format implemented by Rank Math

= 1.0.0 =
* Initial release on WP.org.

== Support ==

We do not handle support in the WordPress.org community forums.

We do try to handle support for our free version users at the following e-mail address:

E-mail: support@wpallimport.com

Support for free version customers is not guaranteed and based on ability. For premium support, purchase [WP All Import Pro](https://www.wpallimport.com/pricing/?utm_source=free-plugin&utm_medium=dot-org&utm_campaign=rankmath-seo).
