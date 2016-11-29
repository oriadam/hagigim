<?php
global $CONFIG;
require_once "config.php";
// require_once "reader.lib.php";

?>
<html>
<head>
<title><?=$CONFIG['page_title']?></title>
<meta name="viewport"
	content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
<script
	src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<link
	href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
	rel="stylesheet">
<script
	src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script src="http://www.turnjs.com/lib/turn.min.js"></script>
<link href="style.css" rel="stylesheet">
<?=$CONFIG['rtl'] ? '<link href="rtl.css" rel="stylesheet">':''?>
<?=file_exists('custom/style.css') ? '<link href="custom/style.css" rel="stylesheet">':''?>
<?=$CONFIG['head']?>
</head>
<body>
	<?=$CONFIG['body']?>
	<template id="tmpl_empty_page">
	<div></div>
	</template>
	<template id="tmpl_page">
	<div class="content-page">
		<h1 class="page-title"></h1>
		<div class="page-content"></div>
		<?php if ($CONFIG['page_number']) { ?>
		<div class="page_number"></div>
		<?php } ?>
	</div>
	</template>
	<template id="tmpl_loading">
	<div>
		<?=$CONFIG['loading_page']?>
	</div>
	</template>
	<template id="tmpl_cover_front">
	<div class="hard">
		<?=$CONFIG['cover_front']?>
	</div>
	</template>
	<template id="tmpl_inside_front">
	<div class="hard">
		<?=$CONFIG['inside_front']?>
	</div>
	</template>
	<template id="tmpl_inside_back">
	<div class="hard">
		<?=$CONFIG['inside_back']?>
	</div>
	</template>
	<template id="tmpl_cover_back">
	<div class="hard">
		<?=$CONFIG['cover_back']?>
	</div>
	</template>

	<div id="id-form" class="container width-100 input-append form-inline form-group">
		<span id="id-prev" class="form-control btn btn-primary width-10"><?=$CONFIG['text_prev']?></span>
		<input id="id-q" placeholder="<?=$CONFIG['text_search']?>" type="text" class="form-control search-query width-30" /> 
		<span id="id-go" class="form-control btn btn-small btn-primary width-10"><?=$CONFIG['text_go']?></span>
		<output id="id-found" class="form-control text width-40"></output>
		<span id="id-next" class="form-control btn btn-primary width-10"><?=$CONFIG['text_next']?></span>
	</div>
	<div id="book_container">
		<div id="flipbook"></div>
	</div>

	<script>
	// for a full list of options see http://www.turnjs.com/#api
	// width, height, pages are added automatically on build_book()
	turn_options = <?=json_encode($CONFIG['turn_options'])?>;

	var q = localStorage.getItem('turn_reader_q') || ''; // current search query
	var lastQ; // previous search query
	var $book = $('#flipbook'); // book jQuery element
	var $book_parent = $book.parent();
	var pages; // number of pages (including 4 cover pages)
	var book_list; // array of pages as json object of id,content,name,filename. note that array index = page - 3 (because it starts with 0 and does not include the cover pages)
	var ajax_cache = {}; // local cache of ajax requests - used by ajax() function
	var loaded_pages; // remember which pages were already loaded (or currently loading)
	var hard_cover = true; // currently - not supporting no hard covers
	var cover_pages_before,cover_pages_after;
	var start_with_closed_book = <?=$CONFIG["start_with_closed_book"] ? 1 : 0?>;
	var page_number = <?=$CONFIG["page_number"] ? 1 : 0?>;
	var single_page_mode_under_width_of = <?=$CONFIG["single_page_mode_under_width_of"]?>;
	var last_display_mode = 'double';
	var show_peel_corner_TO;
	var show_peel_corner = function(){
		clearTimeout(show_peel_corner_TO);
		show_peel_corner_TO = setTimeout(function(){
			if (!$book.turn('animating'))
				$book.turn('peel','bl');
		},2000);
	};
	$('#id-q').val(q);
	$('#id-searching').hide();
	$('#id-go').click(function(){
		q = $.trim($('#id-q').val());
		if (q==lastQ)
			return;
		$('#id-go').hide();
		$('#id-searching').show();
		$('#id-found').text('...');
		load_book(q);
	});
	$('#id-q').keypress(function(e) {
		if(e.which == 13) {
			$('#id-go').click();
		}
	});
	$('#id-prev').click(function(){
		$book.turn('previous');
	});
	$('#id-next').click(function(){
		$book.turn('next');
	});
	// create a new jQuery element out of a <template> element of id '#tmpl_'+name
	function tmpl(name,id){
		var clone = document.importNode(document.querySelector('#tmpl_'+name).content, true).children[0];
		if (id)
			clone.id = id;
		return $(clone);
	}

	// attached to the "turning" event
	function turning(event, page, view) {
		<?php if ($CONFIG["show_peel_corner"]) {?>
		show_peel_corner();
		<?php }?>
		if (page > pages - cover_pages_after)
			return; // page out of range, a cover page, or is already loaded
		var range = $book.turn('range', page);
		for (var i = range[0]; i<=range[1]; i++)
			load_page(i);
	}

	// load a book according to a search query using ajax
	function load_book(q) {
		if (lastQ != q) {
			// remember last visit q
			q = q || '';
			lastQ = q;
			localStorage.setItem('turn_reader_q',q);

			var url='ajax.php?f=list&q='+encodeURI(q||'');
			ajax(url,function(data){
				if (data.error){
					$('#id-found').text(data.error);
					return;
				}
				if (data.length){
					book_list = data;
					build_book();
					<?php if ($CONFIG["show_peel_corner"]) {?>
					show_peel_corner();
					<?php }?>
					// always load first pages
					load_page(1+cover_pages_before);
					load_page(2+cover_pages_before);
					load_page(3+cover_pages_before);
					if (!start_with_closed_book){
						// start on first page
						$book.turn("page",3);
					}
					$('#id-found').html("<?=$CONFIG['text_found']?>".replace('%s',data.length));
				} else {
					$('#id-found').html("<?=$CONFIG['text_not_found']?>");
				}
				$('#id-go').show();
				$('#id-searching').hide();
			});
		}
	}

	// populate the book. based on book_list. removes previous content if any.
	function build_book(){
		// reset
		pages = book_list.length;
		var extra_blank_page = !!(pages % 2); // for odd number of pages, add a blank page at the end
		if (hard_cover) {
			cover_pages_before = cover_pages_after = 2;
		} else {
			cover_pages_before = cover_pages_after = 0;
		}
		if(extra_blank_page){
			cover_pages_after++;
		}
		pages += cover_pages_before + cover_pages_after;

		var id = $book[0].id;
		try{
			$book.turn("destroy")
		}catch(e){}
		$book.remove();
		$book = $('<div id="'+id+'">').appendTo($book_parent);
		/*<?php if ($CONFIG["pages_depth"]){ ?>*/
		$book.addClass("pages_depth");
		/*<?php } ?>*/
		/*<?php if ($CONFIG["middle_gradient"]){ ?>*/
		$book.addClass('middle_gradient');
		/*<?php } ?>*/
		$book.bind('turning', turning);
		loaded_pages = [];

		// append pages to book and remember which pages are already loaded
		var append = function(tmpl_name,page,id){
			if (page && !id)
				id = 'page-'+page;
			var $elem = tmpl(tmpl_name,id);
			$book.append($elem);
		}

		// add front cover
		if (hard_cover){
			append('cover_front',1,'cover_front');
			append('inside_front',2,'inside_front');
		}

		// add book content placeholders
		// book actual content will is loaded via ajax on turning event. see function turning()
		var i;
		for (i=0;i<book_list.length;i++){
			var page = i + 3;
			append('empty_page',page);
		}

		// when number of pages is odd, add another blank page to allow folding of the last page
		if (extra_blank_page) {
			append('empty_page',pages - 2);
		}

		// add back cover
		if (hard_cover){
			append('inside_back',pages-1,'inside_back');
			append('cover_back',pages,'cover_back');
		}

		// the magic!
		var options = turn_options;
		//options.width = $book_parent.width();
		//options.height = $book_parent.height();
		options.width = '100%';
		options.height = '100%';
		options.pages = pages;
		$book.turn(options);
		resize();
		// fix page width via css
		$('#id-style').remove();
		$('head').append('<style id="id-style">#flipbook .page { width:' +(options.width/2)+'px; height:' + options.height + 'px;</style>');

	}
	function resize(){
		$book.turn("size",$book_parent.width(),$book_parent.height());
		var display = window.innerWidth > single_page_mode_under_width_of ? 'double' : 'single';
		if (last_display_mode!=display){
			last_display_mode=display;
			$book.turn("display",display);
		}
	}
	$(window).resize(resize);

	// load a specific page number using ajax
	function load_page(page) {
		if (page<=cover_pages_before || page>pages - cover_pages_after || loaded_pages[page]){
			return; // page out of range, a cover page, or is already loaded
		}

		if (page_number){
			$('.p' + page + ' .page_number').html(page - cover_pages_before);
		}

		var index = page - cover_pages_before - 1; // index is zero-based, page is one-based
		if (book_list[index] && book_list[index].id) {
			loaded_pages[page]=1; // do not load same page twice

			// Show 'loading...' message on page
			var element = tmpl("loading");
			element.find('.page-title').html(book_list[index].name);
			$('#flipbook .p'+page).empty().append(element);
			//$book.turn('addPage', element, page);

			// Get the data for that page
			ajax( 'ajax.php?f=content&id=' + book_list[index].id + '&modifiedTime=' + book_list[index].modifiedTime,function(data) {
				if (data.error){
					data.content = 'Error: ' + data.error;
					loaded_pages[page]=0;
				}

				// Create an element for this page
				var element = tmpl('page');
				element.find('.page-title').html(data.name || book_list[index].name);
				element.find('.page-content').html(data.content);
				if (page_number){
						element.find('.page_number').html(page - cover_pages_before);
				}
				
				$('#flipbook .p'+page).empty().append(element);
				//$book.turn('addPage', element, page);
			});
		}

	}

	// handle ajax calls + local memory caching
	function ajax(url,callback){
		if (url in ajax_cache){
			callback(ajax_cache[url]);
		} else {
			$.ajax({
				url: url,
				success:function(msg){
					var data;
					try{
						 data = JSON.parse(msg);
					}catch(e){
						console.log("AJAX Error: ", msg, url);
						return callback({error:msg});
					}
					if (data.error){
						console.log("AJAX Error: ", data.error, url);
					} else {
						ajax_cache[url] = data;
					}
					callback(data);
				},
				complete:function(){
					console.log('ajax complete',url,arguments[0],arguments[1],arguments[2]);
				}
			});
		}
	}

	// load initial book by the last or default query
	load_book(q);
	</script>
	<?=$CONFIG['footer']?>
</body>
</html>