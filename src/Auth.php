<?php

/**
 * Auth Facade
 * @author  Armando Arce <armando.arce@gmail.com>
*/
 
class Auth {
  
  public static function user() {
    return Session::get('user');
  }
  
  public static function id() {
	return Session::get('id');
  }
  
  public static function check() {
	return (Session::get('id')!=NULL);
  }
  
  public static function attempt($item) {
	$user = DB::table('users')->where('email',$item['email'])->get();
	if (isset($user)) {
	  if (trim($item['password']) == trim($user[0]['password'])) {
	    Session::put('user',$user[0]);
		Session::put('id',$user[0]['id']);
	    return true;
	  }
	}
	return false;
  }
  
  public static function login($user,$remember) {
  }
  
  public static function loginUsingId($id) {
  }
  
  public static function logout() {
	Session::forget('user');
	Session::forget('id');
  }
  
  public static function viaRemember() {
  }
  
  public static function once($credentials) {
  }
  
  public static function onceBasic() {
  }
}
?>