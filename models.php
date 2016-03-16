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

class models {

	public static function render() {
		if (is_postback()) {
			query::begin_transaction();
			if (is_array(($brand_id_array = util::array_get('brand_id', $_POST))) &&
				is_array(($name_array = util::array_get('name', $_POST))) &&
				is_array(($sip_lines_array = util::array_get('sip_lines', $_POST))) &&
				is_array(($exp_modules_array = util::array_get('exp_modules', $_POST))) &&
				is_array(($model_id_array = util::array_get('model_id', $_POST)))) {
				foreach ($model_id_array as $i => &$model_id) {
					$model_id = intval(trim($model_id));
					$brand_id = intval(trim(util::array_get($i, $brand_id_array)));
					$name = trim(util::array_get($i, $name_array));
					$sip_lines = intval(trim(util::array_get($i, $sip_lines_array)));
					$exp_modules = intval(trim(util::array_get($i, $exp_modules_array)));
					if ($model_id > 0) {
						query('update `xepm_models` set
								`brand_id` = ?,
								`sip_lines` = ?,
								`iax2_lines` = ?,
								`dss_buttons` = ?,
								`exp_modules` = ?
							where `model_id` = ?',
							$brand_id,
							$sip_lines,
							0,
							0,
							$exp_modules,
							$model_id);
					} else {
						$model_id = query('insert into `xepm_models` (
								`brand_id`,
								`sip_lines`,
								`iax2_lines`,
								`dss_buttons`,
								`exp_modules`,
								`name`
							) values (?, ?, ?, ?, ?, ?)',
							$brand_id,
							$sip_lines,
							0,
							0,
							$exp_modules,
							$name)->insert_id;
					}
				}
				if (count($model_id_array) > 0) {
					delete_in('delete from `xepm_models`
						where `model_id` not in(---)',
						$model_id_array);
				} else {
					query('truncate table `xepm_models`');
				}
			} else {
				query('truncate table `xepm_models`');
			}
			query::commit();
			util::redirect();
		}

		$tpl = new template('util_models.tpl');
		$tpl->models = xepmdb::models();
		$tpl->brands = xepmdb::brands();
		$tpl->render();
	}
}

?>
