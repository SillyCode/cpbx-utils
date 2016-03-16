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

class ouis {

	public static function render() {
		if (is_postback()) {
			if (($brand_id = intval(trim(util::array_get('brand_id', $_POST)))) > 0) {
				query::begin_transaction();
				query('delete from `xepm_brand_ouis` where `brand_id` = ?', $brand_id);
				if (is_array(($oui_array = util::array_get('oui', $_POST)))) {
					foreach ($oui_array as $oui) {
						$oui = trim($oui);
						query('insert ignore into `xepm_brand_ouis` (
								`brand_id`,
								`oui`
							) values (?, unhex(?))',
							$brand_id, $oui);
					}
				}
				query::commit();
			}
			util::redirect();
		}

		$brands = xepmdb::brands();
		$brand_id = (count($brands) > 0) ? current($brands)->brand_id : 0;
		$brand_id = intval(trim(util::array_get('brand_id', $_GET, $brand_id)));

		$tpl = new template('util_ouis.tpl');
		$tpl->brand_id = $brand_id;
		$tpl->brands = $brands;
		$tpl->ouis = xepmdb::brand_ouis($brand_id);
		$tpl->render();
	}
}

?>
