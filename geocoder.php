<?php

// Delay fucnction for geocode requests
function m_sleep($milliseconds){
	return usleep($milliseconds * 1000);
}

// Make requests to geocoder
function file_get_content_curl($url){
	// Throw Error if the curl function doesn't exist.
	if(!function_exists('curl_init')){
		die('CURL is not installed!');
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// Send request to geocoder and store response
	$output = curl_exec($ch);
	curl_close($ch);

	// Return geocoder response
	return $output;
}

// Geocode address input
function geocode($address){

	// URL encode the address
	$address = str_replace('%2C' ,',', rawurlencode($address));  // Bing dosen't like url encoded commas, so we restore them after encoding


	// Set Bing Maps Geocode API URL
	$url = 'http://dev.virtualearth.net/REST/v1/Locations/' . $address . '?key=[BING_API_KEY]';

	// Get geocoder response
	$resp_json = file_get_content_curl($url);

	// Decode response to JSON
	$resp = json_decode($resp_json, true);
 
		// Check for successful geocoder response
		if($resp['statusDescription']=='OK'){
 
			// Parse geocoder response data
			$lati = isset($resp['resourceSets'][0]['resources'][0]['point']['coordinates'][0]) ? $resp['resourceSets'][0]['resources'][0]['point']['coordinates'][0] : "";
			$longi = isset($resp['resourceSets'][0]['resources'][0]['point']['coordinates'][1]) ? $resp['resourceSets'][0]['resources'][0]['point']['coordinates'][1] : "";
			$formatted_address = isset($resp['resourceSets'][0]['resources'][0]['address']['formattedAddress']) ? $resp['resourceSets'][0]['resources'][0]['address']['formattedAddress'] : "";

			// Get Street Address
			$street_address = $resp['resourceSets'][0]['resources'][0]['address']['addressLine'];

			// Get City
			$city = $resp['resourceSets'][0]['resources'][0]['address']['locality'];

			// Get State
			$state = $resp['resourceSets'][0]['resources'][0]['address']['adminDistrict'];

			// Get Zip Code
			$zip = $resp['resourceSets'][0]['resources'][0]['address']['postalCode'];


			// Geocoder Score
			$score = implode(",", $resp['resourceSets'][0]['resources'][0]['matchCodes']);


			// Verify if data is complete
			if($lati && $longi && $formatted_address){

				// Store data in output array
				$data_arr = array();            

				array_push(
					$data_arr, 
					    $lati, 			//0
					    $longi,			//1
					    $formatted_address,		//2
					    $street_address,		//3
					    $city,			//4
					    $state,			//5
					    $zip,			//6
					    $score			//7
				);

				// Return output array
				return $data_arr;

			}else{	
				// Return false on incomplete geocoder results
				return false;
			}

    	// Return false on geocoder service failure
	}else{
        	print("ERROR: {$resp['status']}");
        	return false;
    	}
}


// Begin geocoding proceedure
$record = 0;

// Load in CSV file
$fp = file("input.csv");

// Count total rows
$totalRecords = count($fp);


// Open input CSV File
if(($handle1 = fopen("input.csv", "r")) !== FALSE){

	// Create output CSV file
	if(($handle2 = fopen("output.csv", "w")) !== FALSE){

		// Loop though input CSV file rows
		while(($data = fgetcsv($handle1, 7000, ",")) !== FALSE){

			// Delay geocoder exection by a random number of milliseconds to avoid abuse flagging
			$random_time = mt_rand(20, 25);
			m_sleep($random_time);

			// Geocode input CSV row data
			$geocode = geocode($data[0]);
			
			// Store geocoder output in array
			$new_data[0] = $data[0];
			$new_data[1] = $geocode[0];
			$new_data[2] = $geocode[1];
			$new_data[3] = $geocode[2];
			$new_data[4] = $geocode[3];
			$new_data[5] = $geocode[4];
			$new_data[6] = $geocode[5];
			$new_data[7] = $geocode[6];
			$new_data[8] = $geocode[7];

			// Write output array to output CSV file
			fputcsv($handle2, $new_data);
			
			// Output status message
			print("Record: " . ($record+1) . " of " . $totalRecords . " - " . $new_data[0]. "\r\n");


			$record++;
		}
		
		// Close output CSV
		fclose($handle2);
	}
	
	// Close input CSV
	fclose($handle1);
}
?>
