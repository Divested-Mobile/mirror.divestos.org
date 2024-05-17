<?php
//Copyright (c) 2017-2020 Divested Computing Group
//
//This program is free software: you can redistribute it and/or modify
//it under the terms of the GNU Affero General Public License as published by
//the Free Software Foundation, either version 3 of the License, or
//(at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU Affero General Public License for more details.
//
//You should have received a copy of the GNU Affero General Public License
//along with this program.  If not, see <https://www.gnu.org/licenses/>.

include "mirrorutils/config.php";
include "mirrorutils/security.php";
include "mirrorutils/utils.php";

$numMirrors = 0;

if(!isLikelyBot()) {
	$base = noHTML($_GET["base"]);
	$base = str_replace("&period;", ".", $base);
	$file = noHTML($_GET["f"]);
	$file = str_replace("&period;", ".", $file);
	$file = str_replace("&sol;", "/", $file);
	$file = str_replace("&lowbar;", "_", $file);
	if(checkString($base, 2, 24, 1, 0, 0) && checkString($file, 3, 128, 3, 2, 0)) {
		header('Content-Disposition: attachment; filename="' . explode("/", $file)[1] . '"');
		header('Location: ' . getMirror() . $base . "/" . $file);
		//print("Picking: " . getMirror());
	} else {
		print("Invalid request");
		http_response_code(400);
	}
} else {
	print("Forbidden");
	http_response_code(401);
}

function getMirror() {
	if(in_array($_SERVER['SERVER_NAME'], $GLOBALS['SBNR_DOMAINS_CLOUDFLARE_ONLY'])) {
		return $GLOBALS['SBNR_MIRRORS_CLOUDFLARE'][array_rand($GLOBALS['SBNR_MIRRORS_CLOUDFLARE'])];
	} else if(in_array($_SERVER['SERVER_NAME'], $GLOBALS['SBNR_DOMAINS_ONIONS_ONLY'])) {
		return $GLOBALS['SBNR_MIRRORS_ONIONS'][array_rand($GLOBALS['SBNR_MIRRORS_ONIONS'])];
	} else {
		if(false /*isset($_SERVER['MM_LATITUDE']) && isset($_SERVER['MM_LONGITUDE'])*/) {
			$closestRecordedMirror = -1;
			$closestRecordedMirrorDistance = 6371000;
			//print("Identifed location is " . $_SERVER['MM_LATITUDE'] . ", " . $_SERVER['MM_LONGITUDE'] . "<br>" . PHP_EOL);
			for($inc = 0; $inc < sizeof($GLOBALS['SBNR_MIRRORS_CLEARNET']); $inc++) {
				$measuredDistance = vincentyGreatCircleDistance($_SERVER['MM_LATITUDE'], $_SERVER['MM_LONGITUDE'],
					$GLOBALS['SBNR_MIRRORS_CLEARNET_LATITUDES'][$inc], $GLOBALS['SBNR_MIRRORS_CLEARNET_LONGITUDES'][$inc]);
				//print($measuredDistance . "m to " . $GLOBALS['SBNR_MIRRORS_CLEARNET'][$inc] . "<br>" . PHP_EOL);
				if($measuredDistance < $closestRecordedMirrorDistance) {
					$closestRecordedMirror = $inc;
					$closestRecordedMirrorDistance = $measuredDistance;
				}
			}
			if($closestRecordedMirror !== -1) {
				return $GLOBALS['SBNR_MIRRORS_CLEARNET'][$closestRecordedMirror];
			}
		}
		return $GLOBALS['SBNR_MIRRORS_CLEARNET'][array_rand($GLOBALS['SBNR_MIRRORS_CLEARNET'])];
	}
}

/**
 * Calculates the great-circle distance between two points, with
 * the Vincenty formula.
 * @param float $latitudeFrom Latitude of start point in [deg decimal]
 * @param float $longitudeFrom Longitude of start point in [deg decimal]
 * @param float $latitudeTo Latitude of target point in [deg decimal]
 * @param float $longitudeTo Longitude of target point in [deg decimal]
 * @param float $earthRadius Mean earth radius in [m]
 * @return float Distance between points in [m] (same as earthRadius)
 * Credit (CC BY-SA 3.0): https://stackoverflow.com/a/10054282
 */
function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
	// convert from degrees to radians
	$latFrom = deg2rad($latitudeFrom);
	$lonFrom = deg2rad($longitudeFrom);
	$latTo = deg2rad($latitudeTo);
	$lonTo = deg2rad($longitudeTo);

	$lonDelta = $lonTo - $lonFrom;
	$a = pow(cos($latTo) * sin($lonDelta), 2) + pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
	$b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

	$angle = atan2(sqrt($a), $b);
	return round($angle * $earthRadius);
}

?>
