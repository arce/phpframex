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
    $qry = new Query();
    $qry->params['table'] = static::$table;
    return $qry->get();
  }

  public static function find($id) {
    $qry = new Query();
    $qry->params['table'] = static::$table;
    $qry->find($id);
    return $qry->get();
  }

  public static function where($field,$value) {
    $qry = new Query();
    $qry->params['table'] = static::$table;
    $qry->where($field,$value);
    return $qry;
  }

  public static function create($item) {
    $params = ['table' => static::$table];
    DB::_insert($params,$item);
  }

  public static function update($id,$item) {
    $params = ['table' => static::$table,'where'=>[["AND",$field,"=",$value]]];
    DB::_update($params,$item);
  }

  public static function destroy($id) {
    $params = ['table' => static::$table,'where'=>[["AND",$field,"=",$value]]];
    DB::_delete($params);
  }

  public function save() {
    $item = [];
    foreach (static::$columns as $k => $v)
      $item[$v] = $this->{$v};
    if ($id==NULL)
      DB::_insert($params,$item);
    else
      DB::_update($params,$item);
  }
}

?>
