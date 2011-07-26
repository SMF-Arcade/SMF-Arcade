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
	void ArcadeStats()
		- ...

	void arcadeStats2()
		- ...

	array ArcadeStats_MostPlayed([count = 10], [time])
		- ...

	array ArcadeStats_Rating([count = 10])
		- ...

	array ArcadeStats_BestPlayers([count = 10])
		- ...

	array ArcadeStats_LongestChampions([count = 10], [time])
		- ...

	array ArcadeStats_MostActive([count = 10], [time])
		- ...

*/

function ArcadeStatistics()
{
	global $txt, $context, $scripturl;

	// Load data using functions
	$context['arcade']['statistics']['play'] = ArcadeStats_MostPlayed();
	$context['arcade']['statistics']['active'] = ArcadeStats_MostActive();
	$context['arcade']['statistics']['rating'] = ArcadeStats_Rating();
	$context['arcade']['statistics']['champions'] = ArcadeStats_BestPlayers();
	$context['arcade']['statistics']['longest'] = ArcadeStats_LongestChampions();

	// Layout
	loadTemplate('ArcadeStats');
	$context['sub_template'] = 'arcade_statistics';
	$context['page_title'] = $txt['arcade_stats_title'];

	// Linktree
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=arcade;sa=stats',
		'name' => $txt['arcade_stats'],
	);
}

function ArcadeStats_MostPlayed($count = 10)
{
	// Returns most playd games
	global $db_prefix, $scripturl, $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT game.id_game, game.game_name, game.game_rating, game.num_plays
		FROM {db_prefix}arcade_games AS game
		WHERE game.num_plays > 0
		ORDER BY game.num_plays DESC
		LIMIT {int:count}',
		array(
			'count' => $count,
			'empty' => ''
		)
	);

	$top = array();
	$max = -1;

	while ($score = $smcFunc['db_fetch_assoc']($request))
	{
		if ($max == -1)
			$max = $score['num_plays'];
		if ($max == 0)
			return false; // No one has played games yet0

		$top[] = array(
			'id' => $score['id_game'],
			'name' => $score['game_name'],
			'link' => '<a href="' . $scripturl . '?action=arcade;sa=play;game=' . $score['id_game'] . '">' .  $score['game_name'] . '</a>',
			'rating' => $score['game_rating'],
			'plays' =>comma_format($score['num_plays']),
			'precent' => ($score['num_plays'] / $max) * 100,
		);
	}
	$smcFunc['db_free_result']($request);

	if (count($top) == 0)
		return false;
	elseif ($count > 1)
		return $top;
	else
		return $top[0];
}

function ArcadeStats_Rating($count = 10)
{
	global $db_prefix, $scripturl, $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT game.id_game, game.game_name, game.game_rating, game.num_plays
		FROM {db_prefix}arcade_games AS game
		WHERE game_rating > 0
		ORDER BY game.game_rating DESC
		LIMIT {int:count}',
		array(
			'count' => $count,
			'empty' => ''
		)
	);

	$top = array();
	$max = -1;

	while ($score = $smcFunc['db_fetch_assoc']($request))
	{
		if ($max == -1)
			$max = $score['game_rating'];

		$top[] = array(
			'id' => $score['id_game'],
			'name' => $score['game_name'],
			'link' => '<a href="' . $scripturl . '?action=arcade;sa=play;game=' . $score['id_game'] . '">' .  $score['game_name'] . '</a>',
			'rating' => $score['game_rating'],
			'plays' => comma_format($score['num_plays']),
			'precent' => ($score['game_rating'] / $max) * 100,
		);
	}
	$smcFunc['db_free_result']($request);

	if (count($top) == 0)
		return false;
	elseif ($count > 1)
		return $top;
	else
		return $top[0];
}

function ArcadeStats_BestPlayers($count = 10)
{
	// Returns best players by count of champions
	global $db_prefix, $scripturl, $txt, $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS champions, IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, {string:empty}) AS real_name
		FROM {db_prefix}arcade_games AS game
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = game.id_champion)
		WHERE id_champion_score > 0
		GROUP BY game.id_champion
		ORDER BY champions DESC
		LIMIT {int:count}',
		array(
			'count' => $count,
			'empty' => ''
		)
	);

	$top = array();
	$max = -1;

	while ($score = $smcFunc['db_fetch_assoc']($request))
	{
		if ($max == -1)
			$max = $score['champions'];

		$top[] = array(
			'name' => $score['real_name'],
			'link' => !empty($score['real_name']) ? '<a href="' . $scripturl . '?action=profile;u=' . $score['id_member'] . '">' .  $score['real_name'] . '</a>' : $txt['guest'],
			'champions' => comma_format($score['champions']),
			'precent' => ($score['champions'] / $max) * 100,
		);
	}
	$smcFunc['db_free_result']($request);

	if (count($top) == 0)
		return false;
	elseif ($count > 1)
		return $top;
	else
		return $top[0];
}

function ArcadeStats_MostActive($count = 10, $time = -1)
{
	// Returns most active players
	global $db_prefix, $scripturl, $txt, $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS scores, IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, {string:empty}) AS real_name
		FROM {db_prefix}arcade_scores AS score
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = score.id_member)
		GROUP BY score.id_member
		ORDER BY scores DESC
		LIMIT {int:count}',
		array(
			'count' => $count,
			'empty' => ''
		)
	);

	$top = array();
	$max = -1;

	while ($score = $smcFunc['db_fetch_assoc']($request))
	{
		if ($max == -1)
			$max = $score['scores'];

		$top[] = array(
			'name' => $score['real_name'],
			'link' => !empty($score['real_name']) ? '<a href="' . $scripturl . '?action=profile;u=' . $score['id_member'] . '">' .  $score['real_name'] . '</a>' : $txt['guest'],
			'scores' => comma_format($score['scores']),
			'precent' => ($score['scores'] / $max) * 100,
		);
	}
	$smcFunc['db_free_result']($request);

	if (count($top) == 0 || $count == 1)
		return false;
	elseif ($count > 1)
		return $top;
	else
		return $top[0];
}

function ArcadeStats_LongestChampions($count = 10, $time = - 1, $where = false)
{
	global $db_prefix, $scripturl, $txt, $smcFunc;

	if (!$where)
	{
		$where = '1 = 1';
		$order = 'CASE WHEN champion_from > 0 THEN (CASE WHEN champion_to = 0 THEN UNIX_TIMESTAMP() ELSE champion_to END - champion_from) ELSE 0 END DESC';
	}
	elseif ($where == 'current')
	{
		$where = 'champion_to = 0';
		$order = 'champion_from';
	}
	elseif ($where == 'past')
	{
		$where = 'champion_to > 0';
		$order = 'champion_to - champion_from';
	}

	$request = $smcFunc['db_query']('', '
		SELECT game.id_game, game.game_name,
			CASE WHEN champion_from > 0 THEN (CASE WHEN champion_to = 0 THEN UNIX_TIMESTAMP() ELSE champion_to END - champion_from) ELSE 0 END AS champion_duration,
			IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, {string:empty}) AS real_name, CASE WHEN champion_to = 0 THEN 1 ELSE 0 END AS current
		FROM {db_prefix}arcade_scores AS score
			LEFT JOIN {db_prefix}arcade_games AS game ON (game.id_game = score.id_game)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = score.id_member)
		WHERE ' . $where .'
		GROUP BY score.id_score
		HAVING champion_duration > 0
		ORDER BY ' . $order . '
		LIMIT {int:count}',
		array(
			'count' => $count,
			'empty' => '',
		)
	);

	$top = array();
	$max = -1;

	while ($score = $smcFunc['db_fetch_assoc']($request))
	{
		if ($max == -1)
			$max = $score['champion_duration'];

		$top[] = array(
			'game_name' => $score['game_name'],
			'game_link' => '<a href="' . $scripturl . '?action=arcade;sa=play;game=' . $score['id_game'] . '">' .  $score['game_name'] . '</a>',
			'member_name' => $score['real_name'],
			'member_link' => !empty($score['real_name']) ? '<a href="' . $scripturl . '?action=profile;u=' . $score['id_member'] . '">' .  $score['real_name'] . '</a>' : $txt['guest'],
			'duration' => duration_format($score['champion_duration']),
			'precent' => ($score['champion_duration'] / $max) * 100,
			'current' => $score['current'] == 1,
		);
	}
	$smcFunc['db_free_result']($request);

	if (count($top) == 0)
		return false;
	elseif ($count > 1)
		return $top;
	else
		return $top[0];
}

?>