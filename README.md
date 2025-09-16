Access Areas for WordPress
==========================

This is the official github repository of the [Access Areas for WordPress](http://wordpress.org/plugins/wp-access-areas/)
plugin. This repo might contain untested and possibly unstable or insecure code. So use it on your own risk.

About
-----
WP Access Areas lets you fine-tune who may read, edit or comment on your Blog posts.
You can either restrict access to logged-in uses only, certain WordPress-Roles or
even custom Access Areas.

Features
--------
- Define custom Access Areas and assign them to your blog-users
- Restrict reading, editing and commenting permission to logged-in users, certain WordPress-Roles or Access Areas
- On a Network you can define global access areas
- German and Italian localization
- Clean uninstall

Installation
------------
Either move the plugin dir in your `wp-content/` directory ...

... or git-clone it:
```
$ cd wp-content/plugins/
$ git clone git@github.com:mcguffin/wp-access-areas.git
```

Finally activate it in the GUI.

Development
-----------
Install dev
```
npm install
```


Run code Audit
```
npm run audit
```

Compatibility
-------------
- Up to WP 5.4
- The plugin is still functional in WP < 3.8, but some items may not be displayed correctly.

Plugin API
----------
See the [Project Wiki](../../wiki/) for details.
