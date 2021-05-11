<?php
/**
 * Session Facade
 * @author  Armando Arce <armando.arce@gmail.com>
 */

class Session {

  public static function put($key,$value) {
	global $sessions;
	$sessions[$key] = $value;
  }
  
  public static function get($key) {
	if (isset($_SESSION[$key])) {
	  return $_SESSION[$key];
	} else return NULL;
  }
  
  public static function forget($key) {
	global $sessions;
	$sessions[$key] = NULL;
  }
  
  public static function pull($key) {
  }
  
  public static function has($key) {
	return isset($_SESSION[$key]);
  }
  
  public static function exists($key) {
	return isset($_SESSION[$key]);
  }
}
?>
