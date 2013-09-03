<?php
/**
* @package WP_AccessAreas
* @version 1.1.9
*/ 

// 
function wpaa_user_can( $cap ) {
	global $wp_roles;
	
	// exist always true. read always treu for logged in users. admin can do verything on single sites
	if ( 'exist' == $cap 
		|| 'read' == $cap && is_user_logged_in() 
		|| ( wpaa_is_local_cap( $cap ) && current_user_can( 'administrator' ) ) 
	)
		return true;
	
	// true for role
	if ( $wp_roles->is_role( $cap ) )
		return wpaa_user_can_role( $cap );
	
	// any other cap including custom caps.
	return current_user_can( $cap );
}

function wpaa_user_can_accessarea( $cap ) {
	global $wp_roles;

	// always true for administrators on local caps
	if ( wpaa_is_local_cap( $cap ) && current_user_can( 'administrator' ) )
		$can = true;
	else
		$can = current_user_can( $cap );
	
	// any other cap including custom caps.
	return $can;
}

function wpaa_is_local_cap( $cap ) {
	return strpos($cap,wpaa_get_local_prefix( )) === 0;
}

function wpaa_get_local_prefix( $blog_id = null ){
	$prefix = WPUND_USERLABEL_PREFIX;
	if ( ! $blog_id )
		$blog_id = get_current_blog_id();

	if ( $blog_id ) 
		$prefix .= "{$blog_id}_";
	
	return $prefix;
}

// returns if current users caps all cover the given role
function wpaa_user_can_role( $role , $user_role_caps = null ) {
	global $wp_roles;
	if ( is_null( $user_role_caps ) )
		$user_role_caps = wpaa_get_user_role_caps();
		
	if ( $wp_roles->is_role($role) )
		return 0 == count(array_diff_assoc(  $wp_roles->get_role( $role )->capabilities , $user_role_caps ) );
	return false;
}


// returns all of WPs capabilities assigned to current user's roles
function wpaa_get_user_role_caps( $user_roles = null ) {
	global $wp_roles;
	if ( is_null($user_roles) )
		$user_roles = wp_get_current_user()->roles;
	
	$user_role_caps = array();
	
	foreach ( $user_roles as $i=>$rolename )
		if ( $wp_roles->is_role( $rolename ) )
			$user_role_caps += array_filter( $wp_roles->get_role($rolename)->capabilities , 'intval' );
	return $user_role_caps;
}
// returns all roles a user can possibly have
function wpaa_user_contained_roles( $user_roles = null ) {
	if ( is_null( $user_roles ) )
		$user_roles = wp_get_current_user()->roles;
	
	$contained_roles = array();

	foreach ( $user_roles as $role )
		$contained_roles += wpaa_contained_roles( $role );

	return array_unique( $contained_roles );
}

// returns all roles which are covered by $role
function wpaa_contained_roles( $role ) {
	global $wp_roles;
	
	$rolenames = $wp_roles->get_names();
	$contained_roles = array();
	foreach ($rolenames as $rolename => $human_rolename )
		if (wpaa_role_contains( $role , $rolename )) 
			$contained_roles[] = $rolename;
	return $contained_roles;
}

// returns if $container role covers $contained role
function wpaa_role_contains( $container , $contained ) {
	global $wp_roles;
	// roles are equal, always true
	if ( $container == $contained ) {
		$contains = true;
	} else if ( $wp_roles->is_role( $container ) && $wp_roles->is_role( $contained ) ) {
		$contains = 0 < count(array_diff_assoc(  
			$wp_roles->get_role( $container )->capabilities , 
			$wp_roles->get_role( $contained )->capabilities
		));
		
	}
	return $contains;
}


//var_dump(wpaa_role_contains_role( 'administrator' , 'administrator' ));