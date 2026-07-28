=== User Role Editor Pro ===
Contributors: Vladimir Garagulya (https://www.role-editor.com)
Tags: user, role, editor, security, access, permission, capability
Requires at least: 4.4
Tested up to: 7.0
Stable tag: 4.65
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.role-editor.com/end-user-license-agreement/

User Role Editor Pro WordPress plugin makes user roles and capabilities changing easy. Edit/add/delete WordPress user roles and capabilities.

== Description ==

User Role Editor Pro WordPress plugin allows you to change user roles and capabilities easy.
Just turn on check boxes of capabilities you wish to add to the selected role and click "Update" button to save your changes. That's done. 
Add new roles and customize its capabilities according to your needs, from scratch of as a copy of other existing role. 
Unnecessary self-made role can be deleted if there are no users whom such role is assigned.
Role assigned every new created user by default may be changed too.
Capabilities could be assigned on per user basis. Multiple roles could be assigned to user simultaneously.
You can add new capabilities and remove unnecessary capabilities which could be left from uninstalled plugins.
Multi-site support is provided.

== Installation ==

Installation procedure:

1. Deactivate plugin if you have the previous version installed.
2. Extract "user-role-editor-pro.zip" archive content to the "/wp-content/plugins/user-role-editor-pro" directory.
3. Activate "User Role Editor Pro" plugin via 'Plugins' menu in WordPress admin menu. 
4. Go to the "Settings"-"User Role Editor" and adjust plugin options according to your needs. For WordPress multisite URE options page is located under Network Admin Settings menu.
5. Go to the "Users"-"User Role Editor" menu item and change WordPress roles and capabilities according to your needs.

In case you have a free version of User Role Editor installed: 
Pro version includes its own copy of a free version (or the core of a User Role Editor). So you should deactivate free version and can remove it before installing of a Pro version. 
The only thing that you should remember is that both versions (free and Pro) use the same place to store their settings data. 
So if you delete free version via WordPress Plugins Delete link, plugin will delete automatically its settings data. Changes made to the roles will stay unchanged.
You will have to configure lost part of the settings at the User Role Editor Pro Settings page again after that.
Right decision in this case is to delete free version folder (user-role-editor) after deactivation via FTP, not via WordPress.

== Changelog ==
= [4.65] 21.05.2026 =
* Core version: 4.65
* Update: Marked as compatible with WordPress 7.0
* Update: Minor fixes to pages markup are applied to correspond WordPress 7.0 CSS changes. 
* Update: "defined('ABSPATH')" guard was added to all PHP files to exclude PHP files direct execution. 
* Update: sanitize_text_field(), sanitize_key(), sanitize_url() functions are used to secure user input before processing.
* Update: _nonce field checking was added before data update in addition checking made already on the higher level.
* Core version was updated to 4.65
* Fix: Users->User Role Editor->Import: single user role was imported successfully but the empty page was shown instead of URE page with successful import notification.
* Fix: Meta Boxes Access add-on: WP Multisite: Network Admin->Users->User Role Editor->Meta Boxes: current role does not lose now all capabilities after the 'Update' button click.
* Fix: Other Roles Access add-on: WP Multisite: Network Admin->Users->User Role Editor->Other Roles: current role does not lose now all capabilities after the 'Update' button click.
* Fix: Posts Edit Access add-on: WP Multisite: Network Admin->Users->User Role Editor->Posts Edit: current role does not lose now all capabilities after the 'Update' button click.
* Fix: Plugins Access add-on: WP Multisite: Network Admin->Users->User Role Editor->Plugins: current role does not lose now all capabilities after the 'Update' button click.
* Fix: Export roles CSV download file with .pdf extension in the FireFox browser. Content type header was replaced to 'text/plain'.
* Update: Meta Boxes Access add-on: data is updated via AJAX without full page refresh now.
* Update: Other Roles Access add-on: data is updated via AJAX without full page refresh now.
* Update: Posts Edit Access add-on: data for role is updated without full page refresh  via AJAX now.
* Update: Plugins Access add-on: data is updated via AJAX without full page refresh  now.
* Update: Import role CSV: uploaded file .csv extention and mime type checking were added.
* Update: "Users->User Role Editor->Import" button is hidden in case page is opened from the WP Multisite -> Network admin. Use it from the selected single site only.

= [4.64.6] 03.12.2025 =
* Core version: 4.64.6
* Update: Marked as compatible with WordPress 6.9
* Update: Gravity Forms Access add-on: Form switcher drop-down list includes only forms allowed for the current user.
* Core version was updated to 4.64.6
* Update: Minor code enhancements according to the "Plugin Check" tool recommendations.
* Update: "Users->Grant Roles" HTML code download optimization to exclude cases when URE's "Grant Roles" data flickers or stays visible while Users page is opening.

Full list of changes is available in changelog.txt file.
