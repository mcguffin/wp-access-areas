WP Access Areas ToDo
====================


```
./vendor/squizlabs/php_codesniffer/bin/phpcs . --report=code --standard=./phpcs.ruleset.xml -n -s
```

**Known false positives in the results:**

 - inc/class-wpaa_accessarea.php
   - False Positives: 
     - 57: WordPress.DB.PreparedSQL.NotPrepared 57
     - 57: WordPress.DB.PreparedSQL.NotPrepared 72
     - 57: WordPress.DB.PreparedSQL.NotPrepared 133
 - inc/class-accessareas_list_table.php
   - False Positive:
     - 130: WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
     - 134: WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
 - inc/class-wpaa_posts.php
   - False Positives: WordPress.DB.PreparedSQL.NotPrepared 57, 72, 133
     - 170: 2x WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     - 360: 2x WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 - inc/class-wpaa_caps.php
   - False Positives:
     - 39: WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
     - 42: WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
 - inc/class-wpaa_settings.php
   - False Positives:
     - 40: WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
     - 116: WordPress.Security.NonceVerification.Missing
     - 230: WordPress.Security.EscapeOutput.OutputNotEscaped
     - 248: WordPress.Security.EscapeOutput.OutputNotEscaped
 - inc/class-wpaa_install.php
   - False Positives:
     - 100: WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     - 102: WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     - 105: WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     - 107: 2x WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     - 120: WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     - 122: WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     - 125: WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     - 127: WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     - 208: WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
 - `wp-access-areas.php`
   - 53: PHPCS_SecurityAudit.Misc.IncludeMismatch.ErrMiscIncludeMismatchNoExt

Broken Features:
 - Users: grant/revoke bulk actions
 - QuickEdit: not preselected

 