<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.5
 * @license http://download.smfarcade.info/license.php New-BSD
 */

function template_arcade_above()
{
	global $scripturl, $txt, $context, $settings, $options;

	if (!empty($context['arcade_tabs']))
	{
		echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span class="floatleft">', $context['arcade_tabs']['title'], '</span>
			<img id="arcade_toggle" class="floatright" src="', $settings['images_url'], '/collapse.gif', '" alt="*" title="', $txt['upshrink_description'], '" align="bottom" style="margin: 10px 5px 0 1em; display: none;" />
		</h3>
	</div>
	<div id="arcade_panel" class="plainbox"', empty($options['arcade_panel_collapse']) ? '' : ' style="display: none;"', '>';

		if (!empty($context['arcade']['notice']))
			echo '
		<span class="arcade_notice">', $context['arcade']['notice'], '</span><br />';

		echo '
		<form action="', $scripturl, '?action=arcade;sa=search" method="post">
			<input id="gamesearch" style="width: 240px;" autocomplete="off" type="text" name="name" value="', isset($context['arcade_search']['name']) ? $context['arcade_search']['name'] : '', '" /> <input class="button_submit" type="submit" value="', $txt['arcade_search'], '" />
			<div id="suggest_gamesearch" class="game_suggest"></div>
			<div id="search_extra">
				<input type="checkbox" id="favorites" name="favorites" value="1"', !empty($context['arcade_search']['favorites']) ? ' checked="checked"' : '', ' class="check" /> <label for="favorites">', $txt['search_favorites'], '</label>
			</div>
			<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
				var gSuggest = new gameSuggest("', $context['session_id'], '", "gamesearch");
			// ]]></script>
		</form>
	</div>
	<div id="adm_submenus"><ul class="dropmenu">';

		// Print out all the items in this tab.
		foreach ($context['arcade_tabs']['tabs'] as $tab)
			echo '
		<li>
			<a href="', $tab['href'], '" class="', !empty($tab['is_selected']) ? 'active ' : '', 'firstlevel">
				<span class="firstlevel">', $tab['title'], '</span>
			</a>
		</li>';

		echo '
	</ul></div>
	<script type="text/javascript"><!-- // --><![CDATA[
		var oArcadeHeaderToggle = new smc_Toggle({
			bToggleEnabled: true,
			bCurrentlyCollapsed: ', empty($options['arcade_panel_collapse']) ? 'false' : 'true', ',
			aSwappableContainers: [
				\'arcade_panel\'
			],
			aSwapImages: [
				{
					sId: \'arcade_toggle\',
					srcExpanded: smf_images_url + \'/collapse.gif\',
					altExpanded: ', JavaScriptEscape($txt['upshrink_description']), ',
					srcCollapsed: smf_images_url + \'/expand.gif\',
					altCollapsed: ', JavaScriptEscape($txt['upshrink_description']), '
				}
			],
			oThemeOptions: {
				bUseThemeSettings: ', $context['user']['is_guest'] ? 'false' : 'true', ',
				sOptionName: \'arcade_panel_collapse\',
				sSessionVar: ', JavaScriptEscape($context['session_var']), ',
				sSessionId: ', JavaScriptEscape($context['session_id']), '
			},
			oCookieOptions: {
				bUseCookie: ', $context['user']['is_guest'] ? 'true' : 'false', ',
				sCookieName: \'arcadeupshrink\'
			}
		});
	// ]]></script>';
	}

	echo '
	<div id="arcade_top">';
}

function template_arcade_below()
{
	global $arcade_version;

	// Print out copyright and version. Removing copyright is not allowed by license
	echo '
	</div>

	<div id="arcade_bottom" class="smalltext" style="text-align: center;">
		Powered by: <a href="http://www.smfarcade.info/" target="_blank">SMF Arcade ', $arcade_version, '</a> &copy; <a href="http://www.madjoki.com/" target="_blank">Niko Pahajoki</a> 2004-2011
	</div>';

}

?>