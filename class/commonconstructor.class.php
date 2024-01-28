<?php
/* Copyright (C) 2019-2023  Frédéric France <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/commonconstructor.class.php
 * \ingroup dolidocusign
 * \brief   This file is a class file for module class
 *
 */

/**
 * Class CommonConstructor
 */
class CommonConstructor extends CommonObject
{
	/**
	 * __construct
	 *
	 * @param  DoliDB $db DoliDB
	 * @return void
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$pattern = "#(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#";
		$rfe = new ReflectionClass($this);
		$properties = $rfe->getProperties();
		$parameters = [];
		/**
		 *  'type' if the field format.
		 *  'label' the translation key.
		 *  'enabled' is a condition when the field must be managed.
		 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
		 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
		 *  'index' if we want an index in database.
		 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
		 *  'position' is the sort order of field.
		 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
		 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
		 *  'help' is a string visible as a tooltip on field
		 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
		 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
		 *  'showoncombobox' if field must be shown into the label of combobox
		 */
		foreach ($properties as $key => $property) {
			$comment_string = $property->getDocComment();
			preg_match_all($pattern, $comment_string, $matches, PREG_PATTERN_ORDER);
			if (is_countable($matches)) {
				$found = false;
				$field = '';
				foreach ($matches[1] as $match) {
					$parampattern = "#(@dolibarr_[a-zA-Z]*)\s*(.*)#";
					preg_match_all($parampattern, $match, $param, PREG_PATTERN_ORDER);
					// take care we dont know in advance the order of the parameters
					if (!empty($param[1][0]) && !empty($param[2][0])) {
						switch ($param[1][0]) {
							case '@dolibarr_field':
								$found = true;
								$field = $param[2][0];
								$parameters[$field] = [];
								break;
							case '@dolibarr_label':
								$label = $param[2][0];
								break;
							case '@dolibarr_type':
								$type = $param[2][0];
								break;
							case '@dolibarr_enabled':
								$enabled = $param[2][0];
								break;
							case '@dolibarr_visible':
								$visible = $param[2][0];
								break;
							case '@dolibarr_position':
								$position = $param[2][0];
								break;
							case '@dolibarr_notnull':
								$notnull = $param[2][0];
								break;
							case '@dolibarr_searchall':
								$searchall = $param[2][0];
								break;
							case '@dolibarr_index':
								$index = $param[2][0];
								break;
							case '@dolibarr_comment':
								$comment = $param[2][0];
								break;
							case '@dolibarr_default':
								$default = $param[2][0];
								break;
							case '@dolibarr_showoncombobox':
								$showoncombobox = $param[2][0];
								break;
							case '@dolibarr_css':
								$css = $param[2][0];
								break;
							case '@dolibarr_param':
								$serialisedparam = unserialize($param[2][0]);
								break;
							case '@dolibarr_arrayofkeyval':
								$arrayofkeyval = unserialize($param[2][0]);
								break;
							case '@dolibarr_mapping':
								$mapping = explode(':', $param[2][0]);
								break;
						}
					}
					// add parameters founds
					if ($found && !empty($label)) {
						$parameters[$field]['label'] = $label;
						unset($label);
					}
					if ($found && !empty($type)) {
						$parameters[$field]['type'] = $type;
						unset($type);
					}
					if ($found && !empty($enabled)) {
						$parameters[$field]['enabled'] = dol_eval($enabled, 1);
						unset($enabled);
					}
					if ($found && !empty($visible)) {
						$parameters[$field]['visible'] = dol_eval($visible, 1);
						unset($visible);
					}
					if ($found && !empty($position)) {
						$parameters[$field]['position'] = dol_eval($position, 1);
						unset($position);
					}
					if ($found && !empty($notnull)) {
						$parameters[$field]['notnull'] = dol_eval($notnull, 1);
						unset($notnull);
					}
					if ($found && !empty($searchall)) {
						$parameters[$field]['searchall'] = dol_eval($searchall, 1);
						unset($searchall);
					}
					if ($found && !empty($index)) {
						$parameters[$field]['index'] = dol_eval($index, 1);
						unset($index);
					}
					if ($found && !empty($comment)) {
						$parameters[$field]['comment'] = $comment;
						unset($comment);
					}
					if ($found && !empty($default)) {
						$parameters[$field]['default'] = $default;
						unset($default);
					}
					if ($found && !empty($showoncombobox)) {
						$parameters[$field]['showoncombobox'] = $showoncombobox;
						unset($showoncombobox);
					}
					if ($found && !empty($css)) {
						$parameters[$field]['css'] = $css;
						unset($css);
					}
					if ($found && !empty($serialisedparam)) {
						$parameters[$field]['param'] = $serialisedparam;
						unset($serialisedparam);
					}
					if ($found && !empty($arrayofkeyval)) {
						$parameters[$field]['arrayofkeyval'] = $arrayofkeyval;
						unset($arrayofkeyval);
					}
					if ($found && !empty($mapping)) {
						$parameters[$field]['mapping'] = [
							'type' => $mapping[0],
							'class' => $mapping[1],
							'table' => $mapping[2],
							'alias' => $mapping[3],
							'index' => $mapping[4],
							'filter' => $mapping[5] ?? '',
						];
						unset($mapping);
					}
				}
			}
		}
		$this->fields = array_merge($parameters, $this->fields);
	}
}
