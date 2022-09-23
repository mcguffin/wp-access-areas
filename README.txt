=== WordPress Access Areas ===
Contributors: podpirate
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WF4Z3HU93XYJA
Tags: access, role, capability, user, security, editor
Requires at least: 4.6
Requires PHP: 5.6
Tested up to: 6.0
Stable tag: 1.5.19
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fine tuning access to your posts.

== Description ==

WP Access Areas lets you fine-tune who may read, edit or comment on your Blog posts.
You can either restrict access to logged-in uses only, certain WordPress-Roles or even custom Access Areas.

= Features =
- Define custom Access Areas and assign them to your blog-users
- Restrict reading, editing and commenting permission to logged-in users, certain WordPress-Roles or Access Areas
- define global access areas on a network
- Supports bulk editing
- German, Italian, Polish and Swedish localization (Huge Thankyou @ all translators!)

= Known Issues =
- WordPress calendar Widget still shows dates where restricted posts have been created.
  When clicked on such a date a 404 will occur. There is an open [WordPress Core ticket on that issue](https://core.trac.wordpress.org/ticket/29319).
- Taxonomy menus (e.g. Tags / Categories) also count restricted posts when the total number of posts in a taxonomy is ascertained.
  See [this post](http://wordpress.org/support/topic/archive-recents-posts-last-comments-show-restricted-content?replies=5#post-5929330) for details.

= Development =

Please head over to the source code [on Github](https://github.com/mcguffin/wp-access-areas).

== Installation ==

1. Upload the 'wp-access-areas.zip' to the `/wp-content/plugins/` directory and unzip it.

2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently asked questions ==

= Why can't I protect media? =

Because the plugin can only protect posts, which are database entries. A media also contains a
file stored on your servers file system. A file is normally just returned by the server, the
WordPress core is not involved. In order to protect a file, let's say an image, the Image URL
would have to be point to a special Script, that decides whether the file is protected or not,
and if so, which user or group of users would be granted access.

A lot of processing would be going on, and each and every little thumbnail would add another
one or two seconds to your page load time. The result: Tears, rage and support requests.

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

Please do so in the [GitHub Repository](https://github.com/mcguffin/wp-access-areas).

= I found a bug and fixed it. How can I contribute? =

Pull request are welcome in the [GitHub Repository](https://github.com/mcguffin/wp-access-areas).

== Screenshots ==

1. Area Access Manager
2. User Editing
3. Post Access Control
4. Post Access Behaviour

== Changelog ==

= 1.5.19 =
 - Fix: Nonce Verification fails when using WP password reset 

= 1.5.18 =
 - Fix: PHP Warning when using plugin together with imsanity

= 1.5.17 =
 - Fix: Access settings broken in post quick edit

= 1.5.15 =
 - Fix: could not save website settings in network admin when running on multisite
 - Fix: role capabilities not saved

= 1.5.14 =
 - Fix: could not add caps on user-edit

= 1.5.13 =
 - Fix: WP deprecation warning
 - Fix: Chrome DOM warning

= 1.5.12 =
 - Fix: nonce error when adding User in network admin if plugin is not network active

= 1.5.11 =
 - Fix: __doint_it_wrong message wpdb->prepare

= 1.5.10 =
 - Fix: wpdb table prefix messed up in multisite

= 1.5.9 =
 - Fix: Pages saved via ajax not working. (Elementor)

= 1.5.8 =
 - Security hardening

= 1.5.7 =
 - Fix anaother PHP Warning

= 1.5.6 =
 - Fix Multisite Database Error when WPAA is not active for network.

= 1.5.5 =
 - Fix PHP Warning

= 1.5.4 =
 - Fix WSOD when saving post

= 1.5.3 =
 - Fix a Bug where a logged in user wasn't redirected to the fallback page. Thanks to [Andrey Shevtsov](https://github.com/freeworlder)
 - Merry Christmas (Gregorian Calendar)

= 1.5.2 =
 - Fix Multisite: Network Access Areas were visible when plugin was single activated
 - Introduce filters: `wpaa_can_protect_{$post_type}`, `wpaa_can_edit_{$post_type}_view_cap`, `wpaa_can_edit_{$post_type}_edit_cap`, `wpaa_can_edit_{$post_type}_comment_cap`

= 1.5.1 =
 - Localization: move de_DE and de_DE_formal to translate.wordpress.org

= 1.5.0 =
 - Fix: A network admin without blog role could not edit post access by WP Roles
 - Plugin settings: Use WP Post statuses in favor of hard coded status list (allows use of custom post statuses now)
 - Introduce filter: 'wpaa_allowed_post_stati'
 - Localization: Rework strings
 - Localization: Introduce de_DE_formal
 - Localization: consistent use of plugin textdomain

= 1.4.7 =
 - Fix: PHP deprecated warning during install + network upgrade
 - Fix: Incorrect Post Classes

= 1.4.6 =
 - Fix: Crash during install

= 1.4.5 =
 - Fix: WP _doing_it_wrong message

= 1.4.4 =
 - Fix: Multisite install procedere
 - Fix: Add self repair functionality (Ass missing posts table columns)

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
