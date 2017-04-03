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

	'print': {
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

	'sound': {
		icon: 'fa',
		icon_inactive: 'fa-volume-off',
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
		icon_inactive: 'line-through',
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
				item.$wrapper = $('<div id="addthis_container" class="addthis_inline_share_toolbox">').hide().appendTo($body);
				$body.append('<script src="https://s7.addthis.com/js/300/addthis_widget.js' + CONFIG["addthis_code"] + '"></script>');
			}
		},
		f: function(item) {
			console.log(item);
			item.currentToggleState = !item.currentToggleState;
			if (item.currentToggleState) {
				var rect = item.$el[0].getBoundingClientRect();
				var id = item.$nav[0].id;
				var halfwidth = item.$wrapper.width() / 2;
				item.$wrapper.css({
					position: 'fixed',
					left: id == 'tb-right' ? 'auto' : id == 'tb-left' ? rect.right : (rect.left - halfwidth),
					right: id == 'tb-right' ? rect.width : 'auto',
					top: id == 'tb-bottom' ? 'auto' : id == 'tb-top' ? rect.height : rect.top,
					bottom: id == 'tb-bottom' ? rect.height : 'auto'
				});
			}
			item.$wrapper.fadeToggle(item.currentToggleState);
		},
		toggle: true,
		active: function(item) {
			return item.currentToggleState;
		},
		visible: function(item) {
			return item.$wrapper;
		},
	},


	/////////
	// SEARCH 
	/////////

	'nav-q': {
		html: '<input id="id-q" placeholder="" type="text" class="form-control search-query" />',
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

	'nav-pagenum': {
		cls: 'tb-padding',
		f: function(item) {
			bootbox.prompt({
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