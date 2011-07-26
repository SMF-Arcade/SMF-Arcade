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

function ArcadeVbPlay(&$game, &$session_info)
{
	global $scripturl, $context, $modSettings, $smcFunc;

}

function ArcadeVbXMLPlay(&$game, &$session_info)
{
	global $scripturl, $context, $modSettings, $smcFunc;

}

function ArcadeVbStart()
{
	global $scripturl, $context, $modSettings, $smcFunc;

	if (!($game = getGameInfo($_POST['gamename'])))
		return false;

	$time = time();
	$gamerand = rand(1, 10);
	$lastid = rand(0, 234);

	$_SESSION['arcade_play_' . $game['id']] = array(
		'game' => $game['internal_name'],
		'id' => $game['id'],
		'start_time' => $time,
		'done' => false,
		'score' => 0,
		'end_time' => 0,
		'initbar' => $gamerand,
		'last_id' => $lastid,
	);
	$_SESSION['arcade_play_vb3g'][$lastid] = $game['id'];

	ob_end_clean();
	if (!empty($modSettings['enableCompressedOutput']))
		@ob_start('ob_gzhandler');
	else
		ob_start();

	echo '&connStatus=1&initbar=', $gamerand, '&gametime=', $time, '&lastid=', $lastid, '&result=OK';

	obExit(false);
}

function ArcadeVbPermRequest()
{
	global $scripturl, $context, $modSettings, $smcFunc;

	if (!($game = getGameInfo($_SESSION['arcade_play_vb3g'][$_POST['id']])))
		return false;

	$session = &$_SESSION['arcade_play_' . $game['id']];

	$noteid = $_POST['note'] / ($_POST['fakekey'] * ceil($_POST['score']));

	if ($_POST['id'] != $session['last_id'] || $noteid != $_POST['id'] || $_POST['gametime'] != $session['start_time'])
	{
		ob_end_clean();
		if (!empty($modSettings['enableCompressedOutput']))
			@ob_start('ob_gzhandler');
		else
			ob_start();

		echo '&validate=0';

		obExit(false);
	}

	$session['end_time'] = microtime_float();
	$session['score'] = $_POST['score'];

	ob_end_clean();
	if (!empty($modSettings['enableCompressedOutput']))
		@ob_start('ob_gzhandler');
	else
		ob_start();

	echo '&validate=1&microone=', microtime_float(), '&result=OK';

	obExit(false);
}

function ArcadeVbGetGame()
{
	global $scripturl, $context, $modSettings, $smcFunc;

	return getGameInfo($_SESSION['arcade']['play_vb3g'][$_POST['id']]);
}

function ArcadeVbSubmit(&$game, $session)
{
	global $scripturl, $context, $modSettings, $smcFunc;

	$diff = (microtime_float() - $session['end_time']);

	if ($diff > 4500 || !empty($session['ping']))
		return false;

	$session['ping'] = true;

	$cheating = CheatingCheck();

	unset($_SESSION['arcade_play_vb3g'][$_POST['id']]);

	return array(
		'cheating' => $cheating,
		'score' => $session['score'],
		'start_time' => $session['start_time'],
		'duration' => round($session['end_time'] - $session['start_time'], 0),
		'end_time' => round($session['end_time'], 0),
	);
}

function ArcadeVbHtml(&$game, $auto_start = true)
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