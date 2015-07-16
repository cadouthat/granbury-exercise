<?php
/*
Main entry point for Granbury API
*/
require_once("init.php");
require_once("api_items.php");
require_once("api_orders.php");
require_once("api_tenders.php");

//Required arguments
if(!isset($_GET['api'])) api_failure("API version required");
if(!isset($_GET['collection'])) api_failure("Invalid operation");

//Validate API version
if($_GET['api'] != "v1") api_failure("API version not supported");

//Parse JSON input
global $_JSON;
$_JSON = json_decode(file_get_contents('php://input'), true);

//Route requests by collection
$item = isset($_GET['item']) ? $_GET['item'] : NULL;
switch($_GET['collection'])
{
	case "items":
		processItems($item);
		break;
	case "orders":
		processOrders($item);
		break;
	case "tenders":
		processTenders($item);
		break;
	default:
		api_failure("Invalid operation");
		break;
}

//By default, return an empty success message
api_success();
?>