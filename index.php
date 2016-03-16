<?php

/*
 * Copyright 2014, Xorcom Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 */

require_once('../lib/template.php');
require_once('../lib/query.php');
require_once('../lib/util.php');
require_once('../xepmdb.php');

function __autoload($classname) {
	$filename = strtolower($classname) . '.php';
	if (file_exists($filename)) { include($filename); }
}

function generate_tabs() {
	$filenames = glob('*.php');
	natcasesort($filenames);
	return array_map(function($filename) {
		$filename = str_replace('_', ' ', pathinfo($filename, PATHINFO_FILENAME));
		return array('url' => urlencode($filename), 'text' => ucwords($filename));
	}, array_filter($filenames, function($filename) {
		return ($filename != 'index.php');
	}));
}

function generate_content() {
	$request_url = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
	if ($request_url == 'xepm-utils') { return null; }
	$classname = str_replace('+', '_', strtolower($request_url));
	ob_start();
	$classname::render();
	return ob_get_clean();
}

$tpl = new template('util_index.tpl');
$tpl->tabs = generate_tabs();
$tpl->content = generate_content();
$tpl->render();

?>
