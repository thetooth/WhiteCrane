<?php

//  Copyright 2005-2010 Ameoto Systems. All Rights Reserved.
//  Written by Jeffrey Jenner, thetooth@ameoto.com


if (! defined ( "Init" )) {
	die ( "For security reasons you can not run this component directly." );
}

register_shutdown_function ( array ('WC', 'cleanup' ) );
set_error_handler ( array ('WCException', '_' ) );

// Interface for plugins and software intergration
interface WCAPI {
	public function _init();
}
// WhiteCrane ;)
class WC {
	// Storage
	protected static $_callbackArray = array ("WCParser" => null );
	protected static $ApplicationInfo;
	
	public $_WCUID = false;
	public $_pPath = 'pages/home.txt';
	public $_cPath = 'paper/cache/home.txt';
	public $_config = array ();
	public $_user = array ();
	public $_lang = array ();
	public $_parsedURL;
	
	// Dynamic and Private
	private $_loginStatus;
	
	/**
	 * API interface shortcut
	 * @param WCAPI $callback Callback class name
	 * @param str $type Optional. On call contructor
	 */
	public function _api(WCAPI $callback, $type = "_init") {
		$callback->$type ();
	}
	
	/**
	 * Add static method callback to hooks array
	 * @param str $gateway Hook name
	 * @param str $usrClass Callback class name
	 * @param str $method Callback method name
	 */
	protected function _addCall($gateway, $usrClass, $method) {
		self::$_callbackArray [$gateway] [] = array ($usrClass, $method );
	}
	
	/**
	 * Method for running user callbacks from the hooks array
	 * @param string $class Hook key
	 * @param void|mixed $params Data to pass to user callback, if any.
	 * @return void|boolean|mixed Returns the resualting callbacks output on sucess. False|E_USER_WARNING on error or emty array.
	 */
	protected function _usrCallback($class, $params = array()) {
		if (! is_array ( self::$_callbackArray [$class] )) {
			return false;
		}
		foreach ( self::$_callbackArray [$class] as $node ) {
			$cls = array (new $node [0] (), $node [1] );
			if (! is_callable ( $cls )) {
				echo WCException::ErrorMsg ( "<strong>Exception:</strong> Could not run user callback " . $node [0] . "::" . $node [1] . " in " . $class );
				return false;
			} else {
				return call_user_func_array ( $cls, $params );
			}
		}
	}
	
	/**
	 * Load configuration and aditional resources, then populate WC's storage 
	 * and check off a few things.
	 */
	public function __construct() {
		global $sys;
		$config = null;
		$user   = null;
		$lang   = null;
		
		// Load configuration
		if ((@include ("paper/config.php")) === false) {
			/** OOP tips: In theroy we should never get far enough to invoke
			 * this... but due to the child class inheriting this constructor
			 * it also inherits its problems */
			define ( "WCGUID", "null" );
			exit ( WCException::HTMLErrorMsg ( 'No configuration found, please run the <a href="install.php">installer</a>.' ) );
		}
		$this->_config = $config;
		$this->_user = $user;
		
		// Debugging?
		if ($this->_config ['debug'] == true) {
			ini_set ( 'scream.enabled', true );
		}
		
		// Open output buffer
		ob_start ( array ($this, "compress" ) );
		
		// Load default language and then merge with native
		require ("paper/lang.eng.php");
		$this->_lang = $lang;
		if ($this->_config ['lang'] !== "eng") {
			if((@include ("paper/lang." . $this->_config ['lang'] . ".php")) === false) {
				trigger_error( 'Failed to load additional language file: paper/lang.' . $this->_config ['lang'] . '.php', E_USER_WARNING );
			}else{
				$this->_lang = array_merge ( $this->_lang, $lang );
			}
		}
		
		$this->_WCUID = "E" . substr ( sha1 ( $this->_config ['salt'] ), 0, 8 );
		$this->_pPath = 'pages/' . str_replace ( "/", ";", WC_Page ) . '.txt';
		$this->_cPath = 'paper/cache/' . str_replace ( "/", ";", WC_Page ) . '.txt';
		
		if (! defined ( "WCGUID" )) {
			define ( "WCGUID", $this->_WCUID );
		}
		
		// Send headers nice an erly
		if (! file_exists ( $this->_pPath )) {
			header ( "HTTP/1.0 404 Not Found" );
		} elseif (WC_Page == '403') {
			header ( "HTTP/1.0 403 Forbidden" );
		}
		if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Cancel') {
			header ( "HTTP/1.0 301 Moved Permanently" );
			header ( "Location: " . WC_SELF . '/index.php?page=' . WC_Page );
			if ($this->_config ['debug'] == false) {
				exit ( 0 );
			}
		}
		if ($this->_config ['debug'] == true && isset ( $_REQUEST ['ping'] )) {
			die ( 'WC-' . $sys ['version'] );
		}
		// Authenticate user
		if (isset ( $_REQUEST ['act'] ) && $_REQUEST ["act"] == "log") {
			$user = strip_tags ( substr ( $_REQUEST ['username'], 0, 32 ) );
			$pass = sha1 ( session_id () . $this->passwdHash ( $user, substr ( $_REQUEST ['password'], 0, 32 ) ) );
			if (preg_match ( "/[a-zA-Z0-1-_]/", $user ) && isset ( $this->_user [$user] [0] ) && sha1 ( session_id () . $this->_user [$user] [0] ) == $pass) {
				session_regenerate_id ( true );
				$_SESSION ["logged"] = $user;
				$_SESSION [$this->_WCUID] = sha1 ( $_SERVER ['HTTP_USER_AGENT'] . $this->_user [$_SESSION ["logged"]] [0] . $this->_config ['salt'] );
				$this->_dump ( $user . " has authenticated.\n" );
				$this->_loginStatus = true;
			} else {
				session_regenerate_id ( true );
				$this->_dump ( $user . " has failed to authenticate.\n" );
				$this->_loginStatus = false;
			}
			unset ( $user, $pass );
		}
		// Get real domain name root
		if ($_SERVER ["HTTP_HOST"] == "localhost" || ! isset ( $this->selfParse ["domain"] )) {
			$realDNR = false;
		} elseif ($_SERVER ["HTTP_HOST"] != $this->selfParse ["domain"]) {
			$realDNR = "." . $this->selfParse ["domain"];
		} else {
			$realDNR = $_SERVER ["HTTP_HOST"];
		}
		// Login via cookie
		if ($this->_config ['cookies'] == true) {
			if (! isset ( $_SESSION ["logged"] ) && isset ( $_COOKIE ['wc-' . $this->_WCUID] )) {
				$cookie = preg_split ( '/,/', base64_decode ( htmlentities ( $_COOKIE ['wc-' . $this->_WCUID] ) ) );
				if (preg_match ( "/[a-zA-Z0-9-_]+/", trim ( $cookie [0] ) ) && trim ( $cookie [1] ) == sha1 ( $_SERVER ['HTTP_USER_AGENT'] . $this->_user [trim ( $cookie [0] )] [0] . $this->_config ['salt'] )) {
					$_SESSION ["logged"] = trim ( $cookie [0] );
					$_SESSION [$this->_WCUID] = sha1 ( $_SERVER ['HTTP_USER_AGENT'] . $this->_user [$_SESSION ["logged"]] [0] . $this->_config ['salt'] );
				}
			} elseif (isset ( $_SESSION ["logged"] ) && ! isset ( $_COOKIE ['wc-' . $this->_WCUID] )) {
				setcookie ( 'wc-' . $this->_WCUID, base64_encode ( $_SESSION ['logged'] . ',' . sha1 ( $_SERVER ['HTTP_USER_AGENT'] . $this->_user [$_SESSION ["logged"]] [0] . $this->_config ['salt'] ) ), 2592000 + time (), "/", $realDNR );
			}
			if (isset ( $_REQUEST ['logout'] ) && $_REQUEST ['logout'] == "true") {
				setcookie ( 'wc-' . $this->_WCUID, base64_encode ( 'null,null' ), 1, "/", $realDNR );
			}
		}
		// Kill teh infidels
		if (isset ( $_SESSION ["logged"] ) && (! isset ( $_SESSION [$this->_WCUID] ) || @$_SESSION [$this->_WCUID] != sha1 ( $_SERVER ['HTTP_USER_AGENT'] . $this->_user [$_SESSION ["logged"]] [0] . $this->_config ['salt'] ))) {
			$this->_dump ( "Hash check failed for " . $_SESSION ["logged"] . " with Serial-" . $this->_WCUID . ".\n" );
			unset ( $_SESSION [$this->_WCUID] );
			unset ( $_SESSION ["logged"] );
			session_regenerate_id ( true );
			setcookie ( 'wc-' . $this->_WCUID, base64_encode ( 'null,null' ), 1, "/", $realDNR );
			exit ( WCException::ErrorMsg ( $this->_lang ['err'] ['hashcheck'] . "<br /><strong style=\"font-size:9px\">System Serial-" . $this->_WCUID . "</strong>" ) );
		}
		// Clear session
		if (isset ( $_REQUEST ['logout'] ) && $_REQUEST ['logout'] == "true") {
			unset ( $_SESSION [$this->_WCUID] );
			unset ( $_SESSION ['logged'] );
			session_regenerate_id ( true );
		}
	}
	// Public GUID
	public static function GUID() {
		$pubGUID = sha1 ( WCGUID );
		return "<strong>" . substr ( $pubGUID, 0, 8 ) . " " . substr ( $pubGUID, 8, 8 ) . " " . substr ( $pubGUID, 16, 8 ) . " " . substr ( $pubGUID, 24, 8 ) . " " . substr ( $pubGUID, 32, 8 ) . "</strong>";
	}
	// Headers
	public function META() {
		echo '<script type="text/javascript" src="' . WC_SELF . '/paper/common.js"></script>';
		if (isset ( $_SESSION ['logged'] )) {
			echo '<script type="text/javascript" src="' . WC_SELF . '/paper/quicktags.js"></script>';
		}
	}
	// Title
	public function _PageTitle($page) {
		$this->_pPath = 'pages/' . str_replace ( "/", ";", $page ) . '.txt';
		if (file_exists ( $this->_pPath )) {
			$fp = fopen ( $this->_pPath, 'r' ) or die ( $this->_lang ['err'] ['filesystem'] . ' [009]' );
			$firstline = fgets ( $fp, 256 );
			$set = false;
			if (substr ( $firstline, 0, 6 ) == "#title") {
				$htmltitle = trim ( ltrim ( $firstline, "#title" ) );
				$htmltitle = strip_tags ( $htmltitle );
				$set = true;
			} elseif (substr ( $firstline, 0, 5 ) == "=====" && $set == false) {
				$htmltitle = trim ( ltrim ( $firstline, "=====" ) );
				$htmltitle = strip_tags ( $htmltitle );
				$htmltitle = preg_replace ( '/=====/is', '', $htmltitle );
				$set = true;
			} elseif ($set == false || $htmltitle == '') {
				$htmltitle = $page;
			}
		} else {
			$htmltitle = '404';
		}
		return $htmltitle;
	}
	// Dump to log file
	public function _dump($log) {
		$fp = fopen ( "paper/access.log", "a+" );
		fwrite ( $fp, "#" . date ( 'Y-m-d g:i:s A' ) . " - " . $log );
		fclose ( $fp );
	}
	//Return a Boolean
	public function _Bool($bValue = false) {
		return ($bValue ? 'true' : 'false');
	}
	private function parseUrl($url) {
		$r = '^(?:(?P<scheme>\w+)://)?';
		$r .= '(?:(?P<login>\w+):(?P<pass>\w+)@)?';
		$r .= '(?P<host>(?:(?P<subdomain>[\w\.]+)\.)?' . '(?P<domain>\w+\.(?P<extension>\w+)))';
		$r .= '(?::(?P<port>\d+))?';
		$r .= '(?P<path>[\w/]*/(?P<file>\w+(?:\.\w+)?)?)?';
		$r .= '(?:\?(?P<arg>[\w=&]+))?';
		$r = "!$r!";
		preg_match ( $r, $url, $out );
		$this->_parsedURL = $out;
	}
	// Encode/Decode System (Always use an unmaped hash sample from salt for best protection, not 0-8)
	function encrypt($string, $key, $raw = false) {
		$result = '';
		for($i = 0; $i < strlen ( $string ); $i ++) {
			$char = substr ( $string, $i, 1 );
			$keychar = substr ( $key, ($i % strlen ( $key )) - 1, 1 );
			$char = chr ( ord ( $char ) + ord ( $keychar ) );
			$result .= $char;
		}
		if ($raw == false) {
			return base64_encode ( $result );
		} else {
			return $result;
		}
	}
	function decrypt($string, $key, $raw = false) {
		$result = '';
		if ($raw == false) {
			$string = base64_decode ( $string );
		}
		for($i = 0; $i < strlen ( $string ); $i ++) {
			$char = substr ( $string, $i, 1 );
			$keychar = substr ( $key, ($i % strlen ( $key )) - 1, 1 );
			$char = chr ( ord ( $char ) - ord ( $keychar ) );
			$result .= $char;
		}
		return $result;
	}
	public function passwdHash($user = "##$@", $pass) {
		return sha1 ( $this->encrypt ( $pass, $user, true ) );
	}
	// Output compression
	public function compress($buffer) {
		$pres_tags = array ("pre", "code", "xmp", "textarea" );
		$preserved_tags = array ();
		foreach ( $pres_tags as $pres_tag ) {
			if (preg_match_all ( "!(<$pres_tag.*?>).+?(</$pres_tag.*?>)!ms", $buffer, $matches )) {
				foreach ( $matches [0] as $match ) {
					$hash = md5 ( $match );
					$preserved_tags [$hash] = $match;
					$buffer = str_replace ( $match, "<" . $hash . ">", $buffer );
				}
			}
		}
		if ($buffer) {
			$buffer = preg_replace ( "!\n[ \t]+!", "\n", $buffer );
			$buffer = preg_replace ( "![ \t]+!", " ", $buffer );
			$buffer = preg_replace ( "!\n+!", "\n", $buffer );
			$buffer = preg_replace ( '!\s*(</?(div|td|tr|th|table|p|ul|li|body|head|html|script|meta|select|option|iframe|h\d|br /|dl|dt|dd|span|input|button|label)[^>]*>)\s*!i', "$1", $buffer );
			$buffer = trim ( $buffer );
		}
		if (! empty ( $preserved_tags )) {
			foreach ( $preserved_tags as $hash => $tag ) {
				$buffer = str_replace ( "<" . $hash . ">", $tag, $buffer );
			}
		}
		return $buffer;
	}
	// Clean Up
	public static function cleanup() {
		global $sys;
		$ob = ob_get_status ();
		if (isset ( $ob ['type'] ) && $ob ['type'] == 1) {
			header ( "Connection: close" );
			ob_end_flush ();
		}
		$error = error_get_last ();
		if ($error != null && ($error ['type'] == 1 || $error ['type'] == 256)) {
			echo "<div class=\"error\"><strong>;-; Execution aborted due to an unrecoverable error.</strong><br />Use the debugging infomation above to correct the issue(if applicable) or;<br />If you found a bug within WhiteCrane itself please email me at thetooth@ameoto.com with a bug report.</div><br />";
		}
	}
	//Reload Page
	public function Reload($i, $prop) {
		global $argv;
		$prop = (isset ( $_REQUEST ['ref'] ) ? $_REQUEST ['ref'] : $prop);
		echo (! isset ( $argv ) ? '<meta http-equiv="refresh" content="' . $i . '; url=' . WC_SELF . '/index.php?page=' . WC_Page . $prop . '" />' : "");
	}
	//Indexing System
	public function _Index() {
		$files = array ();
		try {
			$handle = new directoryIterator ( './pages/' );
		} catch ( Exception $e ) {
			return trigger_error ( "Could not open pages directory, IO issue in WC::Index()<br /><br />" . $e->getMessage (), E_USER_WARNING );
		}
		while ( $handle->valid () ) {
			if (! $handle->isDot () && ! $handle->isDir ()) {
				$files [] = $handle->getFilename ();
			}
			$handle->next ();
		}
		sort ( $files, SORT_REGULAR );
		if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Index') {
			$content = '<h2>' . $this->_lang ['obj'] ['Index'] . '</h2><ul>';
			foreach ( $files as $file ) {
				if (! preg_match ( '/(.+?).txt$/', $file ) || preg_match ( '/^(\$|blog_)/', $file )) {
					continue;
				}
				$file = str_replace ( '.txt', '', $file );
				if ($this->_config ['rewrite'] == true) {
					$content .= '<li><a href="' . WC_SELF . '/' . $file . '">';
				} else {
					$content .= '<li><a href="' . WC_SELF . '/?page=' . $file . '">';
				}
				$content .= $this->_PageTitle ( $file );
				$content .= '</a> [' . $file . ']</li>';
			}
			;
			$content .= '</ul>';
			echo $content;
		} else {
			return $files;
		}
	}
	// Render current page
	public function _RenderPage() {
		if (! isset ( $_REQUEST ['act'] )) {
			if (! file_exists ( $this->_pPath ) || ! is_file ( $this->_pPath )) {
				if (file_exists ( 'pages/404.txt' )) {
					$parser = new WCParser ();
					$Render = $parser->parse ( file_get_contents ( 'pages/404.txt' ) );
					echo eval ( '?>' . $Render . '<?php ' );
				} else {
					echo $this->_lang ['err'] ['404'];
				}
			} elseif (file_exists ( $this->_cPath ) && filemtime ( $this->_cPath ) >= filemtime ( $this->_pPath ) && $this->_config ['debug'] == false) {
				if ($this->_config ['inlinephp'] == true) {
					include ($this->_cPath);
				} else {
					echo file_get_contents ( $this->_cPath );
				}
			} else {
				if (! isset ( $parser )) {
					$parser = new WCParser ();
				}
				$Render = $parser->parse ( file_get_contents ( $this->_pPath ) );
				if ($this->_config ['inlinephp'] == true) {
					echo eval ( '?>' . $Render . '<?php ' );
				} else {
					echo $Render;
				}
				if ($this->_config ['debug'] == false) {
					$cacheHandle = fopen ( $this->_cPath, 'w' ) or exit ( $this->_lang ['err'] ['cache'] );
					fwrite ( $cacheHandle, $Render );
					fclose ( $cacheHandle );
				} elseif (file_exists ( $this->_cPath )) {
					unlink ( $this->_cPath );
				}
				unset ( $parser );
				unset ( $Render );
			}
		}
	}
	
	/**
	 * Clear Submit to update_config()
	 * @deprecated
	 * @return boolean
	 */
	public function validateForm() {
		// Get requests
		$_user = $_REQUEST ['id'];
		if (! isset ( $_REQUEST [$_user] ) || empty ( $_REQUEST [$_user] )) {
			$_pass = $this->_user [$_user] [0];
		} else {
			$_pass = $this->passwdHash ( $_user, $_REQUEST [$_user] );
		}
		$_email = strip_tags ( $_REQUEST [$_user . '_email'] );
		// Validate
		if ($_REQUEST [$_user] != $_REQUEST [$_REQUEST ['id'] . '_conf'] || ! preg_match ( "/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$/", $_email )) {
			return false;
		}
		// Set users var
		$this->_user [$_user] [0] = $_pass;
		$this->_user [$_user] [1] = $this->_user [$_user] [1];
		$this->_user [$_user] [2] = $_email;
		// Clear inputs for shitty out of date bits
		foreach ( array_keys ( $_REQUEST ) as $i ) {
			if (in_array ( $i, array ('act', 'profile', 'id', 'redirect' ) )) {
				continue;
			}
			unset ( $_REQUEST [$i] );
		}
		return true;
	}
	// XHR decoding
	private function utf8Urldecode($value) {
		if (is_array ( $value )) {
			foreach ( array_keys ( $value ) as $val ) {
				$value [$val] = $this->utf8Urldecode ( $value [$val] );
			}
		} else {
			$value = preg_replace ( '/%([0-9a-f]{2})/ie', "chr(hexdec($1))", ( string ) $value );
		}
		return $value;
	}
	// Event Responders
	public function _Event() {
		// If where not doing anything lets gtfo of here before we waste 20k
		if (! isset ( $_REQUEST ['act'] )) {
			return 1;
		}
		// Control structures below only available to authenticated users
		if (isset ( $_SESSION ['logged'] ) && array_key_exists ( $_SESSION ["logged"], $this->_user )) {
			// Control structures below only available to Administators
			if ($this->_user [$_SESSION ["logged"]] [1] == 1) {
				$localMGR = new WCMGR ();
				switch ($_REQUEST ['act']) {
					case "System" :
						// Display System Config page
						$localMGR->systemMGR ();
						break;
					case "User Management" :
						// Display User Management page
						$localMGR->userMGR ();
						break;
					case "Save Config" :
						// Call update_config with clean name
						echo '<p>' . $this->_lang ['txt'] ['Save Config'] . '</p>';
						$localMGR->update_config ();
						break;
					default :
						unset ( $localMGR );
				}
			}
			//Create a page and write a header to it
			if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Create' && $this->_user [$_SESSION ["logged"]] [1] == (1 || 2)) {
				echo '<p>' . $this->_lang ['txt'] ['Create'] . '</p>';
				$file = fopen ( $this->_pPath, 'w' ) or die ( $this->_lang ['err'] ['filesystem'] . ' [009]' );
				if ($_POST ['title'] != '') {
					fwrite ( $file, "=====" . $_POST ['title'] . "=====" );
				} else {
					fwrite ( $file, "=====" . WC_Page . "=====" );
				}
				fclose ( $file );
				$this->Reload ( 1, '' );
			}
			//Save page contents
			if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Save' && ($this->_user [$_SESSION ["logged"]] [1] == (1 || 2))) {
				echo '<p>' . $this->_lang ['txt'] ['Save'];
				if (isset ( $_REQUEST ['quicksave'] )) {
					$message = $this->utf8Urldecode ( $_POST ['message'] );
					$namespace = strtolower ( $this->utf8Urldecode ( $_POST ['namespace'] ) );
				} else {
					if (get_magic_quotes_gpc ()) {
						$message = stripslashes ( $_POST ['message'] );
					} else {
						$message = $_POST ['message'];
					}
					$namespace = strtolower ( $_POST ['namespace'] );
				}
				if (file_exists ( "pages/$" . WC_Page . ".lock" ) && ! isset ( $_REQUEST ['quicksave'] )) {
					unlink ( "pages/$" . WC_Page . ".lock" );
				}
				$file = fopen ( $this->_pPath, 'w' ) or die ( $this->_lang ['err'] ['filesystem'] . ' [009]' );
				if ($file) {
					if (isset ( $_REQUEST ['quicksave'] )) {
						echo "::saveok::";
					}
					fwrite ( $file, $message );
					fclose ( $file );
				}
				if ((preg_match ( '/\?ERR/', str_replace ( array ('|', '<', '>', '"', '\'', ':', '\\', '*', '?' ), '?ERR', $namespace ) )) == true) {
					echo '<br /><strong>' . $this->_lang ['err'] ['nameSpace'] . '</strong>';
					$error = true;
				} elseif ($namespace != WC_Page && ! isset ( $error )) {
					rename ( $this->_pPath, 'pages/' . str_replace ( "/", ";", $namespace ) . '.txt' ) or die ( $this->_lang ['err'] ['filesystem'] . ' [007]' );
					@unlink ( $this->_cPath );
					echo '<br />' . $this->_lang ['txt'] ['Move'] . ' <a href="?page=' . $namespace . '">' . $namespace . '</a>.';
				} else {
					$this->Reload ( 1, '' );
				}
				echo '</p>';
			}
			//Destroy a page
			if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Delete' && $this->_user [$_SESSION ["logged"]] [1] == (1 || 2)) {
				echo '<p>' . $this->_lang ['txt'] ['Delete'] . '</p>';
				unlink ( $this->_pPath ) or die ( $this->_lang ['err'] ['filesystem'] . ' [002]' );
				if (file_exists ( 'paper/cache/' . WC_Page . '.txt' )) {
					unlink ( 'paper/cache/' . WC_Page . '.txt' ) or die ( $this->_lang ['err'] ['filesystem'] . ' [004]' );
				}
				$this->Reload ( 1, '' );
			}
			// Call upload function on request or if file handeler is set
			if (((isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Upload') || isset ( $_FILES ['uploadedfile'] )) && $this->_user [$_SESSION ["logged"]] [1] == (1 || 2)) {
				//$this->upload();
			}
			// Flush paper/cache/ dir
			if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Purge Cache') {
				echo (! isset ( $argv ) ? '<p>' . $this->_lang ['txt'] ['Purge Cache'] . '</p>' : $this->_lang ['txt'] ['Purge Cache']);
				$path = @opendir ( "./paper/cache/" ) or die ( $this->_lang ['err'] ['filesystem'] . ' [0C1]' );
				while ( false !== ($filename = readdir ( $path )) ) {
					if ($filename == "." || $filename == ".." || $filename == ".svn" || ! is_file ( "paper/cache/" . $filename )) {
						continue;
					}
					unlink ( "paper/cache/" . $filename ) or die ( $this->_lang ['err'] ['filesystem'] . ' [004]' );
					echo '.';
				}
				echo 'OK!';
				$this->Reload ( 1, '&amp;act=System' );
			}
			// Preproccess all pages and save to cache
			if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Build Cache') {
				echo (! isset ( $argv ) ? '<p>' . $this->_lang ['txt'] ['Build Cache'] . '</p>' : $this->_lang ['txt'] ['Build Cache']);
				$files = $this->_Index ();
				$count = 0;
				foreach ( $files as $file ) {
					if ($file == "." || $file == ".." || ! preg_match ( '/(.+?).txt$/', $file ) || preg_match ( '/^(\$)/', $file ) || file_exists ( 'paper/cache/' . $file )) {
						continue;
					}
					$parser = new WCParser ();
					$Render = $parser->parse ( file_get_contents ( './pages/' . $file ) );
					$cacheHandle = fopen ( 'paper/cache/' . str_replace ( "/", ";", $file ), 'w' ) or die ( $this->_lang ['err'] ['cache'] );
					fwrite ( $cacheHandle, $Render );
					fclose ( $cacheHandle );
					echo '.';
					$count ++;
					unset ( $parser );
				}
				echo 'OK!';
				$this->Reload ( 1, '&amp;act=System' );
			}
		}
		// Quick method for reloading and or clearing requests
		if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Cancel') {
			echo '<p>' . $this->_lang ['txt'] ['Cancel'] . '</p>';
			if (file_exists ( "pages/$" . str_replace ( "/", ";", WC_Page ) . ".lock" )) {
				unlink ( "pages/$" . str_replace ( "/", ";", WC_Page ) . ".lock" );
			}
			$this->Reload ( 0, '' );
		}
		// Display the Index page
		if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Index') {
			$this->_Index ();
		}
		// Display user profiles
		if (isset ( $_REQUEST ['act'] ) && $_REQUEST ['act'] == 'Profile') {
			$localMGR = new WCMGR ();
			$localMGR->profileMGR ();
			unset ( $localMGR );
		}
		// Display login status
		if (isset ( $this->_loginStatus ) && $this->_loginStatus === true) {
			echo $this->_lang ['txt'] ['authenticate'];
			$this->Reload ( 1, '' );
		} elseif (isset ( $this->_loginStatus )) {
			echo $this->_lang ['txt'] ['authenticateFail'];
		}
		// Clear session
		if (isset ( $_REQUEST ['logout'] ) && $_REQUEST ['logout'] == "true") {
			echo $this->_lang ['txt'] ['logout'];
		}
	}
	
	//ACP
	public function acp() {
		if (isset ( $_SESSION ['logged'] ) && @array_key_exists ( $_SESSION ["logged"], $this->_user )) {
			echo '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td>', '<span>' . $this->_lang ['txt'] ['authTxt'] . ' <a href="' . WC_SELF . '/?act=Profile&amp;id=' . $_SESSION ['logged'] . '">' . $_SESSION ['logged'] . '</a>. ', '<a href="' . WC_SELF . '/?page=' . WC_Page . '&amp;logout=true">' . $this->_lang ['obj'] ['Logout'] . '</a></span></td><td align="right">';
			if (! isset ( $_REQUEST ['act'] ) && $this->_user [$_SESSION ["logged"]] [1] == (1 || 2)) {
				echo '<form action="' . WC_SELF . '/?page=' . WC_Page . '" method="post"><input type="hidden" name="act" value="" />';
				if (file_exists ( $this->_pPath )) {
					echo '<button type="submit" name="act" value="Delete" onclick="return confirmPage()" class="btn">' . $this->_lang ['obj'] ['Delete'] . '</button>', '<button type="submit" name="act" value="Edit" class="btn">' . $this->_lang ['obj'] ['Edit'] . '</button>';
				}
				if (! file_exists ( $this->_pPath )) {
					echo '<span>Header(Optional)</span><input name="title" type="text"  class="btn"/>', '<button type="submit" name="act" value="Create" class="btn">' . $this->_lang ['obj'] ['Create'] . '</button>';
				}
				echo '<button type="submit" name="act" value="Index" class="btn">' . $this->_lang ['obj'] ['Index'] . '</button>';
				if ($this->_user [$_SESSION ["logged"]] [1] == 1) {
					echo '<button type="submit" name="act" value="System" class="btn">' . $this->_lang ['obj'] ['System'] . '</button></form>';
				}
			}
			echo '</td></tr></table>';
		}
	}
}
/**
 * Rendering helper -- faster then DOM */
class WCRender extends WC {
	/* vars */
	var $type;
	var $attributes;
	var $self_closers;
	
	/* constructor */
	function html_element($type, $self_closers = array('input','img','hr','br','meta','link')) {
		$this->type = strtolower ( $type );
		$this->self_closers = $self_closers;
	}
	
	// Message utility
	public static function message() {
		$args = func_get_args ();
		if (count ( $args ) < 1) {
			return;
		}
		//$s = vsprintf(array_shift($args), $args);
		$s = new WCRender ( 'p' );
		$s->inject ( vsprintf ( array_shift ( $args ), $args ) );
		return $s->build ();
	
		//"<p class=\"message\">$s</p>\n";
	}
	
	/* get */
	function __get($attribute) {
		return $this->attributes [$attribute];
	}
	
	/* set — array or key,value */
	function __set($attribute, $value = '') {
		if (! is_array ( $attribute )) {
			$this->attributes [$attribute] = $value;
		} else {
			$this->attributes = array_merge ( $this->attributes, $attribute );
		}
	}
	
	/* remove an attribute */
	function remove($att) {
		if (isset ( $this->attributes [$att] )) {
			unset ( $this->attributes [$att] );
		}
	}
	
	/* clear */
	function clear() {
		$this->attributes = array ();
	}
	
	/* inject */
	function inject($object) {
		if (@get_class ( $object ) == __class__) {
			$this->attributes ['text'] .= $object->build ();
		}
	}
	
	/* build */
	function build() {
		//start
		$build = '<' . $this->type;
		
		//add attributes
		if (count ( $this->attributes )) {
			foreach ( $this->attributes as $key => $value ) {
				if ($key != 'text') {
					$build .= ' ' . $key . '="' . $value . '"';
				}
			}
		}
		
		//closing
		if (! in_array ( $this->type, $this->self_closers )) {
			$build .= '>' . $this->attributes ['text'] . '</' . $this->type . '>';
		} else {
			$build .= ' />';
		}
		
		//return it
		return $build;
	}
}

?>