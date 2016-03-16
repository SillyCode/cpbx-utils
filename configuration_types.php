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

class configuration_types {
	public static function render() {
		if (is_postback()) {
			query::begin_transaction();
			if (is_array(($name_array = util::array_get('name', $_POST))) &&
				is_array(($model_id_array = util::array_get('model_id', $_POST))) &&
				is_array(($type_id_array = util::array_get('type_id', $_POST)))) {
				foreach ($type_id_array as $i => &$type_id) {
					$configuration_type_name_id = 0;
					$type_id = intval(trim($type_id));
					$model_id = intval(trim(util::array_get($i, $model_id_array)));
					$name = trim(util::array_get($i, $name_array));
					foreach(query('select
							`configuration_type_name_id`
						from `xepm_configuration_type_names`
						where `name` = ?',
						$name) as $configuration_name_id) {
						$configuration_type_name_id = $configuration_name_id->configuration_type_name_id;
					}
					if(!isset($configuration_type_name_id) || $configuration_type_name_id < 1) {
						$configuration_type_name_id = query('insert into `xepm_configuration_type_names` (
							`name`
							) values (?)',
							$name)->insert_id;
					}
					if ($type_id > 0) {
						query('update `xepm_configuration_types` as `configuration_types`
								left join `xepm_configuration_type_names` as `configuration_type_names` on (
								`configuration_types`.`configuration_type_name_id` = `configuration_type_names`.`configuration_type_name_id`)
								set `configuration_types`.`configuration_type_name_id` = ?
							where `configuration_types`.`configuration_type_id` = ?',
							$configuration_type_name_id,
							$type_id);
					} else {
						$type_id = query('insert into `xepm_configuration_types` (
								`model_id`,
								`configuration_type_name_id`
							) values (?,?)',
							$model_id,
							$configuration_type_name_id)->insert_id;
					}
				}
				if (count($type_id_array) > 0) {
					delete_in('delete from `xepm_configuration_types`
						where `configuration_type_id` not in (---)',
						$type_id_array);
				} else {
					query('truncate table `xepm_configuration_types`');
				}
			} else {
				query('truncate table `xepm_configuration_types`');
			}
			query::commit();
			util::redirect();
		}
		$tpl = new template('util_configuration_types.tpl');
		$tpl->devices = xepmdb::brands_with_models();
		$tpl->configurations = xepmdb::model_with_configuration();
		$tpl->render();
	}
}

?>
