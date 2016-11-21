<?php
global $CONFIG;
require_once "config.php";
require_once "reader.lib.php";

?>
<html>
<head>
	<title><?=$CONFIG['page_title']?></title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js"></script></head>
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<link href="style.css" rel="stylesheet">
</head>
<body>
<template id="tmpl_page">
	<div class="page">
		<h1 class="page-title"></h1>
		<div class="page-content"></div>
	</div>
</template>
<template id="tmpl_cover_front">
	<div class="hard cover_front">
		<?=$CONFIG['cover_front']?>
	</div>
</template>
<template id="tmpl_hard">
	<div class="hard"></div>
</template>
<template id="tmpl_loading">
	<div class="page">Loading page...</div>
</template>
<template id="tmpl_cover_back">
	<div class="hard cover_back">
		<?=$CONFIG['cover_back']?>
	</div>
</template>

<div class="container">
	<div class="form-group form-inline form-search">
		<div class="input-append">
			<input id="id-q" placeholder="חיפוש" type="text" class="form-control search-query">
			<span id="id-go" class="form-control btn btn-small btn-primary">המשך</span>
		</div>
	</div>
	<div id="book_container">
		<div id="book"></div>
	</div>
</div>

<script>
	turn_options = {
		// for list of options see http://www.turnjs.com/#api
		"width":"100%",
		"height":"100%",
		"autoCenter":true,
	};
	
	var ajax_cache = {}; // used by ajax() function
	var q = localStorage.getItem('turn_reader_q') || '';
	var lastQ; 
	var page = localStorage.getItem('turn_reader_page') || 0;
	var lastPage;
	var $book = $('#book');
	var book_pages;
	var list;

	$('#id-q').val(q);
	
	$('#book').bind('turning', function(event, page, view) {
		var range = $(this).turn('range', page);
		for (page = range[0]; page<=range[1]; page++)
			addPage(page, $(this));
			goToPage(page);
	});

	$('#id-go').click(function(){
		q = $('#id-q').val();
		load_book(q);
	});

	function tmpl(name){
		return $($('#tmpl_'+name).html());
	}

	function build_book(list,current_page){
		// reset 
		var pages = 4 + list.length;
		book_pages = [];
		$book
			.empty()
			//.turn('destroy')
			.turn($.extend(turn_options,{pages:pages}))
		
		// add front cover	
		book_pages[1] = tmpl('cover_front');
		book_pages[2] = tmpl('hard');
		$book
			.turn("addPage", book_pages[1], 1)
			.turn("addPage", book_pages[2], 2)

		// add book content
		var i;
		for (i=0;i<list.length;i++){
			book_pages[i+3] = list[i];
		}
		// book actual content is loaded via ajax on turning event. see goToPage()

		// add back cover
		book_pages[pages - 1] = tmpl('hard');
		book_pages[pages] = tmpl('cover_back');
		$book
			.turn("addPage", book_pages[pages - 1], pages - 1)
			.turn("addPage", book_pages[pages], pages)
	
		// go to first (or last read) page - this also handles ajax loading of the pages
		goToPage(current_page);
	}

	function goToPage(page){
		if (lastPage!=page){
			rememberPage(page);
			$book.turn('page',page);
		}
	}

	function rememberPage(page){
		lastPage = page;
		localStorage.setItem('turn_reader_page',page);
	}

	function load_book(q) {
		if (lastQ != q) {
			// remember last visit q and page
			q = q || '';
			lastQ = q;
			localStorage.setItem('turn_reader_q',q);

			var url='ajax.php?f=list&q='+encodeURI(q||'');
			ajax(url,function(list){
				build_book(list,lastPage);
				rememberPage(q ? 1:0);
			});
		}
	}

	function addPage(page, book) {
		// Check if the page is not in the book
		if (!book.turn('hasPage', page)) {
			if (book_pages[page] && book_pages[page].id){
				// Create an element for this page
				var element = tmpl('loading');
				book.turn('addPage', element, page);
				// Get the data for this page
				ajax( 'ajax.php?f=content&id=' + book_pages[page].id,function(data) {
					element.html(data.content);
				});
			}
		}
	}

	function ajax(url,callback){
		if (url in ajax_cache){
			callback(ajax_cache[url]);
		} else {
			$.ajax({
				url: url,
				success:function(msg){
					console.log('ajax success',url,arguments[0],arguments[1],arguments[2]);
					ajax_cache[url] = JSON.parse(msg);
					callback(ajax_cache[url]);
				},
				complete:function(){
					console.log('ajax',url,arguments[0],arguments[1],arguments[2]);
				}
			});
		}
	}

	load_book(q);
	</script>
	</body>
	</html>