<?php
/**
 * Redirect Facade
 * @author  Armando Arce <armando.arce@gmail.com>
 */

class Redirect {

	static function to($to = null, $status = 302, $headers = [], $secure = null) {
	    global $redirect;
	    $redirect = $url;		
	}
}

function redirect($to = null, $status = 302, $headers = [], $secure = null) {
  global $redirect;
  $redirect = $url;
}

?>
