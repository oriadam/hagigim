// custom hook functions

function process_page_hook(page, page_element, page_content, title) {
	// allow changing the current visible page
	if (page_content[0].innerHTML) {
		page_content[0].innerHTML = page_content[0].innerHTML
			.replace(/\bdir="rtl"/g, '')
			.replace(/\bid="[^"]+"/g, '')
			.replace(/\bclass="\s*first_content_line"/g, 'clas="first_content_line"')
			.replace(/\bclass="[^"]+"/g, '')
			.replace(/\bclas="/g, 'class="')
			.replace(/&nbsp;/g, ' ')
			.replace(/ +/g, ' ')
			.replace(/<h\d\s*>(.*?)<\/h\d>/g, '<p>$1</p>')
			.replace(/<[uo]l\s*>(.*?)<\/[uo]l>/g, '$1')
			.replace(/<li\s*>(.*?)<\/li>/g, '<p class="li">$1</p>')
			.replace(/<span\s*>(.*?)<\/span>/g, '$1')
			.replace(/<p\s*>(.*?)<\/p>/g, '$1<br>')
			.replace(/(<br>)+/g, '<br>')
			.replace(/(<br>|<p><\/p>)+(.*first_content_line)/g, '$2')
	}
}

function process_tb_hook() {
	// allow changing the tb_items array
}