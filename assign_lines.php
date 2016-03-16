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

class assign_lines {

	private static function assigned_lines() {
		$assigned_lines = array();
		foreach(query('select
				`model_lines`.`model_line_id`,
				`model_lines`.`model_id`,
				`model_lines`.`line_name_id`,
				`model_lines`.`value`,
				`line_names`.`name` as `line_name`,
				`models`.`name` as `model_name`,
				`brands`.`name` as `brand_name`
			from `xepm_model_lines` as `model_lines`
			left join `xepm_line_names` as `line_names` on (
				`line_names`.`line_name_id` = `model_lines`.`line_name_id`)
			left join `xepm_models` as `models` on (
				`models`.`model_id` = `model_lines`.`model_id`)
			left join `xepm_brands` as `brands` on(
				`brands`.`brand_id` = `models`.`brand_id`)') as $assigned_line) {
			$assigned_lines[] = $assigned_line;
		}
		uasort($assigned_lines, function($a, $b) {
			if (($result = strnatcasecmp($a->brand_name, $b->brand_name)) == 0) {
				if (($result = strnatcasecmp($a->model_name, $b->model_name)) == 0) {
					$result = strnatcasecmp($a->line_name, $b->line_name);
				}
			}
			return $result;
		});
		return $assigned_lines;
	}

	public static function render() {
		if (is_postback()) {
			query::begin_transaction();
			if (is_array(($model_line_id_array = util::array_get('model_line_id', $_POST))) &&
				is_array(($model_id_array = util::array_get('model_id', $_POST))) &&
				is_array(($line_name_id_array = util::array_get('line_name_id', $_POST))) &&
				is_array(($value_array = util::array_get('value', $_POST)))) {
				foreach ($model_line_id_array as $i => &$model_line_id) {
					$model_line_id = intval(trim($model_line_id));
					$model_id = intval(trim(util::array_get($i, $model_id_array)));
					$line_name_id = intval(trim(util::array_get($i, $line_name_id_array)));
					$value = trim(util::array_get($i, $value_array));
					if ($model_line_id > 0) {
						query('update `xepm_model_lines` set
								`model_id` = ?,
								`line_name_id` = ?,
								`value` = ?
							where `model_line_id` = ?',
							$model_id,
							$line_name_id,
							$value,
							$model_line_id);
					} else {
						$model_line_id = query('insert into `xepm_model_lines` (
								`model_id`,
								`line_name_id`,
								`value`
							) values (?, ?, ?)',
							$model_id,
							$line_name_id,
							$value
						)->insert_id;
					}
				}
				if (count($model_line_id_array) > 0) {
					delete_in('delete from `xepm_model_lines`
						where `model_line_id` not in (---)',
						$model_line_id_array);
				} else {
					query('truncate table `xepm_model_lines`');
				}
			} else {
				query('truncate table `xepm_model_lines`');
			}
			query::commit();
			util::redirect();
		}

		$tpl = new template('util_assign_lines.tpl');
		$tpl->models = xepmdb::models_with_brand_with_configuration();
		$tpl->lines = xepmdb::lines();
		$tpl->assigned_lines = self::assigned_lines();
		$tpl->render();
	}
}

?>
