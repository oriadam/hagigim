<?php
global $CONFIG;
require_once "config.php";
// require_once "reader.lib.php";

?>
<html>
<head>
	<title><?=$CONFIG["text_window_title"]?></title>
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
	<?=file_exists('custom/style.css') ? '<link href="custom/style.css" rel="stylesheet">':''?>
	<?=file_exists('custom/style.css.php') ? '<link href="custom/style.css.php" rel="stylesheet">':''?>
	<?=$CONFIG["html_head"]?>
</head>
<body class="<?=$CONFIG["rtl"]? 'rtl':'ltr'?>">
	<?=$CONFIG["html_body"]?>
	<template id="tmpl_empty_page">
	<div class="page_outer_wrap">
		<div class="empty_page">
			<div class="empty_page_content"></div>
		</div>
	</div>
	</template>
	<template id="tmpl_extra_page">
	<div class="skip_me page_wrap"></div>
	</template>
	<template id="tmpl_page">
	<div class="page_wrap">
		<div class="content_page show_page_title<?=$CONFIG["show_page_title"]?1:0?>">
			<div class="page_top">
				<div class='page_top_content'><?=$CONFIG["html_page_top"]?></div>
				<?php if ($CONFIG["show_page_title"]) { ?>
				<h1 class="page_title"></h1>
				<?php } ?>
			</div>
			<div class="page_content_wrapper">
				<div class="page_content"></div>
			</div>
			<?php if ($CONFIG["show_page_number"]) { ?>
			<div class="page_number_wrapper">
				<div class="page_number"></div>
			</div>
			<?php } ?>
		</div>
	</div>	
	</template>
	<template id="tmpl_cover_front">
	<div class="hard page_wrap cover_front">
		<?=$CONFIG["html_cover_front"]?>
	</div>
	</template>
	<template id="tmpl_inside_front">
	<div class="hard skip_me page_wrap inside_front">
		<?=$CONFIG["html_inside_front"]?>
	</div>
	</template>
	<template id="tmpl_inside_back">
	<div class="hard skip_me page_wrap inside_back">
		<?=$CONFIG["html_inside_back"]?>
	</div>
	</template>
	<template id="tmpl_cover_back">
	<div class="hard page_wrap cover_back">
		<?=$CONFIG["html_cover_back"]?>
	</div>
	</template>

	<div id="id-form" class="container book_container_width input-append form-inline form-group">
		<input id="id-q" placeholder="" type="text" class="form-control search-query top-form-element" />
		<span id="id-go" class="form-control btn btn-small btn-primary top-form-element top-form-button"></span>
		<output id="id-found" class="form-control text top-form-element"></output>
		<span id="id-prev" class="form-control btn btn-primary top-form-element top-form-button"></span>
		<span id="id-next" class="form-control btn btn-primary top-form-element top-form-button"></span>
	</div>
	<div id="book_container" class="book_container_width">
		<div id="pages_depth_l" class="pages_depth_element"></div>
		<div id="flipbook_parent">
			<div id="flipbook"></div>
		</div>
		<div id="pages_depth_r" class="pages_depth_element"></div>
	</div>
	<!-- START workaround for clip-path on firefox -->
	<!-- the svg must be loaded before style.css -->
	<!-- the style must be here and not inside style.css -->
	<svg width="0" height="0">
		<defs>
			<clipPath id="pages_depth_clip_l" clipPathUnits="objectBoundingBox">
				<polygon points="0 0.01, 1 0, 1 1, 0 0.99" />
			</clipPath>
			<clipPath id="pages_depth_clip_r" clipPathUnits="objectBoundingBox">
				<polygon points="0 0, 1 0.01, 1 0.99, 0 1" />
			</clipPath>
		</defs>
	</svg>
	<style>
		#pages_depth_l {
			clip-path: url("#pages_depth_clip_l");
		}
		#pages_depth_r {
	    	clip-path: url("#pages_depth_clip_r");
		}
	</style>

	<!-- END workaround for clip-path on firefox -->

	<script>
	// for a full list of options see http://www.turnjs.com/#api
	// width, height, pages are added automatically on build_book()
	var CONFIG = <?=json_encode(config_for_js())?>;

	var q = '';
	var lastQ; // previous search query
	var $body = $('body');
	var $book = $('#flipbook'); // book jQuery element
	var $book_parent = $('#flipbook_parent');
	var $size_parent = $('#book_container');
	var pages; // number of pages (including 4 cover pages)
	var book_list; // array of pages as json object of id,content,name,filename. note that array index = page - 3 (because it starts with 0 and does not include the cover pages)
	var ajax_cache = {}; // local cache of ajax requests - used by ajax() function
	var loaded_pages = []; // remember which pages were already loaded (or currently loading)
	var search_results = []; // array of search results (relevant on "search" mode, irrelevant on "filter" mode)
	var search_position = 0; // position in current search results (relevant on "search" mode, irrelevant on "filter" mode)
	var cover_pages_before,cover_pages_after;
	var page_content_scroll_hide_page_number = null;
	var turn_display_mode; // 'single' or 'double' pages view. intentionally start as undefined
	var direction = CONFIG["rtl"] ? 'rtl' : 'ltr';
	var mobile_mode; // mobile device mode true/false. intentionally start as undefined
	var mobile_orientation; // 'p' for portrait, 'l' for landscape. intentionally start as undefined
	var pages_depth_width = CONFIG["pages_depth"] ? undefined : 0; // width of pages_depth elements together. 0 for off. intentionally start as undefined
	var pages_depth_height;
	var $pages_depth_tooltip;
	var pages_depth_tooltip_page;
	var show_peel_corner_TO;
	var pause_turn_events;
	var search_results_clicked;
	var show_peel_corner = function(){
		clearTimeout(show_peel_corner_TO);
		show_peel_corner_TO = setTimeout(function(){
			if (!$book.turn('animating')&&!search_results_clicked){
				// bug when activating peel animation while moving pages or showing another peel
				// bug: srcolling stuck when peel animation
				$book.turn('peel','bl');
			}
			search_results_clicked = false;
		},2000);
	};
	if (CONFIG["remember_last_search"]){
		q = localStorage.getItem('turn_reader_q') || ''; // current search query
	};
	$('#id-q').prop('placeholder',CONFIG["text_search"]).val(q);
	$('#id-go').click(function(){
		q = $.trim($('#id-q').val());
		if (q==lastQ)
			return;
		handle_search(q);
	});
	$('#id-q').keypress(function(e) {
		if(e.which == 13) {
			$('#id-go').click();
		}
	});
	$('#id-prev').html(CONFIG["text_prev"]).click(function(){
		search_next_prev('previous');
	});
	$('#id-next').html(CONFIG["text_next"]).click(function(){
		search_next_prev('next');
	});
	$pages_depth_tooltip = $('<div id="pages_depth_tooltip">').hide().appendTo($body);
	if (CONFIG["browse_via_pages_depth"]){
		$('#pages_depth_l,#pages_depth_r').on('mousemove',function(event){
			var $elem = $(this);
			var width = $elem.width();
			var offset = $elem.offset();
			var pos = event.pageX - offset.left;
			var lr = this.id[this.id.length-1]; // 'l' or 'r'
			var percent = 1 - ((width - pos) / width);
			if (direction == "rtl") {
				// swap the directions
				lr = lr == 'l' ? 'r':'l';
				percent = 1 - percent;
			}
			var relevant_pages,pages_offset;
			if (lr=='l') {
				// handle left side (right side on rtl)
				pages_offset = cover_pages_before;
				relevant_pages = current_page() - cover_pages_before;
				if (relevant_pages > pages_offset){
					pages_depth_tooltip_page = pages_offset + Math.round(percent * relevant_pages);
				} else {
					pages_depth_tooltip_page = 0;
				}
			} else {
				// handle right side (left side on rtl)
				pages_offset = current_page() + cover_pages_before;
				relevant_pages = pages - pages_offset;
				if (relevant_pages > 0) {
					pages_depth_tooltip_page = pages_offset + Math.round(percent * relevant_pages);
				} else {
					pages_depth_tooltip_page = 0;
				}
			}
			if (pages_depth_tooltip_page){
				$pages_depth_tooltip.html(pages_depth_tooltip_page).show().offset({
					left : event.pageX - ($pages_depth_tooltip.width()),
					top: event.pageY - $pages_depth_tooltip.height()*2,
				})
			} else {
				$pages_depth_tooltip.hide();
			}
		}).on('mouseenter',function(){
			$pages_depth_tooltip.show();
		}).on('mouseleave',function(){
			$pages_depth_tooltip.hide();
		}).on('click',function(){
			if (pages_depth_tooltip_page){
				go_to_page(pages_depth_tooltip_page + cover_pages_before); // i don't understant why the +1 is necessary, but it is :-/
			}
		})
	}

	// create a new jQuery element out of a <template> element of id '#tmpl_'+name
	function tmpl(name,id){
		var clone = document.importNode(document.querySelector('#tmpl_'+name).content, true).querySelector('*');
		if (id)
			clone.id = id;
		return $(clone);
	}

	// turnjs turning event - when starting to animate turning to a specific page
	function turning(event, page, view) {
		clearTimeout(show_peel_corner_TO);
		if (pause_turn_events){
			return;
		}
		if (is_page_skip_me(page)){
    		event.preventDefault();
			go_to_page(page);
		} else {
			if (page > pages - cover_pages_after)
				return; // page out of range, a cover page, or is already loaded
			var range = $book.turn('range', page);
			for (var i = range[0]; i<=range[1]; i++){
				load_page(i);
			}
			pages_depth_turning();
		}
	}

	// turnjs turned event - when a page has finished animation and is displayed
	function turned(event, page, view) {
		if (pause_turn_events){
			return;
		}
		var visible_scrollable;
		for (var i = 0; i<=view.length; i++){
			if (handle_scrollable_pages(view[i])){
				visible_scrollable=1;
			}
		}
		if (CONFIG["show_peel_corner"] && !visible_scrollable){
			// bug: showing peel disables the scrolling
			show_peel_corner();
		}
		
		set_buttons_state();
		
	}

	// hide page numbers when scrolling down a page content
	if (CONFIG["show_page_number"]){
		page_content_scroll_hide_page_number = function(ev){
			var scrolled = !!ev.target.scrollTop;
			if (ev.target['data-scrolled'] != scrolled){
				ev.target['data-scrolled'] = scrolled;
				$(ev.target.parentElement).toggleClass('scrolled',scrolled);
			}
		};
	}

	// disable/enable prev/next buttons
	function set_buttons_state(){
		if (search_results.length){
			$('#id-next').toggleClass('disabled',search_position>=search_results.length-1);
			$('#id-prev').toggleClass('disabled',search_position<=0);
		} else {
			$('#id-next').toggleClass('disabled',current_page()>=pages);
			$('#id-prev').toggleClass('disabled',current_page()<=1);
		}
	}

	// check if page cannot be moved to
	function is_page_skip_me(page){
		return turn_display_mode=='single' && !!$('.p'+page+'.skip_me').length;
	}

	// wrapper for $book.turn('page',page)
	// handles skipping of cover pages on single page mode
	function go_to_page(page){
		if (page == 'prev'){
			page = 'previous';
		}
		if (page == 'next' || page == 'previous' ){
			if (turn_display_mode=='single'){
				var direction = page == 'previous' ? -1 : 1;
				var new_page = current_page()+direction;
				if (is_page_skip_me(new_page)){
					// activate skipping empty pages method below
					page = new_page;
				}
			}
		}
		if (turn_display_mode == 'single'){
			var direction = current_page()>page ? -1 : 1;
			// skipping empty pages when need to
			while (is_page_skip_me(page)){
				page+=direction;
			}
		}
		if (page == 'next' || page == 'previous') {
			$book.turn(page);
		} else {
			$book.turn('page',page);
		}
		pages_depth_turning();
	}

	// wrapper for $book.turn('page')
	function current_page() {
		return $book.turn('page');
	}

	// populate search results text
	function set_found_text(results_or_text){
		if (typeof results_or_text == 'string') {
			$('#id-found').html(results_or_text);
		} else if (typeof results_or_text == 'number') {
			if (results_or_text>0){
				$('#id-found').html(CONFIG["text_found"].replace('%s',results_or_text));
			} else {
				$('#id-found').html(CONFIG["text_not_found"]);
			}
		} else {
			$('#id-found').html("");
		}
	}

	// set status of currently searching
	function set_searching_state(state){
		$('#id-go').toggleClass('disabled',state).html( state? CONFIG["text_searching"]:CONFIG["text_go"] );
	}

	// handle search query
	function handle_search(q){
		if (lastQ != q) {
			// remember last visit q
			set_searching_state(true);
			var callback = function () {
				set_searching_state(false);
			}
			q = q || '';
			lastQ = q;
			if (CONFIG["remember_last_search"]){
				localStorage.setItem('turn_reader_q',q);
			}
			if (CONFIG["search_or_filter"]=='search'){
				load_search(q,callback);
			} else {
				// filter mode
				load_book(q,callback);
			}
		}
	}

	// search mode
	function load_search(q,callback){
		if (!q){
			// clear search
			set_found_text("");
			populate_search_results(false);
			if (callback){
				callback();
			}
		} else {
			// do search
			var url='ajax.php?f=list&q='+encodeURI(q||'');
			ajax(url,function(data){
				if (data.error){
					$('#id-found').text(data.error);
					return;
				}
				populate_search_results(data);
				if (callback){
					callback();
				}
			});
		}
	}

	// populate search results
	function populate_search_results(data){
		// sort and populate results by page numbers
		var pages = [];
		var length;
		if (data) {
			length = data.length;
			for (var i=0;i<data.length;i++){
				var id = data[i].id;
				pages[id_to_page(id)]=id;
			}
		}
		search_results = [];
		for(var i=0;i<pages.length;i++){
			if (pages[i]){
				search_results[search_results.length] = {
					id:pages[i],
					page:i
				};
			}
		}
		search_position = 0;
		set_found_text(length);
		set_searching_state(false);
		if (length){
			// go to first result
			go_to_search_position();
		}
		set_buttons_state();
	}

	function go_to_search_position(){
		set_found_text(CONFIG["text_found_in"].replace('%s1',search_position+1).replace('%s2',search_results.length));
		var page = search_results[search_position].page;
		if (page){
			// animate pages flow
			//pause_turn_events = true;

			//pause_turn_events = false;

			// go to actual page
			go_to_page(page);
		}
	}

	function id_to_page(id){
		for(var i=0;i<book_list.length;i++){
			if (book_list[i].id==id)
				return 1 + i + cover_pages_before;
		}
	}

	// next/prev buttons
	function search_next_prev(next_or_prev){
		if (next_or_prev=='prev')
			next_or_prev='previous';
		if (search_results.length){
			search_results_clicked = true;
			// next/prev search result
			if ((next_or_prev=='next' && search_position<search_results.length-1)
				|| (next_or_prev=='previous' && search_position>0 )){
					search_position += next_or_prev == 'next' ? 1 : -1;
				go_to_search_position();
			}
		} else {
			// next/prev page
			go_to_page(next_or_prev);
		}
		set_buttons_state();
	}

	// load a book according to a search query using ajax
	// used: filter mode + on first run
	function load_book(q,callback) {
		set_found_text("");
		set_searching_state(true);
		var url = 'ajax.php?f=list&q='+encodeURI(q||'');
		var ajax_object = ajax(url,function(data){
			set_searching_state(false);
			search_results = [];
			search_position = 0;
			if (data.error){
				set_found_text(data.error);
				return;
			}
			set_found_text(data.length);
			if (data.length){
				book_list = data;
				build_book();
				if (CONFIG["show_peel_corner"]) {
					show_peel_corner();
				}
				// always load first pages
				load_page(1+cover_pages_before);
				load_page(2+cover_pages_before);
				load_page(3+cover_pages_before);
				if (!CONFIG["start_with_closed_book"]){
					// start on first page
					go_to_page(3);
				}
			}
			if (callback){
				callback();
			}
			set_buttons_state();
			var args = arguments;
			args.callee.name = 'load_book_ready';
		});
	}

	// populate the book. based on book_list. removes previous content if any.
	function build_book(){
		// reset
		pages = book_list.length;
		var extra_blank_page = !!(pages % 2); // for odd number of pages, add a blank page at the end
		if (CONFIG["hard_cover"]) {
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
		if (CONFIG["middle_gradient"]){
			$book.addClass('middle_gradient');
		}
		$book.bind('turning', turning);
		$book.bind('turned', turned);
		$book.bind('start', start_event);
		loaded_pages = [];

		// append pages to book and remember which pages are already loaded
		var append = function(tmpl_name,page,id){
			if (page && !id)
				id = 'page-'+page;
			var $elem = tmpl(tmpl_name,id);
			$book.append($elem);
		}

		// add front cover
		if (CONFIG["hard_cover"]){
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
			append('extra_page',pages - 2);
		}

		// add back cover
		if (CONFIG["hard_cover"]){
			append('inside_back',pages-1,'inside_back');
			append('cover_back',pages,'cover_back');
		}

		// the turnjs magic!
		var turn_options = CONFIG["turn_options"];
		turn_options.width = $size_parent.width() - pages_depth_width;
		turn_options.height = $size_parent.height();
		turn_options.pages = pages;
		turn_options.direction = direction;
		$book.turn(turn_options);
		resize();
		// fix page width via css
		//$('#id-style-fix-pages').remove();
		//$('head').append('<style id="id-style-fix-pages">#flipbook .page { width:' +(turn_options.width/2)+'px; height:' + turn_options.height + 'px;</style>');
		handle_pages_depth();
	}//build_book

	// detect and handle single/double pages view mode. 
	// called by set_mode_mode() when mobile mode has changed
	function set_display_mode() {
		var mode = CONFIG["single_page_mode"]=="always" || (mobile_mode && CONFIG["single_page_mode"]=="mobile") ? 'single' : 'double';
		if (turn_display_mode!==mode) {
			// single/double mode change + on init
			turn_display_mode=mode;
			$body.toggleClass('display-single',turn_display_mode=='single').toggleClass('display-double',turn_display_mode=='double');
			if (turn_display_mode=='single'){
				go_to_page(current_page()); // make sure not to dispaly skip_me pages
			}
			$book.turn("display",turn_display_mode);
		}
	}//set_display_mode

	// handle book depth display/hide
	// called by resize() and build_book()
	function handle_pages_depth() {
		if (!CONFIG["pages_depth"]){
			return;
		}
		var width = 0;
		if (turn_display_mode=='double') {
			width = Math.min(CONFIG["pages_depth_max_width"],Math.floor(pages*CONFIG["pages_depth_paper_thickness"]));
		}
		var height = $size_parent.height();
		if (pages_depth_width !== width || pages_depth_height !== height){
			pages_depth_height = height;
			pages_depth_width = width;
			var visible = !!pages_depth_width;
			$('.pages_depth_element').toggle(visible);
			$body.toggleClass('pages_depth',visible);
			if (visible){
				pages_depth_turning();
			}
		}
	}//handle_pages_depth

	// handle book depth effect, width of both sides
	// called by turned()
	function pages_depth_turning() {
		if (pages_depth_width){
			var percent_of_book = current_page()/pages; 
			if (direction == "rtl"){
				percent_of_book = 1-percent_of_book;
			}
			var width_l = pages_depth_width * percent_of_book;
			var width_r = pages_depth_width - width_l;
			$('#pages_depth_l').width(width_l).toggleClass('has_width',!!pages_depth_width).height(pages_depth_height);
			$('#pages_depth_r').width(width_r).toggleClass('has_width',!!pages_depth_width).height(pages_depth_height);
			if (CONFIG["pages_depth_fix_margin"]){
				$('#pages_depth_l').css('margin-left' ,width_r);
				$('#pages_depth_r').css('margin-right',width_l);
			}
			$size_parent.toggleClass('pages_depth',!!pages_depth_width);
		}
	}

	// detect and handle mobile mode
	// called by resize()
	function set_mobile_mode() {
		var mode = window.innerWidth <= CONFIG["mobile_width"];
		if (mobile_mode !== mode){
			// desktop/mobile mode change + on init
			mobile_mode = mode;
			$body.toggleClass('mobile',mobile_mode).toggleClass('desktop',!mobile_mode);
			set_display_mode();
		}
		var orientation = mobile_mode ? (innerWidth>innerHeight ? 'p':'l') : undefined;
		if (mobile_orientation !== orientation) {
			mobile_orientation = orientation;
			$body.toggleClass('orientation-p',orientation == 'p').toggleClass('orientation-l',orientation=='l');
		}

	}//set_mobile_mode

	// turnjs animation start event 
	function start_event(event, pageObject, corner) {
		if (CONFIG["prevent_corner_peels_on_mobile_to_allow_scrolling"] && mobile_mode && mobile_orientation == 'p' && corner && corner[1]==direction[0]) {
			event.preventDefault();
			return false;
		}
	};

	// turnjs resize event - redetect mobile state, and reset the book size
	function resize(){
		set_mobile_mode();
		handle_pages_depth();
		var width = $size_parent.width() - pages_depth_width;
		$book_parent.width(width);
		$book.turn("size",width,$size_parent.height());
	}
	$(window).resize(resize);

	// make changes to a page
	function process_page(page,page_element,page_content,title){
		if (CONFIG["hr_to_read_more"]){
			var hr = page_element.find("hr:first");
			var hidden = hr.nextAll().addClass('hr_read_more_text').hide();
			var clickable = $("<span class='hr_read_more_clickable'>").html(CONFIG["text_hr_read_more"]).click(function(){
				clickable.remove();
				hidden.slideDown();
			});
			hr.after(clickable);
		}
		if (CONFIG["remove_first_row_if_identical_to_page_title"] || CONFIG["bold_first_content_line"]){
			var first_content_line = get_first_content_line(page_content[0]);
			first_content_line.className+=' first_content_line';
		}
		if (CONFIG["remove_first_row_if_identical_to_page_title"]){
			if ($.trim(first_content_line.textContent)==$.trim(title)){
				first_content_line.style.display = 'none';
			}
		}
		if (CONFIG["show_page_number"]){
			page_element.find('.page_number').html(page - cover_pages_before);
		}
		if (window.process_page_hook)
			window.process_page_hook(page, page_element, page_content, title);
	}

	// load a specific page number using ajax
	function load_page(page) {
		if (page<=cover_pages_before || page>pages - cover_pages_after || loaded_pages[page]){
			return; // page out of range, a cover page, or is already loaded
		}

		if (CONFIG["show_page_number"]){
			$('.p' + page + ' .page_number').html(page - cover_pages_before);
		}

		var index = page - cover_pages_before - 1; // index is zero-based, page is one-based
		if (book_list[index] && book_list[index].id) {
			loaded_pages[page]=1; // do not load same page twice

			// Get the data for that page
			ajax( 'ajax.php?f=content&id=' + book_list[index].id + '&modifiedTime=' + book_list[index].modifiedTime,function(data) {
				if (data.error){
					data.content = 'Error: ' + data.error;
					loaded_pages[page]=0; // allow retry loading by turning pages
				}

				// Create an element for this page
				var title = $.trim(data.name || book_list[index].name);
				var element = tmpl('page');
				if (CONFIG["show_page_title"]){
					element.find('.page_title').html(title);
				}
				var page_content = element.find('.page_content');
				page_content.html(data.content);
				$('#flipbook .p'+page).empty().append(element);

				process_page(page,element,page_content,title);
				
			});
		}
	}

	// helper for remove_first_row_if_identical_to_page_title and bold_first_content_line
	function get_first_content_line(element){
		var children = $(element).find(':not(:empty)');
		if (children.length){
			// traverse all children
			for (var i=0;i<children.length;i++){
				var value = get_first_content_line(children[i]);
				if (value!==undefined){
					// stop when element is found (return element), or when we past the first element (return false)
					return value;
				}
			}
		} else {
			// check current element
			var content = $.trim(element.textContent);
			if (content){
				return get_display_block_parent(element,content);
			}
		}
	}

	// helper for get_first_content_line - find the display:block parent
	function get_display_block_parent(element,content){
		while(getComputedStyle(element)['display']!='block'){
			var parent = element.parentElement;
			if ($.trim(parent.textContent)!=content){
				return element;
			}
			element = parent;
		}
		return element;
	}

	// handle scrollable pages
	function handle_scrollable_pages(page){
		var scrollable;
		var page_content = document.querySelector('.p'+page+' .page_content');
		if (page_content){
			scrollable = page_content.scrollHeight > page_content.offsetHeight + 10;
			if (scrollable){
				var $page_content = $(page_content);
				$page_content.addClass('scrollable');
				if (page_content_scroll_hide_page_number){
					$page_content.scroll(page_content_scroll_hide_page_number);
				}
			}
		}
		return scrollable;
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
					//console.log('ajax complete',url,arguments[0],arguments[1],arguments[2]);
				}
			});
		}
	}

	// load initial book by the last or default query
	if (CONFIG["search_or_filter"] == 'search'){
		// load entire book
		load_book('',function(){
			load_search(q);
		});
	} else {
		// load only search results
		load_book(q);
	}

	</script>
	<?=file_exists('custom/script.js') ? '<script src="custom/script.js"></script>':''?>
	<?=$CONFIG["html_footer"]?>
</body>
</html>