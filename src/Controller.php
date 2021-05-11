<?php
/**
 * Controller Class
 * @author  Armando Arce <armando.arce@gmail.com>
 */

abstract class Controller {
  
  public function index() {}
  public function create() {}
  public function store($param1 = NULL) {}
  public function show($id) {}
  public function edit($id) {}
  public function update($param1,$param2 = NULL) {}
  public function destroy($id) {}
}
?>
