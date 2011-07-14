
// @param	string
// @return	number

String.prototype.startsWith = function(str){
	return this.indexOf(str) === 0;			// .indexOf() is case sensitive
};


var	keyboard = function(e){

	if (keyboardCache){
		return null;
	}

	switch (e.keyCode){	// keycode table, http://unixpapa.com/js/key.html
		case 0:
		case 16:
		case 17:
		case 18:
		case 91:
		case 92:
		case 93:
		case 219:
		case 220:
		case 224:							// command, branded keys
			keyboardCache = e.keyCode;			// being lazy with setTimeout,
			window.setTimeout('keyboardCache = null', 400);
			break;								// but accurate in many cases
		case 27:							// esc

			if (app.entries){					// reset cached focus
				app.entries.lastly = null;
			}

			document.activeElement.blur();
			break;
		case 65:							// a
			redirect(document.getElementById('pots'));
			break;
		case 72:							// h
			redirect(document.getElementById('prev'));
			break;
		case 73:							// i
			redirect(document.getElementById('home'));
			break;
		case 74:							// j

			if (app.pageType === 'index'){
				shiftFocus(1);
			}

			break;
		case 75:							// k

			if (app.pageType === 'index'){
				shiftFocus(-1);
			}

			break;
		case 76:							// l
			redirect(document.getElementById('next'));
			break;
		case 79:							// o
			redirect(document.activeElement);
			break;
	}

},
	keyboardCache = null;


// @param	number or object
// @return	number or redirect

var redirect = function(el){

	if (el === 1){							// = "open (o)" link

		if (app.entries.lastly === null){
			return null

		} else {
			el = app.entries[app.entries.lastly];
		}
	}

	if (!el || !el.href){
		return null;
	}

	el = el.href;

	if (el.search(/^(?:https?\:\/\/\S*)/i) === -1){
		el = !el.startsWith('/')
			? 'http://' + self.location.host + '/' + el
			: 'http://' + self.location.host + el;
	}

	self.location.href = el;
};


// @param	number
// @param	number

var shiftFocus = function(n, click){
	var current = app.entries.lastly;

	if (current === null){

		if (app.entries.length === 1){
			current = 0;

		} else {
			current = n === 1
				? 0
				: app.entries.length - 1;
		}

	} else if (	(	!click
				&&	document.activeElement !== app.entries[current]
				)
			|	(	click
				&&	app.entries.time < new Date().getTime()
				)
	){										// re-focus on cached focus
		current = current;

	} else if (current === 0){

		if (app.entries.length !== 1){
			current = n === 1
				? current + n
				: app.entries.length - 1;
		}

	} else if (current === app.entries.length - 1){
		current = n === 1
			? 0
			: current + n;
	} else {
		current+= n;
	}

	app.entries.lastly = current;
	app.entries.tab = null;
	app.entries[current].focus();
};


var focused = function(e){
	var	entries,
		i,
		l,
		target;
	e.stopPropagation();

	if (app.entries.tab){
		entries = app.entries;
		i = 0;
		l = entries.length + 1;
		target = e.target;

		while (--l){

			if (entries[i] === target){
				app.entries.lastly = i;
				break;

			} else {
				++i;
			}
		}

	} else {
		app.entries.tab = 1;
	}
};


var blurred = function(e){
	e.stopPropagation();
	app.entries.time = new Date().getTime() + 400;
};


// @param	number
// @param	number
// @param	string

var flimflam = function(page, total, path){
	var	a,
		f = document.createDocumentFragment(),
		i = 1,
		pagination = document.getElementById('paginate'),
		span;

	++total;
	while (--total){

		if (i === page){
			span = document.createElement('span');
			span.innerHTML = i;				// html: &nbsp; or &#160; unicode: \U00A0
			f.appendChild(span);
			f.appendChild(document.createTextNode(' '));

		} else {
			a = document.createElement('a');
			a.innerHTML = i;
			a.href = path + i + '/';
			f.appendChild(a);
			f.appendChild(document.createTextNode(' '));
		}

		++i;
	}

	pagination.innerHTML = '';
	pagination.appendChild(f);
};


var domready = function(){
	var	entries,
		i,
		l,
		pageType = app.pageType = document.body.className;
	document.removeEventListener('DomContentLoaded', domready, false);

	if (pageType === 'index'){
		entries = app.entries = document.getElementById(pageType).getElementsByTagName('a');
		i = 0;
		l = entries.length + 1;

		while (--l){
			entries[i].addEventListener('blur', blurred, false);
			entries[i].addEventListener('focus', focused, false);
			++i;
		}

		app.entries.lastly =
		app.entries.time = null,
		app.entries.tab = 1;
	}

	document.addEventListener('keydown', keyboard, false);
},
	app = {};


document.addEventListener('DOMContentLoaded', domready, false);
