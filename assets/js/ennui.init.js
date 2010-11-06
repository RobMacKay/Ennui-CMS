/**
 * JavaScript to be executed for all pages and all visitors
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
// All JavaScript to be fired when the DOM is ready
var TB_lightColor = '#FFF',
    TB_darkColor = '#240800';

jQuery(function($){

    // Technically valid workaround for target="_blank"
    $('[rel="external"]').attr('target', '_blank');

    // Load a user's Flickr stream
    $('#sidebar-flickr')
        .loadFlickr({
                "displayNum" : 8,
                "randomize" : true,
                "callback" : function(el){
                    $(el).thumbBox({
                            "darkColor" : "#000",
                            "lightColor" : "#FFF"
                        });
                }
            });

    /***************************************************************************
    * ECMS JS required for normal functionality — alter at your own risk
    ***************************************************************************/

    // AJAXify comment flagging
    $('.flag-comment')
        .bind('click', function(e){
                e.preventDefault();

                $.ajax({
                        "type" : "GET",
                        "url" : $(this).attr('href'),
                        "success" : function(response){
                                $.fn.thumbBox.buildModal(response, {
                                    "fullWidth" : 350,
                                    "darkColor" : "#240800",
                                    "lightColor" : "#FFF"
                                });
                                $(".thumbbox-main-modal *")
                                    .bind("click", function(e){
                                            e.stopPropagation();
                                        });
                            }
                    });
            });

});
