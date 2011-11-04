<?php

//  Copyright 2005-2010 Ameoto Systems. All Rights Reserved.
//  Written by Jeffrey Jenner, thetooth@ameoto.com


session_start ();
error_reporting ( E_ALL );

define ( "Init", 0 );
$sys ['version'] = '2.0.1 r105';
$sys ['plugins'] = false;
$SELF = filter_var ( (isset ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] == "on" ? 'https://' : 'http://') . dirname ( (isset ( $_SERVER ['HTTP_HOST'] ) ? $_SERVER ['HTTP_HOST'] : 'localhost') . $_SERVER ['PHP_SELF'] ), FILTER_SANITIZE_STRING );

// Fetch the current page
if (! isset ( $_REQUEST ['page'] )) {
	$page = 'home';
} else {
	$page = $_REQUEST ['page'];
	$page = trim ( rawurldecode ( $page ) );
	$page = strtolower ( strip_tags ( $page ) );
	$page = preg_replace ( '/\/$/', "", $page );
	if ($page == '') {
		$page = 'home';
	}
	if (substr_count ( $page, '..' ) > 0 || substr_count ( $page, '~' ) > 0) {
		$page = 'home';
		header ( "HTTP/1.0 403 Forbidden" );
	}
}

// Defines
define ( "WCVERSION", $sys ['version'] );
define ( "ParserAPI", 'WCParser' );
define ( "WC_SELF", $SELF );
define ( "WC_Page", $page );

/* Load library
               __   __
              __ \ / __
             /  \ | /  \
                 \|/
            _,.---v---._
   /\__/\  /            \
   \_  _/ /              \ 
     \ \_|           @ __|
      \                \_
       \     ,__/       /
~~~~~~~~`~~~~~~~~~~~~~~/~~~~*/
spl_autoload_register ( null, false );
spl_autoload_extensions ( '.php, .lib.php' );

function WCSPL($class) {
	global $sys;
	$filename = strtolower ( $class ) . '.lib.php';
	if (substr ( $class, 0, 2 ) !== "WC") {
		$file = 'paper/plugins/' . $filename;
	} else {
		$file = 'paper/' . $filename;
	}
	if (! file_exists ( $file )) {
		trigger_error ( "SPL bootstrap failed to locate class \"$class\" [$filename]", E_USER_NOTICE );
		return false;
	}
	require_once ($file);
	return true;
}
spl_autoload_register ( 'WCSPL' );

// Hi, my names Dew. Lets have some fun!
$dew = new WC ();

// Begin Output
if ((require ('themes/' . $dew->_config ['theme'] . '/header.php')) && (file_exists ( 'themes/' . $dew->_config ['theme'] . '/footer.php' )) === false) {
	exit ( WCException::ErrorMsg ( $dew->_lang ['err'] ['theme_include'] ) );
} else {
	// System events
	$dew->_Event ();
	// Render Page
	$dew->_RenderPage ();
	
	//If user is authenticated display admin tools
	if (isset ( $_SESSION ['logged'] ) && array_key_exists ( $_SESSION ["logged"], $dew->_user )) {
		// Check for lock file
		if (file_exists ( "pages/$" . str_replace ( "/", ";", WC_Page ) . ".lock" )) {
			$lockSet = str_split ( fgets ( fopen ( "pages/$" . str_replace ( "/", ";", WC_Page ) . ".lock", "r" ) ), 14 );
			if (($lockSet [0] - date ( 'YmdHis' )) < - 500) {
				unlink ( "pages/$" . str_replace ( "/", ";", WC_Page ) . ".lock" );
			}
		}
		//Display editor
		if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Edit' && ($dew->_user [$_SESSION ["logged"]] [1] == (1 || 2) || $page == '$user-' . $_SESSION ['logged'])) {
			echo '<h1>Editing</h1>';
			if (isset ( $_REQUEST ['preview'] )) {
				$content = stripslashes ( $_REQUEST ['message'] );
				$parser = new WCParser ();
				$Render = $parser->parse ( $content );
				if ($dew->_config ['inlinephp'] == true) {
					eval ( '?><div class="quote">' . $Render . '</div><?php ' );
				} else {
					echo '<div class="quote">' . $Render . '</div>';
				}
				unset ( $parser );
				unset ( $Render );
			}
			if (file_exists ( "pages/$" . str_replace ( "/", ";", WC_Page ) . ".lock" ) && ! isset ( $_REQUEST ['FORCE'] ) && ! isset ( $_REQUEST ['preview'] )) {
				echo '<p>' . str_replace ( '::user', $lockSet [1], $dew->_lang ['txt'] ['pageLock'] ) . ' ', '(<a href="' . WC_SELF . '/?page=' . WC_Page . '&amp;act=Edit&amp;FORCE=true">' . $dew->_lang ['obj'] ['Continue'] . '</a>)</p>';
			} else {
				$lockfile = fopen ( "pages/$" . str_replace ( "/", ";", $page ) . ".lock", "w" );
				fwrite ( $lockfile, date ( 'YmdHis' ) . $_SESSION ['logged'] );
				fclose ( $lockfile );
				echo '<form name="editor" action="' . WC_SELF . '/?page=' . WC_Page . '" method="post" enctype="multipart/form-data">', '<div id="menu"><script type="text/javascript">edToolbar(\'canvas\');</script></div>', '<textarea name="message" id="canvas" rows="24" onKeyDown="insertTab(this, event);">';
				if (isset ( $_REQUEST ['preview'] )) {
					echo htmlentities ( $content, ENT_COMPAT, 'UTF-8' );
				} elseif (file_exists ( $dew->_pPath )) {
					echo htmlentities ( file_get_contents ( $dew->_pPath ), ENT_COMPAT, 'UTF-8' );
				}
				echo '</textarea><script type="text/javascript">setCaretToEnd(document.editor.message);</script>', '<div class="quote"><p><input type="file" name="uploadedfile" /> ' . $dew->_lang ['txt'] ['uploadReplace'] . '<input type="checkbox" name="file_rw" /></p>', '</div><br /><span style="float:right;" id="btn">', '~/<input type="text" name="namespace" value="' . WC_Page . '" class="btn">&nbsp;<button type="submit" name="act" value="Cancel" class="btn">' . $dew->_lang ['obj'] ['Cancel'] . '</button>', '<button type="submit" name="preview" onclick="document.forms[\'editor\'].action = \'' . WC_SELF . '/?page=' . WC_Page . '&act=Edit\'; return true;" class="btn">' . $dew->_lang ['obj'] ['Preview'] . '</button>', '<button type="submit" name="act" value="Save" class="btn">' . $dew->_lang ['obj'] ['Save'] . '</button>', '</span></form>';
			}
		}
	} elseif ($dew->_config ['login'] == true) {
		echo '<div style="text-align:right;"><a href="javascript:ReverseDisplay(\'login\')" style="text-decoration:none">+</a></div>', '<form name="login" action="' . WC_SELF . '/?page=' . WC_Page . '" method="post"><div id="login" style="display:none;">', '<input type="hidden" name="act" value="log" />' . $dew->_lang ['obj'] ['Username'] . ': <input type="text" name="username" /> ', $dew->_lang ['obj'] ['Password'] . ': <input type="password" name="password" />', '<button type="submit" value="Login">' . $dew->_lang ['obj'] ['Login'] . '</button></div></form>';
	}
	require ('themes/' . $dew->_config ['theme'] . '/footer.php');
}
session_write_close ();
?>