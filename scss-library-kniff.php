<?php
/*
Plugin Name: SCSS-Library Kniff Fork
Description: Adds support for SCSS stylesheets to wp_enqueue_style.
Author: Florian Groh
GitHub Plugin URI: flowgrow/scss-library-kniff
Primary Branch: master
Release Asset: true
Version: 0.3.1
Tested up to: 5.9.2
Requires PHP: 7.1
Text Domain: scsslib

Copyright 2019-2022 Juan Sebastián Echeverry (baxtian.echeverry@gmail.com)
Copyright 2022-2022 Florian Groh (florian@kniff.at)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Si estamos usando wp-cli, no correr el plugin
if (defined('WP_CLI') && WP_CLI) {
	return;
}

require_once('vendor/autoload.php');

ScssLibrary\ScssLibrary::get_instance();
ScssLibrary\Settings\ScssLibrary::get_instance();
