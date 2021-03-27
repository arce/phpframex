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
