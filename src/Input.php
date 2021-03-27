<?php
/**
 * Input Class
 * @author  Armando Arce <armando.arce@gmail.com>
 */

class Input {

  public static function get($name) {
	return htmlspecialchars($_REQUEST[$name]);
  }
  
  public static function file($name) {
	if (isset($_FILES[$name])) {
	  if ($_FILES[$name]["error"] > 0) {
		 return false;
	  } else {
	     $storagename = $name . ".dat";
	     move_uploaded_file($_FILES[$name]["tmp_name"], "upload/" . $storagename);
	     return "upload/" . $storagename;
	   }
	 } else {
	   return false;
	 }
  }
}
?>