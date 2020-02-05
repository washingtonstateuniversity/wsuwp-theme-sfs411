const stalenessMetaBoxHandling = ( function() {
	const durationOptionsContainer = document.getElementById( 'sfs411-staleness-management_options' );
	const durationOptions          = Array.prototype.slice.apply( durationOptionsContainer.querySelectorAll( 'input' ) );
	const resetNote                = document.getElementById( 'sfs411-staleness-management_options-note' );
	const resetButton              = document.getElementById( 'sfs411-staleness-management_reset' );

	/**
	 * Toggles the display and `disabled` attributes of the duration options.
	 *
	 * @param {object} event The click event.
	 */
	function resetStaleness( event ) {
		if ( event.target.classList.contains( 'cancel' ) ) {
			resetButton.classList.remove( 'cancel' );
			resetButton.innerText = 'Reset';

			durationOptionsContainer.classList.add( 'hidden' );
			durationOptions.forEach( ( option ) => {
				option.setAttribute( 'disabled', '' );
			} );

			resetNote.setAttribute( 'disabled', '' );
		} else {
			resetButton.classList.add( 'cancel' );
			resetButton.innerText = 'Cancel';

			durationOptionsContainer.classList.remove( 'hidden' );
			durationOptions.forEach( ( option ) => {
				option.removeAttribute( 'disabled' );
			} );

			resetNote.removeAttribute( 'disabled' );
		}
	}

	resetButton.addEventListener( 'click', resetStaleness );
} () );