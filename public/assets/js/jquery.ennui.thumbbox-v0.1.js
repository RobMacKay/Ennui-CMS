(function($){
	$.fn.thumbBox = function(options)
	{
		var defaults = {
				"previewWidth":"100",
				"previewHeight":"60",
				"fullWidth":"576",
				"fullHeight":"476",
				"padding":"12",
				"border":"2",
				"showCaption":true
			},
	    	o = $.extend(defaults, options),
			$inuse = false,
			$show_cap = o.showCaption;

		return this.each(function() {
			var $this = $(this),
				$firstimg = $('<img>')
					.attr({
						"src" : $(this).children('li:first').children('img').attr('src').replace(/(portfolio\d{1,4})/i,"$1/preview"),
						"alt" : $(this).children('li:first').children('img').attr('alt')
					})
					.css({
						"border" : "0",
						"width" : "100%"
					}),
				$gal = $('<a>')
					.attr("href", "#")
					.bind("click", function(){
						displayThumbbox($this);
						$(".thumbbox-main-thumbs-wrapper>ul")
							.children("li:first")
								.addClass("thumbbox-current-img")
								.children('img')
									.fadeTo(300, 1)
									.end()
								.end()
							.children("li")
								.bind("click", function(){
									selectThumb($(this));
								});
						thumbImageSize();
						changeMainImage($this.children('li:first'));
						return false;
					})
					.html($firstimg)
					.append("View "+$this.children('li').length
							+" Screenshots and Project Desciption");
			$(this).prepend($gal);
		});

		function displayThumbbox($this)
		{
			var $main = $('<div>')
					.addClass('thumbbox-main-img')
					.css({
						"height":parseInt(o.fullHeight)-parseInt(o.previewHeight)
							+parseInt(o.padding)+"px"
					}),
				$ul = $('<ul>')
					.css({
						"left":"0px",
						"width":$this.children('li').length*(parseInt(o.previewWidth)
								+parseInt(o.border)/2)+parseInt(o.border)/2+"px"
					})
					.html($this.children('li').clone()),
				$max_images = Math.min(
						$this.children('li').length,
						Math.floor(parseInt(o.fullWidth)/(parseInt(o.previewWidth)
							+parseInt(o.border))-1)
					),
				$wrapper = $('<span>')
					.addClass('thumbbox-main-thumbs-wrapper')
					.css({
						"height":parseInt(o.previewHeight)+parseInt(o.border)*2+"px",
						"width":parseInt(o.border)*$max_images/2
							+parseInt(o.border)/2+parseInt(o.previewWidth)*$max_images+"px"
					})
					.html($ul),
				$thumbs = $("<div>")
					.addClass("thumbbox-main-thumbs")
					.css({
						"height":parseInt(o.previewHeight)+parseInt(o.padding)+"px"
					})
					.html($wrapper),
				$close = $("<a>")
					.addClass("thumbbox-close-btn")
					.attr("href","#")
					.bind("click", function(){
							$(".thumbbox-overlay, .thumbbox-main")
								.fadeOut(300, function(){ $(this).remove(); });
							return false;
						})
					.html("&#215;"),
				$prev = $("<a>")
					.addClass("thumbbox-prev-btn")
					.attr("href", "#")
					.bind("click", function(){
							return thumbSlide(1);
						})
					.html("&laquo; prev"),
				$next = $("<a>")
					.addClass("thumbbox-next-btn")
					.attr("href", "#")
					.bind("click", function(){
							return thumbSlide(-1);
						})
					.html("next &raquo;"),
				$thumbs = $("<div>")
					.addClass("thumbbox-main-thumbs")
					.css({
						"height":parseInt(o.previewHeight)+parseInt(o.padding)+"px"
					})
					.html($wrapper)
					.append($prev)
					.append($next),
				$overlay = $('<div>')
					.addClass("thumbbox-overlay")
					.css({"opacity":"0"})
					.bind("click", function(){
							$(".thumbbox-overlay, .thumbbox-main")
								.fadeOut(300, function(){ $(this).remove(); });
						})
					.appendTo('body')
					.fadeTo(300, .5),
				$width = Math.round(parseInt(o.fullWidth)+parseInt(o.padding)*2),
				$height = Math.round(parseInt(o.fullHeight)+parseInt(o.padding)*2),
				$margin = Math.round((parseInt(o.fullWidth)+parseInt(o.padding)*2)/2*-1),
				$thumbbox = $('<div>')
					.addClass("thumbbox-main")
					.css({
						"top":$(window).scrollTop()+25+"px",
						"width":$width+"px",
						"height":$height+"px",
						"margin-left":$margin+"px"
					})
					.html($main)
					.append($thumbs)
					.append($close)
					.appendTo("body");
		}

		function thumbImageSize()
		{
			$(".thumbbox-main-thumbs-wrapper>ul>li")
				.css({
					"width":parseInt(o.previewWidth)+"px",
					"height":parseInt(o.previewHeight)+"px"
				})
				.children('img')
					.each(function(){
							var w = $(this).width(),
								h = $(this).height(),
								max_w = parseInt(o.previewWidth),
								max_h = parseInt(o.previewHeight),
								r = Math.max(max_w/w, max_h/h),
								d = {
									"width":Math.round(w*r),
									"height":Math.round(h*r),
									"top":Math.round((max_h-h*r)/2),
									"left":Math.round((max_w-w*r)/2)
								};
							$(this).css({
								"width":d.width+"px",
								"height":d.height+"px",
								"margin-top":d.top+"px",
								"margin-left":d.left+"px"
							});
						});
		}

		function mainImageWidth(img)
		{
			var w = img.width(),
				h = img.height(),
				max_w = parseInt(o.fullWidth)-parseInt(o.padding)-parseInt(o.border)
				max_h = parseInt(o.fullHeight)-parseInt(o.previewHeight)-parseInt(o.padding)-parseInt(o.border),
				r = Math.min(max_w/w, max_h/h),
				d = {
					"width":Math.round(w*r-parseInt(o.border)),
					"height":Math.round(h*r),
					"margin":Math.round((max_h-h*r)/2+parseInt(o.padding))
				};
			img.css({
				"width":d.width+"px",
				"height":d.height+"px",
				"margin-top":d.margin+"px",
				"margin-left":"auto",
				"margin-right":"auto"
			});
		}

		function selectThumb($thumb)
		{
			changeMainImage($thumb);
			$thumb
				.addClass("thumbbox-current-img")
				.children('img')
					.fadeTo(300, 1)
					.end()
				.parent()
					.children('li')
						.not($thumb)
						.attr("class", "")
						.children('img')
							.fadeTo(300, .75);
		}

		function changeMainImage($li)
		{
			if($li.attr("class")!="thumbbox-current-img")
			{
				$('.thumbbox-main-img>img').fadeTo(300, 0);
				var $img = $li
						.children('img')
							.clone()
								.css({ "opacity":0 })
								.data("title", $li.children('img').attr("title"))
								.attr("title", "");
				$('.thumbbox-main-img-caption-toggle').remove();
				if($img.data("title")!==undefined)
				{
					if($img.data("title").match("<p>")==undefined)
					{
						var $title = "<p>"+$img.data("title")+"</p>";
					}
					else
					{
						var $title = $img.data("title");
					}
					var $caption = $("<div>")
							.addClass("thumbbox-main-img-caption")
							.html($title),
						$toggle = $('<a>')
							.addClass("thumbbox-main-img-caption-toggle")
							.attr("href", "#")
							.bind("click", function(){
								if($show_cap===false)
								{
									$(".thumbbox-main-img-caption")
										.fadeTo(300, 1);
									$(this)
										.text("Hide Caption")
										.css({ "color":"#CCC" });
									$show_cap = true;
								}
								else
								{
									$(".thumbbox-main-img-caption")
										.fadeTo(300, 0);
									$(this)
										.text("Show Caption")
										.css({ "color":"#2C2C2C" });
									$show_cap = false;
								}
								return false;
							})
							.appendTo($('.thumbbox-main-thumbs'));
					if($show_cap===false)
					{
						$caption.css({ "opacity":0 });
						$toggle.text("Show Caption").css({ "color":"#2C2C2C" });
					}
					else
					{
						$caption.css({ "opacity":1 });
						$toggle.text("Hide Caption").css({ "color":"#CCC" });
					}
				}
				else
				{
					var $caption = null;
					var $toggle = null;
				}
				$(".thumbbox-main-img")
					.html($img)
					.append($caption);
				mainImageWidth($img);
				$('.thumbbox-main-img>img').fadeTo(300, 1);
			}
		}

		function thumbSlide(d)
		{
			if($inuse===false)
			{
				$inuse = true;
				var $slider_width = $(".thumbbox-main-thumbs-wrapper").width(),
					$total_width = $(".thumbbox-main-thumbs-wrapper>ul").width(),
					$move_distance = (parseInt(o.previewWidth)+parseInt(o.border)/2)*d,
					$cur_left = parseInt($(".thumbbox-main-thumbs-wrapper>ul").css("left")),
					$new_left = $cur_left+$move_distance,
					$min_left = $slider_width-$total_width;
				$thumb = (d==1) ? $(".thumbbox-current-img").prev('li') : $(".thumbbox-current-img").next('li');
				if($thumb.children('img').attr('src')!==undefined)
				{
					selectThumb($thumb);
				}
				if($new_left<=0 && $new_left>=$min_left)
				{
					$(".thumbbox-main-thumbs-wrapper>ul")
						.animate({
									"left":$new_left+"px"
								},
							300,
							"swing",
							function(){
								$inuse=false;
							});
				}
				else
				{
					$inuse = false;
				}
			}
			return false;
		}
 	}
})(jQuery)