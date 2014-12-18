<?php
/**
* @package WP_AccessAreas
* @version 1.1.9
*/ 

/**
 * Whether current user has capability or role.
 *
 * @param string $cap Capability or role name.
 * @param array $args Arguments such as Post IDs. (will be passed to PW current_user_can() if applicable)
 * @return bool
 */
function wpaa_user_can( $cap , $args = array() ) {
	global $wp_roles;
	
	// exist always true. read always true for logged in users. 
	if ( 'exist' == $cap || ('read' == $cap && is_user_logged_in() ) ) 
		return true;
	
	// true for role
	if ( $wp_roles->is_role( $cap ) )
		$can = wpaa_user_can_role( $cap );
	else if ( wpaa_is_access_area( $cap ) )
		$can = wpaa_user_can_accessarea( $cap , $args );
	else 
		$can = current_user_can( $cap , $args );
	
	return $can;
}

/**
 * Get Access Area object.
 *
 * @param string|int $identifier Capability name or numeric ID.
 * @return object
 */
function wpaa_get_access_area( $identifier ) {
	if ( is_numeric( $identifier ) ) {
		return WPAA_AccessArea::get_userlabel( $identifier );
	} else if ( wpaa_is_access_area( $identifier ) ) {
		return WPAA_AccessArea::get_userlabel_by_cap( $identifier );
	}
	
}

/**
 * Safely return access capability for use in posts table.
 *
 * @param string $cap Access Area Capability name, valid role name, 'read' or 'exist'
 * @return object
 */
function wpaa_sanitize_access_cap( $cap ) {
	global $wp_roles;
	if ( $cap == 'exist' || $cap == 'read' || $wp_roles->is_role( $cap ) || wpaa_access_area_exists( $cap ) )
		return $cap;
	return 'exist';
}

/**
 * Check if a string is a valid Access Area capability.
 *
 * @param string $cap Access Area Capability name
 * @return boolean
 */
function wpaa_is_access_area( $cap ) {
	return strpos( $cap , WPUND_USERLABEL_PREFIX ) === 0;
}

/**
 * Check if an Access Area exists.
 *
 * @param string $cap Access Area Capability name
 * @return boolean
 */
function wpaa_access_area_exists( $cap ) {
	if ( ! wpaa_is_access_area( $cap ) )
		return false;
	return WPAA_AccessArea::capability_exists( $cap );
}

/**
 * Check if a user is allowed in a specific Access Area.
 *
 * @param string $cap Access Area Capability name
 * @param array $args Arguments such as Post IDs. (will be passed to PW current_user_can() if applicable)
 * @return boolean
 */
function wpaa_user_can_accessarea( $cap , $args = array() ) {
	global $wp_roles;

	// always true for administrators on local caps
	if ( wpaa_is_local_cap( $cap ) && current_user_can( 'administrator' ) || is_super_admin() )
		$can = true;
	else
		$can = current_user_can( $cap , $args );
	
	// any other cap including custom caps.
	return $can;
}

/**
 * Check if an Access Area capability is valid on the current blog.
 *
 * @param string $cap Access Area Capability name
 * @return boolean
 */
function wpaa_is_local_cap( $cap ) {
	return strpos($cap,wpaa_get_local_prefix( )) === 0;
}

/**
 * Get Access area prefix for blog.
 *
 * @param string $blog_id ID of blog. If null get_current_blog_id() will be used
 * @return string
 */
function wpaa_get_local_prefix( $blog_id = null ){
	$prefix = WPUND_USERLABEL_PREFIX;
	if ( ! $blog_id )
		$blog_id = get_current_blog_id();

	if ( $blog_id ) 
		$prefix .= "{$blog_id}_";
	
	return $prefix;
}

/**
 * Whether a users capablities cover a role.
 *
 * @param string $role A valid Wordpress rolename
 * @param array $user_role_caps The users capabilities. Should be accumulated from all roles a user has. Use `wpaa_get_user_role_caps()` to get them. If `null` the current users capabilities will be used.
 * @return string
 */
function wpaa_user_can_role( $role , $user_role_caps = null ) {
	global $wp_roles;
	if ( is_null( $user_role_caps ) )
		$user_role_caps = wpaa_get_user_role_caps();
	if ( $wp_roles->is_role($role) )
		return 0 == count(array_diff_assoc(  $wp_roles->get_role( $role )->capabilities , $user_role_caps ) );
	return false;
}


/**
 * All capabilities contained in a role
 *
 * @param string $role A valid Wordpress rolename
 * @param string|array $user_roles One or more valid wordpress rolenames. If omitted the currant users roles will be used.
 * @return string
 */
function wpaa_get_user_role_caps( $user_roles = null ) {
	global $wp_roles;
	if ( is_null($user_roles) )
		$user_roles = wp_get_current_user()->roles;
	
	$user_role_caps = array();
	
	foreach ( (array) $user_roles as $i=>$rolename )
		if ( $wp_roles->is_role( $rolename ) )
			$user_role_caps += array_filter( $wp_roles->get_role($rolename)->capabilities , 'intval' );
	return $user_role_caps;
}

/**
 * Get all inferior roles of one or more roles.
 *
 * @param string|array $user_roles One or more valid wordpress rolenames. If omitted the currant users roles will be used.
 * @return array
 */
function wpaa_user_contained_roles( $user_roles = null ) {
	if ( is_null( $user_roles ) )
		$user_roles = wp_get_current_user()->roles;
	
	$contained_roles = array();

	foreach ( $user_roles as $role )
		$contained_roles += wpaa_contained_roles( $role );

	return array_unique( $contained_roles );
}

/**
 * Get all inferior roles of a single role.
 *
 * @param string|array $role A valid wordpress rolenames.
 * @return array
 */
function wpaa_contained_roles( $role ) {
	global $wp_roles;
	
	$rolenames = $wp_roles->get_names();
	$contained_roles = array();
	foreach ($rolenames as $rolename => $human_rolename )
		if ( wpaa_role_contains( $role , $rolename ) ) 
			$contained_roles[] = $rolename;
	return $contained_roles;
}

/**
 * Whether a role is covered by another role.
 *
 * @param string $container A valid wordpress rolenames.
 * @param string $contained An other valid wordpress rolenames.
 * @return bool
 */
function wpaa_role_contains( $container , $contained ) {
	global $wp_roles;
	// roles are equal, always true
	if ( $container == $contained ) {
		$contains = true;
	} else if ( $wp_roles->is_role( $container ) && $wp_roles->is_role( $contained ) ) {

		$contains = 0 == count(array_diff_assoc(  
			$wp_roles->get_role( $contained )->capabilities ,
			$wp_roles->get_role( $container )->capabilities 
		));
		
	}
	return $contains;
}



/**
 * Whether a post is publicly viewable.
 *
 * @param int|object $post Pot Object or post ID.
 * @return bool
 */
function wpaa_is_post_public( $post ) {
	if ( ! is_object( $post ) )
		$post = get_post( $post );
	if ( $post )
		return $post->post_status == 'publish' && $post->post_view_cap == 'exist';
	return false;
}

