<?php
session_start ();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>WhiteCrane Installer</title>
<link rel="stylesheet" href="themes/whitecrane/style.css"
	type="text/css" />
<script type="text/javascript" src="paper/common.js"></script>
</head>
<body>
<div id="maincontent">
<h1>WhiteCrane Installer</h1>
  <?php
		
		function bool2text($bValue = false) {
			return ($bValue ? 'true' : 'false');
		}
		function genRandomString() {
			$length = 8;
			$characters = '0123456789abcdefghijklmnopqrstuvwxyz-_:^+~*';
			$string = '';
			for($p = 0; $p < $length; $p ++) {
				$string .= $characters [mt_rand ( 0, strlen ( $characters ) )];
			}
			return $string;
		}
		function genSysId() {
			$id = "";
			$tag = array ("ARM", "NEC", "AME", "DEV" );
			if ($_SERVER ['HTTP_HOST'] == "dev.ameoto.com") {
				$id = $tag [2];
			} else if ($_SERVER ['HTTP_HOST'] != "localhost" || $_SERVER ['HTTP_HOST'] != "127.0.0.1") {
				$id = $tag [rand ( 0, 1 )];
			} else {
				$id = $tag [3];
			}
			$id .= rand ( 11, 99 );
			$id .= genRandomString ();
			$id .= "-u" . rand ( 111, 998 );
			return $id;
		}
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
		function passhash($user = "##$@", $pass) {
			return sha1 ( encrypt ( $pass, $user, true ) );
		}
		function sys_install() {
			$key = genSysId ();
			$config ['site_title'] = @$_REQUEST ['name'];
			$config ['salt'] = $key;
			$config ['lang'] = 'eng';
			$config ['theme'] = 'whitecrane';
			$config ['login'] = true;
			$config ['cookies'] = true;
			$config ['inlinephp'] = false;
			$config ['compress'] = true;
			$config ['rewrite'] = false;
			$config ['updates'] = (function_exists ( "curl_init" ) ? true : false);
			$config ['debug'] = false;
			$config ['nl'] = false;
			$config ['autolinks'] = true;
			$config ['smilies'] = true;
			$user [@$_REQUEST ['user']] = array (0 => @passhash ( $_REQUEST ['user'], $_REQUEST ['pass'] ), 1 => 1, 2 => "" );
			
			$setup = "#Installer::" . date ( 'Y-m-d g:i:s A' ) . "\n";
			$setup .= "\$config = array(\n";
			
			// Loop threw config and check for external input
			ksort ( $config, SORT_REGULAR );
			foreach ( array_keys ( $config ) as $config_node ) {
				$setup .= "\t'" . $config_node . "' => ";
				$value = $config [$config_node];
				if (is_bool ( $config [$config_node] )) {
					$setup .= bool2text ( $value );
				} else {
					$setup .= "'" . $value . "'";
				}
				$setup .= ",\n";
			}
			$setup .= ");\n";
			
			// Loop threw users again checking for input
			ksort ( $user, SORT_REGULAR );
			foreach ( array_keys ( $user ) as $user_node ) {
				$setup .= "	\$user[\"" . $user_node . "\"] = array(0 => \"" . $user [$user_node] [0] . "\", 1 => " . $user [$user_node] [1] . ", 2 => \"" . $user [$user_node] [2] . "\");\n";
			}
			
			$configFile = fopen ( 'paper/config.php', 'w' );
			fwrite ( $configFile, "<?php if(!defined(\"Init\")){ die('PRIVATE'); }\n\n" . $setup . "?>" );
			fclose ( $configFile );
			$log = fopen ( "paper/access.log", "a+" );
			fwrite ( $log, "#" . date ( 'Y-m-d g:i:s A' ) . " - WhiteCrane Installed (^_^;)" );
			fclose ( $log );
			return $key;
		}
		
		if (isset ( $_REQUEST ['install'] )) {
			if (! $_REQUEST ['name'] == '' && ! $_REQUEST ['user'] == '' && ! $_REQUEST ['pass'] == '' && $_REQUEST ['pass'] == $_REQUEST ['pass2']) {
				$key = sys_install ();
				$serial = ( string ) "E" . substr ( sha1 ( $key ), 0, 8 );
				$_SESSION ["logged"] = $_REQUEST ['user'];
				$_SESSION [$serial] = sha1 ( $_SERVER ['HTTP_USER_AGENT'] . passhash ( $_REQUEST ['user'], $_REQUEST ['pass'] ) . $key );
				echo '<meta http-equiv="refresh" content="1; url=./index.php" />';
			} else {
				echo ('<p style="color:#F00">Oh noes! Something is not right, please try again.</p>');
			}
		}
		if (! file_exists ( 'paper/config.php' )) {
			$x = @$_REQUEST ['q'];
			switch ($x) {
				case 'license' :
					?>
  <pre>
Copyright (c) 2008-2011 Ameoto Systems Inc. All Rights Reserved.
              1991-1992 RSA Data Security, Inc. Created 1991. All rights reserved.
              
Distributed under the 3-clause "New BSD" License.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the Ameoto Systems Inc. nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS ``AS IS'' AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL Ameoto Systems Inc. BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.</pre>
<div align="right"><a href="?q=install">Agree</a></div>
  <?php
					break;
				case 'install' :
					?>
  <p>WhiteCrane requires that a few directorys are accessible by php so
we will now see if that's possible.</p>
  <?php
					$files = array (0 => './pages/', 1 => './uploads/', 2 => './paper/', 3 => './paper/cache/' );
					echo '<p><strong>Checking filesystem... ';
					foreach ( $files as $check ) {
						if (! is_writable ( $check )) {
							die ( '<span style="color:#F00">Oh Noes!</span></strong><br />Please verify and chmod the ' . $check . ' directory so php can modify it. <br />You may need to set the owner to that of the webservers run name("www-data", "apache") to avoid security issues.</p></body></html>' );
						}
					}
					echo '<span style="color:#0F0">OK!</span><br />SPL Extensions... ';
					if (function_exists ( "spl_autoload_register" )) {
						echo '<span style="color:#0F0">OK!</span>';
					} else {
						die ( '<span style="color:#F00">SPL not fond.</span>' );
					}
					echo '<br />Networking... ';
					if (function_exists ( "curl_init" )) {
						echo '<span style="color:#0F0">OK!</span>';
					} else {
						echo '<span style="color:#F60">Automatic updates are not posible on this server("curl_init" missing).</span>';
					}
					echo '</strong></p>';
					?>
  <p>All you need to do now is fill out the form below and WhiteCrane
will be up and running. Once installed this file will no longer function
but it is still recommended that you delete it from you web server.</p>
<form id="installer" name="install" method="post"
	action="install.php?q=install">
<table width="100%" border="0" cellpadding="0" cellspacing="0"
	class="form-table">
	<tr>
		<td width="13%">
		<p>Site Name:</p>
		</td>
		<td width="87%"><input type="text" name="name" id="textfield" /></td>
	</tr>
	<tr>
		<td>
		<p>Username:</p>
		</td>
		<td><input type="text" name="user" id="textfield2" /></td>
	</tr>
	<tr>
		<td>
		<p>Password:</p>
		</td>
		<td><input type="password" name="pass" id="textfield3" /> <br />
		<input type="password" name="pass2" id="textfield3" /> (again)</td>
	</tr>
</table>
<div align="center"><label> <input type="submit" name="install"
	id="button" value="Install" /> </label></div>
</form>
  <?php
					break;
				default :
					?>
  <p>Thank you for downloading WhiteCrane.</p>
<h2>Requirements</h2>
  <?php
					if (( int ) substr ( phpversion (), 0, 1 ) < 5) {
						echo '<p><strong><span style="color:#F00">WhiteCrane is incompetable with your current version.(' . phpversion () . ')</span></strong></p>';
					}
					?>
  <p>You need a web server with PHP5 and that's about it. Its also recormened you have cURL enabled to be notifiyed about updates.</p>
<p>Developed under Lighttpd &amp; IIS7 running PHP 5.2-3, tested in production on Apache and Nginx too.</p>
<h2>Release Notes</h2>
<pre>All new release, complete rewrite and OOP design!</pre>
<div align="right"><a href="?q=license">Next</a></div>
  <?php
			}
		} else {
			?>
  <p><strong>Success!</strong> Please remember remove this file from
your web server and enjoy your new cms(logging you in, one sec...).</p>
  <?php
		}
		?>
</div>
</body>
</html>
<?php
session_write_close ();
?>