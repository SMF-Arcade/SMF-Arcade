<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.5
 * @license http://download.smfarcade.info/license.php New-BSD
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/*
	void ArcadeAdmin()
		- ???

	void/array ArcadeAdminSettings()
		- ???

	void/array ArcadeAdminPemission()
		- ???

	void ArcadeAdminCategory()
		- ???

	void ArcadeCategoryList()
		- ???

	void ArcadeCategoryEdit()
		- ???

	void ArcadeCategorySave()
		- ???
*/

function ArcadeAdmin()
{
	global $sourcedir, $scripturl, $txt, $modSettings, $context, $settings, $arcade_server;

	require_once($sourcedir . '/Arcade.php');
	require_once($sourcedir . '/Subs-ArcadeAdmin.php');
	require_once($sourcedir . '/ManageServer.php');

	isAllowedTo('arcade_admin');
	loadArcade('admin', 'arcadesettings');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['arcade_admin_settings'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['arcade_general_desc'];

	$subActions = array(
		'main' => array('ArcadeAdminMain'),
		'settings' => array('ArcadeAdminSettings'),
		'permission' => array('ArcadeAdminPemission'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	if (isset($subActions[$_REQUEST['sa']][1]))
		isAllowedTo($subActions[$_REQUEST['sa']][1]);

	$subActions[$_REQUEST['sa']][0]();
}

function ArcadeAdminMain()
{
	global $scripturl, $txt, $modSettings, $context, $settings;

	$context['sub_template'] = 'arcade_admin_main';
}

function ArcadeAdminSettings($return_config = false)
{
	global $scripturl, $txt, $modSettings, $context, $settings, $sourcedir;
	
	if ($return_config)
		require_once($sourcedir . '/Subs-Arcade.php');
	else
		$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['arcade_settings_desc'];

	$config_vars = array(
			array('check', 'arcadeArenaEnabled'),
			array('check', 'arcadeEnableFavorites'),
			array('check', 'arcadeEnableRatings'),
		'',
			array('text', 'gamesUrl'),
			array('text', 'gamesDirectory'),
			array('check', 'arcadeGamecacheUpdate'),
		'',
			array('int', 'arcadeCommentLen', 'subtext' => $txt['arcadeCommentLen_subtext']),
		'',
			array('int', 'gamesPerPage'),
			array('int', 'matchesPerPage'),
			array('int', 'scoresPerPage'),
		'',
			array('select', 'arcadeCheckLevel',
				array($txt['arcade_check_level0'], $txt['arcade_check_level1'], $txt['arcade_check_level2'])
			),
		'',
			array('int', 'arcadeMaxScores'),
	);
	
	foreach (submitSystemInfo('*') as $id => $system)
	{
		if (!isset($system['get_settings']))
			continue;
		
		// Load file
		require_once($sourcedir . '/' . $system['file']);
		
		// Add settings to page
		$config_vars[] = $system['name'];
		$config_vars = array_merge($config_vars, $system['get_settings']());
	}

	if ($return_config)
		return $config_vars;

	if (isset($_GET['save']))
	{
		checkSession('post');

		$maxScores = !empty($modSettings['arcadeMaxScores']) ? $modSettings['arcadeMaxScores'] : 0;

		saveDBSettings($config_vars);

		writeLog();

		if (!empty($modSettings['arcadeMaxScores']) && (empty($maxScores) || $maxScores > $modSettings['arcadeMaxScores']))
			redirectexit('action=admin;area=arcademaintenance;sa=fixScores');

		redirectexit('action=admin;area=arcade;sa=settings');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=arcade;sa=settings;save';
	$context['settings_title'] = $txt['arcade_admin_settings'];
	$context['sub_template'] = 'show_settings';

	prepareDBSettingContext($config_vars);
}

function ArcadeAdminPemission($return_config = false)
{
	global $scripturl, $txt, $modSettings, $context, $settings;

	$config_vars = array(
			array('select', 'arcadePermissionMode',
				array($txt['arcade_permission_mode_none'], $txt['arcade_permission_mode_category'],
					$txt['arcade_permission_mode_game'], $txt['arcade_permission_mode_and_both'],
					$txt['arcade_permission_mode_or_both'])
			),
		'',
			array('check', 'arcadePostPermission'),
			array('int', 'arcadePostsPlay'),
			array('int', 'arcadePostsLastDay'),
			array('int', 'arcadePostsPlayAverage'),
		'',
			array('permissions', 'arcade_view', 0, $txt['perm_arcade_view']),
			array('permissions', 'arcade_play', 0, $txt['perm_arcade_play']),
			array('permissions', 'arcade_submit', 0, $txt['perm_arcade_submit']),
	);

	if ($return_config)
		return $config_vars;

	if (isset($_GET['save']))
	{
		checkSession('post');

		saveDBSettings($config_vars);

		writeLog();

		redirectexit('action=admin;area=arcade;sa=permission');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=arcade;sa=permission;save';
	$context['settings_title'] = $txt['arcade_general_permissions'];
	$context['sub_template'] = 'show_settings';

	prepareDBSettingContext($config_vars);
}

function ArcadeAdminCategory()
{
	global $context, $sourcedir, $txt;

	require_once($sourcedir . '/Arcade.php');
	require_once($sourcedir . '/Subs-ArcadeAdmin.php');

	loadArcade('admin', 'managecategory');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['arcade_manage_category'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['arcade_manage_category_desc'];

	$subActions = array(
		'list' => array('ArcadeCategoryList', 'arcade_admin'),
		'edit' => array('ArcadeCategoryEdit', 'arcade_admin'),
		'new' => array('ArcadeCategoryEdit', 'arcade_admin'),
		'save' => array('ArcadeCategorySave', 'arcade_admin'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';
	isAllowedTo($subActions[$_REQUEST['sa']][1]);

	$subActions[$_REQUEST['sa']][0]();
}

function ArcadeCategoryList()
{
	global $db_prefix, $modSettings, $context, $sourcedir, $scripturl, $smcFunc;

	if (isset($_REQUEST['save']))
	{
		checkSession('post');

		asort($_REQUEST['category_order'], SORT_NUMERIC);

		$i = 1;

		if (!empty($_REQUEST['category']))
		{
			$ids = array();
			foreach ($_REQUEST['category'] as $id)
			{
				$ids[] = $id;
				unset($_REQUEST['category_order'][$id]);
			}

			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}arcade_categories
				WHERE id_cat IN({array_int:category})',
				array(
					'category' => $ids,
				)
			);
		}

		foreach ($_REQUEST['category_order'] as $id => $dummy)
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}arcade_categories
				SET cat_order = {int:order}
				WHERE id_cat = {int:category}',
				array(
					'order' => $i++,
					'category' => $id,
				)
			);
	}

	$request = $smcFunc['db_query']('', '
		SELECT id_cat, cat_name, num_games, cat_order
		FROM {db_prefix}arcade_categories
		ORDER BY cat_order',
		array()
	);

	$context['arcade_category'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['arcade_category'][] = array(
			'id' => $row['id_cat'],
			'name' => $row['cat_name'],
			'href' => $scripturl . '?action=admin;area=arcadecategory;sa=edit;category=' . $row['id_cat'],
			'games' => $row['num_games'],
			'order' => $row['cat_order'],
		);
	}
	$smcFunc['db_free_result']($request);

	// Template
	$context['sub_template'] = 'arcade_admin_category_list';
}

function ArcadeCategoryEdit()
{
	global $db_prefix, $modSettings, $context, $sourcedir, $smcFunc;

	$new = false;

	if (isset($_REQUEST['category']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_cat, cat_name, num_games, cat_order, member_groups
			FROM {db_prefix}arcade_categories
			WHERE id_Cat = {int:category}',
			array(
				'category' => $_REQUEST['category'],
			)
		);

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		$context['category'] = array(
			'id' => $row['id_cat'],
			'name' => $row['cat_name'],
			'member_groups' => explode(',', $row['member_groups']),
		);
	}
	else
	{
		$new = true;

		$context['category'] = array(
			'id' => 'new',
			'name' => '',
			'member_groups' => array(),
		);
	}

	$context['groups'] = arcadeGetGroups($new ? 'all' : $context['category']['member_groups']);

	// Template
	$context['sub_template'] = 'arcade_admin_category_edit';
}

function ArcadeCategorySave()
{
	global $db_prefix, $modSettings, $context, $sourcedir, $smcFunc;

	checkSession('post');

	$memberGroups = array();

	if (!empty($_REQUEST['groups']))
		foreach ($_REQUEST['groups'] as $k => $id)
			$memberGroups[] = (int) $id;

	if ($_REQUEST['category'] == 'new')
	{
		$request = $smcFunc['db_query']('', '
			SELECT MAX(cat_order)
			FROM {db_prefix}arcade_categories'
		);

		list ($max) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		$smcFunc['db_insert']('insert',
			'{db_prefix}arcade_categories',
			array('cat_name' => 'string', 'member_groups' => 'string', 'cat_order' => 'int',),
			array($_REQUEST['category_name'], implode(',', $memberGroups), ++$max),
			array('id_cat')
		);
	}
	else
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}arcade_categories
			SET
				cat_name = {string:name},
				member_groups = {string:groups}
			WHERE id_cat = {int:category}',
			array(
				'name' => $_REQUEST['category_name'],
				'groups' => implode(',', $memberGroups),
				'category' => $_REQUEST['category']
			)
		);
	}

	redirectexit('action=admin;area=arcadecategory');
}

?>