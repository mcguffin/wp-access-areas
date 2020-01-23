<?php

class WPAA_Template {


    public static function access_area( $name, $global = false ) {
        return sprintf( '<span class="wpaa-access-area dashicons-before dashicons-admin-%s">%s</span>', $global ? 'site' : 'home', $name );
    }

    // --------------------------------------------------
    // edit post - Access Area dropdown menu
    // --------------------------------------------------
    public static function access_area_dropdown( $roles, $groups, $selected_cap, $fieldname, $first_item_value = null, $first_item_label = '' ) {

        if ( ! $selected_cap ) {
            $selected_cap = 'exist';
        }

        ?><select id="<?php echo sanitize_key( $fieldname ); ?>-select" name="<?php esc_attr_e( $fieldname ); ?>">
            <?php

            if ( ! is_null( $first_item_value ) && ! is_null( $first_item_label ) ) {
    			?>
                <option value="<?php esc_attr_e( $first_item_value ); ?>">
                    <?php esc_html_e( $first_item_label ); ?>
                </option>
    			<?php
            }

            ?>
            <option value="exist" <?php selected( $selected_cap, 'exist' ); ?>>
                <?php esc_html_e( 'WordPress default', 'wp-access-areas' ); ?>
            </option>
            <?php
            if ( strpos( $fieldname, 'post_edit_cap' ) === false ) {
                ?>
                <option value="read" <?php selected( $selected_cap, 'read' ); ?>>
                    <?php esc_html_e( 'Logged in Users', 'wp-access-areas' ); ?>
                </option>
    			<?php
            }

            ?>
            <optgroup label="<?php esc_attr_e( 'WordPress roles', 'wp-access-areas' ); ?>">
                <?php
                foreach ( $roles as $role => $rolename ) {
                    if ( ! wpaa_user_can_role( $role ) ) {
                        continue;
                    }
        			?>
                    <option value="<?php esc_attr_e( $role ); ?>" <?php selected( $selected_cap, $role ); ?>>
                        <?php esc_html_e( $rolename ); ?>
                    </option>
        			<?php
                }
            ?>
            </optgroup>
            <?php
            if ( count( $groups ) ) {
    			?>
                <optgroup label="<?php esc_attr_e( 'Users with Access to', 'wp-access-areas' ); ?>">
                    <?php
    				foreach ( $groups as $group_cap => $group ) {
                        if ( ! wpaa_user_can_accessarea( $group_cap ) ) {
                            continue;
                        }
                        ?>
                        <option value="<?php esc_attr_e( $group_cap ); ?>" <?php selected( $selected_cap, $group_cap ); ?>>
                            <?php
                                esc_html_e( $group['title'], 'wp-access-areas' );
                                echo $group['global'] ? ' ' . esc_html__( '(Network)', 'wp-access-areas' ) : '';
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </optgroup>
    			<?php
            }  /* if count( $groups ) */
            ?>
        </select>
        <?php
    }

    // --------------------------------------------------
    // edit post - Fallback page dropdown menu
    // --------------------------------------------------
    public static function fallback_page_dropdown( $post_fallback_page = false, $fieldname = '_wpaa_fallback_page' ) {
        global $wpdb;
        if ( ! wpaa_is_post_public( $post_fallback_page ) ) {
            $post_fallback_page = 0;
        }

        // if not fallback page, use global fallback page
        $restricted_pages = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id 
			FROM $wpdb->posts 
			WHERE 
				post_type=%s AND 
				post_status=%s AND
				post_view_cap!=%s",
                'page',
                'publish',
                'exist'
            )
        );
        wp_dropdown_pages(
            array(
				'selected'          => intval( $post_fallback_page ),
				'name'              => esc_attr( $fieldname ),
				'exclude'           => array_map( 'intval', $restricted_pages ),
				'show_option_none'  => esc_html__( 'Front page', 'wp-access-areas' ),
				'option_none_value' => 0,
            )
        );
    }
    public static function behavior_select( $post_behavior = '', $fieldname = '_wpaa_post_behavior' ) {
        $behaviors = array(
			array(
				'value' => '404',
				'label' => __( 'Show 404', 'wp-access-areas' ),
			),
			array(
				'value' => 'page',
				'label' => __( 'Redirect to the fallback page.', 'wp-access-areas' ),
			),
			array(
				'value' => 'login',
				'label' => __( 'If not logged in, redirect to login. Otherwise redirect to the fallback page.', 'wp-access-areas' ),
			),
        );

        foreach ( $behaviors as $item ) {
            $value = $item['value'];
            $label = $item['label'];
            ?>
            <label for="wpaa-view-post-behavior-<?php echo sanitize_key( $value ); ?>">
                <input name="<?php esc_attr_e( $fieldname ); ?>" <?php checked( $value, $post_behavior ); ?> class="wpaa-post-behavior" id="wpaa-view-post-behavior-<?php echo sanitize_key( $value ); ?>" value="<?php esc_attr_e( $value ); ?>" type="radio" />
                <?php esc_html_e( $label ); ?>
                <br />
            </label>
            <?php
        }
    }
}
