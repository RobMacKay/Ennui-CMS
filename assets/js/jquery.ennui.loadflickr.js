/**
 * LoadFlickr - A jQuery Plugin to load images from a given Flickr user into an
 *      unordered list
 *
 * Demo and Documentation at http://ennuidesign.com/projects/loadflickr
 *
 * @version 1.0.0
 * @author Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright 2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
(function($){

    $.fn.loadFlickr = function(options)
    {
        var o = $.extend($.fn.loadFlickr.defaults, options);

        return this.each(function(){
            var thisCache = this;

            // Request the JSON and process it
            $.ajax({
                type:'GET',
                url:"http://api.flickr.com/services/feeds/photos_public.gne",
                data:"id="+o.flickrID+"&lang=en-us&format=json&jsoncallback=?",
                success:function(feed) {
                    // Create an empty array to store images
                    var thumbs = [];

                    // Shuffle the array if the option is enabled
                    if( o.randomize===true )
                    {
                        feed.items.shuffle();
                    }

                    // Loop through the items
                    for(var i=0, l=feed.items.length; i<l; ++i)
                    {
                        // Manipulate the image to get thumb and medium sizes
                        var title = feed.items[i].title,
                            hide = i>=o.displayNum ? ' style="display: none;"' : '',
                            markup = feed.items[i].media.m.replace(
                                    /^(.*?)_m\.jpg$/,
                                    '<li' + hide
                                        + '><a href="$1.jpg">'
                                        + '<img src="$1_s.jpg" alt="'
                                        + title + '" /></a></li>'
                                );

                        // Add the new element to the array
                        thumbs.push(markup);
                    }

                    // Display the thumbnails on the page
                    $(thisCache).html(thumbs.join(''));

                    // Fire the callback
                    o.callback(thisCache);
                },
                dataType:'jsonp'
            });
        });
    };

    // Add a shuffle function to the Array prototype
    Array.prototype.shuffle = function() {
            var len = this.length,
                i = len;
             while( i-- )
             {
                var p = parseInt(Math.random()*len),
                    t = this[i];

                this[i] = this[p];
                this[p] = t;
            }
        };

    $.fn.loadFlickr.defaults = {
            "flickrID" : "29080075@N02",
            "displayNum" : 9,
            "randomize" : false,
            "callback" : function(el){}
        };

})(jQuery)
