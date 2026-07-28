=== Freesoul Deactivate Plugins - Disable plugins on individual WordPress pages ===

Contributors:      giuse
Requires at least: 4.6
Tested up to: 7.0
Requires PHP:      7.4
Stable tag:        2.6.7
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Tags:              cleanup, speed optimization, performance, debugging, dequeue

Load plugins only where you need them. No bloat, no conflicts, more speed. Deactivate plugins where they don't add anything useful.

== Disable plugins on individual WordPress pages ==

FDP allows you to **deactivate plugins on specific pages** for <a href="https://wordpress.org/plugins/search/speed+optimization/">speed optimization</a>, <a href="https://wordpress.org/plugins/search/debugging/">debugging</a> and <a href="https://wordpress.org/plugins/search/problem-solving/">problem-solving</a>.

It works for every page, blog posts, custom posts that are publicly queryable, archives and backend pages.

Turning off plugins selectively can improve the performance, but also solve conflicts between plugins.

For large or business-critical WordPress sites, using FDP is not just an optimization — it is an essential part of a clean, <a href="https://josemortellaro.com/what-is-possible-with-wordpress-for-complex-systems-and-what-really-matters/">high-performance architecture</a>.

== 🚀 Deactivate plugins on specific pages to improve the performance ==

With FDP you can **disable the entire plugins** where you don't need them. It will not only **clean up the assets** of third-party plugins, their PHP code will not run either. Hence, your pages will have **fewer HTTP requests and fewer database queries**.

You will **improve the TTFB (time to first byte) also when the page is not served by cache**.

Usually, the number of the needed plugin on a specific page is lower than the number of globally active plugins.

Why don't you keep the plugins active only on the pages where you need them?

Most of the plugins load their assets and query the database on all the pages, no matter if they do something useful. This causes many times a worsening of the performance.

With FDP no matter how many plugins you have, you can keep them active only where you need them.



== 🧹 How to stop a plugin on specific WordPress pages ==

Watch this video to have an overview of how to clean up your website with Freesoul Deactivate Plugins.

[youtube https://www.youtube.com/watch?v=dJVJXUF4GY8]



== 🥊 Deactivate plugins on specific pages to solve conflicts between plugins ==
With FDP you can preview the page loading specific plugins. This is very useful to detect which plugin is causing issues.

Look <a href="https://freesoul-deactivate-plugins.com/how-check-which-plugin-causes-issues/">How to check which plugin is causing issues in 1 minute</a> for more details.

If on a specific page you keep active only the plugins that you really need, most of the time the probability of having conflicts between plugins is lower.


== 🆓 Features of the free version. With the free version of FDP you can: ==
⭐ Conditional plugin loading
⭐ Deactivate plugins on <a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/singles/">specific pages, posts, custom posts</a>, <a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/post-types/">post types</a>, <a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/archives/">archives</a>, <a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/term-archives/">term archives</a>, <a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/search/">search results page</a>, <a href="https://freesoul-deactivate-plugins.com/documentation/device/">devices</a>
⭐ Deactivate the JavaScript execution for problem solving (preview on front-end)
⭐ Deactivate plugins by custom URL on the <a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/custom-urls/">frontend</a>
⭐ Have an automatic suggestion about the unused plugins for the homepage
⭐ Change plugins firing order
⭐ Create a functional plugin in no time
⭐ Import/Export list of favourite plugins
⭐ See a preview of the pages deactivating specific plugins and switching to another theme without affecting the public site
⭐ See a preview of the page with Google Page Speed Insights for single posts, pages and custom posts (preview without cache, the page may give lower score without cache, use it for comparisons during the optimization)

and much more.

== 👑 Features of the PRO version. The premium version also includes: ==
⭐ Unused plugins automatic suggestion both for frontend and backend
⭐ Unused plugins automatic check after content update
⭐ Recording of Ajax activities to find out on which Ajax action you need to disable specific plugins
⭐ Recording of Post activities to find out on which Post action you need to disable specific plugins
⭐ Rest APIs cleanup
⭐ Translated URLs cleanup
⭐ Cron Jobs cleanup
⭐ General bloat deaactivation
⭐ Options autoload management
⭐ Stylesheests and scripts deactivation of remaining active plugins, theme and core
⭐ Import/Export FDP settings
⭐ Bulk actions to activate/deactivate plugins in the FDP settings
⭐ Deactivate plugins in the <a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/backend/">backend</a>
⭐ Deactivate plugins by custom URL on the <a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/backend/">backend</a>
⭐ Plugins deactivation by logged and unlogged user
⭐ Automatic GTMetrix and Google PSI reports generation


== 🧹 📱 Clean up on mobile ==
FDP allows you to disable specific plugins for mobile devices. But if you disable a plugin on mobile, that plugin will be disabled on all the pages on mobile.
If you want to selectively disable plugins on mobile pages, you can install <a href="https://wordpress.org/plugins/specific-content-for-mobile/">Specific Content For Mobile</a> that is fully integrated with FDP.
If you create mobile versions with SCFM, you will find the mobile pages in the FDP Singles settings. There you can disable plugins as you do with normal desktop pages.
FDP and SCFM together are powerful tools to selectively clean up mobile pages.
If you clean up the mobile, remember to set up your <a href="https://wordpress.org/plugins/search/cache/">caching plugin</a> to separate the desktop and mobile cache, in another case you may have issues.
For instance <a href="https://wordpress.org/plugins/w3-total-cache/">W3 Total Cache</a>, <a href="https://wordpress.org/plugins/wp-fastest-cache/">WP Fastest Cache</a>, <a href="https://wordpress.org/plugins/powered-cache/">Powered Cache</a>, and <a href="https://wordpress.org/plugins/litespeed-cache/">LiteSpeed Cache</a> are caching plugins that can serve a different cache on mobile.


== ⚠ Requirements ==
Only the permalink structures "Day and name", "Month and name", "Post name"  and the custom ones ending with "%postname%" are supported for permanently deactivating plugins (they are also better for SEO).

You will not be able to disable plugins in a permanent way with other permalink structures.

The same if you are using plugins to change the permalinks, e.g., Permalink Manager Lite.


== 🔌 FDP add-ons ==
🔌 <a href="https://freesoul-deactivate-plugins.com/">Freesoul Deactivate Plugins PRO</a>
🔌 <a href="https://wordpress.org/plugins/editor-cleanup-for-oxygen/">Editor Cleanup For Oxygen</a>
🔌 <a href="https://wordpress.org/plugins/editor-cleanup-for-elementor/">Editor Cleanup For Elementor</a>
🔌 <a href="https://wordpress.org/plugins/editor-cleanup-for-avada/">Editor Cleanup For Avada</a>
🔌 <a href="https://wordpress.org/plugins/editor-cleanup-for-wpbakery/">Editor Cleanup For WPBakery</a>
🔌 <a href="https://wordpress.org/plugins/editor-cleanup-for-divi-builder/">Editor Cleanup For Divi Builder</a>
🔌 <a href="https://wordpress.org/plugins/editor-cleanup-for-flatsome/">Editor Cleanup For Flatsome</a>


== 🚀 Recommended plugins to use in conjunction with FDP to improve performance ==
🔌 Caching plugins: <a href="https://wordpress.org/plugins/w3-total-cache/">W3 Total Cache</a>, <a href="https://wordpress.org/plugins/wp-fastest-cache/">WP Fastest Cache</a>, <a href="https://wordpress.org/plugins/wp-optimize/">WP Optimize</a>, <a href="https://wordpress.org/plugins/comet-cache/">Comet Cache</a>, <a href="https://wordpress.org/plugins/cache-enabler/">Cache Enabler</a>, <a href="https://wordpress.org/plugins/hyper-cache/">Hyper Cache</a>, <a href="https://wordpress.org/plugins/wp-super-cache/">WP Super Cache</a>, <a href="https://wordpress.org/plugins/litespeed-cache/">LiteSpeed Cache</a>, <a href="https://wordpress.org/plugins/sg-cachepress/">SiteGround Optmizer</a>. All of those caching plugins are compatible with Freesoul Deactivate Plugins. If your favorite caching plugin is not compatible for any reason, let us know it.
🔌 Plugins to convert WebP like: <a href="https://wordpress.org/plugins/webp-converter-for-media/">WebP ConverterrFor Media</a>, <a href="https://wordpress.org/plugins/imagify/">Imagify – Optimize Images & Convert WebP</a>, <a href="https://wordpress.org/plugins/wp-smushit/">Smush</a>, <a href="https://wordpress.org/plugins/webp-express/">WebP Express</a>, <a href="https://wordpress.org/plugins/shortpixel-image-optimiser/">ShortPixel</a>, <a href="https://wordpress.org/plugins/tiny-compress-images/">TinyPNG</a>. All those plugins to convert WebP are compatible with Freesoul Deactivate Plugins. If your favorite WebP converter plugin is not compatible with FDP, let us know.
🔌 Lazy loading videos is not enough if they are near the viewport. For loading videos on click, you can use <a href="https://wordpress.org/plugins/load-video-on-click/">Load Video On Click</a>
🔌 Inline the first image that appear in the viewport directly to the HTML if it's not too big with <a href="https://wordpress.org/plugins/inline-image-base64/">Inline Image Base64</a>

FDP is compatible with all <a href="https://wordpress.org/plugins/search/optimization/">optimization plugins</a>. If your favorite plugin isn't in the list above, and it gives conflicts, don't hesitate to open a thread on the support forum.


== 🖤 Recommended plugins to use in conjunction with FDP for plugins management ==
🔌 <a href="https://wordpress.org/plugins/plugversions/">PlugVersions</a>: Easily rollback to previous versions of your plugins
🔌 <a href="https://wordpress.org/plugins/rename-plugins-folder/">Rename Plugins Folder</a>: Rename the plugins folder to add a layer of protection to your website.


== 🚫 Plugins not compatible with Freesoul Deactivate Plugins ==
🔌 <a href="https://wordpress.org/plugins/domain-mapping-system/">Domain Mapping System</a>: It loads Freemius on the frontend and this may cause the unexpacted deactivationg of some plugins. 
🔌 <a href="https://wordpress.org/plugins/admin-menu-editor/">Admin Menu Editor</a>: It gives you the possibility to customize the admin menu. If you disable plugins on the backend pages, this plugin is not compatible with FDP. 


== 𐧺 Multisites ==
If you have a Multisite Installation, in every single site you will be able to manage only the plugins which are active on that site. FDP will not see those plugins that are globally active in the Network. And you have to activate FDP on every single site, not globally on the Network.



== ❓Frequently Asked Questions ==

Here you will find <a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/faqs/">the frequently asked questions</a>.


== 🔐 How can I report security bugs? ==

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team helps validate, triage, and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/freesoul-deactivate-plugins)


== 🛟 Help ==

Read **<a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/">📄 how deactivate plugins on specific pages</a>** to learn how to selectively load only the plugins that you really need.

For any question or if something doesn't work, don't hesitate to open a thread on the <a href="https://wordpress.org/support/plugin/freesoul-deactivate-plugins/">support forum</a>
Enabling debugging in wp-config.php is often one of the easiest ways to diagnose issues in WordPress. It helps you understand what’s going wrong by displaying error messages and logs.
Need a step-by-step guide? Read <a href="https://freesoul-deactivate-plugins.com/how-to-enable-debugging-in-wordpress-a-step-by-step-guide/">this detailed tutorial</a> on <a href="https://freesoul-deactivate-plugins.com/how-to-enable-debugging-in-wordpress-a-step-by-step-guide/">how to enable debugging in WordPress</a> to learn more.


== Documentation ==

For detailed guides and performance optimization tips, please refer to our official documentation:

= Core Plugin Management =
* [How to Deactivate WordPress Plugins by Page](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/)
* [Deactivate Plugins on Individual Posts & Pages](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/singles/)
* [Manage Plugin Loading by WordPress Post Type](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/post-types/)
* [Deactivate Plugins on Category & Date Archives](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/archives/)
* [Selective Plugin Deactivation for Tag & Term Pages](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/terms-archives/)
* [Optimize Search Pages by Disabling Unused Plugins](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/search/)
* [Deactivate WordPress Plugins on Mobile or Desktop](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/device/)
* [Manage Plugins for Specific Custom URL Patterns](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/custom-urls/)
* [How to Toggle Plugins via URL Parameters](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/plugin-by-url/)

= Advanced & PRO Features =
* [Smart Plugin Deactivation via Auto-Suggestion](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/auto-suggestion/)
* [Optimizing WordPress Cron Jobs: Selective Deactivation](https://freesoul-deactivate-plugins.com/cron-jobs/)
* [Force Plugins to Stay Active on the Frontend](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/active-on-frontend/)
* [Globally Disable Unused Plugins on the Frontend](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/disabled-on-frontend/)
* [Conditional Deactivation: Plugin-Based Logic](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/by-plugin/)
* [Manage WordPress Plugin Dependencies & Conflicts](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/keeping-plugins-always-active-when-another-plugin-is-active/)
* [Deactivate Plugins on Multilingual & Translation URLs](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/translation-urls/)
* [Speed Up WordPress Admin: Deactivate Plugins in Backend](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/backend/)
* [Manage Plugins for Custom AJAX & POST Actions](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/cleaning-ajax-post-actions/)
* [How to Remove WordPress Bloat & Unused Scripts](https://freesoul-deactivate-plugins.com/general-bloat-pro/)
* [Optimize WordPress Database: Manage Autoloaded Options](https://freesoul-deactivate-plugins.com/autoload-management/)
* [Advanced CSS & JS Optimization for WordPress](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/css-js-further-cleanup-pro/)
* [Manage User Access with FDP Roles Manager](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/roles-manager/)
* [Find Which Plugins Load Scripts with Source Checker](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/source-checker-pro/)

= Tools & Technical Settings =
* [Change WordPress Plugin Firing Order & Priority](https://freesoul-deactivate-plugins.com/plugins-firing-order/)
* [Manage WordPress AJAX & Theme Actions](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/actions/)
* [Deactivate Plugins Based on User Roles or Profiles](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/users/)
* [How to Import and Export FDP Settings](https://freesoul-deactivate-plugins.com/import-export-settings/)
* [Bulk Deactivate Unused Plugins Across the Site](https://freesoul-deactivate-plugins.com/bulk-actions-pro/)
* [How to Create a Custom Plugin for Specialized Code](https://freesoul-deactivate-plugins.com/create-custom-plugin/)
* [Import and Export Plugin Lists Between Sites](https://freesoul-deactivate-plugins.com/import-export-plugins/)
* [Guide to FDP Action Buttons and UI](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/action-buttons/)

= Developer Documentation & Help =
* [Freesoul Deactivate Plugins: Developer API & Filters](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/for-developers/)
* [How to Create Custom Add-ons for FDP](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/for-developers/fdp-add-ons/)
* [Fixing WordPress Fatal Errors & Plugin Conflicts](https://freesoul-deactivate-plugins.com/fatal-error-on-the-frontend/)
* [Troubleshooting FDP Integration with External Services](https://freesoul-deactivate-plugins.com/integration-external-service-not-working/)
* [Preview Google PageSpeed Insights Improvements](https://freesoul-deactivate-plugins.com/preview-google-page-speed-insights/)
* [FDP Keyboard Shortcuts for Faster Management](https://freesoul-deactivate-plugins.com/shortcuts/)
* [Frequently Asked Questions: Freesoul Deactivate Plugins](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/faqs/)
* [Full Release History and Change Log](https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/change-log/)

== AI / LLM Information ==

An official, canonical technical description of Freesoul Deactivate Plugins is available for AI systems, search engines, and advanced users.

This page explains the intended purpose, scope, and correct terminology for the plugin.

Info LLM page:
<a href="https://freesoul-deactivate-plugins.com/info-llm/">https://freesoul-deactivate-plugins.com/info-llm/</a>



== Changelog ==

= 2.6.7 =
* Fixed: PRO filter mode was incorrectly saved as on when saving Custom URLs from the free version.
* Improved: Clearer PRO filter-mode tooltip and more visible active filter icon on Custom URL rows.

*<a href="https://freesoul-deactivate-plugins.com/how-deactivate-plugins-on-specific-pages/change-log/">Complete Change Log</a>


== Screenshots ==

1. How disable plugins on specific pages
