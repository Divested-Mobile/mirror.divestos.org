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

//Disable some dangerous features
ini_set("allow_url_fopen", "Off");
ini_set("allow_url_include", "Off");
ini_set("expose_php", "Off");
ini_set("file_uploads", false);

//Hardening
ini_set("post_max_size", "2K");

//Disable error/trace reporting
ini_set("display_errors", "Off");
error_reporting(E_ERROR | E_PARSE);

?>
