<?php
/**
 * Cookie Facade
 * @author  Armando Arce <armando.arce@gmail.com>
 */

class Cookie {
  
  public static function get($key,$default = NULL) {
	return $_COOKIE[$key];
  }
  
  public static function has($key) {
	return isset($_COOKIE[$key]);
  }
  
  public static function queue($key,$value,$time) {
	global $cookies;
	$cookies[$key] = $value;
  }
  
  public static function forget($key) {
	global $cookies;
	$cookies[$key] = NULL;
  }
}
?>
