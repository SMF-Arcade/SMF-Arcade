<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6 Alpha
 * @license http://download.smfarcade.info/license.php New-BSD
 */

function arcadeStats($memID)
{
	global $db_prefix, $scripturl, $txt, $modSettings, $context, $settings, $user_info, $smcFunc, $sourcedir;

	require_once($sourcedir . '/Arcade.php');

	SMFArcade::loadArcade('profile');

	$context['arcade']['member_stats'] = array();

	$result = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS champion
		FROM {db_prefix}arcade_games
		WHERE id_champion = {int:member}
			AND enabled = 1',
		array(
			'member' => $memID,
		)
	);

	$context['arcade']['member_stats'] += $smcFunc['db_fetch_assoc']($result);
	$smcFunc['db_free_result']($result);

	$result = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS rates, (SUM(rating) / COUNT(*)) AS avg_rating
		FROM {db_prefix}arcade_rates
		WHERE id_member = {int:member}',
		array(
			'member' => $memID,
		)
	);

	$context['arcade']['member_stats'] += $smcFunc['db_fetch_assoc']($result);
	$smcFunc['db_free_result']($result);

	$result = $smcFunc['db_query']('', '
		SELECT s.position, s.score, s.end_time, game.game_name, game.id_game
		FROM ({db_prefix}arcade_scores AS s, {db_prefix}arcade_games AS game)
		WHERE id_member = {int:member}
			AND personal_best = 1
			AND s.id_game = game.id_game
			AND game.enabled = 1
		ORDER BY position
		LIMIT 10',
		array(
			'member' => $memID,
		)
	);

	$context['arcade']['member_stats']['scores'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($result))
		$context['arcade']['member_stats']['scores'][] = array(
			'link' => $scripturl . '?action=arcade;game=' . $row['id_game'],
			'name' => $row['game_name'],
			'score' => comma_format($row['score']),
			'position' => $row['position'],
			'time' => timeformat($row['end_time'])
		);
	$smcFunc['db_free_result']($result);

	$result = $smcFunc['db_query']('', '
		SELECT s.position, s.score, s.end_time, game.game_name, game.id_game
		FROM ({db_prefix}arcade_scores AS s, {db_prefix}arcade_games AS game)
		WHERE id_member = {int:member}
			AND personal_best = 1
			AND s.id_game = game.id_game
			AND game.enabled = 1
		ORDER BY end_time DESC
		LIMIT 10',
		array(
			'member' => $memID,
		)
	);

	$context['arcade']['member_stats']['latest_scores'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($result))
		$context['arcade']['member_stats']['latest_scores'][] = array(
			'link' => $scripturl . '?action=arcade;game=' . $row['id_game'],
			'name' => $row['game_name'],
			'score' => comma_format($row['score']),
			'position' => $row['position'],
			'time' => timeformat($row['end_time'])
		);
	$smcFunc['db_free_result']($result);

	// Layout
	$context['sub_template'] = 'arcade_user_statistics';
	$context['page_title'] = sprintf($txt['arcade_user_stats_title'], $context['member']['name']);
}

function arcadeChallenge($memID)
{
	global $db_prefix, $scripturl, $txt, $modSettings, $context, $settings, $user_info, $smcFunc, $sourcedir;

	require_once($sourcedir . '/Arcade.php');
	require_once($sourcedir . '/ArcadeArena.php');
	require_once($sourcedir . '/Subs-Members.php');

	SMFArcade::loadArcade('profile');

	if (!memberAllowedTo(array('arcade_join_match', 'arcade_join_invite_match'), $memID))
		fatal_lang_error('arcade_no_invite', false);

	$context['matches'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT id_match, name
		FROM {db_prefix}arcade_matches
		WHERE id_member = {int:member}
			AND status = 0',
		array(
			'member' => $user_info['id'],
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['matches'][] = array(
			'id' => $row['id_match'],
			'name' => $row['name'],
		);
	$smcFunc['db_free_result']($request);

	// Layout
	$context['sub_template'] = 'arcade_arena_challenge';
	$context['page_title'] = sprintf($txt['arcade_arena_challenge_title'], $context['member']['name']);
}

function arcadeSettings($memID)
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc, $user_info, $sourcedir, $modSettings;

	require_once($sourcedir . '/Arcade.php');

	SMFArcade::loadArcade('profile');

	$events = arcadeEvent('get');

	$arcadeSettings = loadArcadeSettings($memID);

	$context['profile_fields'] = array(
		'notifications' => array(
			'type' => 'callback',
			'callback_func' => 'arcade_notification',
		),
		'gamesPerPage' => array(
			'label' => $txt['arcade_user_gamesPerPage'],
			'type' => 'select',
			'options' => array(
				0 => sprintf($txt['arcade_user_gamesPerPage_default'], $modSettings['gamesPerPage']),
				5 => 5,
				10 => 10,
				20 => 20,
				25 => 25,
				50 => 50,
			),
			'cast' => 'int',
			'validate' => 'int',
			'value' => isset($arcadeSettings['gamesPerPage']) ? $arcadeSettings['gamesPerPage'] : 0,
		),
		'scoresPerPage' => array(
			'label' => $txt['arcade_user_scoresPerPage'],
			'type' => 'select',
			'options' => array(
				0 => sprintf($txt['arcade_user_scoresPerPage_default'], $modSettings['scoresPerPage']),
				5 => 5,
				10 => 10,
				20 => 20,
				25 => 25,
				50 => 50,
			),
			'cast' => 'int',
			'validate' => 'int',
			'value' => isset($arcadeSettings['scoresPerPage']) ? $arcadeSettings['scoresPerPage'] : 0,
		)
	);

	if (!empty($modSettings['disableCustomPerPage']))
	{
		unset($context['profile_fields']['gamesPerPage']);
		unset($context['profile_fields']['scoresPerPage']);
	}

	if (isset($_REQUEST['save']))
	{
		checkSession('post');

		$updates = array();

		$errors = false;

		foreach ($events as $event)
		{
			foreach ($event['notification'] as $notify => $default)
			{
				if (empty($_POST[$notify]))
					$updates[] = array($memID, $notify, 0);
				else
					$updates[] = array($memID, $notify, 1);
			}
		}

		foreach ($context['profile_fields'] as $id => $field)
		{
			if ($id == 'notifications' || !isset($_POST[$id]))
				continue;

			if ($field['cast'] == 'int')
				$_POST[$id] = (int) $_POST[$id];

			if ($field['type'] == 'select')
			{
				if (isset($field['options'][$_POST[$id]]))
					$updates[] = array($memID, $id, $_POST[$id]);
			}
		}

		if (!$errors)
		{
			$smcFunc['db_insert']('replace',
				'{db_prefix}arcade_settings',
				array('id_member' => 'int', 'variable' => 'string-255', 'value' => 'string'),
				$updates,
				array('id_member', 'variable')
			);

			redirectexit('action=profile;u=' . $memID . ';sa=arcadeSettings');
		}
	}

	$context['notifications'] = array();

	foreach ($events as $event)
	{
		foreach ($event['notification'] as $notify => $default)
		{
			$context['notifications'][$notify] = array(
				'id' => $notify,
				'text' => $txt['arcade_notification_' . $notify],
				'value' => isset($arcadeSettings[$notify]) ? (bool) $arcadeSettings[$notify] : $default,
				'default' => !isset($arcadeSettings[$notify])
			);
		}
	}

	// Template
	$context['profile_custom_submit_url'] = $scripturl . '?action=profile;area=arcadeSettings;u=' . $memID . ';save';
	$context['page_desc'] = $txt['arcade_usersettings_desc'];
	$context['sub_template'] = 'edit_options';
}

?>