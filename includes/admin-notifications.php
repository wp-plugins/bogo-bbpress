<?php

add_action('admin_menu', function() {
	
	if( !is_plugin_active( 'bbpress/bbpress.php' ) ||
			!is_plugin_active( 'bogo/bogo.php' ) ) {
		return;
	}
	
	$page_hook = add_options_page( _x( 'Bogo BBPress notifications translation', 'admin page title', 'bogobbp' ), 'BogoBBP', 'manage_options', 'bogobbp-notifications-translation', 'bogobbp_admin_display_notifications_translation_page' );
	
	add_action( 'admin_print_styles-' . $page_hook, function() {
		wp_enqueue_style( 'bogobbp-admin-notifications', plugins_url( 'admin-notifications.css', __FILE__ ) );
	});
});

function bogobbp_admin_display_notifications_translation_page() {
	
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	if( !is_plugin_active( 'bbpress/bbpress.php' ) || !is_plugin_active( 'bogo/bogo.php' ) ) {
		wp_die( __( 'Both Bogo and BBPress must be active to translate your notifications.' ) );
	}
	
	echo '<div id="bogobbp-notifications">';
	echo '<h1>Bogo BBPress notifications translation</h1>';
	echo '<p class="tokens_explanation">Available tokens: {{author}}, {{content}}, {{link}} and {{title}}</p>';
	
	$default_locale = bogo_get_default_locale();
	$languages = array( $default_locale => bogo_get_language( $default_locale ) ) + bogo_available_languages();

	$option = get_option( 'bogobbp_notifications_data' );
	
	if ( isset( $_GET['save'] ) ) {
	
		$new_option = array();
		foreach ( $_POST as $id => $translation ) {
			list( $locale, $type ) = explode( '%', $id );
			$translation = stripslashes_deep( implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $translation ) ) ) );
			if ( $translation ) {
				$new_option[$locale][$type] = $translation;
			}
		}

		$success = true;
		if ( $new_option != $option ) {
			if ( $option === false ) {
				$success = add_option( 'bogobbp_notifications_data', $new_option, '', 'no' );
			} else {
				$success = update_option( 'bogobbp_notifications_data', $new_option );
			}
		}

		$option = $new_option;

		if ( $success ) {
			echo '<p class="success">' . __( 'Your translations have been saved.', 'bogobbp' ) . '</span>';
		} else {
			echo '<p class="failure">' . __( 'An error occurred, please try again.', 'bogobbp' ) . '</span>';
		}
	}
	
	echo '<form method="post" action="options-general.php?page=bogobbp-notifications-translation&save=1">';
		echo '<table><thead>';
			foreach ( $languages as $locale => $language ) {
				echo '<th>' . $language . '</th>';
			}
		echo '</thead><tbody>';
			echo '<tr class="empty"><td>&nbsp;</td></tr>';
			bogo_buddypress_admin_display_notifications_item( $languages, $option, 'topic_subject' );
			bogo_buddypress_admin_display_notifications_item( $languages, $option, 'topic_message' );
			bogo_buddypress_admin_display_notifications_item( $languages, $option, 'reply_subject' );
			bogo_buddypress_admin_display_notifications_item( $languages, $option, 'reply_message' );
		echo '</tbody></table>';
		
		echo '<p class="submit"><input type="submit" class="button-primary" value="' . _x( 'Save Changes', 'admin save', 'bogobbp' ) . '" /></p>';
		
	echo '</form>';
	echo '</div>';
}

function bogo_buddypress_admin_display_notifications_item( $languages, $option, $type ) {
	echo '<tr>';
	foreach ( $languages as $locale => $language ) {
		echo '<td>';
			$val = isset( $option[$locale][$type] ) ? $option[$locale][$type] : bogobbp_get_default_message( $type );
			if ( $type == 'reply_subject' || $type == 'topic_subject' ) {
				echo '<input name="' . $locale . '%' . $type . '" type="text" value="' . esc_html( $val ) .'" />';
			} else {
				echo '<textarea name="' . $locale . '%' . $type . '">' . esc_textarea( $val ) . '</textarea>';
			}
		echo '</td>';
	}
	echo '</tr>';
}

?>
