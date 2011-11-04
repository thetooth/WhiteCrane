<?php

//  Copyright 2005-2010 Ameoto Systems. All Rights Reserved.
//  Written by Jeffrey Jenner, thetooth@ameoto.com


if (! defined ( "Init" )) {
	die ( "For security reasons you can not run this component directly." );
}

WC::$ApplicationInfo ['WCCache'] = array ('Description' => "WhiteCrane abstraction layer for caching. Currently supported accelerators: XCache.", 'Version' => '0.0.0', 'Url' => 'http://thetooth.name', 'Author' => "Jeffrey Jenner, thetooth@ameoto.com" );

// Main
class WCCache {
	public $_;
	public function __construct($int = 'Disk', $subint = '') {
		$int = 'WCCache_' . $int;
		if (($this->_ = new $int ( $subint )) === false) {
			return - 1;
		}
		return 0;
	}
}
// Interface for remote cache services
interface WCCacheInterface {
	public function _put($key, $value, $ttl = 0);
	public function _set($key, $value, $ttl = 0);
	public function _get($key);
	public function _del($key);
	public function _disconnect();
}
// XCache
class WCCache_XCache implements WCCacheInterface {
	private $identifier = '';
	
	// Check if xcache is alive and set the identifier
	public function __construct($identifier = '') {
		if (! function_exists ( 'xcache_get' )) {
			$this->crashed = true;
			return false;
		}
		$this->identifier = $identifier;
	}
	// Put data into remote cache
	public function _put($key, $value, $ttl = 0) {
		$ttl = $ttl > 0 ? intval ( $ttl ) : '';
		if ($ttl) {
			return xcache_set ( md5 ( $this->identifier . $key ), $value, $ttl );
		} else {
			return xcache_set ( md5 ( $this->identifier . $key ), $value );
		}
	}
	// Retrieve a value from remote cache store
	public function _get($key) {
		$return_val = "";
		if (xcache_isset ( md5 ( $this->identifier . $key ) )) {
			$return_val = xcache_get ( md5 ( $this->identifier . $key ) );
		}
		return $return_val;
	}
	// Update value in remote cache store
	public function _set($key, $value, $ttl = 0) {
		$this->removeFromCache ( $key );
		return $this->putInCache ( $key, $value, $ttl );
	}
	// Delete value in remote cache
	public function _del($key) {
		return xcache_unset ( md5 ( $this->identifier . $key ) );
	}
	// Nothing to do here
	public function _disconnect() {
		return true;
	}
}
// Fallback
class WCCache_Disk implements WCCacheInterface {
	private $identifier = '';
	private $root = 'paper/cache/';
	
	public function __construct($identifier = '') {
		if (! is_writeable ( $this->root )) {
			$this->crashed = true;
			return false;
		}
		if (! file_exists ( $this->root . 'diskcache_lock.php' )) {
			$fh = @fopen ( $this->root . 'diskcache_lock.php', 'wb' );
			if ($fh) {
				flock ( $fh, LOCK_EX );
				fwrite ( $fh, 0 );
				flock ( $fh, LOCK_UN );
				fclose ( $fh );
			}
		}
		
		$this->identifier = $identifier;
	}
	public function _put($key, $value, $ttl = 0) {
		$lock = @fopen ( $this->root . 'diskcache_lock.php', 'wb' );
		if ($lock) {
			flock ( $lock, LOCK_EX );
		}
		$fh = @fopen ( $this->root . md5 ( $this->identifier . $key ) . '.php', 'wb' );
		if (! $fh) {
			return false;
		}
		$extra_flag = "";
		if (is_array ( $value )) {
			$value = serialize ( $value );
			$extra_flag = "\n" . '$is_array = 1;' . "\n\n";
		}
		$extra_flag .= "\n" . '$ttl = ' . $ttl . ";\n\n";
		$value = '"' . addslashes ( $value ) . '"';
		$file_content = "<?" . "php\n\n" . '$value = ' . $value . ";\n" . $extra_flag . "\n?" . '>';
		flock ( $fh, LOCK_EX );
		fwrite ( $fh, $file_content );
		flock ( $fh, LOCK_UN );
		fclose ( $fh );
		@chmod ( $this->root . md5 ( $this->identifier . $key ) . '.php', 0777 );
		
		flock ( $lock, LOCK_UN );
		fclose ( $lock );
		return true;
	}
	public function _get($key) {
		$lock = @fopen ( $this->root . 'diskcache_lock.php', 'wb' );
		if ($lock) {
			flock ( $lock, LOCK_SH );
		}
		$return_val = "";
		if (file_exists ( $this->root . md5 ( $this->identifier . $key ) . '.php' )) {
			$value = false;
			require $this->root . md5 ( $this->identifier . $key ) . '.php';
			$return_val = stripslashes ( $value );
			if (isset ( $is_array ) && $is_array == 1) {
				$return_val = unserialize ( $return_val );
			}
			if (isset ( $ttl ) && $ttl > 0) {
				if (($mtime = filemtime ( $this->root . md5 ( $this->identifier . $key ) . '.php' )) === true) {
					if (time () - $mtime > $ttl) {
						@unlink ( $this->root . md5 ( $this->identifier . $key ) . '.php' );
						return false;
					}
				}
			}
		}
		flock ( $lock, LOCK_UN );
		fclose ( $lock );
		return $return_val;
	}
	public function _set($key, $value, $ttl = 0) {
		return $this->_put ( $key, $value, $ttl );
	}
	public function _del($key) {
		$lock = @fopen ( $this->root . 'diskcache_lock.php', 'wb' );
		
		if ($lock) {
			flock ( $lock, LOCK_EX );
		}
		if (file_exists ( $this->root . md5 ( $this->identifier . $key ) . '.php' )) {
			@unlink ( $this->root . md5 ( $this->identifier . $key ) . '.php' );
		}
		flock ( $lock, LOCK_UN );
		fclose ( $lock );
	}
	// Not used
	public function _disconnect() {
		return true;
	}
}
?>