#!/usr/bin/php
<?php
	if( $argv[1] == 'skills' ) {
		echo "description=zigbee led transformer\n";
		echo "link=https://wiki.loxberry.de/konfiguration/widget_help/widget_mqtt/mqtt_gateway_udp_transformers/udp_transformer_-_zigbee_led\n";
		echo "input=text\n";
		echo "output=text\n";
		exit();
	}
	
	// ---- THIS CAN BE USED ALWAYS ----
	// Remove the script name from parameters
	array_shift($argv);
	// Join together all command line arguments
	$commandline = implode( ' ', $argv );	
	// Split topic and data by separator
	list($topic, $data ) = explode( '#', $commandline, 2);
	// ----------------------------------
	
	list($command, $value_pct) = explode( ' ', $data);
	
	$data = array ( );

	// checks if the command comes with costum max / min levels
	if (preg_match('/^(white|rgb|rgbw|tunablew)(?:_(\d+)_(\d+))?(?:_(\d+))?$/', $command, $matches)) {
		$command = $matches[1];
		$ctemp_max = isset($matches[2]) ? intval($matches[2]) : 500;#6500;
		$ctemp_min = isset($matches[3]) ? intval($matches[3]) : 150;#3000;
		$bright_max = isset($matches[4]) ? intval($matches[4]) : 254;#100;
	}

	switch ($command) {
		// Color mode
		case 'white': 
			$bright = round( $value_pct / 100 * $bright_max );
			if ($bright == 0) {
				$data['state'] = "off";
				break;
			}
			$data['brightness'] = intval($bright);
			break;

		case 'rgb':
			$tunable = str_pad( $value_pct, 9, '0', STR_PAD_LEFT );
			if ($value_pct == 0) {
				$data['state'] = "off";
				break;
			}
			if (substr($tunable, 0, 1)== 2) {
					break;				
			} else {
				$rgb_pct = str_pad( $value_pct, 9, '0', STR_PAD_LEFT );
				$red = round( substr( $rgb_pct, -3, 3) / 100 * $bright_max );
				$green = round( substr( $rgb_pct, -6, 3) / 100 * $bright_max );
				$blue = round( substr( $rgb_pct, -9, 3) / 100 * $bright_max );
				$bright = max($red, $green, $blue);
				$data['brightness'] = intval($bright);
				$data["color"] = array("r" => $red, "g" => $green, "b" => $blue);
			}
			break;

		case 'rgbw':
			$tunable = str_pad( $value_pct, 9, '0', STR_PAD_LEFT );
			$bright = substr( $tunable, -7, 3);
			$temp = substr( $tunable, -4, 4);
			if ($value_pct == 0) {
				$data['state'] = "off";
				break;
			}
			if (substr($tunable, 0, 1)== 2) {
				if ($bright == 0) {
					$data['state'] = "off";
					break;
				}
				// Normalize Lumitech 0...100 to 0...254
				$bright = round($bright * $bright_max / 100);
				// Normalize Lumitech 2700...6500 to required
				$temp = round((($temp - 2700) * ($ctemp_min - $ctemp_max)) / (6500 - 2700) + $ctemp_max);
				$data['brightness'] = intval($bright); 
				$data['color_temp'] = $temp;
			} else {
				$rgb_pct = str_pad( $value_pct, 9, '0', STR_PAD_LEFT );
				$red = round( substr( $rgb_pct, -3, 3) / 100 * $bright_max );
				$green = round( substr( $rgb_pct, -6, 3) / 100 * $bright_max );
				$blue = round( substr( $rgb_pct, -9, 3) / 100 * $bright_max );
				$bright = max($red, $green, $blue);
				$data['brightness'] = intval($bright);
				$data["color"] = array("r" => $red, "g" => $green, "b" => $blue);
			}
			break;

		case 'tunablew':
			$tunable = str_pad( $value_pct, 9, '0', STR_PAD_LEFT );
			$bright = substr( $tunable, -7, 3);
			$temp = substr( $tunable, -4, 4);
			if ($bright == 0) {
				$data['state'] = "off";
			} elseif (substr($tunable, 0, 1)== 2) {
				// Normalize Lumitech 0...100 to 0...254
				$bright = round($bright * $bright_max / 100);
				// Normalize Lumitech 2700...6500 to required
				$temp = round((($temp - 2700) * ($ctemp_min - $ctemp_max)) / (6500 - 2700) + $ctemp_max);
				$data['brightness'] = intval($bright); 
				$data['color_temp'] = $temp;
			}
			break;

		default:
			error_log('Transformer shelly_rgb&w: Wrong parameters (white or rgb missing)');
	}
	
	echo $topic."#".json_encode($data, JSON_UNESCAPED_SLASHES)."\n";
	