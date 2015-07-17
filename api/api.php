<?php
/*
Main entry point for Granbury API
*/
require(__DIR__."/init.php");
require_once(__DIR__."/api_items.php");
require_once(__DIR__."/api_orders.php");
require_once(__DIR__."/api_tenders.php");

//Required arguments
if(!isset($_GET['api'])) return api_failure("API version required");
if(!isset($_GET['collection'])) return api_failure("Invalid operation");

//Validate API version
if($_GET['api'] != "v1") return api_failure("API version not supported");

//Parse JSON input
global $_JSON;
if(!isset($_JSON))
{
	$_JSON = json_decode(file_get_contents('php://input'), true);
}

//Route requests by collection
$item = isset($_GET['item']) ? $_GET['item'] : NULL;
$subitem = isset($_GET['subitem']) ? $_GET['subitem'] : NULL;
switch($_GET['collection'])
{
	case "items":
		processItems($item, $subitem);
		break;
	case "orders":
		processOrders($item, $subitem);
		break;
	case "tenders":
		processTenders($item, $subitem);
		break;
	default:
		return api_failure("Invalid operation");
		break;
}

//By default, return an empty success message
global $API_TEST_RESULT;
if(!API_TEST || !isset($API_TEST_RESULT))
{
	return api_success();
}
?>