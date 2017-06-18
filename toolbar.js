var icon_forward = CONFIG["rtl"] ? 'backward' : 'forward';
var icon_backward = CONFIG["rtl"] ? 'forward' : 'backward';
var tb_items = {};

var tb_items_config = {
	'fullscreen': {
		icon: 'fa-external-link-square',
		f: function(item) {
			// taken from: http://stackoverflow.com/a/7525760/3356679
			try {
				if (item.active && item.active(item))
					item.exitFullScreen.call(document);
				else
					item.enterFullScreen.call(item.elem);
			} catch (e) {
				console.error(e);
				bootbox.alert({
					message: CONFIG["text_hit_f11"],
					backdrop: true,
					closeButton: false,
				});
				item.visible = function(item) {
					return false;
				}
			}
			setTimeout(function() {
				item.updateState(item);
			}, 100);
		},
		// toggle: true, // TODO: detect fullscreen 
		visible: function(item) {
			return item.enterFullScreen && !$('body').is('.mobile'); // when there is no fullscreen option in browser, hide the toolbar item
		},
		active: function(item) {
			return item.elem === document.body && (document.currentFullScreenElement || document.fullscreenElement || document.webkitCurrentFullScreenElement || document.webkitFullscreenElement || document.mozCurrentFullScreenElement || document.mozFullscreenElement || document.msCurrentFullScreenElement || document.msFullscreenElement);
		},
		init: function(item) {
			item.elem = document.querySelector(CONFIG["fullscreen_selector"]);
			item.enterFullScreen = item.elem.requestFullScreen || item.elem.webkitRequestFullScreen || item.elem.mozRequestFullScreen || item.elem.msRequestFullScreen;
			item.exitFullScreen = document.exitFullScreen || document.cancelFullScreen || document.webkitExitFullScreen || document.webkitCancelFullScreen || document.mozExitFullScreen || document.mozCancelFullScreen || document.msExitFullScreen || document.msCancelFullScreen;
		},
	},

	'print1': {
		icon: 'fa-print',
		f: function(item) {
			var $print_container = $('#print_container').empty();
			var views = $book.turn('view');
			var added;
			for (var i = 0; i < views.length; i++) {
				var $page_content = $('.p' + views[i] + ' .page_content');
				if ($page_content.length) {
					$page_content.clone().appendTo($print_container).prop('style', '');
					added = 1;
				}
			}
			if (added) {
				window.print();
			} else {
				bootbox.alert({
					message: CONFIG["text_nothing_to_print"],
					backdrop: true,
					closeButton: false,
				});
			}
		},
	},

	'print2': {
		icon: 'fa-print',
		f: function(item) {
			html2canvas(document.querySelector('#book_container'), {
				onrendered: function(canvas) 
				{  
					var dataUrl = canvas.toDataURL(); //attempt to save base64 string to server using this var  
					var windowContent = '<!DOCTYPE html>';
					windowContent += '<html>'
					windowContent += '<body>'
					windowContent += '<img src="' + dataUrl + '">';
					windowContent += '</body>';
					windowContent += '</html>';
					var printWin = window.open('','','width=340,height=260');
					printWin.document.open();
					printWin.document.write(windowContent);
					printWin.document.close();
					printWin.focus();
					printWin.print();
					printWin.close();
				}
			});
		},
	},

	'print0': {
		icon: 'fa-print',
		f: function(item) {
			window.print();
		},
	},

	'sound': {
		icon: 'fa',
		icon_inactive: 'fa-volume-off ' + (CONFIG["tb_diagonal_strikethrough"] ? 'tb_diagonal_strikethrough' : ''),
		icon_active: 'fa-volume-up',
		f: function(item) {
			sound_active = !sound_active;
			localStorage.setItem("sound_active", sound_active ? 1 : 0)
		},
		toggle: true,
		active: function(item) {
			return sound_active;
		},
		visible: function(item) {
			return CONFIG["page_sound"];
		},
	},

	'music': {
		icon: 'fa-music',
		icon_inactive: (CONFIG["tb_diagonal_strikethrough"] ? 'tb_diagonal_strikethrough' : ''),
		f: function(item) {
			music_active = !music_active;
			localStorage.setItem("music_active", music_active ? 1 : 0)
			music_handler();
		},
		toggle: true,
		active: function(item) {
			return music_active;
		},
		visible: function(item) {
			return CONFIG["music_url"];
		},
	},

	'zoom': {
		icon: 'fa-search-plus',
		f: function(item) {
			if (zoom_active) {
				$zoom_elem.panzoom("reset").panzoom("destroy");
				zoom_active = 0;
			} else {
				$zoom_elem.panzoom({
					minScale: 0.5,
					maxScale: 5.0,
					increment: 0.1,
				}).panzoom('zoom', true);
				$body.on('mousewheel.focal', function(ev) {
					ev.preventDefault();
					var delta = ev.delta || ev.originalEvent.wheelDelta;
					var zoomOut = delta ? delta < 0 : ev.originalEvent.deltaY > 0;
					$zoom_elem.panzoom('zoom', zoomOut, {
						increment: 0.1,
						animate: false,
						focal: ev
					});
				});
				zoom_active = 1;
			}
		},
		toggle: true,
		active: function(item) {
			return zoom_active;
		},
	},

	'textselect': {
		icon: 'fa-text-width',
		f: function(item) {
			$body.toggleClass('textselect');
		},
		toggle: true,
		active: function(item) {
			return $body.is('.textselect');
		},
	},

	'homepage': {
		icon: 'fa-home',
		f: function(item) {
			window.open(CONFIG["url_homepage"]);
		},
		visible: function(item) {
			return CONFIG["url_homepage"]; // when there is no homepage url set, hide the toolbar item
		},
	},

	'addthis': {
		icon: 'fa-share-alt',
		init: function(item) {
			if (CONFIG["addthis_code"]) {
				//$.getScript('https://s7.addthis.com/js/300/addthis_widget.js' + CONFIG["addthis_code"] + '&async=1', function() {
				addthis.addEventListener('addthis.menu.share', function() {
					bootbox.hideAll();
				});
				//});
			}
		},
		f: function(item) {
			if ($('.modal-dialog:visible').length)
				return;
			var docs = current_pages_docs();
			if (!docs || !docs.length)
				return;
			var onHide = function() {
				console.log('onEscape');
				$('.addthis_inline_share_toolbox').appendTo('body');
				return true;
			}
			var onClick = function() {
				var $this = $(this);
				$('.share_doc_select_item').removeClass('btn-primary');
				$this.addClass('btn-primary');
				var doc = $this.data('doc');
				var new_url_search = '?id=' + doc.id;
				document.title = doc.name;
				if (location.search != new_url_search)
					window.history.pushState({ "id": doc.id }, "", new_url_search);
				var img = 'https://drive.google.com/thumbnail?authuser=0&sz=w320&id=' + doc.id;
				var anchor = document.createElement('a');
				anchor.href = new_url_search;
				addthis_share = {
					url: anchor.href,
					title: doc.name,
					media: img,
				};
				$('.addthis_inline_share_toolbox').remove();
				$('.addthis_wrapper').append('<div class="addthis_inline_share_toolbox">');
				addthis.layers.refresh();
			};
			var wrapper = $('<div>');
			var container = $('<div class="share_doc_select_container">').appendTo(wrapper);
			for (var i = 0; i < docs.length; i++) {
				var item = $('<div class="share_doc_select_item btn btn-default">')
					.attr('data-doc', JSON.stringify(docs[i]))
					.text(docs[i].name)
					.click(onClick);
				container.append(item);
			}
			wrapper.append('<div class="addthis_wrapper">');
			item.bootbox_dialog = bootbox.dialog({
				title: CONFIG["text_share_dialog"],
				message: wrapper[0],
				backdrop: true,
				closeButton: true,
				onEscape: true
			}).on('hidden.bs.modal', onHide);
			if (docs.length == 1)
				$('.share_doc_select_item').click();
		},
		visible: function(item) {
			return CONFIG["addthis_code"];
		},
	},

	'bookmark': {
		icon: 'fa',
		icon_inactive: 'fa-bookmark-o',
		icon_active: 'fa-bookmark',
		f: function(item) {
			toggle_bookmark();
		},
		toggle: true,
		active: function(item) {
			return is_bookmarked();
		},
	},

	/////////
	// SEARCH 
	/////////

	'nav-q': {
		html: '<span id="q-wrap"><input id="id-q" placeholder="" type="text" class="form-control search-query" /><span id="q-clear"></span></span>',
	},

	'nav-go': {
		icon: 'fa-search',
		f: function(item) {
			handle_search();
		},
	},

	'nav-clear': {
		icon: 'fa-ban',
		f: function(item) {
			$('#id-q').val('');
			handle_search();
		},
		enabled: function(item) {
			return !!$('#id-q').val();
		},
		visible: function(item) {
			return !$('body').is('.mobile');
		}
	},

	'nav-found': {
		cls: 'tb-padding',
		enabled: function(item) {
			return !!item.$el.html();
		}
	},

	'nav-first': {
		icon: 'fa-fast-' + icon_backward,
		f: function(item) {
			search_next_prev('first');
		},
		enabled: function(item) {
			return search_results.length ? search_position > 0 : current_page() > 1;
		},
		visible: function(item) {
			return !$('body').is('.mobile');
		}
	},

	'nav-prev': {
		icon: 'fa-step-' + icon_backward,
		f: function(item) {
			search_next_prev('previous');
		},
		enabled: function(item) {
			return search_results.length ? search_position > 0 : current_page() > 1;
		},
	},

	'nav-next': {
		icon: 'fa-step-' + icon_forward,
		f: function(item) {
			search_next_prev('next');
		},
		enabled: function(item) {
			return search_results.length ? search_position < search_results.length - 1 : current_page() < numpages - 1;
		},
	},

	'nav-last': {
		icon: 'fa-fast-' + icon_forward,
		f: function(item) {
			search_next_prev('last');
		},
		enabled: function(item) {
			return search_results.length ? search_position < search_results.length - 1 : current_page() < numpages - 1;
		},
		visible: function(item) {
			return !$('body').is('.mobile');
		}
	},
	
	'nav-page-first': {
		icon: 'fa-fast-' + icon_backward,
		f: function(item) {
			search_next_prev('first','p');
		},
		enabled: function(item) {
			return current_page() > 1;
		},
		visible: function(item) {
			return !$('body').is('.mobile');
		}
	},

	'nav-page-prev': {
		icon: 'fa-step-' + icon_backward,
		f: function(item) {
			search_next_prev('previous','p');
		},
		enabled: function(item) {
			return current_page() > 1;
		},
	},

	'nav-page-next': {
		icon: 'fa-step-' + icon_forward,
		f: function(item) {
			search_next_prev('next','p');
		},
		enabled: function(item) {
			return current_page() < numpages - 1;
		},
	},

	'nav-page-last': {
		icon: 'fa-fast-' + icon_forward,
		f: function(item) {
			search_next_prev('last','p');
		},
		enabled: function(item) {
			return current_page() < numpages - 1;
		},
		visible: function(item) {
			return !$('body').is('.mobile');
		}
	},

	'nav-result-prev': {
		icon: 'fa-arrow-circle-up',
		f: function(item) {
			search_next_prev('previous','s');
		},
		enabled: function(item) {
			return search_results.length && search_position > 0;
		},
	},

	'nav-result-next': {
		icon: 'fa-arrow-circle-down',
		f: function(item) {
			search_next_prev('next','s');
		},
		enabled: function(item) {
			return search_results.length && search_position < search_results.length - 1;
		},
	},

	'nav-pagenum': {
		cls: 'tb-padding',
		f: function(item) {
			bootbox.prompt({
				size: 'small',
				className: 'pagenum-dialog',
				title: CONFIG["text_enter_pagenum"],
				inputType: 'number',
				backdrop: true,
				closeButton: false,
				callback: function(result) {
					var val = parseInt(result);
					if (val)
						go_to_pagenum(val);
				}
			});
		},
		update: function(item) {
			var current = current_pagenum && total_pagenum && current_pagenum();
			if (current) {
				item.$el.html(CONFIG["text_pagenum_in"].replace('%s1', current).replace('%s2', total_pagenum()));
			} else {
				item.$el.html(CONFIG["text_pagenum_none"]);
			}
		},
	}
};

if (typeof process_tb_hook == 'function')
	process_tb_hook();

function tb_generate(list, id, cls) {
	if (typeof list == 'string')
		list = list.split(',');

	var $nav = $('<nav class="tb-nav nav nav-pills">').attr('id', 'tb-' + id).addClass(cls);
	var $ul = $('<ul class="input-append form-inline form-group">').attr('id', 'tb-ul-' + id).appendTo($nav);
	list.forEach(function(k) {
		var item, title, $li, $span, $inside_span, $icon;
		if (!k) return;
		item = $.extend({}, tb_items_config[k]);
		tb_items[k] = item;
		if (!item) {
			console.log('ERROR: BAD TOOLBAR ITEM "' + k + '" in ' + id);
			return;
		}

		title = CONFIG['text_tb_item_' + k];
		$li = $('<li class="tb-li">').attr('id', 'tb-li-' + k);
		if (title)
			$li.attr('title', title);
		if (item.cls)
			$li.addClass(item.cls);
		item.$li = $li;
		item.$nav = $nav;
		item.$ul = $ul;
		$span = $('<span class="btn btn-primary form-control tb-item">').attr('id', 'tb-item-' + k);
		item.$el = $span;
		if (item.f)
			$span.click(function() {
				item.f(item)
			});
		$inside_span;
		if (item.html)
			$inside_span = $(item.html);
		else if (item.href)
			$inside_span = $('<a class="toolbar-item-link">').attr('href', item.href);
		if (item.icon) {
			$icon = $('<i class="tb-icon ' + (/\bfa\b/.test(item.icon) ? 'fa ' : '') + item.icon + '">');
			if ($inside_span)
				$inside_span.append($icon);
			else
				$inside_span = $icon;
		} else {
			$icon = $('');
		}
		if (item.init)
			$(window).on('load', function() {
				item.init(item);
			});
		item.updateState = function(item) {
			if (item.active) {
				var active = !!item.active(item);
				if (item.icon_inactive)
					$icon.toggleClass(item.icon_inactive, !active);
				if (item.icon_active)
					$icon.toggleClass(item.icon_active, active);
				$span.toggleClass('active', active);
			}
			if (item.visible) {
				$li.toggle(!!item.visible(item));
			}
			if (item.enabled) {
				$span.toggleClass('disabled', !item.enabled(item));
			}
			if (item.update) {
				item.update(item);
			}
		};
		$ul.append($li.append($span.append($inside_span)));
	});
	$nav.click(tb_updateState);
	return $nav;
}

function tb_updateState() {
	Object.keys(tb_items).forEach(function(k) {
		tb_items[k].updateState && tb_items[k].updateState(tb_items[k]);
	});
	$('.tb-v').each(function() {
		this.style.top = 'calc( 50% - ' + ($(this).height() / 2) + 'px )';
	});
	$('.tb-h').each(function() {
		this.style.left = 'calc( 50% - ' + ($(this).width() / 2) + 'px )';
	});
}

$(window).on('resize', tb_updateState);