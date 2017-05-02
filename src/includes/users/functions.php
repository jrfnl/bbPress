<?php

/**
 * bbPress User Functions
 *
 * @package bbPress
 * @subpackage Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Redirect back to $url when attempting to use the login page
 *
 * @since 2.0.0 bbPress (r2815)
 *
 * @param string $url The url
 * @param string $raw_url Raw url
 * @param object $user User object
 * @uses is_wp_error() To check if the user param is a {@link WP_Error}
 * @uses admin_url() To get the admin url
 * @uses home_url() To get the home url
 * @uses esc_url() To escape the url
 * @uses bbp_redirect() To redirect
 */
function bbp_redirect_login( $url = '', $raw_url = '', $user = '' ) {

	// Raw redirect_to was passed, so use it
	if ( ! empty( $raw_url ) ) {
		$url = $raw_url;

	// $url was manually set in wp-login.php to redirect to admin
	} elseif ( admin_url() === $url ) {
		$url = home_url();

	// $url is empty
	} elseif ( empty( $url ) ) {
		$url = home_url();
	}

	return apply_filters( 'bbp_redirect_login', $url, $raw_url, $user );
}

/**
 * Is an anonymous topic/reply being made?
 *
 * @since 2.0.0 bbPress (r2688)
 *
 * @uses is_user_logged_in() Is the user logged in?
 * @uses bbp_allow_anonymous() Is anonymous posting allowed?
 * @uses apply_filters() Calls 'bbp_is_anonymous' with the return value
 * @return bool True if anonymous is allowed and user is not logged in, false if
 *               anonymous is not allowed or user is logged in
 */
function bbp_is_anonymous() {
	if ( ! is_user_logged_in() && bbp_allow_anonymous() ) {
		$is_anonymous = true;
	} else {
		$is_anonymous = false;
	}

	return apply_filters( 'bbp_is_anonymous', $is_anonymous );
}

/**
 * Echoes the values for current poster (uses WP comment cookies)
 *
 * @since 2.0.0 bbPress (r2734)
 *
 * @param string $key Which value to echo?
 * @uses bbp_get_current_anonymous_user_data() To get the current anonymous user
 *                                              data
 */
function bbp_current_anonymous_user_data( $key = '' ) {
	echo bbp_get_current_anonymous_user_data( $key );
}

	/**
	 * Get the cookies for current poster (uses WP comment cookies).
	 *
	 * @since 2.0.0 bbPress (r2734)
	 *
	 * @param string $key Optional. Which value to get? If not given, then
	 *                     an array is returned.
	 * @uses sanitize_comment_cookies() To sanitize the current poster data
	 * @uses wp_get_current_commenter() To get the current poster data	 *
	 * @return string|array Cookie(s) for current poster
	 */
	function bbp_get_current_anonymous_user_data( $key = '' ) {

		// Array of allowed cookie names
		$cookie_names = array(
			'name'  => 'comment_author',
			'email' => 'comment_author_email',
			'url'   => 'comment_author_url',

			// Here just for the sake of them, use the above ones
			'comment_author'       => 'comment_author',
			'comment_author_email' => 'comment_author_email',
			'comment_author_url'   => 'comment_author_url',
		);

		// Sanitize core cookies
		sanitize_comment_cookies();

		// Get the current poster's info from the cookies
		$bbp_current_poster = wp_get_current_commenter();

		// Sanitize the cookie key being retrieved
		$key = sanitize_key( $key );

		// Maybe return a specific key
		if ( ! empty( $key ) && in_array( $key, array_keys( $cookie_names ), true ) ) {
			return $bbp_current_poster[ $cookie_names[ $key ] ];
		}

		// Return all keys
		return $bbp_current_poster;
	}

/**
 * Set the cookies for current poster (uses WP comment cookies)
 *
 * @since 2.0.0 bbPress (r2734)
 *
 * @param array $anonymous_data Optional - if it's an anonymous post. Do not
 *                              supply if supplying $author_id. Should be
 *                              sanitized (see {@link bbp_filter_anonymous_post_data()}
 * @uses apply_filters() Calls 'comment_cookie_lifetime' for cookie lifetime.
 *                        Defaults to 30000000.
 */
function bbp_set_current_anonymous_user_data( $anonymous_data = array() ) {

	// Bail if empty or not an array
	if ( empty( $anonymous_data ) || ! is_array( $anonymous_data ) ) {
		return;
	}

	// Setup cookie expiration
	$lifetime = (int) apply_filters( 'comment_cookie_lifetime', 30000000 );
	$expiry   = time() + $lifetime;
	$secure   = ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) );

	// Set the cookies
	setcookie( 'comment_author_'       . COOKIEHASH, $anonymous_data['bbp_anonymous_name'],    $expiry, COOKIEPATH, COOKIE_DOMAIN, $secure );
	setcookie( 'comment_author_email_' . COOKIEHASH, $anonymous_data['bbp_anonymous_email'],   $expiry, COOKIEPATH, COOKIE_DOMAIN, $secure );
	setcookie( 'comment_author_url_'   . COOKIEHASH, $anonymous_data['bbp_anonymous_website'], $expiry, COOKIEPATH, COOKIE_DOMAIN, $secure );
}

/**
 * Get the poster IP address
 *
 * @since 2.0.0 bbPress (r3120)
 * @since 2.6.0 bbPress (r5609) Added `empty()` check for unit tests
 *
 * @return string
 */
function bbp_current_author_ip() {

	// Check for remote address
	$remote_address = ! empty( $_SERVER['REMOTE_ADDR'] )
		? $_SERVER['REMOTE_ADDR']
		: '127.0.0.1';

	// Remove any unsavory bits
	$retval = preg_replace( '/[^0-9a-fA-F:., ]/', '', $remote_address );

	return apply_filters( 'bbp_current_author_ip', $retval, $remote_address );
}

/**
 * Get the poster user agent
 *
 * @since 2.0.0 bbPress (r3446)
 *
 * @return string
 */
function bbp_current_author_ua() {
	$retval = ! empty( $_SERVER['HTTP_USER_AGENT'] )
		? substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 )
		: '';

	return apply_filters( 'bbp_current_author_ua', $retval );
}

/** User Relationships ********************************************************/

/**
 * Set a user id on an object
 *
 * @since 2.6.0 bbPress(r6109)
 *
 * @param int    $object_id The object id
 * @param int    $user_id   The user id
 * @param string $meta_key  The relationship key
 * @param string $meta_type The relationship type
 *
 * @uses add_post_meta() To set the term on the object
 * @uses apply_filters() Calls 'bbp_add_user_to_object' with the object id, user
 *                        id, and taxonomy
 * @return bool Returns true if the user taxonomy term is added to the object,
 *               otherwise false
 */
function bbp_add_user_to_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post' ) {
	$retval = add_metadata( $meta_type, $object_id, $meta_key, $user_id, false );

	return (bool) apply_filters( 'bbp_add_user_to_object', (bool) $retval, $object_id, $user_id, $meta_key, $meta_type );
}

/**
 * Remove a user id from an object
 *
 * @since 2.6.0 bbPress(r6109)
 *
 * @param int    $object_id The post id
 * @param int    $user_id   The user id
 * @param string $meta_key  The relationship key
 * @param string $meta_type The relationship type
 *
 * @uses delete_post_meta() To remove the term from the object
 * @uses apply_filters() Calls 'bbp_remove_user_from_object' with the object
 *                        id, user id, and taxonomy
 * @return bool Returns true is the user taxonomy term is removed from the object,
 *               otherwise false
 */
function bbp_remove_user_from_object( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post' ) {
	$retval = delete_metadata( $meta_type, $object_id, $meta_key, $user_id, false );

	return (bool) apply_filters( 'bbp_remove_user_from_object', (bool) $retval, $object_id, $user_id, $meta_key, $meta_type );
}

/**
 * Get user taxonomy terms for an object
 *
 * @since 2.6.0 bbPress(r6109)
 *
 * @param int    $object_id The object id
 * @param string $meta_key  The key used to index this relationship
 * @param string $meta_type The type of meta to look in
 *
 * @uses get_post_meta() To get the user taxonomy terms
 * @uses apply_filters() Calls 'bbp_get_users_for_object' with the user
 *                        taxonomy terms, object id, and taxonomy
 * @return array Returns the user taxonomy terms of the object
 */
function bbp_get_users_for_object( $object_id = 0, $meta_key = '', $meta_type = 'post' ) {
	$meta   = get_metadata( $meta_type, $object_id, $meta_key, false );
	$retval = wp_parse_id_list( $meta );

	return (array) apply_filters( 'bbp_get_users_for_object', (array) $retval, $object_id, $meta_key, $meta_type );
}

/**
 * Check if the user id is set on an object
 *
 * @since 2.6.0 bbPress(r6109)
 *
 * @param int    $object_id The object id
 * @param int    $user_id   The user id
 * @param string $meta_key  The relationship key
 * @param string $meta_type The relationship type
 *
 * @uses get_post_meta() To check if the user id is set on the object
 * @uses apply_filters() Calls 'bbp_is_object_of_user' with the object id,
 *                        user id, and taxonomy
 * @return bool Returns true if the user id is set on the object for the
 *               taxonomy, otherwise false
 */
function bbp_is_object_of_user( $object_id = 0, $user_id = 0, $meta_key = '', $meta_type = 'post' ) {
	$user_ids = bbp_get_users_for_object( $object_id, $meta_key, $meta_type );
	$retval   = is_numeric( array_search( $user_id, $user_ids, true ) );

	return (bool) apply_filters( 'bbp_is_object_of_user', (bool) $retval, $object_id, $user_id, $meta_key, $meta_type );
}

/** Engagements ***************************************************************/

/**
 * Get the users who have engaged in a topic
 *
 * @since 2.6.0 bbPress (r6320)
 *
 * @param int $topic_id Optional. Topic id
 * @uses bbp_get_users_for_object() To get user ids who engaged
 * @uses apply_filters() Calls 'bbp_get_topic_engagements' with the users and
 *                        topic id
 * @return array|bool Results if the topic has any engagements, otherwise false
 */
function bbp_get_topic_engagements( $topic_id = 0 ) {
	$topic_id = bbp_get_topic_id( $topic_id );
	$users    = bbp_get_users_for_object( $topic_id, '_bbp_engagement' );

	return (array) apply_filters( 'bbp_get_topic_engagements', $users, $topic_id );
}

/**
 * Get a user's topic engagements
 *
 * @since 2.6.0 bbPress (r6320)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_has_topics() To get the topics
 * @uses apply_filters() Calls 'bbp_get_user_engagements' with the topic query and
 *                        user id
 * @return array|bool Results if user has engaged, otherwise false
 */
function bbp_get_user_engagements( $user_id = 0 ) {
	$user_id     = bbp_get_user_id( $user_id );
	$engagements = bbp_has_topics( array(
		'meta_query' => array(
			array(
				'key'     => '_bbp_engagement',
				'value'   => $user_id,
				'compare' => 'NUMERIC'
			)
		)
	) );

	return apply_filters( 'bbp_get_user_engagements', $engagements, $user_id );
}

/**
 * Get a user's engaged topic ids
 *
 * @since 2.6.0 bbPress (r6320)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_get_topic_post_type() To get the topic post type
 * @uses apply_filters() Calls 'bbp_get_user_engaged_topic_ids' with
 *                        the engaged topics and user id
 * @return array|bool Results if user has engaged, otherwise null
 */
function bbp_get_user_engaged_topic_ids( $user_id = 0 ) {
	$user_id     = bbp_get_user_id( $user_id );
	$engagements = new WP_Query( array(
		'fields'        => 'ids',
		'post_type'     => bbp_get_topic_post_type(),
		'nopaging'      => true,
		'no_found_rows' => true,
		'meta_query'    => array( array(
			'key'     => '_bbp_engagement',
			'value'   => $user_id,
			'compare' => 'NUMERIC'
		) )
	) );

	return (array) apply_filters( 'bbp_get_user_engaged_topic_ids', $engagements->posts, $user_id );
}

/**
 * Check if a user is engaged in a topic or not
 *
 * @since 2.6.0 bbPress (r6320)
 *
 * @param int $user_id Optional. User id
 * @param int $topic_id Optional. Topic id
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_get_topic_id() To get the topic id
 * @uses bbp_is_object_of_user() To check if the user has engaged
 * @uses apply_filters() Calls 'bbp_is_user_engaged' with the bool, user id,
 *                        topic id and engagements
 * @return bool True if the topic is in user's engagements, otherwise false
 */
function bbp_is_user_engaged( $user_id = 0, $topic_id = 0 ) {
	$user_id  = bbp_get_user_id( $user_id, true, true );
	$topic_id = bbp_get_topic_id( $topic_id );
	$retval   = bbp_is_object_of_user( $topic_id, $user_id, '_bbp_engagement' );

	return (bool) apply_filters( 'bbp_is_user_engaged', (bool) $retval, $user_id, $topic_id );
}

/**
 * Add a topic to user's engagements
 *
 * @since 2.6.0 bbPress (r6320)
 *
 * @param int $user_id Optional. User id
 * @param int $topic_id Optional. Topic id
 * @uses bbp_is_user_engaged() To check if the user is engaged in a topic
 * @uses do_action() Calls 'bbp_add_user_engagement' with the user id and topic id
 * @return bool Always true
 */
function bbp_add_user_engagement( $user_id = 0, $topic_id = 0 ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $topic_id ) ) {
		return false;
	}

	// Bail if no topic
	$topic = bbp_get_topic( $topic_id );
	if ( empty( $topic ) ) {
		return false;
	}

	// Bail if already a engaged
	if ( bbp_is_user_engaged( $user_id, $topic_id ) ) {
		return false;
	}

	// Bail if add fails
	if ( ! bbp_add_user_to_object( $topic_id, $user_id, '_bbp_engagement' ) ) {
		return false;
	}

	do_action( 'bbp_add_user_engagement', $user_id, $topic_id );

	return true;
}

/**
 * Remove a topic from user's engagements
 *
 * @since 2.6.0 bbPress (r6320)
 *
 * @param int $user_id Optional. User id
 * @param int $topic_id Optional. Topic id
 * @uses bbp_is_user_engaged() To check if the user is engaged in a topic
 * @uses do_action() Calls 'bbp_remove_user_engagement' with the user & topic id
 * @return bool True if the topic was removed from user's engagements, otherwise
 *               false
 */
function bbp_remove_user_engagement( $user_id, $topic_id ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $topic_id ) ) {
		return false;
	}

	// Bail if not already engaged
	if ( ! bbp_is_user_engaged( $user_id, $topic_id ) ) {
		return false;
	}

	// Bail if remove fails
	if ( ! bbp_remove_user_from_object( $topic_id, $user_id, '_bbp_engagement' ) ) {
		return false;
	}

	do_action( 'bbp_remove_user_engagement', $user_id, $topic_id );

	return true;
}

/** Favorites *****************************************************************/

/**
 * Get the users who have made the topic favorite
 *
 * @since 2.0.0 bbPress (r2658)
 *
 * @param int $topic_id Optional. Topic id
 * @uses bbp_get_users_for_object() To get user IDs who favorited
 * @uses apply_filters() Calls 'bbp_get_topic_favoriters' with the users and
 *                        topic id
 * @return array|bool Results if the topic has any favoriters, otherwise false
 */
function bbp_get_topic_favoriters( $topic_id = 0 ) {
	$topic_id = bbp_get_topic_id( $topic_id );
	$users    = bbp_get_users_for_object( $topic_id, '_bbp_favorite' );

	return (array) apply_filters( 'bbp_get_topic_favoriters', $users, $topic_id );
}

/**
 * Get a user's favorite topics
 *
 * @since 2.0.0 bbPress (r2652)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_has_topics() To get the topics
 * @uses apply_filters() Calls 'bbp_get_user_favorites' with the topic query and
 *                        user id
 * @return array|bool Results if user has favorites, otherwise false
 */
function bbp_get_user_favorites( $user_id = 0 ) {
	$user_id = bbp_get_user_id( $user_id );
	$query   = bbp_has_topics( array(
		'meta_query' => array(
			array(
				'key'     => '_bbp_favorite',
				'value'   => $user_id,
				'compare' => 'NUMERIC'
			)
		)
	) );

	return apply_filters( 'bbp_get_user_favorites', $query, $user_id );
}

/**
 * Get a user's favorite topic ids
 *
 * @since 2.0.0 bbPress (r2652)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_get_topic_post_type() To get the topic post type
 * @uses apply_filters() Calls 'bbp_get_user_favorites_topic_ids' with
 *                        the favorites and user id
 * @return array|bool Results if user has favorites, otherwise null
 */
function bbp_get_user_favorites_topic_ids( $user_id = 0 ) {
	$user_id   = bbp_get_user_id( $user_id );
	$favorites = new WP_Query( array(
		'fields'        => 'ids',
		'post_type'     => bbp_get_topic_post_type(),
		'nopaging'      => true,
		'no_found_rows' => true,
		'meta_query'    => array( array(
			'key'     => '_bbp_favorite',
			'value'   => $user_id,
			'compare' => 'NUMERIC'
		) )
	) );

	return (array) apply_filters( 'bbp_get_user_favorites_topic_ids', $favorites->posts, $user_id );
}

/**
 * Check if a topic is in user's favorites or not
 *
 * @since 2.0.0 bbPress (r2652)
 *
 * @param int $user_id Optional. User id
 * @param int $topic_id Optional. Topic id
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_get_user_favorites_topic_ids() To get the user favorites
 * @uses bbp_get_topic() To get the topic
 * @uses bbp_get_topic_id() To get the topic id
 * @uses bbp_is_object_of_user() To check if the user has a favorite
 * @uses apply_filters() Calls 'bbp_is_user_favorite' with the bool, user id,
 *                        topic id and favorites
 * @return bool True if the topic is in user's favorites, otherwise false
 */
function bbp_is_user_favorite( $user_id = 0, $topic_id = 0 ) {
	$retval    = false;
	$user_id   = bbp_get_user_id( $user_id, true, true );
	$favorites = bbp_get_user_favorites_topic_ids( $user_id );

	if ( ! empty( $favorites ) ) {

		// Checking a specific topic id
		if ( ! empty( $topic_id ) ) {
			$topic    = bbp_get_topic( $topic_id );
			$topic_id = ! empty( $topic ) ? $topic->ID : 0;

		// Using the global topic id
		} elseif ( bbp_get_topic_id() ) {
			$topic_id = bbp_get_topic_id();

		// Use the current post id
		} elseif ( ! bbp_get_topic_id() ) {
			$topic_id = get_the_ID();
		}

		// Is topic_id in the user's favorites
		if ( ! empty( $topic_id ) ) {
			$retval = bbp_is_object_of_user( $topic_id, $user_id, '_bbp_favorite' );
		}
	}

	return (bool) apply_filters( 'bbp_is_user_favorite', (bool) $retval, $user_id, $topic_id, $favorites );
}

/**
 * Add a topic to user's favorites
 *
 * @since 2.0.0 bbPress (r2652)
 *
 * @param int $user_id Optional. User id
 * @param int $topic_id Optional. Topic id
 * @uses bbp_is_user_favorite() To check if the topic is a user favorite
 * @uses do_action() Calls 'bbp_add_user_favorite' with the user id and topic id
 * @return bool Always true
 */
function bbp_add_user_favorite( $user_id = 0, $topic_id = 0 ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $topic_id ) ) {
		return false;
	}

	// Bail if no topic
	$topic = bbp_get_topic( $topic_id );
	if ( empty( $topic ) ) {
		return false;
	}

	// Bail if already a favorite
	if ( bbp_is_user_favorite( $user_id, $topic_id ) ) {
		return false;
	}

	// Bail if add fails
	if ( ! bbp_add_user_to_object( $topic_id, $user_id, '_bbp_favorite' ) ) {
		return false;
	}

	do_action( 'bbp_add_user_favorite', $user_id, $topic_id );

	return true;
}

/**
 * Remove a topic from user's favorites
 *
 * @since 2.0.0 bbPress (r2652)
 *
 * @param int $user_id Optional. User id
 * @param int $topic_id Optional. Topic id
 * @uses bbp_is_user_favorite() To check if the topic is a user favorite
 * @uses do_action() Calls 'bbp_remove_user_favorite' with the user & topic id
 * @return bool True if the topic was removed from user's favorites, otherwise
 *               false
 */
function bbp_remove_user_favorite( $user_id, $topic_id ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $topic_id ) ) {
		return false;
	}

	// Bail if not already a favorite
	if ( ! bbp_is_user_favorite( $user_id, $topic_id ) ) {
		return false;
	}

	// Bail if remove fails
	if ( ! bbp_remove_user_from_object( $topic_id, $user_id, '_bbp_favorite' ) ) {
		return false;
	}

	do_action( 'bbp_remove_user_favorite', $user_id, $topic_id );

	return true;
}

/**
 * Handles the front end adding and removing of favorite topics
 *
 * @param string $action The requested action to compare this function to
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_verify_nonce_request() To verify the nonce and check the request
 * @uses current_user_can() To check if the current user can edit the user
 * @uses bbPress:errors:add() To log the error messages
 * @uses bbp_is_user_favorite() To check if the topic is in user's favorites
 * @uses bbp_remove_user_favorite() To remove the user favorite
 * @uses bbp_add_user_favorite() To add the user favorite
 * @uses do_action() Calls 'bbp_favorites_handler' with success, user id, topic
 *                    id and action
 * @uses bbp_is_favorites() To check if it's the favorites page
 * @uses bbp_get_favorites_link() To get the favorites page link
 * @uses bbp_get_topic_permalink() To get the topic permalink
 * @uses bbp_redirect() To redirect to the url
 */
function bbp_favorites_handler( $action = '' ) {

	if ( ! bbp_is_favorites_active() ) {
		return false;
	}

	// Bail if no topic ID is passed
	if ( empty( $_GET['topic_id'] ) ) {
		return;
	}

	// Setup possible get actions
	$possible_actions = array(
		'bbp_favorite_add',
		'bbp_favorite_remove',
	);

	// Bail if actions aren't meant for this function
	if ( ! in_array( $action, $possible_actions ) ) {
		return;
	}

	// What action is taking place?
	$topic_id = intval( $_GET['topic_id'] );
	$user_id  = bbp_get_user_id( 0, true, true );

	// Check for empty topic
	if ( empty( $topic_id ) ) {
		bbp_add_error( 'bbp_favorite_topic_id', __( '<strong>ERROR</strong>: No topic was found. Which topic are you marking/unmarking as favorite?', 'bbpress' ) );

	// Check nonce
	} elseif ( ! bbp_verify_nonce_request( 'toggle-favorite_' . $topic_id ) ) {
		bbp_add_error( 'bbp_favorite_nonce', __( '<strong>ERROR</strong>: Are you sure you wanted to do that?', 'bbpress' ) );

	// Check current user's ability to edit the user
	} elseif ( ! current_user_can( 'edit_user', $user_id ) ) {
		bbp_add_error( 'bbp_favorite_permission', __( '<strong>ERROR</strong>: You do not have permission to edit favorites for that user!.', 'bbpress' ) );
	}

	// Bail if errors
	if ( bbp_has_errors() ) {
		return;
	}

	/** No errors *************************************************************/

	$is_favorite = bbp_is_user_favorite( $user_id, $topic_id );
	$success     = false;

	if ( true === $is_favorite && 'bbp_favorite_remove' === $action ) {
		$success = bbp_remove_user_favorite( $user_id, $topic_id );
	} elseif ( false === $is_favorite && 'bbp_favorite_add' === $action ) {
		$success = bbp_add_user_favorite( $user_id, $topic_id );
	}

	// Do additional favorites actions
	do_action( 'bbp_favorites_handler', $success, $user_id, $topic_id, $action );

	// Success!
	if ( true === $success ) {

		// Redirect back from whence we came
		if ( ! empty( $_REQUEST['redirect_to'] ) ) {
			$redirect = $_REQUEST['redirect_to']; // Validated later
		} elseif ( bbp_is_favorites() ) {
			$redirect = bbp_get_favorites_permalink( $user_id, true );
		} elseif ( bbp_is_single_user() ) {
			$redirect = bbp_get_user_profile_url();
		} elseif ( is_singular( bbp_get_topic_post_type() ) ) {
			$redirect = bbp_get_topic_permalink( $topic_id );
		} elseif ( is_single() || is_page() ) {
			$redirect = get_permalink();
		} else {
			$redirect = get_permalink( $topic_id );
		}

		bbp_redirect( $redirect );

	// Fail! Handle errors
	} elseif ( true === $is_favorite && 'bbp_favorite_remove' === $action ) {
		bbp_add_error( 'bbp_favorite_remove', __( '<strong>ERROR</strong>: There was a problem removing that topic from favorites.', 'bbpress' ) );
	} elseif ( false === $is_favorite && 'bbp_favorite_add' === $action ) {
		bbp_add_error( 'bbp_favorite_add',    __( '<strong>ERROR</strong>: There was a problem favoriting that topic.', 'bbpress' ) );
	}
}

/** Subscriptions *************************************************************/

/**
 * Get the users who have subscribed to the forum
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $forum_id Optional. forum id
 * @uses bbp_get_users_for_object() To get the forum subscribers
 * @uses apply_filters() Calls 'bbp_get_forum_subscribers' with the subscribers
 * @return array|bool Results if the forum has any subscribers, otherwise false
 */
function bbp_get_forum_subscribers( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$users    = bbp_get_users_for_object( $forum_id, '_bbp_subscription' );

	return (array) apply_filters( 'bbp_get_forum_subscribers', $users, $forum_id );
}

/**
 * Get the users who have subscribed to the topic
 *
 * @since 2.0.0 bbPress (r2668)
 *
 * @param int $topic_id Optional. Topic id
 * @uses bbp_get_users_for_object() To get the topic subscribers
 * @uses apply_filters() Calls 'bbp_get_topic_subscribers' with the subscribers
 * @return array|bool Results if the topic has any subscribers, otherwise false
 */
function bbp_get_topic_subscribers( $topic_id = 0 ) {
	$topic_id = bbp_get_topic_id( $topic_id );
	$users    = bbp_get_users_for_object( $topic_id, '_bbp_subscription' );

	return (array) apply_filters( 'bbp_get_topic_subscribers', $users, $topic_id );
}

/**
 * Get a user's subscribed topics
 *
 * @since 2.0.0 bbPress (r2668)
 *
 * @deprecated 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_get_user_topic_subscriptions() To get the user's subscriptions
 * @return array|bool Results if user has subscriptions, otherwise false
 */
function bbp_get_user_subscriptions( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, 2.5, 'bbp_get_user_topic_subscriptions()' );
	$query = bbp_get_user_topic_subscriptions( $user_id );
	return apply_filters( 'bbp_get_user_subscriptions', $query, $user_id );
}

/**
 * Get a user's subscribed topics
 *
 * @since 2.0.0 bbPress (r2668)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_has_topics() To get the topics
 * @uses apply_filters() Calls 'bbp_get_user_subscriptions' with the topic query
 *                        and user id
 * @return array|bool Results if user has subscriptions, otherwise false
 */
function bbp_get_user_topic_subscriptions( $user_id = 0 ) {
	$user_id = bbp_get_user_id( $user_id );
	$query   = bbp_has_topics( array(
		'meta_query' => array(
			array(
				'key'     => '_bbp_subscription',
				'value'   => $user_id,
				'compare' => 'NUMERIC'
			)
		)
	) );

	return apply_filters( 'bbp_get_user_topic_subscriptions', $query, $user_id );
}

/**
 * Get a user's subscribed forums
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_has_forums() To get the forums
 * @uses apply_filters() Calls 'bbp_get_user_forum_subscriptions' with the forum
 *                        query and user id
 * @return array|bool Results if user has subscriptions, otherwise false
 */
function bbp_get_user_forum_subscriptions( $user_id = 0 ) {
	$user_id = bbp_get_user_id( $user_id );
	$query   = bbp_has_forums( array(
		'meta_query' => array(
			array(
				'key'     => '_bbp_subscription',
				'value'   => $user_id,
				'compare' => 'NUMERIC'
			)
		)
	) );

	return apply_filters( 'bbp_get_user_forum_subscriptions', $query, $user_id );
}

/**
 * Get a user's subscribed forum ids
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_get_forum_post_type() To get the forum post type
 * @uses apply_filters() Calls 'bbp_get_user_subscribed_forum_ids' with
 *                        the subscriptions and user id
 * @return array|bool Results if user has subscriptions, otherwise null
 */
function bbp_get_user_subscribed_forum_ids( $user_id = 0 ) {
	$user_id       = bbp_get_user_id( $user_id );
	$subscriptions = new WP_Query( array(
		'fields'        => 'ids',
		'post_type'     => bbp_get_forum_post_type(),
		'nopaging'      => true,
		'no_found_rows' => true,
		'meta_query'    => array( array(
			'key'     => '_bbp_subscription',
			'value'   => $user_id,
			'compare' => 'NUMERIC'
		) )
	) );

	return (array) apply_filters( 'bbp_get_user_subscribed_forum_ids', $subscriptions->posts, $user_id );
}

/**
 * Get a user's subscribed topics' ids
 *
 * @since 2.0.0 bbPress (r2668)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_get_topic_post_type() To get the topic post type
 * @uses apply_filters() Calls 'bbp_get_user_subscribed_topic_ids' with
 *                        the subscriptions and user id
 * @return array|bool Results if user has subscriptions, otherwise null
 */
function bbp_get_user_subscribed_topic_ids( $user_id = 0 ) {
	$user_id       = bbp_get_user_id( $user_id );
	$subscriptions = new WP_Query( array(
		'fields'        => 'ids',
		'post_type'     => bbp_get_topic_post_type(),
		'nopaging'      => true,
		'no_found_rows' => true,
		'meta_query' => array( array(
			'key'     => '_bbp_subscription',
			'value'   => $user_id,
			'compare' => 'NUMERIC'
		) )
	) );

	return (array) apply_filters( 'bbp_get_user_subscribed_topic_ids', $subscriptions->posts, $user_id );
}

/**
 * Check if a topic or forum is in user's subscription list or not
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @param int $object_id Optional. Topic id
 * @uses get_post() To get the post object
 * @uses bbp_get_user_subscribed_forum_ids() To get the user's forum subscriptions
 * @uses bbp_get_user_subscribed_topic_ids() To get the user's topic subscriptions
 * @uses bbp_get_forum_post_type() To get the forum post type
 * @uses bbp_get_topic_post_type() To get the topic post type
 * @uses apply_filters() Calls 'bbp_is_user_subscribed' with the bool, user id,
 *                        forum/topic id and subsriptions
 * @return bool True if the forum or topic is in user's subscriptions, otherwise false
 */
function bbp_is_user_subscribed( $user_id = 0, $object_id = 0 ) {

	// Assume user is not subscribed
	$retval = false;

	// Setup ID's array
	$subscribed_ids = array();

	// User and object ID's are passed
	if ( ! empty( $user_id ) && ! empty( $object_id ) ) {

		// Get the post type
		$post_type = get_post_type( $object_id );

		// Post exists, so check the types
		if ( ! empty( $post_type ) ) {

			switch( $post_type ) {

				// Forum
				case bbp_get_forum_post_type() :
					$subscribed_ids = bbp_get_user_subscribed_forum_ids( $user_id );
					$retval         = bbp_is_user_subscribed_to_forum( $user_id, $object_id, $subscribed_ids );
					break;

				// Topic (default)
				case bbp_get_topic_post_type() :
				default :
					$subscribed_ids = bbp_get_user_subscribed_topic_ids( $user_id );
					$retval         = bbp_is_user_subscribed_to_topic( $user_id, $object_id, $subscribed_ids );
					break;
			}
		}
	}

	return (bool) apply_filters( 'bbp_is_user_subscribed', $retval, $user_id, $object_id, $subscribed_ids );
}

/**
 * Check if a forum is in user's subscription list or not
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @param int $forum_id Optional. Topic id
 * @param array $subscribed_ids Optional. Array of forum ID's to check
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_get_forum() To get the forum
 * @uses bbp_get_forum_id() To get the forum id
 * @uses bbp_is_object_of_user() To check if the user has a subscription
 * @uses apply_filters() Calls 'bbp_is_user_subscribed_to_forum' with the bool, user id,
 *                        forum id and subsriptions
 * @return bool True if the forum is in user's subscriptions, otherwise false
 */
function bbp_is_user_subscribed_to_forum( $user_id = 0, $forum_id = 0, $subscribed_ids = array() ) {

	// Assume user is not subscribed
	$retval = false;

	// Validate user
	$user_id = bbp_get_user_id( $user_id, true, true );
	if ( ! empty( $user_id ) ) {

		// Get subscription ID's if none passed
		if ( empty( $subscribed_ids ) ) {
			$subscribed_ids = bbp_get_user_subscribed_forum_ids( $user_id );
		}

		// User has forum subscriptions
		if ( ! empty( $subscribed_ids ) ) {

			// Checking a specific forum id
			if ( ! empty( $forum_id ) ) {
				$forum    = bbp_get_forum( $forum_id );
				$forum_id = ! empty( $forum ) ? $forum->ID : 0;

			// Using the global forum id
			} elseif ( bbp_get_forum_id() ) {
				$forum_id = bbp_get_forum_id();

			// Use the current post id
			} elseif ( ! bbp_get_forum_id() ) {
				$forum_id = get_the_ID();
			}

			// Is forum_id in the user's subscriptions
			if ( ! empty( $forum_id ) ) {
				$retval = bbp_is_object_of_user( $forum_id, $user_id, '_bbp_subscription' );
			}
		}
	}

	return (bool) apply_filters( 'bbp_is_user_subscribed_to_forum', (bool) $retval, $user_id, $forum_id, $subscribed_ids );
}

/**
 * Check if a topic is in user's subscription list or not
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @param int $topic_id Optional. Topic id
 * @param array $subscribed_ids Optional. Array of topic ID's to check
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_get_topic() To get the topic
 * @uses bbp_get_topic_id() To get the topic id
 * @uses bbp_is_object_of_user() To check if the user is subscribed
 * @uses apply_filters() Calls 'bbp_is_user_subscribed_to_topic' with the bool, user id,
 *                        topic id and subsriptions
 * @return bool True if the topic is in user's subscriptions, otherwise false
 */
function bbp_is_user_subscribed_to_topic( $user_id = 0, $topic_id = 0, $subscribed_ids = array() ) {

	// Assume user is not subscribed
	$retval = false;

	// Validate user
	$user_id = bbp_get_user_id( $user_id, true, true );
	if ( ! empty( $user_id ) ) {

		// Get subscription ID's if none passed
		if ( empty( $subscribed_ids ) ) {
			$subscribed_ids = bbp_get_user_subscribed_topic_ids( $user_id );
		}

		// User has topic subscriptions
		if ( ! empty( $subscribed_ids ) ) {

			// Checking a specific topic id
			if ( ! empty( $topic_id ) ) {
				$topic    = bbp_get_topic( $topic_id );
				$topic_id = ! empty( $topic ) ? $topic->ID : 0;

			// Using the global topic id
			} elseif ( bbp_get_topic_id() ) {
				$topic_id = bbp_get_topic_id();

			// Use the current post id
			} elseif ( ! bbp_get_topic_id() ) {
				$topic_id = get_the_ID();
			}

			// Is topic_id in the user's subscriptions
			if ( ! empty( $topic_id ) ) {
				$retval = bbp_is_object_of_user( $topic_id, $user_id, '_bbp_subscription' );
			}
		}
	}

	return (bool) apply_filters( 'bbp_is_user_subscribed_to_topic', (bool) $retval, $user_id, $topic_id, $subscribed_ids );
}

/**
 * Add a user subscription
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @param int $object_id Optional. Topic id
 * @uses get_post() To get the post object
 * @uses do_action() Calls 'bbp_add_user_subscription' with the user & object id
 * @return bool Always true
 */
function bbp_add_user_subscription( $user_id = 0, $object_id = 0 ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $object_id ) ) {
		return false;
	}

	// Get the post type
	$post_type = get_post_type( $object_id );
	if ( empty( $post_type ) ) {
		return false;
	}

	// Bail if already subscribed
	if ( bbp_is_user_subscribed( $user_id, $object_id ) ) {
		return false;
	}

	// Bail if add fails
	if ( ! bbp_add_user_to_object( $object_id, $user_id, '_bbp_subscription' ) ) {
		return false;
	}

	do_action( 'bbp_add_user_subscription', $user_id, $object_id, $post_type );

	return true;
}

/**
 * Add a forum to user's subscriptions
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @param int $forum_id Optional. forum id
 * @uses bbp_get_forum() To get the forum
 * @uses bbp_add_user_subscription() To add the user subscription
 * @uses do_action() Calls 'bbp_add_user_subscription' with the user & forum id
 * @return bool Always true
 */
function bbp_add_user_forum_subscription( $user_id = 0, $forum_id = 0 ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $forum_id ) ) {
		return false;
	}

	// Bail if no forum
	$forum = bbp_get_forum( $forum_id );
	if ( empty( $forum ) ) {
		return false;
	}

	// Bail if already subscribed
	if ( bbp_is_user_subscribed( $user_id, $forum_id ) ) {
		return false;
	}

	// Bail if add fails
	if ( ! bbp_add_user_subscription( $user_id, $forum_id ) ) {
		return false;
	}

	do_action( 'bbp_add_user_forum_subscription', $user_id, $forum_id );

	return true;
}

/**
 * Add a topic to user's subscriptions
 *
 * @since 2.0.0 bbPress (r2668)
 *
 * @param int $user_id Optional. User id
 * @param int $topic_id Optional. Topic id
 * @uses bbp_get_topic() To get the topic
 * @uses bbp_add_user_subscription() To add the subscription
 * @uses do_action() Calls 'bbp_add_user_subscription' with the user & topic id
 * @return bool Always true
 */
function bbp_add_user_topic_subscription( $user_id = 0, $topic_id = 0 ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $topic_id ) ) {
		return false;
	}

	// Bail if no topic
	$topic = bbp_get_topic( $topic_id );
	if ( empty( $topic ) ) {
		return false;
	}

	// Bail if already subscribed
	if ( bbp_is_user_subscribed_to_topic( $user_id, $topic_id ) ) {
		return false;
	}

	// Bail if add fails
	if ( ! bbp_add_user_subscription( $user_id, $topic_id ) ) {
		return false;
	}

	do_action( 'bbp_add_user_topic_subscription', $user_id, $topic_id );

	return true;
}

/**
 * Remove a user subscription
 *
 * @since 2.0.0 bbPress (r2668)
 *
 * @param int $user_id Optional. User id
 * @param int $object_id Optional. Topic id
 * @uses get_post() To get the post object
 * @uses bbp_is_user_subscribed() To check if the user is already subscribed
 * @uses do_action() Calls 'bbp_remove_user_subscription' with the user id and
 *                    topic id
 * @return bool True if the topic was removed from user's subscriptions,
 *               otherwise false
 */
function bbp_remove_user_subscription( $user_id = 0, $object_id = 0 ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $object_id ) ) {
		return false;
	}

	// Get post type
	$post_type = get_post_type( $object_id );
	if ( empty( $post_type ) ) {
		return false;
	}

	// Bail if not subscribed
	if ( ! bbp_is_user_subscribed( $user_id, $object_id ) ) {
		return false;
	}

	// Bail if remove fails
	if ( ! bbp_remove_user_from_object( $object_id, $user_id, '_bbp_subscription' ) ) {
		return false;
	}

	do_action( 'bbp_remove_user_subscription', $user_id, $object_id, $post_type );

	return true;
}

/**
 * Remove a forum from user's subscriptions
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @param int $forum_id Optional. forum id
 * @uses bbp_remove_user_subscription() To remove the subscription
 * @uses do_action() Calls 'bbp_remove_user_subscription' with the user id and
 *                    forum id
 * @return bool True if the forum was removed from user's subscriptions,
 *               otherwise false
 */
function bbp_remove_user_forum_subscription( $user_id = 0, $forum_id = 0 ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $forum_id ) ) {
		return false;
	}

	// Bail if remove fails
	if ( ! bbp_remove_user_subscription( $user_id, $forum_id ) ) {
		return false;
	}

	do_action( 'bbp_remove_user_forum_subscription', $user_id, $forum_id );

	return true;
}

/**
 * Remove a topic from user's subscriptions
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param int $user_id Optional. User id
 * @param int $topic_id Optional. Topic id
 * @uses bbp_remove_user_subscription() To remove the subscription
 * @uses do_action() Calls 'bbp_remove_user_topic_subscription' with the user id and
 *                    topic id
 * @return bool True if the topic was removed from user's subscriptions,
 *               otherwise false
 */
function bbp_remove_user_topic_subscription( $user_id = 0, $topic_id = 0 ) {

	// Bail if not enough info
	if ( empty( $user_id ) || empty( $topic_id ) ) {
		return false;
	}

	// Bail if remove fails
	if ( ! bbp_remove_user_subscription( $user_id, $topic_id ) ) {
		return false;
	}

	do_action( 'bbp_remove_user_topic_subscription', $user_id, $topic_id );

	return true;
}

/**
 * Handles the front end subscribing and unsubscribing forums
 *
 * @since 2.5.0 bbPress (r5156)
 *
 * @param string $action The requested action to compare this function to
 * @uses bbp_is_subscriptions_active() To check if the subscriptions are active
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_verify_nonce_request() To verify the nonce and check the request
 * @uses current_user_can() To check if the current user can edit the user
 * @uses bbPress:errors:add() To log the error messages
 * @uses bbp_is_user_subscribed() To check if the forum is in user's
 *                                 subscriptions
 * @uses bbp_remove_user_subscription() To remove the user subscription
 * @uses bbp_add_user_subscription() To add the user subscription
 * @uses do_action() Calls 'bbp_subscriptions_handler' with success, user id,
 *                    forum id and action
 * @uses bbp_is_subscription() To check if it's the subscription page
 * @uses bbp_get_forum_permalink() To get the forum permalink
 * @uses bbp_redirect() To redirect to the url
 */
function bbp_forum_subscriptions_handler( $action = '' ) {

	if ( ! bbp_is_subscriptions_active() ) {
		return false;
	}

	// Bail if no forum ID is passed
	if ( empty( $_GET['forum_id'] ) ) {
		return;
	}

	// Setup possible get actions
	$possible_actions = array(
		'bbp_subscribe',
		'bbp_unsubscribe',
	);

	// Bail if actions aren't meant for this function
	if ( ! in_array( $action, $possible_actions ) ) {
		return;
	}

	// Get required data
	$user_id  = bbp_get_user_id( 0, true, true );
	$forum_id = intval( $_GET['forum_id'] );

	// Check for empty forum
	if ( empty( $forum_id ) ) {
		bbp_add_error( 'bbp_subscription_forum_id', __( '<strong>ERROR</strong>: No forum was found. Which forum are you subscribing/unsubscribing to?', 'bbpress' ) );

	// Check nonce
	} elseif ( ! bbp_verify_nonce_request( 'toggle-subscription_' . $forum_id ) ) {
		bbp_add_error( 'bbp_subscription_forum_id', __( '<strong>ERROR</strong>: Are you sure you wanted to do that?', 'bbpress' ) );

	// Check current user's ability to edit the user
	} elseif ( ! current_user_can( 'edit_user', $user_id ) ) {
		bbp_add_error( 'bbp_subscription_permission', __( '<strong>ERROR</strong>: You do not have permission to edit favorites of that user.', 'bbpress' ) );
	}

	// Bail if we have errors
	if ( bbp_has_errors() ) {
		return;
	}

	/** No errors *************************************************************/

	$is_subscription = bbp_is_user_subscribed( $user_id, $forum_id );
	$success         = false;

	if ( true === $is_subscription && 'bbp_unsubscribe' === $action ) {
		$success = bbp_remove_user_subscription( $user_id, $forum_id );
	} elseif ( false === $is_subscription && 'bbp_subscribe' === $action ) {
		$success = bbp_add_user_subscription( $user_id, $forum_id );
	}

	// Do additional subscriptions actions
	do_action( 'bbp_subscriptions_handler', $success, $user_id, $forum_id, $action );

	// Success!
	if ( true === $success ) {

		// Redirect back from whence we came
		if ( ! empty( $_REQUEST['redirect_to'] ) ) {
			$redirect = $_REQUEST['redirect_to']; // Validated later
		} elseif ( bbp_is_subscriptions() ) {
			$redirect = bbp_get_subscriptions_permalink( $user_id );
		} elseif ( bbp_is_single_user() ) {
			$redirect = bbp_get_user_profile_url();
		} elseif ( is_singular( bbp_get_forum_post_type() ) ) {
			$redirect = bbp_get_forum_permalink( $forum_id );
		} elseif ( is_single() || is_page() ) {
			$redirect = get_permalink();
		} else {
			$redirect = get_permalink( $forum_id );
		}

		bbp_redirect( $redirect );

	// Fail! Handle errors
	} elseif ( true === $is_subscription && 'bbp_unsubscribe' === $action ) {
		bbp_add_error( 'bbp_unsubscribe', __( '<strong>ERROR</strong>: There was a problem unsubscribing from that forum.', 'bbpress' ) );
	} elseif ( false === $is_subscription && 'bbp_subscribe' === $action ) {
		bbp_add_error( 'bbp_subscribe',    __( '<strong>ERROR</strong>: There was a problem subscribing to that forum.', 'bbpress' ) );
	}
}

/**
 * Handles the front end subscribing and unsubscribing topics
 *
 * @since 2.0.0 bbPress (r2790)
 *
 * @param string $action The requested action to compare this function to
 * @uses bbp_is_subscriptions_active() To check if the subscriptions are active
 * @uses bbp_get_user_id() To get the user id
 * @uses bbp_verify_nonce_request() To verify the nonce and check the request
 * @uses current_user_can() To check if the current user can edit the user
 * @uses bbPress:errors:add() To log the error messages
 * @uses bbp_is_user_subscribed() To check if the topic is in user's
 *                                 subscriptions
 * @uses bbp_remove_user_subscription() To remove the user subscription
 * @uses bbp_add_user_subscription() To add the user subscription
 * @uses do_action() Calls 'bbp_subscriptions_handler' with success, user id,
 *                    topic id and action
 * @uses bbp_is_subscription() To check if it's the subscription page
 * @uses bbp_get_topic_permalink() To get the topic permalink
 * @uses bbp_redirect() To redirect to the url
 */
function bbp_subscriptions_handler( $action = '' ) {

	if ( ! bbp_is_subscriptions_active() ) {
		return false;
	}

	// Bail if no topic ID is passed
	if ( empty( $_GET['topic_id'] ) ) {
		return;
	}

	// Setup possible get actions
	$possible_actions = array(
		'bbp_subscribe',
		'bbp_unsubscribe',
	);

	// Bail if actions aren't meant for this function
	if ( ! in_array( $action, $possible_actions ) ) {
		return;
	}

	// Get required data
	$user_id  = bbp_get_user_id( 0, true, true );
	$topic_id = intval( $_GET['topic_id'] );

	// Check for empty topic
	if ( empty( $topic_id ) ) {
		bbp_add_error( 'bbp_subscription_topic_id', __( '<strong>ERROR</strong>: No topic was found. Which topic are you subscribing/unsubscribing to?', 'bbpress' ) );

	// Check nonce
	} elseif ( ! bbp_verify_nonce_request( 'toggle-subscription_' . $topic_id ) ) {
		bbp_add_error( 'bbp_subscription_topic_id', __( '<strong>ERROR</strong>: Are you sure you wanted to do that?', 'bbpress' ) );

	// Check current user's ability to edit the user
	} elseif ( ! current_user_can( 'edit_user', $user_id ) ) {
		bbp_add_error( 'bbp_subscription_permission', __( '<strong>ERROR</strong>: You do not have permission to edit favorites of that user.', 'bbpress' ) );
	}

	// Bail if we have errors
	if ( bbp_has_errors() ) {
		return;
	}

	/** No errors *************************************************************/

	$is_subscription = bbp_is_user_subscribed( $user_id, $topic_id );
	$success         = false;

	if ( true === $is_subscription && 'bbp_unsubscribe' === $action ) {
		$success = bbp_remove_user_subscription( $user_id, $topic_id );
	} elseif ( false === $is_subscription && 'bbp_subscribe' === $action ) {
		$success = bbp_add_user_subscription( $user_id, $topic_id );
	}

	// Do additional subscriptions actions
	do_action( 'bbp_subscriptions_handler', $success, $user_id, $topic_id, $action );

	// Success!
	if ( true === $success ) {

		// Redirect back from whence we came
		if ( ! empty( $_REQUEST['redirect_to'] ) ) {
			$redirect = $_REQUEST['redirect_to']; // Validated later
		} elseif ( bbp_is_subscriptions() ) {
			$redirect = bbp_get_subscriptions_permalink( $user_id );
		} elseif ( bbp_is_single_user() ) {
			$redirect = bbp_get_user_profile_url();
		} elseif ( is_singular( bbp_get_topic_post_type() ) ) {
			$redirect = bbp_get_topic_permalink( $topic_id );
		} elseif ( is_single() || is_page() ) {
			$redirect = get_permalink();
		} else {
			$redirect = get_permalink( $topic_id );
		}

		bbp_redirect( $redirect );

	// Fail! Handle errors
	} elseif ( true === $is_subscription && 'bbp_unsubscribe' === $action ) {
		bbp_add_error( 'bbp_unsubscribe', __( '<strong>ERROR</strong>: There was a problem unsubscribing from that topic.', 'bbpress' ) );
	} elseif ( false === $is_subscription && 'bbp_subscribe' === $action ) {
		bbp_add_error( 'bbp_subscribe',    __( '<strong>ERROR</strong>: There was a problem subscribing to that topic.', 'bbpress' ) );
	}
}

/** Edit **********************************************************************/

/**
 * Handles the front end user editing from POST requests
 *
 * @since 2.0.0 bbPress (r2790)
 *
 * @param string $action The requested action to compare this function to
 * @uses is_multisite() To check if it's a multisite
 * @uses bbp_is_user_home() To check if the user is at home (the display page
 *                           is the one of the logged in user)
 * @uses get_option() To get the displayed user's new email id option
 * @uses wp_update_user() To update the user
 * @uses delete_option() To delete the displayed user's email id option
 * @uses bbp_get_user_profile_edit_url() To get the edit profile url
 * @uses bbp_redirect() To redirect to the url
 * @uses bbp_verify_nonce_request() To verify the nonce and check the request
 * @uses current_user_can() To check if the current user can edit the user
 * @uses do_action() Calls 'personal_options_update' or
 *                   'edit_user_options_update' (based on if it's the user home)
 *                   with the displayed user id
 * @uses edit_user() To edit the user based on the post data
 * @uses get_userdata() To get the user data
 * @uses is_email() To check if the string is an email id or not
 * @uses is_network_admin() To check if the user is the network admin
 * @uses revoke_super_admin() To revoke super admin privileges
 * @uses grant_super_admin() To grant super admin privileges
 * @uses is_wp_error() To check if the value retrieved is a {@link WP_Error}
 */
function bbp_edit_user_handler( $action = '' ) {

	// Bail if action is not `bbp-update-user`
	if ( 'bbp-update-user' !== $action ) {
		return;
	}

	// Get the displayed user ID
	$user_id = bbp_get_displayed_user_id();

	// Nonce check
	if ( ! bbp_verify_nonce_request( 'update-user_' . $user_id ) ) {
		bbp_add_error( 'bbp_update_user_nonce', __( '<strong>ERROR</strong>: Are you sure you wanted to do that?', 'bbpress' ) );
		return;
	}

	// Cap check
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		bbp_add_error( 'bbp_update_user_capability', __( '<strong>ERROR</strong>: Are you sure you wanted to do that?', 'bbpress' ) );
		return;
	}

	// Empty email check
	if ( empty( $_POST['email'] ) ) {
		bbp_add_error( 'bbp_user_email_empty', __( '<strong>ERROR</strong>: That is not a valid email address.', 'bbpress' ), array( 'form-field' => 'email' ) );
		return;
	}

	// Get the users current email address to use for comparisons
	$user_email = bbp_get_displayed_user_field( 'user_email', 'raw' );

	// Bail if no email change
	if ( $user_email !== $_POST['email'] ) {

		// Check that new email address is valid
		if ( ! is_email( $_POST['email'] ) ) {
			bbp_add_error( 'bbp_user_email_invalid', __( '<strong>ERROR</strong>: That is not a valid email address.', 'bbpress' ), array( 'form-field' => 'email' ) );
			return;
		}

		// Check if email address is already in use
		if ( email_exists( $_POST['email'] ) ) {
			bbp_add_error( 'bbp_user_email_taken', __( '<strong>ERROR</strong>: That email address is already in use.', 'bbpress' ), array( 'form-field' => 'email' ) );
			return;
		}

		// Update the option
		$key    = $user_id . '_new_email';
		$hash   = md5( $_POST['email'] . time() . mt_rand() );
		$option = array(
			'hash'     => $hash,
			'newemail' => $_POST['email']
		);
		update_option( $key, $option );

		// Attempt to notify the user of email address change
		bbp_edit_user_email_send_notification( $user_id, $option );

		// Set the POST email variable back to the user's email address
		// so `edit_user()` does not attempt to update it. This is not ideal,
		// but it's also what send_confirmation_on_profile_email() does.
		$_POST['email'] = $user_email;
	}

	// Do action based on who's profile you're editing
	$edit_action = bbp_is_user_home_edit()
		? 'personal_options_update'
		: 'edit_user_profile_update';

	do_action( $edit_action, $user_id );

	// Prevent edit_user() from wiping out the user's Toolbar on front setting
	if ( ! isset( $_POST['admin_bar_front'] ) && _get_admin_bar_pref( 'front', $user_id ) ) {
		$_POST['admin_bar_front'] = 1;
	}

	// Bail if errors already exist
	if ( bbp_has_errors() ) {
		return;
	}

	// Handle user edit
	$edit_user = edit_user( $user_id );

	// Error(s) editng the user, so copy them into the global
	if ( is_wp_error( $edit_user ) ) {
		bbpress()->errors = $edit_user;

	// Successful edit to redirect
	} elseif ( is_integer( $edit_user ) ) {

		// Maybe update super admin ability
		if ( is_multisite() && ! bbp_is_user_home_edit() && current_user_can( 'manage_network_options' ) && is_super_admin() ) {
			empty( $_POST['super_admin'] )
				? revoke_super_admin( $edit_user )
				: grant_super_admin( $edit_user );
		}

		// Redirect
		$args     = array( 'updated' => 'true' );
		$user_url = bbp_get_user_profile_edit_url( $edit_user );
		$redirect = add_query_arg( $args, $user_url );

		bbp_redirect( $redirect );
	}
}

/**
 * Handles user email address updating from GET requests
 *
 * @since 2.6.0 bbPress (r5660)
 *
 * @param string $action
 *
 * @uses bbp_is_user_home_edit()         To check if on the current users profile edit page
 * @uses bbp_get_displayed_user_id()     To get the ID of the user being edited
 * @uses bbp_get_user_profile_edit_url() To get the URL of the user being edited
 * @uses bbp_redirect()                  To redirect away from the current page
 * @uses hash_equals()                   To compare email hash to saved option hash
 * @uses email_exists()                  To check if user has email address already
 * @uses bbp_add_error()                 To add user feedback
 * @uses wp_update_user()                To update the user with their new email address
 * @uses bbp_verify_nonce_request()      To verify the intent of the user
 */
function bbp_user_email_change_handler( $action = '' ) {

	// Bail if action is not `bbp-update-user-email`
	if ( 'bbp-update-user-email' !== $action ) {
		return;
	}

	// Bail if not on users own profile
	if ( ! bbp_is_user_home_edit() ) {
		return;
	}

	// Bail if not attempting to modify user email address
	if ( empty( $_GET['newuseremail'] ) && empty( $_GET['dismiss'] ) ) {
		return;
	}

	// Get the displayed user ID & option key
	$user_id     = bbp_get_displayed_user_id();
	$key         = $user_id . '_new_email';
	$redirect_to = bbp_get_user_profile_edit_url( $user_id );

	// Execute confirmed email change.
	if ( ! empty( $_GET['newuseremail'] ) ) {

		// Check for email address change option
		$new_email = get_option( $key );

		// Redirect if *no* email address change exists
		if ( false === $new_email ) {
			bbp_redirect( $redirect_to );
		}

		// Cleanup & redirect if *invalid* email address change exists
		if ( empty( $new_email['hash'] ) || empty( $new_email['newemail'] ) ) {
			delete_option( $key );

			bbp_redirect( $redirect_to );
		}

		// Compare hashes, and update user if hashes match
		if ( hash_equals( $new_email['hash'], $_GET['newuseremail'] ) ) {

			// Does another user have this email address already?
			if ( email_exists( $new_email['newemail'] ) ) {
				delete_option( $key );

				bbp_add_error( 'bbp_user_email_taken', __( '<strong>ERROR</strong>: That email address is already in use.', 'bbpress' ), array( 'form-field' => 'email' ) );

			// Email address is good to change to
			} else {

				// Create a stdClass (for easy call to wp_update_user())
				$user             = new stdClass();
				$user->ID         = $user_id;
				$user->user_email = esc_html( trim( $new_email['newemail'] ) );

				// Attempt to update user email
				$update_user = wp_update_user( $user );

				// Error(s) editing the user, so copy them into the global
				if ( is_wp_error( $update_user ) ) {
					bbpress()->errors = $update_user;

				// All done, so redirect and show the updated message
				} else {

					// Update signups table, if signups table & entry exists
					// For Multisite & BuddyPress compatibility
					$bbp_db = bbp_db();
					if ( ! empty( $bbp_db->signups ) && $bbp_db->get_var( $bbp_db->prepare( "SELECT user_login FROM {$bbp_db->signups} WHERE user_login = %s", bbp_get_displayed_user_field( 'user_login', 'raw' ) ) ) ) {
						$bbp_db->query( $bbp_db->prepare( "UPDATE {$bbp_db->signups} SET user_email = %s WHERE user_login = %s", $user->user_email, bbp_get_displayed_user_field( 'user_login', 'raw' ) ) );
					}

					delete_option( $key );

					bbp_redirect( add_query_arg( array( 'updated' => 'true' ), $redirect_to ) );
				}
			}
		}

	// Delete new email address from user options
	} elseif ( ! empty( $_GET['dismiss'] ) && ( $key === $_GET['dismiss'] ) ) {
		if ( ! bbp_verify_nonce_request( "dismiss-{$key}" ) ) {
			bbp_add_error( 'bbp_dismiss_new_email_nonce', __( '<strong>ERROR</strong>: Are you sure you wanted to do that?', 'bbpress' ) );
			return;
		}

		delete_option( $key );
		bbp_redirect( $redirect_to );
	}
}

/**
 * Sends an email when an email address change occurs on POST requests
 *
 * @since 2.6.0 bbPress (r5660)
 *
 * @see send_confirmation_on_profile_email()
 *
 * @uses bbp_parse_args()                To parse the option arguments
 * @uses bbp_add_error()                 To provide feedback to user
 * @uses bbp_get_displayed_user_field()  To get the user_login
 * @uses bbp_get_user_profile_edit_url() To get the user profile edit link
 * @uses add_query_arg()                 To add arguments the link
 * @uses wp_mail()                       To send the notification
 */
function bbp_edit_user_email_send_notification( $user_id = 0, $args = array() ) {

	// Parse args
	$r = bbp_parse_args( $args, array(
		'hash'     => '',
		'newemail' => '',
	) );

	// Bail if any relevant parameters are empty
	if ( empty( $user_id ) || empty( $r['hash'] ) || empty( $r['newemail'] ) ) {
		bbp_add_error( 'bbp_user_email_invalid_hash', __( '<strong>ERROR</strong>: An error occurred while updating your email address.', 'bbpress' ), array( 'form-field' => 'email' ) );
		return;
	}

	// Build the nonced URL to dismiss the pending change
	$user_login  = bbp_get_displayed_user_field( 'user_login', 'raw' );
	$user_url    = bbp_get_user_profile_edit_url( $user_id );
	$confirm_url = add_query_arg( array(
		'action'       => 'bbp-update-user-email',
		'newuseremail' => $r['hash']
	), $user_url );

	$email_text = __( '%1$s

Someone requested a change to the email address on your account.

Please click the following link to confirm this change:
%2$s

If you did not request this, you can safely ignore and delete this notification.

This email was sent to: %3$s

Regards,
The %4$s Team
%5$s', 'bbpress' );

	/**
	 * Filter the email text sent when a user changes emails.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 *
	 * %1$s - The current user's username
	 * %2$s - The link to click on to confirm the email change
	 * %3$s - The new email
	 * %4$s - The name of the site
	 * %5$s - The URL to the site
	 *
	 * @param string $email_text Text in the email.
	 * @param string $r          New user email that the current user has changed to.
	 */
	$content = apply_filters( 'bbp_user_email_update_content', $email_text, $r );

	// Build the email message
	$message = sprintf( $content, $user_login, $confirm_url, $r['newemail'], get_site_option( 'site_name' ), network_home_url() );

	// Build the email subject
	$subject = sprintf( __( '[%s] New Email Address', 'bbpress' ), wp_specialchars_decode( get_option( 'blogname' ) ) );

	// Send the email
	wp_mail( $r['newemail'], $subject, $message );
}

/**
 * Conditionally hook the core WordPress output actions to the end of the
 * default user's edit profile template
 *
 * This allows clever plugin authors to conditionally unhook the WordPress core
 * output actions if they don't want any unexpected junk to appear there, and
 * also avoids needing to pollute the templates with additional logic and actions.
 *
 * @since 2.2.0 bbPress (r4273)
 *
 * @uses bbp_is_user_home_edit() To switch the action fired
 * @uses get_userdata() To get the current user's data
 * @uses bbp_get_displayed_user_id() To get the currently displayed user ID
 */
function bbp_user_edit_after() {
	$action = bbp_is_user_home_edit() ? 'show_user_profile' : 'edit_user_profile';

	do_action( $action, get_userdata( bbp_get_displayed_user_id() ) );
}

/** User Queries **************************************************************/

/**
 * Get the topics that a user created
 *
 * @since 2.0.0 bbPress (r2660)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_get_user_id() To get the topic id
 * @uses bbp_has_topics() To get the topics created by the user
 * @return array|bool Results if the user has created topics, otherwise false
 */
function bbp_get_user_topics_started( $user_id = 0 ) {

	// Validate user
	$user_id = bbp_get_user_id( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	// Try to get the topics
	$query = bbp_has_topics( array(
		'author' => $user_id
	) );

	return apply_filters( 'bbp_get_user_topics_started', $query, $user_id );
}

/**
 * Get the replies that a user created
 *
 * @since 2.2.0 bbPress (r4225)
 *
 * @param int $user_id Optional. User id
 * @uses bbp_get_user_id() To get the topic id
 * @uses bbp_has_replies() To get the topics created by the user
 * @return array|bool Results if the user has created topics, otherwise false
 */
function bbp_get_user_replies_created( $user_id = 0 ) {

	// Validate user
	$user_id = bbp_get_user_id( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	// Try to get the topics
	$query = bbp_has_replies( array(
		'post_type' => bbp_get_reply_post_type(),
		'order'     => 'DESC',
		'author'    => $user_id
	) );

	return apply_filters( 'bbp_get_user_replies_created', $query, $user_id );
}

/**
 * Get the total number of users on the forums
 *
 * @since 2.0.0 bbPress (r2769)
 *
 * @uses count_users() To execute our query and get the var back
 * @uses apply_filters() Calls 'bbp_get_total_users' with number of users
 * @return int Total number of users
 */
function bbp_get_total_users() {
	$user_count = count_users();
	return apply_filters( 'bbp_get_total_users', (int) $user_count['total_users'] );
}

/**
 * Get user IDs from nicenames
 *
 * This function is primarily used when saving object moderators
 *
 * @since 2.6.0 bbPress
 *
 * @param mixed $user_nicenames
 * @return array
 */
function bbp_get_user_ids_from_nicenames( $user_nicenames = array() ) {

	// Default value
	$retval = array();

	// Only query if nicenames
	if ( ! empty( $user_nicenames ) ) {

		// Maybe explode by comma
		$user_nicenames = ( is_string( $user_nicenames ) && strstr( $user_nicenames, ',' ) )
			? explode( ',', $user_nicenames )
			: (array) $user_nicenames;

		// Get users
		$users = get_users( array(
			'nicename__in' => $user_nicenames
		) );

		// Pluck or empty
		if ( ! empty( $users ) ) {
			$retval = wp_list_pluck( $users, 'ID' );
		}
	}

	return apply_filters( 'bbp_get_user_ids_from_nicenames', $retval, $user_nicenames );
}

/**
 * Get user nicenames from IDs
 *
 * This function is primarily used when saving object moderators
 *
 * @since 2.6.0 bbPress
 *
 * @param mixed $user_ids
 * @return array
 */
function bbp_get_user_nicenames_from_ids( $user_ids = array() ) {

	// Default value
	$retval = array();

	// Only query if nicenames
	if ( ! empty( $user_ids ) ) {

		// Get users
		$users = get_users( array(
			'include' => $user_ids
		) );

		// Pluck or empty
		if ( ! empty( $users ) ) {
			$retval = wp_list_pluck( $users, 'user_nicename' );
		}
	}

	return apply_filters( 'bbp_get_user_nicenames_from_ids', $retval, $user_ids );
}

/** Post Counts ***************************************************************/

/**
 * Return the raw database count of topics by a user
 *
 * @since 2.1.0 bbPress (r3633)
 *
 * @param int $user_id User ID to get count for
 *
 * @uses bbp_get_user_id()
 * @uses get_posts_by_author_sql()
 * @uses bbp_get_topic_post_type()
 * @uses apply_filters()
 *
 * @return int Raw DB count of topics
 */
function bbp_get_user_topic_count_raw( $user_id = 0 ) {
	$user_id = bbp_get_user_id( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	$count = count_user_posts( $user_id, bbp_get_topic_post_type(), false );
	// Manually add the user closed topic count, see #2978 and #WP12706
	$count = $count + bbp_get_user_closed_topic_count( $user_id );

	return (int) apply_filters( 'bbp_get_user_topic_count_raw', $count, $user_id );
}

/**
 * Return the raw database count of replies by a user
 *
 * @since 2.1.0 bbPress (r3633)
 *
 * @param int $user_id User ID to get count for
 *
 * @uses bbp_get_user_id()
 * @uses get_posts_by_author_sql()
 * @uses bbp_get_reply_post_type()
 * @uses apply_filters()
 *
 * @return int Raw DB count of replies
 */
function bbp_get_user_reply_count_raw( $user_id = 0 ) {
	$user_id = bbp_get_user_id( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	$count = count_user_posts( $user_id, bbp_get_reply_post_type(), false );

	return (int) apply_filters( 'bbp_get_user_reply_count_raw', $count, $user_id );
}

/**
 * Return the raw database count of closed topics by a user
 *
 * @since 2.6.0 bbPress (r6113)
 *
 * @param int $user_id User ID to get count for
 *
 * @return int Raw DB count of user closed topics
 */
function bbp_get_user_closed_topic_count( $user_id = 0 ) {
	$user_id = bbp_get_user_id( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	$bbp_db = bbp_db();
	$count  = (int) $bbp_db->get_var( $bbp_db->prepare(
		"SELECT COUNT(*)
			FROM {$bbp_db->posts}
			WHERE post_type = %s
				AND post_status = %s
				AND post_author = %d",
		bbp_get_topic_post_type(),
		bbp_get_closed_status_id(),
		$user_id
	) );

	return (int) apply_filters( 'bbp_get_user_closed_topic_count', $count, $user_id );
}

/**
 * Bump the topic count for a user by a certain amount.
 *
 * @since 2.6.0 bbPress (r5309)
 *
 * @param int $user_id
 * @param int $difference
 * @uses bbp_get_user_topic_count() To get the users current topic count
 * @uses bbp_set_user_topic_count() To set the users new topic count
 */
function bbp_bump_user_topic_count( $user_id = 0, $difference = 1 ) {

	// Bail if no bump
	if ( empty( $difference ) ) {
		return false;
	}

	// Validate user ID
	$user_id = bbp_get_user_id( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	// Check meta for count, or query directly if not found
	$count = bbp_get_user_topic_count( $user_id, true );
	if ( empty( $count ) ) {
		$count = bbp_get_user_topic_count_raw( $user_id );
	}

	$difference       = (int) $difference;
	$user_topic_count = (int) ( $count + $difference );

	// Add them up and filter them
	$new_count = (int) apply_filters( 'bbp_bump_user_topic_count', $user_topic_count, $user_id, $difference, $count );

	return bbp_update_user_topic_count( $user_id, $new_count );
}

/**
 * Bump the reply count for a user by a certain amount.
 *
 * @since 2.6.0 bbPress (r5309)
 *
 * @param int $user_id
 * @param int $difference
 * @uses bbp_get_user_reply_count() To get the users current reply count
 * @uses bbp_set_user_reply_count() To set the users new reply count
 */
function bbp_bump_user_reply_count( $user_id = 0, $difference = 1 ) {

	// Bail if no bump
	if ( empty( $difference ) ) {
		return false;
	}

	// Validate user ID
	$user_id = bbp_get_user_id( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	// Check meta for count, or query directly if not found
	$count = bbp_get_user_reply_count( $user_id, true );
	if ( empty( $count ) ) {
		$count = bbp_get_user_reply_count_raw( $user_id );
	}

	$difference       = (int) $difference;
	$user_reply_count = (int) ( $count + $difference );

	// Add them up and filter them
	$new_count = (int) apply_filters( 'bbp_bump_user_reply_count', $user_reply_count, $user_id, $difference, $count );

	return bbp_update_user_reply_count( $user_id, $new_count );
}

/**
 * Helper function used to increase (by one) the count of topics for a user when
 * a topic is published.
 *
 * @since 2.6.0 bbPress (r5309)
 *
 * @access
 * @param $topic_id
 * @param $forum_id
 * @param $anonymous_data
 * @param $topic_author
 */
function bbp_increase_user_topic_count( $topic_id = 0 ) {
	$user_id = bbp_get_topic_author_id( $topic_id );
	return bbp_bump_user_topic_count( $user_id, 1 );
}

/**
 * Helper function used to increase (by one) the count of replies for a user when
 * a reply is published.
 *
 * This is a helper function, hooked to `bbp_new_reply`
 *
 * @since 2.6.0 bbPress (r5309)
 *
 * @param $topic_id
 * @param $forum_id
 * @param $anonymous_data
 * @param $topic_author
 */
function bbp_increase_user_reply_count( $reply_id = 0 ) {
	$user_id = bbp_get_reply_author_id( $reply_id );
	return bbp_bump_user_reply_count( $user_id, 1 );
}

/**
 * Helper function used to decrease (by one) the count of topics for a user when
 * a topic is unpublished.
 *
 * @since 2.6.0 bbPress (r5309)
 *
 * @param $topic_id
 */
function bbp_decrease_user_topic_count( $topic_id = 0 ) {
	$user_id = bbp_get_topic_author_id( $topic_id );
	return bbp_bump_user_topic_count( $user_id, -1 );
}

/**
 * Helper function used to increase (by one) the count of replies for a user when
 * a topic is unpublished.
 *
 * @since 2.6.0 bbPress (r5309)
 *
 * @param $reply_id
 */
function bbp_decrease_user_reply_count( $reply_id = 0 ) {
	$user_id = bbp_get_reply_author_id( $reply_id );
	return bbp_bump_user_reply_count( $user_id, -1 );
}

/** Permissions ***************************************************************/

/**
 * Redirect if unathorized user is attempting to edit another user
 *
 * This is hooked to 'bbp_template_redirect' and controls the conditions under
 * which a user can edit another user (or themselves.) If these conditions are
 * met, we assume a user cannot perform this task, and look for ways they can
 * earn the ability to access this template.
 *
 * @since 2.1.0 bbPress (r3605)
 *
 * @uses bbp_is_single_user_edit()
 * @uses current_user_can()
 * @uses bbp_get_displayed_user_id()
 * @uses bbp_redirect()
 * @uses bbp_get_user_profile_url()
 */
function bbp_check_user_edit() {

	// Bail if not editing a user
	if ( ! bbp_is_single_user_edit() ) {
		return;
	}

	// Default to false
	$redirect = true;
	$user_id  = bbp_get_displayed_user_id();

	// Allow user to edit their own profile
	if ( bbp_is_user_home_edit() ) {
		$redirect = false;

	// Allow if current user can edit the displayed user
	} elseif ( current_user_can( 'edit_user', $user_id ) ) {
		$redirect = false;

	// Allow if user can manage network users, or edit-any is enabled
	} elseif ( current_user_can( 'manage_network_users' ) || apply_filters( 'enable_edit_any_user_configuration', false ) ) {
		$redirect = false;
	}

	// Allow conclusion to be overridden
	$redirect = (bool) apply_filters( 'bbp_check_user_edit', $redirect, $user_id );

	// Bail if not redirecting
	if ( false === $redirect ) {
		return;
	}

	// Filter redirect URL
	$profile_url = bbp_get_user_profile_url( $user_id );
	$redirect_to = apply_filters( 'bbp_check_user_edit_redirect_to', $profile_url, $user_id );

	// Redirect
	bbp_redirect( $redirect_to );
}

/**
 * Check if a user is blocked, or cannot spectate the forums.
 *
 * @since 2.0.0 bbPress (r2996)
 *
 * @uses is_user_logged_in() To check if user is logged in
 * @uses bbp_is_user_keymaster() To check if user is a keymaster
 * @uses current_user_can() To check if the current user can spectate
 * @uses is_bbpress() To check if in a bbPress section of the site
 * @uses bbp_set_404() To set a 404 status
 */
function bbp_forum_enforce_blocked() {

	// Bail if not logged in or keymaster
	if ( ! is_user_logged_in() || bbp_is_user_keymaster() ) {
		return;
	}

	// Set 404 if in bbPress and user cannot spectate
	if ( is_bbpress() && ! current_user_can( 'spectate' ) ) {
		bbp_set_404();
	}
}

/** Sanitization **************************************************************/

/**
 * Sanitize displayed user data, when viewing and editing any user.
 *
 * This somewhat monolithic function handles the escaping and sanitization of
 * user data for a bbPress profile. There are two reasons this all happers here:
 *
 * 1. bbPress took a similar approach to WordPress, and funnels all user profile
 *    data through a central helper. This eventually calls sanitize_user_field()
 *    which applies a few context based filters, which some third party plugins
 *    might be relying on bbPress to play nicely with.
 *
 * 2. Early versions of bbPress 2.x templates did not escape this data meaning
 *    a backwards compatible approach like this one was necessary to protect
 *    existing installations that may have custom template parts.
 *
 * @since 2.6.0 bbPress (r5368)
 *
 * @param string $value
 * @param string $field
 * @param string $context
 * @return string
 */
function bbp_sanitize_displayed_user_field( $value = '', $field = '', $context = 'display' ) {

	// Bail if not editing or displaying (maybe we'll do more here later)
	if ( ! in_array( $context, array( 'edit', 'display' ) ) ) {
		return $value;
	}

	// By default, no filter set (consider making this an array later)
	$filter = false;

	// Big switch statement to decide which user field we're sanitizing and how
	switch ( $field ) {

		// Description is a paragraph
		case 'description' :
			$filter = ( 'edit' === $context ) ? '' : 'wp_kses_data';
			break;

		// Email addresses are sanitized with a specific function
		case 'user_email'  :
			$filter = 'sanitize_email';
			break;

		// Name & login fields
		case 'user_login'   :
		case 'display_name' :
		case 'first_name'   :
		case 'last_name'    :
		case 'nick_name'    :
			$filter = ( 'edit' === $context ) ? 'esc_attr' : 'esc_html';
			break;

		// wp-includes/default-filters.php escapes this for us via esc_url()
		case 'user_url' :
			break;
	}

	// Run any applicable filters on the value
	if ( ! empty( $filter ) ) {
		$value = call_user_func( $filter, $value );
	}

	return $value;
}

/** Converter *****************************************************************/

/**
 * Convert passwords from previous platform encryption to WordPress encryption.
 *
 * @since 2.1.0 bbPress (r3813)
 */
function bbp_user_maybe_convert_pass() {

	// Sanitize username
	$username = ! empty( $_POST['log'] )
		? sanitize_user( $_POST['log'] )
		: '';

	// Bail if no username
	if ( empty( $username ) ) {
		return;
	}

	// Bail if no user password to convert
	$bbp_db = bbp_db();
	$query  = $bbp_db->prepare( "SELECT * FROM {$bbp_db->users} INNER JOIN {$bbp_db->usermeta} ON user_id = ID WHERE meta_key = %s AND user_login = %s LIMIT 1", '_bbp_class', $username );
	$row    = $bbp_db->get_row( $query );
	if ( empty( $row ) || is_wp_error( $row ) ) {
		return;
	}

	// Setup admin (to include converter)
	require_once bbpress()->includes_dir . 'admin/admin.php';

	// Create the admin object
	bbp_admin();

	// Convert password
	require_once bbpress()->admin->admin_dir . 'converter.php';
	require_once bbpress()->admin->admin_dir . 'converters/' . sanitize_key( $row->meta_value ) . '.php';

	// Create the converter
	$converter = bbp_new_converter( $row->meta_value );

	// Try to call the conversion method
	if ( is_a( $converter, 'BBP_Converter_Base' ) && method_exists( $converter, 'callback_pass' ) ) {
		$converter->callback_pass( $username, $_POST['pwd'] );
	}
}
