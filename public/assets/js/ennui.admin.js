<!--//

// This variable is the serialized divs in the admin gallery
var $order = null,
    tinymcefile = '/assets/js/tiny_mce/tiny_mce.js',
    processfile = '/assets/inc/update.inc.php',
    uploadifyfile = '/assets/inc/uploadify.inc.php';

$.ajaxSetup({
		"type" : "POST",
		"url" : processfile,
        "error" : function(e) { alert(e); }
});

jQuery(function($){

// Handler for edit button
$(".ecms-edit").live("click", function(e){
        e.preventDefault();

        var url_array = $(this).attr("href").split('/'),
            page = url_array[1],
            id = url_array[3]!=undefined ? url_array[3] : '';

        showedit(page, 'showoptions', id);
    });

// Handler for gallery edit button
$(".ecms-gallery").live("click", function(e){
        e.preventDefault();

        var url_array = $(this).attr("href").split('/'),
            page = url_array[1],
            id = url_array[3]!=undefined ? url_array[3] : '';

        galleryEdit(page, id, '/assets/images/userPics/gallery/');
    });

});

function showedit(page,option,id) {
	var params = "page=" + page + "&action=" + option + "&id=" + id;

	$.ajax({
		data: params,
		success: function(response)
        {
            $.fn.thumbBox.buildModal(response);
            $(".thumbbox-main-modal *")
                .bind("click", function(e){e.stopPropagation();})
            setTimeout("textEdit();", 500);
        }
	});
}

function reorderEntry(page, pos, direction, id) {
	var params = "page="
		+ page
		+ "&action=reorderEntry&pos="
		+ pos
		+ "&id="
		+ id
		+ "&direction="
		+ direction;

	$.ajax({
		data: params,
		success: function()
			{
				document.location = "/"+page;
			}
	});
}

function galleryEdit(page, id, dir) {
	var folder = dir+page+id,
		params = "page=" + page + "&id=" + id + "&action=galleryEdit";

	$.ajax({
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
			data: "page="+page+"&id="+id+"&action=galleryOrder&"+$order,
			success: function(msg){
				$("#"+page).html(msg);
			}
		});
		return false;
	});
}

function addPhotoCaption(page, album_id, image_id, image_cap) {
	var params = "page=" + page
			+ "&album_id=" + album_id
			+ "&image_id=" + image_id
			+ "&image_cap=" + image_cap
			+ "&action=galleryAddCaption";

	$.ajax({
		data: params,
		success: function(response)
			{
				$('#'+page).html(response);
		    	setTimeout("galleryUpload('"+page+album_id+"')",500);
			}
	});
}

function deletePhoto(page, id, img) {
	var params = "page="+page+"&id="+id+"&image="+img+"&action=galleryDeletePhoto";

	$.ajax({
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
        script_url : tinymcefile,
        theme : "advanced",
        plugins : "safari,iespell,inlinepopups,spellchecker,paste,advimage,media",
        theme_advanced_blockformats : "p,h2,h3,blockquote,code",
        style_formats : [
            {title : 'H2 Title', block : 'h2'},
            {title : 'H3 Title', block : 'h3'}
        ],
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_buttons1 : "pasteword,|,bold,italic,underline,blockquote,|,"
            + "justifyleft,justifycenter,justifyright,|,"
            + "bullist,numlist,outdent,indent,|,link,unlink,image,media,code,"
            + "|,forecolor,backcolor,|,formatselect",
        theme_advanced_buttons2 : "",
        theme_advanced_buttons3 : "",
        relative_urls : false
    });
}

function galleryUpload(dir, page, id)
{
	$("#fileUpload").fileUpload({
		'uploader': '/assets/swf/uploader.swf',
		'cancelImg': '/assets/images/cancel.png',
		'script': uploadifyfile,
		'folder': '/'+dir+page+id,
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
		data: params,
		success: function(response){
				$preview.append(response);
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