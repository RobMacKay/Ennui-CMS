/*
 * All JavaScript to be fired when the DOM is ready
 */
$(document).ready(function() {

	/*
	 * Technically valid workaround for target="_blank"
	 */
	$('a[rel="external"]').attr('target', '_blank');

});
