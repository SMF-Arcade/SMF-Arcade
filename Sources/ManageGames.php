<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6 Alpha
 * @license http://download.smfarcade.info/license.php New-BSD
 */

/*
	void ManageGames()
		- ???

	void ManageGamesMain()
		- ???

	void ManageGamesQuickEdit()
		- ???

	void EditGame()
		- ???

	void EditGame2()
		- ???
*/

function ManageGames()
{
	global $scripturl, $txt, $context, $sourcedir, $smcFunc, $modSettings;

	require_once($sourcedir . '/Arcade.php');
	require_once($sourcedir . '/Subs-ArcadeAdmin.php');

	// Templates
	loadTemplate('ManageGames');
	SMFArcade::loadArcade('admin', 'manage_games');

	loadClassFile('Class-Package.php');

	// Need to update files in database?
	if (!empty($modSettings['arcadeGamecacheUpdate']) && (empty($modSettings['arcadeDBUpdate']) || $modSettings['arcadeDBUpdate'] < max(filemtime($modSettings['gamesDirectory']), filemtime(__FILE__))))
		updateGameCache();
		
	if (isset($_REQUEST['uninstall_submit']) && !isset($_REQUEST['sa']))
		$_REQUEST['sa'] = 'uninstall';

	if (isset($_REQUEST['done']) && !empty($_SESSION['qaction']))
	{
		$context['show_done'] = true;

		if ($_SESSION['qaction'] == 'install')
		{
			$context['qaction_title'] = $txt['arcade_install_complete'];
			$context['qaction_text'] = $txt['arcade_install_following_games'];
		}
		elseif ($_SESSION['qaction'] == 'uninstall')
		{
			$context['qaction_title'] = $txt['arcade_uninstall_complete'];
			$context['qaction_text'] = $txt['arcade_uninstall_following_games'];
		}

		if (isset($_SESSION['qaction_data']) && is_array($_SESSION['qaction_data']))
			$context['qaction_data'] = $_SESSION['qaction_data'];

		unset($_SESSION['qaction_data']);
		unset($_SESSION['qaction']);
	}

	$subActions = array(
		'list' => array('ManageGamesList', 'arcade_admin'),
		'uninstall' => array('ManageGamesUninstall', 'arcade_admin'),
		'uninstall2' => array('ManageGamesUninstall2', 'arcade_admin'),
		'install' => array('ManageGamesInstall', 'arcade_admin'),
		'install2' => array('ManageGamesInstall2', 'arcade_admin'),
		'upload' => array('ManageGamesUpload', 'arcade_admin'),
		'upload2' => array('ManageGamesUpload2', 'arcade_admin'),
		'quickedit' => array('ManageGamesQuickEdit', 'arcade_admin'),
		'edit' => array('EditGame', 'arcade_admin'),
		'edit2' => array('EditGame2', 'arcade_admin'),
		'export' => array('ExportGameinfo', 'arcade_admin'),
	);

	// What user wants to do?
	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	// Do we have reason to allow him/her to do it?
	isAllowedTo($subActions[$_REQUEST['sa']][1]);

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['arcade_manage_games'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['arcade_manage_games_desc'];

	$subActions[$_REQUEST['sa']][0]();
}

function ManageGamesList()
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $sourcedir, $smcFunc;

	if (!isset($context['arcade_category']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_cat, cat_name
			FROM {db_prefix}arcade_categories'
		);

		$context['arcade_category'] = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['arcade_category'][$row['id_cat']] = array(
				'id' => $row['id_cat'],
				'name' => $row['cat_name']
			);
		$smcFunc['db_free_result']($request);
	}
	
	$category_data = '';
	
	foreach ($context['arcade_category'] as $id => $cat)
		$category_data .= '
	<option value="' . $id . '">' . $cat['name'] . '</option>';
	
	if (isset($_REQUEST['category_submit']))
	{
		foreach ($_REQUEST['game'] as $id_game)
			updateGame($id_game, array('category' => (int) $_REQUEST['category']), true);
			
		redirectexit('action=admin;area=managegames');
	}
	
	$filter = 'all';
	
	if (isset($_REQUEST['filter']) && in_array($_REQUEST['filter'], array('enabled', 'disabled')))
		$filter = $_REQUEST['filter'];
		
	$listOptions = array(
		'id' => 'games_list',
		'title' => '',
		'items_per_page' => $modSettings['gamesPerPage'],
		'base_href' => $scripturl . '?action=admin;area=managegames' . ($filter !== 'all' ? ';filter=' . $filter : ''),
		'default_sort_col' => 'name',
		'no_items_label' => sprintf($filter == 'all' ? $txt['arcade_no_games_installed'] : $txt['arcade_no_games_filter'], $scripturl . '?action=admin;area=managegames;sa=install'),
		'use_tabs' => true,
		'list_menu' => array(
			'style' => 'buttons',
			'position' => 'right',
			'columns' => 3,
			'show_on' => 'both',
			'links' => array(
				'show_all' => array(
					'href' => $scripturl . '?action=admin;area=managegames',
					'label' => $txt['manage_games_filter_all'],
					'is_selected' => $filter == 'all',
				),
				'enabled' => array(
					'href' => $scripturl . '?action=admin;area=managegames;filter=enabled',
					'label' => $txt['manage_games_filter_enabled'],
					'is_selected' => $filter == 'enabled',
				),
				'disabled' => array(
					'href' => $scripturl . '?action=admin;area=managegames;filter=disabled',
					'label' => $txt['manage_games_filter_disabled'],
					'is_selected' => $filter == 'disabled',
				),
			),
		),
		'get_items' => array(
			'function' => 'list_getGamesInstalled',
			'params' => array($filter),
		),
		'get_count' => array(
			'function' => 'list_getNumGamesInstalled',
			'params' => array($filter),
		),
		'columns' => array(
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form, \'game[]\');" />',
					'style' => 'width: 10px;'
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="game[]" value="%d" class="check" />',
						'params' => array('id' => false),
					),
					'style' => 'text-align: center;',
				),
			),
			'name' => array(
				'header' => array(
					'value' => $txt['arcade_game_name'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'function' => create_function('$rowData', '
						$link = \'<a href="\' . $rowData[\'href\'] . \'">\' . $rowData[\'name\'] . \'</a>\';

						if (!empty($rowData[\'error\']))
							$link .= \'<div class="alert smalltext">\' . $rowData[\'error\'] . \'</div>\';

						return $link;
					'),
				),
				'sort' => array(
					'default' => 'g.game_name',
					'reverse' => 'g.game_name DESC',
				),
			),
			'category' => array(
				'header' => array(
					'value' => $txt['arcade_category'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'function' => create_function('$rowData', '
						$link = $rowData[\'category\'][\'name\'];

						return $link;
					'),
				),
				'sort' => array(
					'default' => 'cat.cat_name',
					'reverse' => 'cat.cat_name DESC',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=managegames' . ($filter !== 'all' ? ';filter=' . $filter : ''),
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<select name="category">' . $category_data . '</select> <input class="button_submit" type="submit" name="category_submit" value="' . $txt['quickmod_change_category'] . '" />
				<input class="button_submit" type="submit" name="uninstall_submit" value="' . $txt['quickmod_uninstall_selected'] . '" />',
				'class' => 'titlebg',
				'style' => 'text-align: right;',
			),
		),
	);

	// Create the list.
	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);
	
	$context['sub_template'] = 'manage_games_list';
}

function ManageGamesInstall()
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $sourcedir, $smcFunc;

	$context['sub_template'] = 'manage_games_list';

	$listOptions = array(
		'id' => 'games_list',
		'title' => '',
		'items_per_page' => $modSettings['gamesPerPage'],
		'base_href' => $scripturl . '?action=admin;area=managegames;sa=install',
		'default_sort_col' => 'name',
		'no_items_label' => sprintf($txt['arcade_no_games_available_for_install'], $scripturl . '?action=admin;area=managegames;sa=upload'),
		'get_items' => array(
			'function' => 'list_getGamesInstall',
			'params' => array(
			),
		),
		'get_count' => array(
			'function' => 'list_getNumGamesInstall',
			'params' => array(
			),
		),
		'columns' => array(
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form, \'file[]\');" />',
					'style' => 'width: 10px;'
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="file[]" value="%d" class="check" />',
						'params' => array('id_file' => false),
					),
					'style' => 'text-align: center;',
				),
			),
			'name' => array(
				'header' => array(
					'value' => $txt['arcade_game_name'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'function' => create_function('$rowData', '
						$link = $rowData[\'name\'];

						return $link;
					'),
				),
				'sort' => array(
					'default' => 'f.game_name',
					'reverse' => 'f.game_name DESC',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=managegames;sa=install2',
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input class="button_submit" type="submit" name="install_submit" value="' . $txt['quickmod_install_selected'] . '" />',
				'class' => 'titlebg',
				'style' => 'text-align: right;',
			),
		),
	);

	// Create the list.
	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);
}

function ManageGamesInstall2()
{
	global $smcFunc;

	checkSession('request');

	if (!isset($_REQUEST['file']))
		fatal_lang_error('arcade_no_games_selected', false);

	if (!is_array($_REQUEST['file']))
		$games = array($_REQUEST['file']);
	else
		$games = $_REQUEST['file'];

	foreach ($games as $id => $game)
		$games[$id] = (int) $game;
	$games = array_unique($games);

	if (count($games) == 0)
		fatal_lang_error('arcade_no_games_selected', false);

	$request = $smcFunc['db_query']('', '
		SELECT id_file, file_type, status
		FROM {db_prefix}arcade_files
		WHERE id_file IN ({array_int:games})
			AND status = 10',
		array(
			'games' => $games,
		)
	);

	$unpack = array();
	$install = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		// Needs uncompression?
		if ($row['file_type'] !== 'game')
			$unpack[] = $row['id_file'];
		else
			$install[] = $row['id_file'];
	}
	$smcFunc['db_free_result']($request);

	// Unpack games first
	if (!empty($unpack))
		unpackGames($unpack);

	if (!empty($install))
	{
		$_SESSION['qaction'] = 'install';
		$_SESSION['qaction_data'] = installGames($install);
	}

	redirectexit('action=admin;area=managegames;sa=install;done');
}

function ManageGamesUninstall()
{
	global $smcFunc, $context, $txt, $scripturl;

	checkSession('request');

	if (!isset($_REQUEST['game']))
		fatal_lang_error('arcade_no_games_selected', false);

	if (!is_array($_REQUEST['game']))
		$games = array($_REQUEST['game']);
	else
		$games = $_REQUEST['game'];

	foreach ($games as $id => $game)
		$games[$id] = (int) $game;
	$games = array_unique($games);

	if (count($games) == 0)
		fatal_lang_error('arcade_no_games_selected', false);

	$request = $smcFunc['db_query']('', '
		SELECT f.id_file, f.id_game, f.file_type, f.status, g.game_name
		FROM {db_prefix}arcade_files AS f
			INNER JOIN {db_prefix}arcade_games AS g ON (g.id_game = f.id_game)
		WHERE f.id_game IN ({array_int:games})
			AND f.status < 10',
		array(
			'games' => $games,
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		fatal_lang_error('arcade_no_games_selected', false);

	$context['games'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['games'][$row['id_game']] = array(
			'id' => $row['id_game'],
			'id_file' => $row['id_file'],
			'name' => $row['game_name'],
		);
	$smcFunc['db_free_result']($request);

	$context['confirm_url'] = $scripturl . '?action=admin;area=managegames;sa=uninstall2';
	$context['confirm_title'] = $txt['arcade_uninstall_games'];
	$context['confirm_text'] = $txt['arcade_following_games_uninstall'];
	$context['confirm_button'] = $txt['arcade_uninstall_games'];

	// Template
	$context['sub_template'] = 'manage_games_uninstall_confirm';
}

function ManageGamesUninstall2()
{
	global $smcFunc, $context;

	checkSession('request');

	if (!isset($_REQUEST['game']))
		fatal_lang_error('arcade_no_games_selected', false);

	if (!is_array($_REQUEST['game']))
		$games = array($_REQUEST['game']);
	else
		$games = $_REQUEST['game'];

	foreach ($games as $id => $game)
		$games[$id] = (int) $game;
	$games = array_unique($games);

	if (count($games) == 0)
		fatal_lang_error('arcade_no_games_selected', false);

	$request = $smcFunc['db_query']('', '
		SELECT f.id_file, f.id_game, f.file_type, f.status, g.game_name
		FROM {db_prefix}arcade_files AS f
			INNER JOIN {db_prefix}arcade_games AS g ON (g.id_game = f.id_game)
		WHERE f.id_game IN ({array_int:games})
			AND f.status < 10',
		array(
			'games' => $games,
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		fatal_lang_error('arcade_no_games_selected', false);

	$context['games'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['games'][$row['id_game']] = array(
			'id_game' => $row['id_game'],
			'id_file' => $row['id_file'],
			'name' => $row['game_name']
		);
	$smcFunc['db_free_result']($request);

	$id_game = array();
	foreach ($context['games'] as $game)
		$id_game[] = $game['id_game'];

	$_SESSION['qaction'] = 'uninstall';
	$_SESSION['qaction_data'] = uninstallGames($id_game, isset($_REQUEST['remove_files']));

	redirectexit('action=admin;area=managegames;sa=main;done');
}

function ManageGamesUpload()
{
	global $scripturl, $txt, $modSettings, $context, $sourcedir, $smcFunc;

	if (!is_writable($modSettings['gamesDirectory']) && !chmod($modSettings['gamesDirectory'], 0777))
		fatal_lang_error('arcade_not_writable', false, array($modSettings['gamesDirectory']));

	$context['post_max_size'] = return_bytes(ini_get('post_max_size')) / 1048576;

	// Template
	$context['sub_template'] = 'manage_games_upload';
}

function ManageGamesUpload2()
{
	global $scripturl, $txt, $modSettings, $context, $sourcedir, $smcFunc;

	foreach ($_FILES['attachment']['tmp_name'] as $n => $dummy)
	{
		if ($_FILES['attachment']['name'][$n] == '')
			continue;

		$newname = strtolower(basename($_FILES['attachment']['name'][$n]));
		$target = $modSettings['gamesDirectory'];

		if (substr($newname, -2) != 'gz' && substr($newname, -3) != 'tar' && substr($newname, -3) != 'zip')
			continue;

		if ($target != $modSettings['gamesDirectory'])
		{
			if (!file_exists($target) && !mkdir($target, 0777))
				fatal_lang_error('arcade_not_writable', false, array($target));

			if (!is_writable($target) && !chmod($target, 0777))
				fatal_lang_error('arcade_not_writable', false, array($target));
		}

		if (!move_uploaded_file($_FILES['attachment']['tmp_name'][$n], $target . '/' . $newname))
			fatal_lang_error('arcade_upload_file', false);
	}

	redirectexit('action=admin;area=managegames;sa=install');
}

function EditGame()
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $sourcedir, $smcFunc, $boarddir;

	$context['game_permissions'] = $modSettings['arcadePermissionMode'] > 2;
	$context['edit_page'] = !isset($_REQUEST['advanced']) ? 'basic' : 'advanced';

	// Load game data unless it has been loaded by EditGame2
	if (!isset($context['game']))
	{
		$id = loadGame((int) $_REQUEST['game'], true);

		if ($id === false)
			fatal_lang_error('arcade_game_not_found', false);

		$game = &$context['arcade']['game_data'][$id];

		$context['game'] = array(
			'id' => $game['id_game'],
			'internal_name' => $game['internal_name'],
			'category' => $game['id_cat'],
			'name' => htmlspecialchars($game['game_name']),
			'thumbnail' => htmlspecialchars($game['thumbnail']),
			'thumbnail_small' => htmlspecialchars($game['thumbnail_small']),
			'description' => htmlspecialchars($game['description']),
			'help' => htmlspecialchars($game['help']),
			'game_file' => $game['game_file'],
			'game_directory' => $game['game_directory'],
			'submit_system' => $game['submit_system'],
			'score_type' => $game['score_type'],
			'member_groups' => explode(',', $game['member_groups']),
			'extra_data' => unserialize($game['extra_data']),
			'enabled' => !empty($game['enabled']),
		);

		if (!is_array($context['game']['extra_data']) || isset($_REQUEST['detect']))
		{
			require_once($sourcedir . '/SWFReader.php');
			$swf = new SWFReader();

			if (substr($game['game_file'], -3) == 'swf')
			{
				$swf->open($modSettings['gamesDirectory'] . '/' . $game['game_directory'] . '/' . $game['game_file']);

				$context['game']['extra_data'] = array(
					'width' => $swf->header['width'],
					'height' => $swf->header['height'],
					'flash_version' => $swf->header['version'],
					'background_color' => $swf->header['background'],
				);

				$swf->close();
			}
			else
			{
				$context['game']['extra_data'] = array(
					'width' => '',
					'height' => '',
					'flash_version' => '',
					'background_color' => array('', '', ''),
				);
			}
		}
	}

	if ($context['game_permissions'])
		$context['groups'] = arcadeGetGroups($context['game']['member_groups']);

	// Load categories
	if (!isset($context['arcade_category']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_cat, cat_name
			FROM {db_prefix}arcade_categories'
		);

		$context['arcade_category'] = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['arcade_category'][] = array(
				'id' => $row['id_cat'],
				'name' => $row['cat_name']
			);
		$smcFunc['db_free_result']($request);
	}
	
	// Load Sumbit Systems
	if (!isset($context['submit_systems']))
		$context['submit_systems'] = SubmitSystemInfo('*');
		
	$context['template_layers'][] = 'edit_game';

	if (!isset($_REQUEST['advanced']))
		$context['sub_template'] = 'edit_game_basic';
	else
		$context['sub_template'] = 'edit_game_advanced';
}

function EditGame2()
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $sourcedir, $smcFunc, $boarddir;

	$context['game_permissions'] = $modSettings['arcadePermissionMode'] > 2;
	$context['edit_page'] = !isset($_REQUEST['advanced']) ? 'basic' : 'advanced';

	if (!isset($context['game']))
	{
		$id = loadGame((int) $_REQUEST['game'], true);

		if ($id === false)
			fatal_lang_error('arcade_game_not_found', false);

		$game = &$context['arcade']['game_data'][$id];

		$context['game'] = array(
			'id' => $game['id_game'],
			'internal_name' => $game['internal_name'],
			'category' => $game['id_cat'],
			'name' => htmlspecialchars($game['game_name']),
			'thumbnail' => htmlspecialchars($game['thumbnail']),
			'thumbnail_small' => htmlspecialchars($game['thumbnail_small']),
			'description' => htmlspecialchars($game['description']),
			'help' => htmlspecialchars($game['help']),
			'game_file' => $game['game_file'],
			'game_directory' => $game['game_directory'],
			'submit_system' => $game['submit_system'],
			'score_type' => $game['score_type'],
			'member_groups' => explode(',', $game['member_groups']),
			'extra_data' => unserialize($game['extra_data']),
			'enabled' => !empty($game['enabled']),
		);

		if (!is_array($context['game']['extra_data']) || isset($_REQUEST['detect']))
		{
			require_once($sourcedir . '/SWFReader.php');
			$swf = new SWFReader();

			if (substr($game['game_file'], -3) == 'swf')
			{
				$swf->open($modSettings['gamesDirectory'] . '/' . $game['game_directory'] . '/' . $game['game_file']);

				$context['game']['extra_data'] = array(
					'width' => $swf->header['width'],
					'height' => $swf->header['height'],
					'flash_version' => $swf->header['version'],
					'background_color' => $swf->header['background'],
				);

				$swf->close();
			}
			else
			{
				$context['game']['extra_data'] = array(
					'width' => '',
					'height' => '',
					'flash_version' => '',
					'background_color' => array('', '', ''),
				);
			}
		}
	}

	$context['game_permissions'] = $modSettings['arcadePermissionMode'] > 2;

	// Load categories
	if (!isset($context['arcade_category']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_cat, cat_name
			FROM {db_prefix}arcade_categories'
		);

		$context['arcade_category'] = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['arcade_category'][] = array(
				'id' => $row['id_cat'],
				'name' => $row['cat_name']
			);
		$smcFunc['db_free_result']($request);
	}
	
	// Load Sumbit Systems
	if (!isset($context['submit_systems']))
		$context['submit_systems'] = SubmitSystemInfo('*');

	$gameOptions = array();
	$errors = array();

	if (checkSession('post', '', false) !== '')
		$errors['session'] = 'session_timeout';

	// Basic
	if (empty($_REQUEST['edit_page']) || $_REQUEST['edit_page'] == 'basic')
	{
		if (isset($_POST['game_name']) && trim($_POST['game_name']) == '')
			$errors['game_name'] = 'invalid';

		$gameOptions['name'] = $_POST['game_name'];
		$gameOptions['description'] = $_POST['description'];
		$gameOptions['thumbnail'] = $_POST['thumbnail'];
		$gameOptions['thumbanil_small'] = $_POST['thumbnail_small'];
		$gameOptions['help'] = $_POST['help'];

		$gameOptions['enabled'] = !empty($_POST['game_enabled']);

		if ($context['game_permissions'])
		{
			$gameOptions['member_groups'] = array();

			if (!empty($_POST['groups']))
				foreach ($_POST['groups'] as $id)
					$gameOptions['member_groups'][] = (int) $id;
		}

		$gameOptions['category'] = (int) $_POST['category'];
	}
	// Advanced
	else
	{
		if (trim($_POST['internal_name']) == '')
			$errors['internal_name'] = 'invalid';

		if (trim($_POST['game_file']) == '')
			$errors['game_file'] = 'invalid';

		if (!isset($context['submit_systems'][$_POST['submit_system']]))
			$errors['submit_system'] = 'invalid';

		$extra_data = $context['game']['extra_data'];

		if (isset($_POST['extra_data']))
		{
			foreach ($_POST['extra_data'] as $item => $value)
				$extra_data[$item] = $value;
		}

		$gameOptions['internal_name'] = $_POST['internal_name'];
		$gameOptions['submit_system'] = $_POST['submit_system'];
		$gameOptions['game_directory'] = $_POST['game_directory'];
		$gameOptions['game_file'] = $_POST['game_file'];
		$gameOptions['score_type'] = (int) $_POST['score_type'];
		$gameOptions['extra_data'] = $extra_data;
	}

	if (!empty($errors))
	{
		$context['errors'] = $errors;
		return EditGame();
	}

	updateGame($context['game']['id'], $gameOptions, true);

	redirectexit('action=admin;area=managegames');
}

function ExportGameInfo()
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $sourcedir, $smcFunc, $boarddir;

	$id = loadGame((int) $_REQUEST['game'], true);

	if ($id === false)
		fatal_lang_error('arcade_game_not_found', false);

	$game = &$context['arcade']['game_data'][$id];

	ob_end_clean();
	if (!empty($modSettings['enableCompressedOutput']))
		@ob_start('ob_gzhandler');
	else
		ob_start();

	$game['extra_data'] = unserialize($game['extra_data']);
	$extra = '';

	if (isset($game['extra_data']['flash_version']))
	{
		$extra = '
	<flash>
		<version>' . $game['extra_data']['flash_version'] . '</version>
		<width>' . $game['extra_data']['width'] . '</width>
		<height>' . $game['extra_data']['height'] . '</height>
		<bgcolor>' . strtoupper(implode('', array_map('dechex', $game['extra_data']['background_color'])))  . '</bgcolor>
	</flash>';
	}

	header('Content-Type: text/xml; charset=' . (empty($context['character_set']) ? 'ISO-8859-1' : $context['character_set']));

	printf('<?xml version="1.0"?>
<!-- Generated with SMF Arcade %s -->
<game-info>
	<id>%s</id>
	<name><![CDATA[%s]]></name>
	<description><![CDATA[%s]]></description>
	<help><![CDATA[%s]]></help>
	<thumbnail>%s</thumbnail>
	<thumbnail-small>%s</thumbnail-small>
	<file>%s</file>
	<scoring>%d</scoring>
	<submit>%s</submit>%s
</game-info>',
	SMFArcade::VERSION,
	$game['internal_name'], htmlspecialchars($game['game_name']), htmlspecialchars($game['description']),
	htmlspecialchars($game['help']), htmlspecialchars($game['thumbnail']), htmlspecialchars($game['thumbnail_small']),
	$game['game_file'], $game['score_type'], $game['submit_system'], $extra
);

	obExit(false);
}

?>