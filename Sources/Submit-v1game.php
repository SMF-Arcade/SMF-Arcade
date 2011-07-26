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

/*
	!!!
*/

// Get Game
function ArcadeV1GetGame()
{
	//if (!isset($_SESSION['arcade_v1game'][$_POST['game']]))
	//	return false;

	return GetGameInfo($_POST['game']);
}

// Get Score
function ArcadeV1Submit(&$game, $session_info)
{
	if (isset($_POST['score']) && is_numeric($_POST['score']))
		$score = (float) $_POST['score'];
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

function ArcadeV1Play(&$game, &$session_info)
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc;

	//$_SESSION['arcade_v1game'][$game['internal_name']] = $game['id'];

	// We store this session to check cheating later
	$session_info = array(
		'game' => $game['internal_name'],
		'id' => $game['id'],
		'start_time' => time(),
		'done' => false,
		'score' => 0,
		'end_time' => 0,
	);
}

function ArcadeV1XMLPlay(&$game, &$session_info)
{
	global $scripturl, $txt, $db_prefix, $context, $smcFunc;

	// We store this session to check cheating later
	$session_info = array(
		'game' => $game['internal_name'],
		'id' => $game['id'],
		'start_time' => time(),
		'done' => false,
		'score' => 0,
		'end_time' => 0,
	);

	return true;
}

function ArcadeV1Html(&$game, $auto_start = true)
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