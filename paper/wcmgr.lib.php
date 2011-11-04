<?php

//  Copyright 2005-2010 Ameoto Systems. All Rights Reserved.
//  Written by TheTooth, thetooth@ameoto.com


if (! defined ( "Init" )) {
	die ( "For security reasons you can not run this component directly." );
}
// System Administration Componets
class WCMGR extends WC {
	function themesList() {
		$dir_handle = @opendir ( "themes/" ) or die ( '<p>Unable to open themes/</p>' );
		echo '<select name="theme">';
		while ( ($file = readdir ( $dir_handle )) == true ) {
			if (substr_count ( $file, '.' ) > 0 || is_file ( $file )) {
				continue;
			}
			if ($file == $this->_config ['theme']) {
				echo '<option value="' . $file . '" selected="selected">' . $file . '</option>';
			} else {
				echo '<option value="' . $file . '">' . $file . '</option>';
			}
		}
		;
		echo '</select>';
		closedir ( $dir_handle );
	}
	function langList() {
		$dir_handle = @opendir ( "paper/" ) or die ( '<p>Unable to open paper/</p>' );
		echo '<select name="lang">';
		while ( ($file = readdir ( $dir_handle )) == true ) {
			if (is_dir ( $file ) || ! preg_match ( '/^lang\.(.+?)\.php$/', $file )) {
				continue;
			}
			$lang = preg_replace ( '/^lang\.(.+?)\.php$/', "$1", $file );
			if ($lang == $this->_config ['lang']) {
				echo '<option value="' . $lang . '" selected="selected">' . $lang . '</option>';
			} else {
				echo '<option value="' . $lang . '">' . $lang . '</option>';
			}
		}
		;
		echo '</select>';
		closedir ( $dir_handle );
	}
	public static function UIGenInput($type, $name, $value, $makeReadOnly = 'no', $size = 20) {
		if ($makeReadOnly == 'yes') {
			$read = 'readonly=""';
		} else {
			$read = '';
		}
		return "<input type=\"$type\" name=\"$name\" value=\"$value\" size=\"$size\" $read>";
	}
	public static function UIGenSelect($name, $makemultiple = 'no', $optvalue, $optdisplay) {
		if ($makemultiple == 'no') {
			$make = '';
		} else {
			$make = 'MULTIPLE';
		}
		if (! array ($optvalue )) {
			return false;
		}
		$output = "<select name=\"$name\" $make>";
		for($i = 0; $i < sizeof ( $optvalue ); $i ++) {
			$output .= "<option value=\"$optvalue[$i]\">$optdisplay[$i]</option>";
		}
		$output .= "</select>";
		return $output;
	}
	public static function UI($io = null) {
		if ($io === null) {
			return;
		}
		foreach ( array_keys ( $io ) as $node ) {
			switch ($node) {
				case 'input' :
					return self::UIGenInput ( $io [$node] ['type'], $io [$node] ['name'], $io [$node] ['value'] );
					break;
				case 'select' :
					return self::UIGenSelect ( $io [$node] ['name'], $io [$node] ['optvalue'], $io [$node] ['optdisplay'] );
					break;
				default :
					WCException::ErrorMsg ( "Nothing to draw!" );
			}
		}
	}
	function is_checked($var) {
		if ($this->_config [$var] == true) {
			echo 'checked="checked"';
		}
	}
	function updates() {
		global $sys;
		if (function_exists ( "curl_init" )) {
			$servers = array ("http://ameoto.com/updates/wc/updates.php", "http://marcellus.ameoto.com/updates/wc/updates.php", "http://192.168.0.7/updates/wc/updates.php" );
			foreach ( $servers as $url ) {
				$ch = curl_init ();
				curl_setopt ( $ch, CURLOPT_URL, $url . "?check=" . urlencode ( $sys ['version'] ) );
				curl_setopt ( $ch, CURLOPT_FRESH_CONNECT, TRUE );
				curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
				curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
				curl_setopt ( $ch, CURLOPT_REFERER, 'http://updates.ameoto.local/' . $_SERVER ['HTTP_HOST'] );
				curl_setopt ( $ch, CURLOPT_TIMEOUT, 5 );
				$buffer = curl_exec ( $ch );
				$html = $buffer;
				if ($html == false) {
					$html = curl_error ( ($ch) );
					curl_close ( $ch );
				} else {
					curl_close ( $ch );
					break;
				}
			}
			return $html;
		} else {
			return "curl";
		}
	}
	
	function systemMGR() {
		global $sys;
		echo '<h2>' . $this->_lang ['txt'] ['System'] . ' ' . $sys ['version'] . '</h2>';
		if ($this->_config ['updates'] == true || isset ( $_REQUEST ['update'] )) {
			if (! file_exists ( "paper/update.tmp" ) || isset ( $_REQUEST ['update'] )) {
				$satus = $this->updates ();
				$lockfile = fopen ( "paper/update.tmp", "w" );
				fwrite ( $lockfile, date ( 'YmdHis' ) . "::" . substr ( $satus, 0, 4 ) );
				fclose ( $lockfile );
			} else {
				$lockSet = preg_split ( "/::/", fgets ( fopen ( "paper/update.tmp", "r" ) ), 2 );
				if (($lockSet [0] - date ( 'YmdHis' )) < - 70000) {
					unlink ( "paper/update.tmp" );
					$satus = $this->updates ();
				} else {
					$satus = $lockSet [1];
				}
			}
			switch (substr ( $satus, 0, 4 )) {
				case "S201" :
					if ($this->_config ['debug'] == true || isset ( $_REQUEST ['update'] )) {
						echo "<div class=\"quote\">You are up to date!</div>";
					}
					break;
				case "S202" :
					echo "<div class=\"quote\"><strong>Your installation appears to be out of date, its recommended you update soon.</strong>";
					if (isset ( $lockfile )) {
						echo "<br />Version: " . substr ( $satus, 6, 9 ) . "<pre>" . substr ( $satus, 15 ) . "</pre>";
					}
					echo "</div>";
					break;
				default :
					if ($this->_config ['debug'] == true || isset ( $_REQUEST ['update'] )) {
						echo "<pre>Error: " . $satus . "</pre>";
					}
			}
		}
		?>

<form id="config" name="act" method="post"
	action="<?php
		echo $_SERVER ['PHP_SELF'] . '?page=' . WC_Page;
		?>"><input name="mainconfig" type="hidden" value="!" /> <input
	name="redirect" type="hidden" value="&amp;act=System" />
<div class="interface">
<dl>
	<dt><strong>Site Name</strong></dt>
	<dd><span class="input"> <input name="site_title" type="text"
		value="<?php
		echo $this->_config ['site_title'];
		?>" /> </span></dd>
	<dt><strong>Language</strong></dt>
	<dd><span class="input">
				<?php
		$this->langList ();
		?>
				</span></dd>
	<dt><strong>Theme</strong></dt>
	<dd><span class="input">
				<?php
		$this->themesList ();
		?>
				</span></dd>
	<dt><strong>Parser</strong></dt>
	<dd>
	<p><span class="checkbox"> <input name="nl" type="checkbox" id="nl"
		<?php
		$this->is_checked ( 'nl' );
		?> /> </span> <label for="nl" style="display: inline;"> Auto convert
	new lines</label></p>
	<p><span class="checkbox"> <input name="inlinephp" type="checkbox"
		id="inlinephp" <?php
		$this->is_checked ( 'inlinephp' );
		?> /> </span> <label for="inlinephp" style="display: inline;"> Enable
	php embedding</label></p>
	<p><span class="checkbox"> <input name="compress" type="checkbox"
		id="compress" <?php
		$this->is_checked ( 'compress' );
		?> /> </span> <label for="compress" style="display: inline;"> Enable
	output compression</label></p>
	<p><span class="checkbox"> <input name="autolinks" type="checkbox"
		id="autolinks" <?php
		$this->is_checked ( 'autolinks' );
		?> /> </span> <label for="autolinks" style="display: inline;"> Auto
	Links</label></p>
	<p><span class="checkbox"> <input name="smilies" type="checkbox"
		id="smilies" <?php
		$this->is_checked ( 'smilies' );
		?> /> </span> <label for="smilies" style="display: inline;"> Enable
	smiles</label></p>
	</dd>
	<dt><strong>System</strong></dt>
	<dd>
	<p><span class="checkbox"> <input name="rewrite" type="checkbox"
		id="rewrite" <?php
		$this->is_checked ( 'rewrite' );
		?> /> </span><label for="rewrite" style="display: inline;"> Enable URL
	Rewrite</label>(<a href="javascript:ReverseDisplay('rewrite-help')">?</a>)</p>
	<p id="rewrite-help" style="display: none;">In order for this to work
	you must have <strong>mod_access</strong> and <strong>mod_rewrite</strong>
	enabled in Apache.</p>
	<p><span class="checkbox"> <input name="cookies" type="checkbox"
		id="cookies" <?php
		$this->is_checked ( 'cookies' );
		?> /> </span><label for="cookies" style="display: inline;"> Save
	authentication in cookies</label></p>
	<p><span class="checkbox"> <input name="updates" type="checkbox"
		id="updates" <?php
		$this->is_checked ( 'updates' );
		?> /> </span><label for="updates" style="display: inline;"> Check for
	updates, </label><a href="index.php?act=System&amp;update=true">Check
	Now</a></p>
	</dd>
	<dt><strong>Advanced</strong></dt>
	<dd>
	<p><span class="checkbox"> <input name="debug" type="checkbox"
		id="debug" <?php
		$this->is_checked ( 'debug' );
		?> /> </span><label for="debug" style="display: inline;"> Debug Mode(<a
		href="javascript:ReverseDisplay('debug-help')">?</a>)</label></p>
	<p id="debug-help" style="display: none;">Nothing good will come from
	this! turn back now, Debug Mode will disable caching, use an
	incrementing config update and also remove all the bandaids from the
	ships hull.</p>
	<p><span class="checkbox"> <input name="login" type="checkbox"
		id="login" <?php
		$this->is_checked ( 'login' );
		?> /> </span><label for="login" style="display: inline;"> Display
	login on every page</label></p>
	</dd>
</dl>
</div>
<div align="right">
<button type="submit" name="act" value="User Management">User Management</button>
<button type="submit" name="act" value="Build Cache"><?php
		echo $this->_lang ['obj'] ['Build Cache'];
		?></button>
<button type="submit" name="act" value="Purge Cache"><?php
		echo $this->_lang ['obj'] ['Purge Cache'];
		?></button>
|
<button type="submit" name="act" value="Cancel" class="btn"><?php
		echo $this->_lang ['obj'] ['Cancel'];
		?></button>
<button type="submit" name="act" value="Save Config" class="btn"><?php
		echo $this->_lang ['obj'] ['Save'];
		?></button>
</div>
</form>
<?php
	}
	//Configaration File Fuctions
	final function update_config() {
		$error = false;
		$redirect = null;
		if (isset ( $_REQUEST ['redirect'] )) {
			$redirect = $_REQUEST ['redirect'];
		}
		$setup = "#" . $_SESSION ['logged'] . "::" . @$_SERVER ['REMOTE_ADDR'] . "::" . date ( 'Y-m-d g:i:s A' ) . "\n";
		$setup .= "\$config = array(\n";
		
		// Loop threw config and check for external input
		ksort ( $this->_config, SORT_REGULAR );
		foreach ( array_keys ( $this->_config ) as $config_node ) {
			$setup .= "\t'" . $config_node . "' => ";
			if (isset ( $_REQUEST ['mainconfig'] ) && $this->_user [$_SESSION ["logged"]] [1] == 1) {
				if (isset ( $_REQUEST [$config_node] )) {
					$value = addslashes ( $_REQUEST [$config_node] );
				} else {
					$value = $this->_config [$config_node];
				}
			} else {
				$value = $this->_config [$config_node];
			}
			if (is_bool ( $this->_config [$config_node] )) {
				$setup .= $this->_Bool ( isset ( $_REQUEST [$config_node] ) );
			} else {
				$setup .= "'" . $value . "'";
			}
			$setup .= ",\n";
		}
		$setup .= ");\n";
		
		// Remove users
		if (isset ( $_REQUEST ['remove_user'] ) && $this->_user [$_SESSION ["logged"]] [1] == 1) {
			$redirect = "&amp;act=User Management";
			if (strstr ( $_REQUEST ['remove_user'], "," )) {
				$rmUsers = preg_split ( '/,/', urldecode ( $_REQUEST ['remove_user'] ) );
				foreach ( $rmUsers as $_user ) {
					unset ( $this->_user [$_user] );
				}
			} else {
				unset ( $this->_user [$_REQUEST ['remove_user']] );
			}
		}
		
		// Add users
		if (isset ( $_REQUEST ['add_user'] ) && $this->_user [$_SESSION ["logged"]] [1] == 1) {
			if (! $_REQUEST ['user'] == '' && $_REQUEST ['pass'] == $_REQUEST ['pass2'] && ! array_key_exists ( $_REQUEST ['user'], $this->_user )) {
				$this->_user [htmlspecialchars ( strtolower ( $_REQUEST ['user'] ) )] = array (0 => $this->passwdHash ( htmlspecialchars ( strtolower ( $_REQUEST ['user'] ) ), $_REQUEST ['pass'] ), 1 => 0, 2 => "" );
			} else {
				$error = true;
			}
		}
		
		// Loop threw users again checking for input
		ksort ( $this->_user, SORT_REGULAR );
		foreach ( array_keys ( $this->_user ) as $user_node ) {
			$setup .= "	\$user[\"" . $user_node . "\"] = array(0 => \"" . (isset ( $_REQUEST [$user_node] ) ? $this->passwdHash ( $user_node, $_REQUEST [$user_node] ) : $this->_user [$user_node] [0]) . "\", 1 => " . ((isset ( $_REQUEST [$user_node . '_mode'] ) && $this->_user [$_SESSION ["logged"]] [1] == 1) ? $_REQUEST [$user_node . '_mode'] : $this->_user [$user_node] [1]) . ", 2 => \"" . (isset ( $_REQUEST [$user_node . '_email'] ) ? htmlentities ( $_REQUEST [$user_node . '_email'] ) : $this->_user [$user_node] [2]) . "\");\n";
		}
		
		// Write the config if its error free and syntax is valid
		if ($error == false && eval ( $setup ) !== false) {
			$this->_dump ( "Updated Config." );
			$configFile = fopen ( 'paper/config.php', 'w' ) or die ( $this->_lang ['err'] ['filesystem'] );
			fwrite ( $configFile, "<?php if(!defined(\"Init\")){ die('PRIVATE'); }\n\n" . $setup . "?>" );
			fclose ( $configFile );
			$this->Reload ( 1, $redirect );
		} else {
			echo $this->_lang ['txt'] ['bad_syntax'];
		}
	}
	//User Page
	function userMGR() {
		echo '<h2>User Management</h2>', '<form id="config" name="act" method="post" action="' . $_SERVER ['PHP_SELF'] . '?page=' . WC_Page . '">', '<div class="interface"><dl>';
		foreach ( array_keys ( $this->_user ) as $user_node ) {
			echo '<dt><a href="?act=Profile&amp;id=' . $user_node . '">' . $user_node . '</a></dt>';
			echo '<dd><span class="button"><select name="' . $user_node . '_mode">';
			$acctype = array (0 => 'Standard', 1 => 'Administrator', 2 => 'Editor' );
			foreach ( array_keys ( $acctype ) as $i ) {
				echo '<option value="' . $i . '" ';
				if ($this->_user [$user_node] [1] == $i) {
					echo 'selected="selected"';
				}
				echo '>' . $acctype [$i] . '</option>';
			}
			echo '</select></span> | ';
			if ($_SESSION ["logged"] != $user_node) {
				echo ' <a href="?page=' . WC_Page . '&amp;remove_user=' . urlencode ( $user_node ) . '&amp;act=Save Config" onClick="return confirmUser()">Delete</a>';
			} else {
				echo ' Delete';
			}
			'</dd>';
		}
		echo '</dl></div><p><a href="javascript:ReverseDisplay(\'addnew\')" style="text-decoration:none">Add New User</a></p>', '<div id="addnew" style="display:none;"><p>After submitting the new users details you may return here to change the password or remove the user.</p>', '<form id="user" name="act" method="post" action="' . $_SERVER ['PHP_SELF'] . '?page=' . WC_Page . '">', '<input name="act" type="hidden" value="Save Config" />', '<input name="redirect" type="hidden" value="&amp;act=User Management" />', '<table width="100%" border="0" cellpadding="0" cellspacing="0" class="form-table"><tr>', '<td><p><strong>Username:</strong></p></td><td><input type="text" name="user" id="textfield1" value="" /></td>', '</tr><tr><td><p><strong>Password:</strong></p></td><td><input type="password" name="pass" id="textfield2" value="" />', '<br /><input type="password" name="pass2" id="textfield3" value="" /> (again)</td></tr></table>', '<input type="submit" name="add_user" value="Add" /></div>', '<p align="right"><input type="submit" name="act" value="Cancel" class="btn" />', '<input type="submit" value="Update Users" class="btn" /></p></form>';
	}
	public function profileMGR() {
		if (isset ( $_REQUEST ['edit'] ) && isset ( $_REQUEST ['id'] ) && (@$_SESSION ['logged'] == $_REQUEST ['id'] || $this->_user [$_SESSION ["logged"]] [1] == 1)) {
			echo '<h2>' . $this->_lang ['txt'] ['profileHead'] . '</h2><form id="config" name="act" method="post" action="' . $_SERVER ['PHP_SELF'] . '?act=Profile&amp;id=' . $_REQUEST ['id'] . '">', '<input name="redirect" type="hidden" value="&amp;act=Profile&amp;id=' . $_REQUEST ['id'] . '" />', '<table width="100%" border="0" cellpadding="0" cellspacing="0" class="form-table">', '<tr><td width="18%"><p><strong>Email:</strong></p></td><td width="82%">', '<input name="' . html_entity_decode ( $_REQUEST ['id'] ) . '_email" type="text" value="' . $this->_user [$_REQUEST ['id']] [2] . '" maxlength="255" /></td></tr>', 

			'<tr><td><p><strong>New Password:</strong></p></td><td>', '<input name="' . html_entity_decode ( $_REQUEST ['id'] ) . '" type="password" value="" maxlength="32" /><br />', '<input name="' . html_entity_decode ( $_REQUEST ['id'] ) . '_conf" type="password" value="" maxlength="32" />(Confirm)', '</td></tr></table><div align="right"><button type="submit" name="profile" value="Save" class="btn">' . $this->_lang ['obj'] ['Save'] . '</button>', '</div></form>';
		} elseif (array_key_exists ( $_REQUEST ['id'], $this->_user ) && ! isset ( $_REQUEST ['profile'] )) {
			if (isset ( $_REQUEST ['id'] )) {
				$user_node = $_REQUEST ['id'];
			} else {
				$user_node = $_SESSION ['logged'];
			}
			echo '<h2 id="userinfo"><strong>' . ($this->_user [$user_node] [1] == (1 || 2) ? '~' : '') . $user_node . '</strong> ' . ($this->_user [$user_node] [2] != "" ? '(<a href="mailto:' . $this->_user [$user_node] [2] . '">' . $this->_user [$user_node] [2] . '</a>)' : '') . '</h2>';
			if ($_SESSION ['logged'] == $user_node || $this->_user [$_SESSION ['logged']] [1] == 1) {
				echo '<p><a href="?act=Profile&amp;id=' . $user_node . '&amp;edit=true">Modify Account</a></p>';
			}
		} elseif (isset ( $_REQUEST ['profile'] ) && $_REQUEST ['profile'] == 'Save' && isset ( $_REQUEST ['id'] ) && (@$_SESSION ['logged'] == $_REQUEST ['id'] || $this->_user [$_SESSION ["logged"]] [1] == 1)) {
			echo '<p>' . $this->_lang ['txt'] ['SaveU'] . '</p>';
			if ($this->validateForm ()) {
				$this->update_config ();
				$this->_dump ( $_SESSION ["logged"] . " requested hash re-gen with.\n" );
				$_SESSION [$this->_WCUID] = sha1 ( $_SERVER ['HTTP_USER_AGENT'] . $this->_user [$_SESSION ["logged"]] [0] . $this->_config ['salt'] );
				setcookie ( 'wc-' . $this->_WCUID, base64_encode ( $_SESSION ['logged'] . ',' . sha1 ( $_SERVER ['HTTP_USER_AGENT'] . $this->_user [$_SESSION ["logged"]] [0] . $this->_config ['salt'] ) ), 2592000 + time (), "/", $this->_parsedURL );
			} else {
				echo '<p><strong>' . $this->_lang ['err'] ['profileInvalid'] . '</strong></p>';
			}
		} else {
			echo '<p><strong>' . $this->_lang ['err'] ['profileBlank'] . '</strong></p>';
		}
	}
}

?>