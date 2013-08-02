<?php

$latitude = 43.08;//$argv[1];
$longitude = -79.08;//$argv[2];
// $distance = isset($argv[3]) ? $argv[3] : 10;

$distance = 1000;

$MIN_ZOOM = 10;
$MAX_ZOOM = 14;

$MAP_DIR = "map/";

if (!is_dir($MAP_DIR)) {
	mkdir($MAP_DIR);
}

function getX($lon, $zoom) {
	return floor((($lon + 180) / 360) * pow(2, $zoom));
}

function getY($lat, $zoom) {
	return floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) /2 * pow(2, $zoom));
}

function newLongitudeLatitude($initial_longitude, $initial_latitude, $distance, $bearing) {
	
	$bearing *= 0.0174532925;
	
	// $EARTH_RADIUS_M = 6378.137;
	// $d_R = $distance / $EARTH_RADIUS_M;
	// $new_latitude = $initial_latitude + asin( sin($initial_latitude) * cos($d_R) + cos($initial_latitude) * sin($d_R) * cos($bearing) );
	// $new_longitude = $initial_longitude + atan2( sin($bearing) * sin($d_R) * cos($initial_latitude), cos($d_R) - sin($initial_latitude) * sin($new_latitude));
	

	$dx = $distance * sin($bearing);
	$dy = $distance * cos($bearing);
	$d_longitude = $dx / ( 111320 * cos($initial_latitude) );
	$d_latitude = $dy / 110540;
	$new_longitude = $initial_longitude + $d_longitude;
	$new_latitude = $initial_latitude + $d_latitude;

	// print_r(array(
	// 	'longitude' => $new_longitude,
	// 	'latitude' => $new_latitude,
	// ));

	return array(
		'longitude' => $new_longitude,
		'latitude' => $new_latitude,
	);
}

// Determine lon/lat square.
// Longitude = X
// Latitude = Y

$north_lonlat = newLongitudeLatitude($longitude, $latitude, $distance, 0);
$south_lonlat = newLongitudeLatitude($longitude, $latitude, $distance, 180);
$east_lonlat = newLongitudeLatitude($longitude, $latitude, $distance, 90);
$west_lonlat = newLongitudeLatitude($longitude, $latitude, $distance, 270);

// echo $north_lonlat['latitude']. "," . $north_lonlat['longitude'] ."\n";
// echo $south_lonlat['latitude']. "," . $south_lonlat['longitude'] ."\n";
// echo $east_lonlat['latitude']. "," . $east_lonlat['longitude'] ."\n";
// echo $west_lonlat['latitude']. "," . $west_lonlat['longitude'] ."\n";

// print_r($north_lonlat);
// print_r($south_lonlat);
// print_r($east_lonlat);
// print_r($west_lonlat);
// die;

$min_longitude = $west_lonlat['longitude'];
$max_longitude = $east_lonlat['longitude'];

$min_latitude = $south_lonlat['latitude'];
$max_latitude = $north_lonlat['latitude'];

// echo $max_longitude . "\n";
// echo $min_longitude . "\n";
// echo $max_latitude . "\n";
// echo $min_latitude . "\n";

for ($zoom = $MIN_ZOOM; $zoom <= $MAX_ZOOM; ++$zoom) {

	if (!is_dir($MAP_DIR.$zoom)) {
		mkdir($MAP_DIR.$zoom);
	}

	$max_x = getX($min_longitude, $zoom) + 1;
	$min_x = getX($max_longitude, $zoom) - 1;

	$max_y = getY($min_latitude, $zoom) + 1;
	$min_y = getY($max_latitude, $zoom) - 1;

	$x_array = array();
	$y_array = array();


	for ($i = $min_x; $i <= $max_x; $i++) { 
		$x_array[] = $i;
	}

	for ($i = $min_y; $i <= $max_y; $i++) { 
		$y_array[] = $i;
	}

	foreach ($x_array as $kx => $x) {

		if (!is_dir($MAP_DIR.$zoom."/".$x)) {
			mkdir($MAP_DIR.$zoom."/".$x);
		}

		foreach ($y_array as $ky => $y) {

			$url = "http://a.tile.openstreetmap.org/$zoom/$x/$y.png";
			$filename = $MAP_DIR.$zoom."/".$x."/".$y.".png";

			if (!file_put_contents($filename, fopen($url, 'r'))) {
				echo "FAIL\n";
			}
		}
	}
}
