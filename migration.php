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

class migration {

	private static function export() {
		if (($ampconf = util::ampconf()) !== null) {
			@set_time_limit(0); // mysqldump command may take a long time
			$tables = array(
				'xepm_brands',
				'xepm_modules',
				'xepm_line_names',
				'xepm_setting_names',
				'xepm_brand_ouis',
				'xepm_brand_timezones',
				'xepm_models',
				'xepm_configuration_type_names',
				'xepm_configuration_types',
				'xepm_setting_groups',
				'xepm_model_lines',
				'xepm_model_modules',
				'xepm_settings');
			$output = tempnam(sys_get_temp_dir(), '');
			$mysqldump = sprintf(
				'mysqldump --compact --extended-insert=false --hex-blob -u %s -p%s %s %s',
				$ampconf->AMPDBUSER,
				$ampconf->AMPDBPASS,
				$ampconf->AMPDBNAME,
				implode(' ', $tables));
			foreach(query('select version from modules where modulename="xepm"') as $module_version) {
				$version = $module_version->version;
			}
			$command = '(echo -e "# version: '. $version .'"; echo -e "SET foreign_key_checks = 0;\nSET autocommit = 0;\nSTART TRANSACTION;" && ' .
				$mysqldump . ' && echo "COMMIT;") | sed "/^\/\*/d;/^$/d" | gzip -c >> ' . $output;
			if (system($command , $errno) !== false && $errno == 0) {
				header('Content-disposition: attachment; filename=xepm.sql.gz');
				header('Content-type: application/x-gzip');
				readfile($output);
			} else {
				echo 'Command failed';
			}
			@unlink($output);
			exit;
		}
	}

	private static function append_import($srcdb, $dstdb) {
		query::begin_transaction();
		query('insert ignore into `' . $dstdb . '`.`xepm_brands` (`name`)
			select
				`brands`.`name`
			from `' . $srcdb . '`.`xepm_brands` as `brands`');
		query('insert ignore into `' . $dstdb . '`.`xepm_modules` (`brand_id`, `buttons`, `name`)
			select
				`dst_brands`.`brand_id`,
				`modules`.`buttons`,
				`modules`.`name`
			from `' . $srcdb . '`.`xepm_modules` as `modules`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `modules`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)');
		query('insert ignore into `' . $dstdb . '`.`xepm_line_names` (`name`)
			select
				`line_names`.`name`
			from `' . $srcdb . '`.`xepm_line_names` as `line_names`');
		query('insert ignore into `' . $dstdb . '`.`xepm_setting_names` (`name`)
			select
				`setting_names`.`name`
			from `' . $srcdb . '`.`xepm_setting_names` as `setting_names`');
		query('insert ignore into `' . $dstdb . '`.`xepm_brand_ouis` (`brand_id`, `oui`)
			select
				`dst_brands`.`brand_id`,
				`ouis`.`oui`
			from `' . $srcdb . '`.`xepm_brand_ouis` as `ouis`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `ouis`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)');
		query('insert ignore into `' . $dstdb . '`.`xepm_brand_timezones` (`brand_id`, `offset`, `name`, `value`)
			select
				`dst_brands`.`brand_id`,
				`timezones`.`offset`,
				`timezones`.`name`,
				`timezones`.`value`
			from `' . $srcdb . '`.`xepm_brand_timezones` as `timezones`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `timezones`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)');
		query('insert ignore into `' . $dstdb . '`.`xepm_models` (`brand_id`, `sip_lines`, `iax2_lines`, `dss_buttons`, `exp_modules`, `name`)
			select
				`dst_brands`.`brand_id`,
				`models`.`sip_lines`,
				`models`.`iax2_lines`,
				`models`.`dss_buttons`,
				`models`.`exp_modules`,
				`models`.`name`
			from `' . $srcdb . '`.`xepm_models` as `models`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `models`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)');
		query('insert ignore into `' . $dstdb . '`.`xepm_configuration_type_names` (`name`)
			select
				`configuration_type_names`.`name`
			from `' . $srcdb . '`.`xepm_configuration_type_names` as `configuration_type_names`');
		query('insert ignore into `' . $dstdb . '`.`xepm_configuration_types` (`model_id`, `configuration_type_name_id`)
			select
				`dst_models`.`model_id`,
				`dst_configuration_type_names`.`configuration_type_name_id`
			from `' . $srcdb . '`.`xepm_configuration_types` as `configuration_types`
			left join `' . $srcdb . '`.`xepm_models` as `src_models` on
				(`src_models`.`model_id` = `configuration_types`.`model_id`)
			left join `' . $srcdb . '`.`xepm_configuration_type_names` as `src_configuration_type_names` on
				(`src_configuration_type_names`.`configuration_type_name_id` = `configuration_types`.`configuration_type_name_id`)
			left join `' . $dstdb . '`.`xepm_configuration_type_names` as `dst_configuration_type_names` on
				(`dst_configuration_type_names`.`name` = `src_configuration_type_names`.`name`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`name` = `src_models`.`name`)
			where `dst_models`.`model_id` is not null');
		query('insert ignore into `' . $dstdb . '`.`xepm_setting_groups` (`name`, `group_type`)
			select
				`setting_groups`.`name`,
				`setting_groups`.`group_type`
			from `' . $srcdb . '`.`xepm_setting_groups` as `setting_groups`');
		query('insert ignore into `' . $dstdb . '`.`xepm_model_lines` (`model_id`, `line_name_id`, `value`)
			select
				`dst_models`.`model_id`,
				`dst_line_names`.`line_name_id`,
				`model_lines`.`value`
			from `' . $srcdb . '`.`xepm_model_lines` as `model_lines`
			left join `' . $srcdb . '`.`xepm_models` as `src_models` on
				(`src_models`.`model_id` = `model_lines`.`model_id`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`name` = `src_models`.`name`)
			left join `' . $srcdb . '`.`xepm_line_names` as `src_line_names` on
				(`src_line_names`.`line_name_id` = `model_lines`.`line_name_id`)
			left join `' . $dstdb . '`.`xepm_line_names` as `dst_line_names` on
				(`dst_line_names`.`name` = `src_line_names`.`name`)
			where `dst_models`.`model_id` is not null');
		query('insert ignore into `' . $dstdb . '`.`xepm_model_modules` (`model_id`, `module_id`)
			select
				`dst_models`.`model_id`,
				`dst_modules`.`module_id`
			from `' . $srcdb . '`.`xepm_model_modules` as `model_modules`
			left join `' . $srcdb . '`.`xepm_models` as `src_models` on
				(`src_models`.`model_id` = `model_modules`.`model_id`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`name` = `src_models`.`name`)
			left join `' . $srcdb . '`.`xepm_modules` as `src_modules` on
				(`src_modules`.`module_id` = `model_modules`.`module_id`)
			left join `' . $dstdb . '`.`xepm_modules` as `dst_modules` on
				(`dst_modules`.`name` = `src_modules`.`name`)
			where `dst_models`.`model_id` is not null');


		query('insert ignore into `' . $dstdb . '`.`xepm_settings` (`setting_group_id`, `parent_id`, `configuration_type_id`, `setting_name_id`, `value`)
			select
				`dst_setting_groups`.`setting_group_id`,
				null,
				`dst_configuration_types`.`configuration_type_id`,
				`dst_setting_names`.`setting_name_id`,
				`src_settings`.`value`
			from `' . $srcdb .'`.`xepm_brands` as `src_brands`
			left join `' . $srcdb .'`.`xepm_models` as `src_models` on
				(`src_models`.`brand_id` = `src_brands`.`brand_id`)
			left join `' . $srcdb .'`.`xepm_configuration_types` as `src_configuration_types` on
				(`src_configuration_types`.`model_id` = `src_models`.`model_id`)
			left join `' . $srcdb .'`.`xepm_configuration_type_names` as `src_configuration_type_names` on
				(`src_configuration_type_names`.`configuration_type_name_id` = `src_configuration_types`.`configuration_type_name_id`)
			left join `' . $srcdb .'`.`xepm_settings` as `src_settings` on
				(`src_settings`.`configuration_type_id` = `src_configuration_types`.`configuration_type_id`)
			left join `' . $srcdb .'`.`xepm_setting_names` as `src_setting_names` on
				(`src_setting_names`.`setting_name_id` = `src_settings`.`setting_name_id`)
			left join `' . $srcdb .'`.`xepm_setting_groups` as `src_setting_groups` on
				(`src_setting_groups`.`setting_group_id` = `src_settings`.`setting_group_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`brand_id` = `dst_brands`.`brand_id` and
					`dst_models`.`name` = `src_models`.`name`)
			left join `' . $dstdb . '`.`xepm_configuration_type_names` as `dst_configuration_type_names` on
				(`dst_configuration_type_names`.`name` = `src_configuration_type_names`.`name`)
			left join `' . $dstdb . '`.`xepm_configuration_types` as `dst_configuration_types` on
				(`dst_configuration_types`.`model_id` = `dst_models`.`model_id` and
					`dst_configuration_types`.`configuration_type_name_id` = `dst_configuration_type_names`.`configuration_type_name_id`)
			left join `' . $dstdb . '`.`xepm_setting_names` as `dst_setting_names` on
				(`dst_setting_names`.`name` = `src_setting_names`.`name`)
			left join `' . $dstdb . '`.`xepm_setting_groups` as `dst_setting_groups` on
				(`dst_setting_groups`.`name` = `src_setting_groups`.`name`)
			where `src_settings`.`parent_id` is null and
				`dst_setting_groups`.`setting_group_id` is not null and
					`dst_configuration_types`.`configuration_type_id` is not null and
						`dst_setting_names`.`setting_name_id` is not null');

		query('insert ignore into `' . $dstdb . '`.`xepm_settings` (`setting_group_id`, `parent_id`, `configuration_type_id`, `setting_name_id`, `value`)
			select
				`dst_setting_groups`.`setting_group_id`,
				`dst_parent_settings`.`setting_id`,
				`dst_configuration_types`.`configuration_type_id`,
				`dst_setting_names`.`setting_name_id`,
				`src_settings`.`value`
			from `' . $srcdb .'`.`xepm_brands` as `src_brands`
			left join `' . $srcdb .'`.`xepm_models` as `src_models` on
				(`src_models`.`brand_id` = `src_brands`.`brand_id`)
			left join `' . $srcdb .'`.`xepm_configuration_types` as `src_configuration_types` on
				(`src_configuration_types`.`model_id` = `src_models`.`model_id`)
			left join `' . $srcdb .'`.`xepm_configuration_type_names` as `src_configuration_type_names` on
				(`src_configuration_type_names`.`configuration_type_name_id` = `src_configuration_types`.`configuration_type_name_id`)
			left join `' . $srcdb .'`.`xepm_settings` as `src_settings` on
				(`src_settings`.`configuration_type_id` = `src_configuration_types`.`configuration_type_id`)
			left join `' . $srcdb .'`.`xepm_setting_names` as `src_setting_names` on
				(`src_setting_names`.`setting_name_id` = `src_settings`.`setting_name_id`)
			left join `' . $srcdb .'`.`xepm_setting_groups` as `src_setting_groups` on
				(`src_setting_groups`.`setting_group_id` = `src_settings`.`setting_group_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`brand_id` = `dst_brands`.`brand_id` and
					`dst_models`.`name` = `src_models`.`name`)
			left join `' . $dstdb . '`.`xepm_configuration_type_names` as `dst_configuration_type_names` on
				(`dst_configuration_type_names`.`name` = `src_configuration_type_names`.`name`)
			left join `' . $dstdb . '`.`xepm_configuration_types` as `dst_configuration_types` on
				(`dst_configuration_types`.`model_id` = `dst_models`.`model_id` and
					`dst_configuration_types`.`configuration_type_name_id` = `dst_configuration_type_names`.`configuration_type_name_id`)
			left join `' . $dstdb . '`.`xepm_setting_names` as `dst_setting_names` on
				(`dst_setting_names`.`name` = `src_setting_names`.`name`)
			left join `' . $dstdb . '`.`xepm_setting_groups` as `dst_setting_groups` on
				(`dst_setting_groups`.`name` = `src_setting_groups`.`name`)
			left join `' . $srcdb .'`.`xepm_settings` as `src_parent_settings` on
				(`src_parent_settings`.`setting_id` = `src_settings`.`parent_id`)
			left join `' . $srcdb .'`.`xepm_setting_names` as `src_parent_setting_names` on
				(`src_parent_setting_names`.`setting_name_id` = `src_parent_settings`.`setting_name_id`)
			left join `' . $dstdb . '`.`xepm_setting_names` as `dst_parent_setting_names` on
				(`dst_parent_setting_names`.`name` = `src_parent_setting_names`.`name`)
			left join `' . $dstdb . '`.`xepm_settings` as `dst_parent_settings` on
				(`dst_parent_settings`.`configuration_type_id` = `dst_configuration_types`.`configuration_type_id` and
					`dst_parent_settings`.`setting_name_id` = `dst_parent_setting_names`.`setting_name_id`)
			where `src_settings`.`parent_id` is not null and
				`dst_setting_groups`.`setting_group_id` is not null and
					`dst_parent_settings`.`setting_id` is not null and
						`dst_configuration_types`.`configuration_type_id` is not null and
							`dst_setting_names`.`setting_name_id` is not null');
		query::commit();
	}

	private static function update_import($srcdb, $dstdb) {
		query::begin_transaction();
		query('insert ignore into `' . $dstdb . '`.`xepm_brands` (`name`)
			select
				`brands`.`name`
			from `' . $srcdb . '`.`xepm_brands` as `brands`');
		query('insert into `' . $dstdb . '`.`xepm_modules` (`brand_id`, `buttons`, `name`)
			select
				`dst_brands`.`brand_id`,
				`modules`.`buttons`,
				`modules`.`name`
			from `' . $srcdb . '`.`xepm_modules` as `modules`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `modules`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)
			on duplicate key update
				`buttons` = values(`buttons`)');
		query('insert ignore into `' . $dstdb . '`.`xepm_line_names` (`name`)
			select
				`line_names`.`name`
			from `' . $srcdb . '`.`xepm_line_names` as `line_names`');
		query('insert ignore into `' . $dstdb . '`.`xepm_setting_names` (`name`)
			select
				`setting_names`.`name`
			from `' . $srcdb . '`.`xepm_setting_names` as `setting_names`');
		query('insert ignore into `' . $dstdb . '`.`xepm_brand_ouis` (`brand_id`, `oui`)
			select
				`dst_brands`.`brand_id`,
				`ouis`.`oui`
			from `' . $srcdb . '`.`xepm_brand_ouis` as `ouis`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `ouis`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)');
		query('insert into `' . $dstdb . '`.`xepm_brand_timezones` (`brand_id`, `offset`, `name`, `value`)
			select
				`dst_brands`.`brand_id`,
				`timezones`.`offset`,
				`timezones`.`name`,
				`timezones`.`value`
			from `' . $srcdb . '`.`xepm_brand_timezones` as `timezones`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `timezones`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)
			on duplicate key update
				`value` = values(`value`)');
		query('insert into `' . $dstdb . '`.`xepm_models` (`brand_id`, `sip_lines`, `iax2_lines`, `dss_buttons`, `exp_modules`, `name`)
			select
				`dst_brands`.`brand_id`,
				`models`.`sip_lines`,
				`models`.`iax2_lines`,
				`models`.`dss_buttons`,
				`models`.`exp_modules`,
				`models`.`name`
			from `' . $srcdb . '`.`xepm_models` as `models`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `models`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)
			on duplicate key update
				`sip_lines` = values(`sip_lines`),
				`iax2_lines` = values(`iax2_lines`),
				`dss_buttons` = values(`dss_buttons`),
				`exp_modules` = values(`exp_modules`)');
		query('insert ignore into `' . $dstdb . '`.`xepm_configuration_type_names` (`name`)
			select
				`configuration_type_names`.`name`
			from `' . $srcdb . '`.`xepm_configuration_type_names` as `configuration_type_names`');
		query('insert ignore into `' . $dstdb . '`.`xepm_configuration_types` (`model_id`, `configuration_type_name_id`)
			select
				`dst_models`.`model_id`,
				`dst_configuration_type_names`.`configuration_type_name_id`
			from `' . $srcdb . '`.`xepm_configuration_types` as `configuration_types`
			left join `' . $srcdb . '`.`xepm_models` as `src_models` on
				(`src_models`.`model_id` = `configuration_types`.`model_id`)
			left join `' . $srcdb . '`.`xepm_configuration_type_names` as `src_configuration_type_names` on
				(`src_configuration_type_names`.`configuration_type_name_id` = `configuration_types`.`configuration_type_name_id`)
			left join `' . $dstdb . '`.`xepm_configuration_type_names` as `dst_configuration_type_names` on
				(`dst_configuration_type_names`.`name` = `src_configuration_type_names`.`name`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`name` = `src_models`.`name`)');
		query('insert into `' . $dstdb . '`.`xepm_setting_groups` (`name`, `group_type`)
			select
				`setting_groups`.`name`,
				`setting_groups`.`group_type`
			from `' . $srcdb . '`.`xepm_setting_groups` as `setting_groups`
			on duplicate key update
				`name` = values(`name`),
				`group_type` = values(`group_type`)');
		query('insert into `' . $dstdb . '`.`xepm_model_lines` (`model_id`, `line_name_id`, `value`)
			select
				`dst_models`.`model_id`,
				`dst_line_names`.`line_name_id`,
				`model_lines`.`value`
			from `' . $srcdb . '`.`xepm_model_lines` as `model_lines`
			inner join `' . $srcdb . '`.`xepm_models` as `src_models` on
				(`src_models`.`model_id` = `model_lines`.`model_id`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`name` = `src_models`.`name`)
			left join `' . $srcdb . '`.`xepm_line_names` as `src_line_names` on
				(`src_line_names`.`line_name_id` = `model_lines`.`line_name_id`)
			left join `' . $dstdb . '`.`xepm_line_names` as `dst_line_names` on
				(`dst_line_names`.`name` = `src_line_names`.`name`)
			on duplicate key update
				`value` = values(`value`)');
		query('insert ignore into `' . $dstdb . '`.`xepm_model_modules` (`model_id`, `module_id`)
			select
				`dst_models`.`model_id`,
				`dst_modules`.`module_id`
			from `' . $srcdb . '`.`xepm_model_modules` as `model_modules`
			left join `' . $srcdb . '`.`xepm_models` as `src_models` on
				(`src_models`.`model_id` = `model_modules`.`model_id`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`name` = `src_models`.`name`)
			left join `' . $srcdb . '`.`xepm_modules` as `src_modules` on
				(`src_modules`.`module_id` = `model_modules`.`module_id`)
			inner join `' . $dstdb . '`.`xepm_modules` as `dst_modules` on
				(`dst_modules`.`name` = `src_modules`.`name`)');
		query('insert into `' . $dstdb . '`.`xepm_settings` (`setting_id`, `setting_group_id`, `parent_id`, `configuration_type_id`, `setting_name_id`, `value`)
			select
				`settings`.`setting_id`,
				`values`.`setting_group_id`,
				`values`.`parent_id`,
				`values`.`configuration_type_id`,
				`values`.`setting_name_id`,
				`values`.`value`
			from (
				select
					`dst_setting_groups`.`setting_group_id`,
					`dst_settings`.`setting_id` as `parent_id`,
					`dst_configuration_types`.`configuration_type_id`,
					`dst_setting_names`.`setting_name_id`,
					`settings`.`value`
				from `' . $srcdb . '`.`xepm_settings` as `settings`
				left join `' . $srcdb . '`.`xepm_setting_groups` as `src_setting_groups` on
					(`src_setting_groups`.`setting_group_id` = `settings`.`setting_group_id`)
				left join `' . $dstdb . '`.`xepm_setting_groups` as `dst_setting_groups` on
					(`dst_setting_groups`.`name` = `src_setting_groups`.`name`)
				left join `' . $srcdb . '`.`xepm_configuration_types` as `src_configuration_types` on
					(`src_configuration_types`.`configuration_type_id` = `settings`.`configuration_type_id`)
				left join `' . $srcdb . '`.`xepm_models` as `src_models` on
					(`src_models`.`model_id` = `src_configuration_types`.`model_id`)
				left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
					(`dst_models`.`name` = `src_models`.`name`)
				left join `' . $srcdb . '`.`xepm_configuration_type_names` as `src_configuration_type_names` on
					(`src_configuration_type_names`.`configuration_type_name_id` = `src_configuration_types`.`configuration_type_name_id`)
				left join `' . $dstdb . '`.`xepm_configuration_type_names` as `dst_configuration_type_names` on
					(`dst_configuration_type_names`.`name` = `src_configuration_type_names`.`name`)
				left join `' . $dstdb . '`.`xepm_configuration_types` as `dst_configuration_types` on
					(`dst_configuration_types`.`model_id` = `dst_models`.`model_id` and
						`dst_configuration_types`.`configuration_type_name_id` = `dst_configuration_type_names`.`configuration_type_name_id`)
				left join `' . $srcdb . '`.`xepm_setting_names` as `src_setting_names` on
					(`src_setting_names`.`setting_name_id` = `settings`.`setting_name_id`)
				left join `' . $dstdb . '`.`xepm_setting_names` as `dst_setting_names` on
					(`dst_setting_names`.`name` = `src_setting_names`.`name`)
				left join `xepm_settings` as `parent_settings` on
					(`parent_settings`.`setting_id` = `settings`.`parent_id`)
				left join `' . $srcdb . '`.`xepm_configuration_types` as `src_parent_configuration_types` on
					(`src_parent_configuration_types`.`configuration_type_id` = `parent_settings`.`configuration_type_id`)
				left join `' . $srcdb . '`.`xepm_models` as `src_parent_models` on
					(`src_parent_models`.`model_id` = `src_parent_configuration_types`.`model_id`)
				left join `' . $dstdb . '`.`xepm_models` as `dst_parent_models` on
					(`dst_parent_models`.`name` = `src_parent_models`.`name`)
				left join `' . $srcdb . '`.`xepm_configuration_type_names` as `src_parent_configuration_type_names` on
					(`src_parent_configuration_type_names`.`configuration_type_name_id` = `src_parent_configuration_types`.`configuration_type_name_id`)
				left join `' . $dstdb . '`.`xepm_configuration_type_names` as `dst_parent_configuration_type_names` on
					(`dst_parent_configuration_type_names`.`name` = `src_parent_configuration_type_names`.`name`)
				left join `' . $dstdb . '`.`xepm_configuration_types` as `dst_parent_configuration_types` on
					(`dst_parent_configuration_types`.`model_id` = `dst_parent_models`.`model_id` and
						`dst_parent_configuration_types`.`configuration_type_name_id` = `dst_parent_configuration_type_names`.`configuration_type_name_id`)
				left join `' . $srcdb . '`.`xepm_setting_names` as `src_parent_setting_names` on
					(`src_parent_setting_names`.`setting_name_id` = `parent_settings`.`setting_name_id`)
				left join `' . $dstdb . '`.`xepm_setting_names` as `dst_parent_setting_names` on
					(`dst_parent_setting_names`.`name` = `src_parent_setting_names`.`name`)
				left join `' . $dstdb . '`.`xepm_settings` as `dst_settings` on
					(`dst_settings`.`configuration_type_id` = `dst_parent_configuration_types`.`configuration_type_id` and
						`dst_settings`.`setting_name_id` = `dst_parent_setting_names`.`setting_name_id`)) as `values`
			left join ' . $dstdb . '.xepm_settings as settings on
				(`settings`.`parent_id` <=> `values`.`parent_id` and
					`settings`.`configuration_type_id` <=> `values`.`configuration_type_id` and
						`settings`.`setting_name_id` <=> `values`.`setting_name_id`)
			on duplicate key update `value` = values(`value`)');
		query::commit();
	}

	private static function replace_import($srcdb, $dstdb) {
		query::begin_transaction();
		query('truncate table `' . $dstdb . '`.`xepm_brands`');
		query('insert into `' . $dstdb . '`.`xepm_brands` (`name`)
			select
				`brands`.`name`
			from `' . $srcdb . '`.`xepm_brands` as `brands`');
		query('truncate table `' . $dstdb . '`.`xepm_modules`');
		query('insert into `' . $dstdb . '`.`xepm_modules` (`brand_id`, `buttons`, `name`)
			select
				`dst_brands`.`brand_id`,
				`modules`.`buttons`,
				`modules`.`name`
			from `' . $srcdb . '`.`xepm_modules` as `modules`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `modules`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)');
		query('truncate table `' . $dstdb . '`.`xepm_line_names`');
		query('insert into `' . $dstdb . '`.`xepm_line_names` (`name`)
			select
				`line_names`.`name`
			from `' . $srcdb . '`.`xepm_line_names` as `line_names`');
		query('truncate table `' . $dstdb . '`.`xepm_setting_names`');
		query('insert into `' . $dstdb . '`.`xepm_setting_names` (`name`)
			select
				`setting_names`.`name`
			from `' . $srcdb . '`.`xepm_setting_names` as `setting_names`');
		query('truncate table `' . $dstdb . '`.`xepm_brand_ouis`');
		query('insert into `' . $dstdb . '`.`xepm_brand_ouis` (`brand_id`, `oui`)
			select
				`dst_brands`.`brand_id`,
				`ouis`.`oui`
			from `' . $srcdb . '`.`xepm_brand_ouis` as `ouis`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `ouis`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)');
		query('truncate table `' . $dstdb . '`.`xepm_brand_timezones`');
		query('insert into `' . $dstdb . '`.`xepm_brand_timezones` (`brand_id`, `offset`, `name`, `value`)
			select
				`dst_brands`.`brand_id`,
				`timezones`.`offset`,
				`timezones`.`name`,
				`timezones`.`value`
			from `' . $srcdb . '`.`xepm_brand_timezones` as `timezones`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `timezones`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)');
		query('truncate table `' . $dstdb . '`.`xepm_models`');
		query('insert into `' . $dstdb . '`.`xepm_models` (`brand_id`, `sip_lines`, `iax2_lines`, `dss_buttons`, `exp_modules`, `name`)
			select
				`dst_brands`.`brand_id`,
				`models`.`sip_lines`,
				`models`.`iax2_lines`,
				`models`.`dss_buttons`,
				`models`.`exp_modules`,
				`models`.`name`
			from `' . $srcdb . '`.`xepm_models` as `models`
			left join `' . $srcdb . '`.`xepm_brands` as `src_brands` on
				(`src_brands`.`brand_id` = `models`.`brand_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)');
		query('truncate table `' . $dstdb . '`.`xepm_configuration_type_names`');
		query('insert into `' . $dstdb . '`.`xepm_configuration_type_names` (`name`)
			select
				`configuration_type_names`.`name`
			from `' . $srcdb . '`.`xepm_configuration_type_names` as `configuration_type_names`');
		query('truncate table `' . $dstdb . '`.`xepm_configuration_types`');
		query('insert into `' . $dstdb . '`.`xepm_configuration_types` (`model_id`, `configuration_type_name_id`)
			select
				`dst_models`.`model_id`,
				`dst_configuration_type_names`.`configuration_type_name_id`
			from `' . $srcdb . '`.`xepm_configuration_types` as `configuration_types`
			left join `' . $srcdb . '`.`xepm_models` as `src_models` on
				(`src_models`.`model_id` = `configuration_types`.`model_id`)
			left join `' . $srcdb . '`.`xepm_configuration_type_names` as `src_configuration_type_names` on
				(`src_configuration_type_names`.`configuration_type_name_id` = `configuration_types`.`configuration_type_name_id`)
			left join `' . $dstdb . '`.`xepm_configuration_type_names` as `dst_configuration_type_names` on
				(`dst_configuration_type_names`.`name` = `src_configuration_type_names`.`name`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`name` = `src_models`.`name`)');
		query('truncate table `' . $dstdb .'`.`xepm_setting_groups`');
		query('insert into `' . $dstdb . '`.`xepm_setting_groups` (`name`, `group_type`)
			select
				`setting_groups`.`name`,
				`setting_groups`.`group_type`
			from `' . $srcdb . '`.`xepm_setting_groups` as `setting_groups`');
		query('truncate table `' . $dstdb . '`.`xepm_model_lines`');
		query('insert into `' . $dstdb . '`.`xepm_model_lines` (`model_id`, `line_name_id`, `value`)
			select
				`dst_models`.`model_id`,
				`dst_line_names`.`line_name_id`,
				`model_lines`.`value`
			from `' . $srcdb . '`.`xepm_model_lines` as `model_lines`
			left join `' . $srcdb . '`.`xepm_models` as `src_models` on
				(`src_models`.`model_id` = `model_lines`.`model_id`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`name` = `src_models`.`name`)
			left join `' . $srcdb . '`.`xepm_line_names` as `src_line_names` on
				(`src_line_names`.`line_name_id` = `model_lines`.`line_name_id`)
			left join `' . $dstdb . '`.`xepm_line_names` as `dst_line_names` on
				(`dst_line_names`.`name` = `src_line_names`.`name`)
			where `dst_models`.`model_id` is not null');
		query('truncate table `' . $dstdb . '`.`xepm_model_modules`');
		query('insert into `' . $dstdb . '`.`xepm_model_modules` (`model_id`, `module_id`)
			select
				`dst_models`.`model_id`,
				`dst_modules`.`module_id`
			from `' . $srcdb . '`.`xepm_model_modules` as `model_modules`
			left join `' . $srcdb . '`.`xepm_models` as `src_models` on
				(`src_models`.`model_id` = `model_modules`.`model_id`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`name` = `src_models`.`name`)
			left join `' . $srcdb . '`.`xepm_modules` as `src_modules` on
				(`src_modules`.`module_id` = `model_modules`.`module_id`)
			left join `' . $dstdb . '`.`xepm_modules` as `dst_modules` on
				(`dst_modules`.`name` = `src_modules`.`name`)
			where `dst_models`.`model_id` is not null');
		query('truncate table `' . $dstdb . '`.`xepm_settings`');
				query('insert ignore into `' . $dstdb . '`.`xepm_settings` (`setting_group_id`, `parent_id`, `configuration_type_id`, `setting_name_id`, `value`)
			select
				`dst_setting_groups`.`setting_group_id`,
				null,
				`dst_configuration_types`.`configuration_type_id`,
				`dst_setting_names`.`setting_name_id`,
				`src_settings`.`value`
			from `' . $srcdb .'`.`xepm_brands` as `src_brands`
			left join `' . $srcdb .'`.`xepm_models` as `src_models` on
				(`src_models`.`brand_id` = `src_brands`.`brand_id`)
			left join `' . $srcdb .'`.`xepm_configuration_types` as `src_configuration_types` on
				(`src_configuration_types`.`model_id` = `src_models`.`model_id`)
			left join `' . $srcdb .'`.`xepm_configuration_type_names` as `src_configuration_type_names` on
				(`src_configuration_type_names`.`configuration_type_name_id` = `src_configuration_types`.`configuration_type_name_id`)
			left join `' . $srcdb .'`.`xepm_settings` as `src_settings` on
				(`src_settings`.`configuration_type_id` = `src_configuration_types`.`configuration_type_id`)
			left join `' . $srcdb .'`.`xepm_setting_names` as `src_setting_names` on
				(`src_setting_names`.`setting_name_id` = `src_settings`.`setting_name_id`)
			left join `' . $srcdb .'`.`xepm_setting_groups` as `src_setting_groups` on
				(`src_setting_groups`.`setting_group_id` = `src_settings`.`setting_group_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`brand_id` = `dst_brands`.`brand_id` and
					`dst_models`.`name` = `src_models`.`name`)
			left join `' . $dstdb . '`.`xepm_configuration_type_names` as `dst_configuration_type_names` on
				(`dst_configuration_type_names`.`name` = `src_configuration_type_names`.`name`)
			left join `' . $dstdb . '`.`xepm_configuration_types` as `dst_configuration_types` on
				(`dst_configuration_types`.`model_id` = `dst_models`.`model_id` and
					`dst_configuration_types`.`configuration_type_name_id` = `dst_configuration_type_names`.`configuration_type_name_id`)
			left join `' . $dstdb . '`.`xepm_setting_names` as `dst_setting_names` on
				(`dst_setting_names`.`name` = `src_setting_names`.`name`)
			left join `' . $dstdb . '`.`xepm_setting_groups` as `dst_setting_groups` on
				(`dst_setting_groups`.`name` = `src_setting_groups`.`name`)
			where `src_settings`.`parent_id` is null and
				`dst_setting_groups`.`setting_group_id` is not null and
					`dst_configuration_types`.`configuration_type_id` is not null and
						`dst_setting_names`.`setting_name_id` is not null');

		query('insert ignore into `' . $dstdb . '`.`xepm_settings` (`setting_group_id`, `parent_id`, `configuration_type_id`, `setting_name_id`, `value`)
			select
				`dst_setting_groups`.`setting_group_id`,
				`dst_parent_settings`.`setting_id`,
				`dst_configuration_types`.`configuration_type_id`,
				`dst_setting_names`.`setting_name_id`,
				`src_settings`.`value`
			from `' . $srcdb .'`.`xepm_brands` as `src_brands`
			left join `' . $srcdb .'`.`xepm_models` as `src_models` on
				(`src_models`.`brand_id` = `src_brands`.`brand_id`)
			left join `' . $srcdb .'`.`xepm_configuration_types` as `src_configuration_types` on
				(`src_configuration_types`.`model_id` = `src_models`.`model_id`)
			left join `' . $srcdb .'`.`xepm_configuration_type_names` as `src_configuration_type_names` on
				(`src_configuration_type_names`.`configuration_type_name_id` = `src_configuration_types`.`configuration_type_name_id`)
			left join `' . $srcdb .'`.`xepm_settings` as `src_settings` on
				(`src_settings`.`configuration_type_id` = `src_configuration_types`.`configuration_type_id`)
			left join `' . $srcdb .'`.`xepm_setting_names` as `src_setting_names` on
				(`src_setting_names`.`setting_name_id` = `src_settings`.`setting_name_id`)
			left join `' . $srcdb .'`.`xepm_setting_groups` as `src_setting_groups` on
				(`src_setting_groups`.`setting_group_id` = `src_settings`.`setting_group_id`)
			left join `' . $dstdb . '`.`xepm_brands` as `dst_brands` on
				(`dst_brands`.`name` = `src_brands`.`name`)
			left join `' . $dstdb . '`.`xepm_models` as `dst_models` on
				(`dst_models`.`brand_id` = `dst_brands`.`brand_id` and
					`dst_models`.`name` = `src_models`.`name`)
			left join `' . $dstdb . '`.`xepm_configuration_type_names` as `dst_configuration_type_names` on
				(`dst_configuration_type_names`.`name` = `src_configuration_type_names`.`name`)
			left join `' . $dstdb . '`.`xepm_configuration_types` as `dst_configuration_types` on
				(`dst_configuration_types`.`model_id` = `dst_models`.`model_id` and
					`dst_configuration_types`.`configuration_type_name_id` = `dst_configuration_type_names`.`configuration_type_name_id`)
			left join `' . $dstdb . '`.`xepm_setting_names` as `dst_setting_names` on
				(`dst_setting_names`.`name` = `src_setting_names`.`name`)
			left join `' . $dstdb . '`.`xepm_setting_groups` as `dst_setting_groups` on
				(`dst_setting_groups`.`name` = `src_setting_groups`.`name`)
			left join `' . $srcdb .'`.`xepm_settings` as `src_parent_settings` on
				(`src_parent_settings`.`setting_id` = `src_settings`.`parent_id`)
			left join `' . $srcdb .'`.`xepm_setting_names` as `src_parent_setting_names` on
				(`src_parent_setting_names`.`setting_name_id` = `src_parent_settings`.`setting_name_id`)
			left join `' . $dstdb . '`.`xepm_setting_names` as `dst_parent_setting_names` on
				(`dst_parent_setting_names`.`name` = `src_parent_setting_names`.`name`)
			left join `' . $dstdb . '`.`xepm_settings` as `dst_parent_settings` on
				(`dst_parent_settings`.`configuration_type_id` = `dst_configuration_types`.`configuration_type_id` and
					`dst_parent_settings`.`setting_name_id` = `dst_parent_setting_names`.`setting_name_id`)
			where `src_settings`.`parent_id` is not null and
				`dst_setting_groups`.`setting_group_id` is not null and
					`dst_parent_settings`.`setting_id` is not null and
						`dst_configuration_types`.`configuration_type_id` is not null and
							`dst_setting_names`.`setting_name_id` is not null');
		query::commit();
	}

	public static function render() {
		$attempted_import = false;
		$import_succeeded = false;
		if (is_postback()) {
			if (is_array($file = util::array_get('file', $_FILES))) {
				$mode = strtolower(trim(util::array_get('mode', $_POST)));
				$error = intval(trim(util::array_get('error', $file)));
				$filename = trim(util::array_get('tmp_name', $file));
				if ($error == 0 && is_uploaded_file($filename) &&
					in_array($mode, array('append', 'update', 'replace')) &&
					($ampconf = util::ampconf()) !== null) {
					@set_time_limit(0); // Import may take a long time
					$attempted_import = true;
					$tmpdb = 'xepmtmp';
					$command = sprintf(
						'echo "drop database if exists %s; create database %s; grant all on %s.* to %s@localhost" | mysql -u root && gunzip -c %s | mysql -u %s -p%s %s',
						$tmpdb,
						$tmpdb,
						$tmpdb,
						$ampconf->AMPDBUSER,
						$filename,
						$ampconf->AMPDBUSER,
						$ampconf->AMPDBPASS,
						$tmpdb);
					if (system($command, $errno) !== false && $errno == 0) {
						self::correct_collations($tmpdb, $ampconf->AMPDBNAME);
						switch ($mode) {
							case 'append': { self::append_import($tmpdb, $ampconf->AMPDBNAME); break; }
							case 'update': { self::update_import($tmpdb, $ampconf->AMPDBNAME); break; }
							case 'replace': { self::replace_import($tmpdb, $ampconf->AMPDBNAME); break; }
						}
						$command = sprintf('echo "drop database if exists %s" | mysql -u root', $tmpdb);
						if (system($command, $errno) !== false && $errno == 0) {
							$import_succeeded = true;
						}
					}
				}
			} else {
				self::export();
			}
		}
		$tpl = new template('util_migration.tpl');
		$tpl->attempted_import = $attempted_import;
		$tpl->import_succeeded = $import_succeeded;
		$tpl->render();
	}

	function correct_collations($srcdb, $dstdb) {
		foreach (array($srcdb, $dstdb) as $db) {
			$requires_conversion = true;
			foreach(query('show full columns from `' . $db . '`.xepm_setting_names where Collation = "utf8_bin"') as $row) {
				$requires_conversion = false;
			}
			if($requires_conversion) {
				query('alter table `' . $db . '`.xepm_setting_names convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_brand_timezones convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_brands convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_configuration_type_names convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_device_extensions convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_hosts convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_line_names convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_models convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_modules convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_setting_groups convert to character set utf8 collate utf8_bin');
				query('alter table `' . $db . '`.xepm_templates convert to character set utf8 collate utf8_bin');
			}
		}
	}
}

?>
