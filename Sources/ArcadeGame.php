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
	void ArcadePlay()
		- ...

	void ArcadeSubmit()
		- ...

	void ArcadeComment()
		- ...

	void ArcadeHighscore()
		- ...

	void ArcadeSave_Guest()
		- ...
*/

function ArcadePlay()
{
	global $sourcedir, $scripturl, $txt, $db_prefix, $context, $smcFunc, $user_info;

 	if (!$context['arcade']['can_play'])
 		fatal_lang_error('cannot_arcade_play', false);

	if (empty($_REQUEST['game']) && !isset($_REQUEST['random']) && empty($_REQUEST['match']))
		fatal_lang_error('arcade_game_not_found', false);

	$extra = array();

	if (isset($_REQUEST['match']))
	{
		$id_match = (int) $_REQUEST['match'];
		$extra['match'] = $id_match;
		$extra['max_try'] = 1;

		$request = $smcFunc['db_query']('', '
			SELECT m.id_match, m.name, m.current_players, m.num_players, m.current_round, m.num_rounds, m.status
			FROM {db_prefix}arcade_matches AS m
			WHERE m.id_match = {int:match}
				AND status = 1',
			array(
				'match' => $id_match,
			)
		);
		$matchInfo = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$matchInfo)
			fatal_lang_error('arcade_game_not_found', false);

		$request = $smcFunc['db_query']('', '
			SELECT id_game
			FROM {db_prefix}arcade_matches_rounds
			WHERE id_match = {int:match}
				AND round = {int:round}',
			array(
				'match' => $id_match,
				'round' => $matchInfo['current_round'],
			)
		);
		$round = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$round || empty($round['id_game']))
			fatal_lang_error('arcade_game_not_found');

		$request = $smcFunc['db_query']('', '
			SELECT status
			FROM {db_prefix}arcade_matches_players
			WHERE id_match = {int:match}
				AND id_member = {int:member}',
			array(
				'match' => $id_match,
				'round' => $matchInfo['current_round'],
				'member' => $user_info['id'],
			)
		);
		$result = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$result)
			fatal_lang_error('arcade_game_not_found', false);

		if ($result['status'] == 1)
		{
			matchUpdatePlayers($id_match, array($user_info['id']), 2);
		}
		elseif ($result['status'] == 2 || $result['status'] == 3)
		{
			matchUpdatePlayers($id_match, array($user_info['id']), 3);

			redirectexit('action=arcade;sa=viewMatch;match=' . $id_match);
		}

		$_REQUEST['game'] = $round['id_game'];
	}

	if (!$context['game'] = getGameInfo(isset($_REQUEST['random']) ? 'random' : $_REQUEST['game']))
		fatal_lang_error('arcade_game_not_found', false);

	//$system = SubmitSystemInfo($context['game']['submit_system']);
	//require_once($sourcedir . '/' . $system['file']);
	//$isCustomGame = $context['game']['submit_system'] == 'custom_game';
	
	if (!isset($_SESSION['arcade_play_' . $context['game']['id']]) || isset($_REQUEST['restart']))
		$_SESSION['arcade_play_' . $context['game']['id']] = array('data' => array());

	$context['game_class'] = new $context['game']['class']($context['game'], $_SESSION['arcade_play_' . $context['game']['id']]['data']);
	
	if (!$context['game_class'] instanceof Arcade_Game)
		fatal_lang_error('arcade_game_not_found', false);
		
	// Layout start
	loadTemplate('ArcadeGame');
	$context['template_layers'][] = 'arcade_game';
	$context['sub_template'] = 'arcade_game_play';
	$context['page_title'] = sprintf($txt['arcade_game_play'], $context['game']['name']);
		
	$context['game_class']->Prepare();
	
	$_SESSION['arcade_play_' . $context['game']['id']]['data'] = $context['game_class']->getSession();
	
	// Arcade javascript
	$context['html_headers'] .= '
	<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/arcade/game.js"></script>
	<script>
		var currentGame = new SMFArcade_game(' . $context['game']['id'] . ');
	</script>';
	
	
	return;

	/*if (!isset($_REQUEST['xml']) && !isset($_REQUEST['ajax']))
	{
		

		//$system['play']($context['game'], $_SESSION['arcade_play_' . $context['game']['id']]);

		//$_SESSION['arcade_play_extra_' . $context['game']['id']] = $extra;

		//$context['game']['html'] = $system['html'];

		

		$context['arcade']['play'] = true;

		$context['linktree'][] = array(
			'url' => $scripturl . '?action=arcade;sa=play;game=' . $context['game']['id'],
			'name' => $context['game']['name'],
		);

		return;
	}
	else
	{
		if (!isset($_SESSION['arcade_play_' . $context['game']['id']]))
			ArcadeXMLOutput(array('check' => '0'), null);

		$ok = $system['xml_play']($context['game'], $_SESSION['arcade_play_' . $context['game']['id']]);

		if (!$ok)
			ArcadeXMLOutput(array('check' => '0'), null);

		if ($ok === true)
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}arcade_games
				SET num_plays = num_plays + 1
				WHERE id_game = {int:game}',
				array(
					'game' => $context['game']['id'],
				)
			);

		ArcadeXMLOutput(array('check' => '1'), null);
	}*/

	fatal_error('Hacking attempt...');
}

function ArcadeSubmit()
{
	global $scripturl, $sourcedir, $modSettings, $txt, $db_prefix, $context, $smcFunc, $user_info;

	if (!$system = SubmitSystemInfo())
		fatal_lang_error('arcade_submit_error', false);

	require_once($sourcedir . '/' . $system['file']);

	if ($system['get_game'] !== false)
		$context['game'] = $system['get_game']();

	// Check that everyhing is ok
	if (!$context['game'] || !isset($_SESSION['arcade_play_' . $context['game']['id']]) || !isset($_SESSION['arcade_play_extra_' . $context['game']['id']]))
		fatal_lang_error('arcade_submit_error_session', false);

	if ($context['game']['score_type'] == 2 || $context['game']['submit_system'] != $system['system'])
	{
		log_error(sprintf($txt['arcade_submit_error_configure_log'], $context['game']['name'], $system['system']));
		fatal_lang_error('arcade_submit_error', false);
	}

	$session_info = $_SESSION['arcade_play_' . $context['game']['id']];
	$extra = $_SESSION['arcade_play_extra_' . $context['game']['id']];

	unset($_SESSION['arcade_play_' . $context['game']['id']], $_SESSION['arcade_play_extra_' . $context['game']['id']]);

	$submit_info = $system['info']($context['game'], $session_info);

	if (!$submit_info || isset($submit_info['error']))
		fatal_lang_error('arcade_submit_error', false);

	if (isset($extra['match']))
	{
		$id_match = (int) $extra['match'];

		$request = $smcFunc['db_query']('', '
			SELECT m.id_match, m.name, m.current_players, m.num_players, m.current_round, m.num_rounds, m.status
			FROM {db_prefix}arcade_matches AS m
			WHERE m.id_match = {int:match}
				AND status = 1',
			array(
				'match' => $id_match,
			)
		);
		$matchInfo = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$matchInfo)
			fatal_lang_error('arcade_game_not_found', false);

		$request = $smcFunc['db_query']('', '
			SELECT id_game
			FROM {db_prefix}arcade_matches_rounds
			WHERE id_match = {int:match}
				AND round = {int:round}',
			array(
				'match' => $id_match,
				'round' => $matchInfo['current_round'],
			)
		);
		$round = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$round)
			fatal_lang_error('arcade_game_not_found', false);

		$request = $smcFunc['db_query']('', '
			SELECT status
			FROM {db_prefix}arcade_matches_players
			WHERE id_match = {int:match}
				AND id_member = {int:member}',
			array(
				'match' => $id_match,
				'round' => $matchInfo['current_round'],
				'member' => $user_info['id'],
			)
		);
		$result = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$result)
			fatal_lang_error('arcade_game_not_found', false);

		if ($result['status'] == 2)
		{
			$smcFunc['db_insert']('insert',
				'{db_prefix}arcade_matches_results',
				array(
					'id_match' => 'int',
					'id_member' => 'int',
					'round' => 'int',
					'score' => 'float',
					'duration' => 'float',
					'end_time' => 'int',
					'score_status' => 'string-30',
					'validate_hash' => 'string-255',
				),
				array(
					$id_match,
					$user_info['id'],
					$matchInfo['current_round'],
					$submit_info['score'],
					$submit_info['duration'],
					$submit_info['end_time'],
					$submit_info['cheating'],
					!empty($submit_info['hash']) ? $submit_info['hash'] : 'v1' . sha1($submit_info['score']),
				),
				array('id_match', 'id_member', 'round')
			);

			matchUpdatePlayers($id_match, array($user_info['id']), 3);

			redirectexit('action=arcade;sa=viewMatch;match=' . $id_match);
		}
	}

	if ($user_info['is_guest'] && !$context['arcade']['can_submit'])
	{
		$_SESSION['arcade']['highscore'] = array(
			'id' => false,
			'game' => $context['game']['id'],
			'score' => $submit_info['score'],
			'position' => 0,
			'start' => 0,
			'saved' => false,
			'error' => 'arcade_no_permission'
		);

		redirectexit('action=arcade;sa=highscore;game=' . $context['game']['id']);
	}
	elseif ($user_info['is_guest'])
	{
		$member = array(
			'id' => $user_info['id'],
			'name' => isset($_SESSION['playerName']) ? $_SESSION['playerName'] : '',
			'ip' => $user_info['ip']
		);
	}
	else
	{
		$member = array(
			'id' => $user_info['id'],
			'name' => $user_info['name'],
			'ip' => $user_info['ip']
		);
	}

	$score = array(
		'score' => $submit_info['score'],
		'duration' => $submit_info['duration'],
		'endTime' => $submit_info['end_time'],
		'status' => $submit_info['cheating'],
		'hash' => !empty($submit_info['hash']) ? $submit_info['hash'] : '',
	);

	if ($context['arcade']['can_submit'] && empty($member['name']))
	{
		$_SESSION['save_score'] = array($context['game'], $member, $score);
		redirectexit('action=arcade;sa=save;game=' . $context['game']['id']);
	}
	elseif ($context['arcade']['can_submit'])
	{
		$save = SaveScore($context['game'], $member, $score);

		if ($save === false || $save['id'] === false)
			$_SESSION['arcade']['highscore'] = array(
				'id' => false,
				'game' => $context['game']['id'],
				'score' => $score['score'],
				'start' => 0,
				'saved' => false,
				'error' => isset($save['error']) ? $save['error'] : 'arcade_saving_error',
			);

		else
			$_SESSION['arcade']['highscore'] = array(
				'id' => $save['id'],
				'position' => $save['position'],
				'game' => $context['game']['id'],
				'newChampion' => $save['newChampion'],
				'personalBest' => $save['isPersonalBest'],
				'score' => $score['score'],
				'start' => floor(($save['position'] - 1) / $context['scores_per_page']) * $context['scores_per_page'],
				'saved' => true
			);
	}
	else
		$_SESSION['arcade']['highscore'] = array(
			'id' => false,
			'game' => $context['game']['id'],
			'score' => $submit_info['score'],
			'position' => 0,
			'start' => 0,
			'saved' => false,
			'error' => 'arcade_no_permission'
		);

	if (!isset($_REQUEST['xml']))
		redirectexit('action=arcade;sa=highscore;game=' . $context['game']['id'] . ';start=' . $_SESSION['arcade']['highscore']['start']);
}

function ArcadeSave_Guest()
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $func, $sourcedir, $smcFunc;

	if (!isset($_REQUEST['name']) && !isset($_SESSION['playerName']))
	{
		$context['arcade']['submit'] = 'askname';

		return ArcadeHighscore();
	}
	elseif (isset($_REQUEST['name']) || isset($_SESSION['playerName']))
	{
		$_REQUEST['game'] = $_SESSION['save_score'][0]['id'];

		if (isset($_REQUEST['name']))
		{
			require_once($sourcedir . '/Subs-Members.php');

			checkSession('post');

			$name = htmlspecialchars($_REQUEST['name']);

			if (isReservedName($name, 0, true, false))
			{
				$context['arcade']['submit'] = 'askname';
				$context['arcade']['error'] = 'bad_name';

				return ArcadeHighscore();
			}

			$_SESSION['playerName'] = $name;
			$_SESSION['save_score'][1]['name'] = $name;
		}

		SaveScore($_SESSION['save_score'][0], $_SESSION['save_score'][1], $_SESSION['save_score'][2]);

		unset($_SESSION['save_score']);
		redirectexit('action=arcade;sa=highscore;game=' . $_REQUEST['game']);
	}
}

function ArcadeHighscore()
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $smcFunc, $user_info, $sourcedir;

	// Is game set
	if (!isset($_REQUEST['game']))
		fatal_lang_error('arcade_game_not_found', false);
	// Get game info
	if (!($game = getGameInfo($_REQUEST['game'])))
		fatal_lang_error('arcade_game_not_found', false);
	if (!$game['highscore_support'])
		fatal_lang_error('arcade_game_not_found', false);

	$newScore = false;

	// Did we just play
	if (isset($_SESSION['arcade']['highscore']['game']) && $_SESSION['arcade']['highscore']['game'] == $game['id'])
	{
		// For highlight
		$newScore = $_SESSION['arcade']['highscore']['saved'];
		$newScore_id = $_SESSION['arcade']['highscore']['id'];

		$context['arcade']['submit'] = 'newscore';

		$score = &$_SESSION['arcade']['highscore'];

		$context['arcade']['new_score'] = array(
			'id' => $score['id'],
			'saved' => $score['saved'],
			'error' => !empty($score['error']) ? $score['error'] : '',
			'score' => comma_format((float) $score['score']),
			'position' => isset($score['position']) ? $score['position'] : 0,
			'can_comment' => $context['arcade']['can_comment_own'] || $context['arcade']['can_comment_any'],
			'is_new_champion' => !empty($score['newChampion']),
			'is_personal_best' => !empty($score['personalBest']),
		);

		if (!isset($_GET['start']))
			$_REQUEST['start'] = $score['start'];
	}
	elseif (isset($_SESSION['arcade']['highscore']))
		unset($_SESSION['arcade']['highscore']);

	// Edit Comment
	if (isset($_REQUEST['csave']))
	{
		if (isset($_SESSION['arcade']['highscore']))
			unset($_SESSION['arcade']['highscore']);

		require_once($sourcedir . '/Subs-Post.php');

		$where = $context['arcade']['can_comment_any'] ? '1 = 1' : ($context['arcade']['can_comment_own'] ? 'id_member = {int:member}' : '0 = 1');

		$_REQUEST['new_comment'] = strtr($smcFunc['htmlspecialchars']($_REQUEST['new_comment'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => ''));

		preparsecode($_REQUEST['new_comment']);

		if (!empty($modSettings['arcadeCommentLen']))
			$_REQUEST['new_comment'] = substr($_REQUEST['new_comment'], 0, $modSettings['arcadeCommentLen']);

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}arcade_scores
			SET comment = {string:comment}
			WHERE id_score = {int:score}
				AND ' . $where,
			array(
				'score' => (int) $_REQUEST['score'],
				'comment' => $_REQUEST['new_comment'],
				'member' => $user_info['id'],
			)
		);

		$_SESSION['arcade']['highscore']['saved'] = true;

		if (isset($_REQUEST['xml']))
		{
			ArcadeXMLOutput(
				array(
					'comment' => parse_bbc($_REQUEST['new_comment']),
					'message' => $txt['arcade_comment_saved']
				),
				null
			);
		}

		redirectexit('action=arcade;sa=highscore;game=' . $game['id']);
	}
	// Quick Management
	elseif ($context['arcade']['can_admin_arcade'] && isset($_REQUEST['qaction']))
	{
		checkSession('request');

		if ($_REQUEST['qaction'] == 'delete' && !empty($_REQUEST['scores']))
			deleteScores($game, $_REQUEST['scores']);

		redirectexit('action=arcade;sa=highscore;game=' . $game['id']);
	}

	// How many scores there are
	$result = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}arcade_scores
		WHERE id_game = {int:game}',
		array(
			'game' => $game['id'],
		)
	);
	list ($scoreCount) = $smcFunc['db_fetch_row']($result);
	$smcFunc['db_free_result']($result);

	$context['page_index'] = constructPageIndex($scripturl .'?action=arcade;sa=highscore;game=' . $game['id'], $_REQUEST['start'], $scoreCount, $context['scores_per_page'], false);

	// Actual query
	$result = $smcFunc['db_query']('', '
		SELECT
			sc.id_score, sc.score, sc.end_time AS time, sc.duration, sc.comment,
			sc.position, sc.score_status, IFNULL(mem.id_member, 0) AS id_member,
			IFNULL(mem.real_name, sc.player_name) AS real_name
		FROM  {db_prefix}arcade_scores AS sc
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = sc.id_member)
		WHERE id_game = {int:game}
		ORDER BY position
		LIMIT {int:start}, {int:scores_per_page}',
		array(
			'game' => $game['id'],
			'empty' => '',
			'start' => $_REQUEST['start'],
			'scores_per_page' => $context['scores_per_page'],
		)
	);

	$context['arcade']['scores'] = array();
	$context['game']	= $game;

	while ($score = $smcFunc['db_fetch_assoc']($result))
	{
		censorText($score['comment']);

		if (empty($score['real_name']))
			$score['real_name'] = $txt['guest'];

		$context['arcade']['scores'][$score['id_score']] = array(
			'id' => $score['id_score'],
			'own' => $user_info['id'] == $score['id_member'],
			'member' => array(
				'id' => $score['id_member'],
				'name' => $score['real_name'],
				'link' => !empty($score['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $score['id_member'] . '">' . $score['real_name'] . '</a>' : $score['real_name'],
			),
			'position' => $score['position'],
			'score' => comma_format((float) $score['score']),
			'time' => timeformat($score['time']),
			'duration' => $score['duration'],
			'scoreStatus' => $score['score_status'],
			'comment' => parse_bbc(!empty($score['comment']) ? $score['comment'] : $txt['arcade_no_comment']),
			'raw_comment' => $score['comment'],
			'can_edit' => $user_info['id'] == $score['id_member'] ? ($context['arcade']['can_comment_own'] || $context['arcade']['can_comment_any']) : $context['arcade']['can_comment_any'],
		);
	}
	$smcFunc['db_free_result']($result);

	if (isset($_REQUEST['edit']))
	{
		if ($context['arcade']['scores'][(int) $_REQUEST['score']]['can_edit'])
			$context['arcade']['scores'][(int) $_REQUEST['score']]['edit'] = true;
	}

	if ($newScore)
		$context['arcade']['scores'][$newScore_id]['highlight'] = true;

	// Template
	loadTemplate('ArcadeGame');
	$context['template_layers'][] = 'arcade_game';
	$context['sub_template'] = 'arcade_game_highscore';
	$context['page_title'] = sprintf($txt['arcade_view_highscore'], $game['name']);

	$context['linktree'][] = array(
		'url' => $scripturl . '?action=arcade;sa=play;game=' . $game['id'],
		'name' => $game['name'],
	);
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=arcade;sa=highscore;game=' . $game['id'],
		'name' => $txt['arcade_viewscore'],
	);

	// Do we show remove score functions?
	$context['arcade']['show_editor'] = $context['arcade']['can_admin_arcade'];

	return true;
}

?>