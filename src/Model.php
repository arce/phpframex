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
  
  public static function where($field,$value) {
	$params = ['table'=>static::$table,'where'=>[$field=>$value]];
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
