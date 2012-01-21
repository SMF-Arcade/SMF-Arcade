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
	!!!
*/

function ArcadeIBPGetGame()
{
	if (empty($_POST['gname']))
		return false;
	
	return getGameInfo($_POST['gname']);
}

function ArcadeIBPSubmit(&$game, $session_info)
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $smcFunc;

	if (isset($_POST['gscore']) && is_numeric($_POST['gscore']))
		$score = (float) $_POST['gscore'];

	else
		return false;

	$cheating = CheatingCheck();

	return array(
		'cheating' => $cheating,
		'score' => $score,
		'start_time' => $session_info['start_time'],
		'duration' => time() - $session_info['start_time'],
		'end_time' => time(),
	);
}

function ArcadeIBPPlay(&$game, &$session)
{
	// We store this session to check cheating later
	$session = array(
		'game' => $game['internal_name'],
		'id' => $game['id'],
		'start_time' => time(),
		'done' => false,
		'score' => 0,
		'end_time' => 0,
	);
}

function ArcadeIBPXMLPlay(&$game, &$session)
{
	$session = array(
		'game' => $game['internal_name'],
		'id' => $game['id'],
		'start_time' => time(),
		'done' => false,
		'score' => 0,
		'end_time' => 0,
	);
	
	return true;
}

// v3
function ArcadeIBP3GetGame()
{
	if (empty($_SESSION['arcade']['ibp_game']))
		return false;
	
	return getGameInfo($_SESSION['arcade']['ibp_game']);
}

function ArcadeIBP3Submit(&$game, $session_info)
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $smcFunc;

	list ($hash1, $hash2, $endTime) = $_SESSION['arcade']['ibp_verify'];

	// No longer needed
	unset($_SESSION['arcade']['ibp_verify']);

	// How long it took? Must be more than 0 secods and less than 7 seconds (same as in IPB arcade)
	$time_taken = microtime_float() - $endTime;

	if ($time_taken < 0 || $time_taken > 7)
		return false;

	// Was score even submitted
	if (isset($_POST['gscore']) && is_numeric($_POST['gscore']))
		$score = (float) $_POST['gscore'];

	else
		return false;

	// Check 'hash'
	if (($score * $hash1 ^ $hash2) != $_POST['enscore'])
		return false;

	$cheating = CheatingCheck();

	return array(
		'cheating' => $cheating,
		'score' => $score,
		'start_time' => $session_info['start_time'],
		'duration' => round($endTime - $session_info['start_time'], 0),
		'end_time' => round($endTime, 0),
	);
}

function ArcadeIBP3Play(&$game, &$session)
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc;

	// We store this session to check cheating later
	$session = array(
		'game' => $game['internal_name'],
		'id' => $game['id'],
		'start_time' => time(),
		'done' => false,
		'score' => 0,
		'end_time' => 0,
	);

	$_SESSION['arcade']['ibp_game'] = $game['internal_name'];
}

function ArcadeIBP3XMLPlay(&$game, &$session)
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc;

	$session = array(
		'game' => $game['internal_name'],
		'id' => $game['id'],
		'start_time' => time(),
		'done' => false,
		'score' => 0,
		'end_time' => 0,
	);

	$_SESSION['arcade']['ibp_game'] = $game['internal_name'];

	return true;
}

// v3.2
function ArcadeIBP32GetGame()
{
	if (empty($_POST['gname']))
		return false;
	
	return getGameInfo($_POST['gname']);
}

function ArcadeIBP32Submit(&$game, $session_info)
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $smcFunc;

	list ($hash1, $hash2, $endTime) = $_SESSION['arcade']['ibp_verify'];

	// No longer needed
	unset($_SESSION['arcade']['ibp_verify']);

	// How long it took? Must be more than 0 secods and less than 7 seconds (same as in IPB arcade)
	$time_taken = microtime_float() - $endTime;

	if ($time_taken < 0 || $time_taken > 7)
		return false;

	// Was score even submitted
	if (isset($_POST['gscore']) && is_numeric($_POST['gscore']))
		$score = (float) $_POST['gscore'];

	else
		return false;

	// Check 'hash'
	if (($score * $hash1 ^ $hash2) != $_POST['enscore'])
		return false;

	$cheating = CheatingCheck();

	return array(
		'cheating' => $cheating,
		'score' => $score,
		'start_time' => $session_info['start_time'],
		'duration' => round($endTime - $session_info['start_time'], 0),
		'end_time' => round($endTime, 0),
	);
}

function ArcadeVerifyIBP()
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $smcFunc;

	$randomchar = rand(1, 200);
	$randomchar2 = rand(1, 200);

	$_SESSION['arcade']['ibp_verify'] = array($randomchar, $randomchar2, microtime_float());

	// We output flash vars no need for anything that might output something before or after this
	ob_end_clean();
	if (!empty($modSettings['enableCompressedOutput']))
		@ob_start('ob_gzhandler');
	else
		ob_start();

	echo '&randchar=', $randomchar, '&randchar2=', $randomchar2, '&savescore=1&blah=OK';

	obExit(false);
}

function ArcadeIBPHtml(&$game, $auto_start = true)
{
	global $txt, $context, $settings;

	echo '
	<script type="text/javascript" src="', $settings['default_theme_url'], '/scripts/swfobject.js" defer="defer"></script>
	<div id="game" style="margin: auto; width: ', $game['extra_data']['width'], 'px; height: ', $game['extra_data']['height'], 'px; ">
		', $txt['arcade_no_javascript'], '
	</div>

	<script language="JavaScript" type="text/javascript" defer="defer"><!-- // --><![CDATA[
		var play_url = smf_scripturl + "?action=arcade;sa=play;xml";
		var running = false;

		function arcadeRestart()
		{
			running = false;

			setInnerHTML(document.getElementById("game"), "', addslashes($txt['arcade_please_wait']), '");

			var i, x = new Array();

			x[0] = "game=', $game['id'] . '";
			x[1] = "', $context['session_var'], '=', $context['session_id'], '";

			arcadeAjaxSend(play_url, x.join("&"), ArcadeStart);

			return false;
		}

		function ArcadeStart()
		{
			if (running)
				return;

			running = true;

			setInnerHTML(document.getElementById("game"), "', addslashes($txt['arcade_no_flash']), '");

			var so = new SWFObject("' , $game['url']['flash'], '", "', $game['file'], '", "', $game['extra_data']['width'], '", "', $game['extra_data']['height'], '", "7");
			so.addParam("menu", "false");
			so.write("game");

			return true;
		}

		', $auto_start ? 'addLoadEvent(arcadeRestart);' : '', '
	// ]]></script>';
}

?>