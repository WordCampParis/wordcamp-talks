/* global wctWelcome */
( function( $ ) {

	$( '.wrap h1' ).first().after( $( '#welcome-speaker-panel' ) );

	if ( typeof wctWelcome === 'undefined' ) {
		return;
	}

	$( '#welcome-speaker-panel' ).on( 'click', '.welcome-panel-close', function( event ) {
		event.preventDefault();

		$.post( wctWelcome.ajaxurl, {
			action: 'wct_update_speaker_panel',
			welcomespeakernonce: $( '#welcomespeakernonce' ).val()
		} );

		$( event.delegateTarget ).remove();
	} );

} )( jQuery );
