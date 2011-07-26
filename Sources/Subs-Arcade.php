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

/*	This file contains most functions used by Arcade

	void arcadePermissionQuery()
		- ???

	void PostPermissionCheck()
		- ???

	array loadArcadeSettings()
		- ???

	string getSubmitSystem()
		- ???

	array submitSystemInfo()
		- ???

	int loadGame()
		- ???

	array getGameInfo()
		- ???

	array getGameOfDay()
		- ???

	array newGameOfDay()
		- ???

	array getRecommendedGames()
		- ???

	boolean updateGame()
		- ???

	array arcadeGetEventTypes()
		- ???

	boolean arcadeEvent()
		- ???

	void arcadeEventNewChampion()
		- ???

	void arcadeEventArenaInvite()
		- ???

	boolean checkNotificationReceiver()
		- ???

	array checkNotificationReceivers()
		- ???

	void addNotificationRecievers()
		- ???

	array ArcadeLatestScores()
		- ???

	array SaveScore()
		- ???

	boolean deleteScores()
		- ???

	array loadMatch()
		- ???

	int createMatch()
		- ???

	boolean matchAddPlayers()
		- ???

	boolean deleteMatch()
		- ???

	void ArcadeXMLOutput()
		- ???

	void Array2XML()
		- ???

	array memberAllowedTo()
		- ???

	float microtime_float()
		- ???

	string duration_format()
		- ???
*/

function arcade_get_url($params = array())
{
	global $scripturl, $modSettings;

	// Running in "standalone" mode WITH rewrite
	if (!empty($modSettings['arcadeStandalone']) && $modSettings['arcadeStandalone'] == 2)
	{
		// Main Page? Too easy
		if (empty($params))
			return $modSettings['arcadeStandaloneUrl'] . '/';

		$query = '';

		foreach ($params as $p => $value)
		{
			if ($value === null)
				continue;

			if (!empty($query))
				$query .= ';';
			else
				$query .= '?';

			if (is_int($p))
				$query .= $value;
			else
				$query .= $p . '=' . $value;
		}

		return $modSettings['arcadeStandaloneUrl'] . '/' . $query;
	}
	// Running in "standalone" mode without rewrite or standard mode
	else
	{
		$return = '';

		if (empty($params) && empty($modSettings['arcadeStandaloneUrl']))
			$params['action'] = 'arcade';

		foreach ($params as $p => $value)
		{
			if ($value === null)
				continue;

			if (!empty($return))
				$return .= ';';
			else
				$return .= '?';

			if (is_int($p))
				$return .= $value;
			else
				$return .= $p . '=' . $value;
		}

		if (!empty($modSettings['arcadeStandaloneUrl']))
			return $modSettings['arcadeStandaloneUrl'] . $return;
		else
			return $scripturl . $return;
	}
}

function arcadePermissionQuery()
{
	global $scripturl, $modSettings, $context, $user_info;

	// No need to check for admins
	if (allowedTo('arcade_admin'))
	{
		$see_game = '1=1';
		$see_category = '1=1';
	}
	// Build permission query
	else
	{
		if (!isset($modSettings['arcadePermissionMode']))
			$modSettings['arcadePermissionMode'] = 1;

		if ($modSettings['arcadePermissionMode'] >= 2)
		{
			// Can see game?
			if ($user_info['is_guest'])
				$see_game = '(game.local_permissions = 0 OR FIND_IN_SET(-1, game.member_groups))';
			// Registered user.... just the groups in $user_info['groups'].
			else
				$see_game = '(game.local_permissions = 0 OR (FIND_IN_SET(' . implode(', game.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', game.member_groups)))';
		}

		if ($modSettings['arcadePermissionMode'] == 1 || $modSettings['arcadePermissionMode'] >= 3)
		{
			// Can see category?
			if ($user_info['is_guest'])
				$see_category = 'FIND_IN_SET(-1, category.member_groups)';
			// Registered user.... just the groups in $user_info['groups'].
			else
				$see_category = '(FIND_IN_SET(' . implode(', category.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', category.member_groups) OR ISNULL(category.member_groups))';
		}
	}

	$arena_category = '(FIND_IN_SET(-2, category.member_groups) OR ISNULL(category.member_groups))';
	$arena_game = '(game.local_permissions = 0 OR FIND_IN_SET(-2, game.member_groups))';

	// Build final query
	// No game/category permissions used
	if (empty($modSettings['arcadePermissionMode']))
	{
		$user_info['query_see_game'] = 'enabled = 1';
		$user_info['query_arena_game'] = 'enabled = 1';
	}
	// Only category used
	elseif ($modSettings['arcadePermissionMode'] == 1)
	{
		$user_info['query_see_game'] = "(enabled = 1 AND $see_category)";
		$user_info['query_arena_game'] = "(enabled = 1 AND $arena_category)";
	}
	// Only category used
	elseif ($modSettings['arcadePermissionMode'] == 2)
	{
		$user_info['query_see_game'] = "(enabled = 1 AND $see_game)";
		$user_info['query_arena_game'] = "(enabled = 1 AND $arena_game)";
	}
	// Required to have permssion to game and category
	elseif ($modSettings['arcadePermissionMode'] == 3)
	{
		$user_info['query_see_game'] = "(enabled = 1 AND ($see_category AND $see_game))";
		$user_info['query_arena_game'] = "(enabled = 1 AND ($arena_category AND $arena_game))";
	}
	// Required to have permssion to game OR category
	elseif ($modSettings['arcadePermissionMode'] == 4)
	{
		$user_info['query_see_game'] = "(enabled = 1 AND ($see_category OR $see_game))";
		$user_info['query_arena_game'] = "(enabled = 1 AND ($arena_category OR $arena_game))";
	}

	$user_info['query_see_match'] = "(private_game = 0 OR me.id_member = $user_info[id])";
}

function PostPermissionCheck()
{
	global $txt, $modSettings, $context, $user_info, $user_profile, $smcFunc;

	// Is Post permissions enabled or is user all-migty admin?
	if ((allowedTo('arcade_admin') && empty($_REQUEST['pcheck'])) || empty($modSettings['arcadePostPermission']) || !$context['arcade']['can_play'])
		return;

	// Guests cannot ever pass
	elseif ($user_info['is_guest'])
	{
		$context['arcade']['can_play'] = false;
		$context['arcade']['notice'] = $txt['arcade_notice_post_requirement'];

		return;
	}

	// We don't want to load this data on every page load
	if (isset($_SESSION['arcade_posts']) && time() - $_SESSION['arcade_posts']['time'] < 360 && empty($_REQUEST['pcheck']))
		$context['arcade']['posts'] = &$_SESSION['arcade_posts'];

	// But now we have to...
	else
	{
		loadMemberData($user_info['id'], false, 'minimal');

		$days = ceil(time() - $user_profile[$user_info['id']]['date_registered'] / 86400);

		// At should be always at least one day
		if ($days < 1)
			$days = 1;

		$context['arcade']['posts'] = array(
			'cumulative' => $user_profile[$user_info['id']]['posts'],
			'average' => $user_profile[$user_info['id']]['posts'] / $days,
			'last_day' => 0,
			'time' => time(),
		);

		if (!empty($modSettings['arcadePostsPlayPerDay']))
		{
			$result = $smcFunc['db_query']('', '
				SELECT COUNT(*)
				FROM {db_prefix}messages AS m
					LEFT JOIN {db_prefix}boards AS b ON (m.id_board = b.id_board)
				WHERE b.count_posts != 1
					AND m.id_member = {int:member}
					AND m.poster_time >= {int:from}',
				array(
					'member' => $user_info['id'],
					'from' => time() - 86400
				)
			);

			list ($context['arcade']['posts']['last_day']) = $smcFunc['db_fetch_row']($result);
			$smcFunc['db_free_result']($result);
		}
		else
		{
			$context['arcade']['posts']['last_day'] = 0;
		}

		$_SESSION['arcade_posts'] = $context['arcade']['posts'];
	}

	$cumulativePosts = true;
	$averagePosts = true;
	$postsLastDay = true;

	// Enough post to play?
	if (!empty($modSettings['arcadePostsPlay']))
		$cumulativePosts = $context['arcade']['posts']['cumulative'] >= $modSettings['arcadePostsPlay'];

	// Enough average posts to play?
	if (!empty($modSettings['arcadePostsPlayAverage']))
		$averagePosts = $context['arcade']['posts']['average'] >= $modSettings['arcadePostsPlayAverage'];

	// Enough post today to play?
	if (!empty($modSettings['arcadePostsPlayPerDay']))
		$postsLastDay = $context['arcade']['posts']['last_day'] >= $modSettings['arcadePostsLastDay'];

	// Result is
	$context['arcade']['can_play'] = $cumulativePosts && $averagePosts && $postsLastDay;

	// Should we display notice?
	if (!$cumulativePosts || !$averagePosts || !$postsLastDay)
		$context['arcade']['notice'] = $txt['arcade_notice_post_requirement'];
}

function loadArcadeSettings($memID = 0)
{
	global $smcFunc, $user_info;

	if ($memID == 0 && $user_info['is_guest'])
		return array();

	// Default
	$arcadeSettings = array();

	$result = $smcFunc['db_query']('', '
		SELECT variable, value
		FROM {db_prefix}arcade_settings
		WHERE id_member = {int:member}',
		array(
			'member' => $memID == 0 ? $user_info['id'] : $memID,
		)
	);

	while ($var = $smcFunc['db_fetch_assoc']($result))
		$arcadeSettings[$var['variable']] = $var['value'];
	$smcFunc['db_free_result']($result);

	return $arcadeSettings;
}

function getSubmitSystem()
{
	global $context;

	$ibp = isset($_REQUEST['autocom']) && $_REQUEST['autocom'] == 'arcade';

	if (!empty($context['playing_custom']))
		return 'custom_game';
	elseif (isset($_POST['mochi']))
		return 'mochi';
	elseif (isset($_REQUEST['act']) && strtolower($_REQUEST['act']) == 'arcade')
		return 'ibp';
	elseif ($ibp && !isset($_REQUEST['arcadegid']))
		return 'ibp3';
	elseif ($ibp && isset($_REQUEST['arcadegid']))
		return 'ibp32';
	/*elseif ($ibp && isset($_REQUEST['p']) && $_REQUEST['p'] == 'sngtour')
		return 'ibp_sng';
	elseif (false)
		return 'pnflash';*/
	elseif (isset($_POST['phpbb']) && isset($_POST['game_name']))
		return 'phpbb';
	elseif ((isset($_POST['v3arcade']) || $_REQUEST['sa'] == 'vbBurn') && (isset($_POST['game_name']) || isset($_POST['id'])))
		return 'v3arcade';
	elseif (isset($_REQUEST['sa']) && substr($_REQUEST['sa'], 0, 3) == 'v2S')
		return 'v2game';
	elseif (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'submit')
		return 'v1game';
	else
		return false;
}

function submitSystemInfo($system = '')
{
	global $arcadeFunc, $context, $sourcedir;

	if (empty($system))
		$system = getSubmitSystem();

	if ($system == false)
		$system = 'v1game';

	static $systems = array(
		'v2game' => array(
			'system' => 'v2game',
			'name' => 'SMF Arcade v2 (Actionscript 2)',
			'file' => 'Submit-v2game.php',
			'get_game' => 'ArcadeV2GetGame',
			'info' => 'ArcadeV2Submit',
			'play' => 'ArcadeV2Play',
			'xml_play' => 'ArcadeV2XMLPlay',
			'html' => 'ArcadeV2Html',
		),
		'v1game' => array(
			'system' => 'v1game',
			'name' => 'SMF Arcade v1',
			'file' => 'Submit-v1game.php',
			'get_game' => 'ArcadeV1GetGame',
			'info' => 'ArcadeV1Submit',
			'play' => 'ArcadeV1Play',
			'xml_play' => 'ArcadeV1XMLPlay',
			'html' => 'ArcadeV1Html',
		),
		'custom_game' => array(
			'system' => 'custom_game',
			'name' => 'Custom Game (PHP)',
			'file' => 'Submit-custom.php',
			'get_game' => false,
			'info' => 'ArcadeCustomSubmit',
			'play' => 'ArcadeCustomPlay',
			'xml_play' => 'ArcadeCustomXMLPlay',
			'html' => 'ArcadeCustomHtml',
		),
		'ibp' => array(
			'system' => 'ibp',
			'name' => 'IBP Arcade',
			'file' => 'Submit-ibp.php',
			'get_game' => 'ArcadeIBPGetGame',
			'info' => 'ArcadeIBPSubmit',
			'play' => 'ArcadeIBPPlay',
			'xml_play' => 'ArcadeIBPXMLPlay',
			'html' => 'ArcadeIBPHtml',
		),
		'ibp3' => array(
			'system' => 'ibp3',
			'name' => 'IBP Arcade v3',
			'file' => 'Submit-ibp.php',
			'get_game' => 'ArcadeIBP3GetGame',
			'info' => 'ArcadeIBP3Submit',
			'play' => 'ArcadeIBP3Play',
			'xml_play' => 'ArcadeIBP3XMLPlay',
			'html' => 'ArcadeIBPHtml',
		),
		'ibp32' => array(
			'system' => 'ibp32',
			'name' => 'IBP Arcade v3.2',
			'file' => 'Submit-ibp.php',
			'get_game' => 'ArcadeIBP32GetGame',
			'info' => 'ArcadeIBP32Submit',
			'play' => 'ArcadeIBP3Play',
			'xml_play' => 'ArcadeIBP3XMLPlay',
			'html' => 'ArcadeIBPHtml',
		),
		'v3arcade' => array(
			'system' => 'v3arcade',
			'name' => 'v3Arcade',
			'file' => 'Submit-v3arcade.php',
			'get_game' => 'ArcadevbGetGame',
			'info' => 'ArcadeVbSubmit',
			'play' => 'ArcadeVbPlay',
			'xml_play' => 'ArcadeVbXMLPlay',
			'html' => 'ArcadeVbHtml',
		),
		'phpbb' => array(
			'system' => 'phpbb',
			'name' => 'PhpBB (activity mod)',
			'file' => 'Submit-phpbb.php',
			'get_game' => 'ArcadePHPBBGetGame',
			'info' => 'ArcadePHPBBSubmit',
			'play' => 'ArcadePHPBBPlay',
			'xml_play' => 'ArcadePHPBBXMLPlay',
			'html' => 'ArcadePHPBBHtml',
		),
		'mochi' => array(
			'system' => 'mochi',
			'name' => 'MochiAds (requires external module)',
			'file' => 'Submit-mochi.php',
			'get_game' => 'ArcadeMochiGetGame',
			'get_settings' => 'ArcadeMochiGetSettings',
			'info' => 'ArcadeMochiSubmit',
			'play' => 'ArcadeMochiPlay',
			'xml_play' => 'ArcadeMochiXMLPlay',
			'html' => 'ArcadeMochiHtml',
		),
	);
	static $submit_system_check_done = false;
	
	// Remove non-installed systems
	if (!$submit_system_check_done)
	{
		foreach ($systems as $id => $temp)
		{
			if (!file_exists($sourcedir . '/' . $temp['file']))
				unset($systems[$id]);
		}
		
		$submit_system_check_done = true;
	}
	
	if ($system == '*')
		return $systems;
	elseif (isset($systems[$system]))
		return $systems[$system];
	else
		return false;
}

function CheatingCheck()
{
	global $scripturl, $modSettings;

	$error = '';

	// Default check level is 1
	if (!isset($modSettings['arcadeCheckLevel']))
		$modSettings['arcadeCheckLevel'] = 1;

	if (!empty($_SERVER['HTTP_REFERER']))
		$referer = parse_url($_SERVER['HTTP_REFERER']);

	$real = parse_url($scripturl);

	// Level 1 Check
	// Checks also HTTP_REFERER if it not is empty
	if ($modSettings['arcadeCheckLevel'] == 1)
	{
		if (isset($referer) && ($real['host'] != $referer['host'] || $real['scheme'] != $referer['scheme']))
			$error = 'invalid_referer';
	}
	// Level 2 Check
	// Doesn't allow HTTP_REFERER to be empty
	elseif ($modSettings['arcadeCheckLevel'] == 2)
	{
		if (!isset($referer) || (isset($referer) && ($real['host'] != $referer['host'] || $real['scheme'] != $referer['scheme'])))
			$error = 'invalid_referer';

	}
	// Level 0 check
	else
		$error = '';

	return $error;
}

function loadGame($id_game, $from_admin = false)
{
	global $scripturl, $txt, $db_prefix, $user_info, $smcFunc, $modSettings, $context;

	if (is_numeric($id_game) && isset($context['arcade']['game_data'][$id_game]))
		return $id_game;
	elseif (isset($context['arcade']['game_ids'][$id_game]))
		return $context['arcade']['game_ids'][$id_game];

	if ($from_admin)
		$where = "game.id_game = {int:game}";
	elseif (is_numeric($id_game))
		$where = "{query_see_game}
			AND game.id_game = {int:game}";
	elseif ($id_game == 'random')
		$where = "{query_see_game}
		ORDER BY RAND()";
	else
		$where = "{query_see_game}
			AND game.internal_name = {string:game}";

	$result = $smcFunc['db_query']('', '
		SELECT game.id_game, game.game_name, game.description, game.game_rating, game.num_plays,
			game.game_file, game.game_directory, game.submit_system, game.internal_name,
			game.score_type, game.thumbnail, game.thumbnail_small,
			game.help, game.enabled, game.member_groups, game.extra_data,
			IFNULL(score.id_score,0) AS id_score, IFNULL(score.score, 0) AS champ_score,
			IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, score.player_name) AS real_name,
			IFNULL(score.end_time, 0) AS champion_time, IFNULL(favorite.id_favorite, 0) AS is_favorite,
			IFNULL(category.id_cat, 0) AS id_cat, IFNULL(category.cat_name, {string:string_empty}) As cat_name,
			IFNULL(pb.id_score, 0) AS id_pb, IFNULL(pb.score, 0) AS personal_best, num_favorites
		FROM {db_prefix}arcade_games AS game
			LEFT JOIN {db_prefix}arcade_scores AS score ON (score.id_score = game.id_champion_score)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = game.id_champion)
			LEFT JOIN {db_prefix}arcade_favorite AS favorite ON (favorite.id_game = game.id_game AND favorite.id_member = {int:member})
			LEFT JOIN {db_prefix}arcade_scores AS pb ON (pb.id_game = game.id_game AND pb.id_member = {int:member} AND pb.personal_best = 1)
			LEFT JOIN {db_prefix}arcade_categories AS category ON (category.id_cat = game.id_cat)
		WHERE ' . $where . '
		LIMIT 1',
		array(
			'game' => $id_game,
			'string_empty' => '',
			'member' => $user_info['id'],
		)
	);

	// No game was found
	if ($smcFunc['db_num_rows']($result) == 0)
		return false;

	$game = $smcFunc['db_fetch_assoc']($result);
	$smcFunc['db_free_result']($result);

	$context['arcade']['game_data'][$game['id_game']] = $game;
	$context['arcade']['game_ids'][$game['internal_name']] = $game['id_game'];

	return $game['id_game'];
}

function getGameInfo($id_game = 0, $raw = false)
{
	global $scripturl, $txt, $db_prefix, $user_info, $smcFunc, $modSettings, $context;

	$id_game = loadGame($id_game);

	if ($id_game === false)
		return false;

	if ($raw)
		return $context['arcade']['game_data'][$id_game];

	$game = &$context['arcade']['game_data'][$id_game];

	// Is game installed in subdirectory
	if ($game['game_directory'] != '')
		$gameurl = $modSettings['gamesUrl'] . '/' . $game['game_directory'] . '/';
	// It is in main directory
	else
		$gameurl = $modSettings['gamesUrl'] . '/';

	$description = parse_bbc($game['description']);
	$help = parse_bbc($game['help']);

	if (!empty($game['real_name']))
	{
		$player_name = $game['real_name'];
		$guest = empty($game['id_member']);
	}
	else
	{
		$player_name = $txt['guest'];
		$guest = true;
	}

	return array(
		'id' => $game['id_game'],
		'url' => array(
			'play' => $scripturl . '?action=arcade;sa=play;game=' . $game['id_game'],
			'base_url' => $gameurl,
			'highscore' => $scripturl . '?action=arcade;sa=highscore;game=' . $game['id_game'],
			'flash' => $gameurl . $game['game_file'],
			'favorite' => $context['arcade']['can_favorite'] ? $game['is_favorite'] == 0 ? $scripturl . '?action=arcade;sa=favorite;game=' . $game['id_game'] : $scripturl . '?action=arcade;sa=favorite;remove;game=' . $game['id_game'] : '#',
		),
		'extra_data' => !empty($game['extra_data']) ? unserialize($game['extra_data']) : array(),
		'category' => array(
			'id' => $game['id_cat'],
			'name' => $game['cat_name'],
			'link' => $scripturl . '?action=arcade;category=' . $game['id_cat'],
		),
		'submit_system' => $game['submit_system'],
		'internal_name' => $game['internal_name'],
		'name' => $game['game_name'],
		'directory' => $game['game_directory'],
		'file' => $game['game_file'],
		'description' => $description,
		'help' =>  $help,
		'rating' => $game['game_rating'],
		'rating2' => round($game['game_rating']),
		'thumbnail' => !empty($game['thumbnail']) ? $gameurl . $game['thumbnail'] : '',
		'thumbnail_small' => !empty($game['thumbnail_small']) ? $gameurl . $game['thumbnail_small'] : '',
		'is_champion' => $game['id_score'] > 0,
		'champion' => array(
			'id' => $game['id_member'],
			'name' => $player_name,
			'score_id' => $game['id_score'],
			'link' =>  !$guest ? '<a href="' . $scripturl . '?action=profile;u=' . $game['id_member'] . '">' . $player_name . '</a>' : $player_name,
			'score' => comma_format((float) $game['champ_score']),
			'time' => $game['champion_time'],
		),
		'is_personal_best' => !$user_info['is_guest'] && $game['id_pb'] > 0,
		'personal_best' => !$user_info['is_guest'] ? comma_format((float) $game['personal_best']) : 0,
		'personal_best_score' => !$user_info['is_guest'] ? $game['personal_best'] : 0,
		'score_type' => $game['score_type'],
		'highscore_support' => $game['score_type'] != 2,
		'is_favorite' => $context['arcade']['can_favorite'] ? $game['is_favorite'] > 0 : false,
		'favorite' => $game['num_favorites'],
		'member_groups' => isset($game['member_groups']) ? explode(',', $game['member_groups']) : array(),
	);
}

// Return game of day
function getGameOfDay()
{
	global $db_prefix, $modSettings;

	// Return 'Game of day'

	if (!isset($modSettings['game_of_day']) || !is_numeric($modSettings['game_of_day']) || !isset($modSettings['game_time']) || $modSettings['game_time'] != date('ymd'))
		return newGameOfDay();

	if (!($game = cache_get_data('game_of_day', 360)))
	{
		if (!($game = GetGameInfo($modSettings['game_of_day'])))
			return newGameOfDay();

		cache_put_data('game_of_day', $game, 360);
	}

	return $game;
}

// Generates new game of day
function newGameOfDay()
{
	global $db_prefix, $modSettings;

	$game = getGameInfo('random');

	if (!$game)
		return false;

	updateSettings(array(
		'game_time' => date('ymd'),
		'game_of_day' => $game['id']
	));

	cache_put_data('game_of_day', $game, 360);

	return $game;
}

function getRecommendedGames($id_game)
{
	global $db_prefix, $user_info, $smcFunc;

	if (!is_array($id_game))
		$id_game = array($id_game);

	$request = $smcFunc['db_query']('', '
		SELECT sc.id_member, COUNT(*) as plays
		FROM {db_prefix}arcade_scores AS sc
		WHERE sc.id_game IN({array_int:games})
		GROUP BY sc.id_member
		ORDER BY plays DESC
		LIMIT 50',
		array(
			'games' => $id_game,
		)
	);

	$players = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$players[] = $row['id_member'];
	$smcFunc['db_free_result']($request);

	if (empty($players))
		return false;

	$request = $smcFunc['db_query']('', '
		SELECT sc.id_game, COUNT(*) AS plays
		FROM {db_prefix}arcade_scores AS sc
			LEFT JOIN {db_prefix}arcade_games AS game ON (game.id_game = sc.id_game)
			LEFT JOIN {db_prefix}arcade_categories AS category ON (category.id_cat = game.id_cat)
		WHERE {query_see_game}
			AND sc.id_member IN({array_int:players})
			AND game.id_game NOT IN({array_int:games})
		GROUP BY sc.id_game
		ORDER BY plays DESC
		LIMIT 3',
		array(
			'players' => $players,
			'games' => $id_game,
		)
	);

	$recommended = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($id_game == $row['id_game'])
			continue;

		$recommended[] = getGameInfo($row['id_game']);
	}
	$smcFunc['db_free_result']($request);

	return $recommended;
}

// Updates Game
function updateGame($id_game, $gameOptions, $log = false)
{
	global $scripturl, $sourcedir, $db_prefix, $user_info, $smcFunc;

	if (empty($id_game))
		fatal_error('arcade_game_update_error', false);

	$gameUpdates = array();
	$updateValues = array();

	if (isset($gameOptions['internal_name']))
	{
		$gameUpdates[] = "internal_name = {string:internal_name}";
		$updateValues['internal_name'] = $gameOptions['internal_name'];
	}

	if (isset($gameOptions['name']))
	{
		$gameUpdates[] = "game_name = {string:game_name}";
		$updateValues['game_name'] = $gameOptions['name'];
	}

	if (isset($gameOptions['description']))
	{
		$gameUpdates[] = "description = {string:description}";
		$updateValues['description'] = $gameOptions['description'];
	}

	if (isset($gameOptions['help']))
	{
		$gameUpdates[] = "help = {string:help}";
		$updateValues['help'] = $gameOptions['help'];
	}

	if (isset($gameOptions['thumbnail']))
	{
		$gameUpdates[] = "thumbnail = {string:thumbnail}";
		$updateValues['thumbnail'] = $gameOptions['thumbnail'];
	}

	if (isset($gameOptions['thumbnail_small']))
	{
		$gameUpdates[] = "thumbnail_small = {string:thumbnail_small}";
		$updateValues['thumbnail_small'] = $gameOptions['thumbnail_small'];
	}

	if (isset($gameOptions['game_file']))
	{
		$gameUpdates[] = "game_file = {string:game_file}";
		$updateValues['game_file'] = $gameOptions['game_file'];
	}

	if (isset($gameOptions['game_directory']))
	{
		$gameUpdates[] = "game_directory = {string:game_directory}";
		$updateValues['game_directory'] = $gameOptions['game_directory'];
	}

	if (isset($gameOptions['submit_system']))
	{
		$gameUpdates[] = "submit_system = {string:submit_system}";
		$updateValues['submit_system'] = $gameOptions['submit_system'];
	}

	if (isset($gameOptions['member_groups']))
	{
		$gameUpdates[] = "member_groups = {string:member_groups}";
		$updateValues['member_groups'] = implode(',', $gameOptions['member_groups']);
	}

	if (isset($gameOptions['extra_data']))
	{
		$gameUpdates[] = "extra_data = {string:extra_data}";
		$updateValues['extra_data'] = serialize($gameOptions['extra_data']);
	}

	if (isset($gameOptions['score_type']))
	{
		$gameUpdates[] = "score_type = {int:score_type}";
		$updateValues['score_type'] = $gameOptions['score_type'];

		require_once($sourcedir . '/ArcadeMaintenance.php');
		fixScores($id_game, $gameOptions['score_type']);
	}

	if (isset($gameOptions['num_plays']))
	{
		if ($gameOptions['num_plays'] == '+')
		{
			$gameUpdates[] = "num_plays = num_plays + 1";
		}
		else
		{
			$gameUpdates[] = "num_plays = {int:num_plays}";
			$updateValues['num_plays'] = $gameOptions['num_plays'];
		}
	}

	if (isset($gameOptions['num_rates']))
	{
		if ($gameOptions['num_rates'] == '+')
			$gameUpdates[] = "num_rates = num_rates + 1";
		elseif ($gameOptions['num_rates'] == '-')
			$gameUpdates[] = "num_rates = num_rates - 1";
		else
		{
			$gameUpdates[] = "num_rates = {int:num_rates}";
			$updateValues['num_rates'] = $gameOptions['num_rates'];
		}
	}

	if (isset($gameOptions['num_favorites']))
	{
		if ($gameOptions['num_favorites'] == '+')
			$gameUpdates[] = "num_favorites = num_favorites + 1";
		elseif ($gameOptions['num_favorites'] == '-')
			$gameUpdates[] = "num_favorites = num_favorites - 1";
		else
		{
			$gameUpdates[] = "num_favorites = {int:num_favorites}";
			$updateValues['num_favorites'] = $gameOptions['num_favorites'];
		}
	}

	if (isset($gameOptions['rating']))
	{
		$gameUpdates[] = "game_rating = {float:rating}";
		$updateValues['rating'] = $gameOptions['rating'];
	}

	if (isset($gameOptions['category']))
	{
		$gameUpdates[] = "id_cat = {int:category}";
		$updateValues['category'] = $gameOptions['category'];
		$updateCat = true;
	}

	if (isset($gameOptions['champion']))
	{
		$gameUpdates[] = "id_champion = {int:champion}";
		$updateValues['champion'] = $gameOptions['champion'];
	}

	if (isset($gameOptions['champion_score']))
	{
		$gameUpdates[] = "id_champion_score = {int:champion_score}";
		$updateValues['champion_score'] = $gameOptions['champion_score'];
	}

	if (isset($gameOptions['enabled']))
	{
		$gameUpdates[] = "enabled = {int:enabled}";
		$updateValues['enabled'] = $gameOptions['enabled'] ? 1 : 0;
		$updateCat = true;
	}

	if (isset($gameOptions['local_permissions']))
	{
		$gameUpdates[] = "local_permissions = {int:local_permissions}";
		$updateValues['local_permissions'] = $gameOptions['local_permissions'];
	}

	if (empty($gameUpdates))
		return;

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}arcade_games
		SET ' . implode(', ', $gameUpdates) . '
		WHERE id_game = {int:game}',
		array_merge($updateValues, array(
			'game' => $id_game,
		))
	);

	if ($log)
		logAction('arcade_update_game', array('game' => $id_game));

	if (!empty($updateCat))
		updateCategoryStats();

	return true;
}

// Event system
function arcadeGetEventTypes($id = '')
{
	$events = array(
		'new_champion' => array(
			'id' => 'new_champion',
			'func' => 'arcadeEventNewChampion',
			'notification' => array(
				'new_champion_own' => true,
				'new_champion_any' => false
			)
		),
		'arena_invite' => array(
			'id' => 'arena_invite',
			'func' => 'arcadeEventArenaGeneral',
			'notification' => array(
				'arena_invite' => true,
			)
		),
		'arena_new_round' => array(
			'id' => 'arena_new_round',
			'func' => 'arcadeEventArenaGeneral',
			'notification' => array(
				'arena_new_round' => true,
			)
		),
		'arena_match_end' => array(
			'id' => 'arena_match_end',
			'func' => 'arcadeEventArenaGeneral',
			'notification' => array(
				'arena_match_end' => true,
			)
		),
	);

	if (empty($id))
		return $events;

	if (isset($events[$id]))
		return $events[$id];

	fatal_error('Hacking attempt...');
}

function arcadeEvent($id_event, $data = array())
{
	global $smcFunc, $db_prefix, $scripturl, $txt, $user_info, $sourcedir, $modSettings, $language;

	if ($id_event == 'get' && empty($data))
		return arcadeGetEventTypes();
	else
		$event = arcadeGetEventTypes($id_event);

	$replacements = array(
		'ARCADE_SETTINGS_URL' => $scripturl . '?action=profile;area=arcadeSettings'
	);
	$pms = array();

	$event['func']($event, $replacements, $pms, $data);

	if (empty($pms))
		return true;

	require_once($sourcedir . '/Subs-Post.php');

	$request = $smcFunc['db_query']('', '
		SELECT mem.id_member, mem.email_address, mem.notify_regularity, mem.notify_send_body, mem.lngfile
		FROM {db_prefix}members AS mem
		WHERE mem.id_member IN({array_int:members})
		ORDER BY mem.lngfile',
		array(
			'members' => array_keys($pms),
		)
	);

	while ($rowmember = $smcFunc['db_fetch_assoc']($request))
	{
		$replacements['USERID'] = $rowmember['id_member'];
		$replacements['PROFILE'] = $scripturl . '?action=profile;u=' . $rowmember['id_member'];

		$emailtype = 'notification_arcade_' . $pms[$rowmember['id_member']];

		loadLanguage('ArcadeEmail', empty($rowmember['lngfile']) || empty($modSettings['userLanguage']) ? $language : $rowmember['lngfile'], false);

		$emaildata = loadEmailTemplate($emailtype, $replacements, '', false);
		sendmail($rowmember['email_address'], $emaildata['subject'], $emaildata['body'], null, null, false, 4);

		unset($notification);
	}

	return true;
}

function arcadeEventNewChampion($event, &$replaces, &$pms, $data)
{
	global $smcFunc, $scripturl, $txt, $user_info;

	$replaces += array(
		'champion.name' => $data['member']['name'],
		'champion.score' => comma_format($data['score']['score']),
		'champion.url' => $scripturl . '?action=profile;u=' . $data['member']['id'],
		'GAMENAME' => $data['game']['name'],
		'GAMEURL' => $scripturl . '?action=arcade;game=' . $data['game']['id'],
	);

	if ($data['game']['is_champion'])
	{
		$replaces += array(
			'old_champion.name' => $data['game']['champion']['name'],
			'old_champion.score' => $data['game']['champion']['score'],
			'old_champion.url' => $scripturl . '?action=profile;u=' . $data['member']['id'],
		);

		$send = checkNotificationReceiver($data['game']['champion']['id'], $event, 'new_champion_own');
		$send &= $data['member']['id'] != $data['game']['champion']['id'];

		if ($send)
			$pms[$data['game']['champion']['id']] = 'new_champion_own';

		addNotificationRecievers($pms, $event, 'new_champion_any');
	}
}

function arcadeEventArenaGeneral($event, &$replaces, &$pms, $data)
{
	global $db_prefix, $scripturl, $txt, $user_info;

	$replaces += array(
		'MATCHURL' => $data['match_url'],
		'MATCHNAME' => $data['match_name'],
	);

	if (empty($data['players']))
		return;

	$pms = $pms + checkNotificationReceivers($data['players'], $event, $event['id']);
}

// Check
function checkNotificationReceiver($member, $event, $type)
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT value
		FROM {db_prefix}arcade_settings AS arcset
		WHERE (arcset.variable = {string:type})
			AND arcset.id_member = {int:member}',
		array(
			'type' => $type,
			'member' => $member
		)
	);

	$numRow = $smcFunc['db_num_rows']($request);
	list ($value) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	if ($numRow == 0)
		return $event['notification'][$type];

	return $value;
}

function checkNotificationReceivers($members, $event, $type)
{
	global $smcFunc;

	if ($event['notification'][$type])
		$where = '((arcset.variable = {string:type} AND arcset.value = 1) OR ISNULL(arcset.value))';
	else
		$where = '(arcset.variable = {string:type} AND arcset.value = 1)';

	$request = $smcFunc['db_query']('', '
		SELECT mem.id_member, mem.real_name, mem.email_address
		FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}arcade_settings AS arcset ON (arcset.id_member = mem.id_member)
		WHERE ' . $where .'
			AND mem.id_member IN({array_int:members})',
		array(
			'type' => $type,
			'members' => $members
		)
	);

	$pms = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($pms[$row['id_member']]))
			$pms[$row['id_member']] = $type;
	}
	$smcFunc['db_free_result']($request);

	return $pms;
}

// PM
function addNotificationRecievers(&$pms, $event, $type)
{
	global $db_prefix, $smcFunc;

	if ($event['notification'][$type])
		$where = '((arcset.variable = {string:type} AND arcset.value = 1) OR ISNULL(arcset.value))';
	else
		$where = '(arcset.variable = {string:type} AND arcset.value = 1)';

	$request = $smcFunc['db_query']('', '
		SELECT mem.id_member, mem.real_name, mem.email_address
		FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}arcade_settings AS arcset ON (arcset.id_member = mem.id_member)
		WHERE ' . $where,
		array(
			'type' => $type,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($pms[$row['id_member']]))
			$pms[$row['id_member']] = $type;
	}
	$smcFunc['db_free_result']($request);
}

// Score functions
// Return Latest scores
function ArcadeLatestScores($count = 5, $start = 0)
{
	global $scripturl, $txt, $db_prefix, $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT game.id_game, game.game_name, score.score, score.position,
			IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, {string:empty}) AS real_name, score.end_time
		FROM {db_prefix}arcade_scores AS score
			INNER JOIN {db_prefix}arcade_games AS game ON (game.id_game = score.id_game)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = score.id_member)
		ORDER BY end_time DESC
		LIMIT {int:start}, {int:count}',
		array(
			'start' => $start,
			'count' => $count,
			'empty' => '',
		)
	);

	$data = array();

	while ($score = $smcFunc['db_fetch_assoc']($request))
		$data[] = array(
			'game_id' => $score['id_game'],
			'name' => $score['game_name'],
			'score' => comma_format($score['score']),
			'id' => $score['id_member'],
			'member' => $score['real_name'],
			'memberLink' => !empty($score['real_name']) ? '<a href="' . $scripturl . '?action=profile;u=' . $score['id_member'] . '">' . $score['real_name'] . '</a>' : $txt['guest'],
			'time' => timeformat($score['end_time']),
		);
	$smcFunc['db_free_result']($request);

	return $data;
}

// Score saving
function SaveScore(&$game, $member, $score)
{
	global $db_prefix, $modSettings, $context, $smcFunc, $user_info;

	if ($game['score_type'] == 0)
		$reverse = false;
	elseif ($game['score_type'] == 1)
		$reverse = true;

	// No error by default
	$canSave = true;
	$error = '';

	$scoreLimit = 0;

	if (!empty($modSettings['arcadeMaxScores']))
		$scoreLimit = (int) $modSettings['arcadeMaxScores'];

	if (!empty($scoreLimit))
	{
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}arcade_scores
			WHERE id_game = {int:game}
				AND id_member = {int:member}',
			array(
				'game' => $game['id'],
				'member' => $member['id']
			)
		);

		list ($scoreCount) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		if ($scoreCount < $scoreLimit)
			$canSave = true;
		else
                {
			while ($scoreCount >= $scoreLimit)
			{
				$request = $smcFunc['db_query']('', '
					SELECT id_score, score, position
					FROM {db_prefix}arcade_scores
					WHERE id_game = {int:game}
					    	AND id_member = {int:member}
					ORDER BY score ' . ($reverse ? 'DESC' : 'ASC'),
					array(
						'game' => $game['id'],
						'member' => $member['id']
					)
				);

				list ($old_id_score, $oldScore, $lPosition) = $smcFunc['db_fetch_row']($request);

				if (!$reverse)
					$deleteOld = $oldScore < $score['score'];
				else
					$deleteOld = $oldScore > $score['score'];

				if (!$deleteOld)
				{
					$canSave = false;
					$error = 'arcade_scores_limit';
					
					break;
				}
				else
				{
					$request = $smcFunc['db_query']('', '
						DELETE FROM {db_prefix}arcade_scores
						WHERE id_score = {int:score}',
						array(
							'score' => $old_id_score
						)
					);

					updatePositions($game, $lPosition, '- 1');
					
					$scoreCount--;
				}
			}
		}
	}

	// Get position
	$result = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}arcade_scores
		WHERE score ' . ($reverse ? '<=' : '>='). ' {float:score}
			AND id_game = {int:game}',
		array(
			'score' => $score['score'],
			'game' => $game['id']
		)
	);

	list ($position) = $smcFunc['db_fetch_row']($result);
	$position++;
	$smcFunc['db_free_result']($result);

	if ($position == 1)
		$championFrom = $score['endTime'];
	else
		$championFrom = 0;

	if (!$canSave)
		return array('id' => false, 'error' => $error);

	// Update positions
	updatePositions($game, $position, '+ 1');

	$isPersonalBest = false;

	// This is my score
	if ($member['id'] != 0 && $user_info['id'] == $member['id'])
	{
		if (!$reverse)
			$isPersonalBest = $game['personal_best_score'] < $score['score'];
		else
			$isPersonalBest = $game['personal_best_score'] > $score['score'];
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT score
			FROM {db_prefix}arcade_scores
			WHERE id_member = {int:member}
				AND personal_best = 1',
			array(
				'member' => $member['id']
			)
		);

		if ($smcFunc['db_num_rows'] == 0)
			$isPersonalBest = true;
		else
		{
			list ($personalBestScore) =  $smcFunc['db_fetch_row']($request);

			if (!$reverse)
				$isPersonalBest = $personalBestScore < $score['score'];
			else
				$isPersonalBest = $personalBestScore > $score['score'];
		}
		$smcFunc['db_free_result']($request);
	}

	if ($member['id'] != 0 && $game['is_personal_best'] && $isPersonalBest)
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}arcade_scores
			SET personal_best = 0
			WHERE id_game = {int:game}
				AND id_member = {int:member}',
			array(
				'game' => $game['id'],
				'member' => $member['id']
			)
		);
	}

	$smcFunc['db_insert']('insert',
		'{db_prefix}arcade_scores',
		array(
			'id_game' => 'int',
			'id_member' => 'int',
			'player_name' => 'string',
			'member_ip' => 'string',
			'score' => 'float',
			'position' => 'int',
			'duration' => 'float',
			'end_time' => 'int',
			'champion_from' => 'int',
			'champion_to' => 'int',
			'comment' => 'string',
			'personal_best' => 'int',
			'score_status' => 'string-30',
			'validate_hash' => 'string-255',
		),
		array(
			$game['id'],
			$member['id'],
			$member['name'],
			$member['ip'],
			$score['score'],
			$position,
			$score['duration'],
			$score['endTime'],
			$championFrom,
			0,
			'',
			$isPersonalBest ? 1 : 0,
			$score['status'],
			$score['hash'],
		),
		array()
	);

	$id_score = $smcFunc['db_insert_id']('{db_prefix}arcade_scores', 'id_score');
	$score['id'] = $id_score;

	if ($position == 1)
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}arcade_scores
			SET champion_to = {int:end_time}
			WHERE id_score = {int:score}',
			array(
				'end_time' => $score['endTime'],
				'score' => $game['champion']['score_id'],
			)
		);

		updateGame($game['id'], array('champion' => $member['id'], 'champion_score' => $score['id']));

		$event = array(
			'game' => &$game,
			'member' => $member,
			'score' => $score,
			'time' => $score['endTime']
		);

		arcadeEvent('new_champion', $event);
	}

	cache_put_data('arcade-stats', null, 120);

	return array(
		'id' => $id_score,
		'isPersonalBest' => $isPersonalBest,
		'position' => $position,
		'newChampion' => $position == 1
	);
}

// Delete Scores
function deleteScores(&$game, $id_score)
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $smcFunc, $user_info;

	if (!is_array($id_score) && is_numeric($id_score))
		$id_score = array((int) $id_score);

	if (empty($id_score))
		return true;

	$request = $smcFunc['db_query']('', '
		SELECT id_score, id_member, position, score, personal_best
		FROM {db_prefix}arcade_scores
		WHERE id_score IN({array_int:score})
			AND id_game = {int:game}
		ORDER BY position',
		array(
			'game' => $game['id'],
			'score' => $id_score,
		)
	);

	$removeIds = array();
	$personalBest = array();
	$positions = array();
	$championUpdate = false;

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($row['personal_best'])
			$personalBest[] = $row['id_member'];

		$removeIds[] = $row['id_score'];
		$positions[] = $row['position'];

		if ($row['position'] == 1)
			$championUpdate = true;
	}
	$smcFunc['db_free_result']($request);

	$personalBest = array_unique($personalBest);

	$count = -1;

	if (empty($positions))
		return true;

	$startPos = $positions[0];

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_scores
		WHERE id_score IN({array_int:scores})',
		array(
			'scores' => $removeIds,
		)
	);
	
	// Log removed scores
	logAction('arcade_remove_scores', array('game' => $game['id'], 'scores' => count($removeIds)));

	for ($i = 0; $i < count($positions); $i++)
	{
		if (isset($positions[$i + 1]) && $positions[$i] + 1 == $positions[$i + 1])
			$count--;
		else
		{
			updatePositions($game, $startPos, $count);

			$startPos = $positions[$i];
			$count = -1;
		}
	}

	if ($championUpdate)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_score, id_member
			FROM {db_prefix}arcade_scores
			WHERE position = 1
				AND id_game = {int:game}
			LIMIT 1',
			array(
				'game' => $game['id'],
			)
		);

		$row = $smcFunc['db_fetch_assoc']($request);

		if ($row !== false)
			updateGame($game['id'], array('champion' => $row['id_member'], 'champion_score' => $row['id_score']));
		else
			updateGame($game['id'], array('champion' => 0, 'champion_score' => 0));
		
		$smcFunc['db_free_result']($request);

		if (!empty($row['id_score']))
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}arcade_scores
				SET champion_from = {int:time}
				WHERE id_score = {int:score}',
				array(
					'time' => time(),
					'score' => $row['id_score'],
				)
			);
		}
	}

	if (empty($personalBest))
		return true;

	$request = $smcFunc['db_query']('', '
		SELECT id_score, id_member
		FROM {db_prefix}arcade_scores
		WHERE id_game = {int:game}
			AND id_member IN({array_int:members})
		ORDER BY position',
		array(
			'game' => $game['id'],
			'members' => $personalBest,
		)
	);

	$newPersonalBest = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($newPersonalBest[$row['id_member']]))
			$newPersonalBest[$row['id_member']] = $row['id_score'];
	}
	$smcFunc['db_free_result']($request);

	if (empty($newPersonalBest))
		return true;

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}arcade_scores
		SET personal_best = 1
		WHERE id_score IN({array_int:scores})',
		array(
			'scores' => $newPersonalBest,
		)
	);

	return true;
}

// Updates positions. (new score, remove)
function updatePositions(&$game, $start, $how)
{
	global $db_prefix, $smcFunc;

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}arcade_scores
		SET position = position ' . $how . '
		WHERE id_game = {int:game}
			AND position >= {int:position}',
		array(
			'game' => $game['id'],
			'position' => $start,
		)
	);

	return $smcFunc['db_affected_rows']();
}

function loadMatch($match)
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT m.id_match, m.name, m.private_game, m.created, m.updated, m.status,
			m.num_players, m.current_players, m.num_rounds, m.current_round, m.id_member,
			IFNULL(me.id_member, 0) AS participation, me.status AS my_state
		FROM {db_prefix}arcade_matches AS m
			LEFT JOIN {db_prefix}arcade_matches_players AS me ON (me.id_match = m.id_match
				AND me.id_member = {int:current_member})
		WHERE m.id_match = {int:match}
			AND {query_see_match}',
		array(
			'match' => $match,
			'current_member' => $user_info['id'],
		)
	);

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	if (!$row)
		fatal_lang_error('match_not_found', false);

	$status = array(
		0 => 'arcade_arena_player_invited',
		1 => 'arcade_arena_player_waiting',
		2 => 'arcade_arena_player_waiting',
		3 => 'arcade_arena_player_played',
		4 => 'arcade_arena_player_knockedout',
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

	$context['match'] = array(
		'id' => $row['id_match'],
		'name' => $row['name'],
		'private' => !empty($row['private_game']),
		'created' => timeformat($row['created']),
		'updated' => !empty($row['updated']) ? timeformat($row['updated']) : '',
		'players' => array(),
		'starter' => $row['id_member'],
		'num_players' => $row['current_players'],
		'players_limit' => $row['num_players'],
		'round' => $row['current_round'],
		'rounds' => array(),
		'num_rounds' => $row['num_rounds'],
		//'status' => $status[$row['status']],
		'status' => $status[$row['my_state'] + ($row['status'] * 10 + 10)],
	);

	$can_play = $row['participation'] && ($row['my_state'] == 1 || $row['my_state'] == 2) && $row['status'] == 1;
	$context['can_play_match'] = false;
	$context['can_leave'] = $row['participation'] && $row['status'] == 0 && $row['my_state'] != 0;
	$context['can_accept'] = $row['participation'] && $row['my_state'] == 0 && $row['status'] == 0;
	$context['can_decline'] = $row['participation'] && $row['my_state'] == 0 && $row['status'] == 0;
	$context['can_join_match'] = allowedTo('arcade_join_match') && $row['status'] == 0 && !$row['participation'] && $row['current_players'] < $row['num_players'];
	$context['can_edit_match'] = (allowedTo('arcade_admin') || $context['match']['starter'] == $user_info['id']) && $row['status'] != 2;
	$context['can_start_match'] = $context['can_edit_match'] && $row['status'] == 0 && $row['current_players'] >= 2;

	unset($row);

	// Load players
	$request = $smcFunc['db_query']('', '
		SELECT p.id_member, p.status, p.score, mem.real_name
		FROM {db_prefix}arcade_matches_players AS p
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = p.id_member)
		WHERE id_match = {int:match}
		ORDER BY score DESC',
		array(
			'match' => $context['match']['id']
		)
	);

	$rank = 1;

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['match']['players'][$row['id_member']] = array(
			'id' => $row['id_member'],
			'rank' => $rank++,
			'name' => $row['real_name'],
			'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
			'score' => comma_format($row['score']),
			'status' => $txt[$status[$row['status']]],
			'can_accept' => $row['status'] == 0 && $row['id_member'] == $user_info['id'],
			'can_decline' => $row['status'] == 0 && $row['id_member'] == $user_info['id'],
			'can_kick' => $context['can_edit_match'] && $row['id_member'] != $user_info['id'],
			'accept_url' => $scripturl . '?action=arcade;sa=viewMatch;join;match=' . $context['match']['id'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'decline_url' => $scripturl . '?action=arcade;sa=viewMatch;leave;match=' . $context['match']['id'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'kick_url' => $scripturl . '?action=arcade;sa=viewMatch;kick;player=' . $row['id_member'] . ';match=' . $context['match']['id'] . ';' . $context['session_var'] . '=' . $context['session_id'],
		);

		$context['can_start_match'] &= $row['status'] != 0;
	}
	$smcFunc['db_free_result']($request);

	// Load rounds
	$request = $smcFunc['db_query']('', '
		SELECT r.round, r.id_game, r.status, game.game_name
		FROM {db_prefix}arcade_matches_rounds As r
			LEFT JOIN {db_prefix}arcade_games AS game ON (game.id_game = r.id_game)
		WHERE id_match = {int:match}
		ORDER BY r.round',
		array(
			'match' => $context['match']['id']
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['match']['rounds'][$row['round']] = array(
			'round' => $row['round'],
			'game' => $row['id_game'],
			'name' => $row['game_name'],
			'status' => $row['status'],
			'select' => !(empty($row['game_name']) || empty($row['id_game'])),
			'can_select' => (empty($row['game_name']) || empty($row['id_game'])) && $context['can_edit_match'],
			'can_play' => !empty($row['id_game']) && $row['round'] == $context['match']['round'] && $can_play,
			'play_url' => $scripturl . '?action=arcade;sa=play;match=' . $context['match']['id'],
		);

		if ($context['match']['rounds'][$row['round']]['can_play'])
			$context['can_play_match'] = true;
	}
	$smcFunc['db_free_result']($request);
}

// createMatch
function createMatch($matchOptions)
{
	global $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	if (empty($matchOptions['created']))
		$matchOptions['created'] = time();

	if (!isset($matchOptions['private_game']))
		$matchOptions['private_game'] = 0;

	if (!isset($matchOptions['starter']))
		$matchOptions['starter'] = $user_info['id'];

	if (!empty($matchOptions['extra']))
		$matchOptions['extra'] = serialize($matchOptions['extra']);
	else
		$matchOptions['extra'] = '';

	$smcFunc['db_insert']('insert',
		'{db_prefix}arcade_matches',
		array(
			'name' => 'string',
			'id_member' => 'int',
			'private_game' => 'int',
			'status' => 'int',
			'created' => 'int',
			'updated' => 'int',
			'num_players' => 'int',
			'current_players' => 'int',
			'num_rounds' => 'int',
			'current_round' => 'int',
			'match_data' => 'string',
		),
		array(
			$matchOptions['name'],
			$matchOptions['starter'],
			!empty($matchOptions['private_game']) ? 1 : 0,
			0,
			$matchOptions['created'],
			0,
			$matchOptions['num_players'],
			0,
			$matchOptions['num_rounds'],
			0,
			$matchOptions['extra']
		),
		array()
	);

	$id_match = $smcFunc['db_insert_id']('{db_prefix}arcade_matches', 'id_match');

	$rows = array();
	for ($i = 0; $i < $matchOptions['num_rounds']; $i++)
	{
		$rows[] = array(
			$id_match,
			$i + 1,
			isset($matchOptions['games'][$i]) ? $matchOptions['games'][$i] : 0,
			0,
		);
	}

	$smcFunc['db_insert']('insert',
		'{db_prefix}arcade_matches_rounds',
		array(
			'id_match' => 'int',
			'round' => 'int',
			'id_game' => 'int',
			'status' => 'int',
		),
		$rows,
		array()
	);
	unset($rows);

	if (!empty($matchOptions['players']))
	{
		require_once($sourcedir . '/Subs-Post.php');

		$players = array();

		foreach ($matchOptions['players'] as $id)
			$players[$id] = $id == $matchOptions['starter'] ? 1 : 0;

		matchAddPlayers($id_match, $players);
	}

	return $id_match;
}

function matchAddPlayers($id_match, $players)
{
	global $smcFunc, $sourcedir, $scripturl;

	require_once($sourcedir . '/Subs-Post.php');

	$request = $smcFunc['db_query']('', '
		SELECT m.id_match, m.name, m.current_players, m.num_players
		FROM {db_prefix}arcade_matches AS m
		WHERE m.id_match = {int:match}',
		array(
			'match' => $id_match,
		)
	);
	$matchInfo = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	if (!$matchInfo)
		return false;

	if ((count($players) + $matchInfo['current_players']) > $matchInfo['num_players'])
		return false;

	$request = $smcFunc['db_query']('', '
		SELECT id_member, real_name
		FROM {db_prefix}members
		WHERE id_member IN({array_int:members})',
		array(
			'members' => array_keys($players),
		)
	);

	$rows = array();
	$sendPms = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$rows[] = array(
			$id_match,
			$row['id_member'],
			$players[$row['id_member']],
			'',
		);

		if ($players[$row['id_member']] == 0)
			$sendPms[] = $row['id_member'];
	}
	$smcFunc['db_free_result']($request);

	if (!empty($sendPms))
		arcadeEvent('arena_invite',
			array(
				'match_name' => $matchInfo['name'],
				'match_id' => $id_match,
				'match_url' => $scripturl . '?action=arcade;match=' . $id_match,
				'players' => $sendPms,
			)
		);

	$smcFunc['db_insert']('insert',
		'{db_prefix}arcade_matches_players',
		array(
			'id_match' => 'int',
			'id_member' => 'int',
			'status' => 'int',
			'player_data' => 'string',
		),
		$rows,
		array()
	);

	unset($rows);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}arcade_matches
		SET current_players = {int:players}
		WHERE id_match = {int:match}',
		array(
			'match' => $id_match,
			'players' => (count($players) + $matchInfo['current_players']),
		)
	);

	matchUpdateStatus($id_match);

	return true;
}

function matchUpdatePlayers($id_match, $players, $status = 1)
{
	global $smcFunc, $sourcedir;

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}arcade_matches_players
		SET status = {int:status}
		WHERE id_match = {int:match}
			AND id_member IN({array_int:players})',
		array(
			'match' => $id_match,
			'players' => $players,
			'status' => $status,
		)
	);

	matchUpdateStatus($id_match);

	return true;
}

function matchUpdateStatus($id_match)
{
	global $smcFunc, $scripturl;

	$request = $smcFunc['db_query']('', '
		SELECT m.id_match, m.name, m.current_players, m.num_players, m.current_round, m.num_rounds, m.status, m.match_data
		FROM {db_prefix}arcade_matches AS m
		WHERE m.id_match = {int:match}',
		array(
			'match' => $id_match,
		)
	);
	$matchInfo = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	if (!empty($matchInfo['match_data']))
		$matchInfo['match_data'] = unserialize($matchInfo['match_data']);
	else
		$matchInfo['match_data'] = array();

	if ($matchInfo['status'] == 0)
	{
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}arcade_matches_players
			WHERE id_match = {int:match}
				AND status = 0',
			array(
				'match' => $id_match,
			)
		);

		list ($cn) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		if ($cn > 0)
			return;

		if ($matchInfo['current_players'] == $matchInfo['num_players'])
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}arcade_matches
				SET status = 1, current_round = 1
				WHERE id_match = {int:match}',
				array(
					'match' => $id_match,
				)
			);

			// No one has played yet
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}arcade_matches_players
				SET status = 1
				WHERE id_match = {int:match}',
				array(
					'match' => $id_match,
				)
			);
		}
	}
	elseif ($matchInfo['status'] == 1)
	{
		if ($matchInfo['current_round'] == 0)
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}arcade_matches
				SET current_round = 1
				WHERE id_match = {int:match}',
				array(
					'match' => $id_match,
				)
			);

			// No one has played yet
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}arcade_matches_players
				SET status = 1
				WHERE id_match = {int:match}',
				array(
					'match' => $id_match,
				)
			);
		}
		else
		{
			$request = $smcFunc['db_query']('', '
				SELECT COUNT(*)
				FROM {db_prefix}arcade_matches_players
				WHERE id_match = {int:match}
					AND (status = 1 OR status = 2)',
				array(
					'match' => $id_match,
				)
			);

			list ($cn) = $smcFunc['db_fetch_row']($request);
			$smcFunc['db_free_result']($request);

			// Has all played?
			if ($cn > 0)
				return;

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

			$request = $smcFunc['db_query']('', '
				SELECT id_game, score_type, extra_data
				FROM {db_prefix}arcade_games
				WHERE id_game = {int:game}',
				array(
					'game' => $round['id_game'],
				)
			);

			$game = $smcFunc['db_fetch_assoc']($request);
			$smcFunc['db_free_result']($request);

			if ($game['score_type'] == 0)
				$order = 'DESC';
			elseif ($game['score_type'] == 1)
				$order = 'ASC';

			// Scores to give
			$scores = array(10, 8, 6, 5, 4, 3, 2, 1);

			$request = $smcFunc['db_query']('', '
				SELECT id_member
				FROM {db_prefix}arcade_matches_results
				WHERE id_match = {int:match}
					AND round = {int:round}
				ORDER BY score ' . $order . '',
				array(
					'match' => $id_match,
					'round' => $matchInfo['current_round'],
				)
			);

			$current = 0;

			$players = array();

			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				if (isset($scores[$current]))
				{
					$smcFunc['db_query']('', '
						UPDATE {db_prefix}arcade_matches_players
						SET score = score + {int:score}
						WHERE id_match = {int:match}
							AND id_member = {int:player}',
						array(
							'match' => $id_match,
							'player' => $row['id_member'],
							'score' => $scores[$current],
						)
					);
				}

				$players[] = $row['id_member'];

				$current++;
			}
			$smcFunc['db_free_result']($request);

			if ($matchInfo['match_data']['mode'] == 'knockout')
			{
				$request = $smcFunc['db_query']('', '
					SELECT id_member
					FROM {db_prefix}arcade_matches_players
					WHERE id_match = {int:match}
					ORDER BY score
					LIMIT 1',
					array(
						'match' => $id_match,
					)
				);

				list ($knockout) = $smcFunc['db_fetch_row']($request);
				$smcFunc['db_free_result']($request);

				$request = $smcFunc['db_query']('', '
					UPDATE {db_prefix}arcade_matches_players
					SET status = 4
					WHERE id_match = {int:match}
						AND id_member = {int:member}',
					array(
						'match' => $id_match,
						'member' => $knockout,
					)
				);
			}

			// Last round?
			if ($matchInfo['current_round'] == $matchInfo['num_rounds'])
			{
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}arcade_matches
					SET status = 2
					WHERE id_match = {int:match}',
					array(
						'match' => $id_match,
					)
				);

				arcadeEvent('arena_match_end',
					array(
						'match_name' => $matchInfo['name'],
						'match_id' => $id_match,
						'match_url' => $scripturl . '?action=arcade;match=' . $id_match,
						'players' => $players,
					)
				);

				return;
			}
			// Advance to next round
			else
			{
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}arcade_matches
					SET current_round = current_round + 1
					WHERE id_match = {int:match}',
					array(
						'match' => $id_match,
					)
				);

				// No one has played yet
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}arcade_matches_players
					SET status = 1
					WHERE id_match = {int:match}
						AND status = 3',
					array(
						'match' => $id_match,
					)
				);

				arcadeEvent('arena_new_round',
					array(
						'match_name' => $matchInfo['name'],
						'match_id' => $id_match,
						'match_url' => $scripturl . '?action=arcade;match=' . $id_match,
						'players' => $players,
					)
				);
			}
		}
	}

	return;
}

// matchRemovePlayers
function matchRemovePlayers($id_match, $players)
{
	global $smcFunc;

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_matches_players
		WHERE id_match = {int:match}
			AND id_member IN({array_int:players})',
		array(
			'match' => $id_match,
			'players' => $players,
		)
	);

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}arcade_matches_players
		WHERE id_match = {int:match}',
		array(
			'match' => $id_match,
		)
	);

	list ($cn) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}arcade_matches
		SET current_players = {int:players}
		WHERE id_match = {int:match}',
		array(
			'match' => $id_match,
			'players' => $cn,
		)
	);
	
	matchUpdateStatus($id_match);

	return true;
}

// deleteMatch
function deleteMatch($id_match)
{
	global $smcFunc;

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_matches
		WHERE id_match = {int:match}',
		array(
			'match' => $id_match
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_matches_players
		WHERE id_match = {int:match}',
		array(
			'match' => $id_match
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_matches_results
		WHERE id_match = {int:match}',
		array(
			'match' => $id_match
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_matches_rounds
		WHERE id_match = {int:match}',
		array(
			'match' => $id_match
		)
	);

	return true;
}

// Output
function ArcadeXMLOutput($data, $name = null, $elements = array())
{
	global $context, $modSettings;

	ob_end_clean();
	if (!empty($modSettings['enableCompressedOutput']))
		@ob_start('ob_gzhandler');
	else
		ob_start();

	header('Content-Type: text/xml; charset=' . (empty($context['character_set']) ? 'ISO-8859-1' : $context['character_set']));

	echo '<', '?xml version="1.0" encoding="', $context['character_set'], '"?', '>
<smf>
	', Array2XML($data, $name, $elements), '
</smf>';

	obExit(false);
}

function Array2XML($data, $name = null, $elements = array(), $indent = 1)
{
	if (!is_array($data))
		return;

	$output = array();

	$ind = str_repeat("\t", $indent);

	foreach ($data as $k => $data)
	{
		if (is_numeric($k) && $name != null)
		{
			if (is_array($data) && isset($data[0]) && $data[0] = 'call')
				$output [] = '<' . $name . '><![CDATA[' . call_user_func_array($data[1], $data[2]) . ']]></' . $name . '>';
			else
			{
				$output[] = '<' . $name . '>';
				$output[] = '	' . Array2XML($data, null, $elements, $indent++);
				$output[] = '</' . $name . '>';
			}
		}
		elseif (is_numeric($k))
			fatal_lang_error('arcade_internal_error', false);
		else
		{
			if (!empty($elements) && !((in_array($k, $elements) && !is_array($data)) || (isset($elements[$k]) && is_array($data))))
				continue;

			if (is_array($data))
			{
				$output[] = '<' . $k . '>';
				$output[] = '	' . Array2XML($data, null, $elements[$k], $indent++);
				$output[] = '</' . $k . '>';
			}
			else
			{
				if ($data === false)
					$data = 0;
				elseif ($data === true)
					$data = 1;

				if (!is_numeric($data))
					$output [] = '<' . $k . '><![CDATA[' . $data . ']]></' . $k . '>';
				else
					$output [] = '<' . $k . '>' . $data . '</' . $k . '>';
			}
		}
	}

	return implode("\n", $output);
}

function memberAllowedTo($permission, $memID)
{
	if (!is_array($permission))
		$permission = array($permission);

	if (!is_array($memID))
	{
		foreach ($permission as $perm)
		{
			if (in_array($memID, membersAllowedTo($perm)))
				return true;
		}

		return false;
	}

	$allowed = array();

	foreach ($permission as $perm)
	{
		$members = membersAllowedTo($perm);

		foreach ($memID as $i => $id)
		{
			if (in_array($id, $members))
			{
				$allowed[] = $id;

				unset($memID[$i]);

				if (empty($memID))
					return $allowed;
			}
		}
	}

	return $allowed;
}

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return (float) $usec + (float) $sec;
}

function duration_format($seconds, $max = 2)
{
	global $txt;

	if ($seconds < 1)
		return $txt['arcade_unknown'];

	// max: 0 = weeks, 1 = days, 2 = hours, 3 = minutes, 4 = seconds

	if ($max >= 4)
		$max = 3;
	else
		$max--;

	$units = array(
		array(604800, $txt['arcade_weeks']), // Seconds in week
		array(86400, $txt['arcade_days']), // Seconds in day
		array(3600, $txt['arcade_hours']), // Seconds in hour
		array(60, $txt['arcade_mins']), // Seconds in minute
		array(1, $txt['arcade_secs']), // Seconds in minute
	);

	$out = array();

	foreach ($units as $i => $unit)
	{
		if ($max > $i || $seconds < $unit[0])
			continue;

		list ($secs, $text) = $unit;

		$s = floor($seconds / $secs);
		$seconds -= $s * $secs;

		$out[] = $s . ' ' . $text;
	}

	return implode(' ', $out);
}

?>