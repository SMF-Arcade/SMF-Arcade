<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6
 * @license http://download.smfarcade.info/license.php New-BSD
 */

/**
 *
 */
abstract class Arcade_Game_Flash extends Arcade_Game
{
	/**
	 *
	 */
	public function Render()
	{
		global $context, $settings, $txt;
		
		echo '
		<script type="text/javascript" src="', $settings['default_theme_url'], '/scripts/swfobject.js" defer="defer"></script>
		<script language="JavaScript" type="text/javascript" defer="defer"><!-- // --><![CDATA[
			var play_url = smf_scripturl + "?action=arcade;sa=play;xml";
			var running = false;
	
			function arcadeRestart()
			{
				running = false;
	
				setInnerHTML(document.getElementById("game"), "', addslashes($txt['arcade_please_wait']), '");
	
				var i, x = new Array();
	
				x[0] = "game=', $this->game['id'] . '";
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
	
				var so = new SWFObject("' , $this->game['url']['flash'], '", "', $this->game['file'], '", "', $this->game['extra_data']['width'], '", "', $this->game['extra_data']['height'], '", "7");
				so.addParam("menu", "false");
				so.write("game");
	
				return true;
			}
	
			', $auto_start ? 'addLoadEvent(arcadeRestart);' : '', '
		// ]]></script>
		<div id="game" style="margin: auto; width: ', $this->game['extra_data']['width'], 'px; height: ', $this->game['extra_data']['height'], 'px; ">
			', $txt['arcade_no_javascript'], '
		</div>';
	}
}