var xmlhttp;
var spoof = false;
function GetXmlHttpObject(){
	if(window.XMLHttpRequest){
		// code for IE7+, Firefox, Chrome, Opera, Safari
		return new XMLHttpRequest();
	}
	if(window.ActiveXObject){
		// code for IE6, IE5
		return new ActiveXObject("Microsoft.XMLHTTP");
	}
	return null;
}
// Reverse Display //
function ReverseDisplay(d){
	if(document.getElementById(d).style.display == "none"){
		document.getElementById(d).style.display = "";
	}else{
		document.getElementById(d).style.display = "none";
	}
}
// Confirm Handeler //
function confirmPage(){
	var agree=confirm("Are you sure you want to delete this page? \nIt can not be recovered!");
	if(agree){
		return true;
	}else{
		return false;
	}
}
function confirmFile(Page, file){
	var agree=confirm("Are you sure you want to delete this file? \nIt can not be recovered!");
	if(agree){
		window.location = "index.php?page="+encodeURIComponent(Page)+"&act=Upload&unlink="+file;
		return true;
	}else{
		return false;
	}
}
function confirmUser(){
	var agree=confirm("Are you sure you want to delete this user account?");
	if(agree){
		return true;
	}else{
		return false;
	}
}
function randomString(){
	var chars = "0123456789ABCDEF";
	var string_length = 32;
	var randomstring = '';
	for(var i=0; i<string_length; i++){
		var rnum = Math.floor(Math.random()*chars.length);
		randomstring += chars.substring(rnum, rnum+1);
	}
	return randomstring;
}
var loadPreview = false;
function quickSave(url, Page){
	var frm = document.forms["editor"];
	frm.preview.disabled = true;
	frm.preview.innerHTML = "Saving...";
	xmlhttp = GetXmlHttpObject();
	if(xmlhttp==null){
		alert("Failed to send XML HTTP Request!");
		frm.preview.innerHTML = "Failed!";
		return;
	}
	xmlhttp.open("POST",url+"/?page="+encodeURIComponent(Page)+"&quicksave",true);
	xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xmlhttp.send("namespace="+encodeURIComponent(frm.namespace.value)+"&act=Save&message="+encodeURIComponent(frm.message.value));
	xmlhttp.onreadystatechange = function (){
		if(xmlhttp.readyState==4){
			if(xmlhttp.responseText.search(/::saveok::/) != -1){
				frm.preview.innerHTML = "Saved Changes!";
				window.setTimeout(function (){
					frm.preview.innerHTML = "Save";
					frm.preview.disabled = false;
					if(loadPreview){
						if(Page != frm.namespace.value){
							Page = encodeURIComponent(frm.namespace.value);
						}
						window.location = url+"/?page="+Page+"&act=Edit&preview";
					}
					if(Page != frm.namespace.value){
						window.location = "index.php?page="+encodeURIComponent(frm.namespace.value)+"&act=Edit"
					}
				}, 1000);
				return true;
			}else{
				frm.preview.innerHTML = "Error Saving Changes!";
			}
		}
	};
}
function file(where){
	window.location = where;
}
function promptRename(Page, current) {
	var _prompt = prompt('Enter a new name', current);
	if(_prompt){
		window.location = "index.php?page="+encodeURIComponent(Page)+"&act=Upload&ren="+current+"&new="+_prompt;
		return true;
	}else{
		return false;
	}
}
function promptDirectory(Page) {
	var _prompt = prompt('New folder', "");
	if(_prompt){
		window.location = "index.php?page="+encodeURIComponent(Page)+"&act=Upload&mkdir="+_prompt;
		return true;
	}else{
		return false;
	}
}
function confirmFolder(Page, folder){
	var agree=confirm("Are you sure you want to delete this folder and all its contents? \nIt can not be recovered!");
	if(agree){
		window.location = "index.php?page="+encodeURIComponent(Page)+"&act=Upload&rmdir="+folder;
		return true;
	}else{
		return false;
	}
}
function insertTab(o, e){
	var kC = e.keyCode ? e.keyCode : e.charCode ? e.charCode : e.which;
	if(kC == 9 && !e.shiftKey && !e.ctrlKey && !e.altKey){
		var oS = o.scrollTop;
		if(o.setSelectionRange){
			var sS = o.selectionStart;
			var sE = o.selectionEnd;
			o.value = o.value.substring(0, sS) + "\t" + o.value.substr(sE);
			o.setSelectionRange(sS + 1, sS + 1);
			o.focus();
		}else if(o.createTextRange){
			document.selection.createRange().text = "\t";
			e.returnValue = false;
		}
		o.scrollTop = oS;
		if(e.preventDefault){
			e.preventDefault();
		}
		return false;
	}
	return true;
}
function setSelectionRange(input, selectionStart, selectionEnd){
	if(input.setSelectionRange){
		input.focus();
		input.setSelectionRange(selectionStart, selectionEnd);
	}else if(input.createTextRange){
		var range = input.createTextRange();
		range.collapse(true);
		range.moveEnd('character', selectionEnd);
		range.moveStart('character', selectionStart);
		range.select();
	}
}
function setCaretToEnd(input){
	var pos = input.innerHTML.length;
	setSelectionRange(input, pos, pos);
}