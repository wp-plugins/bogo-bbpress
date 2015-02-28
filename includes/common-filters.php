<?php

if ( !has_filter( 'bogo_language_switcher', 'bogocomm_fix_language_switcher_links' ) )
add_filter( 'bogo_language_switcher', 'bogocomm_fix_language_switcher_links' );

if ( !function_exists( 'bogocomm_fix_language_switcher_links' ) ) {
	function bogocomm_fix_language_switcher_links( $output ) {

		if ( !is_bbpress() ) {
			return $output;
		}
	
		$dom = new DOMDocument;
		$dom->loadHTML( $output );
		foreach( $dom->getElementsByTagName( 'li' ) as $li) { // $li is of class DOMNode
	
			list( $item_locale_css, $item_lang ) = explode( ' ', $li->attributes->getNamedItem( 'class' )->value);
			$item_locale = str_replace( '-', '_', $item_locale_css);
		
			// skip item belonging to current locale ( or default locale if no current lang is found in url )
			$current_lang = get_query_var( 'lang' );
			if ( !$current_lang ) {
				$current_lang = bogo_lang_slug( bogo_get_default_locale() );
			}
			if ( $current_lang == $item_lang ) {
				continue;
			}

			$url = bogocomm_localize_current_url( $item_locale );
		
			$a = $dom->createDocumentFragment();
			$a->appendXML( '<a href="' . esc_url( $url ) . '" hreflang="' . $item_locale_css . '" rel="alternate">' . $li->nodeValue . '</a>');
			$li->nodeValue = '';
			$li->appendChild( $a );
		}
	
		return $dom->saveHTML();
	}
}

?>
