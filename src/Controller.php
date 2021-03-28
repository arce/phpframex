<?php
/**
 * Controller Class
 * @author  Armando Arce <armando.arce@gmail.com>
 */

abstract class Controller {
  
  public function index() {}
  public function create() {}
  public function store($param1 = null) {}
  public function show($id) {}
  public function edit($id) {}
  public function update($param1,$param2 = null) {}
  public function destroy($id) {}
}
?>
