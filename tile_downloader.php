<?php

if (count($argv) == 1) {
	echo "usage: latitude longitude distance min_zoom max_zoom map_directory\n";
	exit;
}

$LATITUDE = $argv[1];
$LONGITUDE = $argv[2];
$DISTANCE = isset($argv[3]) ? $argv[3] : 1;
$MIN_ZOOM = isset($argv[4]) ? $argv[4] : 0;
$MAX_ZOOM = isset($argv[5]) ? $argv[5] : 18;
$MAP_DIR = isset($argv[6]) ? $argv[6] : "map/";

if ($LATITUDE > 90 || $LATITUDE < -90) {
	echo "Latitude must be between 90 and -90.\n";
	exit;
} else if ($LONGITUDE > 180 || $LONGITUDE < -180) {
	echo "Longitude must be between 90 and -90.\n";
	exit;
} else if ($DISTANCE < 0) {
	echo "Distance must be greater than 0.\n";
	exit;
} else if ($MIN_ZOOM < 0) {
	echo "Minimum zoom must be greater than 0.\n";
	exit;
} else if ($MIN_ZOOM > $MAX_ZOOM) {
	echo "Maximum zoom must be greater than minimum zoom.\n";
	exit;
}

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
	
	$bearing = deg2rad($bearing);
	$EARTH_RADIUS_M = 6378.137;
	$d_R = $distance / $EARTH_RADIUS_M;

	$initial_latitude = deg2rad($initial_latitude);
	$initial_longitude = deg2rad($initial_longitude);

	$new_latitude = asin( sin($initial_latitude) * cos($d_R) + cos($initial_latitude) * sin($d_R) * cos($bearing) );

	$new_longitude = $initial_longitude + atan2( sin($bearing) * sin($d_R) * cos($initial_latitude), cos($d_R) - sin($initial_latitude) * sin($new_latitude));

	return array(
		'longitude' => rad2deg($new_longitude),
		'latitude' => rad2deg($new_latitude),
	);
}

// Determine lon/lat square.
// Longitude = X
// Latitude = Y

$north_lonlat = newLongitudeLatitude($LONGITUDE, $LATITUDE, $DISTANCE, 0);
$south_lonlat = newLongitudeLatitude($LONGITUDE, $LATITUDE, $DISTANCE, 180);
$east_lonlat = newLongitudeLatitude($LONGITUDE, $LATITUDE, $DISTANCE, 90);
$west_lonlat = newLongitudeLatitude($LONGITUDE, $LATITUDE, $DISTANCE, 270);

$min_longitude = $west_lonlat['longitude'];
$max_longitude = $east_lonlat['longitude'];

$min_latitude = $south_lonlat['latitude'];
$max_latitude = $north_lonlat['latitude'];

for ($zoom = $MIN_ZOOM; $zoom <= $MAX_ZOOM; ++$zoom) {

	if (!is_dir($MAP_DIR.$zoom)) {
		mkdir($MAP_DIR.$zoom);
	}

	$min_x = getX($min_longitude, $zoom);
	$max_x = getX($max_longitude, $zoom);

	$max_y = getY($min_latitude, $zoom);
	$min_y = getY($max_latitude, $zoom);

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
