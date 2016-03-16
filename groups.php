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

class groups {

	public static function render() {
		if (is_postback()) {
			query::begin_transaction();
			if (is_array(($name_array = util::array_get('name', $_POST))) &&
				is_array(($group_id_array = util::array_get('setting_group_id', $_POST)))) {
				foreach ($group_id_array as $i => $group_id) {
					$group_id = intval(trim($group_id));
					$name = trim(util::array_get($i, $name_array));
					if ($group_id > 0) {
						query('update `xepm_setting_groups` set
							`name` = ?
						where `setting_group_id` = ?',
						$name,
						$group_id);
					} else {
						$new_group_id = query('insert into `xepm_setting_groups` (
							`name`)
							values (?)', $name)->insert_id;
						array_push($group_id_array, $new_group_id);
					}
				}
				if (count($group_id_array) > 0) {
					delete_in('delete from `xepm_setting_groups`
						where `setting_group_id` not in (---)',
						$group_id_array);
				} else {
					query('truncate table `xepm_setting_groups`');
				}
			} else {
				query('truncate table `xepm_setting_groups`');
			}
			query::commit();
			util::redirect();
		}
		$tpl = new template('util_groups.tpl');
		$tpl->groups = xepmdb::provision_groups();
		$tpl->render();
	}
}

?>
