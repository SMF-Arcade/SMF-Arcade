<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6 Alpha
 * @license http://download.smfarcade.info/license.php New-BSD
 */

global $txt, $smcFunc, $db_prefix, $modSettings;
global $project_version, $addSettings, $permissions, $tables, $sourcedir;

if (!defined('SMF'))
	require '../SSI.php';
	
remove_integration_function('integrate_pre_include', '$sourcedir/ArcadeHooks.php');
remove_integration_function('integrate_actions', 'Arcade_actions');
remove_integration_function('integrate_core_features', 'Arcade_core_features');
remove_integration_function('integrate_load_permissions', 'Arcade_load_permissions');
remove_integration_function('integrate_profile_areas', 'Arcade_profile_areas');
remove_integration_function('integrate_menu_buttons', 'Arcade_menu_buttons');
remove_integration_function('integrate_admin_areas', 'Arcade_admin_areas');

?>