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

class brands {
	public static function render() {
		if (is_postback()) {
			query::begin_transaction();
			if (is_array(($name_array = util::array_get('name', $_POST))) &&
				is_array(($brand_id_array = util::array_get('brand_id', $_POST)))) {
				foreach ($brand_id_array as $i => &$brand_id) {
					$name = trim(util::array_get($i, $name_array));
					if ($brand_id > 0) {
						query('update `xepm_brands` set
							`name` = ?
							where `brand_id` = ?',
						$name,
						$brand_id);
					} else {
						$brand_id = query('insert into `xepm_brands` (
								`name`
							) values (?)',
							$name
						)->insert_id;
					}
				}
				if (count($brand_id_array) > 0) {
					delete_in('delete from `xepm_brands`
						where `brand_id` not in (---)',
						$brand_id_array);
				} else {
					query('truncate table `xepm_brands`');
				}
			} else {
				query('truncate table `xepm_brands`');
			}
			query::commit();
			util::redirect();
		}
		$tpl = new template('util_brands.tpl');
		$tpl->brands = xepmdb::brands();
		$tpl->render();
	}
}

?>
