<?php

//  Copyright 2005-2010 Ameoto Systems. All Rights Reserved.
//  Written by TheTooth, thetooth@ameoto.com


if (! defined ( "Init" )) {
	die ( "For security reasons you can not run this component directly." );
}
class WCException extends WC {
	/**
	 * Exits with user defined message wrapped in HTML from error template.
	 * 
	 * @param string $msg Message to display.
	 * @param boolean $sendToInternal Optional. Weather to send message to standard error handler.
	 * 
	 * @todo  Need to implement a way to pass stderr infomation(level|file|line No.) to handler.
	 */
	public static function HTMLErrorMsg($msg, $sendToInternal = false) {
		$ob = ob_get_status ();
		if (isset ( $ob ['type'] ) && $ob ['type'] == 1) {
			ob_end_clean ();
		}
		$errorDoc = file_get_contents ( 'paper/error.txt' );
		exit ( str_replace ( '::fail::', ($sendToInternal === true ? self::ErrorMsg ( $msg ) : $msg), $errorDoc ) );
	}
	/**
	 * Returns a preformated block containing ether standard error infomation or a custom user string.
	 * 
	 * @param integer|string $errno E_USER error code or string when not using as stderr.
	 * @param string $errstr Optional. Error Message.
	 * @param string $errfile Optional. Filename error was caught in.
	 * @param integer $errline Optional. Line number of error.
	 * 
	 * @return string 
	 */
	public static function ErrorMsg($errno, $errstr = '', $errfile = '', $errline = 0) {
		$messages = array (E_ERROR => '~Fatal Error', E_WARNING => '~Warning', E_NOTICE => '~Notice', 

		E_USER_ERROR => 'Fatal Error', E_USER_WARNING => 'Warning', E_USER_NOTICE => 'Notice' );
		if (is_string ( $errno )) {
			return "<pre class=\"error\">{$errno}</pre><br />";
		} else {
			return "<pre class=\"error\"><strong>{$messages[$errno]}:</strong> $errstr on line $errline in " . $errfile . "</pre><br />";
		}
	}
	/**
	 * PHP error handler override.
	 * @param unknown_type $errno
	 * @param unknown_type $errstr
	 * @param unknown_type $errfile
	 * @param unknown_type $errline
	 * 
	 * @return boolean Returns false on trigger failer and true on non-fatal output.
	 */
	public static function _($errno, $errstr, $errfile, $errline) {
		global $sys;
		if (! (error_reporting () & $errno)) {
			return false;
		}
		// Win32 fix
		$errfile = str_replace ( '\\', '/', basename ( $errfile ) );
		switch ($errno) {
			case E_USER_ERROR :
				ob_end_clean ();
				self::_dump ( "Fatal: $errstr on line $errline in " . $errfile . "\n" );
				die ( self::ErrorMsg ( $errno, $errstr, $errfile, $errline ) );
				break;
			case E_USER_WARNING :
			case E_USER_NOTICE :
				if (error_reporting () != E_ALL) {
					self::_dump ( "Notice: $errstr on line $errline in " . $errfile . "\n" );
				} else {
					echo self::ErrorMsg ( $errno, $errstr, $errfile, $errline );
				}
				break;
			default :
				echo self::ErrorMsg ( $errno, $errstr, $errfile, $errline );
				break;
		}
		return true;
	}
}
?>