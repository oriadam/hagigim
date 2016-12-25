if (!Element.prototype.remove) Element.prototype.remove = function() {
	this.parentNode && this.parentNode.removeChild(this);
}

if (!Element.prototype.matches) Element.prototype.matches = Element.prototype.msMatchesSelector;
if (!Element.prototype.closest) Element.prototype.closest = function(selector) {
	var el = this;
	while (el) {
		if (el.matches(selector)) {
			return el;
		}
		el = el.parentElement;
	}
};


Array.prototype.last = function() {
	return this[this.length - 1];
	// undefined is returned for array[-1]
}

Array.prototype.first = function() {
	return this[0];
}


NodeList.prototype.forEach = NodeList.prototype.forEach || Array.prototype.forEach;
NodeList.prototype.last = NodeList.prototype.last || Array.prototype.last;
NodeList.prototype.first = NodeList.prototype.first || Array.prototype.first;
NodeList.prototype.lastIndexOf = NodeList.prototype.lastIndexOf || Array.prototype.lastIndexOf;
NodeList.prototype.indexOf = NodeList.prototype.indexOf || Array.prototype.indexOf;
NodeList.prototype.some = NodeList.prototype.some || Array.prototype.some;
NodeList.prototype.every = NodeList.prototype.every || Array.prototype.every;


if (typeof jQuery == 'function' && jQuery.fn)
	jQuery.fn.extend({
		onAndNow: function(events, func) {
			return this.on(events, func).each(func);
		}
	});
