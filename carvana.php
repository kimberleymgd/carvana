<?php

	declare(strict_types = 1);

	// Plugin for loading DOM data
	require "__simple_html_dom.php";

	function fetch_carvana_inventory_by_page(int $page_id) : array {
		// Fetch a single page of vehicles
		// return an array of vehicle records
		
		// Define page link 
		$link = 'https://www.carvana.com/cars?page='.$page_id;
		
		// Get page contents
		$html = file_get_html($link);
		
		// Initialize inventory array to store vehicle records
		$inventory_data = array();
		
		foreach($html->find("script[data-react-helmet=true]") as $car_node) {
			
			// Get vehicle data
			$vehicle_data = array();
			$car_node_json = json_decode($car_node->innertext, true);
			$vehicle_data['vehicle_id'] = intval($car_node_json["sku"]);
			$vehicle_data['vin'] = $car_node_json["vehicleIdentificationNumber"];
			$vehicle_data['make'] = $car_node_json["brand"];
			$vehicle_data['model'] = $car_node_json["model"];
			$vehicle_data['mileage'] = intval($car_node_json["mileageFromOdometer"]);
			$vehicle_data['price'] = floatval($car_node_json["offers"]["price"]);
			
			// Push vehicle to inventory array
			array_push($inventory_data, array($vehicle_data));
			
		}
		
		// Clear page contents variable
		$html->clear();

		// Return scraped inventory data
		return $inventory_data;
		
	}


	function save_carvana_inventory(array $vehicle, mysqli $db) : bool {
		// Persist a single vehicle record to the database
		// Return a boolean indicating success or failure
		
		$sql = "INSERT INTO vehicle (
					vehicle_id, 
					vin, 
					make, 
					model, 
					mileage, 
					price
				) 
				VALUES (
					'".$vehicle['vehicle_id']."', 
					'".$vehicle['vin']."', 
					'".$vehicle['make']."',  
					'".$vehicle['model']."',  
					'".$vehicle['mileage']."',  
					'".$vehicle['price']."' 
				)";
		
		$result = $db -> query($sql);
		
		return $result;
	}


	// Connect to database
	$SERVER = "localhost";
	$DB_USER = "crvnadm";
	$DB_PASS = "password";
	$DB_NAME = "carvana";
	$db = new mysqli($SERVER,$DB_USER,$DB_PASS,$DB_NAME);


	// Check connection
	if ($db -> connect_errno) {
		echo "Failed to connect to MySQL: " . $db -> connect_error;
		exit();
	}
	else {
		// Successful Connection
		// echo "Connected to MySQL";
		
		// Check if vehicle table exists
		$sql = "select * from vehicle";
		$exists = $db -> query($sql);

		// Create vehicle table if it does not exist
		if($exists == FALSE) {
			
			$sql = "CREATE TABLE vehicle (
					vehicle_id int(12) NOT NULL PRIMARY KEY,
					vin varchar(17) NOT NULL,
					make varchar(200) NULL,
					model varchar(200) NOT NULL,
					mileage int(11) NOT NULL,
					price float NOT NULL
				)";
			$result = $db -> query($sql);
		}
		
		
		foreach (fetch_carvana_inventory_by_page(2) as $vehicle) {
				
			save_carvana_inventory($vehicle[0], $db);
			
		}
		
		// Close database connection
		$db -> close();

	}

?>