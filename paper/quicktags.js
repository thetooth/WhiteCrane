// JS QuickTags version 1.3.1 Copyright (c) 2002-2008 Alex King
var edButtons = new Array();
var edLinks = new Array();
var edOpenTags = new Array();

function edButton(id, display, tagStart, tagEnd, access, open) {
	this.id = id;				// used to name the toolbar button
	this.display = display;		// label on button
	this.tagStart = tagStart; 	// open tag
	this.tagEnd = tagEnd;		// close tag
	this.access = access;			// set to -1 if tag does not need to be closed
	this.open = open;			// set to -1 if tag does not need to be closed
}

edButtons.push(
	new edButton(
		'ed_h1'
		,'H1'
		,'====='
		,'====='
		,'h1'
	)
);

edButtons.push(
	new edButton(
		'ed_h2'
		,'H2'
		,'===='
		,'===='
		,'h2'
	)
);

edButtons.push(
	new edButton(
		'ed_h3'
		,'H3'
		,'==='
		,'==='
		,'h3'
	)
);

edButtons.push(
	new edButton(
		'ed_bold'
		,'B'
		,'[b]'
		,'[/b]'
		,'b'
	)
);

edButtons.push(
	new edButton(
		'ed_italic'
		,'I'
		,'[i]'
		,'[/i]'
		,'i'
	)
);

edButtons.push(
	new edButton(
		'ed_underline'
		,'U'
		,'[u]'
		,'[/u]'
		,'u'
	)
);

edButtons.push(
	new edButton(
		'ed_linex'
		,'S'
		,'[s]'
		,'[/s]'
		,'s'
	)
);

edButtons.push(
	new edButton(
		'ed_code'
		,'Code'
		,'[code]'
		,'[/code]'
		,'code'
	)
);

edButtons.push(
	new edButton(
		'ed_ext_link'
		,'Ext. Link'
		,''
		,''
		,-1
	)
); // special case

edButtons.push(
	new edButton(
		'ed_img'
		,'Image'
		,''
		,''
		,'m'
		,-1
	)
); // special case

var extendedStart = edButtons.length;

// below here are the extended buttons

function edShowButton(which, button, i) {
	if (button.access) {
		var accesskey = ' accesskey = "' + button.access + '"'
	}
	else {
		var accesskey = '';
	}
	switch (button.id) {
		case 'ed_img':
			document.write('<a href="#" id="' + button.id + '_' + which + '" ' + accesskey + ' class="ed_button" onclick="edInsertImage(\'' + which + '\');">' + button.display + '</a>');
			break;
		case 'ed_link':
			document.write('<a href="#" id="' + button.id + '_' + which + '" ' + accesskey + ' class="ed_button" onclick="edInsertLink(\'' + which + '\', ' + i + ');">' + button.display + '</a>');
			break;
		case 'ed_ext_link':
			document.write('<a href="#" id="' + button.id + '_' + which + '" ' + accesskey + ' class="ed_button" onclick="edInsertExtLink(\'' + which + '\', ' + i + ');">' + button.display + '</a>');
			break;
		case 'ed_footnote':
			document.write('<a href="#" id="' + button.id + '_' + which + '" ' + accesskey + ' class="ed_button" onclick="edInsertFootnote(\'' + which + '\');">' + button.display + '</a>');
			break;
		case 'ed_via':
			document.write('<a href="#" id="' + button.id + '_' + which + '" ' + accesskey + ' class="ed_button" onclick="edInsertVia(\'' + which + '\');">' + button.display + '</a>');
			break;
		default:
			document.write('<a href="#" id="' + button.id + '_' + which + '" ' + accesskey + ' class="ed_button" onclick="edInsertTag(\'' + which + '\', ' + i + ');">' + button.display + '</a>');
			break;
	}
}

function edAddTag(which, button) {
	if (edButtons[button].tagEnd != '') {
		edOpenTags[which][edOpenTags[which].length] = button;
		document.getElementById(edButtons[button].id + '_' + which).value = '/' + document.getElementById(edButtons[button].id + '_' + which).value;
	}
}

function edRemoveTag(which, button) {
	for (i = 0; i < edOpenTags[which].length; i++) {
		if (edOpenTags[which][i] == button) {
			edOpenTags[which].splice(i, 1);
			document.getElementById(edButtons[button].id + '_' + which).value = document.getElementById(edButtons[button].id + '_' + which).value.replace('/', '');
		}
	}
}

function edCheckOpenTags(which, button) {
	var tag = 0;
	for (i = 0; i < edOpenTags[which].length; i++) {
		if (edOpenTags[which][i] == button) {
			tag++;
		}
	}
	if (tag > 0) {
		return true; // tag found
	}
	else {
		return false; // tag not found
	}
}	

function edCloseAllTags(which) {
	var count = edOpenTags[which].length;
	for (o = 0; o < count; o++) {
		edInsertTag(which, edOpenTags[which][edOpenTags[which].length - 1]);
	}
}

function edQuickLink(i, thisSelect) {
	if (i > -1) {
		var newWin = '';
		if (edLinks[i].newWin == 1) {
			newWin = ' target="_blank"';
		}
		var tempStr = '<a href="' + edLinks[i].URL + '"' + newWin + '>' 
		            + edLinks[i].display
		            + '</a>';
		thisSelect.selectedIndex = 0;
		edInsertContent(edCanvas, tempStr);
	}
	else {
		thisSelect.selectedIndex = 0;
	}
}

function edToolbar(which) {
	document.write('<div id="ed_toolbar_' + which + '"><span>');
	for (i = 0; i < extendedStart; i++) {
		edShowButton(which, edButtons[i], i);
	}
	if (edShowExtraCookie()) {
		document.write(
			'<a href="#" id="ed_close_' + which + '" class="ed_button" onclick="edCloseAllTags(\'' + which + '\');">[/&nbsp;]</a>'
		);
	}
	else {
		document.write(
			'<a href="#" id="ed_close_' + which + '" class="ed_button" onclick="edCloseAllTags(\'' + which + '\');">[/&nbsp;]</a>'
		);
	}
	for (i = extendedStart; i < edButtons.length; i++) {
		edShowButton(which, edButtons[i], i);
	}
	document.write('</span></div>');
    edOpenTags[which] = new Array();
}

function edShowExtra(which) {
	document.getElementById('ed_extra_show_' + which).style.visibility = 'hidden';
	document.getElementById('ed_extra_buttons_' + which).style.display = 'block';
	edSetCookie(
		'js_quicktags_extra'
		, 'show'
		, new Date("December 31, 2100")
	);
}

function edHideExtra(which) {
	document.getElementById('ed_extra_buttons_' + which).style.display = 'none';
	document.getElementById('ed_extra_show_' + which).style.visibility = 'visible';
	edSetCookie(
		'js_quicktags_extra'
		, 'hide'
		, new Date("December 31, 2100")
	);
}

// insertion code

function edInsertTag(which, i) {
    myField = document.getElementById(which);
	//IE support
	if (document.selection) {
		myField.focus();
	    sel = document.selection.createRange();
		if (sel.text.length > 0) {
			sel.text = edButtons[i].tagStart + sel.text + edButtons[i].tagEnd;
		}
		else {
			if (!edCheckOpenTags(which, i) || edButtons[i].tagEnd == '') {
				sel.text = edButtons[i].tagStart;
				edAddTag(which, i);
			}
			else {
				sel.text = edButtons[i].tagEnd;
				edRemoveTag(which, i);
			}
		}
		myField.focus();
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		var cursorPos = endPos;
		var scrollTop = myField.scrollTop;
		if (startPos != endPos) {
			myField.value = myField.value.substring(0, startPos)
			              + edButtons[i].tagStart
			              + myField.value.substring(startPos, endPos) 
			              + edButtons[i].tagEnd
			              + myField.value.substring(endPos, myField.value.length);
			cursorPos += edButtons[i].tagStart.length + edButtons[i].tagEnd.length;
		}
		else {
			if (!edCheckOpenTags(which, i) || edButtons[i].tagEnd == '') {
				myField.value = myField.value.substring(0, startPos) 
				              + edButtons[i].tagStart
				              + myField.value.substring(endPos, myField.value.length);
				edAddTag(which, i);
				cursorPos = startPos + edButtons[i].tagStart.length;
			}
			else {
				myField.value = myField.value.substring(0, startPos) 
				              + edButtons[i].tagEnd
				              + myField.value.substring(endPos, myField.value.length);
				edRemoveTag(which, i);
				cursorPos = startPos + edButtons[i].tagEnd.length;
			}
		}
		myField.focus();
		myField.selectionStart = cursorPos;
		myField.selectionEnd = cursorPos;
		myField.scrollTop = scrollTop;
	}
	else {
		if (!edCheckOpenTags(which, i) || edButtons[i].tagEnd == '') {
			myField.value += edButtons[i].tagStart;
			edAddTag(which, i);
		}
		else {
			myField.value += edButtons[i].tagEnd;
			edRemoveTag(which, i);
		}
		myField.focus();
	}
}

function edInsertContent(which, myValue) {
    myField = document.getElementById(which);
	//IE support
	if (document.selection) {
		myField.focus();
		sel = document.selection.createRange();
		sel.text = myValue;
		myField.focus();
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		var scrollTop = myField.scrollTop;
		myField.value = myField.value.substring(0, startPos)
		              + myValue 
                      + myField.value.substring(endPos, myField.value.length);
		myField.focus();
		myField.selectionStart = startPos + myValue.length;
		myField.selectionEnd = startPos + myValue.length;
		myField.scrollTop = scrollTop;
	} else {
		myField.value += myValue;
		myField.focus();
	}
}

function edInsertExtLink(which, i, defaultValue) {
    myField = document.getElementById(which);
	if (!defaultValue) {
		defaultValue = 'http://';
	}
	if (!edCheckOpenTags(which, i)) {
		var URL = prompt('Enter the URL' ,defaultValue);
		var title = prompt('Enter link text', '');
		if (URL) {
			edButtons[i].tagStart = '[url=' + URL + ']';
			if(title == ""){
				edButtons[i].tagStart += URL;
			}else{
				edButtons[i].tagStart += title;
			}
			edButtons[i].tagStart += '[/url]';
			edInsertTag(which, i);
		}
	}
	else {
		edInsertTag(which, i);
	}
}

function edInsertImage(which) {
    myField = document.getElementById(which);
	var myValue = prompt('Enter the URL of the image', 'http://');
	if (myValue) {
		myValue = '[img]' + myValue + '[/img]';
		edInsertContent(which, myValue);
	}
}

function countInstances(string, substr) {
	var count = string.split(substr);
	return count.length - 1;
}

function edSetCookie(name, value, expires, path, domain) {
	document.cookie= name + "=" + escape(value) +
		((expires) ? "; expires=" + expires.toGMTString() : "") +
		((path) ? "; path=" + path : "") +
		((domain) ? "; domain=" + domain : "");
}

function edShowExtraCookie() {

	var cookies = document.cookie.split(';');
	for (var i=0;i < cookies.length; i++) {
		var cookieData = cookies[i];
		while (cookieData.charAt(0) ==' ') {
			cookieData = cookieData.substring(1, cookieData.length);
		}
		if (cookieData.indexOf('js_quicktags_extra') == 0) {
			if (cookieData.substring(19, cookieData.length) == 'show') {
				return true;
			}
			else {
				return false;
			}
		}
	}
	return false;
}