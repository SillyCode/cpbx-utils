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

function goto_brand(element) { window.location = "?brand_id=" + $(element).val(); }
function goto_model(element) { window.location = "?model_id=" + $(element).val(); }
function goto_module(element) { window.location = "?module_id=" + $(element).val(); }
function goto_group(element) { window.location = "?group_id=" + $(element).val(); }
function goto_configuration(element) { window.location = "?configuration_type_id=" + $(element).val(); }
function delete_row(element) { $(element).closest("tr").remove(); }
function create_row(element) {
	var original = $(element).closest("tr");
	var copy = original.clone();
	copy.find("input[type!='button'],select").each(function() {
		this.name = this.id;
		this.removeAttribute("id");
	});
	copy.find("select").each(function() {
		this.selectedIndex = document.getElementById(this.name).selectedIndex;
	});
	copy.find("input[type='button']").each(function() {
		this.value = "Delete";
		this.onclick = function(event) { delete_row(event.target); };
	});
	copy.appendTo($(element).closest("table").find("tbody"));
	original.find("input[type!='button']").each(function() {
		this.value = null;
	});
}

function create_line_row(element) {
	var original = $(element).closest("tr");
	var copy = original.clone();
	var orig_line_index = original[0].children[1].children[0].selectedIndex;
	var orig_value = original[0].children[2].children[0].value;

	if(orig_line_index < (original[0].children[1].children[0].length - 1)) {
		original[0].children[1].children[0].selectedIndex = orig_line_index + 1;
	}
	if(isNaN(orig_value) == false) {
		original[0].children[2].children[0].value = parseInt(orig_value) + 1;
	}

	copy.find("input[type!='button'],select").each(function() {
		this.name = this.id;
		this.removeAttribute("id");
	});
	copy.find("select").each(function() {
		this.selectedIndex = document.getElementById(this.name).selectedIndex;
	});
	copy.find("input[type='button']").each(function() {
		this.value = "Delete";
		this.onclick = function(event) { delete_row(event.target); };
	});

	copy[0].children[1].children[0].selectedIndex = orig_line_index;
	copy[0].children[2].children[0].value = orig_value;

	copy.appendTo($(element).closest("table").find("tbody"));
}

function create_rows() {
	for(var i = 0;i < $("#number").val(); i++) {
		$("table[class='buttons'] tfoot").find("input[type='button']:last").click();
	}
}

function populate_parent_dropdown(select) {
	if(!select.is_populated) {
		var selected_index = select.options[select.selectedIndex].value; // Keep the selected parent
		while(select.options.length > 0) {
			select.options.remove(0);
		}
		var original_options = $("table[class='settings'] tfoot select:first")[0];
		for(var i = 0; i < original_options.options.length; i++) {
			select.options.add(original_options.options[i].cloneNode(true));
		}
		$(select).val(selected_index); // Restore the selected parent
		select.is_populated = true;
	}
}
