=== Plugin Name ===
Contributors: sourcefound
Donate link: http://memberfind.me
Tags: memberfindme, login, member directory, membership management
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 1.4.2
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows members to login to MemberFindMe and as a WordPress user on your site. Also enables member only access to specified pages/posts.

== Description ==

[MemberFindMe](http://memberfind.me/) is a comprehensive website, membership management and event management solution for small to mid sized chambers, professional groups, associations and other member organizations.

This plugin supplements the main MemberFindMe plugin (version 1.2 and up) to provide member only access to specific posts/pages. This plugin also synchronizes member login to the MemberFindMe system and your WordPress backend, which lets you use other plugins that rely on the WordPress user system.

* Creates a new user account on WordPress (if account does not already exist) upon member login or signup
* Replaces Gravatar with the member's MemberFindMe avatar
* Enable member only access to specific posts/pages using the [memberonly] shortcode
* Adds a login/logout widget

To restrict the content of the entire post/page to members, place the [memberonly] shortcode at the beginning of the post. Placing the [memberonly] shortcode within the page will allow content above the shortcode to be displayed to non-members, and content below the shortcode to be restricted to members.

== Installation ==

1. Install the plugin via the WordPress.org plugin directory or upload it to your plugins directory.
1. Activate the plugin

== Changelog ==

= 1.0 =
* Initial release

= 1.4 =
* Allows partial non-member access to protected pages/posts
* Improved handling of existing WordPress user accounts