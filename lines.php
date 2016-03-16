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

class lines {

	public static function render() {
		if (is_postback()) {
			query::begin_transaction();
			if (is_array(($name_array = util::array_get('name', $_POST))) &&
				is_array(($line_name_id_array = util::array_get('line_name_id', $_POST)))) {
				foreach ($line_name_id_array as $i => &$line_name_id) {
					$line_name_id = intval(trim($line_name_id));
					$name = trim(util::array_get($i, $name_array));
					if ($line_name_id > 0) {
						query('update `xepm_line_names` set
								`name` = ?
							where `line_name_id` = ?',
							$name,
							$line_name_id);
					} else {
						$line_name_id = query('insert into `xepm_line_names` (
								`name`
							) values (?)',
							$name)->insert_id;
					}
				}
				if (count($line_name_id_array) > 0) {
					delete_in('delete from `xepm_line_names`
						where `line_name_id` not in (---)',
						$line_name_id_array);
				} else {
					query('truncate table `xepm_line_names`');
				}
			} else {
				query('truncate table `xepm_line_names`');
			}
			query::commit();
			util::redirect();
		}

		$tpl = new template('util_lines.tpl');
		$tpl->lines = xepmdb::lines();
		$tpl->render();
	}
}

?>
