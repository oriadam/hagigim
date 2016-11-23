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
</head>
<body>
	<template id="tmpl_page">
	<div class="content-page">
		<h1 class="page-title"></h1>
		<div class="page-content"></div>
	</div>
	</template>
	<template id="tmpl_cover_front">
	<div class="hard">
		<?=$CONFIG['cover_front']?>
	</div>
	</template>
	<template id="tmpl_hard">
	<div class="hard"></div>
	</template>
	<template id="tmpl_loading">
	<div>
		<?=$CONFIG['loading_page']?>
	</div>
	</template>
	<template id="tmpl_page">
	<div></div>
	</template>
	<template id="tmpl_empty">
	<div></div>
	</template>
	<template id="tmpl_cover_back">
	<div class="hard">
		<?=$CONFIG['cover_back']?>
	</div>
	</template>

	<div id="id-form" class="container width-100">
		<div class="form-inline form-search">
			<div id="id-form-1" class="form-group width-50">
				<div class="input-append">
					<input id="id-q" placeholder="<?=$CONFIG['text_search']?>"
						type="text" class="form-control search-query width-50" /> <span
						id="id-go" class="form-control btn btn-small btn-primary width-50"><?=$CONFIG['text_go']?></span>
					<span id="id-searching"
						class="form-control btn btn-small btn-primary disabled width-50"><?=$CONFIG['text_searching']?></span>
				</div>
			</div>
			<div id="id-form-2" class="form-group width-50">
				<output id="id-found" class="form-control text width-50"></output>				
				<div class="form-group width-50">
					<span id="id-prev" class="form-control btn btn-primary width-50"><i class="glyphicon glyphicon-forward"></i></span>
					<span id="id-next" class="form-control btn btn-primary width-50"><i class="glyphicon glyphicon-backward"></i></span>
				</div>
			</div>
		</div>
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
	var single_page_mode_under_width_of = <?=$CONFIG["single_page_mode_under_width_of"]?>;
	var last_display_mode = 'double';

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
	function tmpl(name,page_number){
		return $($('#tmpl_'+name).html()).attr('id',page_number ? 'page-'+page_number : null);
	}

	// attached to the "turning" event
	function turning(event, page, view) {
		if (page > pages - cover_pages_after || loaded_pages[page])
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
					// always load first pages
					load_page(1+cover_pages_before);
					load_page(2+cover_pages_before);
					load_page(3+cover_pages_before);
					if (!start_with_closed_book){
						// start on first page
						$book.turn('page',3);
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
		$book.bind('turning', turning);
		loaded_pages = [];

		// append pages to book and remember which pages are already loaded
		var append = function($elem,page){
			$book.append($elem);
			loaded_pages[page]=1;
		}

		// add front cover
		if (hard_cover){
			append(tmpl('cover_front'),1);
			append(tmpl('hard'),2);
		}

		// add book content placeholders
		// book actual content will is loaded via ajax on turning event. see function turning()
		var i;
		for (i=0;i<book_list.length;i++){
			var page = i + 3;
			append(tmpl('empty'),0);
		}

		// when number of pages is odd, add another blank page to allow folding of the last page
		if (extra_blank_page) {
			append(tmpl('empty'),pages - 2);
		}

		// add back cover
		if (hard_cover){
			append(tmpl('hard'),pages - 1);
			append(tmpl('cover_back'), pages);
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
		if (page<=cover_pages_before || page>pages-cover_pages_after || loaded_pages[page])
			return; // page out of range, a cover page, or is already loaded

		var index = page - cover_pages_before - 1; // index is zero-based, page is one-based
		if (book_list[index] && book_list[index].id) {
			loaded_pages[page]=1; // do not load same page twice

			// Show 'loading...' message on page
			var element = tmpl('loading');
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
	setTimeout(function(){
		$book.turn('peel','bl');
	},2000);
	</script>
</body>
</html>