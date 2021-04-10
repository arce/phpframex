<?php
/**
 * View Facade
 * @author  Armando Arce <armando.arce@gmail.com>
 */

class View{

  public static function make($filename,$variables=[]) {
     view($filename,$variables);
  }
  
  public static function exists($filename) {
     return (file_exists('views/'.$filename.'.html') || 
		     file_exists('views/'.$filename.'.php'));
  }

}
?>
