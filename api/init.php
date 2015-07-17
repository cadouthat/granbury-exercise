<?php
/*
Global initialization for Granbury API
*/
require_once("config.php");
require_once("util.php");

//Output charset header
if(!API_TEST) header('Content-Type: text/html; charset=utf-8');

//Set error reporting
error_reporting(API_DEBUG ? E_ALL : 0);

//Connect to MySQL
global $mysql;
$mysql = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if($mysql->connect_errno)
{
	return api_failure("Could not establish a database connection");
}
mysqli_set_charset($mysql, "utf8");

?>