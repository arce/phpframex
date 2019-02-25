<?php
    
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
    } else {
        $maps = null;
        $uri = strpos($params[0], '/') === 0 ? $params[0] : '/' . $params[0];
        $callback = $params[1];
    }

    array_push(self::$maps, $maps);
    array_push(self::$routes, $uri);
    array_push(self::$methods, strtoupper($method));
    array_push(self::$callbacks, $callback);
  }

  public static function resource($uri,$controller) {
    self::get($uri, $controller.'@index');
    self::get($uri.'/create', $controller.'@create');
    self::post($uri, $controller.'@store');
    self::get($uri.'/(:string)', $controller.'@show');
    self::get($uri.'/(:string)/edit',$controller.'@edit');
    self::put($uri.'/(:string)',$controller.'@update');
    self::delete($uri.'/(:string)',$controller.'@destroy');
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

  /**
   * Runs the callback for the given request
   */
  public static function dispatch(){
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];

    $searches = array_keys(static::$patterns);
    $replaces = array_values(static::$patterns);

    $found_route = false;

    self::$routes = preg_replace('/\/+/', '/', self::$routes);

    // Check if route is defined without regex
    if (in_array($uri, self::$routes)) {
      $route_pos = array_keys(self::$routes, $uri);
      foreach ($route_pos as $route) {

        // Using an ANY option to match both GET and POST requests
        if (self::$methods[$route] == $method || self::$methods[$route] == 'ANY' || (!empty(self::$maps[$route]) && in_array($method, self::$maps[$route]))) {
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
            $controller->{$segments[1]}();

            if (self::$halts) return;
          } else {
            // Call closure
            call_user_func(self::$callbacks[$route]);

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
          if (self::$methods[$pos] == $method || self::$methods[$pos] == 'ANY' || (!empty(self::$maps[$pos]) && in_array($method, self::$maps[$pos]))) {
            $found_route = true;

            // Remove $matched[0] as [1] is the first parameter.
            array_shift($matched);

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
                call_user_func_array(array($controller, $segments[1]), $matched);
              }

              if (self::$halts) return;
            } else {
              call_user_func_array(self::$callbacks[$pos], $matched);

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
          self::$error_callback = null;
          self::dispatch();
          return ;
        }
      }
      call_user_func(self::$error_callback);
    }
  }
}

function redirect($url, $statusCode = 303) {
  header('Location: ' . $url, true, $statusCode);
  die();
}

/**
* Template engine
* @author  Ruslan Ismagilov <ruslan.ismagilov.ufa@gmail.com>
*/
class Template {
    /**
     * Content variables
     * @access private
     * @var array
     */
    private $vars = array();

    /**
     * Content delimiters
     * @access private
     * @var string
     */
    private $l_delim = '{{', $r_delim = '}}';

    /**
     * Set template property in template file
     * @access public
     * @param string $key property name
     * @param string $value property value
     */
    public function assign( $key, $value ) {
        $this->vars[$key] = $value;
    }
    
    /**
     * Parce template file
     * @access public
     * @param string $template_file
     */
    public function parse( $template_file ) {
        if ( file_exists( $template_file ) ) {
            $content = file_get_contents($template_file);
            foreach ( $this->vars as $key => $value ) {
                $$key = $value;
                if ( is_array( $value ) || is_bool( $value) ) {
                    $content = $this->parsePair($key, $value, $content);
                } else {
                    $content = $this->parseSingle($key, (string) $value, $content);
                }
            }
            try {
                eval('?> ' . $content . '<?php ' );
            } catch (Throwable $t) {
                $content = null;
            }
        } else {
            exit( '<h1>Template error</h1>' );
        }
    }

    /**
     * Parsing content for single varliable
     * @access private
     * @param string $key property name
     * @param string $value property value
     * @param string $string content to replace
     * @param integer $index index of loop item
     * @return string replaced content
     */
    private function parseSingle( $key, $value, $string, $index = null ) {
        if ( isset( $index ) ) {
            $string = str_replace( $this->l_delim . '.' . $this->r_delim, $index, $string );
        }
        return str_replace( $this->l_delim . $key . $this->r_delim, strip_tags($value), $string );
    }

    /**
     * Parsing content for loop varliable
     * @access private
     * @param string $variable loop name
     * @param string $value loop data
     * @param string $string content to replace
     * @return string replaced content
     */
    private function parsePair( $variable, $data, $string ) {
        $match = $this->matchPair($string, $variable);
        if( $match == false ) return $string;
        
        if (is_bool($data)) {
            $start = $this->l_delim . '#'. $variable . $this->r_delim;
            $end = $this->l_delim . '\/'. $variable . $this->r_delim;
            $endx = $this->l_delim . '/'. $variable . $this->r_delim;
            if ($data==false)
                $string = preg_replace('/'.$start.'[\s\S]+?'.$end.'/', '', $string);
            else {
                $string = str_replace($start,'',$string);
                $string = str_replace($endx,'',$string);
            }
            return $string;
        }
        
        $str = '';
        foreach ( $data as $k_row => $row ) {
            $temp = $match['1'];
            foreach( $row as $key => $val ) {
                if( !is_array( $val ) ) {
                    $index = array_search( $k_row, array_keys( $data ) );
                    $temp = $this->parseSingle( $key, $val, $temp, $index );
                } else {
                    $temp = $this->parsePair( $key, $val, $temp );
                }
            }
            $str .= $temp;
        }

        return str_replace( $match['0'], $str, $string );
    }

    /**
     * Match loop pair
     * @access private
     * @param string $string content with loop
     * @param string $variable loop name
     * @return string matched content
     */
    private function matchPair( $string, $variable ) {
        if ( !preg_match("|" . preg_quote($this->l_delim) . '#' . $variable . preg_quote($this->r_delim) . "(.+?)". preg_quote($this->l_delim) . '/' . $variable . preg_quote($this->r_delim) . "|s", $string, $match ) ) {
            return false;
        }

        return $match;
    }
}
        
function view($filename,$variables=[]) {
    if (!isset($template)) {
      $template = new Template();
    }
    foreach ($variables as $key => $value) {
      $template->assign($key,$value);
    }
    $template->parse('views/'.$filename.'.php');
}

class Input {
  public static $routes = array();

  public static function get($name) {
      return $_REQUEST[$name];
  }
}
