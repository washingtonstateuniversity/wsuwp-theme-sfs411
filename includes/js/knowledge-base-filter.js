{
	const kbItems = document.querySelectorAll( '.kb-item' );

	/**
	 * Hide knowledge base items that do not contain the filter text
	 * whenever there are 3 or more characters entered in the filter field.
	 *
	 * @param {*} event The keyup event.
	 */
	function filterArchive( event ) {
		if ( 3 > event.target.value.length ) {
			kbItems.forEach( ( el ) => el.classList.remove( 'hidden-kb-item' ) );
		} else {
			const filterText = event.target.value.toLowerCase();

			kbItems.forEach( ( el ) => {
				if ( -1 === el.dataset.title.indexOf( filterText ) ) {
					el.classList.add( 'hidden-kb-item' );
				} else {
					el.classList.remove( 'hidden-kb-item' );
				}
			} );
		}
	}

	document.querySelector( '#kb-filter-archive' ).addEventListener( 'keyup', filterArchive );
}
