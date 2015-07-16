<?php
/*
Handles /v1/tenders/ requests
*/

function processTenders($item, $subitem)
{
	global $mysql, $_JSON;
	if(is_null($item))
	{
		//Collection requests
		switch($_SERVER['REQUEST_METHOD'])
		{
			case "POST": //Submit payment record
				//Validate arguments
				if(!isset($_JSON['orderId'])) api_failure("Requires 'orderId'");
				if(!isset($_JSON['amountTendered'])) api_failure("Requires 'amountTendered'");
				if(!isset($_JSON['changeGiven'])) api_failure("Requires 'changeGiven'");
				if(!is_uint($_JSON['amountTendered'])) api_failure("Invalid value for 'amountTendered'");
				if(!is_uint($_JSON['changeGiven'])) api_failure("Invalid value for 'changeGiven'");
				//Find existing order
				$orderId = intval($_JSON['orderId']);
				$order = db_fetch("SELECT orderNumber FROM `order` WHERE orderId = {$orderId}");
				if(is_null($order)) api_failure("Order does not exist");
				//NOTE: Should payment be disallowed on in-progress orders?
				//Insert tender record
				$tender = array();
				$tender['orderId'] = $orderId;
				$tender['amountTendered'] = $_JSON['amountTendered'];
				$tender['changeGiven'] = $_JSON['changeGiven'];
				if(!db_insert("tenderrecord", $tender)) api_dbfailure();
				break;
			default:
				api_failure("Invalid operation");
				break;
		}
	}
	else
	{
		//Item requests (none currently supported)
		api_failure("Invalid operation");
	}
}

?>