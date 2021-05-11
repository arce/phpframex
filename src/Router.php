<?php

$sessions = [];
$cookies = [];
$redirect = null;
$type = 'html';
$accept = [];

/**
 * @method static Macaw get(string $route, Callable $callback)
 * @method static Macaw post(string $route, Callable $callback)
 * @method static Macaw put(string $route, Callable $callback)
 * @method static Macaw delete(string $route, Callable $callback)
 * @method static Macaw options(string $route, Callable $callback)
 * @method static Macaw head(string $route, Callable $callback)
 */
class Route {
  public static $halts = false;
  public static $routes = array();
  public static $methods = array();
  public static $callbacks = array();
  public static $maps = array();
  public static $formats = array();
  public static $patterns = array(
      ':number' => '[0-9]+',
      ':alpha' => '[A-Za-z]+',
      ':string' => '[^/]+',
      ':all' => '.*'
  );
  public static $error_callback;

  /**
   * Defines a route w/ callback and method
   */
  public static function __callstatic($method, $params) {

    if ($method == 'map') {
        $maps = array_map('strtoupper', $params[0]);
        $uri = strpos($params[1], '/') === 0 ? $params[1] : '/' . $params[1];
        $callback = $params[2];
         if (isset($params[3]))
          $formats = $params[3];
        else
          $formats = 'html';
    } else {
        $maps = null;
        $uri = strpos($params[0], '/') === 0 ? $params[0] : '/' . $params[0];
        $callback = $params[1];
        if (isset($params[2]))
          $formats = $params[2];
        else
          $formats = 'html';
    }

    array_push(self::$maps, $maps);
    array_push(self::$routes, $uri);
    array_push(self::$formats, $formats);
    array_push(self::$methods, strtoupper($method));
    array_push(self::$callbacks, $callback);
  }

  public static function resource($uri,$controller,$format='html') {
    self::get($uri, $controller.'@index',$format);
    self::get($uri.'/create', $controller.'@create',$format);
    self::post($uri, $controller.'@store',$format);
    self::get($uri.'/(:number)', $controller.'@show',$format);
    self::get($uri.'/(:number)/edit',$controller.'@edit',$format);
    self::put($uri.'/(:number)',$controller.'@update',$format);
    self::delete($uri.'/(:number)',$controller.'@destroy',$format);
  }

  /**
   * Defines callback if route is not found
  */
  public static function error($callback) {
    self::$error_callback = $callback;
  }

  public static function haltOnMatch($flag = true) {
    self::$halts = $flag;
  }

  public static function dispatch() {
	global $sessions;
	global $cookies;
	global $redirect;
	global $type;
	global $accept;

    $content = self::_dispatch();
    
	foreach ($sessions as $key => $value) {
	  if ($value==null && isset($_SESSION[$key]))
	    unset($_SESSION[$key]);
	  else
	    $_SESSION[$key] = $value;
	}
	  
	foreach ($cookies as $key => $value) {
	  if ($value==null)
	    setcookie($key,"",time() - 3600);
	  else
	    setcookie($key,$value);
	}

	if ($content!=null) {
	  if (in_array('text/html',$accept)||
	      in_array('*/*',$accept)) {
	    if (is_array($content)) {
		  header('Content-Type: text/html');
	      echo to_html($content);
	    } else echo $content;
	  } else if (in_array('application/json',$accept)) {
		if (is_array($content)) {
		  header('Content-Type: application/json');
	      echo json_encode($content);
	    } else echo $content;
	  } else if (in_array('application/xml',$accept)) {
		if (is_array($content)) {
		  header('Content-Type: application/xml');
		  $str = '<data></data>';
	      $xml = new SimpleXMLElement($str,0,false);
          to_xml($xml, $content);
          echo $xml->asXML();
        } else echo $content;
	  }
	}

	if ($redirect!=null)
	  header('Location: ' . $redirect, true, 303);
  }
  
  /**
   * Runs the callback for the given request
   */
  public static function _dispatch(){
	global $type;
	global $accept;
	
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    $type = isset($_SERVER['CONTENT_TYPE'])? $_SERVER['CONTENT_TYPE']:'text/html';
    $accept = isset($_SERVER['HTTP_ACCEPT'])? explode(",",$_SERVER['HTTP_ACCEPT']):'text/html';
    $searches = array_keys(static::$patterns);
    $replaces = array_values(static::$patterns);

    $found_route = false;
    parse_str(file_get_contents("php://input"), $_REQUEST);

    if ($type=='application/json')
       $_REQUEST = json_decode(file_get_contents('php://input'), True);
    else if ($type=='application/xml')
       $_REQUEST = simplexml_load_string(file_get_contents('php://input'));

	if (isset($_REQUEST['_method'])) {
	  $method = $_REQUEST['_method'];
	  unset($_REQUEST['_method']);
    }
	
    self::$routes = preg_replace('/\/+/', '/', self::$routes);

    // Check if route is defined without regex
    if (in_array($uri, self::$routes)) {
      $route_pos = array_keys(self::$routes, $uri);
      foreach ($route_pos as $route) {

        // Using an ANY option to match both GET and POST requests
        if (self::$methods[$route] == $method || 
            self::$methods[$route] == 'ANY' || 
           (!empty(self::$maps[$route]) && 
           in_array($method, self::$maps[$route]))) {
	
          $found_route = true;

          // If route is not an object
          if (!is_object(self::$callbacks[$route])) {

            // Grab all parts based on a / separator
            $parts = explode('/',self::$callbacks[$route]);

            // Collect the last index of the array
            $last = end($parts);

            // Grab the controller name and method call
            $segments = explode('@',$last);

            require_once('controllers/'.$segments[0].'.php');
            
            // Instanitate controller
            $controller = new $segments[0]();

            // Call method
            return $controller->{$segments[1]}($_REQUEST);

            if (self::$halts) return;
          } else {
            // Call closure
            return call_user_func(self::$callbacks[$route]);

            if (self::$halts) return;
          }
        }
      }
    } else {
      // Check if defined with regex
      $pos = 0;
      foreach (self::$routes as $route) {
        if (strpos($route, ':') !== false) {
          $route = str_replace($searches, $replaces, $route);
        }

        if (preg_match('#^' . $route . '$#', $uri, $matched)) {
          if (self::$methods[$pos] == $method || 
              self::$methods[$pos] == 'ANY' || 
             (!empty(self::$maps[$pos]) && 
             in_array($method, self::$maps[$pos]))) {
		
            $found_route = true;
            
            // Remove $matched[0] as [1] is the first parameter.
            array_shift($matched);
            
            if ($_REQUEST!=[])
              $matched = array_merge(array($_REQUEST),$matched);

            if (!is_object(self::$callbacks[$pos])) {

              // Grab all parts based on a / separator
              $parts = explode('/',self::$callbacks[$pos]);

              // Collect the last index of the array
              $last = end($parts);

              // Grab the controller name and method call
              $segments = explode('@',$last);

              require_once('controllers/'.$segments[0].'.php');
              
              // Instanitate controller
              $controller = new $segments[0]();

              // Fix multi parameters
              if (!method_exists($controller, $segments[1])) {
                echo "controller and action not found";
              } else {
                return call_user_func_array(array($controller,$segments[1]), $matched);
              }

              if (self::$halts) return;
            } else {
              return call_user_func_array(self::$callbacks[$pos], $matched);

              if (self::$halts) return;
            }
          }
        }
        $pos++;
      }
    }

    // Run the error callback if the route was not found
    if ($found_route == false) {
      if (!self::$error_callback) {
        self::$error_callback = function() {
          header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
          echo '404';
        };
      } else {
        if (is_string(self::$error_callback)) {
          self::get($_SERVER['REQUEST_URI'], self::$error_callback);
          self::$error_callback = NULL;
          self::dispatch();
          return ;
        }
      }
      call_user_func(self::$error_callback);
    }
  }
}

function to_xml(SimpleXMLElement $object, array $data) {   
    foreach ($data as $key => $value) {
        if (is_array($value)) {
			if ($key == (int)$key) $key = "item";
            $new_object = $object->addChild($key);
            to_xml($new_object, $value);
        } else {
            if ($key != (int)$key) $key = "key_$key";
            $object->addChild($key, $value);
        }   
    }   
}   

function to_html($array) {
    // start table
    $html = '<table>';
    // header row
    $html .= '<tr>';
    foreach($array[0] as $key=>$value){
            $html .= '<th>' . htmlspecialchars($key) . '</th>';
        }
    $html .= '</tr>';

    // data rows
    foreach( $array as $key=>$value){
        $html .= '<tr>';
        foreach($value as $key2=>$value2){
            $html .= '<td>' . htmlspecialchars($value2) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</table>';
    return $html;
}

function redirect($to = NULL, $status = 302, $headers = [], $secure = NULL) {
  global $redirect;
  $redirect = $to;
}

?>
