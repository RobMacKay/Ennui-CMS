<!--//

// This variable is the serialized divs in the admin gallery
$order = null;

function showedit(page,option,id) {
	var url = "/inc/update.inc.php";
	var params = "page=" + page + "&action=" + option + "&id=" + id;

	$.ajax({
		type: "POST",
		url: url,
		data: params,
		success: function(response)
			{
				$('#'+page).html(response).height('auto');
				$('input.nl_preview')
					.bind("click", function(){return newsletterPreview("newsletter");});
				setTimeout("textEdit();", 500);
			}
	});
}

function reorderEntry(page, pos, direction, id) {
	var url = "/inc/update.inc.php";
	var params = "page="
		+ page
		+ "&action=reorderEntry&pos="
		+ pos
		+ "&id="
		+ id
		+ "&direction="
		+ direction;

	$.ajax({
		type: "POST",
		url: url,
		data: params,
		success: function()
			{
				document.location = "/"+page;
			}
	});
}

function galleryEdit(page, id, dir) {
	var url = "/inc/update.inc.php",
		folder = dir+page+id,
		params = "page=" + page + "&id=" + id + "&action=galleryEdit";

	$.ajax({
		type: "POST",
		url: url,
		data: params,
		success: function(response)
			{
				$('#'+page).html(response);
	            setTimeout("galleryLoading('"+dir+"', '"+page+"', '"+id+"')", 500);
			}
	});
}

function galleryLoading(dir, page, id)
{
	// Create a "Sort" button
	var $sort_btn = '<a href="#" id="sortbtn">Sort</a>';

	galleryUpload(dir, page, id);
	$('#admin_gal').append($sort_btn).sortable({
    	update: function(){
			$order = $(this).sortable('serialize');
		}
	});

	$('#sortbtn').bind('click', function(){
		$.ajax({
			type: "POST",
			url: "/inc/update.inc.php",
			data: "page="+page+"&id="+id+"&action=galleryOrder&"+$order,
			success: function(msg){
				$("#"+page).html(msg);
			}
		});
		return false;
	});
}

function addPhotoCaption(page, album_id, image_id, image_cap) {
	var url = "/inc/update.inc.php",
		params = "page=" + page
			+ "&album_id=" + album_id
			+ "&image_id=" + image_id
			+ "&image_cap=" + image_cap
			+ "&action=galleryAddCaption";

	$.ajax({
		type: "POST",
		url: url,
		data: params,
		success: function(response)
			{
				$('#'+page).html(response);
		    	setTimeout("galleryUpload('"+page+album_id+"')",500);
			}
	});
}

function deletePhoto(page, id, img) {
	var url = "/inc/update.inc.php",
		params = "page="+page+"&id="+id+"&image="+img+"&action=galleryDeletePhoto";

	$.ajax({
		type: "POST",
		url: url,
		data: params,
		success: function(response)
			{
				$('#'+page).html(response);
		    	setTimeout("galleryUpload('"+page+id+"')",500);
			}
	});
}

function textEdit() {
	$('textarea#body').tinymce({
		script_url : '/assets/js/tiny_mce/tiny_mce.js',
		theme : "advanced",
		plugins : "safari,iespell,inlinepopups,spellchecker,preview,paste,advimage",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_buttons1 : "pasteword,|,bold,italic,underline,blockquote,|,"
			+ "justifyleft,justifycenter,justifyright,|,"
			+ "bullist,numlist,outdent,indent,|,link,unlink,image,code,preview",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		relative_urls : false
	});
}

function galleryUpload(dir, page, id)
{
	$("#fileUpload").fileUpload({
		'uploader': '/assets/swf/uploader.swf',
		'cancelImg': '/images/cancel.png',
		'script': '/inc/uploadify.inc.php',
		'folder': dir+page+id,
		'fileDesc': 'Image Files',
		'buttonText': 'Select Photos',
		'fileExt': '*.jpg;*.jpeg;*.gif;*.png',
		'multi': true,
		'auto': true,
		'wmode': 'transparent',
		'onAllComplete': function() { galleryEdit(page, id, dir); }
	});
}

function newsletterPreview(page)
{
	var $overlay = $('<div>')
			.addClass("nl_preview-overlay")
			.bind("click", function(){
				$(".nl_preview-overlay, .nl_preview-main")
					.fadeOut(300, function(){ $(this).remove(); });
			})
			.css({"opacity":"0"}),
		$close = $("<a>")
			.addClass("nl_preview-close-btn")
			.attr("href","#")
			.bind("click", function(){
					$(".nl_preview-overlay, .nl_preview-main")
						.fadeOut(300, function(){ $(this).remove(); });
					return false;
				})
			.html("&#215;"),
		$preview = $('<div>')
			.addClass("nl_preview-main")
			.css({
				"opacity":"0",
				"top":$(window).scrollTop()+25+"px"
			})
			.html($close),
		$subject = $("input[name=title]").val(),
		$body = $("textarea#body").html(),
		params = "page=" + page + "&action=nl_preview&subject=" 
			+ $subject + "&body=" + $body;

	$.ajax({
		type: "POST",
		url: "/inc/update.inc.php",
		data: params,
		success: function(response)
			{
				$preview.append(response);
			},
		error: function(e)
			{
				alert(e);
			}
	
	});

	$overlay
		.appendTo('html')
		.fadeTo(300, .5);
	$preview
		.appendTo('html')
		.fadeTo(300, 1);

	return false;
}

//-->