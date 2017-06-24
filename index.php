<?php
global $CONFIG;
require_once "config.php";
// require_once "reader.lib.php";

?>
<html>
<head>
	<title><?=htmlentities($CONFIG["text_window_title"])?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
	<?php if (!empty($CONFIG["generate_og_image"])) { ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>
	<meta id="og_title" />
	<meta id="og_image" />
	<?php } ?>
	<?php if ($CONFIG["addthis_code"]) { ?>
		<script src="https://s7.addthis.com/js/300/addthis_widget.js<?=$CONFIG["addthis_code"]?>"></script>
	<?php } ?>
	<!-- turnjs does not support jquery 3, so use 2 -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script> 
	<!-- turnjs does not support jquery 3, so use 2 -->
	<script src="polyfill.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/0.9.0/purify.min.js"></script>	
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.panzoom/3.2.2/jquery.panzoom.min.js"></script>
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>
	<!-- bootbox dialogs: http://bootboxjs.com/examples.html -->
	<script src="turnjs/turn.min.js" hotlink-src="http://www.turnjs.com/lib/turn.min.js"></script>
	<link href="style.css" rel="stylesheet">
	<?php if ($CONFIG["bootswatch_css"]) { ?>
	<link href="https://cdn.jsdelivr.net/bootswatch/3.3.7/<?=$CONFIG["bootswatch_css"]?>/bootstrap.min.css" rel="stylesheet">
	<?php } ?>
	<?php if ($CONFIG["autofit_text"]) { ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/textfit/2.3.1/textFit.min.js"></script>
	<?php } elseif ($CONFIG["font_size"]) { ?>
	<style>.page_content { font-size: <?=$CONFIG["font_size"]?>px;</style>
	<?php } ?>

	<style>
		.bookmark_ribbon.r {
			border-color: <?=$CONFIG["bookmark_color"]?> transparent <?=$CONFIG["bookmark_color"]?> <?=$CONFIG["bookmark_color"]?>;
		}

		.bookmark_ribbon.l {
			border-color: <?=$CONFIG["bookmark_color"]?> <?=$CONFIG["bookmark_color"]?> <?=$CONFIG["bookmark_color"]?> transparent;
		}

		<?php if ($CONFIG["generate_index"]) { ?>
		.idx_row {
			font-size:<?=(1/$CONFIG["index_lines_per_page"])*60?>vh;
		}
		<?php } ?>
		<?php if ($CONFIG["max_book_width"]) { ?>
		.book_container_width {
		    max-width: <?=$CONFIG["max_book_width"]?>px;
		}
		<?php } ?>

		.display-double #flipbook .even .page_top_content:after {
			content: "<?=$CONFIG["text_top_even_pages"]?>";
		}

		.display-double #flipbook .odd .page_top_content:after {
			content: "<?=$CONFIG["text_top_odd_pages"]?>";
		}

		.display-single #flipbook .page_top_content:after {
			content: "<?=$CONFIG["text_top_single_pages"]?>";
		}
	</style>
	<link href="custom/style-<?=$CUSTOM_CONFIG_NAME?>.css" rel="stylesheet">
	<script src="custom/script-<?=$CUSTOM_CONFIG_NAME?>.js"></script>
	<?=$CONFIG["html_head"]?>


	<template id="tmpl_empty_page">
	<div class="page_outer_wrap">
		<div class="empty_page">
			<div class="empty_page_content"></div>
		</div>
	</div>
	</template>
	<template id="tmpl_index_page">
	<div class="index_page_outer_wrap">
		<div class="index_page">
			<ul class="idx_list"></ul>
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
					<div class="media-print print_footer"></div>
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




</head>
<body id="body" class="<?=$CONFIG["rtl"]? 'rtl':'ltr'?>">
	<?=$CONFIG["html_body"]?>

	<div id="zoom_container">
		<div id="book_container" class="book_container_width">
			<div id="pages_depth_l" class="pages_depth_element"></div>
			<div id="flipbook_parent">
				<div id="flipbook"></div>
			</div>
			<div id="pages_depth_r" class="pages_depth_element"></div>
		</div>
		<?php
			if (is_firefox()){
		?>
		
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
		<?php
		}
		?>
	</div>
	<div id="bookmarks">
		<div id="curpage_bookmark" class="bookmark_ribbon r"></div>
	</div>

	<?php if ($CONFIG["page_sound"]){ ?>
		<audio id="page_sound"><source src="page.ogg" type="audio/ogg"><source src="page.wav" type="audio/wav"></audio>
	<?php } ?>
	<audio id="music_tag"></audio>

	<script>
		var CONFIG = <?=json_encode(config_for_js())?>;
		var CUSTOM_CONFIG_NAME = "<?=$CUSTOM_CONFIG_NAME?>";
		CONFIG["textfit_options"]["minFontSize"] = CONFIG["autofit_text_min"];
		CONFIG["textfit_options"]["maxFontSize"] = CONFIG["autofit_text_max"];

		var $body = $('body');
		var $book = $('#flipbook'); // book jQuery element
		var $book_parent = $('#flipbook_parent');
		var $size_parent = $('#book_container');
		var $zoom_elem = $('#zoom_container');
		var zoom_elem = $zoom_elem[0];
		var $music_tag = $("#music_tag");
		var build_book_time = Date.now(); // remember when book was created, for reasons
		var turn_count = 0;
		var numpages; // number of pages (including 4 cover pages)
		var book_list; // array of pages as json object of id,content,name,filename. note that array index = page - 3 (because it starts with 0 and does not include the cover pages)
		var index_pages; // array of index pages (if index was generated)
		var index_numpages;		
		var ajax_cache = {}; // local cache of ajax requests - used by ajax() function
		var loaded_pages = []; // remember which pages were already loaded (or currently loading)
		var search_results = []; // array of search results (relevant on "search" mode, irrelevant on "filter" mode)
		var search_position = 0; // position in current search results (relevant on "search" mode, irrelevant on "filter" mode)
		var music_list = []; // list of music files that may fit with the currently viewed music file
		var cover_pages_before,cover_pages_after;
		var page_content_scroll_hide_page_number;
		var turn_display_mode; // 'single' or 'double' pages view. intentionally start as undefined
		var direction = CONFIG["rtl"] ? 'rtl' : 'ltr';
		var search_q; // search query
		var mobile_mode; // mobile device mode true/false. intentionally start as undefined
		var mobile_orientation; // 'p' for portrait, 'l' for landscape. intentionally start as undefined
		var pages_depth_width = CONFIG["pages_depth"] ? undefined : 0; // width of pages_depth elements together. 0 for off. intentionally start as undefined
		var pages_depth_height;
		var $pages_depth_tooltip;
		var pages_depth_tooltip_page;
		var show_peel_corner_TO;
		var pause_turn_events;
		var search_results_clicked;
		var zoom_active = false;
		var sound_active = localStorage.getItem("sound_active") === null ? true : localStorage.getItem("sound_active")=="1";
		var music_active = localStorage.getItem("music_active") === null ? CONFIG["music_default"] : localStorage.getItem("music_active")=="1";
		var current_music_url = CONFIG["music_url"],last_music_url;
		var autofit_text_running;
		var enable_pushstate;
		var bookmarks;
</script>
<script src="toolbar.js"></script>
<script src="logic.js"></script>

	<div id="print_container">
	
	<?=$CONFIG["html_footer"]?>

</body>
</html>