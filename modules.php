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

class modules {

	public static function render() {
		if (is_postback()) {
			query::begin_transaction();
			if (is_array(($name_array = util::array_get('name', $_POST))) &&
				is_array(($module_id_array = util::array_get('module_id', $_POST))) &&
				is_array(($brand_id_array = util::array_get('brand_id', $_POST))) &&
				is_array(($button_count_array = util::array_get('button_count', $_POST)))) {
				foreach ($module_id_array as $i => &$module_id) {
					$module_id = intval(trim($module_id));
					$brand_id = intval(trim(util::array_get($i, $brand_id_array)));
					$name = trim(util::array_get($i, $name_array));
					$button_count = intval(trim(util::array_get($i, $button_count_array)));
					if ($module_id > 0) {
						query('update `xepm_modules` set
							`brand_id` = ?,
							`name` = ?,
							`buttons` = ?
							where `module_id` = ?',
						$brand_id,
						$name,
						$button_count,
						$module_id);
					} else {
						$module_id = query('insert into `xepm_modules` (
								`brand_id`,
								`name`,
								`buttons`
							) values (?, ?, ?)',
							$brand_id,
							$name,
							$button_count
						)->insert_id;
					}
				}
				if (count($module_id_array) > 0) {
					delete_in('delete from `xepm_modules`
						where `module_id` not in (---)',
						$module_id_array);
				} else {
					query('truncate table `xepm_modules`');
				}
			} else {
				query('truncate table `xepm_modules`');
			}
			query::commit();
			util::redirect();
		}

		$tpl = new template('util_modules.tpl');
		$tpl->brands = xepmdb::brands();
		$tpl->modules = xepmdb::modules();
		$tpl->render();
	}
}

?>
