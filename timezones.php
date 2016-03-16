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

class timezones {

	public static function render() {
		if (is_postback()) {
			if (($brand_id = intval(trim(util::array_get('brand_id', $_POST)))) > 0) {
				query::begin_transaction();
				if (is_array(($name_array = util::array_get('name', $_POST))) &&
					is_array(($offset_array = util::array_get('offset', $_POST))) &&
					is_array(($value_array = util::array_get('value', $_POST))) &&
					is_array(($timezone_id_array = util::array_get('timezone_id', $_POST)))) {
					foreach ($timezone_id_array as $i => &$timezone_id) {
						$timezone_id = intval(trim($timezone_id));
						$name = trim(util::array_get($i, $name_array));
						$offset = intval(trim(util::array_get($i, $offset_array)));
						$value = trim(util::array_get($i, $value_array));
						if ($timezone_id > 0) {
							query('update `xepm_brand_timezones` set
									`name` = ?,
									`offset` = ?,
									`value` = ?
								where `timezone_id` = ?',
								$name,
								$offset,
								$value,
								$timezone_id);
						} else {
							$timezone_id = query('insert into `xepm_brand_timezones` (
									`brand_id`,
									`name`,
									`offset`,
									`value`
								) values (?, ?, ?, ?)',
								$brand_id,
								$name,
								$offset,
								$value)->insert_id;
						}
					}
					if (count($timezone_id_array) > 0) {
						delete_in('delete from `xepm_brand_timezones`
							where `brand_id` = ?
							and `timezone_id` not in (---)',
							$brand_id,
							$timezone_id_array);
					} else {
						query('delete from `xepm_brand_timezones`
							where `brand_id` = ?', $brand_id);
					}
				} else {
					query('delete from `xepm_brand_timezones`
						where `brand_id` = ?', $brand_id);
				}
				query::commit();
			}
			util::redirect();
		}

		$brands = xepmdb::brands();
		$brand_id = (count($brands) > 0) ? current($brands)->brand_id : 0;
		$brand_id = intval(trim(util::array_get('brand_id', $_GET, $brand_id)));

		$tpl = new template('util_timezones.tpl');
		$tpl->brand_id = $brand_id;
		$tpl->brands = $brands;
		$tpl->timezones = xepmdb::brand_timezones($brand_id);
		$tpl->render();
	}
}

?>
