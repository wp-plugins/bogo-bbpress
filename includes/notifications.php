<?php

function bogobbp_get_default_message( $type ) {

	switch ( $type ) {
	
		case 'topic_subject':
			return '[My BBP-Forums] New topic: "{{title}}"';
			
		case 'topic_message':
			return '{{author}} wrote:

{{content}}


Join the discussion: {{link}}


-----------

You are receiving this email because you subscribed to a forum. Log in to manage your subscriptions.';

		case 'reply_subject':
			return '[My BBP-Forums] New reply for topic "{{title}}"';
			
		case 'reply_message':
			return '{{author}} replied:

{{content}}


Join the discussion: {{link}}


-----------

You are receiving this email because you subscribed to this forum topic. Log in to manage your subscriptions.';

		default: return 'this is a bug';
	}
}

// replace original mail routine with adaptation of bbp_notify_subscribers (see bbpress/includes/common/functions.php)
remove_action( 'bbp_new_reply', 'bbp_notify_subscribers', 11 );
add_action( 'bbp_new_reply', 'bogobbp_notify_topic_subscribers', 11, 5 );
function bogobbp_notify_topic_subscribers( $reply_id = 0, $topic_id = 0, $forum_id = 0, $anonymous_data = false, $reply_author = 0 ) {

	// Bail if subscriptions are turned off
	if ( !bbp_is_subscriptions_active() ) {
		return false;
	}

	/** Validation ************************************************************/

	$reply_id = bbp_get_reply_id( $reply_id );
	$topic_id = bbp_get_topic_id( $topic_id );
	$forum_id = bbp_get_forum_id( $forum_id );

	/** Topic *****************************************************************/

	// Bail if topic is not published
	if ( !bbp_is_topic_published( $topic_id ) ) {
		return false;
	}

	/** Reply *****************************************************************/

	// Bail if reply is not published
	if ( !bbp_is_reply_published( $reply_id ) ) {
		return false;
	}

	// Poster name
	$reply_author_name = bbp_get_reply_author_display_name( $reply_id );

	/** Mail ******************************************************************/

	// Remove filters from reply content and topic title to prevent content
	// from being encoded with HTML entities, wrapped in paragraph tags, etc...
	remove_all_filters( 'bbp_get_reply_content' );
	remove_all_filters( 'bbp_get_topic_title'   );

	// Strip tags from text and setup mail data
	$topic_title   = strip_tags( bbp_get_topic_title( $topic_id ) );
	$reply_content = strip_tags( bbp_get_reply_content( $reply_id ) );
	$reply_url     = bbp_get_reply_url( $reply_id );
	$blog_name     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	/** Users *****************************************************************/

	// Get topic subscribers and bail if empty
	$user_ids = bbp_get_topic_subscribers( $topic_id, true );
	if ( empty( $user_ids ) ) {
		return false;
	}

	/** Translation ***********************************************************/
	
	$users = bogobbp_get_users_locales( $user_ids );
	$translated_messages = bogobbp_translate_notification_messages( $users, $reply_author_name, $reply_content, $topic_title, $reply_url, 'reply' );
	
	/** Send them *************************************************************/
	
	// Custom headers
	$headers = apply_filters( 'bbp_subscription_mail_headers', array() );

	do_action( 'bbp_pre_notify_subscribers', $topic_id, $forum_id, $user_ids );

	// send messages
	$option	= get_option( 'bogobbp_notifications_data' );
	$default_locale = bogo_get_default_locale();
	foreach ( (array) $users as $user ) {
	
		// Don't send notifications to the person who made the post
		if ( !empty( $reply_author ) && (int) $user->id === (int) $reply_author ) {
			continue;
		}

		$locale = isset( $option[$user->locale] ) ? $user->locale : $default_locale;
		wp_mail( get_userdata( $user->id )->user_email,
				$translated_messages[$locale]['reply_subject'],
				$translated_messages[$locale]['reply_message'] );
	}
	
	do_action( 'bbp_post_notify_subscribers', $topic_id, $forum_id, $user_ids );

	return true;
}

// replace original mail routine with adaptation of bbp_notify_forum_subscribers (see bbpress/includes/common/functions.php)
remove_action( 'bbp_new_topic', 'bbp_notify_forum_subscribers', 11 );
add_action( 'bbp_new_topic', 'bogobbp_notify_forum_subscribers', 11, 4 );
function bogobbp_notify_forum_subscribers( $topic_id = 0, $forum_id = 0, $anonymous_data = false, $topic_author = 0 ) {

	// Bail if subscriptions are turned off
	if ( !bbp_is_subscriptions_active() ) {
		return false;
	}

	/** Validation ************************************************************/

	$topic_id = bbp_get_topic_id( $topic_id );
	$forum_id = bbp_get_forum_id( $forum_id );

	/** Topic *****************************************************************/

	// Bail if topic is not published
	if ( ! bbp_is_topic_published( $topic_id ) ) {
		return false;
	}

	// Poster name
	$topic_author_name = bbp_get_topic_author_display_name( $topic_id );

	/** Mail ******************************************************************/

	// Remove filters from reply content and topic title to prevent content
	// from being encoded with HTML entities, wrapped in paragraph tags, etc...
	remove_all_filters( 'bbp_get_topic_content' );
	remove_all_filters( 'bbp_get_topic_title'   );

	// Strip tags from text and setup mail data
	$topic_title   = strip_tags( bbp_get_topic_title( $topic_id ) );
	$topic_content = strip_tags( bbp_get_topic_content( $topic_id ) );
	$topic_url     = get_permalink( $topic_id );
	$blog_name     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	/** User ******************************************************************/

	// Get topic subscribers and bail if empty
	$user_ids = bbp_get_forum_subscribers( $forum_id, true );
	if ( empty( $user_ids ) ) {
		return false;
	}
	
	/** Translation ***********************************************************/
	
	$users = bogobbp_get_users_locales( $user_ids );
	$translated_messages = bogobbp_translate_notification_messages( $users, $topic_author_name, $topic_content, $topic_title, $topic_url, 'topic' );

	/** Send them *************************************************************/

	// Custom headers
	$headers = apply_filters( 'bbp_subscription_mail_headers', array() );

	do_action( 'bbp_pre_notify_forum_subscribers', $topic_id, $forum_id, $user_ids );

	// send messages
	$option	= get_option( 'bogobbp_notifications_data' );
	$default_locale = bogo_get_default_locale();
	foreach ( (array) $users as $user ) {

		// Don't send notifications to the person who made the post
		if ( !empty( $topic_author ) && (int) $user->id === (int) $topic_author ) {
			continue;
		}

		$locale = isset( $option[$user->locale] ) ? $user->locale : $default_locale;
		wp_mail( get_userdata( $user->id )->user_email,
				$translated_messages[$locale]['topic_subject'],
				$translated_messages[$locale]['topic_message'] );
	}
	
	do_action( 'bbp_post_notify_forum_subscribers', $topic_id, $forum_id, $user_ids );

	return true;
}

function bogobbp_get_users_locales( $user_ids ) {
	global $wpdb;
	$in_user_ids = implode(', ', array_map('esc_sql', $user_ids) );
	$users = $wpdb->get_results( "SELECT user_id as id, meta_value as locale FROM {$wpdb->usermeta} WHERE user_id IN ($in_user_ids) AND meta_key='locale';" );
	return $users;
}

function bogobbp_translate_notification_messages( $users, $author_name, $content, $topic_title, $url, $type ) {
	
	// prepare localized messages
	$default_locale = bogo_get_default_locale();
	$tokens = array( '{{author}}', '{{content}}', '{{title}}' );
	$replacements = array( $author_name, $content, $topic_title );
	$option	= get_option( 'bogobbp_notifications_data' );
	$fallback = str_replace( $tokens, $replacements, bogobbp_get_default_message( $type . '_message' ) );
	$translated_messages = array();
	foreach ( $option as $locale => $translations ) {
		foreach ( $translations as $type => $translation ) {
			if ( $translation == '' ) {
				$translated_messages[$locale][$type] = $fallback;
			} else {
				$translated_messages[$locale][$type] = str_replace( $tokens, $replacements, $translation );
			}
			$translated_messages[$locale][$type] = str_replace( '{{link}}',
					bogoxlib_localize_url_using_locale( $url, $locale ),
					$translated_messages[$locale][$type] );
		}
	}
	
	return $translated_messages;
}

?>
