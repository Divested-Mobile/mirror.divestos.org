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

if(!defined('STDIN')) {
	exit();
}

include "mirrorutils/security.php";
include "mirrorutils/utils.php";
ob_start("minifyWhitespace");

$baseReal = $argv[1];
$goldenReal = $argv[2];

function getDownloads($version) {
	$base = $GLOBALS['baseReal'];
	$golden = ($GLOBALS['goldenReal'] === "true");
	if(is_null($base) || strlen($base) == 0 || !(substr_count($base, '/') == 0)) {
		error();
		return;
	}

	$rootdir = "/builds/" . $base . "/";
	$realRootdir = "/var/www/divestos.org" . $rootdir;
	if(!(is_dir($realRootdir))) {
		error();
		return;
	}

	$devices = scandir($realRootdir, 0);
	if(sizeof($devices) == 2) {
		print("No devices available for base");
		return;
	}

	print(getDevices($base, $rootdir, $realRootdir, $devices, $version, $golden));
}

function getDevices($base, $rootdir, $realRootdir, $devices, $version, $golden) {
	$downloads = "";
	$lastSecRelease = 1567728000; //The timestamp of when LineageOS merged the latest Android security bulletin patches, XXX: MUST BE MANUALLY UPDATED
	$curTime = time(); //Used to check if builds are older than 40 days as a fallback if the above isn't updated
	foreach ($devices as $device) {
		if(strlen($device) >= 2 && $device != '..') {
			$files = scandir($realRootdir . $device, 1);
			if(sizeof($files) > 5) {
				$zip = "";
				//Identify an existing image matching the device and version
				foreach ($files as $file) {
					if($file != "status" && $file != "friendlyName" && $file != "incrementals" && !contains($file, "md5sum") && !contains($file, "sha512sum") && strlen($file) > 30 && !startsWith($file, ".") && endsWith($file, ".zip") && !contains($file, "fastboot") && !contains($file, "recovery")) {
						$imageSplit = explode("-", $file);
						if(startsWith(strtolower($imageSplit[4]), $device) && ($imageSplit[1] == $version)) {
							if(strlen($file) > 30) {
								$zip = $file;
								$trueName = explode(".", $imageSplit[4])[0];
								break;
							}
						}
						unset($imageSplit);
					}
				}
				unset($file);

				//We have an image, get the other goodies
				if(strlen($zip) > 30) {
					//OTA
					$otaInstallFile = $zip;
					$resultOTA = "/mirror.php?base=" . $base . "&f=" . $device . "/" . $zip;
					if(file_exists($realRootdir . $device . "/" .$zip . ".sha512sum")) {
						$resultHashOTA = "/mirror.php?base=" . $base . "&f=" . $device . "/" . $zip . ".sha512sum";
					}

					//RECOVERY
					$recovery = str_replace(".zip", "-recovery.img", $zip);
					$recoveryPath = $realRootdir . $device . "/" . $recovery;
					if(strlen($recovery) > 36 && file_exists($recoveryPath)) {
						$initialInstallFile = $recovery;
						$resultRecovery = "/mirror.php?base=" . $base . "&f=" . $device . "/" . $recovery;
						if(file_exists($recoveryPath . ".sha512sum")) {
							$resultHashRecovery = "/mirror.php?base=" . $base . "&f=" . $device . "/" . $recovery . ".sha512sum";
						}
					}
					unset($recovery); unset($recoveryPath);

					//FASTBOOT
					$fastboot = str_replace(".zip", "-fastboot.zip", $zip);
					$fastbootPath = $realRootdir . $device . "/" . $fastboot;
					if(strlen($fastboot) > 36 && file_exists($fastbootPath)) {
						$initialInstallFile = $fastboot;
						$resultFastboot = "/mirror.php?base=" . $base . "&f=" . $device . "/" . $fastboot;
						if(file_exists($fastbootPath . ".sha512sum")) {
							$resultHashFastboot = "/mirror.php?base=" . $base . "&f=" . $device . "/" . $fastboot . ".sha512sum";
						}
					}
					unset($fastboot); unset($fastbootPath);

					//AVB KEY
					$avbKey = "avb_pkmd-" . $device . ".bin";
					$avbKeyPath = $realRootdir . $device . "/" . $avbKey;
					if(file_exists($avbKeyPath)) {
						$resultKeyAVB = "/mirror.php?base=" . $base . "&f=" . $device . "/" . $avbKey;
					}
					unset($avbKey); unset($avbKeyPath);

					//COPY PARTITIONS
					$copyParts = "copy-partitions-" . $device . "-release.zip";
					$copyPartsPath = $realRootdir . $device . "/" . $copyParts;
					if(file_exists($copyPartsPath)) {
						$resultCopyParts = "/mirror.php?base=" . $base . "&f=" . $device . "/" . $copyParts;
					}
					unset($copyParts); unset($copyPartsPath);

					//SUPER
					$superImg = "super_empty-" . $device . ".img";
					$superImgPath = $realRootdir . $device . "/" . $superImg;
					if(file_exists($superImgPath)) {
						$resultSuperImg = "/mirror.php?base=" . $base . "&f=" . $device . "/" . $superImg;
					}
					unset($superImg); unset($superImgPath);

					//STATUS
					$latestFileTime = filemtime($realRootdir . $device . "/" .$zip);
					$outdated = !(($latestFileTime >= $lastSecRelease) && (($curTime - $latestFileTime) <= 3456000));
					$statusFileGeneric = $realRootdir . $device . "/status";
					$statusFileVersioned = $statusFileGeneric . "-" . $version;
					if(file_exists($statusFileVersioned)) {
						$statusFile = $statusFileVersioned;
					} else if(file_exists($statusFileGeneric)) {
						$statusFile = $statusFileGeneric;
					}
					unset($statusFileGeneric); unset($statusFileVersioned);
					if(file_exists($statusFile)) {
						list($resultStatusColor, $resultStatusMessage) = getStatus(file_get_contents($statusFile), $outdated);
					} else {
						list($resultStatusColor, $resultStatusMessage) = getStatus(false, $outdated);
					}
					unset($latestFileTime); unset($outdated); unset($statusFile);

					//BOOTLOADER
					$bootloaderInformationFile = $realRootdir . $device . "/bootloader_information";
					if(file_exists($bootloaderInformationFile)) {
						$bootloaderInformationArr = file($bootloaderInformationFile);
						$resultInstallMethod = $bootloaderInformationArr[0];
						$resultRelockable = $bootloaderInformationArr[1];
						$resultVerifiedBoot = $bootloaderInformationArr[2];
						if(!is_null($bootloaderInformationArr[3])) {
							$resultFirmwareIncluded = $bootloaderInformationArr[3];
						}
						unset($bootloaderInformationArr);
					}
					unset($bootloaderInformationFile);


					//RELEASE DATE
					$releaseDateFile = $realRootdir . $device . "/releasedate";
					if(file_exists($releaseDateFile)) {
						$resultReleaseDate = file_get_contents($releaseDateFile);
					}
					unset($releaseDateFile);

					//FRIENDLY NAME
					$friendlyNamePath = $realRootdir . $device . "/friendlyName";
					if(file_exists($friendlyNamePath)) {
						$resultFriendlyName = file_get_contents($friendlyNamePath);
						$resultFriendlyNameAlt = preg_replace('/ /', '<br>', $resultFriendlyName, 1);
					}
					unset($friendlyNamePath);

					//OUTPUT THE ROW/CARD
					if (!$golden ||
						($golden && (
							($resultStatusMessage == "Tested Working" || $resultStatusMessage == "Reported Working")
							&& ($resultRelockable == "Tested Working\n" || $resultRelockable == "Reported Working\n")
						))
					) {
					$downloads .= "<div class=\"card centero\" id=\"device-" . $device . "\">";
					$downloads .= "<h3><a style=\"color: inherit; text-decoration: none;\" href=\"#device-" . $device . "\">" . $trueName . "<small>" . $resultFriendlyName . "</small></a></h3>";
					$downloads .= "<ul>";
					$downloads .= "<li><a href=\"https://wiki.lineageos.org/devices/" . $trueName . "\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">Device Info</a></li>";
					$downloads .= "<li style=\"color:#" . $resultStatusColor . ";\">Status: " . $resultStatusMessage . "</li>";
					if(file_exists($realRootdir . $device . "/install.html")) {
						//XXX: This needs to be run twice to work
						$downloads .= "<li>Install Method: <a href=\"/builds/LineageOS/" . $device . "/install.html\" target=\"_blank\">" . $resultInstallMethod . "</a></li>";
					} else {
						$downloads .= "<li>Install Method: <a href=\"/pages/bootloader\" target=\"_blank\">" . $resultInstallMethod . "</a></li>";
					}
					$downloads .= "<li>Relockable: " . $resultRelockable . "</li>";
					$downloads .= "<li>Verified Boot: " . $resultVerifiedBoot . "</li>";
					//if(isset($resultReleaseDate)) {
					//	$downloads .= "<li>Release Date: " . $resultReleaseDate . "</li>";
					//}
					if(isset($resultFirmwareIncluded)) {
						$downloads .= "<li>Firmware Included: " . $resultFirmwareIncluded . "</li>";
					}
					$downloads .= "</ul><hr>";
					$downloads .= "<a href=\"" . $resultOTA . "\" download class=\"button primary\">Download</a><a href=\"" . $resultHashOTA . "\" download class=\"button inverse small\">512sum</a>";
					if(isset($resultRecovery)) {
						$downloads .= "<br><a href=\"" . $resultRecovery . "\" download class=\"button teritary\">Recovery</a><a href=\"" . $resultHashRecovery . "\" download class=\"button inverse small\">512sum</a></li>";
					}
					if(isset($resultFastboot)) {
						$downloads .= "<br><a href=\"" . $resultFastboot . "\" download class=\"button teritary\">Fastboot</a><a href=\"" . $resultHashFastboot . "\" download class=\"button inverse small\">512sum</a>";
					}
					$resultExtras = "";
					if(isset($resultKeyAVB)) {
						if(strlen($resultExtras) == 0) { $resultExtras .= "<br>"; }
						$resultExtras .= "<a href=\"" . $resultKeyAVB . "\" download class=\"button inverse small\">AVB Key</a>";
					}
					if(isset($resultCopyParts)) {
						if(strlen($resultExtras) == 0) { $resultExtras .= "<br>"; }
						$btnSyncTxt = "A/B Sync";
						if(isset($resultFirmwareIncluded)) {
							$btnSyncTxt = "<strike>A/B Sync</strike>";
						}
						$resultExtras .= "<a href=\"" . $resultCopyParts . "\" download class=\"button inverse small\">" . $btnSyncTxt . "</a>";
						unset($btnSyncTxt);
					}
					if(isset($resultSuperImg)) {
						if(strlen($resultExtras) == 0) { $resultExtras .= "<br>"; }
						$resultExtras .= "<a href=\"" . $resultSuperImg . "\" download class=\"button inverse small\">Super Image</a>";
					}
					$downloads .= $resultExtras;

					$downloads .= "</div>";

					//Generate the install steps
					if(!$golden) {
						writeInstallSteps($device, $realRootdir . $device . "/", trim($resultInstallMethod), isset($resultKeyAVB), isset($resultRecovery), isset($resultFastboot), (isset($resultCopyParts) && !isset($resultFirmwareIncluded)), $initialInstallFile, $otaInstallFile);
					}
					}

					unset($resultOTA);
					unset($resultHashOTA);
					unset($resultRecovery);
					unset($resultHashRecovery);
					unset($resultFastboot);
					unset($resultHashFastboot);
					unset($resultKeyAVB);
					unset($resultCopyParts);
					unset($resultSuperImg);
					unset($resultStatusColor);
					unset($resultStatusMessage);
					unset($resultInstallMethod);
					unset($resultRelockable);
					unset($resultVerifiedBoot);
					unset($resultReleaseDate);
					unset($resultFirmwareIncluded);
					unset($resultFriendlyName);
					unset($resultFriendlyNameAlt);
					unset($initialInstallFile);
					unset($otaInstallFile);
				}
				unset($zip); unset($trueName);
			}
			unset($files);
		}
		unset($device);
	}
	return $downloads;
}

function writeInstallSteps($deviceName, $path, $installMethod, $needsAVB, $needsRecovery, $needsFastboot, $needsSync, $initialInstallFile, $otaInstallFile) {
	if(str_starts_with($installMethod, "Fastboot")) {
		$fileSteps = fopen($path . "install.html", "w") or die("Unable to open file!");
		$steps = "<ol>\n";
		$steps .= "<li>Backup your device.</li>\n";
		$steps .= "<li>Follow the prerequisite steps <a href=\"/pages/bootloader#prereq\" target=\"_blank\">here</a></li>\n";
		if($installMethod === "Fastboot (Google)") {
			$steps .= "<li>Open the Phone app and type <code>*#*#2432546#*#*</code>, do not hit call</li>\n";
			$steps .= "<li>Enable 'Allow OEM unlocking' under 'Developer options' in Settings. You cannot proceed if it is greyed-out/disabled.</li>\n";
		}
		if($installMethod === "Fastboot (Fairphone)") {
			$steps .= "<li>Follow the vendor-specific unlock steps <a href=\"https://support.fairphone.com/hc/en-us/articles/10492476238865\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">here</a></li>\n";
		}
		if($installMethod === "Fastboot (HTC)") {
			$steps .= "<li>Follow the vendor-specific unlock steps <a href=\"https://www.htcdev.com/bootloader/\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">here</a></li>\n";
		}
		if($installMethod === "Fastboot (LGE)") {
			$steps .= "<li>Unfortunately LGE shutdown their unlock server and now unofficial unlock methods are required. Research and proceed with them at your own risk!</li>\n";
		}
		if($installMethod === "Fastboot (Lenovo)") {
			$steps .= "<li>Follow the vendor-specific unlock steps <a href=\"https://www.zui.com/iunlock\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">here</a></li>\n";
		}
		if($installMethod === "Fastboot (Motorola)") {
			$steps .= "<li>Follow the vendor-specific unlock steps <a href=\"https://motorola-global-portal.custhelp.com/app/standalone/bootloader/unlock-your-device-a\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">here</a></li>\n";
		}
		if($installMethod === "Fastboot (Sony)") {
			$steps .= "<li>Follow the vendor-specific unlock steps <a href=\"https://developer.sonymobile.com/unlockbootloader/unlock-yourboot-loader/\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">here</a></li>\n";
		}
		if($installMethod === "Fastboot (Xiaomi)") {
			$steps .= "<li>Follow the vendor-specific unlock steps <a href=\"https://global.account.xiaomi.com/pass/register\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">here</a></li>\n";
		}
		if($installMethod === "Fastboot (ASUS)") {
			$steps .= "<li>Follow the vendor-specific unlock steps <a href=\"https://www.asus.com/support\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">here</a></li>\n";
		}
		if(file_exists($path . "/combo-bootloader")) {
			$steps .= "<li>Reboot to the bootloader: " . file_get_contents($path . "/combo-bootloader") . "</li>\n";
		} else {
			$steps .= "<li>Reboot to the bootloader via key combination</li>\n";
		}
		if($installMethod === "Fastboot" || $installMethod === "Fastboot (Google)" || $installMethod === "Fastboot (Yandex)") {
			if(file_exists($path . "/command-unlock")) {
				$steps .= "<li><code>$ " . file_get_contents($path . "/command-unlock") . "</code></li>\n";
			} else {
				$steps .= "<li><code>$ fastboot oem unlock</code> or <code>$ fastboot flashing unlock</code></li>\n";
			}
			$steps .= "<li>Reboot the device, then reboot back to bootloader</li>\n";
		}
		if($needsAVB) {
			$steps .= "<li><code>$ fastboot erase avb_custom_key</code></li>\n";
			$steps .= "<li><code>$ fastboot flash avb_custom_key avb_pkmd-" . $deviceName . ".bin</code></li>\n";
			$steps .= "<li>Reboot to the bootloader</li>\n";
		}
		if(file_exists($path . "/super_empty-" . $deviceName . ".img")) {
			$steps .= "<li><code>$ fastboot wipe-super super_empty-" . $deviceName . ".img</code></li>\n";
			$steps .= "<li>Reboot to the bootloader</li>\n";
		}
		if($needsFastboot) {
			$steps .= "<li><code>$ fastboot update " . $initialInstallFile . "</code></li>\n";
		}
		if($needsRecovery) {
			$steps .= "<li><code>$ fastboot flash recovery " . $initialInstallFile . "</code></li>\n";
		}
		if(file_exists($path . "/combo-recovery")) {
			$steps .= "<li>Reboot to the recovery: " . file_get_contents($path . "/combo-recovery") . "</li>\n";
		} else {
			$steps .= "<li>Reboot to recovery (use volume buttons to navigate if on or key combination if off)</li>\n";
		}
		if($needsSync) {
			$steps .= "<li><code>$ adb sideload copy-partitions-" . $deviceName . "-release.zip</a></code></li>\n";
		}
		$steps .= "<li>Choose \"Apply update\", then \"Apply from ADB\", and <code>$ adb sideload " . $otaInstallFile . "</code></li>\n";
		$steps .= "<li>While still in the recovery perform a factory reset</li>\n";
		$steps .= "<li>Reboot into DivestOS. If it takes more than 10 minutes to boot then something is wrong. Do not let it sit for more than 10 minutes!</li>\n";
		$steps .= "<li>There are monthly updates. You MUST read the News page and backup your device before each update.</li>\n";
		$steps .= "</ol>";
		fwrite($fileSteps, $steps);
		fclose($fileSteps);
	}
}

function getStatus($status, $outdated) {
	$color = "03A9F4"; //LIGHT BLUE 500
	$message = "Unknown";
	if(!($status === false)) {
		switch($status) {
			case 0:
				$color = "4CAF50"; //GREEN 500
				$message = "Tested Working";
				break;
			case 6:
				$color = "1B5E20"; //GREEN 900
				$message = "Tested Working (Experimental)";
				break;
			case 7:
				$color = "4CAF50"; //GREEN 500
				$message = "Reported Working";
				break;
			case 8:
				$color = "8BC34A"; //LIGHT GREEN 500
				$message = "Very Likely Working";
				break;
			case 4:
				$color = "CDDC39"; //LIME 500
				$message = "Likely Working";
				break;
			case 5:
				$color = "673AB7"; //DEEP PURPLE 500
				$message = "Mostly Working";
				break;
			case 2:
				$color = "E91E63"; //PINK 500
				$message = "Untested";
				break;
			case 3:
				$color = "880E4F"; //PINK 900
				$message = "Untested (Experimental)";
				break;
			case 10:
				$color = "827717"; //LIME 900
				$message = "Likely Working (Experimental)";
				break;
			case 1:
				$color = "f44336"; //RED 500
				$message = "Broken";
				break;
		}
	}
	if($outdated) {
		$color = "f44336"; //RED 500
		$message = $message . " and Outdated";
	}
	return array($color, $message);
}

function error() {
	print("Invalid base!");
	http_response_code(400);
}

?>
					<div class="section" id="devices">
						<h2 class="centero" id="twenty">20.0 / 13.0 / Tiramisu<a href="#twenty">¶</a></h2>
						<div class="row" style="text-align: center;">
							<?php getDownloads("20.0"); ?>
						</div>
						<hr>
						<h2 class="centero" id="nineteen">19.1 / 12.1 / Snow Cone v2<a href="#nineteen">¶</a></h2>
						<div class="row" style="text-align: center;">
							<?php getDownloads("19.1"); ?>
						</div>
						<hr>
						<h2 class="centero" id="eighteen">18.1 / 11.0 / Red Velvet Cake<a href="#eighteen">¶</a></h2>
						<p class="centero"><mark class="secondary">In-Place upgrades to 18.1 on the following devices devices require a wipe due to their legacy keystore support being removed:</mark><br><mark class="secondary">bacon, clark, crackling, d852, d855, flox, fp2, hammerhead, m8, mako, shamu, victara</mark></p>
						<div class="row" style="text-align: center;">
							<?php getDownloads("18.1"); ?>
						</div>
						<hr>
						<h2 class="centero" id="seventeen">17.1 / 10.0 / Quince Tart / END OF LIFE<a href="#seventeen">¶</a></h2>
						<div class="row" style="text-align: center;">
							<?php getDownloads("17.1"); ?>
						</div>
						<hr>
						<h2 class="centero" id="sixteen">16.0 / 9.0 / Pie / END OF LIFE<a href="#sixteen">¶</a></h2>
						<div class="row" style="text-align: center;">
							<?php getDownloads("16.0"); ?>
						</div>
						<hr>
						<h2 class="centero" id="fifteen">15.1 / 8.1.0 / Oreo / END OF LIFE<a href="#fifteen">¶</a></h2>
						<div class="row" style="text-align: center;">
							<?php getDownloads("15.1"); ?>
						</div>
						<hr>
						<h2 class="centero" id="fourteen">14.1 / 7.1.2 / Nougat / END OF LIFE<a href="#fourteen">¶</a></h2>
						<div class="row" style="text-align: center;">
							<?php getDownloads("14.1"); ?>
						</div>
						<hr>
						<h2 class="centero" id="eleven">11.0 / 4.4.4 / KitKat / END OF LIFE / DEPRECATED<a href="#eleven">¶</a></h2>
						<div class="row" style="text-align: center;">
							<?php getDownloads("11.0"); ?>
						</div>
					</div>
