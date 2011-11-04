<?php
class pluginExample extends WCParser implements WCAPI {
	public function test($str) {
		return preg_replace ( "/whitecrane/i", "MaxPayne", $str );
	}
	public function _init() {
		$this->_addCall ( ParserAPI, get_class (), "test" );
	}
}
?>