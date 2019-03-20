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

  public static function dispatch() {
	$cookies = $GLOBALS['cookies'];
	
	if (isset($cookies))
	  foreach ($cookies as $key => $value)
	    setcookie($key,$value);
	
	$sessions = $GLOBALS['sessions'];
	if (isset($sessions))
	  foreach ($sessions as $key => $value)
	    $_SESSION[$key] = $value;
	
	session_start();
	
	$content = self::_dispatch();
	
	echo $content;
  }
  
  /**
   * Runs the callback for the given request
   */
  public static function _dispatch(){
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
            return $controller->{$segments[1]}();

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
                return call_user_func_array(array($controller, $segments[1]), $matched);
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
?>
<?php

/**
* Template engine
* @author  Ruslan Ismagilov <ruslan.ismagilov.ufa@gmail.com>
* @contributor  Armando Arce <armando.arce@gmail.com>
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
    
    public function countDim($array) {
	  if (is_bool($array)) return 0;
      if (is_array(reset($array)))
        $dim = $this->countDim(reset($array)) + 1;
      else
        $dim = 1;
      return $dim;
    }

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
            while (true) {
              $start = strpos($content,'{{>');
              if ($start === false) break;
              $end = strpos($content,'}}',$start);
              if ($end === false) break;
              $partial_file = substr($content,$start+4,$end-$start-4);
              $partial = file_get_contents('views/'.$partial_file.'.php');
              $partial_tag = substr($content,$start,$end-$start+2);
              $content = str_replace($partial_tag,$partial,$content);
		    }
            foreach ( $this->vars as $key => $value ) {
                $$key = $value;
                if ( is_array( $value ) || is_bool( $value) ) {
					if ($this->countDim($value)<=1) $value = [$value];
                    $content = $this->parsePair($key, $value, $content);
                } else {
                    $content = $this->parseSingle($key, (string) $value, $content);
                }
            }
            return $content;
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
        if (!$match) return $string;
        
        if (is_bool($data[0])) {
			$start = $this->l_delim . '#'. $variable . $this->r_delim;
			$end = $this->l_delim . '\/'. $variable . $this->r_delim;
            $endx = $this->l_delim . '/'. $variable . $this->r_delim;
            
            if (!$data[0])
              $string = preg_replace('/'.$start.'[\s\S]+?'.$end.'/', '', $string);
            else {
			  $string = str_replace($start,'',$string);
              $string = str_replace($endx,'',$string);
			}

            $start = $this->l_delim . '^'. $variable . $this->r_delim;
			$startx = $this->l_delim . '\^'. $variable . $this->r_delim;
            $end = $this->l_delim . '~'. $variable . $this->r_delim;
            
            if ($data[0])
              $string = preg_replace('/'.$startx.'[\s\S]+?'.$end.'/', '', $string);
            else {
			  $string = str_replace($start,'',$string);
              $string = str_replace($end,'',$string);
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
    return $template->parse('views/'.$filename.'.php');
}
?>
<?php
/**
 * Query Class
 * @author  Armando Arce <armando.arce@gmail.com>
*/

class Query {

  public $params = [];
  
  public function where($field,$value,$extra=null) {
	$this->params['where'] = [$field=>$value];
	return $this;
  }
  
  public function orWhere($field,$value,$extra=null) {
	$this->params['where'] = [$field=>$value];
	return $this;
  }
  
  public function distinct() {
	$this->params['distinct'] = true;
	return $this;
  }
  
  public function orderBy($field,$ord) {
	if (!array_key_exists('fields',$this->params))
	  $this->params['fields'] = [];
	if (is_string($fields))
	  $fields = [$fields];
	$this->params['fields'] = array_merge($this->params['fields'],$fields);
	return $this;
  }
  
  public function groupBy($fields) {
	if (!array_key_exists('group',$this->params))
	  $this->params['group'] = [];
	if (is_string($fields))
	  $fields = [$fields];
	$this->params['group'] = array_merge($this->params['group'],$fields);
	return $this;
  }
  
  public function having($field) {
	return $this;
  }
  
  public function skip($num) {
	$this->params['skip'] = $num;
	return $this;
  }
  
  public function limit($num) {
	$this->params['limit'] = $num;
	return $this;
  }
  
  public function select($fields) {
	if (!array_key_exists('fields',$this->params))
	  $this->params['fields'] = [];
	if (is_string($fields))
	  $fields = [$fields];
	$this->params['fields'] = array_merge($this->params['fields'],$fields);
	return $this;
  }
  
  public function first() {
	$this->params['limit'] = 1;
	return DB::_select($this->params);
  }
  
  public function find($id) {
	$this->params['where'] = ['id'=>$id];
	return DB::_select($this->params);
  }
  
  public function get() {
	return DB::_select($this->params);
  }
  
  public function insert($item) {
	DB::_insert($this->params,$item);
  }
  
  public function update($id,$item) {
	$this->params['where'] = ['id'=>$id];
	DB::_update($this->params,$item);
  }
  
  public function delete($id) {
	$this->params['where'] = ['id'=>$id];
	DB::_delete($this->params);
  }
}

?>
<?php
/**
 * DB Class
 * @author  Armando Arce <armando.arce@gmail.com>
*/
 
class DB {

  protected static $db_config;
  protected static $dbh;
  
  public function __construct() {
	if (empty(self::$db_config)) {
	  self::init();
    }
  }
  
  private static function init() {
    if (empty(self::$db_config)) {
      self::$db_config = parse_ini_file('config.ini');
    }
    
    self::$dbh = new PDO(self::$db_config['db_driver'].
                         ":".self::$db_config['db_name']);
    if (empty(self::$dbh)) {
	  echo 'Database not found';
	}
  }
  
  private static function fields($fields) {
	$result = array_keys($fields);
    return implode(',', $result);
  }
  
  private static function questions($fields) {
	$result = str_repeat("?,",sizeof($fields));
	$result = rtrim($result,',');
    return $result;
  }
  
  private static function conditions($conditions,$sep) {
    $result = [];
    foreach ($conditions as $k => $v)
      $result[] = $k."= ?";
    return implode($sep, $result);
  }

  private static function execute($query,$values) {
	if (empty(self::$db_config)) {
	  self::init();
    }
	self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$stmt = self::$dbh->prepare($query);
	foreach ($values as $k => $v)
	  $stmt->bindValue($k+1,$v);
	self::$dbh->beginTransaction();
    $stmt->execute();
    self::$dbh->commit();
  }
  
  public static function table($tableName) {
    $qry = new Query();
	$qry->params['table'] = $tableName;
	return $qry;
  }
  
  public static function select($query,$values) {
	if (empty(self::$db_config)) {
	  self::init();
    }
    self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    self::$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	$stmt = self::$dbh->prepare($query);
	foreach ($values as $k => $v)
	  $stmt->bindValue($k+1,$v);
	$stmt->execute();
    $data = Array();
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $data[] = $result;
    }
    return $data;
  }
  
  public static function _select($params) {
    $query = "SELECT ";
    if (array_key_exists('fields',$params))
      $query .= self::fields($params['fields']);
    else
      $query .= "*";
    $query .= " FROM ".$params['table'];
    $values = [];
    if (array_key_exists('where',$params)) {
      $query .= " WHERE ".self::conditions($params['where'],' AND ');
      $values = array_values($params['where']); 
    }
    if (array_key_exists('group',$params))
      $query .= " GROUP BY ".self::fields($params['group']);
    if (array_key_exists('having',$params)) 
      $query .= " HAVING ".self::conditions($params['having']);
    if (array_key_exists('order',$params))
      $query .= " ORDER BY ".self::fields($params['order']);
    if (array_key_exists('limit',$params))
      $query .= "LIMIT ".$params['limit'];
    if (array_key_exists('skip',$params))
      $query .= "OFFSET ".$params['skip'];
    return self::select($query,$values);
  }
 
  public static function insert($query,$values) {
	self::execute($query,$values);
  }
   
  public static function _insert($params,$item) {
    $query = "INSERT INTO ".$params['table'];
    $query .= " ( ".self::fields($item)." ) ";
    $query .= " VALUES ( ".self::questions(array_keys($item))." ) ";
    $values = array_values($item);
    self::execute($query,$values);
  }
  
  public static function update($query,$values) {
	self::execute($query,$values);
  }
  
  public static function _update($params,$item) {
    $query = "UPDATE ".$params['table']." SET ";
    $query .= self::conditions($item,',');
    $query .= " WHERE ".self::conditions($params['where'],' AND ');
    $values = array_values($item);
    $values = array_merge($values,array_values($params['where']));
    self::execute($query,$values);
  }
  
  public static function delete($query,$values) {
	self::execute($query,$values);
  }
  
  public static function _delete($params) {
    $query = "DELETE FROM ".$params['table'];
    $query .= " WHERE ".self::conditions($params['where'],' AND ');
    $values = array_values($params['where']);
    self::execute($query,$values);
  }
}

?>
<?php
/**
 * Model Class
 * @author  Armando Arce <armando.arce@gmail.com>
*/

class Model {

  protected static $table = '';
  protected static $columns = '';
  protected static $primaryKey = 'id';

  public static function all() {
	$params = ['table' => static::$table];
	return DB::_select($params);
  }
  
  public static function find($id) {
	$params = ['table'=>static::$table,'where'=>['id'=>$id]];
	return DB::_select($params);
  }
  
  public static function create($item) {
	$params = ['table' => static::$table];
	DB::_insert($params,$item);
  }
  
  public static function update($id,$item) {
    $params = ['table' => static::$table,'where'=>['id'=>$id]];
	DB::_update($params,$item);
  }
  
  public static function destroy($id) {
    $params = ['table' => static::$table,'where'=>['id'=>$id]];
	DB::_delete($params);
  }

  public function save() {
	$item = [];
	foreach (static::$columns as $k => $v)
      $item[$v] = $this->{$v};
    if ($id==null)
      DB::_insert($params,$item);
    else
      DB::_update($params,$item);
  }
}

?>
<?php
/**
 * Controller Class
 * @author  Armando Arce <armando.arce@gmail.com>
 */

abstract class Controller {
  
  public function index() {}
  public function create() {}
  public function store() {}
  public function show($id) {}
  public function edit($id) {}
  public function update($id) {}
  public function destroy($id) {}
}
?>
<?php
/**
 * Input Class
 * @author  Armando Arce <armando.arce@gmail.com>
 */

class Input {

  public static function get($name) {
	return htmlspecialchars($_REQUEST[$name]);
  }
}
?>
<?php
/**
 * Cookie Facade
 * @author  Armando Arce <armando.arce@gmail.com>
 */

class Cookie {
  
  public static function get($key,$default=null) {
	return $_COOKIE[$key];
  }
  
  public static function has($key) {
	return isset($_COOKIE[$key]);
  }
  
  public static function queue($key,$value) {
	$cookies = $GLOBALS['cookies'];
	if (!isset($cookies))
	  $cookies = [];
	$cookies[] = [$key=>$value];
	$GLOBALS['cookies'] = $cookies; 
  }
  
  public static function forget($key) {
    setcookie($key,"",time() - 3600);
  }
}
?>
<?php
/**
 * Session Facade
 * @author  Armando Arce <armando.arce@gmail.com>
 */

class Session {

  public static function put($key,$value) {
    $sessions = $GLOBALS['sessions'];
	if (!isset($sessions))
	  $sessions = [];
	$sessions[] = [$key=>$value];
	$GLOBALS['sessions'] = $sessions; 
  }
  
  public static function get($key,$default=null) {
	return $_SESSION[$key];
  }
  
  public static function forget($key) {
	unset ($_SESSION[$key]);
  }
  
  public static function has($key) {
	return isset($_SESSION[$key]);
  }
}
?>
