<?php
/*
Handles /v1/items/ requests
*/

function processItems($item, $subitem)
{
	global $mysql, $_JSON;
	if(is_null($item))
	{
		//Collection requests
		switch($_SERVER['REQUEST_METHOD'])
		{
			case "GET": //Return a list of all items
				//Query item list
				$item_list = db_fetch_all("SELECT name, price FROM `item` ORDER BY price DESC, name");
				api_success(array("items" => $item_list));
				break;

			case "POST": //Import uploaded CSV file
				//Verify uploaded file
				if(!upload_attempted("csv")) api_failure("No file provided");
				if(!upload_okay("csv")) api_failure("File upload failed");
				//Parse CSV lines
				$csv = file_get_contents($_FILES['csv']['tmp_name']);
				$csv = explode("\n", $csv);
				//Require at least 2 rows (title and data)
				if(count($csv) < 2) api_failure("Invalid CSV");
				//Process rows (skip title row)
				array_shift($csv);
				foreach($csv as $line)
				{
					//Skip blank lines
					if(!strlen(trim($line))) continue;
					$line = explode(",", $line);
					//Verify column presence
					if(count($line) != 2) api_failure("Invalid number of columns");
					$name = trim($line[0]);
					//Convert price to fixed point (cent)
					$price = price_to_cents(trim($line[1]));
					//Verify contents
					if(!strlen($name) || $price < 0) api_failure("Invalid data");
					//Attempt insertion
					$item = array();
					$item["name"] = $name;
					$item["price"] = $price;
					if(!db_insert("item", $item)) api_dbfailure();
				}
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