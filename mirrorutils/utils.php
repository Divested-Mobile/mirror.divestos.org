<?php
//Copyright (c) 2016-2020 Divested Computing Group
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

function getBaseUrl($allowlisted, $allowlistedHosts) {
	$accessingDomain = noHTML($_SERVER['SERVER_NAME']);
	$accessingDomain = str_replace("&period;", ".", $accessingDomain);
	$baseURL = "";
	$port = "";
	if($_SERVER['SERVER_PORT'] != 443 && $_SERVER['SERVER_PORT'] != 80) {
		$port = ":" . $_SERVER['SERVER_PORT'];
	}
	if(isset($_SERVER['HTTPS'])) {
		$baseURL .= "https://";
	} else {
		$baseURL .= "http://";
	}
	if($allowlisted) {
		if(in_array($accessingDomain, $allowlistedHosts)) {
			$baseURL .= $accessingDomain;
			$baseURL .= $port;
			return $baseURL;
		}
	} else {
		$baseURL .= $accessingDomain;
		$baseURL .= $port;
		return $baseURL;
	}
	return "invalid://invalid.invalid";
}

//Validates a string
function checkString($input, $minLength = 0, $maxLength = 256, $numPeriods = 1, $numSlashes = 0, $numPeriodsRel = 0) {
	//support already noHTML()'ed strings
	$input = str_replace("&period;", ".", $input);
	$input = str_replace("&sol;", "/", $input);
	//validate
	$ok = !is_null($input)
		&& strlen($input) >= $minLength
		&& strlen($input) <= $maxLength
		&& substr_count($input, '.') <= $numPeriods
		&& substr_count($input, '..') <= $numPeriodsRel
		&& substr_count($input, '/') <= $numSlashes;
	return $ok;
}

function isLikelyBot() {
	if (strlen($_SERVER["HTTP_USER_AGENT"]) < 40) {
		return true;
	}
	$lowerAgent = strtolower($_SERVER["HTTP_USER_AGENT"]);
	$headlessAgents = array("headless", "crawler", "spider", "scraper", "phantom", "selenium", "webdriver", "python-requests/", "python-urllib/", "okhttp/", "curl/", "wget/", "go-http-client/", "powershell/", "libwww-perl/", "httpunit", "httrack", "apache-httpclient/", "php-curl-class/", "aiohttp/", "analytics", "chrome/34.0.1847.131" /*, "/bot" */);
	if(containsArr($lowerAgent, $headlessAgents)) {
		return true;
	}
	return false;
}

//Strip entities to prevent XSS
//Credit (CC BY-SA 4.0): https://paragonie.com/blog/2015/06/preventing-xss-vulnerabilities-in-php-everything-you-need-know
function noHTML($input, $encoding = 'UTF-8') {
	return htmlentities($input, ENT_QUOTES | ENT_HTML5, $encoding);
}

//Credit (CC BY-SA 3.0): https://stackoverflow.com/a/7112596
function contains($haystack, $needle) {
	return strpos($haystack, $needle) !== false;
}

function containsArr($haystack, $needles) {
	foreach ($needles as $needle) {
		if(contains($haystack, $needle)) {
			return true;
		}
	}
	return false;
}

//Credit (CC BY-SA 3.0): https://stackoverflow.com/a/6225706
function minifyWhitespace($buffer) {
	$search = array(
	'/\>[^\S ]+/s',     // strip whitespaces after tags, except space
	'/[^\S ]+\</s',     // strip whitespaces before tags, except space
	'/(\s)+/s',         // shorten multiple whitespace sequences
	'/<!--(.|\s)*?-->/' // remove HTML comments
	);

	$replace = array(
	'>',
	'<',
	'\\1',
	''
	);

	$buffer = preg_replace($search, $replace, $buffer);

	return $buffer;
}

?>
