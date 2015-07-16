<?php
/*
Handles /v1/tenders/ requests
*/

function processTenders($item)
{
	if(is_null($item))
	{
		//Collection requests
		switch($_SERVER['REQUEST_METHOD'])
		{
			case "POST": //Submit payment record
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