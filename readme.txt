=== Plugin Name ===
Contributors: sourcefound
Donate link: http://memberfind.me
Tags: memberfindme, membership management, membership, member login, billing, member access, member content
Requires at least: 3.0.1
Tested up to: 3.9.1
Stable tag: 2.2
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows members to login to MemberFindMe and as a WordPress user on your site. Also enables member only access to specified pages/posts.

== Description ==

[MemberFindMe](http://memberfind.me/) is a comprehensive website, membership management and event management solution for small to mid sized chambers, professional groups, associations and other member organizations.

This plugin supplements the main MemberFindMe plugin (version 1.2 and up) to provide member only access to specific posts/pages. This plugin also synchronizes member login to the MemberFindMe system and your WordPress backend, which lets you use other plugins that rely on the WordPress user system.

* Creates a new user account on WordPress (if account does not already exist) upon member login or signup
* Replaces Gravatar with the member's MemberFindMe avatar
* Enable member only access to specific posts/pages
* Restrict access by membership level or label
* Adds a login/logout widget

To restrict the content of the entire post/page to members, place the [memberonly] shortcode at the beginning of the post. Placing the [memberonly] shortcode within the page will allow content above the shortcode to be displayed to non-members, and content below the shortcode to be restricted to members.

To modify the message that is displayed for non-members, add a message option to the shortcode, ie. [memberonly message="..."].

To restrict access to certain membership levels or labels, add a label option to the shortcode, ie. [memberonly label="..."]. For example [memberonly label="business member"]. You can restrict content to more than 1 label by separating the labels with a comma, for example [memberonly label="membership committee,marketing committee"]. If your label or membership level name contains a comma, you should escape the comma, for example [memberonly label="label%2C1,label%2C2"]. 

To redirect non-members to a different page: [memberonly nonmember-redirect="..."]

To display a membership sign-up form for non-members: [memberonly nonmember="account/join"]

To display a MemberFindMe form or cart for non-members:[memberonly nonmember="!form/..."]

When displaying a membership sign-up form or MemberFindMe form/cart for non-members, the current page will be reloaded to display the member only content after the visitor completes the form and becomes a member. But if you prefer to redirect to a different page instead, add a redirect option. For example: [memberonly nonmember="account/join" redirect="http://www.abc.com/welcome"]

== Installation ==

1. Install the plugin via the WordPress.org plugin directory or upload it to your plugins directory.
1. Activate the plugin

== Changelog ==

= 1.0 =
* Initial release

= 1.4 =
* Allows partial non-member access to protected pages/posts
* Improved handling of existing WordPress user accounts

= 1.6 =
* Allows restricting access by membership level or label

= 1.7 =
* Allows administrator to see member only content

= 1.8 =
* Improved handling of email conflicts

= 2.0 = 
* Allows members to request password
* No longer redirects members to WordPress login page if incorrect email or password is entered
* Adds nonmember-redirect option
* Adds nonmember option
* Adds message option
* Adds redirect option

= 2.1 =
* Adds support for redirect on logout
* Fixes issue with ajax login on some sites

= 2.2 =
* Prevents expired members from viewing member only content