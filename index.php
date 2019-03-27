<?php
    session_start();
    parse_str(file_get_contents("php://input"), $_REQUEST);
    $sessions = [];
    $cookies = [];
    $redirect = null;
    require('PHPFramex.php');
    require('routes.php')
?>
