<?php
//Copyright (c) 2017-2020 Divested Computing Group
//
//This program is free software: you can redistribute it and/or modify
//it under the terms of the GNU Lesser General Public License as published by
//the Free Software Foundation, either version 3 of the License, or
//(at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU Lesser General Public License for more details.
//
//You should have received a copy of the GNU Lesser General Public License
//along with this program.  If not, see <https://www.gnu.org/licenses/>.

$SBNR_DOMAINS_CLEARNET_ONLY = array("divestos.org");
$SBNR_DOMAINS_ONIONS_ONLY = array("divestoseb5nncsydt7zzf5hrfg44md4bxqjs5ifcv4t7gt7u6ohjyyd.onion", "2ceyag7ppvhliszes2v25n5lmpwhzqrc7sv72apqka6hwggfi42y2uid.onion");
$SBNR_DOMAINS_ALL = array_merge($SBNR_DOMAINS_CLEARNET_ONLY, $SBNR_DOMAINS_ONIONS_ONLY);

$SBNR_MIRRORS_ONIONS = array("http://divestoseb5nncsydt7zzf5hrfg44md4bxqjs5ifcv4t7gt7u6ohjyyd.onion/builds/", "http://2ceyag7ppvhliszes2v25n5lmpwhzqrc7sv72apqka6hwggfi42y2uid.onion/builds/");
//THESE NEXT ENTRIES MUST BE KEPT IN SYNC/ORDER
$SBNR_MIRRORS_CLEARNET = array("https://divestos.org/builds/");
$SBNR_MIRRORS_CLEARNET_LATITUDES = array();
$SBNR_MIRRORS_CLEARNET_LONGITUDES = array();

?>
