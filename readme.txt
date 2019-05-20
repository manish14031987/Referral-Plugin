=== mojoreferral Link Creator ===
Contributors: er.manish.developer
Website Link: 
Tags: mojoreferral, shortlink, custom URL
Requires at least: 3.6
Tested up to: 4.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates a custom short URL when saving posts. Requires your own mojoreferral install.

== Description ==

Creates a mojoreferral generated referral link and shortlink on demand or on user creation.

Features:

*   Optional custom keyword for link creation.
*   Will retrieve existing URL if one has already been created.
*   Click count appears on post menu
*   Available for standard posts and custom post types.
*   Optional filter for wp_shortlink
*   Built in cron job will fetch updated click counts every hour.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `mojoreferral-link-creator` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the "mojoreferral Settings" option in the Settings Menu.
4. Enter your mojoreferral custom URL and API key
5. Enjoy!

== Frequently Asked Questions ==


= What's this all about? =

This plugin creates a shortlink (stored in the post meta table) for each post that can be used in sharing buttons, etc.

= What is mojoreferral? =

mojoreferral is a self-hosted PHP based application that allows you to make your own custom shortlinks, similar to bit.ly and j.mp. [Learn more about it here](http://mojoreferral.org/ "mojoreferral download")

= How do I use the template tag? =

Place the following code in your theme file (usually single.php) `<?php mojoreferral_display_box(); ?>`

= The delete function doesn't remove the short URL from my mojoreferral installation =

This is a limitation with the mojoreferral API, as there is not a method yet to delete a link. The delete function has been added to the plugin to allow users to get the updated URL that they may have changed in the mojoreferral admin panel

== Screenshots ==

1. Metabox to create mojoreferral link with optional keyword field
2. Example of a post with a created link and click count
3. Post column displaying click count
4. Settings page



== Changelog ==

= 1.0 =
* First release!


== Upgrade Notice ==

= 1.06 =
* The mojoreferral metabox will not appear until a post has been published. This is to prevent empty or otherwise incorrect URLs from getting created.

= 1.0 =
* First release!
