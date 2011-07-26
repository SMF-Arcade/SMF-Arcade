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
	void ArcadeV2Play()
		- ???

	array ArcadeV2Submit()
		- ???

	void ArcadeV2Html()
		- ???

	void ArcadeV2Start()
		- ???

	void ArcadeV2Hash()
		- ???

	void ArcadeV2Score()
		- ???
*/

function ArcadeV2GetGame()
{
	global $scripturl, $txt, $db_prefix, $context, $sourcedir, $modSettings, $smcFunc;

	return getGameInfo($_POST['game']);
}

function ArcadeV2Submit(&$game, $session)
{
	global $scripturl, $txt, $db_prefix, $context, $sourcedir, $modSettings, $smcFunc;

	checkSession('post');

	// You didn't cheat unless you prove it
	$cheating = false;

	$maxTries = $session['maxTries'];
	$reverse = $game['score_type'] == 2;

	$best = array();
	$isBest = false;
	$tries = 1;

	foreach ($session['scores'] as $id => $score)
	{
		if (!$isBest || (!$reverse && $score['score'] > $best['score']) || ($reverse && $score['score'] < $best['score']))
		{
			$best = $score;
			$isBest = true;
		}

		// If maxTries have been changed it is cheating probably?
		if ($score['maxTries'] != $maxTries)
			$cheating = 'max_tries';

		// You can't play more times than you are allowed to
		if (!empty($maxTries) && $score['try'] > $maxTries)
			$cheating = 'too_much_tries';

		// Yes tries should be here always
		if ($tries != $score['try'])
			$cheating = 'invalid_try';

		$tries++;
	}

	if (empty($best))
		return false;

	return array(
		'cheating' => $cheating,
		'score' => $best['score'],
		'start_time' => $best['startTime'],
		'end_time' => $best['endTime'],
		'duration' => round($best['endTime'] - $best['startTime']),
		'hash' => $best['hash'],
	);
}

function ArcadeV2Play(&$game, &$session_info)
{
	global $scripturl, $txt, $db_prefix, $context, $sourcedir, $modSettings, $smcFunc;
}

function ArcadeV2XMLPlay(&$game, &$session_info)
{
	global $scripturl, $txt, $db_prefix, $context, $sourcedir, $modSettings, $smcFunc;

	return true;
}

function ArcadeV2Html(&$game, $auto_start = true)
{
	global $scripturl, $txt, $context, $settings;

	echo '
	<script type="text/javascript" src="', $settings['default_theme_url'], '/scripts/swfobject.js"></script>
	<div id="game">
		', $txt['arcade_no_flash'], '
	</div>
	<script language="JavaScript" type="text/javascript" defer="defer"><!-- // --><![CDATA[
		var play_url = smf_scripturl + "?action=arcade;sa=play;xml";
		var running = false;

		function arcadeRestart()
		{
			setInnerHTML(document.getElementById("game"), "', addslashes($txt['arcade_no_flash']), '");

			var so = new SWFObject("', $game['url']['flash'], '", "', $game['file'], '", "', $game['extra_data']['width'], '", "', $game['extra_data']['height'], '", "7");
			so.addParam("menu", "false");
			so.addParam("ArcadeUrl", "', $scripturl, '");
			so.write("game");

			return true;
		}

		', $auto_start ? 'arcadeRestart();' : '', '
	// ]]></script>';
}

function ArcadeV2Start()
{
	global $scripturl, $txt, $db_prefix, $context, $sourcedir, $modSettings, $smcFunc;

	ob_end_clean();
	if (!empty($modSettings['enableCompressedOutput']))
		@ob_start('ob_gzhandler');
	else
		ob_start();

	if (!isset($_REQUEST['game']))
		v2Error('invalid_game');

	$game = getGameInfo($_REQUEST['game']);

	if ($game === false)
		v2Error('invalid_game');

	$session = &$_SESSION['arcade_play_' . $game['id']];
	$extra = &$_SESSION['arcade_play_extra_' . $game['id']];

	if (!isset($extra['max_try']))
		$maxTry = 0;
	else
		$maxTry = (int) $extra['max_try'];

	$session = array(
		'id' => $game['id'],
		'game' => $game['internal_name'],
		'maxTries' => $maxTry,
		'loadStart' => time(),
		'scores' => array(),
		'done' => false,
		'hash' => rand(1, 50),
	);

	echo '&maxtry=', $maxTry, '&sesc=', $context['session_id'] , '&hash=', $session['hash'];

	obExit(false);
}

function ArcadeV2Hash()
{
	global $scripturl, $txt, $db_prefix, $context, $sourcedir, $modSettings, $smcFunc;

	checkSession('post');

	ob_end_clean();
	if (!empty($modSettings['enableCompressedOutput']))
		@ob_start('ob_gzhandler');
	else
		ob_start();

	$game = getGameInfo($_REQUEST['game']);

	if ($game === false)
		v2Error('invalid_game');

	$session = &$_SESSION['arcade_play_' . $game['id']];
	$extra = &$_SESSION['arcade_play_extra_' . $game['id']];

	$session['score_hash'] = rand(1, 50);
	$session['score_hash_time'] = microtime_float();

	$session['request_id'] = $_REQUEST['request_id'];

	echo '&scorehash=', $session['score_hash'], '&savescore=1';

	obExit(false);
}

function ArcadeV2Score()
{
	global $scripturl, $txt, $db_prefix, $context, $sourcedir, $modSettings, $smcFunc;

	checkSession('post');

	ob_end_clean();
	if (!empty($modSettings['enableCompressedOutput']))
		@ob_start('ob_gzhandler');
	else
		ob_start();

	$game = getGameInfo($_REQUEST['game']);

	if ($game === false)
		v2Error('invalid_game');

	$session = &$_SESSION['arcade_play_' . $game['id']];
	$extra = &$_SESSION['arcade_play_extra_' . $game['id']];

	if (empty($_POST['score']))
		v2Error('invalid_try');

	$score_hash = $_POST['score'] . $session['hash'] . $session['score_hash'];

	if ($score_hash != $_POST['secret'])
		v2Error('invalid_hash');
	if (!sha1($_REQUEST['game'] . $_POST['score']) == $session['request_id'])
		v2Error('invalid_hash');
	if (!compHsha($_POST['score_hash'], $_POST['score'] . '-' . $session['hash'] . '-' . $session['score_hash']))
		v2Error('invalid_hash');
	if (!compHsha($_POST['secret2'], $_POST['score'] . '-' . $score_hash))
		v2Error('invalid_hash');
	if (microtime_float() - $session['score_hash_time'] > 5)
		v2Error('invalid_try');

	$session['scores'][] = array(
		'maxTries' => $_POST['maxTries'],
		'endTime' => round($_POST['endTime'] / 1000),
		'startTime' => round($_POST['startTime'] / 1000),
		'serverTime' => time(),
		'playerName' => $_POST['playerName'],
		'try' => $_POST['tries'],
		'score' => $_POST['score'],
		'level' => $_POST['level'] != 'undefined' ? $_POST['level'] : '',
		'hash' => serialize(array(
			'2.5.0', $_REQUEST['game'], $_POST['score'], $session['hash'],
			$session['score_hash'], $_POST['secret'], $_POST['score_hash'], $_POST['secret2'],
		)),
	);

	$session['request_id'] = '';

	echo '&maxtry=', $session['maxTries'];

	obExit(false);
}

function compHash($hash1, $hash2)
{
	return round($hash1, 3) == round($hash2, 3);
}
function compHsha($hash1, $hash2)
{
	return $hash1 == sha1($_REQUEST['game'] . sha1($hash2));
}

function v2Error($error)
{
	// DEBUG
	log_error($error);

	obExit(false);
}
?>