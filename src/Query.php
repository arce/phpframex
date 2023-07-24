<?php
/**
 * Query Class
 * @author  Armando Arce <armando.arce@gmail.com>
*/

class Query {

  public $params = [];

  public function itemWhere($relacional,$field,$operator,$value = NULL) {
  	if ($value == NULL) {
  	  $value = $operator;
  	  $operator = '=';
    }
  	$this->params['where'][] = [$relational,$field,$operator,$value];
  }

  public function where($field,$operator,$value = NULL) {
	if (is_array($field))
	  foreach ($field as $item)
		self::itemWhere('AND',$item[0],$item[1],$item[2]);
	else
		self::itemWhere('AND',$field,$operator,$value);
	return $this;
  }

  public function orWhere($field,$value,$extra = NULL) {
  	if (is_array($field))
  	  foreach ($field as $item)
  		self::itemWhere('OR',$item[0],$item[1],$item[2]);
  	else
  		self::itemWhere('OR',$field,$operator,$value);
  	return $this;
  }

  public function distinct() {
	$this->params['distinct'] = true;
	return $this;
  }

  public function orderBy($fields,$ord) {
	if (!array_key_exists('orderBy',$this->params))
	  $this->params['orderBy'] = [];
	if (is_string($fields))
	  $fields = [$fields];
	$this->params['orderBy'] = array_merge($this->params['orderBy'],$fields);
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
	$this->params['where'][] = ['AND','id','=',$id];
	return DB::_select($this->params);
  }

  public function get() {
	return DB::_select($this->params);
  }

  public function insert($item) {
	DB::_insert($this->params,$item);
  }

  public function update($id,$item) {
	$this->params['where'][] = ['AND','id','=',$id];
	DB::_update($this->params,$item);
  }

  public function delete($id) {
	$this->params['where'][] = ['AND','id','=',$id];
	DB::_delete($this->params);
  }
}

?>
