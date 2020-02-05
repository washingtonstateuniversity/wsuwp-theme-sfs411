const stalenessMetaBoxHandling = ( function() {
	const resetButton              = document.getElementById( 'sfs411-staleness-settings_reset' );
	const durationOptionsContainer = document.getElementById( 'sfs411-staleness-settings_duration-options' );
	const durationOptions          = Array.prototype.slice.apply( durationOptionsContainer.querySelectorAll( 'input' ) );

	/**
	 * Toggles the display and `disabled` attributes of the duration options.
	 *
	 * @param {object} event The click event.
	 */
	function resetStaleness( event ) {
		if ( event.target.classList.contains( 'cancel' ) ) {
			durationOptionsContainer.classList.add( 'hidden' );
			resetButton.classList.remove( 'cancel' );
			resetButton.innerText = 'Reset';
			durationOptions.forEach( ( option ) => {
				option.setAttribute( 'disabled', '' );
			} );
		} else {
			durationOptionsContainer.classList.remove( 'hidden' );
			resetButton.classList.add( 'cancel' );
			resetButton.innerText = 'Cancel';
			durationOptions.forEach( ( option ) => {
				option.removeAttribute( 'disabled' );
			} );
		}
	}

	resetButton.addEventListener( 'click', resetStaleness );
} () );