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

	// --------------------------------------------------
	// edit post - Fallback page dropdown menu
	// --------------------------------------------------
	static function fallback_page_dropdown( $post_fallback_page = false , $fieldname = '_wpaa_fallback_page' ) {
		global $wpdb;
		if ( ! wpaa_is_post_public( $post_fallback_page ) )
			$post_fallback_page = 0;
		
		// if not fallback page, use global fallback page
		$restricted_pages = $wpdb->get_col($wpdb->prepare("SELECT id 
			FROM $wpdb->posts 
			WHERE 
				post_type=%s AND 
				post_status=%s AND
				post_view_cap!=%s" , 'page','publish','exist' ));
		wp_dropdown_pages(array(
			'selected' 	=> $post_fallback_page,
			'name'		=> $fieldname,
			'exclude'	=> $restricted_pages,
			'show_option_none' => __('Front page'),
			'option_none_value' => 0,
		));
	}
	static function behavior_select( $post_behavior = '' , $fieldname = '_wpaa_post_behavior' ) {
		$behaviors = array(
			array( 
				'value'	=> '404',
				'label' => __( 'Show 404' , 'wp-access-areas'),
			),
			array(
				'value'	=> 'page',
				'label' => __( 'Redirect to the fallback page.' , 'wp-access-areas'),
			),
			array(
				'value'	=> 'login',
				'label' => __( 'If not logged in, redirect to login. Otherwise redirect to the fallback page.' , 'wp-access-areas'),
			),
		);

		foreach ( $behaviors as $item ) {
			extract( $item );
			?><label for="wpaa-view-post-behavior-<?php echo $value ?>"><?php
				?><input name="<?php echo $fieldname ?>" <?php checked( $value , $post_behavior ); ?> class="wpaa-post-behavior" id="wpaa-view-post-behavior-<?php echo $value ?>" value="<?php echo $value ?>"  type="radio" /><?php
				echo $label 
			?><br /></label><?php
		}
	}
}