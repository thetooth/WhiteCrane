<?php

//  Copyright 2005-2010 Ameoto Systems. All Rights Reserved.
//  Written by TheTooth, thetooth@ameoto.com


if (! defined ( "Init" )) {
	die ( "For security reasons you can not run this component directly." );
}
//Syntax Parser
define ( "HEAD", - 1 );
class WCParser extends WC {
	// Source
	private $_code = '';
	//Wiki Syntax
	private $wiki = array ('=====' => 'h1', '====' => 'h2', '===' => 'h3' );
	private $pvars = array ('#title\s(.+)[^(\n|\r)]' => '', '#restrict\s([a-z0-9,]+)' => '', '#attachment\s(.+)[^(\n|\r)]' => '', '#version' => '' );
	//Protection Array
	private $code_matches = array ();
	private $php_matches = array ();
	private $attachment_matches = array ();
	//BBCode Tags
	private $tags = array ('b' => 'strong', 'i' => 'em', 'u' => 'span style="text-decoration:underline"', 'quote' => 'blockquote', 's' => 'span style="text-decoration: line-through"', 'right' => 'span style="float:right"', 'center' => 'div align="center"', 'list' => 'ul', '\*' => 'li', 'code' => 'pre' );
	//Tags that must be mapped to diffierent parts
	private $mapped = array ('url' => array ('a', 'class="external" href', true ), 'img' => array ('img alt=""', 'src', false ) );
	//Tags with atributes
	private $tags_with_att = array ('color' => array ('font', 'color' ), 'size' => array ('font', 'size' ), 'url' => array ('a', 'class="external" href' ) );
	//Gotta have smilies
	private $smilies = array ('(:\)|:-\()' => 'smile.gif', '(:\(|:-\()' => 'sad.gif', '(;D|;-D)' => 'biggrin.gif', '(:\'\(|:"\()' => 'cry.gif', '(:p|:-p)' => 'tongue.gif', '(:o|:-0)' => 'suprised.gif', '(:@|\>:\(|\>:\))' => 'mad.gif', ':s' => 'confused.gif' );
	// Vevision number
	private $rev;
	//Dynamic Functions
	function BB_Code($new = true, $parse = true, $links = true) {
		$this->convert_newlines = $new;
		$this->parse_smilies = $parse;
		$this->auto_links = $links;
	}
	function Link_Code($matches) {
		$init = (($this->_config ['rewrite'] == true) ? "" : "?page=");
		$validate = ((file_exists ( "./pages/" . strtolower ( str_replace ( "/", ";", $matches [1] ) ) . ".txt" )) ? "" : "title=\"#404 - " . date ( 'Y-m-d g:i:s A' ) . "\"");
		return "<a href=\"" . WC_SELF . "/" . $init . strtolower ( $matches [1] ) . "\" " . $validate . ">" . (isset ( $matches [2] ) ? $matches [2] : $matches [1]) . "</a>";
	}
	function clean_pre($matches) {
		if (is_array ( $matches )) {
			$text = $matches [1] . $matches [2] . "</pre>";
		} else {
			$text = $matches;
		}
		$text = str_replace ( '<br />', '', $text );
		$text = str_replace ( '<p>', "\n", $text );
		$text = str_replace ( '</p>', '', $text );
		return $text;
	}
	private function runCallbacks($str = '') {
		if (is_array ( self::$_callbackArray [get_class ()] ) && ($res = $this->_usrCallback ( get_class (), array ($str ) )) !== false) {
			return $res;
		} else {
			return $str;
		}
	}
	//Constructor
	function parse($code, $rev = HEAD) {
		// Set page version if set
		$this->rev = $rev;
		// Run user callbacks on raw source
		$this->_code = $this->runCallbacks ( $code );
		$this->_select_revision ();
		$this->_strip_code ();
		$this->_parse_tags ();
		$this->_parse_internal ();
		$this->_parse_mapped ();
		$this->_parse_tags_with_att ();
		$this->_parse_links ();
		$this->_parse_smilies ();
		$this->_insert_code ();
		$this->_convert_nl ();
		return $this->_code;
	}
	
	//Parser Functions
	public function _select_revision() {
		preg_match_all ( "/\n@@diff:([0-9]+):(.+?)\n/", $this->_code, $datArray );
		if ($this->rev == HEAD) {
			return;
		}
	}
	function _strip_code() {
		// Filter file
		$this->_code = preg_replace ( '{^\xEF\xBB\xBF|\x1A}', '', $this->_code );
		$this->_code = preg_replace ( '{\r\n?}', "\n", $this->_code );
		//Code Tags
		preg_match_all ( '/\[code\](.+?)\[\/code\]/ism', preg_replace ( "/\\\$([0-9]+?)/", "#$1", $this->_code ), $this->code_matches );
		$this->_code = preg_replace ( '/\[code\](.+?)\[\/code\]+/ism', '::code::', $this->_code );
		//Inline php
		preg_match_all ( '/\<\?php(.+?)\?\>/is', $this->_code, $this->php_matches, PREG_SET_ORDER );
		$this->_code = preg_replace ( '/\<\?php(.+?)\?\>/is', '::Bin::', $this->_code );
		//Attachments
		preg_match_all ( '/\#attachment\s(.*)[^\n]?/', $this->_code, $this->attachment_matches );
		//Code Cleaning
		$this->_code = preg_replace ( '/&(?!amp;)/', '&amp;', $this->_code );
		$this->_code = preg_replace ( '/\^\^(.|[\r\n])*?\^\^/', "", $this->_code );
		global $sys;
		$this->_code = preg_replace ( '/\#version/i', substr ( $sys ['version'], 0, 6 ), $this->_code );
		
		return true;
	}
	function _parse_tags() {
		foreach ( $this->tags as $old => $new ) {
			$ex = explode ( ' ', $new );
			$this->_code = preg_replace ( '/\[' . $old . '\](.+?)\[\/' . $old . '\]/', '<' . $new . '>$1</' . $ex [0] . '>', $this->_code );
		}
	}
	function _parse_internal() {
		if (count ( $this->attachment_matches [1] ) > 0) {
			$this->_code .= "<table id=\"attachments\"><thead><tr><th>Attachments</th></tr></thead><tbody><tr>\n";
			foreach ( $this->attachment_matches [1] as $attachment ) {
				if (file_exists ( 'uploads/' . $attachment )) {
					$this->_code .= "\t<td><a href=\"" . WC_SELF . "/uploads/" . $attachment . "\">" . $attachment . "</a> " . (filesize ( 'uploads/' . $attachment ) / 1000) . "kb</td>\n";
				}
			}
			$this->_code .= "</tr></tbody></table>";
		}
		foreach ( $this->pvars as $tag => $blank ) {
			$this->_code = preg_replace ( '/' . $tag . '/', $blank, $this->_code );
		}
		foreach ( $this->wiki as $old => $new ) {
			$ex = explode ( ' ', $new );
			$this->_code = preg_replace ( '/' . $old . '(.+?)' . $old . '/is', '<' . $new . '>$1</' . $ex [0] . '>', $this->_code );
		}
	}
	function _parse_mapped() {
		foreach ( $this->mapped as $tag => $data ) {
			$reg = '/\[' . $tag . '\](.+?)\[\/' . $tag . '\]/is';
			if ($data [2]) {
				$this->_code = preg_replace ( $reg, '<' . $data [0] . ' ' . $data [1] . '="$1">$1</' . $data [0] . '>', $this->_code );
			} else {
				$this->_code = preg_replace ( $reg, '<' . $data [0] . ' ' . $data [1] . '="$1" />', $this->_code );
			}
		}
	}
	function _parse_tags_with_att() {
		foreach ( $this->tags_with_att as $tag => $data ) {
			$this->_code = preg_replace ( '/\[' . $tag . '=(.+?)\](.+?)\[\/' . $tag . '\]/is', '<' . $data [0] . ' ' . $data [1] . '="$1">$2</' . $data [0] . '>', $this->_code );
		}
	}
	function _parse_smilies() {
		if ($this->_config ['smilies'] == true) {
			foreach ( $this->smilies as $s => $im ) {
				$this->_code = preg_replace ( '/[^"\'a-z0-9]' . $s . '(?:\s)?/i', '<img src="' . WC_SELF . '/paper/smileys/' . $im . '" alt="img-' . $im . '" />', $this->_code );
			}
		}
	}
	function _parse_links() {
		if ($this->_config ['autolinks'] == true) {
			$this->_code = preg_replace ( '/([^"\>])(http:\/\/|ftp:\/\/)([^\s,]*)(?=.*[\<])/i', '$1<a class="external" href="$2$3">$2$3</a>$4', $this->_code );
			$this->_code = preg_replace ( '/([^"\>])([A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4})/i', '$1<a class="external" href="mailto:$2">$2</a>', $this->_code );
		}
		$this->_code = preg_replace_callback ( '/\{\{(.+?)(?:\|(.+?))?\}\}/i', array ($this, 'Link_Code' ), $this->_code );
	}
	function _convert_nl() {
		$br = true;
		if (trim ( $this->_code ) === '') {
			return 0;
		}
		if ($this->_config ['nl'] == false) {
			return 0;
		}
		$this->_code = $this->_code . "\n";
		$this->_code = preg_replace ( '|<br />\s*<br />|', "\n\n", $this->_code );
		$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
		$this->_code = preg_replace ( '!(<' . $allblocks . '[^>]*>)!', "\n$1", $this->_code );
		$this->_code = preg_replace ( '!(</' . $allblocks . '>)!', "$1\n\n", $this->_code );
		if (strpos ( $this->_code, '<object' ) !== false) {
			$this->_code = preg_replace ( '|\s*<param([^>]*)>\s*|', "<param$1>", $this->_code );
			$this->_code = preg_replace ( '|\s*</embed>\s*|', '</embed>', $this->_code );
		}
		$this->_code = preg_replace ( "/\n\n+/", "\n\n", $this->_code ); // take care of duplicates
		// make paragraphs, including one at the end
		$data = preg_split ( '/\n\s*\n/', $this->_code, - 1, PREG_SPLIT_NO_EMPTY );
		$this->_code = '';
		foreach ( $data as $tinkle ) {
			$this->_code .= '<p>' . trim ( $tinkle, "\n" ) . "</p>\n";
		}
		$this->_code = preg_replace ( '|<p>\s*</p>|', '', $this->_code ); // under certain strange conditions it could create a P of entirely whitespace
		$this->_code = preg_replace ( '!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $this->_code );
		$this->_code = preg_replace ( '!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $this->_code ); // don't pee all over a tag
		$this->_code = preg_replace ( "|<p>(<li.+?)</p>|", "$1", $this->_code ); // problem with nested lists
		$this->_code = preg_replace ( '|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $this->_code );
		$this->_code = str_replace ( '</blockquote></p>', '</p></blockquote>', $this->_code );
		$this->_code = preg_replace ( '!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $this->_code );
		$this->_code = preg_replace ( '!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $this->_code );
		if ($br) {
			$this->_code = preg_replace_callback ( '/<(script|style).*?<\/\\1>/s', create_function ( '$matches', 'return str_replace("\n", "<WPPreserveNewline />", $matches[0]);' ), $this->_code );
			$this->_code = preg_replace ( '|(?<!<br />)\s*\n|', "<br />\n", $this->_code ); // optionally make line breaks
			$this->_code = str_replace ( '<WPPreserveNewline />', "\n", $this->_code );
		}
		$this->_code = preg_replace ( '!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $this->_code );
		$this->_code = preg_replace ( '!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $this->_code );
		if (strpos ( $this->_code, '<pre' ) !== false) {
			$this->_code = preg_replace_callback ( '!(<pre[^>]*>)(.*?)</pre>!is', array ($this, 'clean_pre' ), $this->_code );
		}
		$this->_code = preg_replace ( "|\n</p>$|", '</p>', $this->_code );
	}
	function _insert_code() {
		foreach ( $this->code_matches [0] as $match ) {
			$this->_code = preg_replace ( '/::code::/', preg_replace ( "/&amp;/i", "&", htmlentities ( $match ) ), $this->_code, 1 );
			$this->_code = preg_replace ( "/#([0-9]+?)/", "\\\$$1", preg_replace ( '/\[code\](.+?)\[\/code\]/is', '<pre><code>$1</code></pre>', $this->_code ) );
		}
		foreach ( $this->php_matches as $match ) {
			$this->_code = preg_replace ( '/::Bin::/', '<?php' . $match [1] . '?>', $this->_code, 1 );
		}
		return true;
	}
	function addwiki($old, $new) {
		$this->wiki [$old] = $new;
	}
	function addpvars($v) {
		$this->pvars [$v] = NULL;
	}
	function addTag($old, $new) {
		$this->tags [$old] = $new;
	}
	function addMapped($bb, $html, $att, $end = true) {
		$this->mapped [$bb] = array ($html, $att, $end );
	}
	function addTagWithAttribute($bb, $html, $att) {
		$this->tags_with_att [$bb] = array ($html, $att );
	}
	function addSmiley($code, $src) {
		$this->smilies [$code] = $src;
	}
}
?>