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
	die('<b>Error:</b> Cannot install - please run arcadeinstall/index.php instead');

$forced = false;

// Step 1: Rename E-Arcade tables if needed
doRenameTables();

// Step 2: Create and/or Upgrade tables
doTables($tables, $columnRename, true);

// Step 3: Add Settings to database
doSettings($addSettings);

// Step 4: Update "Admin Features"
updateAdminFeatures('arcade', !empty($modSettings['arcadeEnabled']));

// Step 5: Add Permissions to database
doPermission($permissions);

// Step 6: Insert SMF Arcade Package Server to list
$request = $smcFunc['db_query']('', '
	SELECT COUNT(*)
	FROM {db_prefix}package_servers
	WHERE name = {string:name}',
	array(
		'name' => 'SMF Arcade Package Server',
	)
);

list ($count) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);

if ($count == 0 || $forced)
{
	$smcFunc['db_insert']('insert',
		'{db_prefix}package_servers',
		array(
			'name' => 'string',
			'url' => 'string',
		),
		array(
			'SMF Arcade Package Server',
			'http://download.smfarcade.info',
		),
		array()
	);
}

// Step 7: Insert Default Category
$request = $smcFunc['db_query']('', '
	SELECT COUNT(*)
	FROM {db_prefix}arcade_categories');

list ($count) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);

if ($count == 0 || $forced)
{
	$smcFunc['db_insert']('insert',
		'{db_prefix}arcade_categories',
		array('cat_name' => 'string', 'member_groups' => 'string', 'cat_order' => 'int',),
		array('Default', '-2,-1,0,1,2', 1),
		array('id_cat')
	);
}

// Step 8: Update Arcade Version in Database
updateSettings(array('arcadeVersion' => $arcade_version));

// Step 9: Hooks
add_integration_function('integrate_pre_include', '$sourcedir/ArcadeHooks.php');
add_integration_function('integrate_actions', 'Arcade_actions');
add_integration_function('integrate_core_features', 'Arcade_core_features');
add_integration_function('integrate_load_permissions', 'Arcade_load_permissions');
add_integration_function('integrate_profile_areas', 'Arcade_profile_areas');
add_integration_function('integrate_menu_buttons', 'Arcade_menu_buttons');
add_integration_function('integrate_admin_areas', 'Arcade_admin_areas');

function doRenameTables()
{
	global $smcFunc, $db_prefix, $db_type;

	if ($db_type != 'mysql')
		return;

	$tables = $smcFunc['db_list_tables']();

	// Detect eeks mod from unique table name
	if (in_array($db_prefix . 'arcade_shouts', $tables))
	{
		$tables = array(
			'arcade_games' => 'earcade_games',
			'arcade_personalbest' => 'earcade_personalbest',
			'arcade_scores' => 'earcade_scores',
			'arcade_categories' => 'earcade_categories',
			'arcade_favorite' => 'earcade_favorite',
			'arcade_rates' => 'earcade_rates',
			'arcade_settings' => 'earcade_settings',
			'arcade_v3temp' => 'earcade_v3temp',
			'arcade_shouts' => 'earcade_shouts',
			'arcade_tournament_rounds' => 'earcade_tournament_rounds',
			'arcade_tournament_players' => 'earcade_tournament_players',
			'arcade_tournament_scores' => 'earcade_tournament_scores',
			'arcade_tournament' => 'earcade_tournament',
		);

		foreach ($tables as $old => $new)
		{
			// Drop old copies of earcade tables if exists
			$smcFunc['db_query']('', '
				DROP TABLE IF EXISTS {db_prefix}{raw:new}',
				array(
					'old' => $old,
					'new' => $new,
				)
			);
			$smcFunc['db_query']('', '
				RENAME TABLE {db_prefix}{raw:old} TO {db_prefix}{raw:new}',
				array(
					'old' => $old,
					'new' => $new,
				)
			);
		}
	}
}

?>