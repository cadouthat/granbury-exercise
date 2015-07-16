<?php
/*
Handles /v1/orders/ requests
*/

function processOrders($item)
{
	if(is_null($item))
	{
		//Collection requests
		switch($_SERVER['REQUEST_METHOD'])
		{
			case "GET": //List orders
				break;
			case "POST": //Create order
				break;
			default:
				api_failure("Invalid operation");
				break;
		}
	}
	else
	{
		//Item requests
		switch($_SERVER['REQUEST_METHOD'])
		{
			case "PUT": //Submit order
				break;
			case "POST": //Add/update/remove line item
				break;
			default:
				api_failure("Invalid operation");
				break;
		}
	}
}

?>