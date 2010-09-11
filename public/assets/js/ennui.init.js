/*
 * All JavaScript to be fired when the DOM is ready
 */
$(document).ready(function() {

    // Initialize the ThumbBox plugin for gallery viewing
    $('.thumbbox').thumbBox({
            "darkColor" : "#111",
            "lightColor" : "#DDD"
        });

    // Technically valid workaround for target="_blank"
    $('a[rel="external"]').attr('target', '_blank');

});
