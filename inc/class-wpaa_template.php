<?php

class WPAA_Template {

	static function access_area( $name , $global = false , $return = true ) {
		return sprintf( '<span class="wpaa-access-area dashicons-before dashicons-admin-%s">%s</span>' , $global ? 'site' : 'home' , $name );
	}

	// --------------------------------------------------
	// edit post - Access Area dropdown menu
	// --------------------------------------------------
	static function access_area_dropdown( $roles , $groups , $selected_cap , $fieldname , $first_item_value = null , $first_item_label = ''  ) {
		if ( ! $selected_cap )
			$selected_cap = 'exist';
		?><select id="<?php echo sanitize_title($fieldname) ?>-select" name="<?php echo $fieldname ?>"><?php
			if ( ! is_null( $first_item_value ) && ! is_null( $first_item_label ) ) {
				?><option value="<?php $first_item_value ?>"><?php echo $first_item_label ?></option><?php
			}
		
			?><option value="exist" <?php selected($selected_cap , 'exist') ?>><?php _e( 'WordPress default' , 'wp-access-areas' ) ?></option><?php
			if ( strpos( $fieldname , 'post_edit_cap' ) === false ) {
				?><option value="read" <?php selected($selected_cap , 'read') ?>><?php _e( 'Logged in Users' , 'wp-access-areas' ) ?></option><?php
			}
			
			?><optgroup label="<?php _e( 'WordPress roles' , 'wp-access-areas') ?>"><?php
			foreach ($roles as $role => $rolename) {
				if ( ! wpaa_user_can_role( $role ) )
					continue;
				?><option value="<?php echo $role ?>" <?php selected($selected_cap , $role) ?>><?php _ex( $rolename, 'User role' ) ?></option><?php
			} 
			?></optgroup><?php
			if ( count($groups) ) { 
				?><optgroup label="<?php _e( 'Users with Access to' , 'wp-access-areas') ?>"><?php
				foreach ($groups as $group_cap => $group) { 
					if ( ! wpaa_user_can_accessarea($group_cap) )
						continue;
					?><option value="<?php echo $group_cap ?>" <?php selected( $selected_cap , $group_cap ) ?>><?php _e( $group['title'] , 'wp-access-areas' ); echo $group['global'] ? ' ' . __('(Network)','wp-access-areas'):''; ?></option><?php
				 } /* foreach( $groups ) */ 
				?></optgroup><?php 
			}  /* if count( $groups ) */ 
		?></select><?php
	}

}