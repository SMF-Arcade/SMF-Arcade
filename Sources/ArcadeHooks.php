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
	
function arcade_array_insert(&$input, $key, $insert, $where = 'before', $strict = false)
{
	$position = array_search($key, array_keys($input), $strict);
	
	// Key not found -> insert as last
	if ($position === false)
	{
		$input = array_merge($input, $insert);
		return;
	}
	
	if ($where === 'after')
		$position += 1;

	// Insert as first
	if ($position === 0)
		$input = array_merge($insert, $input);
	else
		$input = array_merge(
			array_slice($input, 0, $position, true),
			$insert,
			array_slice($input, $position, null, true)
		);
}
	
function Arcade_actions(&$actionArray)
{
	global $modSettings;
	
	if (empty($modSettings['arcadeEnabled']))
		return;
	
	$actionArray['arcade'] = array('Arcade.php', 'Arcade');
}

function Arcade_core_features(&$core_features)
{
	$core_features['arcade'] = array(
		'url' => 'action=admin;area=arcade',
		'settings' => array(
			'arcadeEnabled' => 1,
		),
	);
}

function Arcade_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	global $context;
	
	$permissionList['membergroup'] += array(
		'arcade_view' => array(false, 'arcade', 'arcade'),
		'arcade_play' => array(false, 'arcade', 'arcade'),
		'arcade_submit' => array(false, 'arcade', 'arcade'),
		'arcade_comment' => array(true, 'arcade', 'arcade', 'arcade_moderate'),
		'arcade_user_stats' => array(true, 'arcade', 'arcade', 'arcade_moderate'),
		'arcade_edit_settings' => array(true, 'arcade', 'arcade', 'arcade_moderate'),
		'arcade_create_match' => array(false, 'arcade', 'arcade'),
		'arcade_join_match' => array(false, 'arcade', 'arcade'),
		'arcade_join_invite_match' => array(false, 'arcade', 'arcade'),
		'arcade_admin' => array(false, 'arcade', 'administrate'),
	);
	
	$context['non_guest_permissions'] = array_merge(
		$context['non_guest_permissions'],
		array(
			'arcade_admin',
			'arcade_create_match',
			'arcade_join_match',
			'arcade_join_invite_match',
			'arcade_comment',
			'arcade_edit_settings',
			'arcade_user_stats',
		)
	);
}

function Arcade_profile_areas(&$profile_areas)
{
	global $modSettings, $txt;
	
	arcade_array_insert($profile_areas['profile_action']['areas'], 'issuewarning',
		array(
			'arcadeChallenge' => array(
				'label' => $txt['sendArcadeChallenge'],
				'file' => 'Profile-Arcade.php',
				'function' => 'arcadeChallenge',
				'enabled' => !empty($modSettings['arcadeArenaEnabled']) && !empty($modSettings['arcadeEnabled']),
				'permission' => array(
					'own' => array(),
					'any' => array('arcade_create_match'),
				),
			),
		)
	);
	
	arcade_array_insert($profile_areas['info']['areas'], 'showposts',
		array(
			'arcadeStats' => array(
				'label' => $txt['arcadeStats'],
				'file' => 'Profile-Arcade.php',
				'function' => 'arcadeStats',
				'enabled' => !empty($modSettings['arcadeEnabled']),
				'permission' => array(
					'own' => array('arcade_user_stats_any', 'arcade_user_stats_own'),
					'any' => array('arcade_user_stats_any'),
				),
			),
		)
	);
	
	arcade_array_insert($profile_areas['edit_profile']['areas'], 'forumprofile',
		array(
			'arcadeSettings' => array(
				'label' => $txt['arcadeSettings'],
				'file' => 'Profile-Arcade.php',
				'function' => 'arcadeSettings',
				'enabled' => !empty($modSettings['arcadeEnabled']),
				'permission' => array(
					'own' => array('arcade_edit_settings_any', 'arcade_edit_settings_own'),
					'any' => array('arcade_edit_settings_any'),
				),
			),
		)
	);
}

function Arcade_menu_buttons(&$menu_buttons)
{
	global $context, $modSettings, $scripturl, $txt;
	
	if (!$context['allow_admin'])
		$context['allow_admin'] = allowedTo('arcade_admin');
	
	$context['allow_arcade'] = allowedTo('arcade_view') && !empty($modSettings['arcadeEnabled']);
	
	arcade_array_insert($menu_buttons, 'search',
		array(
			'arcade' => array(
				'title' => $txt['arcade'],
				'href' => $scripturl . '?action=arcade',
				'show' => $context['allow_arcade'],
				'active_button' => false,
				'sub_buttons' => array(
				),
			),
		)
	);
}

function Arcade_admin_areas(&$admin_areas)
{
	global $context, $modSettings, $scripturl, $txt;
	
	arcade_array_insert($admin_areas, 'members',
		array(
			'arcade' => array(
				'title' => $txt['arcade_admin'],
				'permission' => array('arcade_admin'),
				'areas' => array(
					'arcade' => array(
						'label' => $txt['arcade_general'],
						'file' => 'ArcadeAdmin.php',
						'function' => 'ArcadeAdmin',
						'enabled' => !empty($modSettings['arcadeEnabled']),
						'permission' => array('arcade_admin'),
						'subsections' => array(
							'main' => array($txt['arcade_general_information']),
							'settings' => array($txt['arcade_general_settings']),
							'permission' => array($txt['arcade_general_permissions']),
						),
					),
					'managegames' => array(
						'label' => $txt['arcade_manage_games'],
						'file' => 'ManageGames.php',
						'function' => 'ManageGames',
						'enabled' => !empty($modSettings['arcadeEnabled']),
						'permission' => array('arcade_admin'),
						'subsections' => array(
							'main' => array($txt['arcade_manage_games_edit_games']),
							'install' => array($txt['arcade_manage_games_install']),
							'upload' => array($txt['arcade_manage_games_upload']),
						),
					),
					'arcadecategory' => array(
						'label' => $txt['arcade_manage_category'],
						'file' => 'ArcadeAdmin.php',
						'function' => 'ArcadeAdminCategory',
						'enabled' => !empty($modSettings['arcadeEnabled']),
						'permission' => array('arcade_admin'),
						'subsections' => array(
							'list' => array($txt['arcade_manage_category_list']),
							'new' => array($txt['arcade_manage_category_new']),
						),
					),
					'arcademaintenance' => array(
						'label' => $txt['arcade_maintenance'],
						'file' => 'ArcadeMaintenance.php',
						'function' => 'ArcadeMaintenance',
						'enabled' => !empty($modSettings['arcadeEnabled']),
						'permission' => array('arcade_admin'),
						'subsections' => array(
							'main' => array($txt['arcade_maintenance_main']),
							'highscore' => array($txt['arcade_maintenance_highscore']),
						),
					),
				),
			),
		)
	);
}

?>