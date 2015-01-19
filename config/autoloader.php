<?php
/* 
 * Copyright (C) 2014 fhcomplete.org
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
 *
 * Authors: Robert Hofer <robert.hofer@technikum-wien.at>
 */

/**
 * Autoloader für FHC
 *
 * $class_dirs enthält alle Ordner die Klassen enthalten. Diese müssen einen
 * bestimmten Namen haben: <Klasse>.class.php
 * Dann werden sie automatisch geladen.
 *
 * Außerdem wird die Datei functions.inc.php aus dem Standard "include"-Verz.
 * geladen.
 *
 * @param string $class
 */

// Zur Sicherheit wird der Autoloader nur einmal registriert.
if(!function_exists('fhcomplete_autoloader'))
{
	include dirname(__FILE__) . '/../include/functions.inc.php';

	function fhcomplete_autoloader($class) {

		$base_dir = dirname(__FILE__) . '/../';

		$class_dirs = array(
			$base_dir . 'include/',
			$base_dir . 'addons/reports/include/',
			$base_dir . 'addons/studienplatzverwaltung/include/',
			$base_dir . 'addons/studienplatzverwaltung/vilesci/',
			$base_dir . 'addons/studienplatzverwaltung/soap/',
			$base_dir . 'addons/ldap/vilesci/',
			$base_dir . 'addons/kontoimport/vilesci/',
			$base_dir . 'addons/kompetenzen/',
			$base_dir . 'addons/datenimport/vilesci/',
			$base_dir . 'include/pChart/class/',
			$base_dir . 'include/safehtml/',
			$base_dir . 'soap/',
			$base_dir . 'webdav/',
			$base_dir . 'cms/menu/',
		);

		foreach($class_dirs as $dir)
		{
			$file = $dir . $class . '.class.php';

			if(is_file($file))
			{
				include($file);
				return;
			}
		}
	}

	spl_autoload_register('fhcomplete_autoloader');
}
