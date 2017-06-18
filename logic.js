
		['left', 'right', 'top', 'bottom'].forEach(function(navid) {
			if (/\w/.test(CONFIG['tb_list_' + navid]))
				$body.append(tb_generate(CONFIG['tb_list_' + navid], navid,navid.indexOf('o')>=0? 'tb-h':'tb-v'));
		});

		function music_handler(){
			if (music_active){
				if (last_music_url!=current_music_url){
					$music_tag[0].src = last_music_url = current_music_url;
					$music_tag.stop()[0].volume = 1;
					try {
						$music_tag[0].play();
					}catch(e){
						console.error(e);
					}
				}
			} else if (last_music_url) {
				last_music_url = null;
				$music_tag.animate({volume: 0, duration:200,complete:function(){
					try {
						$music_tag[0].pause();
					}catch(e){
						console.error(e);
					}
				}});
			}
		}

		// create a new jQuery element out of a <template> element of id '#tmpl_'+name
		function tmpl(name,id){
			var clone = document.importNode(document.querySelector('#tmpl_'+name).content, true).querySelector('*');
			if (id)
				clone.id = id;
			//consolelog('tmpl(',name,') = ',clone);
			return $(clone);
		}//tmpl

		// show teaser for flipping pages
		function show_peel_corner(){
			consolelog('show_peel_corner');
			clearTimeout(show_peel_corner_TO);
			show_peel_corner_TO = setTimeout(function(){
				if (!$book.turn('animating')&&!search_results_clicked){
					// bug when activating peel animation while moving pages or showing another peel
					// bug: srcolling stuck when peel animation
					$book.turn('peel','bl');
				}
				search_results_clicked = false;
			},2000);
		}//show_peel_corner

		// check if page cannot be moved to
		function is_page_skip_me(page){
			return turn_display_mode=='single' && !!$('.p'+page+'.skip_me').length;
		}

		// wrapper for $book.turn('page',page)
		// handles skipping of cover pages on single page mode
		function go_to_page(page){
			if (page == 'prev'){text_share_dialog
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
			if (page == 'last')
				page = numpages - 1;
			if (page == 'first')
				page = 1;
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
				if (page>0 && page<numpages){
					$book.turn('page',page);
				} else {
					console.log('Error go_to_page(',page,') - page not in range of book');
				}
			}
			pages_depth_turning();
			consolelog('go_to_page(',page,') to ',page);
		}//go_to_page

		// get objects of the pages in current view (if any)
		function current_pages_docs(){
			var a = [];
			var view = $book.turn('view');
			for (var i=0;i<view.length;i++){
				var idx = page_number_to_page_index(view[i]);
				if (book_list[idx]){
					a[a.length] = book_list[idx];
					a[a.length-1].page_number = view[i];
					a[a.length-1].idx = idx;
				}
			}
			return a;
		}

		function handle_title(){
			if (CONFIG["text_window_title_content"]){
				var rx = /\%page_title/;
				var title = '';
				if (rx.test(CONFIG["text_window_title_content"])){
					var docs = current_pages_docs();
					for (var i=0;i<docs.length;i++){
						if (docs[i].name){
							if (title)
								title += CONFIG["text_window_title_separator"];
							title += docs[i].name;
						}
					}
				}
				document.title = CONFIG["text_window_title_content"].replace(rx,title || '');
				var meta = document.querySelector('#og_title');
				if (meta){
					meta.name = "title";
					meta.content = document.title;
				}
			}
			if (CONFIG["generate_og_image"]) {
				// add share image
				var meta = document.querySelector('#og_image');
				meta.name = 'og:image';
				html2canvas(document.querySelector('#book_container'), {
					onrendered: function(canvas) {
						meta.content = canvas.toDataURL();
					}
				});
			}
		}

		function handle_pushstate(page){
			if (enable_pushstate) {
				var new_url_search = '?p='+page;
				if (location.search!=new_url_search)
					window.history.pushState({"page":page},"", new_url_search);
			}
		}
		
		window.onpopstate = function(e){
			if(e.state){
				go_to_page(e.state.page);
			}
		};

		// wrapper for $book.turn('page')
		function current_page() {
			return $book.turn('page');
		}

		function current_pages() {
			return $book.turn('view');
		}

		function current_pagenum(){
			var current = current_page() - cover_pages_before;
			return current<0 || current>total_pagenum() ? false : current;
		}

		function go_to_pagenum(page){
			if (isFinite(page))
				go_to_page(cover_pages_before + page);
			else
				go_to_page(page);
		}

		function total_pagenum(){
			return numpages - cover_pages_before - cover_pages_after;
		}

		function total_page(){
			return numpages;
		}

		// populate search results text
		function set_found_text(results_or_text){
			if (typeof results_or_text == 'string') {
				$('#tb-item-nav-found').html(results_or_text);
			} else if (typeof results_or_text == 'number') {
				if (results_or_text>0){
					$('#tb-item-nav-found').html(CONFIG["text_found"].replace('%s',results_or_text));
				} else {
					$('#tb-item-nav-found').html(CONFIG["text_not_found"]);
				}
			} else {
				$('#tb-item-nav-found').html("");
			}
		}//set_found_text

		// set status of currently searching
		function set_searching_state(state){
			$('#tb-item-nav-go').toggleClass('disabled',state);
			$('#tb-item-nav-go .tb-icon').toggleClass('fa-refresh fa-spin',state).toggleClass('fa-search',!state);
		}

		// handle search query
		function handle_search(){
			var q = $.trim($('#id-q').val());
			if (search_q !== q) {
				search_q = q;
				set_searching_state(true);
				var set_searching_state_false = function () {
					set_searching_state(false);
				}
				if (CONFIG["remember_last_search"]){
					// remember last visit q
					localStorage.setItem('turn_reader_q',q);
				}
				if (CONFIG["search_or_filter"]=='search'){
					// search mode
					load_search(q,set_searching_state_false);
				} else {
					// filter mode
					load_book(q,set_searching_state_false);
				}
				consolelog('handle_search(',q,')');
			}
		}//handle_search

		// search mode
		function load_search(q,callback){
			consolelog('load_search(',q,')');
			if (!q){
				// clear search
				set_found_text("");
				populate_search_results(false);
				if (callback){
					callback();
				}
			} else {
				// do search
				var url='ajax.php?f=list&cfg='+CUSTOM_CONFIG_NAME+'&q='+encodeURI(q||'');
				var ajax_object = ajax(url,function(data){
					if (data.error){
						$('#tb-item-nav-found').text(data.error);
						return;
					}
					populate_search_results(data);
					if (callback){
						callback();
					}
				});
			}
		}//load_search

		// populate search results
		function populate_search_results(data){
			consolelog('populate_search_results(',data,')');
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
		}//populate_search_results

		function go_to_search_position(){
			consolelog('go_to_search_position: search_results[',search_position,'] = ',search_results[search_position]);
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
		function search_next_prev(next_or_prev,search_or_page){
			if (!search_or_page)
				search_or_page = search_results.length ? 's' : 'p';
			if (search_or_page == 's' && !search_results.length)
				return;
			if (next_or_prev=='prev')
				next_or_prev='previous';
			if (search_or_page == 's'){
				search_results_clicked = true;
				// next/prev search result
				if ((next_or_prev=='next' && search_position<search_results.length-1)
					|| (next_or_prev=='previous' && search_position>0 )){
						search_position += next_or_prev == 'next' ? 1 : -1;
				}
				// last/first search result
				if (next_or_prev=='first' || next_or_prev=='last'){
						search_position = next_or_prev == 'last' ? search_results.length-1 : 0;
				}
				go_to_search_position();
			} else {
				// next/prev page
				go_to_page(next_or_prev);
			}
			tb_updateState();
		}//search_next_prev

		function load_music_files(){
			var url = 'ajax.php?f=music&cfg='+CUSTOM_CONFIG_NAME;
			var ajax_object = ajax(url,function(data){
				if (data.length){
					music_list = data;
				}
			});
		}

		function toggle_bookmark(){
			var pages = current_pages();
			var i0 = bookmarks.indexOf(pages[0]);
			var i1 = bookmarks.indexOf(pages[1]);
			if (i0>=0 || i1>=0){
				if (i0>=0)
					bookmarks.splice(i0,1);
				if (i1>=0)
					bookmarks.splice(i1,1);
			}
			else
			{
				bookmarks.push(pages[0]);
			}
			localStorage.setItem('bookmarks',JSON.stringify(bookmarks));
		}

		function is_bookmarked(){
			var pages = current_pages();
			return bookmarks.indexOf(pages[0])>=0 || bookmarks.indexOf(pages[1])>=0;
		}

		function load_bookmarks(){
			bookmarks = JSON.parse(localStorage.getItem('bookmarks')||'[]');
		}

		function show_bookmarks(){
			bookmarks.sort(function sortNumber(a,b) {return a - b;});
			$('#bookmarks').css($book_parent.position()).width($book_parent.width()).height($book_parent.height());
			bookmarks.forEach(show_bookmark);
		}

		function show_bookmark(page,index) {
			var id = 'bm-'+page;
			var $bookmarks = $('#bookmarks');
			var $elem = $('#'+id);
			if (!$('#'+id).length)
				$elem = $('<div id="' + id + '" class="bookmark_ribbon" data-page="' + page + '" title="' + page_index_to_display_number(page_number_to_page_index(page)) + '">').appendTo($bookmarks);
			var pages = current_pages();
			if (page == pages[0] || page == pages[1])
				$elem.hide();
			else
			{
				var after = page > pages[0] && page > pages[1];
				var side = direction.charAt(after ? 2 : 0) == 'l' ? 'left' : 'right';
				if (side == 'left')
					$elem.css('right','').css('left',-10-$('#pages_depth_l').width());
				else
					$elem.css('left','').css('right',-10-$('#pages_depth_r').width());
				$elem.css('top',10 + (25 * index)).click(bookmark_click).toggleClass('r',side=='right').toggleClass('l',side=='left').show();
			}
		}

		function hide_bookmark(page) {
			var id = 'bm-'+page;
			$('#'+id).remove();
		}

		function bookmark_click(ev) {
			var $elem = $(ev.target);
			var page = $elem.data('page');
			if (page)
				go_to_page(page);
		}

		function get_page_from_location_search(){
			var rx,match;
			rx = /\bp=(\d+)\b/;
			if (rx.test(location.search)){
				match = location.search.match(rx);
				return (1*match[1]) || false;
			}
			rx = /\bid=([^%&]+)\b/;
			if (rx.test(location.search)){
				match = location.search.match(rx);
				return id_to_page(match[1]);
			}
		}

		// load a book according to a search query using ajax
		// used: filter mode + on first run
		function load_book(q,callback) {
			set_found_text("");
			set_searching_state(true);
			var url = 'ajax.php?f=list&cfg='+CUSTOM_CONFIG_NAME+'&q='+encodeURI(q||'');
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
					var p = get_page_from_location_search();
					if (p){
						// start on page from url
						go_to_page(p);
					} else if (!CONFIG["start_with_closed_book"]){
						// start on first page
						go_to_page(3);
					}
				}
				handle_title();
				if (callback){
					callback();
				}
				if (typeof book_loaded_hook == 'function'){
					book_loaded_hook();
				}
				enable_pushstate = 1;
				setTimeout(tb_updateState,100);
			});
		}//load_book

		// generate the index pages and add them to correct place
		function generate_index(){
			if (!CONFIG["generate_index"])
				return;
			var tmpl_row = '<li class="idx_row" pi="__PAGE__"><span class="idx_name">__NAME__</span><span class="idx_number">__NUMBER__</span></a></li>';
			var current_page,i;
			var rows = [];
			var pages_html = [];
			index_pages=[];
			index_numpages = Math.ceil(book_list.length/CONFIG["index_lines_per_page"]);

			if (CONFIG["generate_index"]=="start"){
				cover_pages_before += index_numpages;
			} else {
				cover_pages_after += index_numpages;
			}
			
			// generate the rows
			for (i=0;i<book_list.length;i++){
				current_page = Math.floor(i/CONFIG["index_lines_per_page"]);
				pages_html[current_page] = pages_html[current_page] || '';
				pages_html[current_page] += tmpl_row.replace('__NAME__',book_list[i].name).replace('__PAGE__',i).replace('__NUMBER__',page_index_to_display_number(i));
			}
			for (current_page=0;current_page<pages_html.length;current_page++){
				index_pages[current_page] = tmpl('index_page');
				index_pages[current_page].find('.idx_list').append(pages_html[current_page]).find('.idx_row').click(index_row_click);
			}
		}

		function index_row_click(){
			var p = page_index_to_page_number(1*jQuery(this).attr('pi'));
			go_to_page(p);
		}

		// return page number for displaying page number
		function page_index_to_display_number(i){
			return i + 1;
		}
		// return page number for a page element index
		function page_index_to_page_number(i){
			return i + 1 + cover_pages_before;
		}

		// return page index for a page number
		function page_number_to_page_index(i){
			return i - 1 - cover_pages_before;
		}

		// populate the book. based on book_list. removes previous content if any.
		function build_book(){
			// reset
			cover_pages_before = 0;
			cover_pages_after = 0;
			generate_index();
			
			numpages = book_list.length + cover_pages_before + cover_pages_after;
			var extra_blank_page = (numpages % 2)>0; // for odd number of pages, add a blank page at the end
			if (CONFIG["hard_cover"]) {
				cover_pages_before += 2;
				cover_pages_after += 2;
			}
			if(extra_blank_page){
				cover_pages_after++;
			}

			numpages = book_list.length + cover_pages_before + cover_pages_after;

			var id = $book[0].id;
			try{
				$book.turn("destroy")
			}catch(e){}
			$book.remove();
			$book = $('<div id="'+id+'">').appendTo($book_parent);
			$book.bind('turned', turned);
			$book.bind('turning', turning);
			$book.bind('start', animation_start);
			$book.bind('end', animation_end);
			if (CONFIG["middle_gradient"]){
				$book.addClass('middle_gradient');
			}
			loaded_pages = [];

			// append pages to book and remember which pages are already loaded
			var append = function(tmpl_name,page,id){
				if (page && !id)
					id = 'page-'+page;
				var $elem = tmpl(tmpl_name,id);
				return $elem.appendTo($book);
			}

			// add front cover
			if (CONFIG["hard_cover"]){
				append('cover_front',1,'cover_front');
				append('inside_front',2,'inside_front');
			}

			// add index if at beginning
			if (CONFIG["generate_index"]=="start"){
				for (i=0;i<index_pages.length;i++){
					$book.append(index_pages[i]);
				}
			}

			// add book content placeholders
			// book actual content will is loaded via ajax on turning event. see function turning()
			var i;
			for (i=0;i<book_list.length;i++){
				var page = page_index_to_page_number(i);
				append('empty_page',page).attr('i',i);
			}

			// when number of pages is odd, add another blank page to allow folding of the last page
			if (extra_blank_page) {
				append('extra_page',numpages - 2);
			}

			// add back cover
			if (CONFIG["hard_cover"]){
				append('inside_back',numpages-1,'inside_back');
				append('cover_back',numpages,'cover_back');
			}

			// add index if at end
			if (CONFIG["generate_index"]=="end"){
				for (i=0;i<index_pages.length;i++){
					$book.append(index_pages[i]);
				}
			}

			// the turnjs magic!
			var turn_options = CONFIG["turn_options"];
			turn_options.width = $size_parent.width() - pages_depth_width;
			turn_options.height = $size_parent.height();
			turn_options.pages = numpages;
			turn_options.direction = direction;
			turn_count = 0;
			$book.turn(turn_options);
			build_book_time = Date.now();
			resize();
			$(window).off('resize',resize).resize(resize);
			setTimeout(resize,100);
			setTimeout(resize,1000);
			setTimeout(resize,2000);
			init($book);

			// fix page width via css
			//$('#id-style-fix-pages').remove();
			//$('head').append('<style id="id-style-fix-pages">#flipbook .page { width:' +(turn_options.width/2)+'px; height:' + turn_options.height + 'px;</style>');
		}//build_book

		// detect and handle single/double pages view mode. 
		// called by resize()
		function set_display_mode() {
			var mode = CONFIG["single_page_mode"]=="always" || (mobile_mode && CONFIG["single_page_mode"]=="mobile") ? 'single' : 'double';
			if (turn_display_mode!==mode) {
				// single/double mode change + on init
				turn_display_mode=mode;
				$body.removeClass('display-double display-single');
				$body.addClass('display-'+turn_display_mode);
				$book.turn("display",turn_display_mode);
				if (turn_display_mode=='single'){
					go_to_page(current_page()); // make sure not to dispaly skip_me pages
				}
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
				width = Math.min(CONFIG["pages_depth_max_width"],Math.floor(numpages*CONFIG["pages_depth_paper_thickness"]));
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
				var percent_of_book = current_page()/numpages; 
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
			}
			var orientation = mobile_mode ? (innerWidth>innerHeight ? 'p':'l') : undefined;
			if (mobile_orientation !== orientation) {
				mobile_orientation = orientation;
				$body.toggleClass('orientation-p',orientation == 'p').toggleClass('orientation-l',orientation=='l');
			}

		}//set_mobile_mode

		// turnjs start event 
		function animation_start(event, pageObject, corner) {
			if (CONFIG["prevent_corner_peels_on_mobile_to_allow_scrolling"] && mobile_mode && mobile_orientation == 'p' && corner && corner[1]==direction[0]) {
				event.preventDefault();
				return false;
			}
			autofit_text();
		}

		// turnjs end event 
		function animation_end(event, pageObject, corner) {
		}

		// turnjs turning event - when starting to animate turning to a specific page
		function turning(event, page, view) {
			turn_count ++;
			clearTimeout(show_peel_corner_TO);
			if (pause_turn_events){
				return;
			}
			if (is_page_skip_me(page)){
				event.preventDefault();
				go_to_page(page);
			} else {
				if (page > numpages - cover_pages_after)
					return; // page out of range, a cover page, or is already loaded
				var range = $book.turn('range', page);
				for (var i = range[0]; i<=range[1]; i++){
					load_page(i);
				}
				pages_depth_turning();
			}

			if (CONFIG["page_sound"] && sound_active && turn_count > 1){ // dont play sound on first 'turning' event
				try{
					document.querySelector("#page_sound").play();
				}catch(e){
					console.error(e);
				}
			}

		}//turning

		// turnjs turned event - when a page has finished animation and is displayed
		function turned(event, page, view) {
			if (pause_turn_events){
				consolelog('pause_turn_events',page);
				return;
			}
			consolelog('turned',page);
			var visible_scrollable;
			for (var i = 0; i<=view.length; i++){
				if (handle_scrollable_pages(view[i])){
					visible_scrollable=1;
				}
				var page_element=$('.p'+view[i]);
				if (page_element[0]){
					page_turned(page,page_element);
				}
			}
			
			if (CONFIG["show_peel_corner"] && !visible_scrollable){
				// bug: showing peel disables the scrolling
				show_peel_corner();
			}
			$('#id-pagenum').val(page);
			music_handler();
			autofit_text();
			handle_scrollable_pages();
			tb_updateState();
			handle_pushstate(page);
			handle_title();
			show_bookmarks();
		}//turned

		// event to run when book is ready (called by build_book)
		function init($book){
			page_turned(0,$('.p0'));
			page_turned(0,$('.p1'));
			page_turned(0,$('.p2'));
			page_turned(0,$('.p3'));
		}

		function autofit_text($elements){
			if (CONFIG["autofit_text"]){
				if (autofit_text_running)
					return;
				autofit_text_running=1;
				var $visible = $(".page_content:visible");
				$elements = $elements || $visible;
				var w = $visible.width(),h=$visible.height();
				if (w&&h){
					$elements.each(function(){
						var $container=$(this),
							$content=$container.children(),
							$imgs=$content.find('img');
						$content.width(w).height(h);

						try{
							textFit($content.toArray(),CONFIG["textfit_options"]);
							$imgs.load(function(){
								textFit($content.toArray(),CONFIG["textfit_options"]);
							});
						}catch(e){
							console.error(e);
						}
					});
				}
				autofit_text_running=0;
			}
		}

		// turnjs resize event - redetect mobile state, and reset the book size
		function resize(){
			set_mobile_mode();
			set_display_mode();
			var width = $size_parent.width() - pages_depth_width;
			var height = $size_parent.height();
			$book_parent.width(width);
			handle_pages_depth();
			$book.turn("size",width,height);
			autofit_text();
			handle_scrollable_pages();
			show_bookmarks();
		}

		// make changes to a content page
		function process_page(page,page_element,page_content,title){
			if (!page_element.runonce){
				page_element.runonce = 1;
			}//runonce
			
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

			var music = get_music_iframe(page,page_element,page_content,title);
			if (music){
				page_element.find('.page_content>div').append(music);
			}

		}// process_page

		function get_music_iframe(page,page_element,page_content,title){
			var trimrx = /\.[^\.]*$|_|-|%20| |\s+$|^\s+/g;
			var book_entry = book_list[page_element.parent().attr('i')];
			if (book_entry){
				for (var i in music_list){
					if (music_list[i].name && music_list[i].name.replace(trimrx,'')==book_entry.name.replace(trimrx,'')){
						return $('<div class="music_pop"><iframe src="https://drive.google.com/file/d/'+music_list[i].id+'/preview" i="'+i+'"></iframe></div>');
					}
				}
			}
		}

		// bind events to a page after it has turned
		function page_turned(page,page_element){
			if (!page_element[0])
				return;
			if (page_element[0].className.indexOf('index_page')>=0){
				page_element.find('.idx_row').off().click(index_row_click);
			}
			
			if (window.page_turned_hook)
				window.page_turned_hook(page, page_element);
		}// page_turned

		// load a specific page number using ajax
		function load_page(page) {
			if (page<=cover_pages_before || page>numpages - cover_pages_after || loaded_pages[page]){
				return; // page out of range, a cover page, or is already loaded
			}

			if (CONFIG["show_page_number"]){
				$('.p' + page + ' .page_number').html(page - cover_pages_before);
			}

			var index = page - cover_pages_before - 1; // index is zero-based, page is one-based
			if (book_list[index] && book_list[index].id) {
				loaded_pages[page]=1; // do not load same page twice

				// Get the data for that page
				var url = 'ajax.php?f=content&id=' + book_list[index].id + '&modifiedTime=' + book_list[index].modifiedTime;
				// p.s: no need for cfg parameter here
				ajax(url,function(data) {
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
					element.find('.print_footer').html(location.href+' ' + document.title);
					$('#flipbook .p'+page).empty().append(element);

					process_page(page,element,page_content,title);
					autofit_text();
					handle_scrollable_pages();
					
				});
			}
		}// load page

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
			page = page || current_page();
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
				consolelog('ajax cached',url);
				callback(ajax_cache[url]);
			} else {
				$.ajax({
					url: url,
					success:function(msg){
						var data;
						try{
							data = JSON.parse(msg);
						}catch(e){
							console.log("AJAX Parse Error: ", msg, url);
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
						consolelog('ajax complete',url,arguments);
					}
				});
			}
		}

		function consolelog(){
			if (CONFIG["console_debug"]){
				try{
					console.log.apply(window,arguments);
				}catch(e){
					try{
						console.log(arguments);
					}catch(e){
					}
				}
			}	
		}

		// bug on chrome-for-android: page is blank until orientation change.
		// workaround: use either setTimeout(..,0) or onLoad to run the main script
		setTimeout(function(){
			// for a full list of options see http://www.turnjs.com/#api
			// width, height, pages are added automatically on build_book()
			
			$('#id-q').prop('placeholder',CONFIG["text_search"]).val((CONFIG["remember_last_search"] && localStorage.getItem('turn_reader_q') ) || '' );
			$('#id-q').keypress(function(e) {
				if(e.which == 13) {
					$('#tb-item-nav-go').click();
				}
			});
			$('#q-clear').click(function(){
				$('#id-q').val('');
			});

			$('#id-pagenum').change(function(){
				var n = 1*this.value;
				if (n>0 && n<numpages){
					go_to_page(n);
				} else {
					this.value='?';
				}
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
						relevant_pages = numpages - pages_offset - cover_pages_after - 1;
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
						go_to_page(Math.min(numpages - cover_pages_after,pages_depth_tooltip_page + cover_pages_before)); // i don't understant why the +1 is necessary, but it is :-/
					}
				})
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

			// load initial book by the last or default query
			if (CONFIG["search_or_filter"] == 'search'){
				// load entire book, then handle search
				load_book('',handle_search);
			} else {
				// load only search results
				handle_search();
			}
			load_music_files();
			load_bookmarks();
		},1); // setTimeout main function
