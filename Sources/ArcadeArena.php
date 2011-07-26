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
	void ArcadeMatchList()
		- ???

	void ArcadeMatchList()
		- ???

	void ArcadeViewMatch()
		- ???

	void ArcadeMatchSelectGame()
		- ???

	void ArcadeNewMatch()
		- ???

	void ArcadeNewMatch2()
		- ???

	void ArcadeProfileInvite()
		- ???
*/

function ArcadeMatchList()
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc, $user_info, $modSettings;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}arcade_matches AS m
			LEFT JOIN {db_prefix}arcade_matches_players AS me ON (me.id_match = m.id_match AND me.id_member = {int:member})
		WHERE ({query_see_match})',
		array(
			'member' => $user_info['id'],
		)
	);

	list ($matchCount) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$context['page_index'] = constructPageIndex($scripturl . '?action=arcade;sa=arena', $_REQUEST['start'], $matchCount , $modSettings['matchesPerPage'], false);

	$request = $smcFunc['db_query']('', '
		SELECT
			m.id_match, m.name, m.private_game, m.created, m.updated, m.status,
			m.num_players, m.current_players, m.num_rounds, m.current_round,
			IFNULL(me.id_member, 0) AS participation, me.status AS my_state,
			mem.id_member, mem.real_name
		FROM {db_prefix}arcade_matches AS m
			LEFT JOIN {db_prefix}arcade_matches_players AS me ON (me.id_match = m.id_match AND me.id_member = {int:member})
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = m.id_member)
		WHERE {query_see_match}
		ORDER BY me.status = 1 DESC',
		array(
			'member' => $user_info['id'],
		)
	);

	$context['matches'] = array();

	$status = array(
		10 => 'arcade_arena_waiting_players',
		11 => 'arcade_arena_waiting_other_players',
		20 => 'arcade_arena_started',
		21 => 'arcade_arena_not_played',
		22 => 'arcade_arena_not_played',
		23 => 'arcade_arena_not_other_played',
		24 => 'arcade_arena_dropped',
		30 => 'arcade_arena_complete',
		31 => 'arcade_arena_complete',
		32 => 'arcade_arena_complete',
		33 => 'arcade_arena_complete',
		34 => 'arcade_arena_complete',
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['matches'][] = array(
			'id' => $row['id_match'],
			'name' => $row['name'],
			'link' => '<a href="' . $scripturl . '?action=arcade;sa=viewMatch;match=' . $row['id_match'] . '">' . $row['name'] . '</a>',
			'status' => $status[($row['my_state'] + ($row['status'] * 10 + 10))],
			'joined' => (bool) $row['participation'],
			'my_state' => $row['my_state'],
			'is_private' => (bool) $row['private_game'],
			'players' => $row['current_players'],
			'players_limit' => $row['num_players'],
			'round' => $row['current_round'],
			'rounds' => $row['num_rounds'],
			'starter' => array(
				'id' => $row['id_member'],
				'name' => $row['real_name'],
				'link' => !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>' : '',
			),
		);
	}
	$smcFunc['db_free_result']($request);

	// Layout
	loadTemplate('ArcadeArena');
	$context['sub_template'] = 'arcade_arena_matches';
	$context['page_title'] = $txt['arcade_arena'];

	// Add Arena to link tree
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=arcade;sa=arena',
		'name' => $txt['arcade_arena'],
	);
}

function ArcadeViewMatch()
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc, $user_info;

	if (empty($_REQUEST['match']))
		fatal_lang_error('match_not_found', false);

	loadMatch((int) $_REQUEST['match']);

	// Delete Match
	if (isset($_REQUEST['delete']) && $context['can_edit_match'])
	{
		checkSession('get');
		deleteMatch($context['match']['id']);
		redirectexit('action=arcade;sa=arena');
	}
	// Start match
	elseif (isset($_GET['start']) && $context['can_start_match'])
	{
		checkSession('get');

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}arcade_matches
			SET num_players = current_players
			WHERE id_match = {int:match}',
			array(
				'match' => $context['match']['id'],
			)
		);

		matchUpdateStatus($context['match']['id']);

		redirectexit('action=arcade;sa=viewMatch;match=' . $context['match']['id']);
	}
	// Leave match
	elseif (isset($_REQUEST['leave']) && ($context['can_leave'] || $context['can_decline']))
	{
		checkSession('get');

		// It's starter leaving, delete whole match
		if ($user_info['id'] == $context['match']['starter'])
		{
			deleteMatch($context['match']['id']);
			redirectexit('action=arcade;sa=arena');
		}
		else
		{
			matchRemovePlayers(
				$context['match']['id'],
				array(
					$user_info['id']
				)
			);
		}

		redirectexit('action=arcade;sa=viewMatch;match=' . $context['match']['id']);
	}
	// Kick some user
	elseif (isset($_REQUEST['kick']) && !empty($context['match']['players'][$_REQUEST['player']]['can_kick']))
	{
		checkSession('get');

		matchRemovePlayers(
			$context['match']['id'],
			array(
				$_REQUEST['player']
			)
		);

		redirectexit('action=arcade;sa=viewMatch;match=' . $context['match']['id']);
	}
	// Join
	elseif (isset($_REQUEST['join']) && $context['can_join_match'])
	{
		checkSession('get');

		matchAddPlayers(
			$context['match']['id'],
			array(
				$user_info['id'] => 1
			)
		);

		redirectexit('action=arcade;sa=viewMatch;match=' . $context['match']['id']);
	}
	// Accept
	elseif (isset($_REQUEST['join']) && $context['can_accept'])
	{
		checkSession('get');

		matchUpdatePlayers(
			$context['match']['id'],
			array(
				$user_info['id']
			),
			1
		);

		redirectexit('action=arcade;sa=viewMatch;match=' . $context['match']['id']);
	}

	// Layout
	loadTemplate('ArcadeArena');
	$context['template_layers'][] = 'arcade_arena_view_match';
	$context['sub_template'] = 'arcade_arena_view_match';
	$context['page_title'] = sprintf($txt['arcade_arena_view_match_title'], $context['match']['name']);
	
	// Add Arena to link tree
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=arcade;sa=arena',
		'name' => $txt['arcade_arena'],
	);
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=arcade;sa=viewMatch;match=' . $context['match']['id'],
		'name' => $context['match']['name'],
	);	
}

function ArcadeNewMatch($match = array(), $errors = array())
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc, $user_info, $sourcedir;

	require_once($sourcedir . '/Subs-Auth.php');
	require_once($sourcedir . '/Subs-Members.php');
	require_once($sourcedir . '/Subs-Editor.php');

	$context['match'] = $match + array(
		'name' => isset($_REQUEST['match_name']) ? trim($_REQUEST['match_name']) : '',
		'num_players' => isset($_REQUEST['players']) ? (int) $_REQUEST['players'] : 0,
		'private' => isset($_REQUEST['private']),
		'game_mode' => isset($_REQUEST['game_mode']) ? $_REQUEST['game_mode'] : 'normal',
		'rounds' => isset($_REQUEST['rounds']) ? $_REQUEST['rounds'] : array(),
		'players' => isset($_REQUEST['player']) ? $_REQUEST['player'] : array(),
	);

	if (!is_array($context['match']['players']))
		$context['match']['players'] = array($context['match']['players']);

	$players = array();
	foreach ($context['match']['players'] as $member)
		if (is_numeric($member) && $member != $user_info['id'])
			$players[] = (int) $member;

	// Check that members are allowed to play in arcade
	$players = memberAllowedTo(array('arcade_join_match', 'arcade_join_invite_match'), array_unique($players));

	$context['players'] = array(
		array(
			'id' => $user_info['id'],
			'name' => $user_info['name'],
		),
	);

	// Load info for players if needed
	if (!empty($players))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_member AS id_member, member_name, real_name
			FROM {db_prefix}members
			WHERE id_member IN ({array_int:members})',
			array(
				'members' => $players,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['players'][] = array(
				'id' => $row['id_member'],
				'username' => $row['member_name'],
				'name' => $row['real_name'],
			);
		$smcFunc['db_free_result']($request);
	}

	$last_player = end($context['players']);
	$context['last_player_id'] = $last_player['id'];

	// At least 2 players are needed
	if (empty($context['match']['players']) && $context['match']['num_players'] < 2)
		$context['match']['num_players'] = 2;

	$context['games'] = array();

	if (!empty($context['match']['rounds']))
	{
		// Check that all are numbers
		foreach ($context['match']['rounds'] as $i => $round)
			if (!is_numeric($round))
				unset($context['match']['rounds'][$i]);

		$request = $smcFunc['db_query']('', '
			SELECT id_game, game_name
			FROM {db_prefix}arcade_games AS game
				LEFT JOIN {db_prefix}arcade_categories AS category ON (category.id_cat = game.id_cat)
			WHERE id_game IN({array_int:games})
				AND {query_arena_game}',
			array(
				'games' => array_unique($context['match']['rounds']),
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['games'][$row['id_game']] = array(
				'id' => $row['id_game'],
				'name' => $row['game_name'],
			);
		$smcFunc['db_free_result']($request);
	}

	if (!empty($errors))
	{
		$context['errors'] = array();

		foreach ($errors as $err)
			$context['errors'][] = $txt['arena_error_' . $err];
	}

	checkSubmitOnce('register');

	// Layout
	loadTemplate('ArcadeArena');
	$context['sub_template'] = 'arcade_arena_new_match';
	$context['page_title'] = $txt['arcade_arena_new_match_title'];
	
	// Add Arena to link tree
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=arcade;sa=arena',
		'name' => $txt['arcade_arena'],
	);
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=arcade;sa=newMatch',
		'name' => $txt['arcade_newMatch'],
	);
}

function ArcadeNewMatch2()
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc, $user_info, $sourcedir;

	require_once($sourcedir . '/Subs-Members.php');
	require_once($sourcedir . '/Subs-Auth.php');

	$match = array();
	$showConfirm = false;
	$errors = array();

	if (empty($_REQUEST['match_name']) || trim($_REQUEST['match_name']) == '')
		$errors[] = 'no_name';
	elseif ($smcFunc['strlen']($_REQUEST['match_name']) > 20)
		$errors[] = 'name_too_long';

	if (!empty($_REQUEST['match_name']))
		$match['name'] = $_REQUEST['match_name'];

	if (empty($_REQUEST['game_mode']) || !in_array($_REQUEST['game_mode'], array('normal', 'knockout')))
		$errors[] = 'invalid_game_mode';
	else
		$match['game_mode'] = $_REQUEST['game_mode'];

	$match['private'] = isset($_REQUEST['private']);
	$match['num_players'] = empty($_REQUEST['num_players']) ? 0 : $_REQUEST['num_players'];

	// Check rounds
	$match['rounds'] = array();
	$context['games'] = array();

	if (!empty($_REQUEST['rounds']))
	{
		// Check that all are numbers
		foreach ($_REQUEST['rounds'] as $id => $round)
			if ($round != '::GAME_ID::' && (!isset($_REQUEST['delete_round']) || $_REQUEST['delete_round'] != $id))
				$match['rounds'][] = (int) $round;
	}

	// Game from suggester text field?
	if (!empty($_REQUEST['arenagame_input']))
	{
		$showConfirm = true;

		$_REQUEST['arenagame_input'] = strtr($_REQUEST['arenagame_input'], array('\\"' => '"'));

		preg_match_all('~"([^"]+)"~', $_REQUEST['arenagame_input'], $matches);
		$games = array_unique(array_merge($matches[1], explode(',', preg_replace('~"([^"]+)"~', '', $_REQUEST['arenagame_input']))));

		$request = $smcFunc['db_query']('', '
			SELECT game.id_game
			FROM {db_prefix}arcade_games AS game
				LEFT JOIN {db_prefix}arcade_categories AS category ON (category.id_cat = game.id_cat)
			WHERE game.game_name IN({array_string:games})
				AND {query_arena_game}',
			array(
				'games' => $games,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$match['rounds'][] = (int) $row['id_game'];

		unset($games, $matches);
	}

	if (!empty($match['rounds']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT game.id_game, game.game_name
			FROM {db_prefix}arcade_games AS game
				LEFT JOIN {db_prefix}arcade_categories AS category ON (category.id_cat = game.id_cat)
			WHERE id_game IN({array_int:games})
				AND {query_arena_game}',
			array(
				'games' => array_unique($match['rounds']),
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['games'][$row['id_game']] = array(
				'id' => $row['id_game'],
				'name' => $row['game_name'],
			);
		$smcFunc['db_free_result']($request);

		$valid = true;

		foreach ($match['rounds'] as $i => $r)
		{
			if (!isset($context['games'][$r]))
			{
				$valid = false;
				unset($match['rounds'][$i]);
			}
		}

		if (!$valid)
			$errors[] = 'invalid_rounds';
	}

	// Check players
	$match['players'] = array();

	// Players from add players field?
	if (!empty($_REQUEST['player']))
	{
		$showConfirm = true;

		$_REQUEST['player'] = strtr($_REQUEST['player'], array('\\"' => '"'));
		preg_match_all('~"([^"]+)"~', $_REQUEST['player'], $matches);

		$foundMembers = findMembers(array_unique(array_merge($matches[1], explode(',', preg_replace('~"([^"]+)"~', '', $_REQUEST['player'])))));

		foreach ($foundMembers as $member)
			$match['players'][] = $member['id'];
		unset($foundMembers, $matches);
	}

	// Previous / Players added via suggester
	if (!empty($_REQUEST['players_list']))
	{
		foreach ($_REQUEST['players_list'] as $id)
			if (!isset($_REQUEST['delete_player']) || $_REQUEST['delete_player'] != $id)
				$match['players'][] = (int) $id;
	}

	// Remove duplicates
	$match['players'] = array_unique($match['players']);
	$totalp = count($match['players']);

	// Check that selected players are allowed to play
	$match['players'] = memberAllowedTo(array('arcade_join_match', 'arcade_join_invite_match'), $match['players']);

	// Check number of players
	if ($match['num_players'] < $totalp || $match['num_players'] < 2)
		$errors[] = 'not_enough_players';

	if (count($match['players']) != $totalp)
		$errors[] = 'invalid_members';

	if (count($match['rounds']) === 0)
		$errors[] = 'no_rounds';

	if (!checkSubmitOnce('check', false))
		$errors[] = 'submit_twice';

	$showConfirm = $showConfirm || isset($_REQUEST['delete_round']) || isset($_REQUEST['delete_player']) || isset($_REQUEST['player_submit']) || isset($_REQUEST['arenagame_submit']);

	if ($showConfirm || !empty($errors))
		return ArcadeNewMatch($match, $showConfirm ? array() : $errors);

	$matchOptions = array(
		'name' => $smcFunc['htmlspecialchars']($match['name'], ENT_QUOTES),
		'starter' => $user_info['id'],
		'num_players' => $match['num_players'],
		'games' => $match['rounds'],
		'num_rounds' => count($match['rounds']),
		'players' => $match['players'],
		'extra' => array(
			'mode' => $match['game_mode'],
		),
	);

	$id_match = createMatch($matchOptions);

	redirectexit('action=arcade;sa=viewMatch;match=' . $id_match);
}

?>