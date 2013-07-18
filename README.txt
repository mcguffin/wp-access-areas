=== WordPress Access Areas ===
Contributors: podpirate
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WF4Z3HU93XYJA
Tags: access, role, capability, user, security, editor
Requires at least: 3.5
Tested up to: 3.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fine tuning access to your posts.

== Description ==

WP Access Areas lets you fine-tune who may read or comment on your Blog posts.
You can either restrict access to logged-in uses only, certain WordPress-Roles or 
even custom Access Areas.

= Features =
- Define custom Access Areas and assign them to your blog-users
- Restrict reading and commenting permission to logged-in users, certain WordPress-Roles or Access Areas
- define global access areas on a network
- German localization
- Clean uninstall

Latest files on [GitHub](https://github.com/mcguffin/wp-access-areas).

== Installation ==

1. Upload the 'wp-access-areas.zip' to the `/wp-content/plugins/` directory and unzip it. 

2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently asked questions ==

= What does it exactly do? =

For each Post it stores a capabilty the user needs to have in order to view or comment a post. By defining Access Areas You create nothing more than custom capabilities. 

= Only viewing and commens? Why not editing? =

I couldn't figure out yet which hook to use. I will step into further investigations in the future and add the feature, as it makes totally sense.

= Why didn't you use post_meta to store permissions? WordPress already provides an API for this! =

I did this mainly for performance reason. The plugin mainly affects the WHERE Clause in the database. In some cases using post_meta would mean to add a JOIN clause to the Database query, which would decrease your site's performance.

= Does it mess up my database? =

Well, it makes changes to your database, but it won't make a mess out of it. Upon install it does two things:
1. It creates a table named ´{$wp_prefix}_disclosure_userlabels´. The access areas you define are here.
2. It adds three columns to Your Posts tables: post_view_cap and post_comment_cap. 

Upon uninstall these changes will be removed completely, as well as it will remove any custom generated capability from Your user's profiles.

= I found a bug. Where should I post it? =

I personally prefer GitHub. The plugin code is here: [GitHub](https://github.com/mcguffin/wp-access-areas)

= I want to use the latest files. How can I do this? =

Use the GitHub Repo rather than the WordPress Plugin. Do as follows:

1. If you haven't already done: [Install git](https://help.github.com/articles/set-up-git)

2. in the console cd into Your 'wp-content/plugins´ directory

3. type `git clone git@github.com:mcguffin/wp-access-areas.git`

4. If you want to update to the latest files (be careful, might be untested on Your WP-Version) type git pull´.

= I found a bug and fixed it. How can I contribute? =

Either post it on [GitHub](https://github.com/mcguffin/wp-access-areas) or—if you are working on a cloned repository—send me a pull request.

== Screenshots ==

1. Area Access Manager
2. User Editing
3. Post Access Control

== Changelog ==

= 1.0.0 =
Initial Release

== Upgrade notice ==

= 1.0.0 =
A plugin is born.

