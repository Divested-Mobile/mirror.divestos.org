<?php
//Copyright (c) 2017-2022 Divested Computing Group
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
ob_start("minifyWhitespace");

if(!isLikelyBot()) {
	$base = noHTML($_GET["base"]);
	$base = str_replace("&period;", ".", $base);
	$device = strtolower(noHTML($_GET["device"]));
	$device = str_replace("&lowbar;", "_", $device);
	$inc = noHTML($_GET["inc"]);
	if(!is_null($base) && strlen($base) > 0 && substr_count($base, '.') <= 1 && substr_count($base, '/') == 0 && !is_null($device) && strlen($device) > 0 && substr_count($device, '.') == 0 && substr_count($device, '/') == 0 && (empty($inc) || (!empty($inc) && strlen($inc) < 22))) {
		$rootdir = "builds/" . $base . "/" . $device;
		$rootdirInc = $rootdir . "/incrementals/";
		if(is_dir($rootdir)) {
			$result = getCachedDeviceJson($rootdir, $rootdirInc, $base, $device, $inc);
			$result = str_replace("invalid://invalid.invalid", "https://divestos.org", $result);
			if(contains($result, "invalid://invalid.invalid")) {
				print("Invalid request");
				http_response_code(400);
			} else {
				print($result);
			}
		} else {
			print("Unknown base/device");
			http_response_code(404);
		}
	} else {
		print("Invalid request");
		http_response_code(400);
	}
} else {
	print("Forbidden");
	http_response_code(401);
}

function getCachedDeviceJson($rootdir, $rootdirInc, $base, $device, $inc) {
	if(extension_loaded("redis")) {
		$result = "";
		$redis = new Redis();
		$redis->connect('127.0.0.1');
		$cacheKey = "DivestOS+updater.php+base:" . $base . "+device:" . $device;
		$uptimeKey = "DivestOS+updater.php+uptime";
		if(!empty($inc)) {
			$redis->incr("Counter-" . $cacheKey);
			if (str_starts_with($inc, "engemy") || str_starts_with($inc, "engtad")) {
				$incDate = substr($inc, 6);
				$currentYearMonth = date("Ym");
				$pastYearMonth = date("Ym", strtotime("first day of last month"));
				if(str_starts_with($incDate, $currentYearMonth) || str_starts_with($incDate, $pastYearMonth)) {
					$redis->incr("Updated-" . $cacheKey);
				}
			}
			$cacheKey .= "+inc:" . $inc;
		}
		if(($redis->get($uptimeKey)) == false) {
			$redis->set($uptimeKey, time());
		}
		if(($result = $redis->get($cacheKey)) == false) {
			$result = getDeviceJson($rootdir, $rootdirInc, $base, $device, $inc);
			$redis->setEx($cacheKey, 3600, $result); //1 hour
		}
		$redis->close();
		return $result;
	} else {
		return getDeviceJson($rootdir, $rootdirInc, $base, $device, $inc);
	}
}

function getDeviceJson($rootdir, $rootdirInc, $base, $device, $inc) {
	$fullJson = "";
	$fullJson .= "{";
	$fullJson .= "\n\t\"response\": [";
	if(!empty($inc) && file_exists($rootdirInc)) {
		$imagesInc = scandir($rootdirInc, 1);
		foreach($imagesInc as $imageInc) {
			$imageSplit = explode("-", $imageInc);
			if(str_starts_with($imageSplit[5], $inc)) {
				$fullJson .= getImageJson($rootdirInc, $base, $device, $imageInc);
			}
		}
	}
	$images = scandir($rootdir, 1);
	foreach($images as $image) {
		$fullJson .= getImageJson($rootdir, $base, $device, $image);
	}
	$fullJson = rtrim($fullJson, ", ");
	$fullJson .= "\n\t]";
	$fullJson .= "\n}";
	return $fullJson;
}

function getImageJson($rootdir, $base, $device, $image) {
	if(!contains($image, "md5sum") && !contains($image, "sha512sum") && strlen($image) > 30 && !str_starts_with($image, ".") && str_ends_with($image, ".zip") && !contains($image, "fastboot") && !contains($image, "recovery")) {
		$imageSplit = explode("-", $image); //name-version-date-buildtype-device-[previnc].zip
		unset($json);
		if(str_starts_with(strtolower($imageSplit[4]), $device)) {
			$json = "";
			$json .= "\n\t\t{";
			$json .= "\n\t\t\t\"filename\": \"" . $image . "\",";
			if(contains($rootdir, "incrementals")) {
				$json .= "\n\t\t\t\"url\": \"invalid://invalid.invalid" . "/mirror.php?base=" . $base . "&f=" . $device . "/incrementals/" . $image . "\",";
			} else {
				$json .= "\n\t\t\t\"url\": \"invalid://invalid.invalid" . "/mirror.php?base=" . $base . "&f=" . $device . "/" . $image . "\",";
			}
			$json .= "\n\t\t\t\"version\": \"" . $imageSplit[1] . "\",";
			$json .= "\n\t\t\t\"romtype\": \"" . $imageSplit[3] . "\",";
			$json .= "\n\t\t\t\"id\": \"" . md5(file_get_contents($rootdir . "/". $image . ".sha512sum")) . "\",";
			$json .= "\n\t\t\t\"datetime\": " . filemtime($rootdir . "/". $image) . ",";
			$json .= "\n\t\t\t\"size\": " . filesize($rootdir . "/". $image);
			if(strlen($imageSplit[1]) < 5 && file_exists($rootdir . "/status-" . $imageSplit[1])) {
				$json .=  ",\n\t\t\t\"status\": " . file_get_contents($rootdir . "/status-" . $imageSplit[1]);
			}
			$json .= "\n\t\t},";
			return $json;
		}
	}
}

?>
