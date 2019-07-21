WP Access Areas
===============

Installation
------------
 - Either via the WordPress.org plugin repository ...
 - Or through [Andy Fragens GitHUb Updater](https://github.com/afragen/github-updater) ... (enter `mcguffin/wp-access-areas`) as a plugin URI ...




Additional WP-Capabilities
--------------------------

`wpaa_manage_access_areas`  
...

`wpaa_grant_access`  
Ability to assign Access Areas to users.
**Usage:**  
```
current_user_can( 'wpaa_grant_access' );
    // generally allowed?

current_user_can( 'wpaa_grant_access', 123 );
    // allowed for this user?

current_user_can( 'wpaa_grant_access', 123, 'wpaa_1_some-access-area' );
    // allowed for this user and Access Area?

current_user_can( 'wpaa_grant_access', 'wpaa_1_some-access-area' );
    // allowed for this Access Area?
```

`wpaa_revoke_access`  
Ability to revoke Access Areas from users.


`wpaa_edit_role_caps`  
The Ability to assign `wpaa_set_*_cap` capabilities to User roles.
The Current User must have the `wpaa_set_*_cap` capability in addition.

**Usage:**  
```
current_user_can( 'edit_wpaa_role_caps' ); // generally allowed?
current_user_can( 'edit_wpaa_role_caps', 'editor' ); // generally allowed for this specific role?
```

`wpaa_set_view_cap`, `wpaa_set_edit_cap`, `wpaa_set_comment_cap`  
Ability to edit Post Access settings.
