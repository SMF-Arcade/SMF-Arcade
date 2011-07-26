<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6 Alpha
 * @license http://download.smfarcade.info/license.php New-BSD
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/*
	void ArcadeMaintenance()
		- ???

	void ArcadeMaintenanceActions()
		- ???

	void MaintenanceFixScores()
		- ???

	void fixScores()
		- ???

	void fixCategories()
		- ???
*/

function ArcadeMaintenance()
{
	global $sourcedir, $scripturl, $txt, $modSettings, $context, $settings;

	require_once($sourcedir . '/Arcade.php');
	require_once($sourcedir . '/Subs-ArcadeAdmin.php');
	require_once($sourcedir . '/ManageServer.php');

	isAllowedTo('arcade_admin');
	SMFArcade::loadArcade('admin', 'arcademaintenance');

	// Template
	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['arcade_maintenance'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['arcade_maintenance_desc'];

	$subActions = array(
		'main' => array('ArcadeMaintenanceActions'),
		'highscore' =>  array('ArcadeMaintenanceHighscore'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	$subActions[$_REQUEST['sa']][0]();
}

function ArcadeMaintenanceActions()
{
	global $sourcedir, $scripturl, $txt, $modSettings, $context, $settings;

	$maintenanceActions = array(
		'fixScores' => array('MaintenanceFixScores'),
		'updateGamecache' => array('MaintenanceGameCache'),
	);

	$context['maintenance_finished'] = false;

	if (!empty($_REQUEST['maintenance']) && isset($maintenanceActions[$_REQUEST['maintenance']]))
	{
		checkSession('request');

		$maintenanceActions[$_REQUEST['maintenance']][0]();

		$context['maintenance_finished'] = true;
	}

	// Template
	$context['sub_template'] = 'arcade_admin_maintenance';
}

function ArcadeMaintenanceHighscore()
{
	global $sourcedir, $scripturl, $txt, $modSettings, $context, $settings, $smcFunc;

	if (isset($_REQUEST['score_action']))
	{
		checkSession();

		if ($_REQUEST['score_action'] == 'older' && is_numeric($_REQUEST['age']))
		{
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}arcade_scores
				WHERE end_time < {int:time}',
				array(
					'time' => time() - ((int) $_REQUEST['age'] * 86400)
				)
			);
		}
		elseif ($_REQUEST['score_action'] == 'all')
		{
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}arcade_scores',
				array(
				)
			);
		}

		redirectexit('action=admin;area=arcademaintenance;maintenance=fixScores;back=score;' . $context['session_var'] . '=' . $context['session_id']);
	}

	// Template
	$context['sub_template'] = 'arcade_admin_maintenance_highscore';
}

function MaintenanceFixScores()
{
	global $db_prefix, $modSettings, $smcFunc, $context;

	$request = $smcFunc['db_query']('', '
		SELECT id_game, score_type, extra_data
		FROM {db_prefix}arcade_games');

	while ($row = $smcFunc['db_fetch_assoc']($request))
		fixScores($row['id_game'], $row['score_type']);

	$smcFunc['db_free_result']($request);

	if (isset($_REQUEST['back']) && $_REQUEST['back'] == 'score')
		redirectexit('action=admin;area=arcademaintenance;sa=highscore');
}

function MaintenanceGameCache()
{
	global $db_prefix, $modSettings, $smcFunc, $context;

	loadClassFile('Class-Package.php');
	updateGameCache();
}

function fixScores($id_game, $score_type)
{
	global $db_prefix, $modSettings, $smcFunc;

	// This will use a lot of queries so don't use unless necassary ;)

	if ($score_type == 0)
		$order = 'DESC';
	elseif ($score_type == 1)
		$order = 'ASC';
	else
		return false;

	$users = array();
	$position = 1;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS scores, id_member
		FROM {db_prefix}arcade_scores
		WHERE id_game = {int:game}
		GROUP BY id_member',
		array(
			'game' => $id_game,
		)
	);

	$removeScores = array();
	$scoreCount = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!empty($modSettings['arcadeMaxScores']) && $row['scores'] > $modSettings['arcadeMaxScores'])
		{
			$removeScores[$row['id_member']] = $row['scores'] - $modSettings['arcadeMaxScores'];
			$scoreCount[$row['id_member']] = $row['scores'] - $modSettings['arcadeMaxScores'];
		}
		else
		{
			$scoreCount[$row['id_member']] = $row['scores'];
		}
	}
	$smcFunc['db_free_result']($request);

	// Remove some scores
	if (!empty($removeScores))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_member, id_score
			FROM {db_prefix}arcade_scores
			WHERE id_game = {int:game}
				AND id_member IN({array_int:members})
			ORDER BY score ' . ($score_type == 0 ? 'ASC' : 'DESC'),
			array(
				'game' => $id_game,
				'members' => array_keys($removeScores)
			)
		);

		$removeIds = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if ($removeScores[$row['id_member']] > 0)
			{
				$removeIds[] = $row['id_score'];
				$removeScores[$row['id_member']]--;
			}
		}

		if (!empty($removeIds))
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}arcade_scores
				WHERE id_score IN({array_int:scores})',
				array(
					'scores' => $removeIds,
				)
			);
	}

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}arcade_scores
		SET personal_best = 0
		WHERE id_game = {int:game}',
		array(
			'game' => $id_game,
		)
	);

	$request = $smcFunc['db_query']('', '
		SELECT id_score, score, id_member, position
		FROM {db_prefix}arcade_scores
		WHERE id_game = {int:game}
		ORDER BY score ' . $order,
		array(
			'game' => $id_game,
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		updateGame($id_game, array('champion' => 0, 'champion_score' => 0));

	// Postions and personalbest
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$set = array();

		if (!in_array($row['id_member'], $users))
		{
			$users[] = $row['id_member'];
			$set[] = 'personal_best = 1';
		}

		if ($position != $row['position'])
			$set[] = 'position = {int:position}';

		if (count($set) > 0)
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}arcade_scores
				SET ' . implode(',', $set) . '
				WHERE id_score = {int:score}',
				array(
					'score' => $row['id_score'],
					'position' => $position,
				)
			);

		if ($position == 1)
			updateGame($id_game, array('champion' => $row['id_member'], 'champion_score' => $row['id_score'],));

		$position++;
	}
	$smcFunc['db_free_result']($request);

	// And champion times is still left
	$request = $smcFunc['db_query']('', '
		SELECT id_score, score, end_time
		FROM {db_prefix}arcade_scores
		WHERE id_game = {int:game}
		ORDER BY score ' . $order,
		array(
			'game' => $id_game,
		)
	);

	$best = 0;
	$best_id = 0;

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (($score_type == 0 && $best <= $row['score']) || ($score_type == 1 && $best >= $row['score']))
		{
			$end = $row['end_time'] - 1;

			if ($best_id > 0)
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}arcade_scores
					SET champion_from = end_time, champion_to = {int:champion_to}
					WHERE id_score = {int:best}',
				array(
					'champion_to' => $end,
					'best' => $best_id,
				)
			);

			$best = $row['score'];
			$best_id = $row['id_score'];
		}
	}
	$smcFunc['db_free_result']($request);

	return true;
}

function fixCategories()
{
	global $db_prefix, $modSettings, $smcFunc;

	if (!isset($modSettings['arcadeDefaultCategory']))
		fatal_lang_error('arcade_category_no_default', false);

	$request = $smcFunc['db_query']('', '
		SELECT cat_name
		FROM {db_prefix}arcade_categories
		WHERE id_cat = {int:category}
			AND special = 1',
		array(
			'category' => $modSettings['arcadeDefaultCategory']
		)
	);

	if ($smcFunc['db_num_rows']($res) == 0)
		fatal_lang_error('arcade_category_no_default', false);
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT id_game, IFNULL(cat.cat_name, {string:empty}) AS cn
		FROM {db_prefix}arcade_games AS game
			LEFT JOIN {db_prefix}arcade_categories AS cat ON cat.id_cat = game.id_cat',
		array(
			'empty' => '',
		)
	);

	while ($game = $smcFunc['db_fetch_assoc']($request))
		if ($game['cn'] == '')
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}arcade_games
				SET id_cat = {int:category}
				WHERE id_game = {int:game}',
				array(
					'category' => $modSettings['arcadeDefaultCategory'],
					'game' => $game['id_game'],
				)
			);
	$smcFunc['db_free_result']($request);

	redirectexit('action=arcade;maintenace=done');
}

?>