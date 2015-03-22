=== WordPress Access Areas ===
Contributors: podpirate
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WF4Z3HU93XYJA
Tags: access, role, capability, user, security, editor
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 1.4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fine tuning access to your posts.

== Description ==

WP Access Areas lets you fine-tune who may read, edit or comment on your Blog posts.
You can either restrict access to logged-in uses only, certain WordPress-Roles or 
even custom Access Areas.

= Features =
- Define custom Access Areas and assign them to your blog-users
- Restrict reading, editing and commenting permission to logged-in users, certain WordPress-Roles or Access Areas
- define global access areas on a network
- Supports bulk editing
- German, Italian, Polish and Swedish localization (Huge Thankyou @ all translators!)

Latest files on [GitHub](https://github.com/mcguffin/wp-access-areas).

Developers might like to have a look at [the project wiki](https://github.com/mcguffin/wp-access-areas/wiki).

= Known Issues =
- WordPress calendar Widget still shows dates where even restricted posts have been created. 
  When clicked on such a date a 404 will occur. There already is a 
  [WordPress Core ticket on that issue](https://core.trac.wordpress.org/ticket/29319) but the 
  proposed patch will not make it into WP 4.1. Lets put some hope into 4.2.
- Taxonomy menus (e.g. Tags / Categories) also count restricted posts when the total number 
  of posts in a taxonomy is ascertained. 
  See [this post](http://wordpress.org/support/topic/archive-recents-posts-last-comments-show-restricted-content?replies=5#post-5929330) for details.

== Installation ==

1. Upload the 'wp-access-areas.zip' to the `/wp-content/plugins/` directory and unzip it. 

2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently asked questions ==

= What does it exactly do? =

For each Post it stores a capabilty the user needs to have in order to view, edit or comment on a post. 
By defining an Access Area you create nothing more than a custom capability. 

= Why didn't you use post_meta to store permissions? WordPress already provides an API for this! =

I did this mainly for performance reason. For detecting the reading-permission on specific content, 
the plugin mainly affects the WHERE clause used to retrieve posts. In most cases, using post_meta 
would mean to add lots of JOIN clauses to the database query, slowing down your site's performance.

= Does it mess up my database? =

It makes changes to your database, but it won't make a mess out of it. Upon install it does two things:
1. It creates a table named ´{$wp_prefix}_disclosure_userlabels´. The access areas you define are here.
2. It adds three columns to Your Posts tables: post_view_cap and post_comment_cap. 

Upon uninstall these changes will be removed completely, as well as it will remove any custom generated 
capability from your user's profiles.

= I'd like to do some more magic / science with it. And yes: I can code! =

Developer documentation can be found in [the project wiki](https://github.com/mcguffin/wp-access-areas/wiki).

= I found a bug. Where should I post it? =

I personally prefer GitHub but you can post it in the forum as well. The plugin code is here: [GitHub](https://github.com/mcguffin/wp-access-areas)

= I want to use the latest files. How can I do this? =

Use the GitHub Repo rather than the WordPress Plugin. Do as follows:

1. If you haven't already done: [Install git](https://help.github.com/articles/set-up-git)

2. in the console cd into Your 'wp-content/plugins´ directory

3. type `git clone git@github.com:mcguffin/wp-access-areas.git`

4. If you want to update to the latest files (be careful, might be untested on Your WP-Version) type git pull´.

Please note that the GitHub repository is more likely to contain unstable and untested code. Urgent fixes 
concerning stability or security (like crashes, vulnerabilities and alike) are more likely to be fixed in 
the official WP plugin repository first.

= I found a bug and fixed it. How can I contribute? =

Either post it on [GitHub](https://github.com/mcguffin/wp-access-areas) or—if you are working on a cloned repository—send me a pull request.

= Will you accept translations? =

Yep sure! (And a warm thankyou in advance.) It might take some time until your localization 
will appear in an official plugin release, and it is not unlikely that I will have added 
or removed some strings in the meantime. 

As soon as there is a [public centralized repository for WordPress plugin translations](https://translate.wordpress.org/projects/wp-plugins) 
I will migrate all the translation stuff there.

== Screenshots ==

1. Area Access Manager
2. User Editing
3. Post Access Control
4. Post Access Behaviour

== Changelog ==

= 1.4.3 =
 - Fix: Post Custom behavior not dispalying in metabox when fallback page is default fb page
 - Fix: invalid login redirect URI in subdirectory installs

= 1.4.2 =
 - Fix: no restrictions for empty post objects (fixes buddypress profile page issue)
 - Fix: wrong redirection behavior for logged in users

= 1.4.1 =
 - Fix: set suppress_filters to false on get_posts
 - Fix: Saving Access Area Name

= 1.4.0 =
 - Feature: Explicitly enable / disable custom behaviour on posts.
 - UI: Combine columns in Posts list table
 - Fix: Contained roles were not assumed correctly
 - Fix: QuickEdit did not show Access after save
 - Compatibility: Drop support for WP < 3.8
 - Code refactoring, switched classname prefixes

= 1.3.3 =
 - Fix: Database error on comment feeds. Hiding or redirecting from comment feeds should work now.
 - Fix: Crash during update (function `get_editable_roles` not found)

= 1.3.2 =
 - Security Fix: Exclude restricted posts from comment feeds

= 1.3.1 =
 - Fix: Possible vulnerability where unauthorized users could change post access settings
 - L10n: change plugin textdomain from 'wpundisclosed' to 'wp-access-areas' (= Plugin slug). Rename lang/ > languages/.

= 1.3.0 =
 - WordPress 4.0 compatibility
 - Feature: Show Access Columns on Media and Custom Post type list views
 - Feature: Select default access for new posts.
 - Feature: Role Caps. Set which roles can edit post access properties
 - Improvement: Cache DB results
 - Plugin API: Added filter: <code>wpaa_update_access_area_data</code>
 - Plugin API: Added actions: <code>wpaa_grant_access</code>, <code>wpaa_grant_{$wpaa_capability}</code>, <code>wpaa_revoke_access</code>, <code>wpaa_revoke_{$wpaa_capability}</code>, <code>wpaa_create_access_area</code>, <code>wpaa_update_access_area</code>
 - Plugin API: Added function: <code>wpaa_get_access_area( $identifier )</code>

= 1.2.9 =
Fixing that one: http://wordpress.org/support/topic/plugin-causing-crash-post-woocommerce-update-today?replies=5

= 1.2.8 =
 - Fix: Post Edit save 404 behaviour
 - Fix: Hide inacessible posts in Recent Comments widget
 - Fix: Hide inacessible posts in Latest posts widget
 - Fix: Hide inacessible posts in Archive widget
 - Fix: Don't show comments to inaccessible posts in WP-Admin. (Prohibits editing as well.)
 - L10n: Polish localisation

= 1.2.7 =
 - Feature: Explicitly select Front page as Fallback page.
 - Feature: Edit view cap now available for backend-only posts as well.
 - Fix: 404 behaviour not saving when default behaviour is other than 404
 - API: added function `wpaa_is_post_public( $post )`

= 1.2.6 =
 - Feature: Option to select post status after deleting access area
 - Fix: Wrong viewing permissions after delete access area
 - Fix: remove options upon uninstall
 - Swedish localization

= 1.2.5 =
 - Feature: Bulk edit users: Grant and revoke access.
 - Fix: Was able to create access areas with empty names.
 - Fix: Ignores WP's Comments closed status

= 1.2.4 =
 - Fix: User list table column

= 1.2.3 =
 - Check WP 3.9 compatibility
 - Fix: With no AAs present add Access Area didn't show up on profile edit page

= 1.2.2 =
 - Fix: Used wrong option name on edit post
 - Fix: Embarrassing wrong var name on edit post
 - L10n: Added one more italian string

= 1.2.1 =
 - Feature: Option to redirect to wp-login or to fallback page.
 - Feature: action hook an filter on access attempt for a restricted post. (see GitHub Repo for details)
 - Feature: post classes
 - CSS: use dashicons
 - Italian localization

= 1.2.0 =
 - Feature: Bulk edit Posts
 - Feature: Ajax-Add AAs on User edit screen
 - Debug: Fix invalid HMTL on user list table
 - Debug: Remove edit post link from frontend
 - Debug: Invisible posts are now also excluded from editing
 - Debug: Remove "Who can read"-Select from non-public post types

= 1.1.11 =
 - Debug: Fix Comment issue. Selecting "WordPress default" now does what it is supposed to: handling over the comment responsibility to WordPress.

= 1.1.10 =
 - Debug: Fix missing file issue

= 1.1.9 =
 - Feature/Debug: Network admins now have access to all areas on all blogs. Blog admins have access to all areas on their own blog(s).
 - Code: put general use processes into function

= 1.1.9 =
 - Feature/Debug: Network admins now have access to all areas on all blogs. Blog admins have access to all areas on their own blog(s).
 - Code: put general use processes into function

= 1.1.8 =
 - Fixed: Fixed issue, where access areas where not shown on user editing in single-site installs.

= 1.1.7 =
 - Fixed: Fixed issue, where posts table was not modified after creating new blog. Use WP's upgrade network function to fix all posts tables.

= 1.1.6 =
 - Feature: WP-Capability column in Access Areas table view
 - Fixed: Commenting was still possible after switching off comments and setting comment capabilities to 'use WP defaults'.

= 1.1.5 =
 - Fix [uninstall issue](http://wordpress.org/support/topic/cant-delete-the-plugin)

= 1.1.4 =
 - Fix: issue where WP-comment settings were not applied while saving post
 - Improve DE Localization

= 1.1.3 =
 - Fix: post tables did not update on wpmu_new_blog
 - Fix: [deletion issue](http://wordpress.org/support/topic/bug-report-cant-delete-area?replies=1)
 - Localize Plugin description

= 1.1.2 =
 - Added versioncheck

= 1.1.1 =
 - Improve loading behaviour

= 1.1.0 =
 - Added editing restrictions.
 - Several fixes.

= 1.0.0 =
 - Initial Release

== Upgrade notice ==

